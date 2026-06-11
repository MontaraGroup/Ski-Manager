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
        $today = date('Y-m-d');
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if (!$bonus) {
            $db->table('daily_bonus')->insert(['user_id' => $userId, 'last_claim_day' => 0, 'last_claim_date' => null, 'streak' => 0, 'total_claimed' => 0]);
            $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        }
        $rewards = $this->getRewards();
        $maxDay = !empty($rewards) ? max(array_keys($rewards)) : 7;
        $lastDate = $bonus['last_claim_date'] ?? null;
        $canClaim = ($lastDate === null) || ($lastDate < $today);
        $claimedYesterday = ($lastDate !== null) && ($lastDate === date('Y-m-d', strtotime('-1 day')));
        $nextStreak = $claimedYesterday ? min($maxDay, (int) $bonus['streak'] + 1) : 1;
        $nextReward = $rewards[$nextStreak] ?? 1000;
        return view('dailybonus/index', [
            'bonus' => $bonus,
            'canClaim' => $canClaim,
            'claimedYesterday' => $claimedYesterday,
            'rewards' => $rewards,
            'nextReward' => $nextReward,
        ]);
    }

    public function claim()
    {
        $userId = auth()->id();
        $db = db_connect();
        $today = date('Y-m-d');
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        if ($bonus && $bonus['last_claim_date'] === $today) {
            return redirect()->back()->with('error', 'Already claimed today. Come back tomorrow!');
        }
        $rewards = $this->getRewards();
        $maxDay = !empty($rewards) ? max(array_keys($rewards)) : 7;
        $lastDate = $bonus['last_claim_date'] ?? null;
        $claimedYesterday = ($lastDate !== null) && ($lastDate === date('Y-m-d', strtotime('-1 day')));
        $newStreak = $claimedYesterday ? min($maxDay, (int) $bonus['streak'] + 1) : 1;
        $reward = $rewards[$newStreak] ?? 1000;
        $db->table('daily_bonus')->where('user_id', $userId)->update([
            'last_claim_date' => $today,
            'streak' => $newStreak,
            'total_claimed' => (int) $bonus['total_claimed'] + $reward,
        ]);
        $finance = (new FinanceModel())->where('user_id', $userId)->first();
        if ($finance) (new FinanceModel())->update($finance['id'], ['cash' => (int) $finance['cash'] + $reward]);
        log_activity($userId, 'Daily Bonus', 'Claimed day ' . $newStreak . ' bonus: ' . currency($reward), 'fa-solid fa-gift');
        return redirect()->to('/daily-bonus')->with('success', 'Day ' . $newStreak . ' bonus claimed: ' . currency($reward) . '!');
    }
}
