<?php

namespace App\Controllers;

use App\Libraries\Fleet;
use App\Libraries\Grooming as G;
use App\Libraries\Snowmaking as SM;
use App\Libraries\Utilities;
use App\Libraries\Weather as W;
use App\Libraries\WeatherPro;

/**
 * /snowmaking: snow guns, the snow they make on each slope, targets, the next five days and what it
 * all costs. The rules are App\Libraries\Snowmaking, the same ones the nightly tick uses.
 * The mobile API (Api\V1\App\Snowmaking) sends the same pageData(); keys the Android app reads
 * (overview.standardHours, coveredCount, guns[].operatingCostText, outlook[].hoursText...) stay.
 */
class Snowmaking extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('snowmaking'); if ($locked) return $locked;
        return view('snowmaking/index', ['page' => $this->pageData()]);
    }

    public function pageData(): array
    {
        $userId = auth()->id();
        $db = db_connect();
        helper('weather');

        $today = SM::today($userId);
        $baseTemp = $today['weather']['temp'];
        $humidity = $today['weather']['humidity'];
        $natural = $today['weather']['snow_base'];
        $prices = Fleet::prices();
        $costMult = ((int) getDifficultyConfig('cost_multiplier', '100')) / 100;
        $standard = ['model_key' => ''];

        $crewByGun = array_column(array_filter($today['staff'], fn($s) => !empty($s['assigned_to'])), null, 'assigned_to');
        $crewLevels = SM::crewLevels($today['staff']);
        $allSlopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->orderBy('name')->get()->getResultArray();
        $slopeNames = array_column($allSlopes, 'name', 'id');

        // Energy and water: today's running hours against everything else the sources supply
        $running = $today['running'];
        $energySupply = (int) $db->table('energy_management')->selectSum('output_kwh')->where('user_id', $userId)->where('status', 'active')->get()->getRow()->output_kwh;
        $waterSupply = (int) $db->table('water_management')->selectSum('output_liters')->where('user_id', $userId)->where('status', 'active')->get()->getRow()->output_liters;
        $wsKwh = (\App\Libraries\WinterSports::enabledFor((int) $userId) && $db->tableExists('winter_sport_facilities'))
            ? \App\Libraries\WinterSports::totalEnergyDraw($db->table('winter_sport_facilities')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray())
            : 0;
        $energyDemand = Utilities::energyDemand(
            $running,
            $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->countAllResults(),
            $db->table('player_items')->select('subtype, length_meters')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->get()->getResultArray(),
            $db->table('buildings')->select('level')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray(),
            $wsKwh,
        );
        $waterDemand = Utilities::waterDemand($running);
        $hidden = array_map('trim', explode(',', (string) getDifficultyConfig('hidden_pages', '')));
        // Imports caused by snowmaking: the extra deficit its demand adds on top of everything else
        $energyImport = in_array('energy', $hidden, true) ? 0 : Utilities::importCost('energy', max(0, $energyDemand['total'] - $energySupply) - max(0, $energyDemand['total'] - $energyDemand['snowmaking'] - $energySupply));
        $waterImport = in_array('water', $hidden, true) ? 0 : Utilities::importCost('water', max(0, $waterDemand - $waterSupply));

        $stateLabels = [
            'running' => ['success', 'Making snow'],
            'standby' => ['neutral', 'Standby: slopes at target'],
            'idle' => ['warning', 'Idle: too warm'],
            'off' => ['neutral', 'Off'],
            'broken' => ['danger', 'Broken'],
        ];
        $guns = [];
        $gunNames = [];
        foreach ($today['all_guns'] as $g) {
            $id = (int) $g['id'];
            $p = $today['guns'][$id] ?? null;
            $state = match (true) {
                (int) $g['condition_pct'] <= 0 || $g['status'] === 'broken' => 'broken',
                $g['status'] !== 'active' => 'off',
                $p !== null => 'running',
                SM::canRun($g, $baseTemp, $humidity) => 'standby',
                default => 'idle',
            };
            $crew = $crewByGun[$g['name']] ?? null;
            $max = SM::maxTemp($g);
            $fullCost = (int) round((int) $g['fuel_cost'] * $costMult);
            $share = $p['share'] ?? 0;
            $price = $prices[$g['model_key']] ?? 0;
            $upgrades = [];
            foreach (SM::UPGRADES as $key => $u) {
                $upgrades[] = [
                    'key' => $key,
                    'label' => $u['label'],
                    'icon' => $u['icon'],
                    'effect' => $key === 'nozzles' ? 'Makes snow ' . SM::NOZZLE_BONUS . '°C warmer' : 'Full output without a crew member',
                    'owned' => SM::has($g, $key),
                    'cost' => SM::upgradeCost($key, $price),
                    'costText' => currency(SM::upgradeCost($key, $price)),
                    'useless' => $key === 'nozzles' && $max === null,
                ];
            }
            $gunNames[$id] = (string) $g['name'];
            $guns[] = [
                'id' => $id,
                'name' => (string) $g['name'],
                'brand' => (string) $g['brand'],
                'capacity' => (int) $g['capacity'],
                'condition_pct' => (int) $g['condition_pct'],
                'status' => (string) $g['status'],
                'mode' => $g['status'] === 'active' ? SM::mode($g) : 'off',
                'state' => $state,
                'stateLabel' => $stateLabels[$state][1],
                'stateTone' => $stateLabels[$state][0],
                'tempLimitText' => $max === null ? 'Any temperature' : temp($max) . ' wet-bulb or colder',
                'hours' => SM::hours($g, $baseTemp, $humidity),
                'hoursRunText' => $p ? rtrim(rtrim(number_format($p['hours_run'], 1), '0'), '.') . ' h' : '0 h',
                'cmTodayText' => $p ? snow((int) round($p['cm'])) : snow(0),
                'potentialText' => snow((int) round(SM::potential($g, $baseTemp, $humidity, $crewLevels[$g['name']] ?? null))),
                'outputPct' => (int) round(SM::crewFactor($g, $crewLevels[$g['name']] ?? null) * 100),
                'coversNames' => array_values(array_map(fn($sid) => $slopeNames[$sid] ?? '?', $p['slopes'] ?? [])),
                'priority' => (int) SM::prioritySlope($g),
                'crew' => $crew ? (string) $crew['name'] : null,
                'crewLevel' => $crew ? (int) $crew['level'] : null,
                'crewReliefPct' => SM::CREW_WEAR_RELIEF,
                'operatingCostText' => currency($fullCost),
                'costTodayText' => currency((int) round($fullCost * $share)),
                'energyKwhText' => number_format((int) $g['energy_kwh']) . ' kWh',
                'waterLitersText' => number_format((int) $g['water_liters'] / 1000, 1) . ' m³',
                'upgrades' => $upgrades,
                'repairCost' => Fleet::repairCost($g, $prices),
                'repairCostText' => currency(Fleet::repairCost($g, $prices)),
                'resale' => Fleet::resaleValue($g, $prices),
                'resaleText' => currency(Fleet::resaleValue($g, $prices)),
            ];
        }

        $slopes = [];
        $thin = 0;
        $belowTarget = 0;
        foreach ($allSlopes as $sl) {
            $sid = (int) $sl['id'];
            $isOpen = $sl['status'] === 'open';
            $base = SM::base($sl, $natural);
            $target = SM::target($sl);
            $gunId = $today['covered'][$sid] ?? null;
            if ($isOpen && $base < W::THIN_BASE) $thin++;
            if ($isOpen && $target > 0 && $base < $target) $belowTarget++;
            $slopes[] = [
                'id' => $sid,
                'name' => (string) $sl['name'],
                'difficulty' => (string) ($sl['difficulty'] ?? ''),
                'status' => (string) $sl['status'],
                'condition_pct' => (int) $sl['condition_pct'],
                'made' => (int) $sl['made_snow_cm'],
                'base' => $base,
                'target' => $target,
                'madeText' => snow((int) $sl['made_snow_cm']),
                'baseText' => snow($base),
                'targetText' => $target > 0 ? snow($target) : 'No snowmaking',
                'thin' => $isOpen && $base < W::THIN_BASE,
                'gunId' => $gunId,
                'gunName' => $gunId ? ($gunNames[$gunId] ?? null) : null,
                'isPriority' => false,
                'addingText' => isset($today['made'][$sid]) ? '+' . snow((int) round($today['made'][$sid])) : null,
                'wearText' => !$isOpen ? '' : ($base < W::THIN_BASE ? '+' . G::THIN_BASE_EXTRA . '%/day' : 'Normal'),
            ];
        }
        foreach ($slopes as &$s) {
            foreach ($guns as $g) if ($g['priority'] === $s['id'] && $s['gunId'] === $g['id']) $s['isPriority'] = true;
        }
        unset($s);

        // 24 hours of wet-bulb: which hours each owned limit can make snow
        $hourly = [];
        foreach (SM::HOURLY_OFFSETS as $h => $o) {
            $wb = SM::wetBulb($baseTemp + $o, $humidity);
            $hourly[] = ['hour' => $h, 'temp' => $baseTemp + $o, 'tempText' => temp($baseTemp + $o), 'wetBulb' => round($wb, 1), 'wetBulbText' => temp((int) round($wb)),
                // Compact label for the hour strip, in the player's units: "-7°" / "19°"
                'wetBulbShort' => preg_replace('/°[CF]$/', '°', temp((int) round($wb)))];
        }
        $limits = [];
        foreach ($today['all_guns'] as $g) {
            $max = SM::maxTemp($g);
            $key = $max === null ? 'any' : (string) $max;
            $limits[$key] ??= ['label' => $max === null ? 'Any temperature' : temp($max) . ' or colder', 'hours' => array_map(fn($e) => $e > 0, SM::hourly($g, $baseTemp, $humidity)), 'guns' => []];
            $limits[$key]['guns'][] = (string) $g['name'];
        }
        if (!$limits) {
            $limits['std'] = ['label' => temp(W::SNOWMAKING_MAX_TEMP) . ' or colder', 'hours' => array_map(fn($e) => $e > 0, SM::hourly($standard, $baseTemp, $humidity)), 'guns' => []];
        }

        // The days after tonight (Weather::outlook; Weather Pro sees 14 days): hours, and how much
        // snow switched-on guns could make. Far days use the middle of the forecast range.
        $outlook = [];
        $pro = WeatherPro::active((int) $userId);
        $on = array_filter($today['all_guns'], fn($g) => $g['status'] === 'active' && (int) $g['condition_pct'] > 0);
        foreach (array_slice(W::outlook($pro), 1) as $f) {
            $day = W::seasonDayOf($f['gameDay']);
            $t = $f['tempMid'];
            $h = $f['humidity'];
            $hours = SM::hours($standard, $t, $h);
            $cm = array_sum(array_map(fn($g) => SM::potential($g, $t, $h, $crewLevels[$g['name']] ?? null), $on));
            $gunsCan = count(array_filter($on, fn($g) => SM::hours($g, $t, $h) > 0));
            $outlook[] = [
                'day' => $day,
                'icon' => weatherIcon($f['condition']),
                'tempText' => $f['exact'] ? temp($t) : '~' . temp($t),
                'humidityText' => $h . '%',
                'snowfallText' => $f['snowHigh'] > 0 ? '+' . ($f['snowLow'] === $f['snowHigh'] ? snow($f['snowHigh']) : snow($f['snowLow']) . '–' . snow($f['snowHigh'])) : null,
                'hours' => $hours,
                'hoursText' => $hours ? $hours . ' h guns' : 'Too warm',
                'gunsCanRunText' => !$hours && $gunsCan ? $gunsCan . ' of yours can run' : null,
                'canRun' => $hours > 0 || $gunsCan > 0,
                'capacityText' => $cm > 0 ? 'Up to ' . snow((int) round($cm)) : null,
            ];
        }

        $wbs = array_column($hourly, 'wetBulb');
        $gunCost = (int) round(array_sum(array_column($running, 'fuel_cost')) * $costMult);
        $standardHours = SM::hours($standard, $baseTemp, $humidity);
        $openCount = count(array_filter($allSlopes, fn($s) => $s['status'] === 'open'));

        $fin = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
        $cash = (int) ($fin['cash'] ?? 0);

        return [
            'overview' => [
                'baseTempText' => temp($baseTemp),
                'nightTempText' => temp($baseTemp - 4),
                'dayTempText' => temp($baseTemp + 4),
                'humidityText' => $humidity . '%',
                'wetBulbRangeText' => temp((int) round(min($wbs))) . ' to ' . temp((int) round(max($wbs))),
                'standardHours' => $standardHours,
                'snowBaseText' => snow($natural),
                'thinBaseThresholdText' => snow(W::THIN_BASE),
                'thinBase' => $natural < W::THIN_BASE,
                'thinSlopes' => $thin,
                'belowTarget' => $belowTarget,
                'coveredCount' => count($today['covered']),
                'openCount' => $openCount,
                'runningGunsCount' => count($running),
                'totalGunsCount' => count($guns),
                'cmTodayText' => snow((int) round($today['cm'])),
                'totalOperatingCostText' => currency($gunCost + $energyImport + $waterImport),
                'operatingCostGunsText' => currency($gunCost),
                'energyImportText' => $energyImport > 0 ? currency($energyImport) : null,
                'waterImportText' => $waterImport > 0 ? currency($waterImport) : null,
            ],
            'hourly' => $hourly,
            'limits' => array_values($limits),
            'outlook' => $outlook,
            'guns' => $guns,
            'slopes' => $slopes,
            'targets' => array_map(fn($t) => ['value' => $t, 'label' => $t > 0 ? snow($t) . ' base' : 'No snowmaking'], SM::TARGETS),
            'crewCount' => count($today['staff']),
            'utilities' => [
                'energy' => [
                    'hidden' => in_array('energy', $hidden, true),
                    'needText' => number_format((int) $energyDemand['snowmaking']),
                    'totalText' => number_format((int) $energyDemand['total']),
                    'supplyText' => number_format($energySupply),
                    'ok' => $energySupply >= $energyDemand['total'],
                ],
                'water' => [
                    'hidden' => in_array('water', $hidden, true),
                    'needText' => number_format($waterDemand),
                    'supplyText' => number_format($waterSupply),
                    'ok' => $waterSupply >= $waterDemand,
                ],
            ],
            'rules' => [
                'thinBaseText' => snow(W::THIN_BASE),
                'thinBaseExtra' => G::THIN_BASE_EXTRA,
                'crewWearRelief' => SM::CREW_WEAR_RELIEF,
                'uncrewedPct' => SM::UNCREWED_OUTPUT,
                'crewLevelBonus' => SM::CREW_LEVEL_BONUS,
                'minEfficiency' => SM::MIN_EFFICIENCY,
                'rampText' => SM::RAMP . '°C',
                'maxMadeText' => snow(SM::MAX_MADE),
                'repairSharePct' => (int) (Fleet::REPAIR_SHARE * 100),
                'resaleSharePct' => (int) (Fleet::RESALE_SHARE * 100),
            ],
            'cash' => ['raw' => $cash, 'text' => currency($cash)],
        ];
    }

    private function gun(int $id): ?array
    {
        return db_connect()->table('equipment')->where('id', $id)->where('user_id', auth()->id())->where('equipment_type', 'snowmaker')->get()->getRowArray();
    }

    public function toggle(int $id)
    {
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        return $this->setMode($eq, $eq['status'] === 'active' ? 'off' : SM::mode($eq));
    }

    /** POST mode = off | auto | max */
    public function mode(int $id)
    {
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        $mode = (string) $this->request->getPost('mode');
        if ($mode !== 'off' && !in_array($mode, SM::MODES, true)) return redirect()->back()->with('error', 'Unknown mode.');
        return $this->setMode($eq, $mode);
    }

    private function setMode(array $eq, string $mode)
    {
        if ($mode !== 'off' && ((int) $eq['condition_pct'] <= 0 || $eq['status'] === 'broken')) {
            return redirect()->back()->with('error', $eq['name'] . ' is broken: repair it first.');
        }
        $row = $mode === 'off' ? ['status' => 'off'] : ['status' => 'active', 'snow_mode' => $mode];
        db_connect()->table('equipment')->where('id', $eq['id'])->update($row + ['updated_at' => date('Y-m-d H:i:s')]);
        $msg = $eq['name'] . match ($mode) { 'off' => ' switched off', 'auto' => ' set to auto: makes snow until its slopes reach target', 'max' => ' set to max: makes snow every cold hour' };
        log_activity(auth()->id(), 'Snowmaking', $msg, 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $msg . '.');
    }

    public function toggleAll()
    {
        $userId = auth()->id();
        $on = $this->request->getPost('action') === 'on';
        $q = db_connect()->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker');
        if ($on) {
            $q->where('condition_pct >', 0)->whereIn('status', ['off', 'active'])->update(['status' => 'active', 'updated_at' => date('Y-m-d H:i:s')]);
        } else {
            $q->where('status', 'active')->update(['status' => 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        }
        log_activity($userId, 'Snowmaking', $on ? 'All snow guns switched on' : 'All snow guns switched off', 'fa-solid fa-power-off');
        return redirect()->to('/snowmaking')->with('success', $on ? 'All working snow guns switched on.' : 'All snow guns switched off.');
    }

    /** POST slope_id (or 'all'), target_cm: the base snow guns build the slope up to */
    public function target()
    {
        $userId = auth()->id();
        $target = (int) $this->request->getPost('target_cm');
        if (!in_array($target, SM::TARGETS, true)) return redirect()->back()->with('error', 'Pick one of the target depths.');
        $slopeId = (string) $this->request->getPost('slope_id');
        $q = db_connect()->table('player_items')->where('user_id', $userId)->where('item_type', 'slope');
        if ($slopeId !== 'all') {
            $slope = (clone $q)->where('id', (int) $slopeId)->get()->getRowArray();
            if (!$slope) return redirect()->back()->with('error', 'Slope not found.');
            $q->where('id', (int) $slope['id']);
        }
        $q->update(['snow_target_cm' => $target]);
        $what = $slopeId === 'all' ? 'All slopes' : $slope['name'];
        $msg = $target > 0 ? $what . ': target base ' . snow($target) : $what . ': no snowmaking';
        log_activity($userId, 'Snowmaking', $msg, 'fa-solid fa-ruler-vertical');
        return redirect()->to('/snowmaking')->with('success', $msg . '.');
    }

    /** POST upgrade = nozzles | automation */
    public function upgrade(int $id)
    {
        $userId = auth()->id();
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        $key = (string) $this->request->getPost('upgrade');
        if (!isset(SM::UPGRADES[$key])) return redirect()->back()->with('error', 'Unknown upgrade.');
        if (SM::has($eq, $key)) return redirect()->back()->with('error', $eq['name'] . ' already has ' . strtolower(SM::UPGRADES[$key]['label']) . '.');
        if ($key === 'nozzles' && SM::maxTemp($eq) === null) return redirect()->back()->with('error', $eq['name'] . ' already works at any temperature.');

        $cost = SM::upgradeCost($key, Fleet::prices()[$eq['model_key']] ?? 0);
        $db = db_connect();
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cost)
            ->set('cash', 'cash - ' . $cost, false)->set('total_expenses', 'total_expenses + ' . $cost, false)->update();
        if ($cost > 0 && $db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash: ' . strtolower(SM::UPGRADES[$key]['label']) . ' for ' . $eq['name'] . ' costs ' . currency($cost) . '.');
        }
        $db->table('equipment')->where('id', $eq['id'])->update(['upgrades' => implode(',', array_merge(SM::upgrades($eq), [$key])), 'updated_at' => date('Y-m-d H:i:s')]);
        if ($cost > 0) {
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Equipment', 'description' => SM::UPGRADES[$key]['label'] . ' for ' . $eq['name'],
                'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        $msg = $eq['name'] . ': ' . strtolower(SM::UPGRADES[$key]['label']) . ' fitted for ' . currency($cost);
        log_activity($userId, 'Snowmaking', $msg, 'fa-solid ' . SM::UPGRADES[$key]['icon']);
        return redirect()->to('/snowmaking')->with('success', $msg . '.');
    }

    public function repair(int $id)
    {
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        if ((int) $eq['condition_pct'] >= 100) return redirect()->back()->with('error', $eq['name'] . ' is already in perfect condition.');

        $cost = Fleet::repair(auth()->id(), $eq);
        if ($cost === null) return redirect()->back()->with('error', 'Not enough cash: repairing ' . $eq['name'] . ' costs ' . currency(Fleet::repairCost($eq)) . '.');
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' repaired for ' . currency($cost) . '.');
    }

    public function assignCannon()
    {
        $userId = auth()->id();
        $db = db_connect();
        $eq = $this->gun((int) $this->request->getPost('cannon_id'));
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');

        $slopeId = (int) $this->request->getPost('slope_id');
        $slope = $slopeId ? $db->table('player_items')->where('id', $slopeId)->where('user_id', $userId)->where('item_type', 'slope')->get()->getRowArray() : null;
        if ($slopeId && !$slope) return redirect()->back()->with('error', 'Slope not found.');

        $db->table('equipment')->where('id', $eq['id'])->update(['assigned_to' => $slope ? 'slope_' . $slope['id'] : null, 'updated_at' => date('Y-m-d H:i:s')]);
        $msg = $slope ? $eq['name'] . ' now covers ' . $slope['name'] . ' first' : $eq['name'] . ' now covers the slopes furthest under target first';
        log_activity($userId, 'Snowmaking', $msg, 'fa-solid fa-snowflake');
        return redirect()->to('/snowmaking')->with('success', $msg . '.');
    }

    public function sell(int $id)
    {
        $eq = $this->gun($id);
        if (!$eq) return redirect()->back()->with('error', 'Snow gun not found.');
        $refund = Fleet::sell(auth()->id(), $eq);
        return redirect()->to('/snowmaking')->with('success', $eq['name'] . ' sold for ' . currency($refund) . '.');
    }
}
