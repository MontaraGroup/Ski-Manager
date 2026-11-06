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

        $db = db_connect();

        $formattedBuildings = array_map(function ($b) use ($def) {
            $lvl = (int) $b['level'];
            $nextLvl = $lvl + 1;
            $nextLevelInfo = null;
            if (isset($def['levels'][$nextLvl])) {
                $nxt = $def['levels'][$nextLvl];
                $nextLevelInfo = [
                    'level' => $nextLvl,
                    'name' => (string) $nxt['name'],
                    'cost' => (int) $nxt['cost'],
                    'costText' => currency((int) $nxt['cost']),
                ];
            }
            $lvlCost = (int) ($def['levels'][$lvl]['cost'] ?? 10000);
            $refund = (int) round($lvlCost * 0.25);

            return [
                'id' => (int) $b['id'],
                'name' => (string) $b['name'],
                'level' => $lvl,
                'capacity' => (int) $b['capacity'],
                'revenue_per_day' => (int) $b['revenue_per_day'],
                'revenueText' => currency((int) $b['revenue_per_day']),
                'upkeep_per_day' => (int) $b['upkeep_per_day'],
                'upkeepText' => currency((int) $b['upkeep_per_day']),
                'condition_pct' => (int) $b['condition_pct'],
                'status' => (string) $b['status'],
                'nextLevel' => $nextLevelInfo,
                'refundText' => currency($refund),
            ];
        }, $buildings);

        $catalog = [];
        foreach ($def['levels'] as $lvl => $info) {
            $catalog[] = [
                'level' => (int) $lvl,
                'name' => (string) $info['name'],
                'capacity' => (int) $info['capacity'],
                'revenue' => (int) $info['revenue'],
                'revenueText' => currency((int) $info['revenue']),
                'upkeep' => (int) $info['upkeep'],
                'upkeepText' => currency((int) $info['upkeep']),
                'cost' => (int) $info['cost'],
                'costText' => currency((int) $info['cost']),
            ];
        }

        $typeData = [];
        if ($type === 'hotel') {
            $receptionists = $db->table('staff')->where('user_id', $userId)->where('role', 'receptionist')->where('status', 'active')->countAllResults();
            $visitors = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            $dailyVisitors = (int) ($visitors['daily_visitors'] ?? 0);
            $occupiedBeds = min($totalCapacity, round($dailyVisitors * 0.3));
            $occupancyRate = $totalCapacity > 0 ? (int) round($occupiedBeds / $totalCapacity * 100) : 0;
            $starRating = count($buildings) > 0 ? min(5, round(array_sum(array_column($buildings, 'level')) / count($buildings) + ($receptionists > 0 ? 1 : 0), 1)) : 0;
            $hotelTiers = [
                1 => ['tier' => 'Budget', 'stars' => 1, 'desc' => 'Dormitory beds, shared bathrooms, basic amenities. Popular with backpackers.'],
                2 => ['tier' => 'Mid-Range', 'stars' => 2, 'desc' => 'Private rooms, en-suite bathrooms, restaurant on-site. Families love these.'],
                3 => ['tier' => 'Luxury', 'stars' => 3, 'desc' => 'Suites, spa access, concierge service, ski-in/ski-out. Premium pricing.'],
            ];
            $typeData = [
                'receptionists' => (int) $receptionists,
                'occupiedBeds' => (int) $occupiedBeds,
                'occupancyRate' => (int) $occupancyRate,
                'starRating' => (float) $starRating,
                'tiers' => $hotelTiers,
            ];
        } elseif ($type === 'rental') {
            $gearLevels = [
                1 => ['name' => 'Basic', 'avg_price' => 25, 'avgPriceText' => currency(25), 'items' => [['icon' => 'fa-person-skiing', 'name' => 'Entry Skis'], ['icon' => 'fa-shoe-prints', 'name' => 'Boots'], ['icon' => 'fa-grip-lines', 'name' => 'Poles']]],
                2 => ['name' => 'Standard', 'avg_price' => 45, 'avgPriceText' => currency(45), 'items' => [['icon' => 'fa-person-skiing', 'name' => 'All-Mountain Skis'], ['icon' => 'fa-person-snowboarding', 'name' => 'Snowboards'], ['icon' => 'fa-helmet-safety', 'name' => 'Helmets'], ['icon' => 'fa-glasses', 'name' => 'Goggles']]],
                3 => ['name' => 'Premium', 'avg_price' => 85, 'avgPriceText' => currency(85), 'items' => [['icon' => 'fa-person-skiing', 'name' => 'Race Skis'], ['icon' => 'fa-person-snowboarding', 'name' => 'Pro Boards'], ['icon' => 'fa-helmet-safety', 'name' => 'MIPS Helmets'], ['icon' => 'fa-vest', 'name' => 'Impact Vests'], ['icon' => 'fa-wand-magic-sparkles', 'name' => 'Custom Fitting']]],
            ];
            $totalSetsInUse = min($totalCapacity, round($totalCapacity * 0.7));
            $utilization = $totalCapacity > 0 ? (int) round($totalSetsInUse / $totalCapacity * 100) : 0;
            $typeData = [
                'totalSetsInUse' => (int) $totalSetsInUse,
                'utilization' => (int) $utilization,
                'gearLevels' => $gearLevels,
            ];
        } elseif ($type === 'restaurant') {
            $chefs = $db->table('staff')->where('user_id', $userId)->where('role', 'chef')->where('status', 'active')->get()->getResultArray();
            $avgChefLevel = count($chefs) > 0 ? round(array_sum(array_column($chefs, 'level')) / count($chefs), 1) : 0;
            $foodQuality = min(100, (int) round(40 + ($avgChefLevel * 15) + (count($chefs) * 5)));
            $mealPeriods = [
                ['name' => 'Breakfast', 'time' => '7-10', 'icon' => 'fa-mug-hot'],
                ['name' => 'Lunch', 'time' => '11-14', 'icon' => 'fa-sun'],
                ['name' => 'Apres-Ski', 'time' => '15-18', 'icon' => 'fa-beer-mug-empty'],
                ['name' => 'Dinner', 'time' => '18-22', 'icon' => 'fa-moon'],
            ];
            $cuisineMap = [
                1 => ['name' => 'Fast Food', 'icon' => 'fa-burger', 'menu' => ['Burgers', 'Fries', 'Hot Dogs', 'Pizza Slices', 'Soft Drinks']],
                2 => ['name' => 'Alpine Cuisine', 'icon' => 'fa-cheese', 'menu' => ['Fondue', 'Raclette', 'Tartiflette', 'Rosti', 'Gluhwein']],
                3 => ['name' => 'Fine Dining', 'icon' => 'fa-wine-glass', 'menu' => ['Tasting Menu', 'Wagyu Beef', 'Truffle Risotto', 'Wine Pairing', 'Souffle']],
            ];
            $typeData = [
                'chefCount' => count($chefs),
                'avgChefLevel' => (float) $avgChefLevel,
                'foodQuality' => (int) $foodQuality,
                'mealPeriods' => $mealPeriods,
                'cuisines' => $cuisineMap,
            ];
        } elseif ($type === 'retail') {
            $merchLevels = [
                1 => ['name' => 'Souvenirs', 'products' => [['icon' => 'fa-mug-hot', 'name' => 'Mugs'], ['icon' => 'fa-key', 'name' => 'Keychains'], ['icon' => 'fa-image', 'name' => 'Postcards'], ['icon' => 'fa-magnet', 'name' => 'Magnets']]],
                2 => ['name' => 'Ski Shop', 'products' => [['icon' => 'fa-glasses', 'name' => 'Goggles'], ['icon' => 'fa-mitten', 'name' => 'Gloves'], ['icon' => 'fa-vest', 'name' => 'Jackets'], ['icon' => 'fa-socks', 'name' => 'Base Layers'], ['icon' => 'fa-helmet-safety', 'name' => 'Helmets']]],
                3 => ['name' => 'Boutique', 'products' => [['icon' => 'fa-gem', 'name' => 'Jewelry'], ['icon' => 'fa-shirt', 'name' => 'Designer Wear'], ['icon' => 'fa-bag-shopping', 'name' => 'Luxury Goods'], ['icon' => 'fa-clock', 'name' => 'Watches'], ['icon' => 'fa-wine-bottle', 'name' => 'Fine Spirits']]],
            ];
            $avgMargin = count($buildings) > 0 ? (int) round(($totalRevenue - $totalUpkeep) / max(1, $totalRevenue) * 100) : 0;
            $totalProducts = 0;
            foreach ($buildings as $b) {
                $totalProducts += count($merchLevels[$b['level']]['products'] ?? []);
            }
            $typeData = [
                'avgMargin' => (int) $avgMargin,
                'totalProducts' => (int) $totalProducts,
                'merchLevels' => $merchLevels,
            ];
        } elseif ($type === 'ski_patrol') {
            $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->get()->getResultArray();
            $patrolStaff = $db->table('staff')->where('user_id', $userId)->where('role', 'ski_patrol')->where('status', 'active')->get()->getResultArray();
            $medics = $db->table('staff')->where('user_id', $userId)->where('role', 'medic')->where('status', 'active')->get()->getResultArray();
            $slopeCount = count($slopes);
            $coverageRatio = $slopeCount > 0 ? min(100, (int) round($totalCapacity / $slopeCount * 100)) : 100;
            $safetyScore = min(100, (int) round($coverageRatio * 0.4 + count($patrolStaff) * 8 + count($medics) * 12 + count($openBuildings) * 10));
            $riskLevel = $safetyScore >= 80 ? 'Low' : ($safetyScore >= 50 ? 'Moderate' : 'High');
            $rawIncidents = $db->table('activity_log')->where('user_id', $userId)
                ->groupStart()->like('category', 'accident')->orLike('category', 'rescue')->orLike('category', 'inspection')->groupEnd()
                ->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray();
            $recentIncidents = array_map(function ($inc) {
                return [
                    'id' => (int) ($inc['id'] ?? 0),
                    'category' => (string) ($inc['category'] ?? ''),
                    'details' => (string) ($inc['message'] ?? $inc['description'] ?? ''),
                    'timeAgo' => timeAgo($inc['created_at'] ?? 'now'),
                ];
            }, $rawIncidents);
            $stationTypes = [
                1 => ['name' => 'First Aid Post', 'icon' => 'fa-kit-medical', 'desc' => 'Basic first aid, radio comms'],
                2 => ['name' => 'Patrol Station', 'icon' => 'fa-shield-halved', 'desc' => 'Full medical suite, toboggan, AED'],
                3 => ['name' => 'Rescue Center', 'icon' => 'fa-helicopter', 'desc' => 'Helipad, trauma response, ICU'],
            ];
            $typeData = [
                'safetyScore' => (int) $safetyScore,
                'riskLevel' => (string) $riskLevel,
                'coverageRatio' => (int) $coverageRatio,
                'slopeCount' => (int) $slopeCount,
                'uncoveredSlopes' => max(0, $slopeCount - $totalCapacity),
                'patrolCount' => count($patrolStaff),
                'medicCount' => count($medics),
                'patrolStaff' => array_map(fn($s) => ['name' => (string) $s['name'], 'level' => (int) ($s['level'] ?? 1)], array_slice($patrolStaff, 0, 8)),
                'medics' => array_map(fn($m) => ['name' => (string) $m['name'], 'level' => (int) ($m['level'] ?? 1)], array_slice($medics, 0, 8)),
                'recentIncidents' => $recentIncidents,
                'stationTypes' => $stationTypes,
            ];
        } elseif ($type === 'transportation') {
            $routeInfo = [
                1 => ['name' => 'Shuttle', 'vehicle' => 'fa-van-shuttle', 'desc' => 'Town loop shuttle', 'freq' => 'Every 30 min', 'range' => '5 km radius'],
                2 => ['name' => 'Express', 'vehicle' => 'fa-bus', 'desc' => 'Regional express coach', 'freq' => 'Every 60 min', 'range' => '50 km radius'],
                3 => ['name' => 'Aerial Tram', 'vehicle' => 'fa-cable-car', 'desc' => 'Inter-resort aerial link', 'freq' => 'Continuous', 'range' => 'Resort-to-resort'],
            ];
            $networkScore = min(100, (int) round(count($openBuildings) * 25 + $totalCapacity / 10));
            $typeData = [
                'networkScore' => (int) $networkScore,
                'routes' => $routeInfo,
            ];
        }

        $rawIcon = $def['icon'] ?? 'fa-building';
        $icon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $rawIcon);

        $page = [
            'type' => $type,
            'def' => [
                'label' => (string) $def['label'],
                'singular' => (string) $def['singular'],
                'icon' => (string) $icon,
                'desc' => (string) $def['desc'],
                'route' => (string) $def['route'],
            ],
            'stats' => [
                'totalCapacity' => (int) $totalCapacity,
                'totalRevenueText' => currency((int) $totalRevenue),
                'totalUpkeepText' => currency((int) $totalUpkeep),
                'netProfitText' => currency((int) ($totalRevenue - $totalUpkeep)),
                'openCount' => count($openBuildings),
                'totalCount' => count($buildings),
            ],
            'buildings' => $formattedBuildings,
            'catalog' => $catalog,
            'domain' => $typeData,
        ];

        if (!file_exists(APPPATH . 'Views/buildings/' . $type . '.php')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        return view('buildings/' . $type, ['page' => $page]);
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
        // Real estate has plots, construction and a market of its own (Controllers/RealEstate)
        if ($type === 'real_estate') return redirect()->to('/real-estate');

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
        if ($building['building_type'] === 'real_estate') return redirect()->to('/real-estate');

        // A broken building used to come straight back to 'open' here, a free repair
        if (!in_array($building['status'], ['open', 'closed'], true)) {
            return redirect()->back()->with('error', $building['name'] . ' is ' . $building['status'] . ' and can\'t be reopened.');
        }
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
        if ($building['building_type'] === 'real_estate') return redirect()->to('/real-estate');

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
        if ($building['building_type'] === 'real_estate') return redirect()->to('/real-estate');

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
