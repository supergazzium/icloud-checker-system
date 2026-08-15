<?php

namespace App\Services;

class IFreeICloudService
{
    private string $apiKey;
    private string $username;
    private string $endpoint;
    private int    $timeout;

    public function __construct()
    {
        $this->apiKey   = config('ifreeicloud.ifreeicloud.key');
        $this->endpoint = config('ifreeicloud.ifreeicloud.endpoint', 'https://api.ifreeicloud.co.uk');
        $this->timeout  = config('ifreeicloud.ifreeicloud.timeout', 60);
    }

    public function check(int $serviceId, string $imeiSerial): array
    {
        $start = microtime(true);
        $payload = ['service' => $serviceId, 'imei' => trim($imeiSerial), 'key' => $this->apiKey];

        $ch = curl_init($this->endpoint);
        curl_setopt($ch, CURLOPT_POSTFIELDS,     $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT,        $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $ms = (int) round((microtime(true) - $start) * 1000);

        if ($raw === false || $curlErr) {
            return ['success' => false, 'error' => 'Connection error: '.$curlErr, 'http_code' => 0, 'duration_ms' => $ms, 'raw' => null];
        }
        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP Error: {$httpCode}", 'http_code' => $httpCode, 'duration_ms' => $ms, 'raw' => $raw];
        }

        $result = json_decode($raw);
        if (!$result || $result->success !== true) {
            return ['success' => false, 'error' => $result->error ?? 'Unknown API error', 'http_code' => $httpCode, 'duration_ms' => $ms, 'raw' => $raw];
        }

        return [
            'success'     => true,
            'response'    => $result->response ?? '',
            'object'      => $result->object   ?? null,
            'parsed'      => $this->parseObject($result->object ?? null),
            'http_code'   => $httpCode,
            'duration_ms' => $ms,
            'raw'         => $raw,
        ];
    }

    public function parseObject(?object $obj): array
    {
        if (!$obj) return [];
        return [
            'model'           => $this->pick($obj, ['model','Model']),
            'serial'          => $this->pick($obj, ['serial','serialNumber']),
            'imei'            => $this->pick($obj, ['imei','imei2','IMEI']),
            'color'           => $this->pick($obj, ['color','Color','colour']),
            'storage'         => $this->pick($obj, ['storage','capacity','Capacity']),
            'region'          => $this->pick($obj, ['region','country','Country']),
            'fmi_status'      => $this->pickBool($obj, ['fmiOn','fmi','findmy','FindMy','icloud','iCloud','find_my'], 'ON', 'OFF'),
            'activation_status' => $this->pickBool($obj, ['activated','activation','activationStatus','ActivationStatus'], 'Activated', 'Not Activated'),
            // Provider uses `lostMode` on Apple GSX-style responses. Older
            // aliases kept for other services.
            'blacklist_status' => $this->pickBool($obj, ['lostMode','blacklistStatus','blacklist','Blacklist','gsma','lost','stolen'], 'Blacklisted', 'Clean'),
            // Fall back to carrier ONLY when there's no simlock field at all;
            // empty string means "not applicable" (Mac WiFi-only) and should
            // stay empty, not surface as carrier name.
            'simlock_status'  => $this->pick($obj, ['simLock','simlock','sim_lock','SimLock','simlockStatus']) ?? $this->pick($obj, ['carrier']),
            // Provider uses `mdmLock` on Apple responses.
            'mdm_status'      => $this->pickBool($obj, ['mdmLock','mdm','MDM','remoteManagement'], 'Enrolled', 'Not Enrolled'),
            'replaced_status' => $this->pickBool($obj, ['replaced','Replaced'], 'Yes', 'No'),
            'warranty'        => $this->pick($obj, ['warrantyStatus','warranty','Warranty']),
            'purchase_date'   => $this->pick($obj, ['estPurchaseDate','purchase_date','purchaseDate','soldDate']),
            'carrier'         => $this->pick($obj, ['carrier','network','Network','networkCarrier']),
        ];
    }

    /** Merge a second check's parsed fields into the first, filling only blanks. */
    public function mergeResults(array $a, array $b): array
    {
        if (!$a['success'] && !$b['success']) return $a;
        if (!$a['success']) return $b;
        if (!$b['success']) return $a;

        $merged = $a['parsed'] ?? [];
        foreach (($b['parsed'] ?? []) as $k => $v) {
            if (($merged[$k] ?? null) === null && $v !== null) $merged[$k] = $v;
        }

        return [
            'success'     => true,
            'response'    => ($a['response'] ?? '').'<br>'.($b['response'] ?? ''),
            'object'      => $a['object'] ?? null,
            'parsed'      => $merged,
            'http_code'   => $a['http_code'] ?? 200,
            'duration_ms' => ($a['duration_ms'] ?? 0) + ($b['duration_ms'] ?? 0),
            'raw'         => json_encode(['first' => json_decode($a['raw'] ?? 'null'), 'second' => json_decode($b['raw'] ?? 'null')]),
        ];
    }

    public function getOverallStatus(array $parsed): string
    {
        $fmi   = strtolower($parsed['fmi_status']        ?? '');
        $act   = strtolower($parsed['activation_status'] ?? '');
        $bl    = strtolower($parsed['blacklist_status']  ?? '');

        if (in_array($fmi, ['on','enabled','yes','active','true']))                   return 'locked';
        if (in_array($bl,  ['blacklisted','lost','stolen','blocked','yes']))           return 'blacklisted';
        if (in_array($fmi, ['off','disabled','no','inactive','false']))               return 'clean';
        return 'unknown';
    }

    private function pick(object $obj, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (! isset($obj->$k)) continue;
            $v = $obj->$k;
            if ($v === null || $v === '' || $v === false) continue;
            return (string) $v;
        }
        return null;
    }

    private function pickBool(object $obj, array $keys, string $trueLabel, string $falseLabel): ?string
    {
        foreach ($keys as $k) {
            if (! isset($obj->$k) || $obj->$k === null || $obj->$k === '') continue;
            $v = $obj->$k;
            if (is_bool($v))    return $v ? $trueLabel : $falseLabel;
            if (is_int($v))     return $v ? $trueLabel : $falseLabel;
            $s = strtolower((string) $v);
            if (in_array($s, ['1','true','yes','on','y','t'], true))  return $trueLabel;
            if (in_array($s, ['0','false','no','off','n','f'], true)) return $falseLabel;
            // Provider-supplied text ("Activated", "Blacklisted", etc.) — pass through as-is.
            return (string) $v;
        }
        return null;
    }
}
