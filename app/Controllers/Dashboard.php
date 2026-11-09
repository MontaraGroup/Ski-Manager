<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    private array $availableWidgets = [
        'stats' => ['name' => 'Key Stats', 'icon' => 'fa-solid fa-chart-bar', 'default' => true, 'size' => 'large'],
        'season' => ['name' => 'Season Progress', 'icon' => 'fa-solid fa-calendar', 'default' => true, 'size' => 'large'],
        'weather_mini' => ['name' => 'Weather', 'icon' => 'fa-solid fa-cloud-sun', 'default' => true, 'size' => 'medium'],
        'achievements_mini' => ['name' => 'Achievement Alerts', 'icon' => 'fa-solid fa-bell', 'default' => true, 'size' => 'large'],
        'actions' => ['name' => 'Quick Actions', 'icon' => 'fa-solid fa-bolt', 'default' => true, 'size' => 'medium'],
        'overview' => ['name' => 'Resort Overview', 'icon' => 'fa-solid fa-mountain-sun', 'default' => true, 'size' => 'large'],
        'achievements' => ['name' => 'Achievements', 'icon' => 'fa-solid fa-award', 'default' => true, 'size' => 'medium'],
        'activity' => ['name' => 'Recent Activity', 'icon' => 'fa-solid fa-clock-rotate-left', 'default' => true, 'size' => 'medium'],
        'parking_mini' => ['name' => 'Parking', 'icon' => 'fa-solid fa-square-parking', 'default' => false, 'size' => 'small'],
        'terrain_parks_mini' => ['name' => 'Terrain Parks', 'icon' => 'fa-solid fa-person-snowboarding', 'default' => false, 'size' => 'small'],
        'finances_mini' => ['name' => 'Finances', 'icon' => 'fa-solid fa-coins', 'default' => false, 'size' => 'medium'],
        'staff_mini' => ['name' => 'Staff Overview', 'icon' => 'fa-solid fa-users', 'default' => false, 'size' => 'medium'],
        'equipment_mini' => ['name' => 'Equipment', 'icon' => 'fa-solid fa-toolbox', 'default' => false, 'size' => 'small'],
        'insurance_mini' => ['name' => 'Insurance', 'icon' => 'fa-solid fa-shield-halved', 'default' => false, 'size' => 'small'],
        'loans_mini' => ['name' => 'Loans', 'icon' => 'fa-solid fa-landmark', 'default' => false, 'size' => 'small'],
        'marketing_mini' => ['name' => 'Marketing', 'icon' => 'fa-solid fa-bullhorn', 'default' => false, 'size' => 'small'],
    ];

    public function index()
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');
        $db = db_connect();

        $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();

        if (empty($widgets)) {
            $order = 0;
            foreach ($this->availableWidgets as $key => $w) {
                $db->table('dashboard_widgets')->insert([
                    'user_id' => $userId, 'widget_key' => $key, 'visible' => $w['default'] ? 1 : 0, 'size' => $w['size'], 'sort_order' => $order++,
                ]);
            }
            $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();
        }

        $existingKeys = array_column($widgets, 'widget_key');
        $maxOrder = max(array_column($widgets, 'sort_order') ?: [0]);
        $toInsert = [];
        foreach ($this->availableWidgets as $key => $w) {
            if (!in_array($key, $existingKeys)) {
                $maxOrder++;
                $toInsert[] = [
                    'user_id' => $userId, 'widget_key' => $key, 'visible' => 0, 'size' => $w['size'], 'sort_order' => $maxOrder,
                ];
            }
        }
        if (!empty($toInsert)) {
            $db->table('dashboard_widgets')->insertBatch($toInsert);
            $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();
        }

        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $weather = getCurrentWeather();
        $genepis = $db->table('genepis')->where('user_id', $userId)->get()->getRowArray();

        $allItems = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $slopeTypes = ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'];
        $slopesAll = array_filter($allItems, fn($i) => in_array($i['item_type'] ?? '', $slopeTypes, true));
        $liftsAll  = array_filter($allItems, fn($i) => ($i['item_type'] ?? '') === 'lift');

        $openSlopesArr = array_filter($slopesAll, fn($i) => ($i['status'] ?? '') === 'open');
        $openLiftsArr  = array_filter($liftsAll, fn($i) => ($i['status'] ?? '') === 'open');
        $slopeCount    = count($openSlopesArr);
        $liftCount     = count($openLiftsArr);
        $totalSlopes   = count($slopesAll);
        $totalLifts    = count($liftsAll);
        $slopesByDiff  = ['green' => 0, 'blue' => 0, 'black' => 0, 'double_black' => 0];
        foreach ($openSlopesArr as $s) {
            $d = $s['difficulty'] ?? '';
            if (isset($slopesByDiff[$d])) $slopesByDiff[$d]++;
        }

        $totalLiftCapacity = array_sum(array_map(fn($l) => (int)($l['capacity'] ?? 0), $openLiftsArr));
        $avgSlopeCondition = count($slopesAll) > 0
            ? (int) round(array_sum(array_map(fn($s) => (int)($s['condition_pct'] ?? 100), $slopesAll)) / count($slopesAll))
            : 100;
        $ungroomedCount = count(array_filter($slopesAll, fn($s) => (int)($s['condition_pct'] ?? 100) < 65));

        $staffAll = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $staffCount = count($staffAll);
        $buildingsAll = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        $buildingCount = count($buildingsAll);

        $unclaimedAchievements = $db->table('achievements')->where('user_id', $userId)->where('completed', 1)->where('claimed', 0)->countAllResults();
        $completedAchievements = $db->table('achievements')->where('user_id', $userId)->where('completed', 1)->countAllResults();
        $inProgressAchievements = $db->table('achievements')->where('user_id', $userId)->where('completed', 0)->orderBy('progress', 'DESC')->limit(5)->get()->getResultArray();
        $recentActivity = $db->table('activity_log')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(8)->get()->getResultArray();
        $dailyBonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        $bonusAvailable = !$dailyBonus || ($dailyBonus['last_claim_date'] ?? null) < date('Y-m-d');
        $parkingFacilities = $db->table('parking')->where('user_id', $userId)->get()->getResultArray();
        $terrainParks = $db->table('terrain_parks')->where('user_id', $userId)->get()->getResultArray();
        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();
        $insurance = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $marketing = $db->table('marketing_campaigns')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $dailyVisitors = (int)($finance['daily_visitors'] ?? 0);
        $netProfit = ($finance['total_income'] ?? 0) - ($finance['total_expenses'] ?? 0);

        $cashflow = $this->calcCashflowBreakdown($userId, $dailyVisitors, $staffAll, $equipment, $loans, $marketing, $buildingsAll, $insurance, $finance['difficulty'] ?? 'standard');
        $resortMapKey = $finance['resort_map'] ?? 'ParkCity';
        $resortNames = ResortMap::getResortMapNames();
        $resortMapName = $resortNames[$resortMapKey] ?? 'Park City';

        $seasonLength = max(1, (int) getSeasonLength());
        $seasonProgress = min($gameDay, $seasonLength);
        $seasonNum = (int) getSeasonNumber();
        $seasonPct = (int) round($seasonProgress / $seasonLength * 100);
        $winterDays = min(100, $seasonLength);
        $summerDays = max(0, $seasonLength - $winterDays);

        $cashBalance = (int) ($finance['cash'] ?? 0);
        $repScore = (int) ($finance['reputation'] ?? 0);
        $genepisBal = (int) ($genepis['balance'] ?? 0);
        $resortOpen = (bool) ($finance['resort_open'] ?? 1);

        $rawAlerts = $this->getAlerts($userId, $finance, $equipment, $staffAll);
        $formattedAlerts = array_map(function ($a) {
            $cleanIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) ($a['icon'] ?? 'fa-circle-exclamation'));
            $tone = ($a['type'] ?? '') === 'error' ? 'danger' : 'warning';
            return [
                'type' => $tone,
                'icon' => $cleanIcon,
                'msg'  => (string) ($a['msg'] ?? ''),
                'link' => (string) ($a['link'] ?? ''),
            ];
        }, $rawAlerts);

        $wIcons = [
            'Sunny'         => 'fa-sun',
            'Partly Cloudy' => 'fa-cloud-sun',
            'Cloudy'        => 'fa-cloud',
            'Light Snow'    => 'fa-snowflake',
            'Heavy Snow'    => 'fa-snowflake',
            'Blizzard'      => 'fa-wind',
            'Freezing Rain' => 'fa-cloud-rain',
            'Rain'          => 'fa-cloud-showers-heavy',
        ];
        $currentWeatherIcon = $weather ? ($wIcons[$weather['condition_name']] ?? 'fa-cloud') : 'fa-cloud';

        $totalIncome = (int) ($cashflow['total_income'] ?? 0);
        $totalExpenses = (int) ($cashflow['total_expenses'] ?? 0);
        $dailyProfit = (int) ($cashflow['daily_profit'] ?? 0);
        $incomePct = ($totalIncome + $totalExpenses) > 0 ? (int) round($totalIncome / ($totalIncome + $totalExpenses) * 100) : 50;

        $totalParkingCap = 0;
        $totalOccupied = 0;
        foreach ($parkingFacilities as $pf) {
            if ($pf['status'] === 'open' || $pf['status'] === 'full') {
                $totalParkingCap += (int) $pf['capacity'];
                $totalOccupied += (int) $pf['occupied'];
            }
        }
        $parkingPct = $totalParkingCap > 0 ? (int) round($totalOccupied / $totalParkingCap * 100) : 0;

        $openParks = array_values(array_filter($terrainParks, fn($tp) => ($tp['status'] ?? '') === 'open'));
        $buildingParks = array_values(array_filter($terrainParks, fn($tp) => ($tp['status'] ?? '') === 'under_construction'));
        $totalParkVisitors = array_sum(array_column($terrainParks, 'daily_visitors'));

        $rolesCount = [];
        foreach ($staffAll as $s) {
            $r = (string) ($s['role'] ?? 'unknown');
            $rolesCount[$r] = ($rolesCount[$r] ?? 0) + 1;
        }
        $formattedRoles = [];
        foreach ($rolesCount as $roleKey => $rCount) {
            $formattedRoles[] = [
                'role' => $roleKey,
                'roleName' => ucwords(str_replace('_', ' ', $roleKey)),
                'count' => $rCount,
            ];
        }
        $avgMorale = count($staffAll) > 0 ? (int) round(array_sum(array_column($staffAll, 'morale')) / count($staffAll)) : 0;
        $activeIns = array_values(array_filter($insurance, fn($i) => ($i['active'] ?? 0) == 1));

        $forecast = $weather ? (is_array($weather['forecast'] ?? null) ? $weather['forecast'] : (json_decode($weather['forecast'] ?? '[]', true) ?? [])) : [];
        $formattedForecast = [];
        foreach (array_slice($forecast, 0, 5) as $i => $fc) {
            $cond = (string) ($fc['condition'] ?? 'Cloudy');
            $formattedForecast[] = [
                'label' => $i === 0 ? 'Tmrw' : '+' . ($i + 1) . 'd',
                'condition' => $cond,
                'icon' => $wIcons[$cond] ?? 'fa-cloud',
                'tempFormatted' => temp((int) ($fc['temp'] ?? 0)),
            ];
        }

        $formattedInProgressAch = [];
        foreach (array_slice($inProgressAchievements, 0, 4) as $ach) {
            $target = max(1, (int) ($ach['target'] ?? 1));
            $prog = (int) ($ach['progress'] ?? 0);
            $pct = min(100, (int) round($prog / $target * 100));
            $formattedInProgressAch[] = [
                'id' => (int) ($ach['id'] ?? 0),
                'name' => (string) ($ach['name'] ?? ''),
                'progress' => $prog,
                'target' => $target,
                'progressPct' => $pct,
                'progressFormatted' => number_format($prog),
                'targetFormatted' => number_format($target),
            ];
        }

        $formattedActivity = [];
        foreach (array_slice($recentActivity, 0, 6) as $log) {
            $rawIcon = (string) ($log['icon'] ?? 'fa-solid fa-circle-dot');
            $cleanIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', $rawIcon);
            $formattedActivity[] = [
                'id' => (int) ($log['id'] ?? 0),
                'message' => (string) ($log['message'] ?? ''),
                'icon' => $cleanIcon,
                'game_day' => (int) ($log['game_day'] ?? $gameDay),
            ];
        }

        $formattedWidgets = array_map(function ($w) {
            $info = $this->availableWidgets[$w['widget_key']] ?? null;
            $rawIcon = (string) ($info['icon'] ?? 'fa-solid fa-cube');
            $cleanIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', $rawIcon);
            return [
                'widget_key' => (string) $w['widget_key'],
                'name'       => (string) ($info['name'] ?? $w['widget_key']),
                'icon'       => $cleanIcon,
                'size'       => (string) ($w['size'] ?? 'medium'),
                'visible'    => (bool) $w['visible'],
                'sort_order' => (int) ($w['sort_order'] ?? 0),
            ];
        }, $widgets);

        $formattedAvailableWidgets = [];
        foreach ($this->availableWidgets as $wKey => $wInfo) {
            $rawIcon = (string) ($wInfo['icon'] ?? 'fa-solid fa-cube');
            $cleanIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', $rawIcon);
            $formattedAvailableWidgets[] = [
                'key'     => (string) $wKey,
                'name'    => (string) $wInfo['name'],
                'icon'    => $cleanIcon,
                'default' => (bool) $wInfo['default'],
                'size'    => (string) $wInfo['size'],
            ];
        }

        $page = [
            'hero' => [
                'resortMapKey'       => preg_replace('/[^A-Za-z0-9]/', '', (string) $resortMapKey) ?: 'ParkCity',
                'resortMapName'      => (string) $resortMapName,
                'resortOpen'         => $resortOpen,
                'seasonNum'          => $seasonNum,
                'gameDay'            => $gameDay,
                'liftCount'          => $liftCount,
                'totalLifts'         => $totalLifts,
                'slopeCount'         => $slopeCount,
                'totalSlopes'        => $totalSlopes,
                'repScore'           => $repScore,
                'repScoreFormatted'  => number_format($repScore),
                'bonusAvailable'     => $bonusAvailable,
            ],
            'alerts' => $formattedAlerts,
            'unclaimedAchievements' => $unclaimedAchievements,
            'kpis' => [
                'cashBalance'               => $cashBalance,
                'cashBalanceText'           => currency($cashBalance),
                'cashNegative'              => $cashBalance < 0,
                'netProfit'                 => $netProfit,
                'netProfitText'             => ($netProfit >= 0 ? '+' : '') . currency($netProfit),
                'netProfitPositive'         => $netProfit >= 0,
                'genepisBal'                => $genepisBal,
                'genepisBalFormatted'       => number_format($genepisBal),
                'dailyProfit'               => $dailyProfit,
                'dailyProfitText'           => ($dailyProfit >= 0 ? '+' : '') . currency($dailyProfit),
                'dailyProfitPositive'       => $dailyProfit >= 0,
                'incomePercent'             => $incomePct,
                'incomeText'                => currency($totalIncome),
                'expensesText'              => currency($totalExpenses),
                'dailyVisitors'             => $dailyVisitors,
                'dailyVisitorsFormatted'    => number_format($dailyVisitors),
                'ticketPriceText'           => (int) ($cashflow['ticket_price'] ?? 0) > 0 ? currency((int) $cashflow['ticket_price']) : 'Not set',
                'totalLiftCapacityFormatted'=> number_format($totalLiftCapacity),
                'weather'                   => [
                    'condition_name'   => (string) ($weather['condition_name'] ?? 'Clear'),
                    'tempFormatted'    => $weather ? ($weather['temp_formatted'] ?? temp((int)($weather['temp'] ?? 0))) : '–',
                    'snowBaseFormatted'=> $weather ? snow($weather['snow_base'] ?? 0) : '–',
                    'icon'             => $currentWeatherIcon,
                ],
            ],
            'widgets'          => $formattedWidgets,
            'availableWidgets' => $formattedAvailableWidgets,
            'data' => [
                'stats' => [
                    'revenue'       => currency($totalIncome),
                    'expenses'      => currency($totalExpenses),
                    'tickets'       => currency((int) ($cashflow['ticket_income'] ?? 0)),
                    'buildingsFood' => currency((int) ($cashflow['building_income'] ?? 0)),
                    'payroll'       => currency((int) ($cashflow['salaries'] ?? 0)),
                    'runningCosts'  => currency((int) ($cashflow['running_costs'] ?? 0)),
                    'loanPayments'  => currency((int) ($cashflow['loan_payments'] ?? 0)),
                    'dailyProfit'   => currency($dailyProfit),
                    'isProfit'      => $dailyProfit >= 0,
                ],
                'overview' => [
                    'slopeCount'        => $slopeCount,
                    'totalSlopes'       => $totalSlopes,
                    'slopesByDiff'      => $slopesByDiff,
                    'liftCount'         => $liftCount,
                    'totalLifts'        => $totalLifts,
                    'staffCount'        => $staffCount,
                    'buildingCount'     => $buildingCount,
                    'avgSlopeCondition' => $avgSlopeCondition,
                    'ungroomedCount'    => $ungroomedCount,
                    'trailConditionNote'=> ($ungroomedCount ?? 0) > 0 ? ($ungroomedCount . ' need grooming') : 'All groomed',
                ],
                'season' => [
                    'seasonNum'      => $seasonNum,
                    'seasonProgress' => $seasonProgress,
                    'seasonLength'   => $seasonLength,
                    'seasonPct'      => $seasonPct,
                    'winterDays'     => $winterDays,
                    'summerDays'     => $summerDays,
                ],
                'weather_mini' => [
                    'tempFormatted'     => $weather ? ($weather['temp_formatted'] ?? temp((int)($weather['temp'] ?? 0))) : '–',
                    'condition_name'    => (string) ($weather['condition_name'] ?? 'Clear'),
                    'icon'              => $currentWeatherIcon,
                    'windFormatted'     => speed((int)($weather['wind'] ?? 0)),
                    'snowfallFormatted' => snow($weather['snowfall'] ?? 0),
                    'snowBaseFormatted' => snow($weather['snow_base'] ?? 0),
                    'forecast'          => $formattedForecast,
                ],
                'achievements_mini' => [
                    'unclaimed'       => $unclaimedAchievements,
                    'completed'       => $completedAchievements,
                    'inProgressCount' => count($inProgressAchievements),
                ],
                'achievements' => $formattedInProgressAch,
                'activity'     => $formattedActivity,
                'parking_mini' => [
                    'lotsCount'       => count($parkingFacilities),
                    'spacesFormatted' => number_format($totalParkingCap),
                    'totalOccupied'   => $totalOccupied,
                    'totalCap'        => max(1, $totalParkingCap),
                    'pct'             => $parkingPct,
                ],
                'terrain_parks_mini' => [
                    'openCount'       => count($openParks),
                    'buildingCount'   => count($buildingParks),
                    'ridersFormatted' => number_format($totalParkVisitors),
                ],
                'finances_mini' => [
                    'incomeText'    => currency((int) ($finance['total_income'] ?? 0)),
                    'expensesText'  => currency((int) ($finance['total_expenses'] ?? 0)),
                    'netProfitText' => ($netProfit >= 0 ? '+' : '') . currency($netProfit),
                    'isNetPositive' => $netProfit >= 0,
                ],
                'staff_mini' => [
                    'staffCount' => $staffCount,
                    'roles'      => $formattedRoles,
                    'avgMorale'  => $avgMorale,
                    'moraleTone' => $avgMorale >= 60 ? 'success' : ($avgMorale >= 30 ? 'warning' : 'danger'),
                ],
                'equipment_mini' => [
                    'unitsCount' => count($equipment),
                ],
                'insurance_mini' => [
                    'activeCount'    => count($activeIns),
                    'availableCount' => max(0, count($insurance) - count($activeIns)),
                ],
                'loans_mini' => [
                    'activeCount'  => count($loans),
                    'totalDebtText'=> currency(array_sum(array_column($loans, 'remaining'))),
                ],
                'marketing_mini' => [
                    'campaignsCount' => count($marketing),
                ],
            ],
        ];

        $page['demoEstimate'] = Demo::estimate((int) $userId);

        return view('dashboard/index', ['page' => $page]);
    }

    public function toggleWidget()
    {
        $userId = (int) auth()->id();
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $key = $data['widget_key'] ?? null;
        if (!$key || !isset($this->availableWidgets[$key])) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'csrf_hash' => csrf_hash()]);
            }
            return redirect()->to('/dashboard');
        }

        $db = db_connect();

        $widget = $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->get()->getRowArray();
        if ($widget) {
            $newVisible = $widget['visible'] ? 0 : 1;
            $db->table('dashboard_widgets')->where('id', $widget['id'])->update(['visible' => $newVisible]);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => true, 'visible' => (bool) $newVisible, 'csrf_hash' => csrf_hash()]);
            }
        }
        return redirect()->to('/dashboard');
    }

    public function resizeWidget()
    {
        $userId = (int) auth()->id();
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $key = $data['widget_key'] ?? null;
        $size = $data['size'] ?? null;

        if (!$key || !isset($this->availableWidgets[$key]) || !in_array($size, ['small', 'medium', 'large'], true)) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['success' => false, 'csrf_hash' => csrf_hash()]);
            return redirect()->to('/dashboard');
        }

        $db = db_connect();
        $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->update(['size' => $size]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'size' => $size, 'csrf_hash' => csrf_hash()]);
        }
        return redirect()->to('/dashboard');
    }

    public function reorderWidgets()
    {
        $userId = (int) auth()->id();
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $order = $data['order'] ?? [];
        $db = db_connect();

        if (is_array($order)) {
            foreach ($order as $i => $key) {
                if (is_string($key) && isset($this->availableWidgets[$key])) {
                    $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->update(['sort_order' => (int) $i]);
                }
            }
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'csrf_hash' => csrf_hash()]);
        }
        return redirect()->to('/dashboard');
    }

    public function moveWidget()
    {
        $userId = (int) auth()->id();
        $key = $this->request->getPost('widget_key');
        $direction = $this->request->getPost('direction');
        if (!is_string($key) || !isset($this->availableWidgets[$key]) || !in_array($direction, ['up', 'down'], true)) {
            return redirect()->to('/dashboard');
        }

        $db = db_connect();

        $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();
        $index = array_search($key, array_column($widgets, 'widget_key'));
        if ($index === false) return redirect()->to('/dashboard');

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swapIndex < 0 || $swapIndex >= count($widgets)) return redirect()->to('/dashboard');

        $db->table('dashboard_widgets')->where('id', $widgets[$index]['id'])->update(['sort_order' => $widgets[$swapIndex]['sort_order']]);
        $db->table('dashboard_widgets')->where('id', $widgets[$swapIndex]['id'])->update(['sort_order' => $widgets[$index]['sort_order']]);
        return redirect()->to('/dashboard');
    }

    public function resetWidgets()
    {
        $userId = (int) auth()->id();
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated', 'csrf_hash' => csrf_hash()]);
        }
        $db = db_connect();
        $db->table('dashboard_widgets')->where('user_id', $userId)->delete();
        $order = 0;
        foreach ($this->availableWidgets as $key => $w) {
            $db->table('dashboard_widgets')->insert([
                'user_id' => $userId,
                'widget_key' => $key,
                'visible' => $w['default'] ? 1 : 0,
                'size' => $w['size'],
                'sort_order' => $order++,
            ]);
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'csrf_hash' => csrf_hash()]);
        }
        return redirect()->to('/dashboard')->with('success', 'Dashboard reset to default layout.');
    }

    public function batchUpdateWidgets()
    {
        $userId = (int) auth()->id();
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated', 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $widgets = $data['widgets'] ?? [];
        if (is_array($widgets)) {
            $db = db_connect();
            foreach ($widgets as $w) {
                $key = $w['widget_key'] ?? null;
                if ($key && isset($this->availableWidgets[$key])) {
                    $update = [];
                    if (isset($w['visible'])) $update['visible'] = $w['visible'] ? 1 : 0;
                    if (isset($w['size']) && in_array($w['size'], ['small', 'medium', 'large'], true)) $update['size'] = $w['size'];
                    if (isset($w['sort_order'])) $update['sort_order'] = (int) $w['sort_order'];
                    if (!empty($update)) {
                        $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->update($update);
                    }
                }
            }
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'csrf_hash' => csrf_hash()]);
        }
        return redirect()->to('/dashboard');
    }

    public function recentActivity()
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthenticated']);
        }
        $logs = db_connect()->table('activity_log')
            ->where('user_id', (int) $userId)
            ->orderBy('created_at', 'DESC')
            ->limit(8)
            ->get()->getResultArray();
        return $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate')->setJSON($logs);
    }

    private function calcCashflowBreakdown(
        int $userId,
        int $dailyVisitors,
        array $staff,
        array $equip,
        array $loans,
        array $marketing,
        array $buildings,
        array $insurance = [],
        string $difficulty = 'standard'
    ): array {
        $db = db_connect();

        // Same multipliers the nightly tick applies (Commands/GameTick.php)
        $mult = array_column($db->table('difficulty_config')->where('difficulty', $difficulty)->get()->getResultArray(), 'config_value', 'config_key');
        $revMult  = ((int) ($mult['revenue_multiplier'] ?? 100)) / 100;
        $costMult = ((int) ($mult['cost_multiplier'] ?? 100)) / 100;

        $salaries = (int) array_sum(array_column($staff, 'salary'));

        $activeEquip = array_filter($equip, fn($e) => ($e['status'] ?? '') === 'active');
        $equipCost = (int) array_sum(array_map(fn($e) => (int) round((int) ($e['fuel_cost'] ?? 0) * $costMult), $activeEquip));

        $loanPay = (int) array_sum(array_map(fn($l) => (int)($l['daily_payment'] ?? 0), $loans));
        $marketingCost = (int) array_sum(array_map(fn($m) => (int) round((int) ($m['daily_cost'] ?? 0) * $costMult), $marketing));
        $insuranceCost = (int) array_sum(array_map(fn($i) => (int) ($i['premium_per_day'] ?? 0), array_filter($insurance, fn($i) => (int) ($i['active'] ?? 0) === 1)));

        $openBuildings = array_filter($buildings, fn($b) => ($b['status'] ?? 'open') === 'open');
        $buildingIncome = (int) array_sum(array_map(fn($b) => (int) round((int) ($b['revenue_per_day'] ?? 0) * $revMult), $openBuildings));
        $buildingUpkeep = (int) array_sum(array_map(fn($b) => (int) round((int) ($b['upkeep_per_day'] ?? 0) * $costMult), $openBuildings));

        $tickets = $db->table('lift_tickets')
            ->where('user_id', $userId)
            ->where('active', 1)
            ->where('ticket_type', 'full_day')
            ->get()->getRowArray();
        $ticketPrice = $tickets ? (int)$tickets['price'] : 0;
        // Yesterday's real visitors only; no made-up fallback when there were none
        $ticketIncome = $ticketPrice * max(0, $dailyVisitors);

        // Economy v2: tickets and buildings depend on demand and occupancy, so show what the last tick actually paid
        if (featureEnabled('economy_v2')) {
            $last = json_decode((string) ($db->table('player_finances')->select('last_tick')->where('user_id', $userId)->get()->getRow('last_tick') ?? ''), true);
            if ($last) {
                $ticketIncome = (int) ($last['tickets'] ?? 0);
                $buildingIncome = (int) ($last['buildings'] ?? 0);
                $buildingUpkeep += (int) ($last['lift_slope_running'] ?? 0); // shown with upkeep: lifts and slopes cost to run
            }
        }

        $runningCosts = $buildingUpkeep + $equipCost + $marketingCost + $insuranceCost;
        $totalIncome = $ticketIncome + $buildingIncome;
        $totalExpenses = $salaries + $runningCosts + $loanPay;

        return [
            'ticket_price'    => $ticketPrice,
            'ticket_income'   => $ticketIncome,
            'building_income' => $buildingIncome,
            'total_income'    => $totalIncome,
            'salaries'        => $salaries,
            'building_upkeep' => $buildingUpkeep,
            'equipment_cost'  => $equipCost,
            'marketing_cost'  => $marketingCost,
            'insurance_cost'  => $insuranceCost,
            'running_costs'   => $runningCosts,
            'loan_payments'   => $loanPay,
            'total_expenses'  => $totalExpenses,
            'daily_profit'    => $totalIncome - $totalExpenses,
        ];
    }

    private function getAlerts(int $userId, ?array $finance = null, ?array $equipment = null, ?array $staff = null): array
    {
        $alerts = [];
        $db = db_connect();
        if ($finance === null) {
            $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        }
        if ($finance && (int)$finance['cash'] < 50000) {
            $alerts[] = ['type' => 'error', 'icon' => 'fa-solid fa-money-bill-wave', 'msg' => 'Cash critically low! Consider taking a loan.', 'link' => '/bank'];
        }

        if ($equipment !== null) {
            $broken = count(array_filter($equipment, fn($e) => ($e['status'] ?? '') === 'broken'));
        } else {
            $broken = $db->table('equipment')->where('user_id', $userId)->where('status', 'broken')->countAllResults();
        }
        if ($broken > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'fa-solid fa-wrench', 'msg' => $broken . ' equipment broken. Repair needed.', 'link' => '/equipment'];
        }

        $critical = $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope','downhill','crosscountry','snowpark','luge'])->where('condition_pct <', 40)->countAllResults();
        if ($critical > 0) {
            $alerts[] = ['type' => 'error', 'icon' => 'fa-solid fa-mountain', 'msg' => $critical . ' slope(s) in critical condition!', 'link' => '/grooming'];
        }

        $noStaff = $staff !== null ? count($staff) === 0 : ($db->table('staff')->where('user_id', $userId)->where('status', 'active')->countAllResults() === 0);
        if ($noStaff) {
            $alerts[] = ['type' => 'warning', 'icon' => 'fa-solid fa-users', 'msg' => 'No staff hired yet.', 'link' => '/staff/hire'];
        }
        return $alerts;
    }
}
