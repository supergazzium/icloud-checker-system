<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\IFreeICloudService;
use PHPUnit\Framework\TestCase;

/**
 * These tests feed real captured provider responses into parseObject
 * so we catch regressions when the provider changes field names or
 * value shapes. The class doesn't need a Laravel app boot because
 * IFreeICloudService's constructor only pulls config() — we bypass
 * that by reflecting directly into parseObject().
 *
 * Two fixtures used:
 *   - Apple Mac (service #281): boolean-typed flags, `mdmLock` and
 *     `lostMode` field names, WiFi-only serial with `simLock: false`.
 *   - Older iPhone-style response: `fmi: "ON"` string, `blacklist` field.
 */
final class IFreeICloudServiceParseTest extends TestCase
{
    private function parse(object $obj): array
    {
        // parseObject doesn't touch config; instantiating IFreeICloudService
        // does. Sidestep the ctor by newing via ReflectionClass.
        $svc = (new \ReflectionClass(IFreeICloudService::class))->newInstanceWithoutConstructor();
        return $svc->parseObject($obj);
    }

    public function test_parses_apple_mac_response(): void
    {
        $obj = (object) [
            'thumbnail'        => 'https://km.support.apple.com/kb/securedImage.jsp?productid=301086',
            'modelDesc'        => 'MBA 13 MDN/8C GPU/16GB/256GB-THA',
            'model'            => 'MacBook Air (13-inch, M4, 2025)',
            'imei'             => null,
            'imei2'            => null,
            'serial'           => 'LKQD2YD439',
            'partNumber'       => 'MW123TH/A',
            'partCountry'      => 'Thailand',
            'partType'         => 'Retail Unit',
            'activated'        => true,
            'warrantyStatus'   => 'Out Of Warranty',
            'estPurchaseDate'  => '12 Jul 2025',
            'purchaseCountry'  => 'Thailand',
            'technicalSupport' => true,
            'repairCoverage'   => true,
            'coverageEndDate'  => '12 Jul 2026',
            'acEligible'       => false,
            'replaced'         => false,
            'replacement'      => false,
            'refurbished'      => false,
            'demoUnit'         => false,
            'loaner'           => false,
            'mdmLock'          => false,
            'fmiOn'            => true,
            'lostMode'         => false,
            'carrier'          => 'Not Applicable (WiFi Only)',
            'country'          => 'Thailand',
            'simLock'          => false,
            'isAppleDevice'    => true,
        ];
        $p = $this->parse($obj);

        // Identity
        $this->assertSame('MacBook Air (13-inch, M4, 2025)', $p['model']);
        $this->assertSame('MBA 13 MDN/8C GPU/16GB/256GB-THA', $p['model_desc']);
        $this->assertSame('MW123TH/A', $p['part_number']);
        $this->assertSame('Thailand', $p['part_country']);
        $this->assertSame('Retail Unit', $p['part_type']);
        $this->assertStringStartsWith('https://', $p['thumbnail']);

        // Identifiers
        $this->assertSame('LKQD2YD439', $p['serial']);
        $this->assertNull($p['imei']);
        $this->assertNull($p['imei2']);

        // Region falls back to country.
        $this->assertSame('Thailand', $p['region']);

        // Booleans converted to human labels.
        $this->assertSame('ON', $p['fmi_status']);
        $this->assertSame('Activated', $p['activation_status']);
        $this->assertSame('Clean', $p['blacklist_status']);       // lostMode:false
        $this->assertSame('Not Enrolled', $p['mdm_status']);      // mdmLock:false
        $this->assertSame('No', $p['replaced_status']);
        $this->assertSame('No', $p['replacement']);
        $this->assertSame('No', $p['refurbished']);
        $this->assertSame('No', $p['demo_unit']);
        $this->assertSame('No', $p['loaner']);

        // WiFi-only Mac: simLock is false → falls back to carrier text.
        $this->assertSame('Not Applicable (WiFi Only)', $p['simlock_status']);
        $this->assertSame('Not Applicable (WiFi Only)', $p['carrier']);

        // Warranty / coverage
        $this->assertSame('Out Of Warranty', $p['warranty']);
        $this->assertSame('12 Jul 2026', $p['coverage_end_date']);
        $this->assertSame('Not Eligible', $p['ac_eligible']);
        $this->assertSame('Active', $p['technical_support']);
        $this->assertSame('Active', $p['repair_coverage']);

        // Purchase
        $this->assertSame('12 Jul 2025', $p['purchase_date']);
        $this->assertSame('Thailand', $p['purchase_country']);
    }

    public function test_parses_iphone_style_string_flags(): void
    {
        // Some services use string values instead of booleans.
        $obj = (object) [
            'model'          => 'iPhone 13 Pro',
            'imei'           => '353111111111111',
            'imei2'          => '353111111111112',
            'serial'         => 'ABC12DEF',
            'fmi'            => 'ON',
            'activation'     => 'Activated',
            'blacklist'      => 'Clean',
            'simLock'        => 'Locked',
            'mdm'            => 'Not Enrolled',
            'warrantyStatus' => 'AppleCare+',
            'carrier'        => 'AIS',
        ];
        $p = $this->parse($obj);

        $this->assertSame('iPhone 13 Pro', $p['model']);
        $this->assertSame('353111111111111', $p['imei']);
        $this->assertSame('353111111111112', $p['imei2']);
        $this->assertSame('ON', $p['fmi_status']);
        $this->assertSame('Activated', $p['activation_status']);
        $this->assertSame('Clean', $p['blacklist_status']);
        $this->assertSame('Locked', $p['simlock_status']);
        $this->assertSame('Not Enrolled', $p['mdm_status']);
        $this->assertSame('AppleCare+', $p['warranty']);
        $this->assertSame('AIS', $p['carrier']);
    }

    public function test_empty_object_returns_all_nulls(): void
    {
        // An empty stdClass produces the full key set with null values —
        // useful because callers do $p['model'] ?? null without needing
        // to defensively isset() every key.
        $p = $this->parse((object) []);
        $this->assertArrayHasKey('model', $p);
        $this->assertArrayHasKey('mdm_status', $p);
        $this->assertNull($p['model']);
        $this->assertNull($p['fmi_status']);
    }

    public function test_null_object_returns_empty_array(): void
    {
        $svc = (new \ReflectionClass(IFreeICloudService::class))->newInstanceWithoutConstructor();
        $this->assertSame([], $svc->parseObject(null));
    }

    public function test_pickbool_ignores_boolean_false_for_simlock(): void
    {
        // Regression: pick() previously (str)cast false to "" and returned
        // empty. Now pick() rejects false; simLock:false correctly falls
        // through to carrier.
        $obj = (object) ['simLock' => false, 'carrier' => 'T-Mobile'];
        $p = $this->parse($obj);
        $this->assertSame('T-Mobile', $p['simlock_status']);
    }

    public function test_pickbool_integer_truthy(): void
    {
        // Provider sometimes serialises booleans as 1/0. pickBool should
        // treat integer 1 as truthy.
        $obj = (object) ['fmiOn' => 1, 'activated' => 0];
        $p = $this->parse($obj);
        $this->assertSame('ON', $p['fmi_status']);
        $this->assertSame('Not Activated', $p['activation_status']);
    }
}
