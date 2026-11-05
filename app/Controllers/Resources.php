<?php

namespace App\Controllers;

use App\Libraries\Utilities;

/**
 * /energy and /water: build, switch and repair sources. The numbers shown are the ones the
 * nightly tick bills (App\Libraries\Utilities): upkeep per active source, plus the tariff on
 * any demand the sources don't cover.
 */
class Resources extends BaseController
{
    private const CATEGORIES = [
        'energy' => ['table' => 'energy_management', 'output' => 'output_kwh', 'capacity' => 'capacity_kwh', 'unit' => 'kWh'],
        'water' => ['table' => 'water_management', 'output' => 'output_liters', 'capacity' => 'capacity_liters', 'unit' => 'L'],
    ];

    public function energy(): string
    {
        return $this->page('energy');
    }

    public function water(): string
    {
        return $this->page('water');
    }

    private function page(string $category): string
    {
        if (isPageHidden($category)) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $userId = auth()->id();
        $db = db_connect();
        $meta = self::CATEGORIES[$category];
        $config = Utilities::config($category);
        $costMult = ((int) getDifficultyConfig('cost_multiplier', '100')) / 100;
        $sources = $db->table($meta['table'])->where('user_id', $userId)->orderBy('id')->get()->getResultArray();

        $snowmakers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->where('status', 'active')->get()->getResultArray();
        if ($category === 'energy') {
            $demand = Utilities::energyDemand(
                $snowmakers,
                $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->countAllResults(),
                $db->table('player_items')->select('subtype, length_meters')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->get()->getResultArray(),
                $db->table('buildings')->select('level')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray(),
            );
        } else {
            $demand = ['snowmaking' => Utilities::waterDemand($snowmakers)];
            $demand['total'] = $demand['snowmaking'];
        }

        $output = 0; $upkeep = 0;
        foreach ($sources as $s) {
            if ($s['status'] !== 'active') continue;
            $output += (int) $s[$meta['output']];
            $upkeep += (int) round(($config[$s['source_type']]['upkeep'] ?? (int) $s['cost_per_day']) * $costMult);
        }
        $deficit = max(0, $demand['total'] - $output);

        // Each active source's worth against importing, counting only output that's actually used
        $remaining = $demand['total'];
        foreach ($sources as &$s) {
            $cfg = $config[$s['source_type']] ?? ['upkeep' => (int) $s['cost_per_day'], 'cost' => 0, 'icon' => '', 'label' => $s['source_type']];
            $s['upkeep'] = (int) round($cfg['upkeep'] * $costMult);
            $s['repair_cost'] = $this->repairCost($cfg, (float) $s['condition_pct']);
            if ($s['status'] === 'active') {
                $used = min((int) $s[$meta['output']], max(0, $remaining));
                $remaining -= $used;
                $s['used_output'] = $used;
                $s['value'] = Utilities::dailyValue($category, $cfg, $used, $costMult);
            }
        }
        unset($s);

        $isEnergy = $category === 'energy';
        $fmt = fn(int $n) => number_format($n) . ' ' . $meta['unit'];
        $tariff = $isEnergy ? Utilities::GRID_TARIFF : Utilities::WATER_TARIFF;
        $tariffLabel = $isEnergy
            ? (isImperial() ? '$' . number_format($tariff, 2) : number_format($tariff, 2, ',', '') . ' €') . ' per kWh'
            : currency((int) round($tariff * 1000)) . ' per 1,000 L';

        $labels = [
            'snowmaking' => ['label' => 'Snowmaking', 'icon' => 'snowflake'],
            'night_skiing' => ['label' => 'Night skiing', 'icon' => 'moon'],
            'lifts' => ['label' => 'Lifts', 'icon' => 'cable-car'],
            'buildings' => ['label' => 'Buildings', 'icon' => 'building'],
        ];

        $demandParts = [];
        foreach ($demand as $k => $val) {
            if ($k === 'total') continue;
            $cleanIcon = $labels[$k]['icon'] ?? 'bolt';
            $demandParts[] = [
                'key' => $k,
                'label' => $labels[$k]['label'] ?? ucfirst(str_replace('_', ' ', $k)),
                'icon' => $cleanIcon,
                'value' => (int) $val,
                'valueFormatted' => $fmt((int) $val),
            ];
        }

        $cash = (int) $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow('cash');
        $importCost = Utilities::importCost($category, $deficit);

        $formattedSources = array_map(function($s) use ($config, $costMult, $meta, $fmt, $cash) {
            $cfg = $config[$s['source_type']] ?? ['upkeep' => (int) $s['cost_per_day'], 'cost' => 0, 'icon' => '', 'label' => $s['source_type']];
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $cfg['icon'] ?? 'bolt');
            $repairCost = (int) ($s['repair_cost'] ?? 0);

            return [
                'id' => (int) $s['id'],
                'name' => $s['name'],
                'source_type' => $s['source_type'],
                'typeLabel' => $cfg['label'] ?? ucfirst($s['source_type']),
                'icon' => $cleanIcon,
                'status' => $s['status'],
                'condition_pct' => (int) round((float) $s['condition_pct']),
                'output' => (int) $s[$meta['output']],
                'outputFormatted' => $fmt((int) $s[$meta['output']]),
                'used_output' => (int) ($s['used_output'] ?? 0),
                'upkeep' => (int) $s['upkeep'],
                'upkeepFormatted' => currency((int) $s['upkeep']),
                'repair_cost' => $repairCost,
                'repair_cost_formatted' => currency($repairCost),
                'canAffordRepair' => $cash >= $repairCost,
                'build_days_left' => (int) ($s['build_days_left'] ?? 0),
                'dailyValueFormatted' => isset($s['value']) ? currency((int) $s['value']) : null,
            ];
        }, $sources);

        $ownedCounts = array_count_values(array_column($sources, 'source_type'));
        $availableSources = [];
        foreach ($config as $typeKey => $cfg) {
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $cfg['icon'] ?? 'bolt');
            $limit = Utilities::LIMIT[$typeKey] ?? null;
            $owned = $ownedCounts[$typeKey] ?? 0;
            $upkeepVal = (int) round($cfg['upkeep'] * $costMult);
            $costVal = (int) $cfg['cost'];

            $availableSources[] = [
                'type' => $typeKey,
                'label' => $cfg['label'],
                'icon' => $cleanIcon,
                'cost' => $costVal,
                'costFormatted' => currency($costVal),
                'upkeep' => $upkeepVal,
                'upkeepFormatted' => currency($upkeepVal),
                'output' => (int) $cfg['output'],
                'outputFormatted' => $fmt((int) $cfg['output']),
                'build_days' => (int) $cfg['build_days'],
                'owned' => $owned,
                'limit' => $limit,
                'canAfford' => $cash >= $costVal,
                'atLimit' => $limit !== null && $owned >= $limit,
            ];
        }

        $page = [
            'category' => $category,
            'unit' => $meta['unit'],
            'tariffLabel' => $tariffLabel,
            'cash' => $cash,
            'cashFormatted' => currency($cash),
            'demand' => [
                'total' => (int) $demand['total'],
                'totalFormatted' => $fmt((int) $demand['total']),
                'parts' => $demandParts,
            ],
            'output' => $output,
            'outputFormatted' => $fmt($output),
            'deficit' => $deficit,
            'deficitFormatted' => $fmt($deficit),
            'upkeep' => $upkeep,
            'upkeepFormatted' => currency($upkeep),
            'importCost' => $importCost,
            'importCostFormatted' => currency($importCost),
            'totalDailyCost' => $importCost + $upkeep,
            'totalDailyCostFormatted' => currency($importCost + $upkeep),
            'sources' => $formattedSources,
            'availableSources' => $availableSources,
        ];

        return view('resources/page', ['page' => $page]);
    }

    public function buildEnergy()
    {
        return $this->build('energy');
    }

    public function buildWater()
    {
        return $this->build('water');
    }

    private function build(string $category)
    {
        if (isPageHidden($category)) return redirect()->to('/dashboard');
        $userId = auth()->id();
        $meta = self::CATEGORIES[$category];
        $type = (string) $this->request->getPost('source_type');
        $cfg = Utilities::config($category)[$type] ?? null;
        if (!$cfg) return redirect()->to('/' . $category)->with('error', 'Invalid source type.');
        $name = mb_substr(trim(strip_tags((string) $this->request->getPost('name'))), 0, 60) ?: $cfg['label'];

        $db = db_connect();
        $limit = Utilities::LIMIT[$type] ?? null;
        if ($limit !== null && $db->table($meta['table'])->where('user_id', $userId)->where('source_type', $type)->countAllResults() >= $limit) {
            return redirect()->to('/' . $category)->with('error', 'A resort can only have ' . $limit . ' ' . $cfg['label'] . '.');
        }
        $db->transStart();
        // Deduct only if the cash is there, in one statement, so a double submit can't overspend
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cfg['cost'])->set('cash', 'cash - ' . $cfg['cost'], false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/' . $category)->with('error', 'Not enough cash: ' . $cfg['label'] . ' costs ' . currency($cfg['cost']) . '.');
        }
        $db->table($meta['table'])->insert([
            'user_id' => $userId, 'source_type' => $type, 'name' => $name,
            $meta['capacity'] => $cfg['capacity'], $meta['output'] => $cfg['output'],
            'cost_per_day' => $cfg['upkeep'], 'status' => 'under_construction', 'build_days_left' => $cfg['build_days'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => ucfirst($category),
            'description' => 'Built ' . $name, 'amount' => $cfg['cost'], 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, $category . '_build', 'Started building ' . $name . ' for ' . currency($cfg['cost']));
        return redirect()->to('/' . $category)->with('success', $name . ' is under construction: ready in ' . $cfg['build_days'] . ' day' . ($cfg['build_days'] === 1 ? '' : 's') . '.');
    }

    public function toggleEnergy(int $id)
    {
        return $this->toggle('energy', $id);
    }

    public function toggleWater(int $id)
    {
        return $this->toggle('water', $id);
    }

    private function toggle(string $category, int $id)
    {
        $userId = auth()->id();
        $table = self::CATEGORIES[$category]['table'];
        $db = db_connect();
        $source = $db->table($table)->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        // Only working sources switch; a broken one has to be repaired first
        if (!$source || !in_array($source['status'], ['active', 'off'], true)) {
            return redirect()->to('/' . $category)->with('error', $source && $source['status'] === 'broken' ? 'It\'s broken: repair it first.' : 'That source can\'t be switched yet.');
        }
        $new = $source['status'] === 'active' ? 'off' : 'active';
        $db->table($table)->where('id', $id)->update(['status' => $new, 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity($userId, $category . '_toggle', 'Switched ' . $source['name'] . ' ' . ($new === 'active' ? 'on' : 'off'));
        return redirect()->to('/' . $category)->with('success', $source['name'] . ' switched ' . ($new === 'active' ? 'on' : 'off') . '.');
    }

    public function repairEnergy(int $id)
    {
        return $this->repair('energy', $id);
    }

    public function repairWater(int $id)
    {
        return $this->repair('water', $id);
    }

    /** 10% of the build cost at 0% condition, less as condition is higher */
    private function repairCost(array $cfg, float $conditionPct): int
    {
        return (int) round(($cfg['cost'] ?? 0) * 0.1 * (1 - $conditionPct / 100));
    }

    private function repair(string $category, int $id)
    {
        $userId = auth()->id();
        $table = self::CATEGORIES[$category]['table'];
        $db = db_connect();
        $source = $db->table($table)->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$source || $source['status'] === 'under_construction' || (float) $source['condition_pct'] >= 100) {
            return redirect()->to('/' . $category)->with('error', 'Nothing to repair.');
        }
        $cost = $this->repairCost(Utilities::config($category)[$source['source_type']] ?? [], (float) $source['condition_pct']);

        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cost)->set('cash', 'cash - ' . $cost, false)->set('total_expenses', 'total_expenses + ' . $cost, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/' . $category)->with('error', 'Not enough cash: the repair costs ' . currency($cost) . '.');
        }
        // A repaired broken source comes back on; otherwise it keeps its on/off state
        $db->table($table)->where('id', $id)->update([
            'condition_pct' => 100, 'status' => $source['status'] === 'broken' ? 'active' : $source['status'], 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => ucfirst($category),
            'description' => 'Repaired ' . $source['name'], 'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, $category . '_repair', 'Repaired ' . $source['name'] . ' for ' . currency($cost));
        return redirect()->to('/' . $category)->with('success', $source['name'] . ' repaired for ' . currency($cost) . '.');
    }
}
