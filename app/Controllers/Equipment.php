<?php

namespace App\Controllers;

class Equipment extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $groomers = $db->table('equipment_catalog')->where('equipment_type', 'groomer')->orderBy('sort_order')->get()->getResultArray();
        $snowmakers = $db->table('equipment_catalog')->where('equipment_type', 'snowmaker')->orderBy('sort_order')->get()->getResultArray();

        $groomerCatalog = [];
        foreach ($groomers as $g) {
            $groomerCatalog[$g['model_key']] = ['brand' => $g['brand'], 'name' => $g['name'], 'desc' => $g['description'], 'capacity' => (int) $g['capacity'], 'fuel' => (int) $g['fuel_cost'], 'cost' => (int) $g['cost'], 'img' => $g['icon'], 'output_per_day' => (int) $g['output_per_day'], 'energy_kwh' => (int) $g['energy_kwh'], 'water_liters' => (int) $g['water_liters']];
        }
        $snowmakerCatalog = [];
        foreach ($snowmakers as $s) {
            $snowmakerCatalog[$s['model_key']] = ['brand' => $s['brand'], 'name' => $s['name'], 'desc' => $s['description'], 'capacity' => (int) $s['capacity'], 'fuel' => (int) $s['fuel_cost'], 'cost' => (int) $s['cost'], 'img' => $s['icon'], 'output_per_day' => (int) $s['output_per_day'], 'energy_kwh' => (int) $s['energy_kwh'], 'water_liters' => (int) $s['water_liters']];
        }

        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();
        $ownedGroomers = array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer');
        $ownedSnowmakers = array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker');
        $totalFuel = array_sum(array_column(array_filter($equipment, fn($e) => $e['status'] === 'active'), 'fuel_cost'));

        return view('equipment/index', [
            'groomers' => $groomerCatalog,
            'snowmakers' => $snowmakerCatalog,
            'ownedGroomers' => $ownedGroomers,
            'ownedSnowmakers' => $ownedSnowmakers,
            'totalFuel' => $totalFuel,
            'equipment' => $equipment,
        ]);
    }

    private function getCatalogItem(string $modelKey): ?array
    {
        $db = db_connect();
        $item = $db->table('equipment_catalog')->where('model_key', $modelKey)->get()->getRowArray();
        if (!$item) return null;
        return ['brand' => $item['brand'], 'name' => $item['name'], 'desc' => $item['description'], 'capacity' => (int) $item['capacity'], 'fuel' => (int) $item['fuel_cost'], 'cost' => (int) $item['cost'], 'img' => $item['icon'], 'type' => $item['equipment_type'], 'output_per_day' => (int) $item['output_per_day'], 'energy_kwh' => (int) $item['energy_kwh'], 'water_liters' => (int) $item['water_liters']];
    }

    public function buy()
    {
        $userId = auth()->id();
        $modelKey = $this->request->getPost('model');

        $item = $this->getCatalogItem($modelKey);
        if (!$item) {
            return redirect()->back()->with('error', 'Invalid equipment.');
        }

        $db = db_connect();
        $count = $db->table('equipment')->where('user_id', $userId)->where('model_key', $modelKey)->countAllResults();

        $db->table('equipment')->insert([
            'user_id' => $userId,
            'equipment_type' => $item['type'],
            'model_key' => $modelKey,
            'name' => $item['name'] . ' #' . ($count + 1),
            'brand' => $item['brand'],
            'capacity' => $item['capacity'],
            'fuel_cost' => $item['fuel'],
            'output_per_day' => $item['output_per_day'],
            'energy_kwh' => $item['energy_kwh'],
            'water_liters' => $item['water_liters'],
            'condition_pct' => 100,
            'status' => 'off',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity($userId, 'Equipment', 'Purchased ' . $item['name'] . ' for ' . currency($item['cost']), $item['img']);
        return redirect()->to('/equipment')->with('success', $item['name'] . ' purchased for ' . currency($item['cost']) . '!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Not found.');
        if ((int) $eq['condition_pct'] <= 0) return redirect()->back()->with('error', 'Broken - repair first.');

        $new = $eq['status'] === 'active' ? 'off' : 'active';
        $db->table('equipment')->where('id', $id)->update(['status' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' turned ' . $new . '.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Not found.');

        $db->table('equipment')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, 'Equipment', 'Repaired ' . $eq['name'], 'fa-solid fa-wrench');
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' repaired.');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $db->table('equipment')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$eq) return redirect()->back()->with('error', 'Not found.');

        $db->table('equipment')->where('id', $id)->delete();
        log_activity($userId, 'Equipment', 'Sold ' . $eq['name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' sold.');
    }
}
