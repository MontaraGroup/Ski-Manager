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
        $snowmakers = $staffModel->where('user_id', $userId)->where('role', 'snowmaker')->where('status !=', 'fired')->findAll();

        $weatherModel = new WeatherModel();
        $weather = $weatherModel->orderBy('game_day', 'DESC')->first();
        $temp = $weather ? (int) $weather['temp'] : -5;
        $canMakeSnow = $temp <= -2;
        $snowBase = (int) ($weather['snow_base'] ?? 0);

        $activeCannons = array_filter($cannons, fn($c) => $c['status'] === 'active');
        $totalOutput = array_sum(array_column($activeCannons, 'output_per_day'));
        $totalEnergy = array_sum(array_column($activeCannons, 'energy_kwh'));
        $totalWater = array_sum(array_column($activeCannons, 'water_liters'));

        $energySources = $db->table('energy_management')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $waterSources = $db->table('water_management')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $energySupply = array_sum(array_column($energySources, 'output_kwh'));
        $waterSupply = array_sum(array_column($waterSources, 'output_liters'));

        $dailyEquipCost = array_sum(array_map(fn($c) => $c['status'] === 'active' ? (int)($c['daily_cost'] ?? 0) : 0, $cannons));
        $crewCost = array_sum(array_column($snowmakers, 'salary'));
        $repairCost = 5000;

        $itemModel = new \App\Models\PlayerItemModel();
        $slopes = $itemModel->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->findAll();

        return view('snowmaking/index', [
            'cannons' => $cannons,
            'activeCannons' => $activeCannons,
            'snowmakers' => $snowmakers,
            'temp' => $temp,
            'canMakeSnow' => $canMakeSnow,
            'snowBase' => $snowBase,
            'totalOutput' => $totalOutput,
            'totalEnergy' => $totalEnergy,
            'totalWater' => $totalWater,
            'energySupply' => $energySupply,
            'waterSupply' => $waterSupply,
            'dailyEquipCost' => $dailyEquipCost,
            'crewCost' => $crewCost,
            'repairCost' => $repairCost,
            'slopes' => $slopes,
        ]);
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');
        if ((int) $eq['condition_pct'] <= 0) return redirect()->back()->with('error', 'Broken — repair first.');

        $newStatus = $eq['status'] === 'active' ? 'off' : 'active';
        $db->table('equipment')->where('id', $id)->update(['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, 'Snowmaking', $eq['name'] . ($newStatus === 'active' ? ' turned on' : ' turned off'), 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ($newStatus === 'active' ? ' turned on.' : ' turned off.'));
    }

    public function toggleAll()
    {
        $userId = auth()->id();
        $action = $this->request->getPost('action');
        $db = db_connect();

        if ($action === 'on') {
            $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->where('condition_pct >', 0)->update(['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')]);
            log_activity($userId, 'Snowmaking', 'All cannons turned on', 'fa-solid fa-power-off');
            return redirect()->to('/snowmaking')->with('success', 'All working cannons turned on.');
        } else {
            $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->update(['status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
            log_activity($userId, 'Snowmaking', 'All cannons turned off', 'fa-solid fa-power-off');
            return redirect()->to('/snowmaking')->with('success', 'All cannons turned off.');
        }
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');

        $cost = 5000;
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if ((int)$finance['cash'] < $cost) return redirect()->back()->with('error', 'Not enough cash for repair.');

        $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash - ' . $cost, false)->update();
        $db->table('equipment')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, 'Snowmaking', 'Repaired ' . $eq['name'] . ' for ' . $cost, 'fa-solid fa-wrench');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' repaired for ' . currency($cost) . '.');
    }

    public function assignCannon()
    {
        $userId = auth()->id();
        $cannonId = (int) $this->request->getPost('cannon_id');
        $slopeId = $this->request->getPost('slope_id');
        $db = db_connect();

        $eq = $db->table('equipment')->where('id', $cannonId)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');

        $assignment = $slopeId ? 'slope_' . (int) $slopeId : null;
        $slopeName = '';
        if ($assignment) {
            $slope = $db->table('player_items')->where('id', (int) $slopeId)->where('user_id', $userId)->get()->getRowArray();
            $slopeName = $slope ? $slope['name'] : 'Unknown';
        }

        $db->table('equipment')->where('id', $cannonId)->update(['assigned_to' => $assignment, 'updated_at' => date('Y-m-d H:i:s')]);
        $msg = $assignment ? $eq['name'] . ' assigned to ' . $slopeName : $eq['name'] . ' unassigned';
        log_activity($userId, 'Snowmaking', $msg, 'fa-solid fa-snowflake');
        return redirect()->to('/snowmaking')->with('success', $msg);
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Cannon not found.');

        $refund = (int)(($eq['purchase_price'] ?? 0) * 0.5);
        if ($refund > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $refund, false)->update();
        }
        $db->table('equipment')->where('id', $id)->delete();
        log_activity($userId, 'Snowmaking', 'Sold ' . $eq['name'] . ' for ' . $refund, 'fa-solid fa-coins');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' sold' . ($refund > 0 ? ' for ' . currency($refund) : '') . '.');
    }
}
