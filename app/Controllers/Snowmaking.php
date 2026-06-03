<?php

namespace App\Controllers;

use App\Models\StaffModel;
use App\Models\WeatherModel;

class Snowmaking extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('snowmaking'); if ($locked) return $locked;
        $userId = auth()->id();
        $db = db_connect();

        $cannons = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getResultArray();

        $staffModel = new StaffModel();
        $snowmakers = $staffModel->where('user_id', $userId)->where('role', 'snowmaker')->where('status', 'active')->findAll();

        $weatherModel = new WeatherModel();
        $weather = $weatherModel->orderBy('game_day', 'DESC')->first();
        $temp = $weather ? (int) $weather['temp'] : -5;
        $canMakeSnow = $temp <= -2;

        $activeCannons = array_filter($cannons, fn($c) => $c['status'] === 'active');
        $totalOutput = array_sum(array_column($activeCannons, 'output_per_day'));
        $totalEnergy = array_sum(array_column($activeCannons, 'energy_kwh'));
        $totalWater = array_sum(array_column($activeCannons, 'water_liters'));

        return view('snowmaking/index', [
            'cannons' => $cannons,
            'snowmakers' => $snowmakers,
            'temp' => $temp,
            'canMakeSnow' => $canMakeSnow,
            'totalOutput' => $totalOutput,
            'totalEnergy' => $totalEnergy,
            'totalWater' => $totalWater,
        ]);
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');
        if ((int) $eq['condition_pct'] <= 0) return redirect()->back()->with('error', 'Broken - repair first.');

        $newStatus = $eq['status'] === 'active' ? 'off' : 'active';
        $db->table('equipment')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]);
        $label = $newStatus === 'active' ? 'turned on' : 'turned off';
        log_activity($userId, 'Snowmaking', $eq['name'] . ' ' . $label, 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' ' . $label . '.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');

        $db->table('equipment')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, 'Snowmaking', 'Repaired ' . $eq['name'], 'fa-solid fa-wrench');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' repaired.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');

        $db->table('equipment')->where('id', $id)->delete();
        log_activity($userId, 'Snowmaking', 'Sold ' . $eq['name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' sold.');
    }
}
