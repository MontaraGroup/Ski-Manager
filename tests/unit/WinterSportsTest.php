<?php

use App\Libraries\WinterSports;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for the 5 bespoke Winter Activities engines.
 *
 * @internal
 */
final class WinterSportsTest extends CIUnitTestCase
{
    public function testClosedFacilityEarnsNothing(): void
    {
        $res = WinterSports::settleDay([
            'facility_type' => 'bobsleigh_track',
            'level' => 2,
            'status' => 'closed',
            'condition_pct' => 100,
            'ticket_price' => 85,
        ], 1000, true);

        $this->assertSame(0, $res['guests']);
        $this->assertSame(0, $res['revenue']);
        $this->assertSame(0, $res['upkeep']);
        $this->assertSame(0, $res['kwh']);
    }

    public function testIceRinkScheduleResurfaceAndChiller(): void
    {
        $uncooledWarm = WinterSports::settleDay([
            'facility_type' => 'ice_rink',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 95,
            'ticket_price' => 18,
            'config_json' => json_encode(['slots' => ['morning' => 'open_skate', 'afternoon' => 'resurface', 'evening' => 'open_skate']]),
        ], 600, true, 7);

        $this->assertNotNull($uncooledWarm['weatherNote']);
        $this->assertGreaterThan(0, $uncooledWarm['iceDelta']);

        $chilledDisco = WinterSports::settleDay([
            'facility_type' => 'ice_rink',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 95,
            'ticket_price' => 18,
            'upgrades' => 'skate_shop,string_lights,ice_chiller',
            'config_json' => json_encode(['slots' => ['morning' => 'open_skate', 'afternoon' => 'lessons', 'evening' => 'ice_disco'], 'rental_price' => 12]),
        ], 600, true, 7);

        $this->assertNull($chilledDisco['weatherNote']);
        $this->assertGreaterThan($uncooledWarm['guests'], $chilledDisco['guests']);
        $this->assertGreaterThan(0, $chilledDisco['addonRevenue']);
    }

    public function testCrossCountryLoopsSnowBaseAndMapTrails(): void
    {
        $thinSnow = WinterSports::settleDay([
            'facility_type' => 'cross_country',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 22,
            'config_json' => json_encode(['loops' => ['meadow_3k'], 'grooming' => 'classic']),
        ], 700, true, -5, 10, 12, 0);

        $this->assertNotNull($thinSnow['weatherNote']);

        $expandedDual = WinterSports::settleDay([
            'facility_type' => 'cross_country',
            'level' => 2,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'upgrades' => 'wax_bench,warming_hut,snow_support',
            'ticket_price' => 22,
            'config_json' => json_encode(['loops' => ['meadow_3k', 'forest_7k', 'lakeside_10k'], 'grooming' => 'dual']),
        ], 700, true, -5, 10, 12, 5);

        $this->assertNull($expandedDual['weatherNote']);
        $this->assertGreaterThan($thinSnow['guests'], $expandedDual['guests']);
        $this->assertGreaterThan($thinSnow['capacity'], $expandedDual['capacity']);
        $this->assertGreaterThan(0, $expandedDual['addonRevenue']);
    }

    public function testHockeyArenaMatchBookingAndDasherSponsors(): void
    {
        $offDay = WinterSports::settleDay([
            'facility_type' => 'hockey_arena',
            'level' => 2,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 26,
            'config_json' => json_encode(['booked_match' => 'club_rental']),
        ], 500, true);

        $derby = WinterSports::settleDay([
            'facility_type' => 'hockey_arena',
            'level' => 2,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'upgrades' => 'dasher_sponsors,jumbotron',
            'ticket_price' => 26,
            'config_json' => json_encode(['booked_match' => 'regional_derby']),
        ], 500, true);

        $this->assertSame(1200, $derby['capacity']);
        $this->assertGreaterThan($offDay['guests'], $derby['guests']);
        $this->assertSame(2200, $derby['contractRevenue']);
        $this->assertSame(70, $derby['repBonus']);
    }

    public function testSkiJumpWindHoldMitigationAndHillRecord(): void
    {
        $held = WinterSports::settleDay([
            'facility_type' => 'ski_jump',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 30,
            'config_json' => json_encode(['hills' => ['k60', 'k90'], 'allocation' => 'public']),
        ], 800, true, -4, 42);

        $netted = WinterSports::settleDay([
            'facility_type' => 'ski_jump',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'upgrades' => 'wind_nets',
            'ticket_price' => 30,
            'config_json' => json_encode(['hills' => ['k60', 'k90'], 'allocation' => 'public']),
        ], 800, true, -4, 42);

        $this->assertNotNull($held['weatherNote']);
        $this->assertNull($netted['weatherNote']);
        $this->assertGreaterThan($held['guests'], $netted['guests']);
        $this->assertGreaterThan(58, WinterSports::nextHillRecord(['k60', 'k120'], 58, true, 95));
    }

    public function testBobsleighCoolingKwhSunShadesAndSpeed(): void
    {
        $std = WinterSports::settleDay([
            'facility_type' => 'bobsleigh_track',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 95,
            'ticket_price' => 85,
            'config_json' => json_encode(['mode' => 'split', 'cooling' => 'race_spec']),
        ], 800, true, 10);

        $shaded = WinterSports::settleDay([
            'facility_type' => 'bobsleigh_track',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 95,
            'upgrades' => 'taxi_fleet,sun_shades',
            'ticket_price' => 85,
            'config_json' => json_encode(['mode' => 'split', 'cooling' => 'race_spec']),
        ], 800, true, 10);

        $this->assertLessThan($std['kwh'], $shaded['kwh']);
        $this->assertSame($std['capacity'] * 2, $shaded['capacity']);
        $this->assertGreaterThan(WinterSports::trackSpeedKmh(1, 'eco', 80), WinterSports::trackSpeedKmh(3, 'race_spec', 95));
    }

    public function testSalvageValueIncludesExpansionsAndUpgrades(): void
    {
        $base = WinterSports::salvageValue('ski_jump', 1, [], ['hills' => ['k60']]);
        $expanded = WinterSports::salvageValue('ski_jump', 1, ['wind_nets'], ['hills' => ['k60', 'k90']]);
        $this->assertGreaterThan($base, $expanded);
    }
}
