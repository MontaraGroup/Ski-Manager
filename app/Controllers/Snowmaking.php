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

        $cannonTypes = [
            1 => ['name' => 'Fan Gun (Basic)', 'output' => 3, 'energy' => 500, 'water' => 100, 'cost' => 15000],
            2 => ['name' => 'Fan Gun (Advanced)', 'output' => 6, 'energy' => 800, 'water' => 180, 'cost' => 35000],
            3 => ['name' => 'Lance Gun', 'output' => 8, 'energy' => 600, 'water' => 220, 'cost' => 50000],
            4 => ['name' => 'Tower Gun', 'output' => 12, 'energy' => 1200, 'water' => 350, 'cost' => 85000],
            5 => ['name' => 'All-Weather System', 'output' => 15, 'energy' => 1800, 'water' => 500, 'cost' => 150000],
        ];

        $db = db_connect();
        return view('snowmaking/index', [
            'cannons' => $cannons,
            'snowmakers' => $snowmakers,
            'temp' => $temp,
            'canMakeSnow' => $canMakeSnow,
            'totalOutput' => $totalOutput,
            'totalEnergy' => $totalEnergy,
            'totalWater' => $totalWater,
            'cannonTypes' => $cannonTypes,
        ]);
    }

    public function buy()
    {
        $userId = auth()->id();
        $level = (int) $this->request->getPost('level');

        $types = [
            1 => ['name' => 'Fan Gun (Basic)', 'output' => 3, 'energy' => 500, 'water' => 100],
            2 => ['name' => 'Fan Gun (Advanced)', 'output' => 6, 'energy' => 800, 'water' => 180],
            3 => ['name' => 'Lance Gun', 'output' => 8, 'energy' => 600, 'water' => 220],
            4 => ['name' => 'Tower Gun', 'output' => 12, 'energy' => 1200, 'water' => 350],
            5 => ['name' => 'All-Weather System', 'output' => 15, 'energy' => 1800, 'water' => 500],
        ];

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

        if (!$cannon) {
            return redirect()->back()->with('error', 'Cannon not found.');
        }

        if ($cannon['condition_pct'] <= 0) {
            return redirect()->back()->with('error', 'This cannon is broken and needs repair.');
        }

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

        if (!$cannon) {
            return redirect()->back()->with('error', 'Cannon not found.');
        }

        $this->cannonModel->update($id, ['condition_pct' => 100, 'status' => 'off']);
        return redirect()->to('/snowmaking')->with('success', $cannon['cannon_name'] . ' repaired.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $cannon = $this->cannonModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$cannon) {
            return redirect()->back()->with('error', 'Cannon not found.');
        }

        $this->cannonModel->delete($id);
        log_activity($userId, 'Snowmaking', 'Sold ' . $cannon['cannon_name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/snowmaking')->with('success', $cannon['cannon_name'] . ' sold.');
    }
}
