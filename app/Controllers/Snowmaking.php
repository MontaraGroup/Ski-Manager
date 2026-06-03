<?php

namespace App\Controllers;

use App\Models\SnowCannonModel;
use App\Models\StaffModel;
use App\Models\WeatherModel;

class Snowmaking extends BaseController
{
    protected SnowCannonModel $cannonModel;

    public function __construct()
    {
        $this->cannonModel = new SnowCannonModel();
    }

    private function getCannonTypes(): array
    {
        $db = db_connect();
        $rows = $db->table('cannon_types')->orderBy('sort_order')->get()->getResultArray();
        $types = [];
        foreach ($rows as $r) {
            $types[(int) $r['level']] = [
                'name' => $r['name'], 'output' => (int) $r['output_per_day'],
                'energy' => (int) $r['energy_cost'], 'water' => (int) $r['water_usage'],
                'cost' => (int) $r['cost'],
            ];
        }
        return $types;
    }

    public function index(): string
    {
        $userId = auth()->id();
        $cannons = $this->cannonModel->where('user_id', $userId)->findAll();

        $staffModel = new StaffModel();
        $snowmakers = $staffModel->where('user_id', $userId)
            ->where('role', 'snowmaker')
            ->where('status', 'active')
            ->findAll();

        $weatherModel = new WeatherModel();
        $weather = $weatherModel->orderBy('game_day', 'DESC')->first();
        $temp = $weather ? (int) $weather['temp'] : -5;
        $canMakeSnow = $temp <= -2;

        $activeCannons = array_filter($cannons, fn($c) => $c['status'] === 'active');
        $totalOutput = array_sum(array_column($activeCannons, 'output_per_day'));
        $totalEnergy = array_sum(array_column($activeCannons, 'energy_cost'));
        $totalWater = array_sum(array_column($activeCannons, 'water_usage'));

        $db = db_connect();
        return view('snowmaking/index', [
            'cannons' => $cannons, 'snowmakers' => $snowmakers,
            'temp' => $temp, 'canMakeSnow' => $canMakeSnow,
            'totalOutput' => $totalOutput, 'totalEnergy' => $totalEnergy,
            'totalWater' => $totalWater, 'cannonTypes' => $this->getCannonTypes(),
        ]);
    }

    public function buy()
    {
        $userId = auth()->id();
        $level = (int) $this->request->getPost('level');
        $types = $this->getCannonTypes();

        if (!isset($types[$level])) {
            return redirect()->back()->with('error', 'Invalid cannon type.');
        }

        $type = $types[$level];
        $count = $this->cannonModel->where('user_id', $userId)->countAllResults();

        $this->cannonModel->insert([
            'user_id' => $userId,
            'cannon_name' => $type['name'] . ' #' . ($count + 1),
            'level' => $level,
            'output_per_day' => $type['output'],
            'energy_cost' => $type['energy'],
            'water_usage' => $type['water'],
            'status' => 'off',
            'condition_pct' => 100,
        ]);

        return redirect()->to('/snowmaking')->with('success', $type['name'] . ' purchased!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $cannon = $this->cannonModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$cannon) return redirect()->back()->with('error', 'Cannon not found.');
        if ($cannon['condition_pct'] <= 0) return redirect()->back()->with('error', 'This cannon is broken and needs repair.');

        $newStatus = $cannon['status'] === 'active' ? 'off' : 'active';
        $this->cannonModel->update($id, ['status' => $newStatus]);
        $label = $newStatus === 'active' ? 'turned on' : 'turned off';
        log_activity($userId, 'Snowmaking', $cannon['cannon_name'] . ' ' . $label, 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $cannon['cannon_name'] . ' ' . $label . '.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $cannon = $this->cannonModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$cannon) return redirect()->back()->with('error', 'Cannon not found.');

        $this->cannonModel->update($id, ['condition_pct' => 100, 'status' => 'off']);
        return redirect()->to('/snowmaking')->with('success', $cannon['cannon_name'] . ' repaired.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $cannon = $this->cannonModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$cannon) return redirect()->back()->with('error', 'Cannon not found.');

        $this->cannonModel->delete($id);
        log_activity($userId, 'Snowmaking', 'Sold ' . $cannon['cannon_name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/snowmaking')->with('success', $cannon['cannon_name'] . ' sold.');
    }
}
