<?php

namespace App\Controllers;

use App\Models\NightSkiingModel;
use App\Models\StaffModel;

class NightSkiing extends BaseController
{
    protected NightSkiingModel $lightModel;

    public function __construct()
    {
        $this->lightModel = new NightSkiingModel();
    }

    public function index(): string
    {
        $userId = auth()->id();
        $lights = $this->lightModel->where('user_id', $userId)->findAll();

        $staffModel = new StaffModel();
        $mechanics = $staffModel->where('user_id', $userId)
            ->where('role', 'mechanic')
            ->where('status', 'active')
            ->countAllResults();

        $activeLights = array_filter($lights, fn($l) => $l['status'] === 'active');
        $totalCoverage = array_sum(array_column($activeLights, 'coverage'));
        $totalEnergy = array_sum(array_column($activeLights, 'energy_cost'));

        $nightHours = 5;
        $visitorBonus = min(100, $totalCoverage);
        $extraRevenue = round($visitorBonus * 0.3);

        $lightTypes = [
            'basic_flood' => ['name' => 'Basic Floodlight', 'icon' => 'fa-solid fa-lightbulb', 'coverage' => 5, 'energy' => 200, 'cost' => 8000, 'desc' => 'Simple lighting for beginner slopes'],
            'led_tower' => ['name' => 'LED Tower', 'icon' => 'fa-solid fa-tower-broadcast', 'coverage' => 12, 'energy' => 350, 'cost' => 22000, 'desc' => 'Energy-efficient, bright coverage'],
            'stadium_light' => ['name' => 'Stadium Light', 'icon' => 'fa-solid fa-bolt', 'coverage' => 20, 'energy' => 600, 'cost' => 45000, 'desc' => 'High-power lighting for large areas'],
            'smart_led' => ['name' => 'Smart LED System', 'icon' => 'fa-solid fa-microchip', 'coverage' => 25, 'energy' => 400, 'cost' => 65000, 'desc' => 'Auto-dimming, weather-adaptive'],
            'aurora_system' => ['name' => 'Aurora Display System', 'icon' => 'fa-solid fa-wand-magic-sparkles', 'coverage' => 30, 'energy' => 800, 'cost' => 120000, 'desc' => 'Spectacular colored lighting, boosts prestige'],
        ];

        return view('nightskiing/index', [
            'lights' => $lights,
            'lightTypes' => $lightTypes,
            'totalCoverage' => $totalCoverage,
            'totalEnergy' => $totalEnergy,
            'visitorBonus' => $visitorBonus,
            'extraRevenue' => $extraRevenue,
            'nightHours' => $nightHours,
            'mechanics' => $mechanics,
        ]);
    }

    public function buy()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');

        $types = [
            'basic_flood' => ['name' => 'Basic Floodlight', 'coverage' => 5, 'energy' => 200],
            'led_tower' => ['name' => 'LED Tower', 'coverage' => 12, 'energy' => 350],
            'stadium_light' => ['name' => 'Stadium Light', 'coverage' => 20, 'energy' => 600],
            'smart_led' => ['name' => 'Smart LED System', 'coverage' => 25, 'energy' => 400],
            'aurora_system' => ['name' => 'Aurora Display System', 'coverage' => 30, 'energy' => 800],
        ];

        if (!isset($types[$type])) {
            return redirect()->back()->with('error', 'Invalid light type.');
        }

        $t = $types[$type];
        $count = $this->lightModel->where('user_id', $userId)->countAllResults();

        $this->lightModel->insert([
            'user_id' => $userId,
            'light_name' => $t['name'] . ' #' . ($count + 1),
            'light_type' => $type,
            'level' => 1,
            'coverage' => $t['coverage'],
            'energy_cost' => $t['energy'],
            'status' => 'off',
            'condition_pct' => 100,
        ]);

        return redirect()->to('/night-skiing')->with('success', $t['name'] . ' installed!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $light = $this->lightModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$light) return redirect()->back()->with('error', 'Light not found.');

        if ($light['condition_pct'] <= 0) {
            return redirect()->back()->with('error', 'This light is broken and needs repair.');
        }

        $new = $light['status'] === 'active' ? 'off' : 'active';
        $this->lightModel->update($id, ['status' => $new]);
        log_activity($userId, 'Night Skiing', $light['light_name'] . ' turned ' . ($new === 'active' ? 'on' : 'off'), 'fa-solid fa-power-off');

        return redirect()->to('/night-skiing')->with('success', $light['light_name'] . ' turned ' . ($new === 'active' ? 'on' : 'off') . '.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $light = $this->lightModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$light) return redirect()->back()->with('error', 'Light not found.');

        $this->lightModel->update($id, ['condition_pct' => 100, 'status' => 'off']);
        return redirect()->to('/night-skiing')->with('success', $light['light_name'] . ' repaired.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $light = $this->lightModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$light) return redirect()->back()->with('error', 'Light not found.');

        $this->lightModel->delete($id);
        log_activity($userId, 'Night Skiing', 'Removed ' . $light['light_name'], 'fa-solid fa-trash');
        return redirect()->to('/night-skiing')->with('success', $light['light_name'] . ' removed.');
    }
}
