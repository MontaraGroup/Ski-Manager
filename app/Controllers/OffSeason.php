<?php

namespace App\Controllers;

class OffSeason extends BaseController
{
    public const SUMMER_ACTIVITIES = [
        'hiking_trail' => [
            'name' => 'Hiking Trail', 'icon' => 'fa-solid fa-person-hiking',
            'cost' => 8000, 'revenue' => 500, 'upkeep' => 100, 'capacity' => 60,
            'desc' => 'Marked trails through alpine meadows. Low cost, steady visitors.',
        ],
        'bike_park' => [
            'name' => 'Mountain Bike Park', 'icon' => 'fa-solid fa-bicycle',
            'cost' => 45000, 'revenue' => 2500, 'upkeep' => 500, 'capacity' => 40,
            'desc' => 'Downhill trails with lift access. Popular with thrill-seekers.',
        ],
        'alpine_coaster' => [
            'name' => 'Alpine Coaster', 'icon' => 'fa-solid fa-train',
            'cost' => 150000, 'revenue' => 6000, 'upkeep' => 1000, 'capacity' => 100,
            'desc' => 'Year-round gravity rail ride. High revenue, high maintenance.',
        ],
        'zipline' => [
            'name' => 'Zipline', 'icon' => 'fa-solid fa-person-falling',
            'cost' => 35000, 'revenue' => 1800, 'upkeep' => 300, 'capacity' => 30,
            'desc' => 'Cable ride across the valley. Stunning views, great for tourists.',
        ],
        'climbing_wall' => [
            'name' => 'Climbing Wall', 'icon' => 'fa-solid fa-arrow-up',
            'cost' => 20000, 'revenue' => 1200, 'upkeep' => 200, 'capacity' => 20,
            'desc' => 'Indoor/outdoor climbing facility. Attracts families and groups.',
        ],
        'paragliding' => [
            'name' => 'Paragliding School', 'icon' => 'fa-solid fa-parachute-box',
            'cost' => 60000, 'revenue' => 3500, 'upkeep' => 600, 'capacity' => 15,
            'desc' => 'Tandem flights and courses. Premium pricing, limited capacity.',
        ],
        'spa_wellness' => [
            'name' => 'Mountain Spa', 'icon' => 'fa-solid fa-spa',
            'cost' => 200000, 'revenue' => 8000, 'upkeep' => 2000, 'capacity' => 50,
            'desc' => 'Luxury wellness center. Year-round revenue, high-end clientele.',
        ],
        'adventure_park' => [
            'name' => 'Treetop Adventure Park', 'icon' => 'fa-solid fa-tree',
            'cost' => 80000, 'revenue' => 4000, 'upkeep' => 800, 'capacity' => 45,
            'desc' => 'Rope courses and obstacles in the forest canopy.',
        ],
    ];

    public const MAINTENANCE_TASKS = [
        'slope_repair' => [
            'name' => 'Slope Maintenance', 'icon' => 'fa-solid fa-mountain',
            'desc' => 'Repair erosion, reseed grass, fix drainage. Restores all slope conditions to 100%.',
            'cost_per_unit' => 2000, 'target' => 'slopes',
        ],
        'lift_overhaul' => [
            'name' => 'Lift Overhaul', 'icon' => 'fa-solid fa-cable-car',
            'desc' => 'Full mechanical inspection and cable replacement. Restores all lift conditions to 100%.',
            'cost_per_unit' => 5000, 'target' => 'lifts',
        ],
        'building_renovation' => [
            'name' => 'Building Renovation', 'icon' => 'fa-solid fa-hammer',
            'desc' => 'Repaint, repair, upgrade interiors. Restores all building conditions.',
            'cost_per_unit' => 3000, 'target' => 'buildings',
        ],
        'equipment_service' => [
            'name' => 'Equipment Full Service', 'icon' => 'fa-solid fa-wrench',
            'desc' => 'Complete overhaul of all groomers and snowmakers. Restores to 100%.',
            'cost_per_unit' => 1500, 'target' => 'equipment',
        ],
        'terrain_park_rebuild' => [
            'name' => 'Terrain Park Rebuild', 'icon' => 'fa-solid fa-person-snowboarding',
            'desc' => 'Reshape jumps, replace rails, rebuild features. Full condition restore.',
            'cost_per_unit' => 3000, 'target' => 'terrain_parks',
        ],
    ];

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime('2026-06-01')) / 86400) + 1);
        $seasonDay = (($gameDay - 1) % 135) + 1;
        $isWinter = $seasonDay <= 100;
        $isSummer = !$isWinter;
        $summerDay = $isSummer ? $seasonDay - 100 : 0;
        $daysUntilWinter = $isSummer ? 135 - $seasonDay + 1 : 0;
        $daysUntilSummer = $isWinter ? 100 - $seasonDay + 1 : 0;

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
            'summerDay' => $summerDay, 'daysUntilWinter' => $daysUntilWinter,
            'daysUntilSummer' => $daysUntilSummer,
            'finance' => $finance,
            'summerActivities' => $summerActivities,
            'activityConfig' => self::SUMMER_ACTIVITIES,
            'maintenanceTasks' => self::MAINTENANCE_TASKS,
            'slopes' => $slopes, 'lifts' => $lifts,
            'buildings' => $buildings, 'equipment' => $equipment,
            'terrainParks' => $terrainParks,
            'avgSlopeCond' => $avgSlopeCond, 'avgLiftCond' => $avgLiftCond,
            'avgEquipCond' => $avgEquipCond, 'avgParkCond' => $avgParkCond,
        ]);
    }

    public function runMaintenance()
    {
        $userId = auth()->id();
        $db = db_connect();
        $task = $this->request->getPost('task');

        if (!isset(self::MAINTENANCE_TASKS[$task])) {
            return redirect()->to('/off-season')->with('error', 'Invalid maintenance task.');
        }

        $taskConfig = self::MAINTENANCE_TASKS[$task];
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

        if ($count === 0) {
            return redirect()->to('/off-season')->with('error', 'Nothing needs maintenance in that category.');
        }

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (($finance['cash'] ?? 0) < $totalCost) {
            return redirect()->to('/off-season')->with('error', 'Not enough cash. Need ' . currency($totalCost) . '.');
        }

        $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$totalCost}", false)->update();
        log_activity($userId, 'maintenance', $taskConfig['name'] . ": {$count} items serviced for " . currency($totalCost));
        notify($userId, 'maintenance', $taskConfig['name'] . ' complete', "{$count} items restored to perfect condition.", $taskConfig['icon'], '/off-season');

        return redirect()->to('/off-season')->with('success', $taskConfig['name'] . ' complete! ' . $count . ' items serviced for ' . currency($totalCost) . '.');
    }
}
