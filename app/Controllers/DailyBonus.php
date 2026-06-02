<?php
namespace App\Controllers;
use App\Models\FinanceModel;
class DailyBonus extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $startDate = '2026-06-01';
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if (!$bonus) {
            $db->table('daily_bonus')->insert(['user_id' => $userId, 'last_claim_day' => 0, 'streak' => 0, 'total_claimed' => 0]);
            $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        }
        $canClaim = (int)$bonus['last_claim_day'] < $gameDay;
        $rewards = [1 => 1000, 2 => 1500, 3 => 2000, 4 => 2500, 5 => 3000, 6 => 4000, 7 => 10000];
        $nextReward = $rewards[min(7, (int)$bonus['streak'] + 1)];
        return view('dailybonus/index', ['bonus' => $bonus, 'canClaim' => $canClaim, 'gameDay' => $gameDay, 'rewards' => $rewards, 'nextReward' => $nextReward]);
    }
    public function claim()
    {
        $userId = auth()->id();
        $db = db_connect();
        $startDate = '2026-06-01';
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if ((int)$bonus['last_claim_day'] >= $gameDay) return redirect()->back()->with('error', 'Already claimed today.');
        $wasYesterday = (int)$bonus['last_claim_day'] === $gameDay - 1;
        $newStreak = $wasYesterday ? min(7, (int)$bonus['streak'] + 1) : 1;
        $rewards = [1 => 1000, 2 => 1500, 3 => 2000, 4 => 2500, 5 => 3000, 6 => 4000, 7 => 10000];
        $reward = $rewards[$newStreak];
        $db->table('daily_bonus')->where('user_id', $userId)->update(['last_claim_day' => $gameDay, 'streak' => $newStreak, 'total_claimed' => (int)$bonus['total_claimed'] + $reward]);
        $finance = (new FinanceModel())->where('user_id', $userId)->first();
        if ($finance) (new FinanceModel())->update($finance['id'], ['cash' => (int)$finance['cash'] + $reward]);
        log_activity($userId, 'Daily Bonus', 'Claimed day ' . $newStreak . ' bonus: ' . currency($reward), 'fa-solid fa-gift');
        return redirect()->to('/daily-bonus')->with('success', 'Day ' . $newStreak . ' bonus claimed: ' . currency($reward) . '!');
    }
}
