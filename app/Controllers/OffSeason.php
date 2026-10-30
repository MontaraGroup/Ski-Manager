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
        $seasonDay = getSeasonDay();
        $isWinter = $seasonDay <= getWinterDays();
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
            'summerDay' => $isSummer ? $seasonDay - getWinterDays() : 0,
            'daysUntilWinter' => $isSummer ? getSeasonLength() - $seasonDay + 1 : 0,
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
        // target => [table, extra where, status a repaired broken item returns to]
        $targets = [
            'slopes' => ['player_items', ['item_type' => 'slope'], 'open'],
            'lifts' => ['player_items', ['item_type' => 'lift'], 'open'],
            'buildings' => ['buildings', [], 'open'],
            'equipment' => ['equipment', [], 'off'],
            'terrain_parks' => ['terrain_parks', [], 'open'],
        ];
        if (!isset($targets[$taskConfig['target']])) return redirect()->to('/off-season')->with('error', 'Invalid maintenance task.');
        [$table, $where, $repairedStatus] = $targets[$taskConfig['target']];

        $q = fn() => $db->table($table)->where('user_id', $userId)->where($where)->where('condition_pct <', 100);
        $ids = array_column($q()->select('id')->get()->getResultArray(), 'id');
        $count = count($ids);
        if ($count === 0) return redirect()->to('/off-season')->with('error', 'Nothing needs maintenance in that category.');
        $totalCost = $count * (int) $taskConfig['cost_per_unit'];

        // Pay first, in one statement, and only then repair: repairs used to happen before the cash check
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $totalCost)
            ->set('cash', 'cash - ' . $totalCost, false)->set('total_expenses', 'total_expenses + ' . $totalCost, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/off-season')->with('error', 'Not enough cash. Need ' . currency($totalCost) . '.');
        }
        // Items keep their on/off state; broken ones (and parks closed for low condition) come back
        $db->table($table)->whereIn('id', $ids)->whereIn('status', ['broken', 'maintenance'])->update(['status' => $repairedStatus]);
        $db->table($table)->whereIn('id', $ids)->update(['condition_pct' => 100]);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Maintenance',
            'description' => $taskConfig['name'] . ' (' . $count . ' items)', 'amount' => $totalCost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'maintenance', $taskConfig['name'] . ": {$count} items serviced for " . currency($totalCost));
        notify($userId, 'maintenance', $taskConfig['name'] . ' complete', "{$count} items restored to perfect condition.", $taskConfig['icon'], '/off-season');

        return redirect()->to('/off-season')->with('success', $taskConfig['name'] . ' complete! ' . $count . ' items serviced for ' . currency($totalCost) . '.');
    }

    public function buildActivity()
    {
        $userId = auth()->id();
        $db = db_connect();
        $activityKey = $this->request->getPost('activity_key');
        $config = $this->getActivityConfig();

        if (!isset($config[$activityKey])) {
            return redirect()->to('/off-season')->with('error', 'Invalid activity type.');
        }

        $cfg = $config[$activityKey];
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cfg['cost'])
            ->set('cash', 'cash - ' . $cfg['cost'], false)->set('total_expenses', 'total_expenses + ' . $cfg['cost'], false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/off-season')->with('error', 'Not enough cash. Need ' . currency($cfg['cost']) . '.');
        }
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Off-Season', 'description' => 'Built ' . $cfg['name'],
            'amount' => $cfg['cost'], 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('buildings')->insert([
            'user_id' => $userId,
            'building_type' => 'off_season',
            'name' => $cfg['name'],
            'specialty' => $activityKey,
            'capacity' => $cfg['capacity'],
            'revenue_per_day' => $cfg['revenue'],
            'upkeep_per_day' => $cfg['upkeep'],
            'condition_pct' => 100,
            'status' => 'open',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Off-Season', 'Built ' . $cfg['name'] . ' for ' . currency($cfg['cost']), 'fa-solid fa-sun');
        notify($userId, 'building', $cfg['name'] . ' built!', 'Your new summer activity is ready for guests.', $cfg['icon'] ?? 'fa-solid fa-sun', '/off-season');

        return redirect()->to('/off-season')->with('success', $cfg['name'] . ' built for ' . currency($cfg['cost']) . '!');
    }
}
