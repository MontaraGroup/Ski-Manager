<?php
namespace App\Controllers;
class OffSeason extends BaseController
{
    private function getActivityConfig(): array
    {
        $db = db_connect();
        $rows = $db->table('summer_activity_types')->orderBy('sort_order')->get()->getResultArray();
        $config = [];
        foreach ($rows as $r) {
            $config[$r['activity_key']] = ['name' => $r['name'], 'icon' => $r['icon'], 'cost' => (int) $r['cost'], 'revenue' => (int) $r['revenue'], 'upkeep' => (int) $r['upkeep'], 'capacity' => (int) $r['capacity'], 'desc' => $r['description']];
        }
        return $config;
    }

    private function getMaintenanceTasks(): array
    {
        $db = db_connect();
        $rows = $db->table('maintenance_task_types')->orderBy('sort_order')->get()->getResultArray();
        $tasks = [];
        foreach ($rows as $r) {
            $tasks[$r['task_key']] = ['name' => $r['name'], 'icon' => $r['icon'], 'desc' => $r['description'], 'cost_per_unit' => (int) $r['cost_per_unit'], 'target' => $r['target']];
        }
        return $tasks;
    }

    public function index(): string
    {
        $locked = checkFeatureUnlock('off_season'); if ($locked) return $locked;
        $userId = auth()->id();
        $db = db_connect();
        $gameDay = max(1, (int) ((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
        $seasonDay = (($gameDay - 1) % 135) + 1;
        $isWinter = $seasonDay <= 100;
        $isSummer = !$isWinter;

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $summerActivities = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'off_season')->get()->getResultArray();
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->get()->getResultArray();
        $lifts = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->get()->getResultArray();
        $buildings = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();
        $terrainParks = $db->table('terrain_parks')->where('user_id', $userId)->get()->getResultArray();

        $avgSlopeCond = count($slopes) > 0 ? round(array_sum(array_column($slopes, 'condition_pct')) / count($slopes)) : 100;
        $avgLiftCond = count($lifts) > 0 ? round(array_sum(array_column($lifts, 'condition_pct')) / count($lifts)) : 100;
        $avgEquipCond = count($equipment) > 0 ? round(array_sum(array_column($equipment, 'condition_pct')) / count($equipment)) : 100;
        $avgParkCond = count($terrainParks) > 0 ? round(array_sum(array_column($terrainParks, 'condition_pct')) / count($terrainParks)) : 100;

        return view('off_season/index', [
            'gameDay' => $gameDay, 'seasonDay' => $seasonDay,
            'isWinter' => $isWinter, 'isSummer' => $isSummer,
            'summerDay' => $isSummer ? $seasonDay - 100 : 0,
            'daysUntilWinter' => $isSummer ? 135 - $seasonDay + 1 : 0,
            'daysUntilSummer' => $isWinter ? 100 - $seasonDay + 1 : 0,
            'finance' => $finance, 'summerActivities' => $summerActivities,
            'activityConfig' => $this->getActivityConfig(),
            'maintenanceTasks' => $this->getMaintenanceTasks(),
            'slopes' => $slopes, 'lifts' => $lifts, 'buildings' => $buildings,
            'equipment' => $equipment, 'terrainParks' => $terrainParks,
            'avgSlopeCond' => $avgSlopeCond, 'avgLiftCond' => $avgLiftCond,
            'avgEquipCond' => $avgEquipCond, 'avgParkCond' => $avgParkCond,
        ]);
    }

    public function runMaintenance()
    {
        $userId = auth()->id();
        $db = db_connect();
        $task = $this->request->getPost('task');
        $tasks = $this->getMaintenanceTasks();

        if (!isset($tasks[$task])) {
            return redirect()->to('/off-season')->with('error', 'Invalid maintenance task.');
        }

        $taskConfig = $tasks[$task];
        $count = 0;
        $totalCost = 0;

        switch ($taskConfig['target']) {
            case 'slopes':
                $items = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('condition_pct <', 100)->get()->getResultArray();
                $count = count($items);
                $totalCost = $count * $taskConfig['cost_per_unit'];
                $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->update(['condition_pct' => 100]);
                break;
            case 'lifts':
                $items = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('condition_pct <', 100)->get()->getResultArray();
                $count = count($items);
                $totalCost = $count * $taskConfig['cost_per_unit'];
                $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->update(['condition_pct' => 100]);
                break;
            case 'buildings':
                $count = $db->table('buildings')->where('user_id', $userId)->countAllResults();
                $totalCost = $count * $taskConfig['cost_per_unit'];
                break;
            case 'equipment':
                $items = $db->table('equipment')->where('user_id', $userId)->where('condition_pct <', 100)->get()->getResultArray();
                $count = count($items);
                $totalCost = $count * $taskConfig['cost_per_unit'];
                $db->table('equipment')->where('user_id', $userId)->update(['condition_pct' => 100, 'status' => 'off']);
                break;
            case 'terrain_parks':
                $items = $db->table('terrain_parks')->where('user_id', $userId)->where('condition_pct <', 100)->get()->getResultArray();
                $count = count($items);
                $totalCost = $count * $taskConfig['cost_per_unit'];
                $db->table('terrain_parks')->where('user_id', $userId)->update(['condition_pct' => 100]);
                break;
        }

        if ($count === 0) return redirect()->to('/off-season')->with('error', 'Nothing needs maintenance in that category.');

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $totalCost) return redirect()->to('/off-season')->with('error', 'Not enough cash. Need ' . currency($totalCost) . '.');

        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$totalCost}", false)->update();
        log_activity($userId, 'maintenance', $taskConfig['name'] . ": {$count} items serviced for " . currency($totalCost));
        notify($userId, 'maintenance', $taskConfig['name'] . ' complete', "{$count} items restored to perfect condition.", $taskConfig['icon'], '/off-season');

        return redirect()->to('/off-season')->with('success', $taskConfig['name'] . ' complete! ' . $count . ' items serviced for ' . currency($totalCost) . '.');
    }
}
