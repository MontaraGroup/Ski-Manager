<?php

use App\Controllers\DailyBonus;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * DailyBonus::streakState() is pure: daily_bonus row, season number and today's date in,
 * effective streak and season-save state out.
 *
 * @internal
 */
final class DailyBonusStreakTest extends CIUnitTestCase
{
    private const TODAY = '2026-11-27';

    public function testConsecutiveYesterdayKeepsStreakWithoutUsingSave(): void
    {
        $state = DailyBonus::streakState([
            'last_claim_date' => '2026-11-26',
            'streak' => 14,
            'save_used_season' => 0,
        ], 1, self::TODAY);

        $this->assertFalse($state['claimedToday']);
        $this->assertTrue($state['onStreak']);
        $this->assertSame(14, $state['streak']);
        $this->assertFalse($state['saveProtected']);
        $this->assertTrue($state['saveAvailable']);
        $this->assertSame(0, $state['lostStreak']);
    }

    public function testOneMissedDayIsProtectedOncePerSeason(): void
    {
        $state = DailyBonus::streakState([
            'last_claim_date' => '2026-11-25',
            'streak' => 29,
            'save_used_season' => 0,
        ], 1, self::TODAY);

        $this->assertTrue($state['onStreak']);
        $this->assertTrue($state['saveProtected']);
        $this->assertSame(29, $state['streak']);
        $this->assertSame(0, $state['lostStreak']);
    }

    public function testSecondMissedDayInSameSeasonResetsStreak(): void
    {
        $state = DailyBonus::streakState([
            'last_claim_date' => '2026-11-25',
            'streak' => 29,
            'save_used_season' => 1,
        ], 1, self::TODAY);

        $this->assertFalse($state['onStreak']);
        $this->assertFalse($state['saveProtected']);
        $this->assertTrue($state['saveUsedThisSeason']);
        $this->assertSame(0, $state['streak']);
        $this->assertSame(29, $state['lostStreak']);
    }

    public function testNewSeasonGrantsFreshStreakSave(): void
    {
        $state = DailyBonus::streakState([
            'last_claim_date' => '2026-11-25',
            'streak' => 45,
            'save_used_season' => 1,
        ], 2, self::TODAY);

        $this->assertTrue($state['onStreak']);
        $this->assertTrue($state['saveProtected']);
        $this->assertTrue($state['saveAvailable']);
        $this->assertSame(45, $state['streak']);
    }

    public function testTwoConsecutiveMissedDaysResetStreakEvenWithSaveAvailable(): void
    {
        $state = DailyBonus::streakState([
            'last_claim_date' => '2026-11-24',
            'streak' => 12,
            'save_used_season' => 0,
        ], 1, self::TODAY);

        $this->assertFalse($state['onStreak']);
        $this->assertFalse($state['saveProtected']);
        $this->assertSame(0, $state['streak']);
        $this->assertSame(12, $state['lostStreak']);
    }

    public function testZeroStreakDoesNotTriggerSaveProtection(): void
    {
        $state = DailyBonus::streakState([
            'last_claim_date' => '2026-11-25',
            'streak' => 0,
            'save_used_season' => 0,
        ], 1, self::TODAY);

        $this->assertFalse($state['onStreak']);
        $this->assertFalse($state['saveProtected']);
        $this->assertSame(0, $state['streak']);
    }
}
