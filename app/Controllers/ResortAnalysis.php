<?php

namespace App\Controllers;

use App\Libraries\Economy;
use App\Libraries\Grooming;
use App\Libraries\Snowmaking;
use App\Libraries\Weather;

/**
 * /resort-analysis: a paid (Génépis) consultant's report. It reads what the nightly tick measured
 * yesterday (player_finances.last_tick: income, costs, demand, ticket price effect, lift capacity,
 * building occupancy) plus the resort as it stands, scores seven areas and lists what to do next,
 * most urgent first, each with a link to the page that fixes it.
 */
class ResortAnalysis extends BaseController
{
    private const COST_GENEPIS = 20;

    public const AREAS = [
        'profit' => ['Profit', 'fa-coins', 'text-success'],
        'demand' => ['Skiers & prices', 'fa-person-skiing', 'text-info'],
        'lifts' => ['Lifts', 'fa-cable-car', 'text-primary'],
        'slopes' => ['Slopes & grooming', 'fa-mountain', 'text-info'],
        'buildings' => ['Buildings', 'fa-hotel', 'text-secondary'],
        'staff' => ['Staff', 'fa-users', 'text-warning'],
        'cash' => ['Cash & debt', 'fa-building-columns', 'text-success'],
    ];

    public function index(): string
    {
        $locked = checkFeatureUnlock('resort_analysis'); if ($locked) return $locked;
        $userId = auth()->id();
        $db = db_connect();
        $reports = $db->table('resort_reports')->where('user_id', $userId)->orderBy('id', 'DESC')->get()->getResultArray();
        
        $formattedReports = [];
        foreach ($reports as $i => $r) {
            $d = json_decode((string) $r['report_data'], true) ?: [];
            $score = (int) ($d['overall_score'] ?? 0);
            $recs = $d['recommendations'] ?? [];
            $urgent = count(array_filter($recs, fn($x) => ($x['type'] ?? '') === 'critical'));
            $topRecs = [];
            if ($i === 0 && !empty($recs)) {
                foreach (array_slice($recs, 0, 3) as $rec) {
                    $topRecs[] = [
                        'type' => (string) ($rec['type'] ?? 'info'),
                        'text' => (string) ($rec['text'] ?? ''),
                        'area' => (string) ($rec['area'] ?? ''),
                        'link' => (string) ($rec['link'] ?? ''),
                    ];
                }
            }

            $scoreTone = $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger');

            $formattedReports[] = [
                'id' => (int) $r['id'],
                'game_day' => (int) $r['game_day'],
                'created_at' => (string) $r['created_at'],
                'created_date' => date('M j, Y', strtotime($r['created_at'])),
                'score' => $score,
                'score_tone' => $scoreTone,
                'is_latest' => $i === 0,
                'recommendations_count' => count($recs),
                'urgent_count' => $urgent,
                'top_recommendations' => $topRecs,
            ];
        }

        $areas = [];
        foreach (self::AREAS as $key => [$name, $icon, $color]) {
            $cleanIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $icon);
            $areas[] = [
                'key' => $key,
                'name' => $name,
                'icon' => $cleanIcon,
                'color' => $color,
            ];
        }

        $balance = (int) ($db->table('genepis')->select('balance')->where('user_id', $userId)->get()->getRow()->balance ?? 0);
        $cost = self::COST_GENEPIS;
        $todayReport = $this->todayReport($userId);

        $page = [
            'balance' => $balance,
            'cost' => $cost,
            'canAfford' => $balance >= $cost,
            'gameDay' => getGameDay(),
            'todayReport' => $todayReport ? [
                'id' => (int) $todayReport['id'],
                'game_day' => (int) $todayReport['game_day'],
            ] : null,
            'areas' => $areas,
            'reports' => $formattedReports,
        ];

        return view('resort_analysis/index', ['page' => $page]);
    }

    /** This season's report for today's game day, if one was ordered */
    private function todayReport(int $userId): ?array
    {
        return db_connect()->table('resort_reports')->where('user_id', $userId)->where('game_day', getGameDay())
            ->where('created_at >=', getSeasonStartDate() . ' 00:00:00')->get()->getRowArray();
    }

    public function order()
    {
        $locked = checkFeatureUnlock('resort_analysis'); if ($locked) return redirect()->to('/resort-analysis');
        $userId = auth()->id();
        $db = db_connect();
        if ($existing = $this->todayReport($userId)) {
            return redirect()->to('/resort-analysis/view/' . $existing['id'])->with('error', 'You already have today\'s report. A new one is available tomorrow.');
        }

        $reportData = $this->generateReport($userId);
        $db->transStart();
        // Pay only if the balance covers it, in one statement, so a double click can't overspend
        $db->table('genepis')->where('user_id', $userId)->where('balance >=', self::COST_GENEPIS)
            ->set('balance', 'balance - ' . self::COST_GENEPIS, false)->set('total_spent', 'total_spent + ' . self::COST_GENEPIS, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/resort-analysis')->with('error', 'Not enough Génépis: a report costs ' . self::COST_GENEPIS . '.');
        }
        $db->table('genepis_log')->insert(['user_id' => $userId, 'amount' => -self::COST_GENEPIS, 'reason' => 'Resort Analysis Report', 'created_at' => date('Y-m-d H:i:s')]);
        $db->table('resort_reports')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'status' => 'ready',
            'report_data' => json_encode($reportData), 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = $db->insertID();
        $db->transComplete();

        log_activity($userId, 'resort_analysis', 'Ordered a resort analysis report for ' . self::COST_GENEPIS . ' Génépis', 'fa-solid fa-clipboard-check');
        return redirect()->to('/resort-analysis/view/' . $id)->with('success', 'Your report is ready.');
    }

    public function view(int $id)
    {
        $report = db_connect()->table('resort_reports')->where('id', $id)->where('user_id', auth()->id())->get()->getRowArray();
        if (!$report) return redirect()->to('/resort-analysis')->with('error', 'Report not found.');
        $data = json_decode((string) $report['report_data'], true) ?: [];

        $overall = (int) ($data['overall_score'] ?? 0);
        $scoreTone = $overall >= 70 ? 'success' : ($overall >= 40 ? 'warning' : 'danger');
        $headline = $overall >= 80 ? 'Running very well' : ($overall >= 60 ? 'Solid, with clear wins available' : ($overall >= 40 ? 'Several things holding you back' : 'Needs attention now'));

        $recs = $data['recommendations'] ?? [];
        $urgentCount = count(array_filter($recs, fn($r) => ($r['type'] ?? '') === 'critical'));
        $warningCount = count(array_filter($recs, fn($r) => ($r['type'] ?? '') === 'warning'));
        $totalRecs = count($recs);

        $formattedRecs = array_map(function ($rec) {
            $type = (string) ($rec['type'] ?? 'info');
            $icon = $type === 'critical' ? 'fa-circle-xmark' : ($type === 'warning' ? 'fa-triangle-exclamation' : 'fa-lightbulb');
            $tone = $type === 'critical' ? 'danger' : ($type === 'warning' ? 'warning' : 'accent');
            $label = $type === 'critical' ? 'Urgent' : ($type === 'warning' ? 'Important' : 'Idea');
            return [
                'type' => $type,
                'tone' => $tone,
                'label' => $label,
                'icon' => $icon,
                'area' => (string) ($rec['area'] ?? ''),
                'text' => (string) ($rec['text'] ?? ''),
                'link' => (string) ($rec['link'] ?? ''),
            ];
        }, $recs);

        $formattedSections = [];
        if (!empty($data['sections'])) {
            foreach (array_intersect_key(self::AREAS, $data['sections']) as $key => [$name, $icon, $color]) {
                $sec = $data['sections'][$key];
                $s = isset($sec['score']) && $sec['score'] !== null ? (int) $sec['score'] : null;
                $sTone = $s === null ? 'default' : ($s >= 70 ? 'success' : ($s >= 40 ? 'warning' : 'danger'));
                $cleanIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $icon);
                $formattedSections[] = [
                    'key' => $key,
                    'name' => $name,
                    'icon' => $cleanIcon,
                    'score' => $s,
                    'score_tone' => $sTone,
                    'summary' => (string) ($sec['summary'] ?? ''),
                    'metrics' => array_map(function ($m) {
                        return [
                            'label' => (string) ($m[0] ?? ''),
                            'value' => (string) ($m[1] ?? ''),
                        ];
                    }, $sec['metrics'] ?? []),
                ];
            }
        }

        $v1Labels = [
            'infrastructure' => ['Infrastructure', 'fa-road'],
            'staffing' => ['Staffing', 'fa-users'],
            'finances' => ['Finances', 'fa-coins'],
            'amenities' => ['Amenities', 'fa-building'],
            'equipment' => ['Equipment', 'fa-toolbox'],
            'resources' => ['Resources', 'fa-bolt'],
            'safety' => ['Safety', 'fa-shield-halved'],
        ];
        $legacyScores = [];
        if (empty($data['sections']) && !empty($data['scores'])) {
            foreach ($data['scores'] as $cat => $s) {
                [$cName, $cIcon] = $v1Labels[$cat] ?? [ucfirst($cat), 'fa-circle'];
                $cleanCIcon = preg_replace('/^fa-(solid|regular|brands)\s+/', '', (string) $cIcon);
                $legacyScores[] = [
                    'name' => $cName,
                    'icon' => $cleanCIcon,
                    'score' => (int) $s,
                ];
            }
        }

        $page = [
            'report' => [
                'id' => (int) $report['id'],
                'game_day' => (int) $report['game_day'],
                'created_at' => (string) $report['created_at'],
                'created_date' => date('M j, Y', strtotime($report['created_at'])),
                'measured_day' => isset($data['measured_day']) ? (int) $data['measured_day'] : null,
            ],
            'overall_score' => $overall,
            'score_tone' => $scoreTone,
            'headline' => $headline,
            'stats_summary' => [
                'urgent_count' => $urgentCount,
                'warning_count' => $warningCount,
                'total_recommendations' => $totalRecs,
            ],
            'recommendations' => $formattedRecs,
            'sections' => $formattedSections,
            'legacyScores' => $legacyScores,
        ];

        return view('resort_analysis/view', ['page' => $page]);
    }

    private function generateReport(int $userId): array
    {
        helper('weather');
        $db = db_connect();
        $hidden = array_map('trim', explode(',', (string) getDifficultyConfig('hidden_pages', '')));
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray() ?? [];
        $tick = json_decode((string) ($finance['last_tick'] ?? ''), true) ?: null;
        $items = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $staff = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $buildings = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();
        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $weather = getCurrentWeather();

        $slopes = array_values(array_filter($items, fn($i) => $i['item_type'] === 'slope'));
        $lifts = array_values(array_filter($items, fn($i) => $i['item_type'] === 'lift'));
        $openSlopes = array_values(array_filter($slopes, fn($i) => $i['status'] === 'open'));
        $openLifts = array_values(array_filter($lifts, fn($i) => $i['status'] === 'open'));
        $avg = fn(array $rows, string $col) => $rows ? (int) round(array_sum(array_column($rows, $col)) / count($rows)) : 0;
        $cash = (int) ($finance['cash'] ?? 0);
        $recs = [];
        $add = function (string $type, string $area, string $text, string $link = '') use (&$recs) { $recs[] = compact('type', 'area', 'text', 'link'); };
        $sections = [];

        // --- Profit (yesterday, from the tick) ---
        $income = (int) ($tick['income'] ?? 0);
        $expenses = (int) ($tick['expenses'] ?? 0);
        $profit = $income - $expenses;
        $costLines = array_filter([
            'Lift and slope running costs' => (int) ($tick['lift_slope_running'] ?? 0),
            'Salaries' => (int) ($tick['salaries'] ?? 0),
            'Building upkeep' => (int) ($tick['building_upkeep'] ?? 0),
            'Other costs (equipment, loans, energy, insurance…)' => (int) ($tick['other_costs'] ?? 0),
        ]);
        arsort($costLines);
        if ($tick === null) {
            $sections['profit'] = ['score' => null, 'summary' => 'No day has been played yet: the first figures arrive after tonight\'s update.', 'metrics' => []];
        } else {
            $margin = $income > 0 ? $profit / $income : ($expenses > 0 ? -1 : 0);
            $sections['profit'] = [
                'score' => (int) max(0, min(100, round(50 + $margin * 125))),
                'summary' => $profit >= 0 ? 'Yesterday made ' . currency($profit) . ' profit (' . (int) round($margin * 100) . '% of income).' : 'Yesterday lost ' . currency(-$profit) . '.',
                'metrics' => array_merge([['Income', currency($income)], ['Costs', currency($expenses)]], array_map(fn($k, $v) => [$k, currency($v)], array_keys(array_slice($costLines, 0, 3, true)), array_slice($costLines, 0, 3))),
            ];
            if ($profit < 0) {
                $add('critical', 'Profit', 'You lost ' . currency(-$profit) . ' yesterday. The biggest cost was ' . strtolower((string) array_key_first($costLines)) . ' (' . currency((int) reset($costLines)) . '). Close lifts and slopes nobody needs, or raise income first.', '/finances');
            }
        }

        // --- Skiers & ticket prices ---
        $demand = (int) ($tick['demand'] ?? 0);
        $priceDemand = (float) ($tick['price_demand'] ?? 1);
        $turnedAway = (int) ($tick['turned_away'] ?? 0);
        $visitors = (int) ($tick['visitors'] ?? 0);
        if ($tick === null || $demand <= 0) {
            $sections['demand'] = ['score' => $tick === null ? null : 0, 'summary' => $tick === null ? 'Not measured yet.' : 'No skiers wanted to come yesterday: open slopes are what attract them.', 'metrics' => []];
            if ($tick !== null && !$openSlopes) $add('critical', 'Skiers & prices', 'No open slopes, so no skiers. Build or open slopes on the trail map.', '/map');
        } else {
            $lostToPrice = (int) round($demand * (1 - $priceDemand));
            $sections['demand'] = [
                'score' => (int) round(100 * min(1, $priceDemand) * ($visitors + $turnedAway > 0 ? $visitors / ($visitors + $turnedAway) : 1)),
                'summary' => number_format($visitors) . ' skiers came, out of ' . number_format($demand) . ' who wanted to.',
                'metrics' => [['Skiers who wanted to come', number_format($demand)], ['Put off by ticket prices', number_format($lostToPrice)],
                    ['Turned away (lifts full)', number_format($turnedAway)], ['Earned per skier', currency((int) round((float) ($tick['per_visit'] ?? 0)))]],
            ];
            if ($priceDemand < 0.8) $add('warning', 'Skiers & prices', 'Ticket prices put off ' . (int) round((1 - $priceDemand) * 100) . '% of skiers (' . number_format($lostToPrice) . ' people). Lowering prices may earn more in total.', '/tickets');
            elseif ($priceDemand >= 0.98 && $turnedAway === 0) $add('info', 'Skiers & prices', 'Almost nobody is put off by your ticket prices: you can likely raise them a little.', '/tickets');
        }

        // --- Lifts ---
        $liftCap = (int) ($tick['lift_capacity'] ?? Economy::liftDailyCapacity($openLifts));
        $brokenLifts = count(array_filter($lifts, fn($l) => $l['status'] === 'broken'));
        $mechanicLifts = array_flip(array_column(array_filter($staff, fn($s) => $s['role'] === 'mechanic' && !empty($s['assigned_to'])), 'assigned_to'));
        $unmaintained = count(array_filter($lifts, fn($l) => !isset($mechanicLifts[$l['name'] ?? 'Lift #' . $l['id']])));
        $liftCond = $avg($openLifts, 'condition_pct');
        $sections['lifts'] = [
            'score' => !$lifts ? 0 : (int) round(min(100, $liftCond) * ($turnedAway > 0 ? max(0.4, $visitors / max(1, $visitors + $turnedAway)) : 1) * (count($openLifts) / count($lifts))),
            'summary' => !$lifts ? 'No lifts yet.' : count($openLifts) . ' of ' . count($lifts) . ' lifts open, carrying up to ' . number_format($liftCap) . ' skiers a day.',
            'metrics' => [['Open lifts', count($openLifts) . ' / ' . count($lifts)], ['Daily capacity', number_format($liftCap)], ['Average condition', $liftCond . '%'], ['Broken', (string) $brokenLifts]],
        ];
        if (!$lifts) $add('critical', 'Lifts', 'You have no lifts: skiers need them to reach your slopes. Build one on the trail map.', '/map');
        if ($turnedAway > 0) $add('critical', 'Lifts', number_format($turnedAway) . ' skiers were turned away because your lifts were full. Build another lift or a bigger one.', '/map');
        if ($brokenLifts) $add('warning', 'Lifts', $brokenLifts . ' lift' . ($brokenLifts === 1 ? ' is' : 's are') . ' broken. Repair ' . ($brokenLifts === 1 ? 'it' : 'them') . ' on the resort page to reopen.', '/resort');
        if ($lifts && $unmaintained > 0 && $liftCond < 80) $add('info', 'Lifts', $unmaintained . ' lift' . ($unmaintained === 1 ? ' has' : 's have') . ' no mechanic. Lifts wear up to 2% a day and cost money to repair; a mechanic assigned to a lift cuts its wear by 1% a day.', '/staff/hire');

        // --- Slopes & grooming ---
        $operators = array_values(array_filter($staff, fn($s) => $s['role'] === 'groomer'));
        $fleet = Grooming::fleet(array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer'), $operators);
        $groomable = count(array_filter($slopes, [Grooming::class, 'groomable']));
        $brokenSlopes = count(array_filter($slopes, fn($s) => $s['status'] === 'broken'));
        $slopeCond = $avg($openSlopes, 'condition_pct');
        $thinBase = (int) ($weather['snow_base'] ?? 99) < Weather::THIN_BASE;
        $covered = count(Snowmaking::coverage($openSlopes, array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker'), (int) ($weather['base_temp'] ?? 0)));
        $sections['slopes'] = [
            'score' => !$slopes ? 0 : (int) round(Economy::conditionFactor($slopeCond) * 100 * (count($openSlopes) / count($slopes))),
            'summary' => !$slopes ? 'No slopes yet.' : count($openSlopes) . ' of ' . count($slopes) . ' slopes open at ' . $slopeCond . '% average condition; skiers come less to worn slopes.',
            'metrics' => [['Open slopes', count($openSlopes) . ' / ' . count($slopes)], ['Average condition', $slopeCond . '%'],
                ['Groomed each night', $fleet['capacity'] . ' slopes'], ['Covered by snow guns', $thinBase ? $covered . ' of ' . count($openSlopes) : 'not needed (deep base)']],
        ];
        if (!$slopes) $add('critical', 'Slopes & grooming', 'You have no slopes: they are what bring skiers. Build one on the trail map.', '/map');
        if ($brokenSlopes) $add('warning', 'Slopes & grooming', $brokenSlopes . ' slope' . ($brokenSlopes === 1 ? '' : 's') . ' wore down to 0% and closed. Grooming brings them back.', '/grooming');
        if ($fleet['idle_machines'] > 0) $add('warning', 'Slopes & grooming', $fleet['idle_machines'] . ' groomer' . ($fleet['idle_machines'] === 1 ? ' has' : 's have') . ' no operator and stay parked. Hire groomer operators.', '/staff/hire');
        elseif ($slopes && $fleet['capacity'] === 0) $add('warning', 'Slopes & grooming', 'Nothing grooms your slopes, so they only wear down. Buy a groomer and hire an operator.', '/equipment');
        elseif ($slopes && $fleet['capacity'] < min($groomable, (int) ceil(count($slopes) / 2))) $add('info', 'Slopes & grooming', 'Your groomers reach ' . $fleet['capacity'] . ' slopes a night but ' . $groomable . ' need it. Another groomer would keep them in better shape.', '/equipment');
        if ($thinBase && $openSlopes && $covered < count($openSlopes) && !in_array('snowmaking', $hidden, true)) $add('info', 'Slopes & grooming', 'The natural snow base is thin: ' . (count($openSlopes) - $covered) . ' open slope' . (count($openSlopes) - $covered === 1 ? '' : 's') . ' without a snow gun wear faster.', '/snowmaking');

        // --- Buildings (occupancy from the tick) ---
        $occupancy = is_array($tick['occupancy'] ?? null) ? $tick['occupancy'] : [];
        $avgOcc = $occupancy ? array_sum($occupancy) / count($occupancy) : null;
        $sections['buildings'] = [
            'score' => !$buildings ? 40 : ($avgOcc === null ? null : (int) round(100 * min(1, $avgOcc / 0.85))),
            'summary' => !$buildings ? 'No buildings: restaurants, rentals and hotels earn from your skiers.' : ($avgOcc === null ? count($buildings) . ' buildings; occupancy is measured on a played day.' : count($buildings) . ' buildings, ' . (int) round($avgOcc * 100) . '% full on average, earning ' . currency((int) ($tick['buildings'] ?? 0)) . ' yesterday.'),
            'metrics' => array_map(fn($type, $u) => [ucwords(str_replace('_', ' ', $type)) . ' occupancy', (int) round($u * 100) . '%'], array_keys($occupancy), $occupancy),
        ];
        $buildingPage = ['restaurant' => '/restaurants', 'rental' => '/rentals', 'hotel' => '/hotels'];
        if (!$buildings && $visitors > 0) $add('info', 'Buildings', 'You have skiers but no buildings. A restaurant or ski rental earns from every visitor.', '/restaurants');
        foreach ($occupancy as $type => $u) {
            $label = str_replace('_', ' ', $type);
            if ($u >= 0.95) $add('info', 'Buildings', 'Your ' . $label . 's are full (' . (int) round($u * 100) . '%): another one or an upgrade would earn more.', $buildingPage[$type] ?? '');
            elseif ($u < 0.3) $add('warning', 'Buildings', 'Your ' . $label . 's are only ' . (int) round($u * 100) . '% full: you have more than your skiers need, and each still costs upkeep.', $buildingPage[$type] ?? '');
        }

        // --- Staff ---
        $morale = $avg($staff, 'morale');
        $unassigned = count(array_filter($staff, fn($s) => empty($s['assigned_to']) && $s['role'] !== 'groomer'));
        $sections['staff'] = [
            'score' => !$staff ? 30 : $morale,
            'summary' => !$staff ? 'No staff.' : count($staff) . ' staff at ' . $morale . '% average morale, costing ' . currency((int) ($tick['salaries'] ?? array_sum(array_column($staff, 'salary')))) . ' a day.',
            'metrics' => [['Staff', (string) count($staff)], ['Average morale', $morale . '%'], ['Not assigned', (string) $unassigned]],
        ];
        if (!$staff) $add('warning', 'Staff', 'You have no staff. Groomer operators, mechanics and ski patrol keep the resort running.', '/staff/hire');
        elseif ($morale < 50) $add('warning', 'Staff', 'Staff morale is ' . $morale . '%: unhappy staff quit. Check their workload and pay.', '/staff');
        if ($unassigned > 0) $add('info', 'Staff', $unassigned . ' staff member' . ($unassigned === 1 ? ' isn\'t assigned to anything and only gets' : 's aren\'t assigned to anything and only get') . ' paid. Assign them or let them go.', '/staff');

        // --- Cash & debt ---
        $dailyLoan = array_sum(array_map(fn($l) => min((int) $l['daily_payment'], (int) $l['remaining']), $loans));
        $debt = array_sum(array_column($loans, 'remaining'));
        $runway = $expenses > 0 ? $cash / $expenses : null;
        $sections['cash'] = [
            'score' => $cash < 0 ? 0 : ($profit >= 0 && $tick !== null ? 100 : (int) min(100, round(($runway ?? 30) / 30 * 100))) - ($income > 0 && $dailyLoan > $income * 0.5 ? 30 : 0),
            'summary' => 'Cash ' . currency($cash) . ($debt ? ', owing ' . currency($debt) . ' (' . currency($dailyLoan) . ' a day)' : ', no loans') . '.',
            'metrics' => [['Cash', currency($cash)], ['Owed', currency($debt)], ['Loan repayments a day', currency($dailyLoan)], ['Days of costs in cash', $runway === null ? '–' : (string) (int) floor(max(0, $runway))]],
        ];
        if ($cash < 0) $add('critical', 'Cash & debt', 'Your cash is negative. Cut costs before anything else breaks down.', '/finances');
        elseif ($profit < 0 && $runway !== null && $runway < 14) $add('critical', 'Cash & debt', 'At yesterday\'s loss your cash lasts about ' . (int) floor($runway) . ' more days.', '/finances');
        if ($income > 0 && $dailyLoan > $income * 0.5) $add('warning', 'Cash & debt', 'Loan repayments take ' . (int) round($dailyLoan / $income * 100) . '% of your income. Pay one off early when you can.', '/bank');

        $order = ['critical' => 0, 'warning' => 1, 'info' => 2];
        usort($recs, fn($a, $b) => $order[$a['type']] <=> $order[$b['type']]);
        $scored = array_filter(array_column($sections, 'score'), fn($s) => $s !== null);
        $scores = array_map(fn($s) => $s['score'] === null ? null : max(0, min(100, (int) $s['score'])), $sections);
        foreach ($sections as $k => &$s) $s['score'] = $scores[$k];
        unset($s);

        return [
            'version' => 2,
            'measured_day' => $tick['game_day'] ?? null,
            'overall_score' => $scored ? (int) round(array_sum(array_map(fn($v) => max(0, min(100, $v)), $scored)) / count($scored)) : 0,
            'scores' => array_filter($scores, fn($v) => $v !== null),
            'sections' => $sections,
            'recommendations' => $recs,
            'stats' => [
                'cash' => $cash, 'slopes' => count($slopes), 'open_slopes' => count($openSlopes), 'lifts' => count($lifts), 'open_lifts' => count($openLifts),
                'staff' => count($staff), 'avg_morale' => $morale, 'buildings' => count($buildings), 'equipment' => count($equipment),
                'avg_equip_condition' => $avg($equipment, 'condition_pct'), 'avg_infra_condition' => $avg(array_merge($openSlopes, $openLifts), 'condition_pct'),
                'parking' => 0, 'terrain_parks' => 0, 'energy_sources' => 0, 'water_sources' => 0, 'insurance' => 0,
                'total_debt' => $debt, 'daily_salary' => (int) ($tick['salaries'] ?? array_sum(array_column($staff, 'salary'))),
                'income' => $income, 'expenses' => $expenses, 'visitors' => $visitors, 'demand' => $demand,
            ],
        ];
    }

    public function pdf(int $id)
    {
        $report = db_connect()->table('resort_reports')->where('id', $id)->where('user_id', auth()->id())->get()->getRowArray();
        if (!$report) return redirect()->to('/resort-analysis')->with('error', 'Report not found.');
        $data = json_decode((string) $report['report_data'], true) ?: [];

        $dompdf = new \Dompdf\Dompdf(['defaultFont' => 'Helvetica']);
        $dompdf->loadHtml(view('resort_analysis/pdf', ['report' => $report, 'data' => $data]));
        $dompdf->addInfo('Title', 'Ski Manager Resort Analysis - Day ' . $report['game_day']);
        $dompdf->addInfo('Author', 'Ski Manager - ski-manager.net');
        $dompdf->render();

        return $this->response->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="Resort_Analysis_Day_' . (int) $report['game_day'] . '.pdf"')
            ->setBody($dompdf->output());
    }
}
