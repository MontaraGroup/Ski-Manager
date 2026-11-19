<?php

use App\Libraries\Snowmaking;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Snowmaking::plan() is pure: guns, slopes and weather in, today's snow out.
 *
 * @internal
 */
final class SnowmakingTest extends CIUnitTestCase
{
    private function gun(array $over = []): array
    {
        return $over + ['id' => 1, 'name' => 'Gun 1', 'model_key' => 'ta_tr9', 'status' => 'active', 'condition_pct' => 80,
            'capacity' => 2, 'output_per_day' => 12, 'fuel_cost' => 280, 'energy_kwh' => 200, 'water_liters' => 2300,
            'snow_mode' => 'auto', 'assigned_to' => null, 'upgrades' => null];
    }

    private function slope(int $id, int $made = 0, int $target = 30): array
    {
        return ['id' => $id, 'status' => 'open', 'made_snow_cm' => $made, 'snow_target_cm' => $target];
    }

    private const COLD = ['temp' => -8, 'humidity' => 60, 'snow_base' => 10];

    public function testWetBulbIsBelowAirTemperatureInDryAir(): void
    {
        $this->assertLessThan(0, Snowmaking::wetBulb(0, 40));
        $this->assertGreaterThan(Snowmaking::wetBulb(0, 40), Snowmaking::wetBulb(0, 95));
    }

    public function testDryAirOpensTheWindowOnAMarginalDay(): void
    {
        $g = $this->gun();
        $this->assertGreaterThan(Snowmaking::hours($g, 1, 95), Snowmaking::hours($g, 1, 35));
        $this->assertSame(24, Snowmaking::hours($this->gun(['model_key' => 'ta_aw50']), 20, 90));
    }

    public function testNozzlesWidenTheWindow(): void
    {
        $this->assertGreaterThan(Snowmaking::hours($this->gun(), 2, 70), Snowmaking::hours($this->gun(['upgrades' => 'nozzles']), 2, 70));
    }

    public function testAutoStopsAtTargetAndCostsOnlyTheHoursItRuns(): void
    {
        // Natural 10 + made 18 = 28: 2 cm short of a 30 cm target
        $plan = Snowmaking::plan([$this->gun()], [$this->slope(5, 18)], self::COLD);
        $this->assertEqualsWithDelta(2.0, $plan['made'][5], 0.01);
        $this->assertLessThan(1.0, $plan['guns'][1]['share']);
        $scaled = Snowmaking::scaled($this->gun(), $plan['guns'][1]['share']);
        $this->assertLessThan(280, $scaled['fuel_cost']);
    }

    public function testAutoStandsByWhenEverySlopeIsAtTarget(): void
    {
        $plan = Snowmaking::plan([$this->gun()], [$this->slope(5, 25)], self::COLD);
        $this->assertSame([], $plan['guns']);
        $this->assertSame([], $plan['covered']);
    }

    public function testMaxRunsEveryColdHour(): void
    {
        $plan = Snowmaking::plan([$this->gun(['snow_mode' => 'max'])], [$this->slope(5, 60)], self::COLD);
        $this->assertEqualsWithDelta(1.0, $plan['guns'][1]['share'] * 24 / $plan['guns'][1]['hours'], 0.001);
        $this->assertGreaterThan(0, $plan['made'][5]);
    }

    public function testTargetZeroMeansNoSnowmaking(): void
    {
        $plan = Snowmaking::plan([$this->gun(['snow_mode' => 'max'])], [$this->slope(5, 0, 0)], self::COLD);
        $this->assertSame([], $plan['made']);
    }

    public function testPriorityFirstThenFurthestUnderTargetUpToCapacity(): void
    {
        $slopes = [$this->slope(1, 15), $this->slope(2, 0), $this->slope(3, 10)];
        $plan = Snowmaking::plan([$this->gun(['assigned_to' => 'slope_1'])], $slopes, self::COLD);
        $this->assertSame(['1', '2'], array_map('strval', $plan['guns'][1]['slopes']));
        $this->assertArrayNotHasKey(3, $plan['covered']);
    }

    public function testCrewAndAutomationRaiseOutput(): void
    {
        $g = $this->gun();
        $bare = Snowmaking::potential($g, -8, 60, null);
        $this->assertEqualsWithDelta($bare / 0.7, Snowmaking::potential($g, -8, 60, 0), 0.01);
        $this->assertEqualsWithDelta($bare / 0.7 * 1.06, Snowmaking::potential($g, -8, 60, 2), 0.01);
        $this->assertEqualsWithDelta($bare / 0.7, Snowmaking::potential($this->gun(['upgrades' => 'automation']), -8, 60, null), 0.01);
    }

    public function testOffBrokenAndWarmGunsDoNotRun(): void
    {
        $slopes = [$this->slope(5)];
        $this->assertSame([], Snowmaking::plan([$this->gun(['status' => 'off'])], $slopes, self::COLD)['guns']);
        $this->assertSame([], Snowmaking::plan([$this->gun(['condition_pct' => 0])], $slopes, self::COLD)['guns']);
        $this->assertSame([], Snowmaking::plan([$this->gun()], $slopes, ['temp' => 8, 'humidity' => 90, 'snow_base' => 10])['guns']);
    }

    public function testOneGunPerSlope(): void
    {
        $plan = Snowmaking::plan([$this->gun(), $this->gun(['id' => 2, 'name' => 'Gun 2'])], [$this->slope(5)], self::COLD);
        $this->assertCount(1, $plan['guns']);
    }
}
