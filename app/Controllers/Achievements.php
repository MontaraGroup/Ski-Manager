<?php
namespace App\Controllers;
use App\Models\StaffModel;
use App\Models\BuildingModel;
use App\Models\SnowCannonModel;
class Achievements extends BaseController
{
    private function getAchievementDefs(): array
    {
        $db = db_connect();
        $rows = $db->table('achievement_defs')->orderBy('sort_order')->get()->getResultArray();
        $defs = [];
        foreach ($rows as $r) {
            $defs[] = ['key' => $r['achievement_key'], 'name' => $r['name'], 'desc' => $r['description'], 'icon' => $r['icon'], 'target' => (int) $r['target'], 'reward' => (int) $r['reward']];
        }
        return $defs;
    }

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $achievements = $db->table('achievements')->where('user_id', $userId)->get()->getResultArray();
        if (empty($achievements)) {
            foreach ($this->getAchievementDefs() as $d) {
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
        $streak = $bonus ? (int) $bonus['streak'] : 0;
        $insuranceActive = $db->table('insurance')->where('user_id', $userId)->where('active', 1)->countAllResults();
        $eco = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        $ecoScore = $eco ? (int) $eco['eco_score'] : 0;
        $loanCount = $db->table('loans')->where('user_id', $userId)->countAllResults(false);
        $map = ['first_staff' => $staffCount, 'staff_10' => $staffCount, 'staff_50' => $staffCount, 'first_building' => $buildingCount, 'buildings_10' => $buildingCount, 'buildings_25' => $buildingCount, 'first_cannon' => $cannonCount, 'eco_80' => $ecoScore, 'login_7' => $streak, 'login_30' => $streak, 'first_loan' => $loanCount, 'all_insurance' => $insuranceActive];
        foreach ($achievements as $a) {
            $progress = $map[$a['achievement_key']] ?? 0;
            $completed = $progress >= (int) $a['target'] ? 1 : 0;
            $db->table('achievements')->where('id', $a['id'])->update(['progress' => min($progress, (int) $a['target']), 'completed' => $completed]);
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
        if ($finance) $db->table('player_finances')->where('id', $finance['id'])->update(['cash' => (int) $finance['cash'] + (int) $a['reward_amount']]);
        log_activity($userId, 'Achievement', 'Claimed reward for "' . $a['name'] . '"', 'fa-solid fa-award');
        return redirect()->to('/achievements')->with('success', 'Claimed ' . currency((int) $a['reward_amount']) . ' for "' . $a['name'] . '"!');
    }
}
