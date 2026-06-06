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

    public function index()
    {
        if (!auth()->loggedIn()) return redirect()->to("/login");
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
        usort($achievements, function($a, $b) {
            $aClaimed = $a['claimed'] ? 1 : 0;
            $bClaimed = $b['claimed'] ? 1 : 0;
            if ($aClaimed !== $bClaimed) return $aClaimed - $bClaimed;
            $aClaimable = $a['completed'] && !$a['claimed'] ? 1 : 0;
            $bClaimable = $b['completed'] && !$b['claimed'] ? 1 : 0;
            if ($aClaimable !== $bClaimable) return $bClaimable - $aClaimable;
            $aProg = (int) $a['target'] > 0 ? (int) $a['progress'] / (int) $a['target'] : 0;
            $bProg = (int) $b['target'] > 0 ? (int) $b['progress'] / (int) $b['target'] : 0;
            return $bProg <=> $aProg;
        });
        $completed = count(array_filter($achievements, fn($a) => $a['completed']));
        $unlockMap = [];
        $defs = $db->table('achievement_defs')->where('unlocks IS NOT NULL')->get()->getResultArray();
        foreach ($defs as $d) { $unlockMap[$d['achievement_key']] = $d['unlock_label']; }
        return view('achievements/index', ['achievements' => $achievements, 'completed' => $completed, 'total' => count($achievements), 'unlockMap' => $unlockMap]);
    }

    private function updateProgress($userId, $db, $achievements)
    {
        $staffAll = $db->table('staff')->where('user_id', $userId)->where('status !=', 'fired')->get()->getResultArray();
        $staffCount = count($staffAll);
        $buildingCount = $db->table('buildings')->where('user_id', $userId)->countAllResults();
        $cannonCount = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->countAllResults();
        $groomerCount = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->countAllResults();
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        $streak = $bonus ? (int) $bonus['streak'] : 0;
        $insuranceActive = $db->table('insurance')->where('user_id', $userId)->where('active', 1)->countAllResults();
        $eco = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        $ecoScore = $eco ? (int) $eco['eco_score'] : 0;
        $loanCount = $db->table('loans')->where('user_id', $userId)->countAllResults(false);
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $cash = (int) ($finance['cash'] ?? 0);
        $visitors = (int) ($finance['daily_visitors'] ?? 0);
        $liftCount = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->countAllResults();
        $slopeCount = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->countAllResults();
        $hotelCount = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'hotel')->countAllResults();
        $restaurantCount = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'restaurant')->countAllResults();
        $parkCount = $db->table('terrain_parks')->where('user_id', $userId)->countAllResults();
        $vipCount = $db->table('vip_guests')->where('user_id', $userId)->countAllResults();
        $vipRoyalty = $db->table('vip_guests')->where('user_id', $userId)->where('vip_type', 'royalty')->countAllResults();
        $campaignCount = $db->table('marketing_campaigns')->where('user_id', $userId)->countAllResults();
        $energyCount = $db->table('energy_management')->where('user_id', $userId)->countAllResults();
        $waterCount = $db->table('water_management')->where('user_id', $userId)->countAllResults();
        $parkingCap = $db->table('parking')->where('user_id', $userId)->where('status', 'open')->selectSum('capacity')->get()->getRowArray();
        $totalParkingCap = (int) ($parkingCap['capacity'] ?? 0);
        $regsCompliant = $db->table('regulations')->where('user_id', $userId)->where('compliant', 1)->countAllResults();
        $activeLoans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->countAllResults();
        $noDebt = $loanCount > 0 && $activeLoans === 0 ? 1 : 0;
        $uniqueBrands = $db->table('equipment')->where('user_id', $userId)->select('brand')->distinct()->get()->getResultArray();
        $brandCount = count($uniqueBrands);
        $uniqueRoles = array_unique(array_column($staffAll, 'role'));
        $roleCount = count($uniqueRoles);
        $allHighMorale = $staffCount > 0 && min(array_column($staffAll, 'morale') ?: [0]) >= 80 ? 1 : 0;
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
        $rating = function_exists('resortRating') ? resortRating($userId) : 0;
        $patrolStations = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'ski_patrol')->where('status', 'open')->get()->getResultArray();
        $coverage = $slopeCount > 0 ? min(100, round(array_sum(array_column($patrolStations, 'capacity')) / $slopeCount * 100)) : 100;
        $safetyScore = min(100, round($coverage * 0.4 + $staffCount * 2));

        $map = [
            'first_staff' => $staffCount, 'staff_10' => $staffCount, 'staff_25' => $staffCount, 'staff_50' => $staffCount, 'staff_100' => $staffCount,
            'first_building' => $buildingCount, 'buildings_10' => $buildingCount, 'buildings_25' => $buildingCount, 'buildings_50' => $buildingCount,
            'first_cannon' => $cannonCount, 'eco_80' => $ecoScore, 'login_7' => $streak, 'login_30' => $streak,
            'first_loan' => $loanCount, 'all_insurance' => $insuranceActive, 'no_debt' => $noDebt,
            'cash_100k' => $cash, 'cash_1m' => $cash, 'cash_10m' => $cash,
            'lifts_5' => $liftCount, 'lifts_10' => $liftCount, 'slopes_10' => $slopeCount, 'slopes_25' => $slopeCount,
            'hotels_5' => $hotelCount, 'restaurants_5' => $restaurantCount,
            'groomers_5' => $groomerCount, 'snowmakers_10' => $cannonCount, 'all_brands' => $brandCount,
            'all_roles' => $roleCount, 'high_morale' => $allHighMorale,
            'visitors_500' => $visitors, 'visitors_1000' => $visitors, 'visitors_5000' => $visitors,
            'first_vip' => $vipCount, 'vip_10' => $vipCount, 'vip_royalty' => $vipRoyalty,
            'first_park' => $parkCount, 'parks_5' => $parkCount,
            'first_campaign' => $campaignCount, 'campaigns_10' => $campaignCount,
            'first_energy' => $energyCount, 'first_water' => $waterCount, 'green_energy' => 0,
            'first_parking' => $totalParkingCap > 0 ? 1 : 0, 'parking_1000' => $totalParkingCap,
            'full_compliance' => $regsCompliant, 'zero_fines' => 0,
            'survive_summer' => 0, 'summer_profit' => 0,
            'day_30' => $gameDay, 'day_100' => $gameDay, 'day_365' => $gameDay,
            'rating_5' => $rating, 'full_coverage' => $coverage, 'safety_80' => $safetyScore,
        ];

        foreach ($achievements as $a) {
            $progress = $map[$a['achievement_key']] ?? 0;
            $completed = $progress >= (int) $a['target'] ? 1 : 0;
            $db->table('achievements')->where('id', $a['id'])->update(['progress' => min($progress, (int) $a['target']), 'completed' => $completed]);
        }
    }

    public function claim(int $id)
    {
        if (!auth()->loggedIn()) return redirect()->to("/login");
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

    public function claimAll()
    {
        if (!auth()->loggedIn()) return redirect()->to("/login");
        $userId = auth()->id();
        $db = db_connect();
        $claimable = $db->table('achievements')->where('user_id', $userId)->where('completed', 1)->where('claimed', 0)->get()->getResultArray();

        if (empty($claimable)) return redirect()->to('/achievements')->with('error', 'Nothing to claim.');

        $totalReward = 0;
        foreach ($claimable as $a) {
            $db->table('achievements')->where('id', $a['id'])->update(['claimed' => 1]);
            $totalReward += (int) $a['reward_amount'];
        }

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if ($finance) $db->table('player_finances')->where('id', $finance['id'])->update(['cash' => (int) $finance['cash'] + $totalReward]);

        log_activity($userId, 'Achievement', 'Claimed ' . count($claimable) . ' achievements for ' . number_format($totalReward) . ' total', 'fa-solid fa-award');
        return redirect()->to('/achievements')->with('success', 'Claimed ' . count($claimable) . ' achievements for ' . currency($totalReward) . '!');
    }
}
