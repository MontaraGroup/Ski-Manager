<?php

namespace App\Controllers;

class Resources extends BaseController
{
    public const ENERGY_SOURCES = [
        'grid' => [
            'label' => 'Power Grid Connection',
            'icon' => 'fa-solid fa-plug',
            'cost' => 10000,
            'build_days' => 1,
            'capacity' => 500,
            'output' => 500,
            'upkeep' => 800,
            'decay' => 0.1,
        ],
        'solar' => [
            'label' => 'Solar Panel Array',
            'icon' => 'fa-solid fa-solar-panel',
            'cost' => 75000,
            'build_days' => 4,
            'capacity' => 200,
            'output' => 200,
            'upkeep' => 100,
            'decay' => 0.2,
        ],
        'wind' => [
            'label' => 'Wind Turbine',
            'icon' => 'fa-solid fa-wind',
            'cost' => 120000,
            'build_days' => 6,
            'capacity' => 400,
            'output' => 400,
            'upkeep' => 200,
            'decay' => 0.3,
        ],
        'generator' => [
            'label' => 'Diesel Generator',
            'icon' => 'fa-solid fa-car-battery',
            'cost' => 25000,
            'build_days' => 2,
            'capacity' => 300,
            'output' => 300,
            'upkeep' => 1200,
            'decay' => 0.5,
        ],
    ];

    public const WATER_SOURCES = [
        'reservoir' => [
            'label' => 'Water Reservoir',
            'icon' => 'fa-solid fa-water',
            'cost' => 50000,
            'build_days' => 5,
            'capacity' => 100000,
            'output' => 20000,
            'upkeep' => 300,
            'decay' => 0.1,
        ],
        'well' => [
            'label' => 'Deep Well',
            'icon' => 'fa-solid fa-arrow-down-long',
            'cost' => 30000,
            'build_days' => 3,
            'capacity' => 40000,
            'output' => 10000,
            'upkeep' => 150,
            'decay' => 0.15,
        ],
        'river_pump' => [
            'label' => 'River Pump Station',
            'icon' => 'fa-solid fa-faucet-drip',
            'cost' => 40000,
            'build_days' => 3,
            'capacity' => 60000,
            'output' => 15000,
            'upkeep' => 400,
            'decay' => 0.3,
        ],
        'recycling_plant' => [
            'label' => 'Water Recycling Plant',
            'icon' => 'fa-solid fa-recycle',
            'cost' => 150000,
            'build_days' => 8,
            'capacity' => 80000,
            'output' => 25000,
            'upkeep' => 500,
            'decay' => 0.2,
        ],
    ];

    public function energy(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $sources = $db->table('energy_management')->where('user_id', $userId)->get()->getResultArray();

        $totalCapacity = 0; $totalOutput = 0; $totalUpkeep = 0;
        foreach ($sources as $s) {
            if ($s['status'] === 'active') {
                $totalCapacity += $s['capacity_kwh'];
                $totalOutput += $s['output_kwh'];
                $cfg = self::ENERGY_SOURCES[$s['source_type']] ?? [];
                $totalUpkeep += $cfg['upkeep'] ?? 0;
            }
        }

        $snowmakingDraw = $db->table('snow_cannons')->where('user_id', $userId)->where('status', 'active')->countAllResults(false) * 50;
        $nightSkiDraw = $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->countAllResults(false) * 80;
        $liftDraw = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults(false) * 30;
        $buildingDraw = $db->table('buildings')->where('user_id', $userId)->countAllResults(false) * 20;
        $totalDemand = $snowmakingDraw + $nightSkiDraw + $liftDraw + $buildingDraw;

        return view('resources/energy', [
            'sources' => $sources,
            'energyConfig' => self::ENERGY_SOURCES,
            'totalCapacity' => $totalCapacity,
            'totalOutput' => $totalOutput,
            'totalDemand' => $totalDemand,
            'totalUpkeep' => $totalUpkeep,
            'snowmakingDraw' => $snowmakingDraw,
            'nightSkiDraw' => $nightSkiDraw,
            'liftDraw' => $liftDraw,
            'buildingDraw' => $buildingDraw,
        ]);
    }

    public function water(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $sources = $db->table('water_management')->where('user_id', $userId)->get()->getResultArray();

        $totalCapacity = 0; $totalOutput = 0; $totalUpkeep = 0;
        foreach ($sources as $s) {
            if ($s['status'] === 'active') {
                $totalCapacity += $s['capacity_liters'];
                $totalOutput += $s['output_liters'];
                $cfg = self::WATER_SOURCES[$s['source_type']] ?? [];
                $totalUpkeep += $cfg['upkeep'] ?? 0;
            }
        }

        $snowmakingDraw = $db->table('snow_cannons')->where('user_id', $userId)->where('status', 'active')->countAllResults(false) * 5000;
        $totalDemand = $snowmakingDraw;

        return view('resources/water', [
            'sources' => $sources,
            'waterConfig' => self::WATER_SOURCES,
            'totalCapacity' => $totalCapacity,
            'totalOutput' => $totalOutput,
            'totalDemand' => $totalDemand,
            'totalUpkeep' => $totalUpkeep,
            'snowmakingDraw' => $snowmakingDraw,
        ]);
    }

    public function buildEnergy()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('source_type');
        $name = $this->request->getPost('name');
        $cfg = self::ENERGY_SOURCES[$type] ?? null;
        if (!$cfg) return redirect()->to('/energy')->with('error', 'Invalid source type.');

        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $cfg['cost']) return redirect()->to('/energy')->with('error', 'Not enough cash.');

        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cfg['cost']}", false)->update();
        $db->table('energy_management')->insert([
            'user_id' => $userId, 'source_type' => $type,
            'name' => $name ?: $cfg['label'],
            'capacity_kwh' => $cfg['capacity'], 'output_kwh' => $cfg['output'],
            'cost_per_day' => $cfg['upkeep'], 'status' => 'under_construction',
            'build_days_left' => $cfg['build_days'],
        ]);
        log_activity($userId, 'energy_build', "Started building " . $cfg['label'] . " for " . currency($cfg['cost']));
        return redirect()->to('/energy')->with('success', $cfg['label'] . ' is under construction!');
    }

    public function buildWater()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('source_type');
        $name = $this->request->getPost('name');
        $cfg = self::WATER_SOURCES[$type] ?? null;
        if (!$cfg) return redirect()->to('/water')->with('error', 'Invalid source type.');

        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $cfg['cost']) return redirect()->to('/water')->with('error', 'Not enough cash.');

        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cfg['cost']}", false)->update();
        $db->table('water_management')->insert([
            'user_id' => $userId, 'source_type' => $type,
            'name' => $name ?: $cfg['label'],
            'capacity_liters' => $cfg['capacity'], 'output_liters' => $cfg['output'],
            'cost_per_day' => $cfg['upkeep'], 'status' => 'under_construction',
            'build_days_left' => $cfg['build_days'],
        ]);
        log_activity($userId, 'water_build', "Started building " . $cfg['label'] . " for " . currency($cfg['cost']));
        return redirect()->to('/water')->with('success', $cfg['label'] . ' is under construction!');
    }

    public function toggleEnergy(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $source = $db->table('energy_management')->where('id', $id)->where('user_id', $userId)->first();
        if (!$source || $source['status'] === 'under_construction') return redirect()->to('/energy')->with('error', 'Cannot toggle.');
        $new = $source['status'] === 'active' ? 'off' : 'active';
        $db->table('energy_management')->where('id', $id)->update(['status' => $new]);
        log_activity($userId, 'energy_toggle', ucfirst($new) . " " . $source['name']);
        return redirect()->to('/energy')->with('success', $source['name'] . ' ' . $new . '.');
    }

    public function toggleWater(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $source = $db->table('water_management')->where('id', $id)->where('user_id', $userId)->first();
        if (!$source || $source['status'] === 'under_construction') return redirect()->to('/water')->with('error', 'Cannot toggle.');
        $new = $source['status'] === 'active' ? 'off' : 'active';
        $db->table('water_management')->where('id', $id)->update(['status' => $new]);
        log_activity($userId, 'water_toggle', ucfirst($new) . " " . $source['name']);
        return redirect()->to('/water')->with('success', $source['name'] . ' ' . $new . '.');
    }

    public function repairEnergy(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $source = $db->table('energy_management')->where('id', $id)->where('user_id', $userId)->first();
        if (!$source) return redirect()->to('/energy')->with('error', 'Not found.');
        $cfg = self::ENERGY_SOURCES[$source['source_type']] ?? [];
        $cost = round($cfg['cost'] * 0.1 * (1 - $source['condition_pct'] / 100));
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $cost) return redirect()->to('/energy')->with('error', 'Not enough cash.');
        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cost}", false)->update();
        $db->table('energy_management')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'active']);
        log_activity($userId, 'energy_repair', "Repaired " . $source['name'] . " for " . currency($cost));
        return redirect()->to('/energy')->with('success', $source['name'] . ' repaired for ' . currency($cost) . '.');
    }

    public function repairWater(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $source = $db->table('water_management')->where('id', $id)->where('user_id', $userId)->first();
        if (!$source) return redirect()->to('/water')->with('error', 'Not found.');
        $cfg = self::WATER_SOURCES[$source['source_type']] ?? [];
        $cost = round($cfg['cost'] * 0.1 * (1 - $source['condition_pct'] / 100));
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $cost) return redirect()->to('/water')->with('error', 'Not enough cash.');
        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cost}", false)->update();
        $db->table('water_management')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'active']);
        log_activity($userId, 'water_repair', "Repaired " . $source['name'] . " for " . currency($cost));
        return redirect()->to('/water')->with('success', $source['name'] . ' repaired for ' . currency($cost) . '.');
    }
}
