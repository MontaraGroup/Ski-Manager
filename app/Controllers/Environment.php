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

        $activeCannons = $cannonModel->where('user_id', $userId)->where('status', 'active')->countAllResults();
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

        if (!in_array($field, ['renewable_pct', 'waste_management', 'wildlife_impact'])) {
            return redirect()->back()->with('error', 'Invalid upgrade.');
        }

        $db = db_connect();
        $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
        $newVal = min(100, (int) $env[$field] + $boost);
        $db->table('environmental')->where('user_id', $userId)->update([$field => $newVal, 'updated_at' => date('Y-m-d H:i:s')]);

        log_activity($userId, 'Environment', 'Purchased environmental upgrade', 'fa-solid fa-leaf');
        return redirect()->to('/environment')->with('success', 'Environmental upgrade purchased!');
    }
}
