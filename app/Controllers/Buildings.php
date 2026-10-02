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
        $cost = (int) ($lvl['cost'] ?? 0);
        $db = db_connect();

        $fin = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if ((int) ($fin['cash'] ?? 0) < $cost) {
            return redirect()->back()->with('error', 'Not enough cash to construct ' . $lvl['name'] . ' (' . currency($cost) . ' required).');
        }

        $count = $this->model->where('user_id', $userId)->where('building_type', $type)->countAllResults();

        $db->transStart();
        if ($cost > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cost}", false)->update();
            $startDate = getSeasonStartDate();
            $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => $gameDay,
                'category' => 'Buildings', 'description' => 'Constructed ' . $lvl['name'],
                'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->model->insert([
            'user_id' => $userId, 'building_type' => $type,
            'name' => $lvl['name'] . ' #' . ($count + 1), 'level' => $level,
            'capacity' => $lvl['capacity'], 'revenue_per_day' => $lvl['revenue'],
            'upkeep_per_day' => $lvl['upkeep'], 'condition_pct' => 100, 'status' => 'open',
        ]);
        $db->transComplete();

        log_activity($userId, 'Building', 'Constructed ' . $lvl['name'] . ' for ' . currency($cost), 'fa-solid fa-hotel');
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
        $cost = (int) ($next['cost'] ?? 0);
        $db = db_connect();

        $fin = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if ((int) ($fin['cash'] ?? 0) < $cost) {
            return redirect()->back()->with('error', 'Not enough cash to upgrade ' . $building['name'] . ' (' . currency($cost) . ' required).');
        }

        $db->transStart();
        if ($cost > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$cost}", false)->update();
            $startDate = getSeasonStartDate();
            $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => $gameDay,
                'category' => 'Buildings', 'description' => 'Upgraded ' . $building['name'] . ' to Lv.' . $nextLevel,
                'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->model->update($id, [
            'level' => $nextLevel, 'capacity' => $next['capacity'],
            'revenue_per_day' => $next['revenue'], 'upkeep_per_day' => $next['upkeep'],
            'name' => $next['name'] . ' #' . substr($building['name'], -2),
        ]);
        $db->transComplete();

        log_activity($userId, 'Building', 'Upgraded ' . $building['name'] . ' to Lv.' . $nextLevel . ' for ' . currency($cost), 'fa-solid fa-arrow-up');
        return redirect()->to('/' . $defs[$type]['route'])->with('success', 'Upgraded to ' . $next['name'] . '!');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $defs = $this->getBuildingDefs();
        $type = $building['building_type'];
        $level = (int) $building['level'];
        $lvlCost = (int) ($defs[$type]['levels'][$level]['cost'] ?? 10000);
        $refund = (int) round($lvlCost * 0.25);
        $def = $defs[$type] ?? null;
        $route = $def ? $def['route'] : 'dashboard';
        $db = db_connect();

        $db->transStart();
        $this->model->delete($id);
        if ($refund > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash + {$refund}", false)->update();
            $startDate = getSeasonStartDate();
            $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => $gameDay,
                'category' => 'Buildings', 'description' => 'Demolished ' . $building['name'] . ' (Salvage Refund)',
                'amount' => $refund, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();

        log_activity($userId, 'Building', 'Demolished ' . $building['name'] . ' for ' . currency($refund) . ' salvage', 'fa-solid fa-money-bill-wave');
        return redirect()->to('/' . $route)->with('success', $building['name'] . ' demolished. ' . currency($refund) . ' refunded (25% salvage).');
    }
}
