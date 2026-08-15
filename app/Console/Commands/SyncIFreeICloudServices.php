<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AdminAuditLog;
use App\Models\Service;
use App\Services\IFreeiCloud\IFreeiCloudClient;
use App\Services\IFreeiCloud\IFreeiCloudException;
use App\Services\IFreeiCloud\ServiceImportHelpers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncIFreeICloudServices extends Command
{
    protected $signature = 'ifreeicloud:sync-services
        {--dry-run : Only print what would happen; do not touch the DB.}
        {--rate= : Override USD→THB rate (defaults to config).}
        {--markup= : Override sell-price multiplier (defaults to config).}
        {--only= : Comma-separated provider IDs to sync (defaults to all).}';

    protected $description = 'Sync services from iFreeICloud into the local catalogue. Idempotent.';

    public function handle(IFreeiCloudClient $client): int
    {
        $rate = (float) ($this->option('rate')
            ?? config('ifreeicloud.ifreeicloud.usd_to_thb', 36.00));
        $markup = (float) ($this->option('markup')
            ?? config('ifreeicloud.ifreeicloud.default_markup', 2.5));

        $only = null;
        if ($this->option('only')) {
            $only = array_map('intval', explode(',', (string) $this->option('only')));
        }

        $this->line("Rate: ฿{$rate}/USD  Markup: {$markup}x  Dry run: "
            .($this->option('dry-run') ? 'yes' : 'no'));

        try {
            $list = $client->serviceList(true);
        } catch (IFreeiCloudException $e) {
            $this->error('Provider error: '.$e->getMessage());
            return self::FAILURE;
        }

        if ($list === []) {
            $this->warn('Provider returned no services.');
            return self::SUCCESS;
        }

        $localByPid = Service::all()->keyBy('provider_service_id');

        $created = 0; $updated = 0; $unchanged = 0; $skipped = 0;
        $affected = [];
        $rows = [];

        foreach ($list as $ps) {
            $pid = $ps['provider_service_id'];
            if ($only !== null && ! in_array($pid, $only, true)) {
                continue;
            }
            $prices = ServiceImportHelpers::calculatePrices((float) $ps['usd_price'], $rate, $markup);
            $local  = $localByPid[$pid] ?? null;
            $verb   = $local === null ? 'CREATE' : 'UPDATE';

            // If local exists AND cost_price + provider_price_usd are unchanged,
            // treat as no-op for reporting purposes.
            if ($local !== null
                && (float) $local->cost_price === (float) $prices['cost_price']
                && (float) $local->provider_price_usd === (float) $ps['usd_price']) {
                $verb = 'UNCHANGED';
            }

            $rows[] = [
                $pid,
                mb_strimwidth(ServiceImportHelpers::cleanName($ps['name']), 0, 40, '…'),
                '$'.number_format($ps['usd_price'], 2),
                '฿'.number_format($prices['cost_price'], 2),
                '฿'.number_format($prices['sell_price'], 0),
                $verb,
            ];

            if ($this->option('dry-run')) {
                if ($verb === 'CREATE')  $created++;
                if ($verb === 'UPDATE')  $updated++;
                if ($verb === 'UNCHANGED') $unchanged++;
                continue;
            }

            if ($verb === 'CREATE') {
                Service::create([
                    'name_th'                  => ServiceImportHelpers::cleanName($ps['name']),
                    'name_en'                  => ServiceImportHelpers::cleanName($ps['name']),
                    'description_en'           => $ps['description'] ?: null,
                    'provider_service_id'      => $pid,
                    'device_type'              => ServiceImportHelpers::inferDeviceType($ps['name']),
                    'cost_price'               => $prices['cost_price'],
                    'sell_price'               => $prices['sell_price'],
                    'processing_time'          => $ps['processing_time'] ?: 'Instant',
                    'supports_serial'          => $ps['supports_serial'] !== null ? (int) $ps['supports_serial'] : 1,
                    'active'                   => 0,
                    'sort_order'               => (int) (Service::max('sort_order') ?? 0) + 1,
                    'provider_price_usd'       => $ps['usd_price'],
                    'provider_processing_time' => $ps['processing_time'] ?: null,
                    'provider_supports_serial' => $ps['supports_serial'] !== null ? (int) $ps['supports_serial'] : null,
                    'provider_synced_at'       => now(),
                    'provider_missing_at'      => null,
                ]);
                $created++;
                $affected[] = $pid;
            } elseif ($verb === 'UPDATE') {
                $local->update([
                    'cost_price'               => $prices['cost_price'],
                    'provider_price_usd'       => $ps['usd_price'],
                    'provider_processing_time' => $ps['processing_time'] ?: null,
                    'provider_supports_serial' => $ps['supports_serial'] !== null ? (int) $ps['supports_serial'] : null,
                    'provider_synced_at'       => now(),
                    'provider_missing_at'      => null,
                ]);
                $updated++;
                $affected[] = $pid;
            } else {
                // UNCHANGED — still refresh provider_synced_at for freshness tracking.
                $local->update(['provider_synced_at' => now(), 'provider_missing_at' => null]);
                $unchanged++;
            }
        }

        // Mark local services missing at provider (skipped when --only filter is on).
        if ($only === null && ! $this->option('dry-run')) {
            $currentIds = array_column($list, 'provider_service_id');
            Service::whereNotIn('provider_service_id', $currentIds)
                ->whereNull('provider_missing_at')
                ->update(['provider_missing_at' => now()]);
        }

        $this->table(['ID', 'Name', 'USD', 'Cost ฿', 'Sell ฿', 'Action'], $rows);
        $this->line("Summary: created={$created}  updated={$updated}  unchanged={$unchanged}  skipped={$skipped}");

        if (! $this->option('dry-run') && ($created + $updated) > 0) {
            AdminAuditLog::record(
                'services.import.cli-sync',
                'services',
                null,
                ['created' => $created, 'updated' => $updated, 'rate' => $rate, 'markup' => $markup, 'ids' => $affected],
            );
        }

        return self::SUCCESS;
    }
}
