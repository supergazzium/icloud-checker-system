<?php

namespace App\Services;

class OmiseService
{
    private string $secretKey;
    private string $endpoint;

    public function __construct()
    {
        $this->secretKey = config('omise.secret_key') ?? '';
        $this->endpoint  = config('omise.endpoint', 'https://api.omise.co');
    }

    /** Create a hosted payment link (Omise "Links"). Amount in THB (baht). */
    public function createLink(float $amountBaht, string $title): array
    {
        $payload = [
            'amount'      => (int) round($amountBaht * 100), // satang
            'currency'    => 'thb',
            'title'       => $title,
            'description' => $title,
            'multiple'    => 'false',
        ];
        return $this->request('POST', '/links', $payload);
    }

    /** Create a card charge via a tokenized card (Omise.js). Amount in THB. */
    public function createCharge(float $amountBaht, string $token, string $returnUri, string $description): array
    {
        $payload = [
            'amount'      => (int) round($amountBaht * 100),
            'currency'    => 'thb',
            'card'        => $token,
            'return_uri'  => $returnUri,
            'description' => $description,
        ];
        return $this->request('POST', '/charges', $payload);
    }

    /** Create a PromptPay QR charge. Returns charge data incl. `source.scannable_code.image.download_uri`. */
    public function createPromptPayCharge(float $amountBaht, string $description): array
    {
        $source = $this->request('POST', '/sources', [
            'amount'   => (int) round($amountBaht * 100),
            'currency' => 'thb',
            'type'     => 'promptpay',
        ]);
        if (!$source['success']) return $source;

        return $this->request('POST', '/charges', [
            'amount'      => (int) round($amountBaht * 100),
            'currency'    => 'thb',
            'source'      => $source['data']['id'],
            'description' => $description,
        ]);
    }

    public function getLink(string $linkId): array
    {
        return $this->request('GET', '/links/'.$linkId);
    }

    public function getCharge(string $chargeId): array
    {
        return $this->request('GET', '/charges/'.$chargeId);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        $ch = curl_init($this->endpoint.$path);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey.':');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        }
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $err) {
            return ['success' => false, 'error' => 'Connection error: '.$err];
        }
        $data = json_decode($raw, true);
        if ($httpCode >= 400 || !$data) {
            return ['success' => false, 'error' => $data['message'] ?? "HTTP {$httpCode}", 'raw' => $data];
        }
        return ['success' => true, 'data' => $data];
    }
}
