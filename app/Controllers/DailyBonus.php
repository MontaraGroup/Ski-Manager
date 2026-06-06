<?php
namespace App\Controllers;
use App\Models\FinanceModel;
class DailyBonus extends BaseController
{
    private function getRewards(): array
    {
        $db = db_connect();
        $rows = $db->table('bonus_rewards')->orderBy('streak_day')->get()->getResultArray();
        $rewards = [];
        foreach ($rows as $r) { $rewards[(int) $r['streak_day']] = (int) $r['reward']; }
        return $rewards;
    }

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int) ((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if (!$bonus) {
            $db->table('daily_bonus')->insert(['user_id' => $userId, 'last_claim_day' => 0, 'streak' => 0, 'total_claimed' => 0]);
            $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        }
        $rewards = $this->getRewards();
        $canClaim = (int) $bonus['last_claim_day'] < $gameDay;
        $nextReward = $rewards[min(7, (int) $bonus['streak'] + 1)] ?? 1000;
        return view('dailybonus/index', ['bonus' => $bonus, 'canClaim' => $canClaim, 'gameDay' => $gameDay, 'rewards' => $rewards, 'nextReward' => $nextReward]);
    }

    public function claim()
    {
        $userId = auth()->id();
        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int) ((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if ((int) $bonus['last_claim_day'] >= $gameDay) return redirect()->back()->with('error', 'Already claimed today.');
        $wasYesterday = (int) $bonus['last_claim_day'] === $gameDay - 1;
        $newStreak = $wasYesterday ? min(7, (int) $bonus['streak'] + 1) : 1;
        $rewards = $this->getRewards();
        $reward = $rewards[$newStreak] ?? 1000;
        $db->table('daily_bonus')->where('user_id', $userId)->update(['last_claim_day' => $gameDay, 'streak' => $newStreak, 'total_claimed' => (int) $bonus['total_claimed'] + $reward]);
        $finance = (new FinanceModel())->where('user_id', $userId)->first();
        if ($finance) (new FinanceModel())->update($finance['id'], ['cash' => (int) $finance['cash'] + $reward]);
        log_activity($userId, 'Daily Bonus', 'Claimed day ' . $newStreak . ' bonus: ' . currency($reward), 'fa-solid fa-gift');
        return redirect()->to('/daily-bonus')->with('success', 'Day ' . $newStreak . ' bonus claimed: ' . currency($reward) . '!');
    }
}
