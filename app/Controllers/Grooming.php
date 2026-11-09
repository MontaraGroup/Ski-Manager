<?php

namespace App\Controllers;

use App\Libraries\Economy;
use App\Libraries\Grooming as Rules;

/**
 * Slope grooming. The night shift grooms automatically in the game tick; this page shows
 * condition, tonight's plan and the fleet, and sells paid daytime extra runs.
 */
class Grooming extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('grooming'); if ($locked) return $locked;
        return view('grooming/index', ['page' => $this->pageData()]);
    }

    /** What the page shows; the mobile API sends the same data (Api\V1\App) */
    public function pageData(): array
    {
        $userId = auth()->id();
        $state = $this->state($userId);
        $db = db_connect();

        $sectorNames = array_column($db->table('resort_sectors')->select('id, name')->get()->getResultArray(), 'name', 'id');
        $weather = getCurrentWeather();
        $weatherRow = ['temp' => $weather['base_temp'] ?? $weather['temp'], 'snowfall' => $weather['snowfall'] ?? 0,
            'snow_base' => $weather['snow_base'] ?? 0, 'condition_name' => $weather['condition_name'] ?? ''];
        $snowmakers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->where('status', 'active')->get()->getResultArray();
        $covered = \App\Libraries\Snowmaking::coverage(array_filter($state['slopes'], fn($s) => $s['status'] === 'open'), $snowmakers, (int) $weatherRow['temp']);
        $thinBase = (int) $weatherRow['snow_base'] < \App\Libraries\Weather::THIN_BASE;

        $slopes = [];
        foreach ($state['slopes'] as $s) {
            $s['snow_covered'] = isset($covered[(int) $s['id']]);
            $s['thin_base'] = $thinBase && !$s['snow_covered'];
            $s['decay'] = Rules::decay($s, $weatherRow, $s['snow_covered']);
            $s['draw_pct'] = (int) round(Economy::conditionFactor((int) $s['condition_pct']) * 100);
            $s['groomed_today'] = (int) $s['groomed_day'] === $state['gameDay'];
            $s['extra_cost'] = Rules::extraRunCost($s);
            $s['sector_name'] = $sectorNames[(int) $s['sector']] ?? 'Other';
            $slopes[] = $s;
        }
        usort($slopes, fn($a, $b) => (int) $a['condition_pct'] <=> (int) $b['condition_pct']);

        $sectors = [];
        foreach ($slopes as $s) {
            $sectors[$s['sector_name']][] = (int) $s['condition_pct'];
        }
        $sectors = array_map(fn($c) => ['count' => count($c), 'avg' => (int) round(array_sum($c) / count($c)), 'worst' => min($c)], $sectors);

        $open = array_filter($slopes, fn($s) => $s['status'] === 'open');
        $groomable = array_filter($slopes, [Rules::class, 'groomable']);
        $avgDecay = $open ? array_sum(array_column($open, 'decay')) / count($open) : 0;
        // Points the fleet restores per night, spread over all open slopes, against what they lose
        $nightlyRestorePerSlope = $open ? $state['fleet']['capacity'] * $state['restore'] / count($open) : 0;

        $tonight = Rules::pick($groomable, $state['fleet']['capacity']);
        $tonightIds = array_flip(array_map(fn($s) => (int) $s['id'], $tonight));

        $groomableNotGroomedToday = array_values(array_filter($slopes, fn($s) => Rules::groomable($s) && !$s['groomed_today']));
        $batch = array_slice($groomableNotGroomedToday, 0, $state['runsLeft']);
        $batchCost = array_sum(array_column($batch, 'extra_cost'));

        $crewedIds = array_flip(array_map(fn($m) => (int) $m['id'], $state['fleet']['machines']));
        $activeMachines = count(array_filter($state['machines'], fn($m) => $m['status'] === 'active'));

        $formattedSlopes = [];
        foreach ($slopes as $s) {
            $sId = (int) $s['id'];
            $isGroomable = Rules::groomable($s) && !$s['groomed_today'] && $state['runsLeft'] > 0;
            $formattedSlopes[] = [
                'id' => $sId,
                'name' => (string) $s['name'],
                'difficulty' => (string) ($s['difficulty'] ?? ''),
                'sectorName' => (string) $s['sector_name'],
                'lengthText' => distance((int) $s['length_meters']),
                'condition_pct' => (int) $s['condition_pct'],
                'draw_pct' => (int) $s['draw_pct'],
                'decay' => (int) $s['decay'],
                'thin_base' => (bool) $s['thin_base'],
                'snow_covered' => (bool) $s['snow_covered'],
                'status' => (string) $s['status'],
                'groomed_today' => (bool) $s['groomed_today'],
                'isTonight' => isset($tonightIds[$sId]),
                'isGroomable' => $isGroomable,
                'extraCost' => (int) $s['extra_cost'],
                'extraCostText' => currency((int) $s['extra_cost']),
            ];
        }

        $formattedMachines = [];
        foreach ($state['machines'] as $m) {
            $mId = (int) $m['id'];
            $cond = (int) $m['condition_pct'];
            $status = (string) $m['status'];
            $stateLabel = match (true) {
                $status === 'broken' => 'Broken',
                $status !== 'active' => 'Off',
                isset($crewedIds[$mId]) => 'Running',
                default => 'No operator',
            };
            $stateTone = match (true) {
                $status === 'broken' => 'danger',
                $status !== 'active' => 'neutral',
                isset($crewedIds[$mId]) => 'success',
                default => 'warning',
            };

            $formattedMachines[] = [
                'id' => $mId,
                'name' => (string) $m['name'],
                'capacity' => (int) $m['capacity'],
                'condition_pct' => $cond,
                'status' => $status,
                'stateLabel' => $stateLabel,
                'stateTone' => $stateTone,
                'fuelCostText' => currency((int) $m['fuel_cost']),
            ];
        }

        $formattedOperators = [];
        foreach ($state['operators'] as $o) {
            $formattedOperators[] = [
                'id' => (int) $o['id'],
                'name' => (string) $o['name'],
                'level' => (int) $o['level'],
            ];
        }

        $formattedSectors = [];
        foreach ($sectors as $name => $sec) {
            $formattedSectors[] = [
                'name' => (string) $name,
                'count' => (int) $sec['count'],
                'avg' => (int) $sec['avg'],
                'worst' => (int) $sec['worst'],
            ];
        }

        $fuelPerDay = array_sum(array_map(fn($m) => $m['status'] === 'active' ? (int) $m['fuel_cost'] : 0, $state['machines']));
        $crewPerDay = array_sum(array_column($state['operators'], 'salary'));
        $net = $nightlyRestorePerSlope - $avgDecay;

        $page = [
            'overview' => [
                'capacity' => $state['fleet']['capacity'],
                'runsLeft' => $state['runsLeft'],
                'avgCondition' => $open ? (int) round(array_sum(array_column($open, 'condition_pct')) / count($open)) : 0,
                'avgDecay' => round($avgDecay, 1),
                'nightlyRestorePerSlope' => round($nightlyRestorePerSlope, 1),
                'netPositive' => $net >= 0,
                'tonightCount' => count($tonight),
                'slopesCount' => count($slopes),
                'restore' => $state['restore'],
                'idleMachines' => $state['fleet']['idle_machines'],
                'activeMachines' => $activeMachines,
                'brokenSlopes' => count(array_filter($slopes, fn($s) => $s['status'] === 'broken')),
                'machinesCount' => count($state['machines']),
                'extraRunCostPerKmText' => currency((int) round(\App\Libraries\Grooming::EXTRA_RUN_PER_M * 1000)),
            ],
            'batch' => [
                'count' => count($batch),
                'cost' => $batchCost,
                'costText' => currency($batchCost),
                'available' => count($batch) > 0 && $state['fleet']['capacity'] > 0,
            ],
            'slopes' => $formattedSlopes,
            'machines' => $formattedMachines,
            'operators' => $formattedOperators,
            'costs' => [
                'fuelText' => currency($fuelPerDay),
                'crewText' => currency($crewPerDay),
                'totalText' => currency($fuelPerDay + $crewPerDay),
            ],
            'sectors' => $formattedSectors,
            'rules' => [
                'thinBaseThreshold' => \App\Libraries\Weather::THIN_BASE,
                'restoreBase' => \App\Libraries\Grooming::RESTORE_BASE,
                'restoreMax' => \App\Libraries\Grooming::RESTORE_MAX,
                'extraRunPerKmText' => currency((int) round(\App\Libraries\Grooming::EXTRA_RUN_PER_M * 1000)),
            ],
        ];

        $fin = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $cash = (int) ($fin['cash'] ?? 0);
        $page['cash'] = [
            'raw' => $cash,
            'text' => currency($cash),
        ];

        return $page;
    }

    /** Paid daytime run on one slope */
    public function groomSingle()
    {
        return $this->extraRuns([(int) $this->request->getPost('slope_id')]);
    }

    /** Paid daytime runs on the worst slopes, as many as the fleet and cash allow */
    public function groomAll()
    {
        return $this->extraRuns(null);
    }

    private function extraRuns(?array $slopeIds)
    {
        $userId = auth()->id();
        $state = $this->state($userId);
        if ($state['fleet']['capacity'] === 0) {
            return redirect()->to('/grooming')->with('error', $state['machines'] ? 'No machine can run: switch one on and make sure it has an operator.' : 'You need a grooming machine first.');
        }
        if ($state['runsLeft'] <= 0) {
            return redirect()->to('/grooming')->with('error', 'Your fleet has no daytime runs left today. The night shift grooms again tonight.');
        }

        $due = array_filter($state['slopes'], fn($s) => Rules::groomable($s) && (int) $s['groomed_day'] !== $state['gameDay']);
        if ($slopeIds !== null) {
            $due = array_filter($due, fn($s) => in_array((int) $s['id'], $slopeIds, true));
            if (!$due) return redirect()->to('/grooming')->with('error', 'That slope can\'t be groomed now: it\'s already at 100% or was groomed today.');
        }
        $pick = Rules::pick($due, $state['runsLeft']);
        if (!$pick) return redirect()->to('/grooming')->with('error', 'Every open slope is at 100% or was already groomed today.');

        $db = db_connect();
        $cash = (int) $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow('cash');
        $done = []; $spent = 0;
        foreach ($pick as $s) {
            $cost = Rules::extraRunCost($s);
            if ($spent + $cost > $cash) break;
            $spent += $cost;
            $done[] = $s;
        }
        if (!$done) return redirect()->to('/grooming')->with('error', 'Not enough cash for a grooming run (' . currency(Rules::extraRunCost($pick[0])) . ').');

        $db->transStart();
        foreach ($done as $s) {
            $db->table('player_items')->where('id', $s['id'])->where('user_id', $userId)->update([
                'condition_pct' => min(100, (int) $s['condition_pct'] + $state['restore']), 'groomed_day' => $state['gameDay'], 'groomed_manual' => 1, 'status' => 'open',
            ]);
        }
        $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash - ' . $spent, false)->set('total_expenses', 'total_expenses + ' . $spent, false)->update();
        $label = count($done) === 1 ? $done[0]['name'] : count($done) . ' slopes';
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => $state['gameDay'], 'category' => 'Grooming',
            'description' => 'Daytime grooming: ' . $label, 'amount' => $spent, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Grooming', 'Daytime grooming on ' . $label . ' (+' . $state['restore'] . '% each, ' . currency($spent) . ')', 'fa-solid fa-tractor');
        $short = count($done) < count($pick) ? ' Ran out of cash after ' . count($done) . '.' : '';
        return redirect()->to('/grooming')->with('success', 'Groomed ' . $label . ': +' . $state['restore'] . '% condition for ' . currency($spent) . '.' . $short);
    }

    /** Slopes, fleet and today's run budget for a player */
    private function state(int $userId): array
    {
        $db = db_connect();
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->get()->getResultArray();
        $machines = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->get()->getResultArray();
        $operators = $db->table('staff')->where('user_id', $userId)->where('role', 'groomer')->where('status', 'active')->get()->getResultArray();
        $fleet = Rules::fleet($machines, $operators);
        $gameDay = getGameDay();
        $runsUsed = count(array_filter($slopes, fn($s) => (int) $s['groomed_day'] === $gameDay && (int) $s['groomed_manual'] === 1));

        return [
            'gameDay' => $gameDay,
            'slopes' => $slopes,
            'machines' => $machines,
            'operators' => $operators,
            'fleet' => $fleet,
            'restore' => Rules::restore($operators),
            'runsUsed' => $runsUsed,
            'runsLeft' => max(0, $fleet['capacity'] - $runsUsed),
        ];
    }
}
