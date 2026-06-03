<?php

namespace App\Controllers;

class Resources extends BaseController
{
    private function getResourceConfig(string $category): array
    {
        $db = db_connect();
        $rows = $db->table('resource_types')->where('category', $category)->orderBy('sort_order')->get()->getResultArray();
        $config = [];
        foreach ($rows as $r) {
            $config[$r['resource_key']] = [
                'label' => $r['label'], 'icon' => $r['icon'],
                'cost' => (int) $r['cost'], 'build_days' => (int) $r['build_days'],
                'capacity' => (int) $r['capacity'], 'output' => (int) $r['output'],
                'upkeep' => (int) $r['upkeep'], 'decay' => (float) $r['decay_rate'],
            ];
        }
        return $config;
    }

    public function energy(): string
    {
        if (isPageHidden('energy')) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $userId = auth()->id();
        $db = db_connect();
        $energyConfig = $this->getResourceConfig('energy');
        $sources = $db->table('energy_management')->where('user_id', $userId)->get()->getResultArray();

        $totalCapacity = 0; $totalOutput = 0; $totalUpkeep = 0;
        foreach ($sources as $s) {
            if ($s['status'] === 'active') {
                $totalCapacity += $s['capacity_kwh'];
                $totalOutput += $s['output_kwh'];
                $cfg = $energyConfig[$s['source_type']] ?? [];
                $totalUpkeep += $cfg['upkeep'] ?? 0;
            }
        }

        $snowmakingDraw = $db->table('snow_cannons')->where('user_id', $userId)->where('status', 'active')->countAllResults(false) * 50;
        $nightSkiDraw = $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->countAllResults(false) * 80;
        $liftDraw = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults(false) * 30;
        $buildingDraw = $db->table('buildings')->where('user_id', $userId)->countAllResults(false) * 20;
        $totalDemand = $snowmakingDraw + $nightSkiDraw + $liftDraw + $buildingDraw;

        return view('resources/energy', [
            'sources' => $sources, 'energyConfig' => $energyConfig,
            'totalCapacity' => $totalCapacity, 'totalOutput' => $totalOutput,
            'totalDemand' => $totalDemand, 'totalUpkeep' => $totalUpkeep,
            'snowmakingDraw' => $snowmakingDraw, 'nightSkiDraw' => $nightSkiDraw,
            'liftDraw' => $liftDraw, 'buildingDraw' => $buildingDraw,
        ]);
    }

    public function water(): string
    {
        if (isPageHidden('water')) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $userId = auth()->id();
        $db = db_connect();
        $waterConfig = $this->getResourceConfig('water');
        $sources = $db->table('water_management')->where('user_id', $userId)->get()->getResultArray();

        $totalCapacity = 0; $totalOutput = 0; $totalUpkeep = 0;
        foreach ($sources as $s) {
            if ($s['status'] === 'active') {
                $totalCapacity += $s['capacity_liters'];
                $totalOutput += $s['output_liters'];
                $cfg = $waterConfig[$s['source_type']] ?? [];
                $totalUpkeep += $cfg['upkeep'] ?? 0;
            }
        }

        $snowmakingDraw = $db->table('snow_cannons')->where('user_id', $userId)->where('status', 'active')->countAllResults(false) * 5000;
        $totalDemand = $snowmakingDraw;

        return view('resources/water', [
            'sources' => $sources, 'waterConfig' => $waterConfig,
            'totalCapacity' => $totalCapacity, 'totalOutput' => $totalOutput,
            'totalDemand' => $totalDemand, 'totalUpkeep' => $totalUpkeep,
            'snowmakingDraw' => $snowmakingDraw,
        ]);
    }

    public function buildEnergy()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('source_type');
        $name = $this->request->getPost('name');
        $cfg = $this->getResourceConfig('energy')[$type] ?? null;
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
        $cfg = $this->getResourceConfig('water')[$type] ?? null;
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
        $cfg = $this->getResourceConfig('energy')[$source['source_type']] ?? [];
        $cost = round(($cfg['cost'] ?? 0) * 0.1 * (1 - $source['condition_pct'] / 100));
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
        $cfg = $this->getResourceConfig('water')[$source['source_type']] ?? [];
        $cost = round(($cfg['cost'] ?? 0) * 0.1 * (1 - $source['condition_pct'] / 100));
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $cost) return redirect()->to('/water')->with('error', 'Not enough cash.');
        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cost}", false)->update();
        $db->table('water_management')->where('id', $id)->update(['condition_pct' => 100, 'status' => 'active']);
        log_activity($userId, 'water_repair', "Repaired " . $source['name'] . " for " . currency($cost));
        return redirect()->to('/water')->with('success', $source['name'] . ' repaired for ' . currency($cost) . '.');
    }
}
