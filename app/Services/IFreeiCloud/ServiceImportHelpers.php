<?php

declare(strict_types=1);

namespace App\Services\IFreeiCloud;

/**
 * Pure helpers used by the importer. Kept static and side-effect-free so
 * they can be unit-tested without any container/config/DB.
 */
final class ServiceImportHelpers
{
    /**
     * Strip decorative emoji / symbol runs from a provider service name
     * so it renders cleanly in the admin UI and customer-facing pages.
     * Preserves letters (Latin + Thai + other scripts), digits, common
     * punctuation, and whitespace. Collapses repeated whitespace.
     *
     * Step order matters:
     *   1. Decode HTML entities (`&#128680;` → 🚨) so we can strip them.
     *   2. Kill any lingering unencoded entity fragments (`&12345`).
     *   3. Strip everything outside allowed Unicode categories.
     */
    public static function cleanName(string $raw): string
    {
        // Decode HTML entities so `&#128680;` becomes the actual emoji,
        // which then gets stripped in the character class below.
        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Kill unencoded entity remnants like `&128680` (missing `#` or `;`)
        // that the provider sometimes emits.
        $decoded = preg_replace('/&#?\w+;?/u', '', $decoded) ?? $decoded;
        // Drop everything outside Letters, Marks, Numbers, and a small
        // punctuation set. `\p{L}` = letters, `\p{M}` = combining marks,
        // `\p{N}` = numbers. `u` flag = Unicode-aware.
        $stripped = preg_replace('/[^\p{L}\p{M}\p{N}\s\-\+\(\)\/\.,:\']/u', '', $decoded) ?? $decoded;
        // Collapse whitespace and trim.
        $collapsed = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;
        return trim($collapsed);
    }

    /**
     * Infer device_type from a service name using keyword precedence.
     * Order matters — "MacBook" beats "Mac", "iPad" beats "Apple", etc.
     * Multi-brand or unknown → "all".
     *
     * Returns one of: 'iphone' | 'ipad' | 'macbook' | 'all'.
     */
    public static function inferDeviceType(string $name): string
    {
        $lc = strtolower($name);

        // Most-specific first.
        if (str_contains($lc, 'macbook') || str_contains($lc, 'imac') || str_contains($lc, 'mac ')) {
            return 'macbook';
        }
        if (str_contains($lc, 'ipad')) {
            return 'ipad';
        }
        if (preg_match('/\b(iphone|apple|fmi|icloud|activation|imei)\b/i', $name)) {
            return 'iphone';
        }
        // Non-Apple brands go into the shared "all" bucket.
        $nonApple = ['samsung', 'xiaomi', 'huawei', 'google', 'motorola',
                     'lg ', 'sony', 'oneplus', 'oppo', 'vivo', 'nokia',
                     'realme', 'zte', 'blackberry'];
        foreach ($nonApple as $brand) {
            if (str_contains($lc, $brand)) {
                return 'all';
            }
        }
        return 'all';
    }

    /**
     * Convert a USD provider price into local pricing.
     *
     * - cost_price = usd_price * rate, rounded UP to 2 decimals (so we
     *   never accidentally under-cost ourselves due to rounding).
     * - sell_price = cost_price * markup, rounded to the nearest whole
     *   baht (retail-friendly display).
     *
     * @return array{cost_price: float, sell_price: float}
     */
    public static function calculatePrices(float $usdPrice, float $rate, float $markup): array
    {
        // Round up cost to 2 decimals: ceil(x * 100) / 100.
        $cost = ceil($usdPrice * $rate * 100) / 100;
        // Round sell to the nearest whole baht.
        $sell = round($cost * $markup);
        return ['cost_price' => $cost, 'sell_price' => $sell];
    }
}
