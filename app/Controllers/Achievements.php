<?php
namespace App\Controllers;
use App\Models\StaffModel;
use App\Models\BuildingModel;
use App\Models\SnowCannonModel;
class Achievements extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $achievements = $db->table('achievements')->where('user_id', $userId)->get()->getResultArray();
        if (empty($achievements)) {
            $defs = [
                ['key' => 'first_staff', 'name' => 'First Employee', 'desc' => 'Hire your first staff member', 'icon' => 'fa-solid fa-user-plus', 'target' => 1, 'reward' => 5000],
                ['key' => 'staff_10', 'name' => 'Growing Team', 'desc' => 'Hire 10 staff members', 'icon' => 'fa-solid fa-users', 'target' => 10, 'reward' => 25000],
                ['key' => 'first_building', 'name' => 'First Building', 'desc' => 'Build your first building', 'icon' => 'fa-solid fa-building', 'target' => 1, 'reward' => 5000],
                ['key' => 'buildings_10', 'name' => 'Resort Builder', 'desc' => 'Own 10 buildings', 'icon' => 'fa-solid fa-city', 'target' => 10, 'reward' => 50000],
                ['key' => 'first_cannon', 'name' => 'Snow Maker', 'desc' => 'Buy your first snow cannon', 'icon' => 'fa-solid fa-snowflake', 'target' => 1, 'reward' => 5000],
                ['key' => 'eco_80', 'name' => 'Green Resort', 'desc' => 'Reach eco score of 80+', 'icon' => 'fa-solid fa-leaf', 'target' => 80, 'reward' => 30000],
                ['key' => 'login_7', 'name' => 'Dedicated Manager', 'desc' => 'Log in 7 days in a row', 'icon' => 'fa-solid fa-fire', 'target' => 7, 'reward' => 10000],
                ['key' => 'login_30', 'name' => 'Loyal Manager', 'desc' => 'Log in 30 days in a row', 'icon' => 'fa-solid fa-crown', 'target' => 30, 'reward' => 50000],
                ['key' => 'first_loan', 'name' => 'Borrower', 'desc' => 'Take out your first loan', 'icon' => 'fa-solid fa-building-columns', 'target' => 1, 'reward' => 2000],
                ['key' => 'all_insurance', 'name' => 'Fully Insured', 'desc' => 'Activate all insurance policies', 'icon' => 'fa-solid fa-shield-halved', 'target' => 6, 'reward' => 20000],
                ['key' => 'staff_50', 'name' => 'Big Employer', 'desc' => 'Hire 50 staff members', 'icon' => 'fa-solid fa-people-group', 'target' => 50, 'reward' => 100000],
                ['key' => 'buildings_25', 'name' => 'Master Builder', 'desc' => 'Own 25 buildings', 'icon' => 'fa-solid fa-hammer', 'target' => 25, 'reward' => 100000],
            ];
            foreach ($defs as $d) {
                $db->table('achievements')->insert(['user_id' => $userId, 'achievement_key' => $d['key'], 'name' => $d['name'], 'description' => $d['desc'], 'icon' => $d['icon'], 'progress' => 0, 'target' => $d['target'], 'reward_amount' => $d['reward'], 'completed' => 0, 'claimed' => 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $achievements = $db->table('achievements')->where('user_id', $userId)->get()->getResultArray();
        }
        $this->updateProgress($userId, $db, $achievements);
        $achievements = $db->table('achievements')->where('user_id', $userId)->get()->getResultArray();
        $completed = count(array_filter($achievements, fn($a) => $a['completed']));
        return view('achievements/index', ['achievements' => $achievements, 'completed' => $completed, 'total' => count($achievements)]);
    }
    private function updateProgress($userId, $db, $achievements)
    {
        $staffCount = (new StaffModel())->where('user_id', $userId)->where('status !=', 'fired')->countAllResults();
        $buildingCount = (new BuildingModel())->where('user_id', $userId)->countAllResults();
        $cannonCount = (new SnowCannonModel())->where('user_id', $userId)->countAllResults();
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        $streak = $bonus ? (int)$bonus['streak'] : 0;
        $insuranceActive = $db->table('insurance')->where('user_id', $userId)->where('active', 1)->countAllResults();
        $eco = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        $ecoScore = $eco ? (int)$eco['eco_score'] : 0;
        $loanCount = $db->table('loans')->where('user_id', $userId)->countAllResults(false);
        $map = ['first_staff' => $staffCount, 'staff_10' => $staffCount, 'staff_50' => $staffCount, 'first_building' => $buildingCount, 'buildings_10' => $buildingCount, 'buildings_25' => $buildingCount, 'first_cannon' => $cannonCount, 'eco_80' => $ecoScore, 'login_7' => $streak, 'login_30' => $streak, 'first_loan' => $loanCount, 'all_insurance' => $insuranceActive];
        foreach ($achievements as $a) {
            $progress = $map[$a['achievement_key']] ?? 0;
            $completed = $progress >= (int)$a['target'] ? 1 : 0;
            $db->table('achievements')->where('id', $a['id'])->update(['progress' => min($progress, (int)$a['target']), 'completed' => $completed]);
        }
    }
    public function claim(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $a = $db->table('achievements')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$a || !$a['completed'] || $a['claimed']) return redirect()->back()->with('error', 'Cannot claim.');
        $db->table('achievements')->where('id', $id)->update(['claimed' => 1]);
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if ($finance) $db->table('player_finances')->where('id', $finance['id'])->update(['cash' => (int)$finance['cash'] + (int)$a['reward_amount']]);
        log_activity($userId, 'Achievement', 'Claimed reward for "' . $a['name'] . '"', 'fa-solid fa-award');
        return redirect()->to('/achievements')->with('success', 'Claimed ' . currency((int)$a['reward_amount']) . ' for "' . $a['name'] . '"!');
    }
}
