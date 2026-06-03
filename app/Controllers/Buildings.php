<?php

namespace App\Controllers;

use App\Models\BuildingModel;

class Buildings extends BaseController
{
    protected BuildingModel $model;

    public function __construct()
    {
        $this->model = new BuildingModel();
    }

    private function getBuildingDefs(): array
    {
        $db = db_connect();
        $types = $db->table('building_types')->orderBy('sort_order')->get()->getResultArray();
        $levels = $db->table('building_levels')->orderBy('level')->get()->getResultArray();

        $levelsByType = [];
        foreach ($levels as $l) {
            $levelsByType[$l['type_key']][(int) $l['level']] = [
                'name' => $l['name'], 'capacity' => (int) $l['capacity'],
                'revenue' => (int) $l['revenue'], 'upkeep' => (int) $l['upkeep'], 'cost' => (int) $l['cost'],
            ];
        }

        $defs = [];
        foreach ($types as $t) {
            $defs[$t['type_key']] = [
                'label' => $t['label'], 'singular' => $t['singular'],
                'icon' => $t['icon'], 'color' => $t['color'],
                'desc' => $t['description'], 'route' => $t['route'],
                'levels' => $levelsByType[$t['type_key']] ?? [],
            ];
        }
        return $defs;
    }

    public function show(string $type): string
    {
        $defs = $this->getBuildingDefs();
        if (!isset($defs[$type])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $userId = auth()->id();
        $def = $defs[$type];
        $buildings = $this->model->where('user_id', $userId)->where('building_type', $type)->findAll();

        $openBuildings = array_filter($buildings, fn($b) => $b['status'] === 'open');
        $totalCapacity = array_sum(array_column($openBuildings, 'capacity'));
        $totalRevenue = array_sum(array_column($openBuildings, 'revenue_per_day'));
        $totalUpkeep = array_sum(array_column($openBuildings, 'upkeep_per_day'));

        $viewFile = file_exists(APPPATH . 'Views/buildings/' . $type . '.php') ? 'buildings/' . $type : 'buildings/index';
        return view($viewFile, [
            'type' => $type, 'def' => $def, 'buildings' => $buildings,
            'totalCapacity' => $totalCapacity, 'totalRevenue' => $totalRevenue, 'totalUpkeep' => $totalUpkeep,
        ]);
    }

    public function build()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');
        $level = (int) $this->request->getPost('level');
        $defs = $this->getBuildingDefs();

        if (!isset($defs[$type]) || !isset($defs[$type]['levels'][$level])) {
            return redirect()->back()->with('error', 'Invalid building.');
        }

        $def = $defs[$type];
        $lvl = $def['levels'][$level];
        $count = $this->model->where('user_id', $userId)->where('building_type', $type)->countAllResults();

        $this->model->insert([
            'user_id' => $userId, 'building_type' => $type,
            'name' => $lvl['name'] . ' #' . ($count + 1), 'level' => $level,
            'capacity' => $lvl['capacity'], 'revenue_per_day' => $lvl['revenue'],
            'upkeep_per_day' => $lvl['upkeep'], 'condition_pct' => 100, 'status' => 'open',
        ]);

        return redirect()->to('/' . $def['route'])->with('success', $lvl['name'] . ' built!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $new = $building['status'] === 'open' ? 'closed' : 'open';
        $this->model->update($id, ['status' => $new]);

        $defs = $this->getBuildingDefs();
        $def = $defs[$building['building_type']] ?? null;
        $route = $def ? $def['route'] : 'dashboard';
        return redirect()->to('/' . $route)->with('success', $building['name'] . ' ' . $new . '.');
    }

    public function upgrade(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $defs = $this->getBuildingDefs();
        $type = $building['building_type'];
        $nextLevel = (int) $building['level'] + 1;

        if (!isset($defs[$type]['levels'][$nextLevel])) {
            return redirect()->back()->with('error', 'Already max level.');
        }

        $next = $defs[$type]['levels'][$nextLevel];
        $this->model->update($id, [
            'level' => $nextLevel, 'capacity' => $next['capacity'],
            'revenue_per_day' => $next['revenue'], 'upkeep_per_day' => $next['upkeep'],
            'name' => $next['name'] . ' #' . substr($building['name'], -2),
        ]);
        log_activity($userId, 'Building', 'Upgraded ' . $building['name'] . ' to Lv.' . $nextLevel, 'fa-solid fa-arrow-up');

        return redirect()->to('/' . $defs[$type]['route'])->with('success', 'Upgraded to ' . $next['name'] . '!');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $defs = $this->getBuildingDefs();
        $def = $defs[$building['building_type']] ?? null;
        $route = $def ? $def['route'] : 'dashboard';

        $this->model->delete($id);
        log_activity($userId, 'Building', 'Sold ' . $building['name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/' . $route)->with('success', $building['name'] . ' sold.');
    }
}
