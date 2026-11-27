<?php

use App\Libraries\WinterSports;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * WinterSports::settleDay() and pricing/upgrade helpers are pure.
 *
 * @internal
 */
final class WinterSportsTest extends CIUnitTestCase
{
    public function testOpenIceRinkGeneratesRevenueAndVisitors(): void
    {
        $res = WinterSports::settleDay([
            'facility_type' => 'ice_rink',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ticket_price' => 18,
        ], 500, true);

        $this->assertGreaterThan(0, $res['guests']);
        $this->assertSame($res['guests'] * 18, $res['revenue']);
        $this->assertSame(600, $res['upkeep']);
        $this->assertSame(40, $res['visitorBoost']);
    }

    public function testClosedFacilityEarnsNothing(): void
    {
        $res = WinterSports::settleDay([
            'facility_type' => 'bobsleigh_track',
            'level' => 2,
            'status' => 'closed',
            'condition_pct' => 100,
            'ticket_price' => 45,
        ], 1000, true);

        $this->assertSame(0, $res['guests']);
        $this->assertSame(0, $res['revenue']);
        $this->assertSame(0, $res['upkeep']);
        $this->assertSame(0, $res['visitorBoost']);
    }

    public function testHigherLevelIncreasesCapacityAndUpkeep(): void
    {
        $this->assertGreaterThan(
            WinterSports::capacityFor('hockey_arena', 1),
            WinterSports::capacityFor('hockey_arena', 3)
        );
        $this->assertGreaterThan(
            WinterSports::upkeepFor('hockey_arena', 1),
            WinterSports::upkeepFor('hockey_arena', 3)
        );
        $this->assertSame(0, WinterSports::upgradeCost('hockey_arena', 3));
    }

    public function testSalvageValueIsHalfInvested(): void
    {
        $invested = WinterSports::investedValue('ski_jump', 1);
        $this->assertSame(140000, $invested);
        $this->assertSame(70000, WinterSports::salvageValue('ski_jump', 1));
    }

    public function testCrossCountryFacilityGeneratesIncomeAndVisitorBoost(): void
    {
        $res = WinterSports::settleDay([
            'facility_type' => 'cross_country',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 22,
        ], 600, true);

        $this->assertGreaterThan(0, $res['guests']);
        $this->assertSame($res['guests'] * 22, $res['ticketRevenue']);
        $this->assertSame(0, $res['rentalRevenue']);
        $this->assertSame(850, $res['upkeep']);
        $this->assertSame(65, $res['visitorBoost']);
    }

    public function testAddonsAndExtendedModeIncreaseRevenueAndCapacity(): void
    {
        $base = WinterSports::settleDay([
            'facility_type' => 'ice_rink',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 18,
        ], 1000, true);

        $upgraded = WinterSports::settleDay([
            'facility_type' => 'ice_rink',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'upgrades' => 'floodlights,rental_booth,concessions',
            'session_mode' => 'extended',
            'ticket_price' => 18,
        ], 1000, true);

        $this->assertGreaterThan($base['guests'], $upgraded['guests']);
        $this->assertGreaterThan(0, $upgraded['rentalRevenue']);
        $this->assertGreaterThan(0, $upgraded['concessionRevenue']);
        $this->assertGreaterThan($base['revenue'], $upgraded['revenue']);
    }

    public function testHighWindReducesSkiJumpAttendance(): void
    {
        $calm = WinterSports::settleDay([
            'facility_type' => 'ski_jump',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 32,
        ], 800, true, -4, 15);

        $windy = WinterSports::settleDay([
            'facility_type' => 'ski_jump',
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ice_quality' => 100,
            'ticket_price' => 32,
        ], 800, true, -4, 55);

        $this->assertLessThan($calm['guests'], $windy['guests']);
        $this->assertNotNull($windy['weatherNote']);
    }
}
