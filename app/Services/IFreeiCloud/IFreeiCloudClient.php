<?php

declare(strict_types=1);

namespace App\Services\IFreeiCloud;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Talks to https://api.ifreeicloud.co.uk. Every call POSTs a form-encoded
 * body with `key` (the API key) plus the operation-specific fields, and
 * expects `{ success: true, response, object }` on success.
 *
 * Callers of `serviceList()` benefit from a short cache so the importer
 * screen doesn't hammer the provider on refresh.
 */
class IFreeiCloudClient
{
    private const CACHE_KEY_SERVICELIST = 'ifreeicloud.servicelist';

    private string $apiKey;
    private string $endpoint;
    private int    $timeout;
    private int    $servicelistTtl;

    public function __construct()
    {
        $this->apiKey         = (string) config('ifreeicloud.ifreeicloud.key', '');
        $this->endpoint       = (string) config('ifreeicloud.ifreeicloud.endpoint', 'https://api.ifreeicloud.co.uk');
        $this->timeout        = (int)    config('ifreeicloud.ifreeicloud.timeout', 60);
        $this->servicelistTtl = (int)    config('ifreeicloud.ifreeicloud.servicelist_cache_seconds', 3600);
    }

    /**
     * Fetch and normalise the provider's service catalogue.
     *
     * Returns a list of associative arrays:
     * [{
     *     'provider_service_id' => int,
     *     'name'                => string,
     *     'usd_price'           => float,
     *     'processing_time'     => string,
     *     'supports_serial'     => ?bool,
     *     'description'         => string,
     *  }, ...]
     *
     * The provider returns fields with varied casing across services; we
     * normalise the shape here so downstream code doesn't guess.
     *
     * @return array<int, array{provider_service_id:int, name:string, usd_price:float, processing_time:string, supports_serial:?bool, description:string}>
     */
    public function serviceList(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY_SERVICELIST);
        }

        return Cache::remember(self::CACHE_KEY_SERVICELIST, $this->servicelistTtl, function () {
            $data = $this->post(['accountinfo' => 'servicelist']);

            // The provider returns $result->object as either a list or
            // an object keyed by ID. Normalise to a list.
            $raw = $data['object'] ?? [];
            if (is_object($raw)) {
                $raw = (array) $raw;
            }
            if (! is_array($raw)) {
                return [];
            }

            $out = [];
            foreach ($raw as $item) {
                $arr = is_object($item) ? (array) $item : (array) $item;
                // Real provider field for the ID is `service` (not `id`/`ID`).
                $id = (int) ($arr['service'] ?? $arr['ID'] ?? $arr['id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $out[] = [
                    'provider_service_id' => $id,
                    'name'                => (string) ($arr['name'] ?? $arr['Name'] ?? ''),
                    'usd_price'           => (float)  ($arr['price'] ?? $arr['Price'] ?? 0),
                    'processing_time'     => (string) ($arr['time'] ?? $arr['Time'] ?? $arr['ProcessingTime'] ?? ''),
                    // Provider uses `snSupport` (serial-number support).
                    'supports_serial'     => self::coerceBoolish(
                        $arr['snSupport'] ?? $arr['Serial'] ?? $arr['serial'] ?? $arr['SupportsSerial'] ?? null
                    ),
                    'description'         => (string) ($arr['description'] ?? $arr['Description'] ?? $arr['Info'] ?? $arr['info'] ?? ''),
                ];
            }
            return $out;
        });
    }

    public function balance(): float
    {
        $data = $this->post(['accountinfo' => 'balance']);

        // Provider returns balance in `response` (string) or `object` (number).
        $raw = $data['object'] ?? $data['response'] ?? 0;
        if (is_object($raw)) {
            $raw = ($raw->balance ?? $raw->Balance ?? 0);
        }
        return (float) preg_replace('/[^\d.\-]/', '', (string) $raw);
    }

    /**
     * @return array{success:true, response:string, object:mixed}
     */
    public function check(int $serviceId, string $imei): array
    {
        $data = $this->post([
            'service' => $serviceId,
            'imei'    => trim($imei),
        ]);
        return $data + ['success' => true];
    }

    /**
     * Perform the HTTP call and apply the three-branch error contract.
     * Never returns on failure — always throws IFreeiCloudException with
     * enough context for the caller to log or surface.
     *
     * @param  array<string,scalar>  $payload
     * @return array<string,mixed>  Parsed JSON response body.
     */
    private function post(array $payload): array
    {
        if ($this->apiKey === '') {
            throw new IFreeiCloudException(
                'IFREEICLOUD_KEY is not configured.',
                0,
                'missing_api_key',
            );
        }

        $payload['key'] = $this->apiKey;

        try {
            $response = Http::asForm()
                ->timeout($this->timeout)
                ->connectTimeout($this->timeout)
                ->post($this->endpoint, $payload);
        } catch (ConnectionException $e) {
            throw new IFreeiCloudException(
                'Could not reach iFreeICloud: '.$e->getMessage(),
                0,
                'connection_error',
                $e,
            );
        }

        $status = $response->status();
        if ($status !== 200) {
            throw new IFreeiCloudException(
                "iFreeICloud returned HTTP {$status}.",
                $status,
                'http_error',
            );
        }

        $body = $response->json();
        if (! is_array($body)) {
            throw new IFreeiCloudException(
                'iFreeICloud returned a non-JSON body.',
                $status,
                'invalid_json',
            );
        }

        if (($body['success'] ?? false) !== true) {
            $providerError = (string) ($body['error'] ?? 'unknown_error');
            throw new IFreeiCloudException(
                "iFreeICloud API error: {$providerError}",
                $status,
                $providerError,
            );
        }

        return $body;
    }

    /**
     * Providers vary between 1/0, "1"/"0", true/false, "Yes"/"No" for
     * boolean-ish fields. Anything that looks truthy → true, anything
     * that looks falsy → false, absent → null.
     */
    private static function coerceBoolish(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_bool($value)) {
            return $value;
        }
        $s = strtolower((string) $value);
        if (in_array($s, ['1', 'true', 'yes', 'y', 'on'], true)) {
            return true;
        }
        if (in_array($s, ['0', 'false', 'no', 'n', 'off'], true)) {
            return false;
        }
        return null;
    }
}
