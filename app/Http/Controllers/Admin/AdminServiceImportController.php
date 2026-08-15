<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Service;
use App\Services\IFreeiCloud\IFreeiCloudClient;
use App\Services\IFreeiCloud\IFreeiCloudException;
use App\Services\IFreeiCloud\ServiceImportHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminServiceImportController extends Controller
{
    public function __construct(private IFreeiCloudClient $client) {}

    /**
     * Browse the provider catalogue side-by-side with the local one.
     * Marks each provider service as either already-imported (with a
     * pointer to the local row) or importable.
     *
     * `?refresh=1` invalidates the cached servicelist.
     * `?q=xxx` filters by name (case-insensitive substring).
     */
    public function index(Request $request)
    {
        $rate   = (float) $request->query('rate',
            (float) config('ifreeicloud.ifreeicloud.usd_to_thb', 36.00));
        $markup = (float) $request->query('markup',
            (float) config('ifreeicloud.ifreeicloud.default_markup', 2.5));
        $search = trim((string) $request->query('q', ''));

        // Mark any locally-imported services whose provider_service_id
        // no longer appears in the provider list — a soft "missing" flag.
        $providerError = null;
        $providerServices = [];
        try {
            $providerServices = $this->client->serviceList((bool) $request->query('refresh'));
        } catch (IFreeiCloudException $e) {
            $providerError = $e->getMessage();
        }

        $providerIds = array_column($providerServices, 'provider_service_id');
        if ($providerServices !== [] && $providerIds !== []) {
            $this->reconcileMissingFlags($providerIds);
        }

        // Local services keyed by provider_service_id for quick lookup.
        $localByProviderId = Service::query()
            ->orderBy('sort_order')
            ->get()
            ->keyBy('provider_service_id');

        // Enrich each provider row with local + preview pricing.
        $rows = [];
        foreach ($providerServices as $ps) {
            if ($search !== '' && stripos($ps['name'], $search) === false
                && (string) $ps['provider_service_id'] !== $search) {
                continue;
            }
            $local  = $localByProviderId[$ps['provider_service_id']] ?? null;
            $prices = ServiceImportHelpers::calculatePrices((float) $ps['usd_price'], $rate, $markup);
            $rows[] = [
                'provider_service_id' => $ps['provider_service_id'],
                'name'                => $ps['name'],
                'clean_name'          => ServiceImportHelpers::cleanName($ps['name']),
                'inferred_device'     => ServiceImportHelpers::inferDeviceType($ps['name']),
                'usd_price'           => $ps['usd_price'],
                'processing_time'     => $ps['processing_time'],
                'supports_serial'     => $ps['supports_serial'],
                'description'         => $ps['description'],
                'preview_cost'        => $prices['cost_price'],
                'preview_sell'        => $prices['sell_price'],
                'local'               => $local,
            ];
        }

        return view('admin.services.import', [
            'rows'           => $rows,
            'rate'           => $rate,
            'markup'         => $markup,
            'search'         => $search,
            'providerError'  => $providerError,
            'importedCount'  => $localByProviderId->count(),
            'providerCount'  => count($providerServices),
            'missingLocally' => Service::whereNotNull('provider_missing_at')->count(),
        ]);
    }

    /**
     * Upsert the selected provider services into the local catalogue.
     * Rules:
     *   - Match by provider_service_id.
     *   - Insert new rows as INACTIVE (nothing goes live by accident).
     *   - On update: refresh cost_price + provider_* metadata.
     *   - NEVER overwrite sell_price, name_th, description_th, or active.
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'provider_ids'   => 'required|array|min:1',
            'provider_ids.*' => 'integer',
            'rate'           => 'required|numeric|min:0.01|max:1000',
            'markup'         => 'required|numeric|min:1|max:20',
        ]);

        $wanted = array_values(array_unique(array_map('intval', $validated['provider_ids'])));
        $rate   = (float) $validated['rate'];
        $markup = (float) $validated['markup'];

        // Fetch provider list once and filter to selection.
        try {
            $providerServices = $this->client->serviceList();
        } catch (IFreeiCloudException $e) {
            return back()->with('error', 'ไม่สามารถโหลดรายการจากผู้ให้บริการ: '.$e->getMessage());
        }
        $byId = [];
        foreach ($providerServices as $ps) {
            $byId[$ps['provider_service_id']] = $ps;
        }

        $created = 0; $updated = 0; $skipped = 0;
        $affected = [];

        DB::transaction(function () use ($wanted, $byId, $rate, $markup, &$created, &$updated, &$skipped, &$affected) {
            foreach ($wanted as $pid) {
                if (! isset($byId[$pid])) {
                    $skipped++;
                    continue;
                }
                $ps     = $byId[$pid];
                $prices = ServiceImportHelpers::calculatePrices((float) $ps['usd_price'], $rate, $markup);
                $name   = ServiceImportHelpers::cleanName($ps['name']);
                $device = ServiceImportHelpers::inferDeviceType($ps['name']);

                $local = Service::where('provider_service_id', $pid)->first();

                if ($local === null) {
                    Service::create([
                        'name_th'                  => $name, // admin translates later
                        'name_en'                  => $name,
                        'description_th'           => null,
                        'description_en'           => $ps['description'] ?: null,
                        'provider_service_id'      => $pid,
                        'device_type'              => $device,
                        'cost_price'               => $prices['cost_price'],
                        'sell_price'               => $prices['sell_price'],
                        'processing_time'          => $ps['processing_time'] ?: 'Instant',
                        'supports_serial'          => $ps['supports_serial'] !== null
                            ? (int) $ps['supports_serial']
                            : 1,
                        'active'                   => 0, // start inactive on import
                        'sort_order'               => (int) (Service::max('sort_order') ?? 0) + 1,
                        'provider_price_usd'       => $ps['usd_price'],
                        'provider_processing_time' => $ps['processing_time'] ?: null,
                        'provider_supports_serial' => $ps['supports_serial'] !== null
                            ? (int) $ps['supports_serial']
                            : null,
                        'provider_synced_at'       => now(),
                        'provider_missing_at'      => null,
                    ]);
                    $created++;
                } else {
                    // Refresh only provider-tracked fields + cost_price.
                    // Preserve admin-editable fields.
                    $local->update([
                        'cost_price'               => $prices['cost_price'],
                        'provider_price_usd'       => $ps['usd_price'],
                        'provider_processing_time' => $ps['processing_time'] ?: null,
                        'provider_supports_serial' => $ps['supports_serial'] !== null
                            ? (int) $ps['supports_serial']
                            : null,
                        'provider_synced_at'       => now(),
                        'provider_missing_at'      => null,
                    ]);
                    $updated++;
                }
                $affected[] = $pid;
            }
        });

        AdminAuditLog::record(
            'services.import.sync',
            'services',
            null,
            [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'rate'    => $rate,
                'markup'  => $markup,
                'ids'     => $affected,
            ],
        );

        $msg = "นำเข้าบริการเรียบร้อย: สร้างใหม่ {$created} รายการ, อัปเดต {$updated} รายการ";
        if ($skipped > 0) {
            $msg .= ", ข้าม {$skipped} รายการ (ไม่พบในผู้ให้บริการ)";
        }
        return redirect()->route('admin.services.import.index')->with('success', $msg);
    }

    /**
     * Set provider_missing_at on any local service whose provider_service_id
     * no longer appears in the current provider list; clear it on any that
     * came back.
     *
     * @param  int[]  $currentProviderIds
     */
    private function reconcileMissingFlags(array $currentProviderIds): void
    {
        Service::whereIn('provider_service_id', $currentProviderIds)
            ->whereNotNull('provider_missing_at')
            ->update(['provider_missing_at' => null]);

        Service::whereNotIn('provider_service_id', $currentProviderIds)
            ->whereNull('provider_missing_at')
            ->update(['provider_missing_at' => now()]);
    }
}
