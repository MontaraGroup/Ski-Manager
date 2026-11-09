<?php

namespace App\Controllers;

use App\Libraries\Fleet;
use App\Libraries\Grooming;
use App\Libraries\Snowmaking;

/**
 * /equipment: the shop and your fleet of groomers and snow guns. The numbers shown are the ones the
 * nightly tick uses: a groomer only runs (and burns fuel) with an operator, a snow gun only on days
 * cold enough for its model (App\Libraries\Grooming, App\Libraries\Snowmaking).
 */
class Equipment extends BaseController
{
    /** Alliance Bulk Procurement discount for this player, in percent */
    private function discountPct(int $userId): int
    {
        $db = db_connect();
        if (!$db->tableExists('alliance_members')) return 0;
        $member = $db->table('alliance_members')->where('user_id', $userId)->get()->getRowArray();
        if (!$member) return 0;
        $perk = $db->table('alliance_unlocked_perks')->where('alliance_id', $member['alliance_id'])->where('perk_key', 'bulk_procurement')->get()->getRowArray();
        return $perk ? match ((int) $perk['perk_level']) { 2 => 15, 3 => 20, default => 10 } : 0;
    }

    public function index(): string
    {
        return view('equipment/index', ['page' => $this->pageData()]);
    }

    /** What the page shows; the mobile API sends the same data (Api\V1\App) */
    public function pageData(): array
    {
        helper('weather');
        $userId = auth()->id();
        $db = db_connect();
        $discount = $this->discountPct($userId);
        $costMult = ((int) getDifficultyConfig('cost_multiplier', '100')) / 100;
        $baseTemp = (int) (getCurrentWeather()['base_temp'] ?? 0);

        $equipment = $db->table('equipment')->where('user_id', $userId)->orderBy('equipment_type')->orderBy('id')->get()->getResultArray();
        $staff = $db->table('staff')->where('user_id', $userId)->whereIn('role', ['groomer', 'snowmaker'])->where('status', 'active')->get()->getResultArray();
        $operators = array_values(array_filter($staff, fn($s) => $s['role'] === 'groomer'));
        $groomers = array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer');
        $guns = array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker');
        $fleet = Grooming::fleet($groomers, $operators);
        $crewedIds = array_flip(array_map(fn($m) => (int) $m['id'], $fleet['machines']));
        $crewByName = array_column(array_filter($staff, fn($s) => !empty($s['assigned_to'])), 'name', 'assigned_to');

        $prices = Fleet::prices();
        foreach ($equipment as &$e) {
            $e['repair_cost'] = Fleet::repairCost($e, $prices);
            $e['resale'] = Fleet::resaleValue($e, $prices);
            $e['crew'] = $crewByName[$e['name']] ?? null;
            $broken = $e['status'] === 'broken' || (int) $e['condition_pct'] <= 0;
            if ($e['equipment_type'] === 'groomer') {
                $e['state'] = $broken ? 'broken' : ($e['status'] !== 'active' ? 'off' : (isset($crewedIds[(int) $e['id']]) ? 'running' : 'no_operator'));
            } else {
                $e['state'] = $broken ? 'broken' : ($e['status'] !== 'active' ? 'off' : (Snowmaking::runs($e, $baseTemp) ? 'running' : 'idle'));
            }
            $e['daily_cost'] = $e['state'] === 'running' ? (int) round((int) $e['fuel_cost'] * $costMult) : 0;
        }
        unset($e);

        $owned = array_count_values(array_column($equipment, 'model_key'));
        $catalog = ['groomer' => [], 'snowmaker' => []];
        foreach ($db->table('equipment_catalog')->orderBy('sort_order')->orderBy('cost')->get()->getResultArray() as $c) {
            $c['price'] = (int) round((int) $c['cost'] * (1 - $discount / 100));
            $c['owned'] = $owned[$c['model_key']] ?? 0;
            $c['running_cost'] = (int) round((int) $c['fuel_cost'] * $costMult);
            $c['max_temp'] = $c['equipment_type'] === 'snowmaker' ? Snowmaking::maxTemp($c) : null;
            $catalog[$c['equipment_type']][] = $c;
        }
        foreach ($catalog as &$list) usort($list, fn($a, $b) => $a['price'] <=> $b['price']);
        unset($list);

        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->whereIn('status', ['open', 'broken'])->countAllResults();
        $runningGuns = array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker' && $e['state'] === 'running');
        $cash = (int) ($db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow()->cash ?? 0);

        $stateLabels = [
            'running' => ['tone' => 'success', 'label' => 'Running'],
            'no_operator' => ['tone' => 'warning', 'label' => 'No operator'],
            'idle' => ['tone' => 'warning', 'label' => 'Idle: too warm'],
            'off' => ['tone' => 'neutral', 'label' => 'Off'],
            'broken' => ['tone' => 'danger', 'label' => 'Broken'],
        ];

        $formattedEquipment = [];
        foreach ($equipment as $e) {
            $formattedEquipment[] = [
                'id' => (int) $e['id'],
                'name' => (string) $e['name'],
                'brand' => (string) $e['brand'],
                'equipment_type' => (string) $e['equipment_type'],
                'capacity' => (int) $e['capacity'],
                'condition_pct' => (int) $e['condition_pct'],
                'status' => (string) $e['status'],
                'state' => (string) $e['state'],
                'stateLabel' => $stateLabels[$e['state']]['label'] ?? ucfirst($e['state']),
                'stateTone' => $stateLabels[$e['state']]['tone'] ?? 'neutral',
                'crew' => $e['crew'] ? (string) $e['crew'] : null,
                'fuelCostText' => currency((int) $e['fuel_cost']),
                'repairCost' => (int) $e['repair_cost'],
                'repairCostText' => currency((int) $e['repair_cost']),
                'resale' => (int) $e['resale'],
                'resaleText' => currency((int) $e['resale']),
                'dailyCostText' => currency((int) $e['daily_cost']),
            ];
        }

        $formattedCatalog = ['groomer' => [], 'snowmaker' => []];
        foreach ($catalog as $type => $items) {
            foreach ($items as $c) {
                $formattedCatalog[$type][] = [
                    'model_key' => (string) $c['model_key'],
                    'name' => (string) $c['name'],
                    'brand' => (string) $c['brand'],
                    'description' => (string) $c['description'],
                    'equipment_type' => (string) $c['equipment_type'],
                    'capacity' => (int) $c['capacity'],
                    'owned' => (int) $c['owned'],
                    'costText' => currency((int) $c['cost']),
                    'price' => (int) $c['price'],
                    'priceText' => currency((int) $c['price']),
                    'runningCostText' => currency((int) $c['running_cost']),
                    'maxTempText' => $c['max_temp'] === null ? 'Any temperature' : temp($c['max_temp']) . ' or colder',
                    'energyKwhText' => number_format((int) $c['energy_kwh']) . ' kWh',
                    'waterLitersText' => number_format((int) $c['water_liters'] / 1000, 1) . ' m³',
                    'costPerSlopeText' => currency((int) round($c['price'] / max(1, (int) $c['capacity']))),
                ];
            }
        }

        $page = [
            'cash' => $cash,
            'cashText' => currency($cash),
            'cashNegative' => $cash < 0,
            'discount' => $discount,
            'stats' => [
                'slopes' => $slopes,
                'groomCapacity' => $fleet['capacity'],
                'groomers' => count($groomers),
                'operators' => count($operators),
                'idleGroomers' => $fleet['idle_machines'],
                'guns' => count($guns),
                'gunCapacity' => count(Snowmaking::coverage($db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->get()->getResultArray(), $guns, $baseTemp)),
                'runningGuns' => count($runningGuns),
                'dailyCostText' => currency(array_sum(array_column($equipment, 'daily_cost'))),
                'fleetValueText' => currency(array_sum(array_column($equipment, 'resale'))),
                'equipmentCount' => count($equipment),
            ],
            'equipment' => $formattedEquipment,
            'catalog' => $formattedCatalog,
            'rules' => [
                'repairSharePct' => (int) (Fleet::REPAIR_SHARE * 100),
                'resaleSharePct' => (int) (Fleet::RESALE_SHARE * 100),
            ],
        ];

        return $page;
    }

    public function buy()
    {
        $userId = auth()->id();
        $db = db_connect();
        $item = $db->table('equipment_catalog')->where('model_key', (string) $this->request->getPost('model'))->get()->getRowArray();
        if (!$item) return redirect()->to('/equipment')->with('error', 'Invalid equipment.');

        $cost = (int) round((int) $item['cost'] * (1 - $this->discountPct($userId) / 100));

        // Names identify a machine for crew assignment, so never reuse one that's still in the fleet
        $taken = array_flip(array_column($db->table('equipment')->select('name')->where('user_id', $userId)->where('model_key', $item['model_key'])->get()->getResultArray(), 'name'));
        for ($n = 1; isset($taken[$item['name'] . ' #' . $n]); $n++);
        $name = $item['name'] . ' #' . $n;

        $now = date('Y-m-d H:i:s');
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cost)
            ->set('cash', 'cash - ' . $cost, false)->set('total_expenses', 'total_expenses + ' . $cost, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/equipment')->with('error', 'Not enough cash: ' . $item['name'] . ' costs ' . currency($cost) . '.');
        }
        $db->table('equipment')->insert([
            'user_id' => $userId, 'equipment_type' => $item['equipment_type'], 'model_key' => $item['model_key'], 'name' => $name,
            'brand' => $item['brand'], 'capacity' => $item['capacity'], 'fuel_cost' => $item['fuel_cost'],
            'output_per_day' => $item['output_per_day'], 'energy_kwh' => $item['energy_kwh'], 'water_liters' => $item['water_liters'],
            'condition_pct' => 100, 'status' => 'off', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Equipment', 'description' => 'Bought ' . $name,
            'amount' => $cost, 'type' => 'expense', 'created_at' => $now,
        ]);
        $db->transComplete();

        log_activity($userId, 'Equipment', 'Bought ' . $name . ' for ' . currency($cost), $item['icon'] ?: 'fa-solid fa-cart-plus');
        $next = $item['equipment_type'] === 'groomer' ? 'Switch it on and make sure you have an operator for it.' : 'Switch it on to start covering slopes.';
        return redirect()->to('/equipment')->with('success', $name . ' bought for ' . currency($cost) . '. ' . $next);
    }

    public function toggle(int $id)
    {
        $eq = db_connect()->table('equipment')->where('id', $id)->where('user_id', auth()->id())->get()->getRowArray();
        if (!$eq) return redirect()->to('/equipment')->with('error', 'Not found.');
        if ((int) $eq['condition_pct'] <= 0 || $eq['status'] === 'broken') return redirect()->to('/equipment')->with('error', $eq['name'] . ' is broken: repair it first.');

        $on = $eq['status'] !== 'active';
        db_connect()->table('equipment')->where('id', $id)->update(['status' => $on ? 'active' : 'off', 'updated_at' => date('Y-m-d H:i:s')]);
        log_activity(auth()->id(), 'Equipment', $eq['name'] . ($on ? ' switched on' : ' switched off'), 'fa-solid fa-power-off');
        return redirect()->to('/equipment')->with('success', $eq['name'] . ($on ? ' switched on.' : ' switched off.'));
    }

    public function repair(int $id)
    {
        $eq = db_connect()->table('equipment')->where('id', $id)->where('user_id', auth()->id())->get()->getRowArray();
        if (!$eq) return redirect()->to('/equipment')->with('error', 'Not found.');
        if ((int) $eq['condition_pct'] >= 100) return redirect()->to('/equipment')->with('error', $eq['name'] . ' is already in perfect condition.');

        $cost = Fleet::repair(auth()->id(), $eq);
        if ($cost === null) return redirect()->to('/equipment')->with('error', 'Not enough cash: repairing ' . $eq['name'] . ' costs ' . currency(Fleet::repairCost($eq)) . '.');
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' repaired for ' . currency($cost) . '.');
    }

    public function sell(int $id)
    {
        $eq = db_connect()->table('equipment')->where('id', $id)->where('user_id', auth()->id())->get()->getRowArray();
        if (!$eq) return redirect()->to('/equipment')->with('error', 'Not found.');
        $refund = Fleet::sell(auth()->id(), $eq);
        return redirect()->to('/equipment')->with('success', $eq['name'] . ' sold for ' . currency($refund) . '.');
    }
}
