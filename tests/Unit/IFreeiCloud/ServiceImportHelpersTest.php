<?php

declare(strict_types=1);

namespace Tests\Unit\IFreeiCloud;

use App\Services\IFreeiCloud\ServiceImportHelpers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ServiceImportHelpersTest extends TestCase
{
    // ---------- cleanName ----------

    #[DataProvider('nameCases')]
    public function test_clean_name(string $raw, string $expected): void
    {
        $this->assertSame($expected, ServiceImportHelpers::cleanName($raw));
    }

    public static function nameCases(): array
    {
        return [
            'plain'                     => ['iPhone FMI Check', 'iPhone FMI Check'],
            'trailing emoji'            => ['iPhone FMI 🚨', 'iPhone FMI'],
            'multiple emoji'            => ['iPhone Blacklist 📵 🔒', 'iPhone Blacklist'],
            'flag emoji'                => ['MacBook JP 🇯🇵', 'MacBook JP'],
            'ampersand stripped'        => ['Model & Info (Full)', 'Model Info (Full)'],
            'thai chars survive'        => ['ตรวจ iPhone', 'ตรวจ iPhone'],
            'collapse spaces'           => ['iPhone   FMI   Check', 'iPhone FMI Check'],
            'trim'                      => ['  Motorola Info  ', 'Motorola Info'],
            'punctuation kept'          => ['iCloud: Full Report / Pro', 'iCloud: Full Report / Pro'],
            // Real cases from provider list — HTML entities for emoji.
            'encoded emoji entity'      => ['Blacklist Status &#128680;', 'Blacklist Status'],
            'unencoded entity remnant'  => ['Blacklist Status &128680', 'Blacklist Status'],
            'named html entity'         => ['A &amp; B', 'A B'],
        ];
    }

    // ---------- inferDeviceType ----------

    #[DataProvider('deviceCases')]
    public function test_infer_device_type(string $name, string $expected): void
    {
        $this->assertSame($expected, ServiceImportHelpers::inferDeviceType($name));
    }

    public static function deviceCases(): array
    {
        return [
            'macbook explicit'         => ['MacBook Info', 'macbook'],
            'imac'                     => ['iMac 2019 Info', 'macbook'],
            'ipad explicit'            => ['iPad All-in-One', 'ipad'],
            'iphone explicit'          => ['iPhone FMI Check', 'iphone'],
            'apple generic → iphone'   => ['Apple GSX Report', 'iphone'],
            'fmi keyword → iphone'     => ['FMI Off / On Status', 'iphone'],
            'imei generic → iphone'    => ['IMEI Info Lookup', 'iphone'],
            'samsung → all'            => ['Samsung Info', 'all'],
            'xiaomi → all'             => ['Xiaomi Unlock', 'all'],
            'huawei → all'             => ['Huawei Info', 'all'],
            'motorola → all'           => ['Motorola Info', 'all'],
            'unknown → all'            => ['Something Weird', 'all'],
            'macbook beats apple'      => ['Apple MacBook Full Info', 'macbook'],
            'ipad beats iphone-y word' => ['iPad Activation Lock', 'ipad'],
        ];
    }

    // ---------- calculatePrices ----------

    public function test_calculate_prices_basic(): void
    {
        // $0.55 * 36 = 19.80 cost, * 2.5 = 49.50 → rounds to 50
        $out = ServiceImportHelpers::calculatePrices(0.55, 36.00, 2.5);
        $this->assertSame(19.80, $out['cost_price']);
        $this->assertSame(50.0,  $out['sell_price']);
    }

    public function test_calculate_prices_rounds_cost_up_to_2_decimals(): void
    {
        // $0.101 * 36 = 3.636 → ceil to 3.64
        $out = ServiceImportHelpers::calculatePrices(0.101, 36.00, 2.5);
        $this->assertSame(3.64, $out['cost_price']);
        $this->assertSame(9.0,  $out['sell_price']); // 3.64 * 2.5 = 9.1 → 9
    }

    public function test_calculate_prices_rounds_sell_to_whole_baht(): void
    {
        // $1.00 * 36 = 36.00 cost, * 2.5 = 90.00 → 90 (no half)
        $out = ServiceImportHelpers::calculatePrices(1.00, 36.00, 2.5);
        $this->assertSame(36.00, $out['cost_price']);
        $this->assertSame(90.0,  $out['sell_price']);

        // $1.00 * 36 = 36.00 cost, * 1.7 = 61.2 → 61
        $out = ServiceImportHelpers::calculatePrices(1.00, 36.00, 1.7);
        $this->assertSame(61.0, $out['sell_price']);

        // $1.00 * 36 = 36.00 cost, * 1.75 = 63.0 → 63
        $out = ServiceImportHelpers::calculatePrices(1.00, 36.00, 1.75);
        $this->assertSame(63.0, $out['sell_price']);
    }

    public function test_calculate_prices_zero_input(): void
    {
        $out = ServiceImportHelpers::calculatePrices(0.0, 36.00, 2.5);
        $this->assertSame(0.0, $out['cost_price']);
        $this->assertSame(0.0, $out['sell_price']);
    }

    public function test_calculate_prices_high_rate(): void
    {
        // Sanity check at absurdly high rate — no overflow, still rounds correctly.
        $out = ServiceImportHelpers::calculatePrices(0.10, 100.0, 3.0);
        $this->assertSame(10.00, $out['cost_price']);
        $this->assertSame(30.0,  $out['sell_price']);
    }
}
