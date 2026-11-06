<?php
namespace App\Controllers;

use App\Libraries\Ledger;

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

        $avgSlopeCond = count($slopes) > 0 ? (int) round(array_sum(array_column($slopes, 'condition_pct')) / count($slopes)) : 100;
        $avgLiftCond = count($lifts) > 0 ? (int) round(array_sum(array_column($lifts, 'condition_pct')) / count($lifts)) : 100;
        $avgEquipCond = count($equipment) > 0 ? (int) round(array_sum(array_column($equipment, 'condition_pct')) / count($equipment)) : 100;
        $avgParkCond = count($terrainParks) > 0 ? (int) round(array_sum(array_column($terrainParks, 'condition_pct')) / count($terrainParks)) : 100;

        $cash = (int) ($finance['cash'] ?? 0);
        $tasksConfig = $this->getMaintenanceTasks();
        $formattedTasks = [];
        foreach ($tasksConfig as $key => $task) {
            $itemCount = 0;
            $needsRepair = 0;
            switch ($task['target']) {
                case 'slopes':
                    $itemCount = count($slopes);
                    $needsRepair = count(array_filter($slopes, fn($s) => (int) $s['condition_pct'] < 100));
                    break;
                case 'lifts':
                    $itemCount = count($lifts);
                    $needsRepair = count(array_filter($lifts, fn($l) => (int) $l['condition_pct'] < 100));
                    break;
                case 'buildings':
                    $itemCount = count($buildings);
                    $needsRepair = count(array_filter($buildings, fn($b) => (int) $b['condition_pct'] < 100));
                    break;
                case 'equipment':
                    $itemCount = count($equipment);
                    $needsRepair = count(array_filter($equipment, fn($e) => (int) $e['condition_pct'] < 100));
                    break;
                case 'terrain_parks':
                    $itemCount = count($terrainParks);
                    $needsRepair = count(array_filter($terrainParks, fn($p) => (int) $p['condition_pct'] < 100));
                    break;
            }

            $totalCost = $needsRepair * (int) $task['cost_per_unit'];
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $task['icon'] ?? 'wrench');

            $formattedTasks[] = [
                'key' => $key,
                'name' => $task['name'],
                'icon' => $cleanIcon,
                'desc' => $task['desc'],
                'target' => $task['target'],
                'cost_per_unit' => (int) $task['cost_per_unit'],
                'cost_per_unit_formatted' => currency((int) $task['cost_per_unit']),
                'total_items' => $itemCount,
                'needs_repair' => $needsRepair,
                'total_cost' => $totalCost,
                'total_cost_formatted' => currency($totalCost),
                'canAfford' => $cash >= $totalCost,
            ];
        }

        $formattedActivities = array_map(function($act) use ($isSummer) {
            return [
                'id' => (int) $act['id'],
                'name' => $act['name'],
                'specialty' => $act['specialty'] ?? '',
                'revenueFormatted' => currency((int) ($act['revenue_per_day'] ?? 0)),
                'upkeepFormatted' => currency((int) ($act['upkeep_per_day'] ?? 0)),
                'capacity' => (int) ($act['capacity'] ?? 0),
                'status' => $act['status'] ?? 'open',
                'isActive' => $isSummer && ($act['status'] ?? 'open') === 'open',
            ];
        }, $summerActivities);

        $activityConfig = $this->getActivityConfig();
        $formattedCatalog = [];
        foreach ($activityConfig as $key => $cfg) {
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $cfg['icon'] ?? 'sun');
            $cost = (int) $cfg['cost'];
            $formattedCatalog[] = [
                'key' => $key,
                'name' => $cfg['name'],
                'icon' => $cleanIcon,
                'desc' => $cfg['desc'],
                'cost' => $cost,
                'costFormatted' => currency($cost),
                'revenueFormatted' => currency((int) $cfg['revenue']),
                'upkeepFormatted' => currency((int) $cfg['upkeep']),
                'capacity' => (int) $cfg['capacity'],
                'canAfford' => $cash >= $cost,
            ];
        }

        $page = [
            'season' => [
                'gameDay' => $gameDay,
                'seasonDay' => $seasonDay,
                'isWinter' => $isWinter,
                'isSummer' => $isSummer,
                'summerDay' => $isSummer ? $seasonDay - getWinterDays() : 0,
                'daysUntilWinter' => $isSummer ? getSeasonLength() - $seasonDay + 1 : 0,
                'daysUntilSummer' => $isWinter ? 100 - $seasonDay + 1 : 0,
                'winterDays' => getWinterDays(),
                'summerDays' => getSummerDays(),
                'seasonLength' => getSeasonLength(),
            ],
            'cash' => $cash,
            'cashFormatted' => currency($cash),
            'health' => [
                'slopes' => ['count' => count($slopes), 'avgCondition' => $avgSlopeCond],
                'lifts' => ['count' => count($lifts), 'avgCondition' => $avgLiftCond],
                'buildings' => ['count' => count($buildings), 'avgCondition' => 100],
                'equipment' => ['count' => count($equipment), 'avgCondition' => $avgEquipCond],
                'terrainParks' => ['count' => count($terrainParks), 'avgCondition' => $avgParkCond],
            ],
            'maintenanceTasks' => $formattedTasks,
            'summerActivities' => $formattedActivities,
            'catalogActivities' => $formattedCatalog,
        ];

        return view('off_season/index', ['page' => $page]);
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
        if (!Ledger::charge($userId, $totalCost, 'Maintenance', $taskConfig['name'] . ' (' . $count . ' items)')) {
            $db->transRollback();
            return redirect()->to('/off-season')->with('error', 'Not enough cash. Need ' . currency($totalCost) . '.');
        }
        // Items keep their on/off state; broken ones (and parks closed for low condition) come back
        $db->table($table)->whereIn('id', $ids)->whereIn('status', ['broken', 'maintenance'])->update(['status' => $repairedStatus]);
        $db->table($table)->whereIn('id', $ids)->update(['condition_pct' => 100]);
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
        if (!Ledger::charge($userId, (int) $cfg['cost'], 'Off-Season', 'Built ' . $cfg['name'])) {
            $db->transRollback();
            return redirect()->to('/off-season')->with('error', 'Not enough cash. Need ' . currency($cfg['cost']) . '.');
        }
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
