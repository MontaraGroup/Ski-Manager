<?php

namespace App\Controllers;

/**
 * Daily login bonus. One claim per calendar day (server time); claiming on consecutive days grows
 * the streak, which has no cap. Rewards follow the 7-day table in bonus_rewards, worth 25% more
 * for each full week of streak (up to double), and every 7th day also pays Génépis.
 */
class DailyBonus extends BaseController
{
    public const WEEK_BONUS = 0.25;
    public const MAX_MULTIPLIER = 2.0;
    public const WEEKLY_GENEPIS = 5;
    public const MILESTONE_DAYS = [30, 100];

    /**
     * Compute current streak and season-save status from a daily_bonus row.
     * One missed day (last claim 2 days ago) is forgiven once per season when streak > 0.
     */
    public static function streakState(array $row, int $seasonNumber, ?string $today = null): array
    {
        $today ??= date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
        $twoDaysAgo = date('Y-m-d', strtotime($today . ' -2 days'));

        $last = $row['last_claim_date'] ?? null;
        $rawStreak = (int) ($row['streak'] ?? 0);
        $saveUsedSeason = (int) ($row['save_used_season'] ?? 0);
        $saveAvailable = $saveUsedSeason < $seasonNumber;

        $claimedToday = $last === $today;
        $consecutive = $claimedToday || $last === $yesterday;
        $missedOneDay = !$consecutive && $last === $twoDaysAgo && $rawStreak > 0;
        $saveProtected = $missedOneDay && $saveAvailable;

        $onStreak = $consecutive || $saveProtected;
        $streak = $onStreak ? $rawStreak : 0;
        $lostStreak = !$onStreak && $rawStreak > 1 ? $rawStreak : 0;

        return [
            'claimedToday' => $claimedToday,
            'onStreak' => $onStreak,
            'streak' => $streak,
            'lostStreak' => $lostStreak,
            'saveAvailable' => $saveAvailable,
            'saveProtected' => $saveProtected,
            'saveUsedThisSeason' => !$saveAvailable,
            'saveUsedDate' => $row['save_used_date'] ?? null,
        ];
    }

    private function cycle(): array
    {
        $rows = db_connect()->table('bonus_rewards')->orderBy('streak_day')->get()->getResultArray();
        return array_column($rows, 'reward', 'streak_day') ?: [1 => 1000, 2 => 1500, 3 => 2000, 4 => 2500, 5 => 3000, 6 => 4000, 7 => 10000];
    }

    /** Reward for a given streak day: the cycle day's amount × the week multiplier */
    public static function rewardFor(int $streak, array $cycle): array
    {
        $len = count($cycle);
        $day = (($streak - 1) % $len) + 1;
        $week = intdiv($streak - 1, $len);
        $mult = min(self::MAX_MULTIPLIER, 1 + self::WEEK_BONUS * $week);
        return [
            'day' => $day, 'week' => $week + 1, 'multiplier' => $mult,
            'cash' => (int) round((int) $cycle[$day] * $mult),
            'genepis' => $day === $len ? self::WEEKLY_GENEPIS : 0,
        ];
    }

    private function row(int $userId): array
    {
        $db = db_connect();
        $row = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if (!$row) {
            $db->table('daily_bonus')->insert(['user_id' => $userId, 'last_claim_day' => 0, 'last_claim_date' => null, 'streak' => 0, 'best_streak' => 0, 'total_claimed' => 0]);
            $row = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        }
        return $row;
    }

    public function index(): string
    {
        return view('dailybonus/index', ['page' => $this->pageData()]);
    }

    /** What the page shows: the web view and the app API (Api\V1\App) both use it */
    protected function pageData(): array
    {
        $userId = auth()->id();
        $row = $this->row($userId);
        $cycle = $this->cycle();
        $today = date('Y-m-d');
        $seasonNumber = function_exists('getSeasonNumber') ? getSeasonNumber() : 1;
        $state = self::streakState($row, $seasonNumber, $today);
        $claimedToday = $state['claimedToday'];
        $streak = $state['streak'];
        $next = self::rewardFor($streak + 1, $cycle); // today's claim, or tomorrow's once claimed

        // The 7 days of the week the streak is in (or would start), and what each pays
        $weekStart = $streak > 0 ? $streak - (($streak - 1) % count($cycle)) : 1;
        if (!$claimedToday && $streak > 0 && ($streak % count($cycle)) === 0) $weekStart = $streak + 1;
        $days = [];
        for ($s = $weekStart; $s < $weekStart + count($cycle); $s++) {
            $days[] = self::rewardFor($s, $cycle) + ['streak' => $s, 'done' => $s <= $streak, 'next' => !$claimedToday && $s === $streak + 1];
        }

        $formattedDays = array_map(function($d) {
            return [
                'day' => (int) $d['day'],
                'week' => (int) $d['week'],
                'streak' => (int) $d['streak'],
                'multiplier' => (float) $d['multiplier'],
                'cash' => (int) $d['cash'],
                'cashFormatted' => currency((int) $d['cash']),
                'genepis' => (int) $d['genepis'],
                'done' => (bool) $d['done'],
                'next' => (bool) $d['next'],
            ];
        }, $days);

        $nextMultPct = (int) round(($next['multiplier'] - 1) * 100);
        $bestStreak = max((int) $row['best_streak'], $streak);

        $achRows = db_connect()->table('achievements')
            ->select('achievement_key, completed')
            ->where('user_id', $userId)
            ->whereIn('achievement_key', ['login_30', 'login_100'])
            ->get()
            ->getResultArray();
        $achDone = array_column($achRows, 'completed', 'achievement_key');
        $milestones = array_map(function (int $days) use ($streak, $bestStreak, $achDone) {
            $key = 'login_' . $days;
            $earned = ((int) ($achDone[$key] ?? 0) === 1) || $bestStreak >= $days;
            return [
                'days' => $days,
                'key' => $key,
                'name' => $days . '-Day Streak',
                'earned' => $earned,
                'current' => min($days, $streak),
                'target' => $days,
            ];
        }, self::MILESTONE_DAYS);

        $page = [
            'streak' => $streak,
            'bestStreak' => $bestStreak,
            'totalClaimed' => (int) $row['total_claimed'],
            'totalClaimedFormatted' => currency((int) $row['total_claimed']),
            'claimedToday' => $claimedToday,
            'lostStreak' => $state['lostStreak'],
            'secondsToReset' => max(0, strtotime('tomorrow') - time()),
            'streakSave' => [
                'season' => $seasonNumber,
                'available' => $state['saveAvailable'],
                'protected' => $state['saveProtected'],
                'usedThisSeason' => $state['saveUsedThisSeason'],
                'usedDate' => $state['saveUsedDate'],
            ],
            'milestones' => $milestones,
            'next' => [
                'day' => (int) $next['day'],
                'week' => (int) $next['week'],
                'cash' => (int) $next['cash'],
                'cashFormatted' => currency((int) $next['cash']),
                'genepis' => (int) $next['genepis'],
                'multPct' => $nextMultPct,
            ],
            'currentWeek' => $days[0]['week'] ?? 1,
            'weekMultiplier' => (float) ($days[0]['multiplier'] ?? 1.0),
            'days' => $formattedDays,
            'cycleAmounts' => array_values(array_map('currency', $cycle)),
            'weekBonusPct' => (int) round(self::WEEK_BONUS * 100),
            'maxMultiplier' => self::MAX_MULTIPLIER,
            'weeklyGenepis' => self::WEEKLY_GENEPIS,
        ];

        return $page;
    }

    public function claim()
    {
        $userId = auth()->id();
        $row = $this->row($userId);
        $today = date('Y-m-d');
        $seasonNumber = function_exists('getSeasonNumber') ? getSeasonNumber() : 1;
        $state = self::streakState($row, $seasonNumber, $today);
        $usedSave = $state['saveProtected'];
        $streak = $state['onStreak'] ? (int) $row['streak'] + 1 : 1;
        $r = self::rewardFor($streak, $this->cycle());

        $db = db_connect();
        $db->transStart();
        // Only if not claimed today: two quick clicks can't both pay
        $update = $db->table('daily_bonus')->where('user_id', $userId)
            ->groupStart()->where('last_claim_date IS NULL')->orWhere('last_claim_date <', $today)->groupEnd()
            ->set('last_claim_date', $today)->set('streak', $streak)
            ->set('best_streak', 'GREATEST(best_streak, ' . $streak . ')', false)
            ->set('total_claimed', 'total_claimed + ' . $r['cash'], false);
        if ($usedSave) {
            $update->set('save_used_season', $seasonNumber)->set('save_used_date', $today);
        }
        $update->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/daily-bonus')->with('error', 'Already claimed today. The next bonus unlocks at midnight.');
        }
        $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $r['cash'], false)->update();
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Daily bonus',
            'description' => 'Day ' . $streak . ' login bonus', 'amount' => $r['cash'], 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        if ($r['genepis'] > 0) {
            if (!$db->table('genepis')->where('user_id', $userId)->countAllResults()) {
                $db->table('genepis')->insert(['user_id' => $userId, 'balance' => 0, 'total_earned' => 0, 'total_spent' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            }
            $db->table('genepis')->where('user_id', $userId)->set('balance', 'balance + ' . $r['genepis'], false)->set('total_earned', 'total_earned + ' . $r['genepis'], false)->update();
            $db->table('genepis_log')->insert(['user_id' => $userId, 'amount' => $r['genepis'], 'type' => 'earned', 'reason' => 'Week ' . $r['week'] . ' login streak', 'created_at' => date('Y-m-d H:i:s')]);
        }
        $db->transComplete();

        \App\Libraries\Achievements::sync($userId);

        $extra = $r['genepis'] > 0 ? ' and ' . $r['genepis'] . ' Génépis' : '';
        $saveNote = $usedSave ? ' (season streak save used)' : '';
        log_activity($userId, 'Daily Bonus', 'Claimed day ' . $streak . ' bonus: ' . currency($r['cash']) . $extra . $saveNote, 'fa-solid fa-gift');
        return redirect()->to('/daily-bonus')->with('success', 'Day ' . $streak . ' bonus: ' . currency($r['cash']) . $extra . $saveNote . '.');
    }
}
