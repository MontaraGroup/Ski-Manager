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
}
