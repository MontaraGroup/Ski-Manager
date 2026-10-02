<?php

namespace App\Controllers;

use App\Models\SnowCannonModel;
use App\Models\NightSkiingModel;
use App\Models\BuildingModel;

class Environment extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        if (!$env) {
            $db->table('environmental')->insert(['user_id' => $userId, 'eco_score' => 50, 'carbon_output' => 0, 'renewable_pct' => 0, 'waste_management' => 0, 'wildlife_impact' => 50, 'updated_at' => date('Y-m-d H:i:s')]);
            $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        }

        $cannonModel = new SnowCannonModel();
        $lightModel = new NightSkiingModel();
        $buildingModel = new BuildingModel();

        $activeCannons = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->where('status', 'active')->countAllResults();
        $activeLights = $lightModel->where('user_id', $userId)->where('status', 'active')->countAllResults();
        $totalBuildings = $buildingModel->where('user_id', $userId)->countAllResults();

        $carbonFromCannons = $activeCannons * 15;
        $carbonFromLights = $activeLights * 10;
        $carbonFromBuildings = $totalBuildings * 5;
        $totalCarbon = $carbonFromCannons + $carbonFromLights + $carbonFromBuildings;

        $ecoScore = max(0, min(100, 100 - $totalCarbon + (int) $env['renewable_pct'] + (int) $env['waste_management']));

        $db->table('environmental')->where('user_id', $userId)->update([
            'eco_score' => $ecoScore,
            'carbon_output' => $totalCarbon,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $env['eco_score'] = $ecoScore;
        $env['carbon_output'] = $totalCarbon;

        $upgrades = [
            ['name' => 'Solar Panels', 'icon' => 'fa-solid fa-solar-panel', 'desc' => '+10% renewable energy', 'field' => 'renewable_pct', 'boost' => 10, 'cost' => 50000],
            ['name' => 'Wind Turbine', 'icon' => 'fa-solid fa-wind', 'desc' => '+15% renewable energy', 'field' => 'renewable_pct', 'boost' => 15, 'cost' => 80000],
            ['name' => 'Recycling Center', 'icon' => 'fa-solid fa-recycle', 'desc' => '+10 waste management', 'field' => 'waste_management', 'boost' => 10, 'cost' => 30000],
            ['name' => 'Wildlife Corridor', 'icon' => 'fa-solid fa-paw', 'desc' => '+15 wildlife impact', 'field' => 'wildlife_impact', 'boost' => 15, 'cost' => 40000],
        ];

        return view('environment/index', [
            'env' => $env,
            'totalCarbon' => $totalCarbon,
            'carbonFromCannons' => $carbonFromCannons,
            'carbonFromLights' => $carbonFromLights,
            'carbonFromBuildings' => $carbonFromBuildings,
            'upgrades' => $upgrades,
        ]);
    }

    public function buyUpgrade()
    {
        $userId = auth()->id();
        $field = $this->request->getPost('field');
        $boost = (int) $this->request->getPost('boost');
        $upgradeName = (string) $this->request->getPost('name');

        $catalog = [
            'Solar Panels' => ['cost' => 50000, 'field' => 'renewable_pct', 'boost' => 10],
            'Wind Turbine' => ['cost' => 80000, 'field' => 'renewable_pct', 'boost' => 15],
            'Recycling Center' => ['cost' => 30000, 'field' => 'waste_management', 'boost' => 10],
            'Wildlife Corridor' => ['cost' => 40000, 'field' => 'wildlife_impact', 'boost' => 15],
        ];

        $matched = null;
        if (isset($catalog[$upgradeName])) {
            $matched = $catalog[$upgradeName];
        } else {
            foreach ($catalog as $name => $item) {
                if ($item['field'] === $field && $item['boost'] === $boost) {
                    $matched = $item;
                    $upgradeName = $name;
                    break;
                }
            }
        }

        if (!$matched) {
            return redirect()->back()->with('error', 'Invalid upgrade.');
        }

        $cost = $matched['cost'];
        $db = db_connect();

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $cost) {
            return redirect()->back()->with('error', 'Not enough cash to buy ' . $upgradeName . ' (' . currency($cost) . ' required).');
        }

        // Deduct cash
        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cost}", false)->update();

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId,
            'game_day' => $gameDay,
            'category' => 'Environmental',
            'description' => 'Purchased ' . $upgradeName,
            'amount' => $cost,
            'type' => 'expense',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        $newVal = min(100, (int) ($env[$matched['field']] ?? 0) + $matched['boost']);
        $db->table('environmental')->where('user_id', $userId)->update([$matched['field'] => $newVal, 'updated_at' => date('Y-m-d H:i:s')]);

        log_activity($userId, 'Environment', 'Purchased ' . $upgradeName . ' for ' . currency($cost), 'fa-solid fa-leaf');
        return redirect()->to('/environment')->with('success', $upgradeName . ' purchased for ' . currency($cost) . '!');
    }
}
