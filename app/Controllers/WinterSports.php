<?php

namespace App\Controllers;

use App\Libraries\Ledger;
use App\Libraries\WinterSports as Rules;

/**
 * /winter-sports: build, price, upgrade, repair, toggle and demolish non-skiing winter sport
 * facilities (ice rink, ice hockey venue, ski jump ramp, bobsleigh track).
 */
class WinterSports extends BaseController
{
    public function index(): string
    {
        return view('winter_sports/index', ['page' => $this->pageData()]);
    }

    protected function pageData(): array
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $finance = $db->table('player_finances')->select('cash, daily_visitors')->where('user_id', $userId)->get()->getRowArray() ?? [];
        $cash = (int) ($finance['cash'] ?? 0);
        $visitors = (int) ($finance['daily_visitors'] ?? 0);
        $isWinter = function_exists('isWinterDay') ? isWinterDay() : true;

        $rows = $db->tableExists('winter_sport_facilities')
            ? $db->table('winter_sport_facilities')->where('user_id', $userId)->orderBy('id', 'ASC')->get()->getResultArray()
            : [];

        $countsByType = [];
        $totalRev = 0;
        $totalUpkeep = 0;
        $totalGuests = 0;
        $openCount = 0;

        $facilities = array_map(function (array $f) use (&$countsByType, &$totalRev, &$totalUpkeep, &$totalGuests, &$openCount, $visitors, $isWinter) {
            $type = (string) $f['facility_type'];
            $countsByType[$type] = ($countsByType[$type] ?? 0) + 1;
            $cfg = Rules::CATALOG[$type] ?? Rules::CATALOG['ice_rink'];
            $level = max(1, min(Rules::MAX_LEVEL, (int) $f['level']));
            $cond = (int) $f['condition_pct'];
            $settle = Rules::settleDay($f, $visitors, $isWinter);

            if ($f['status'] === 'open') {
                $openCount++;
                $totalRev += $settle['revenue'];
                $totalUpkeep += $settle['upkeep'];
                $totalGuests += $settle['guests'];
            }

            $nextLevel = $level < Rules::MAX_LEVEL ? $level + 1 : null;
            $upgradeCost = $nextLevel ? Rules::upgradeCost($type, $level) : 0;
            $repairCost = Rules::repairCost($type, $cond);
            $salvage = Rules::salvageValue($type, $level);

            return [
                'id' => (int) $f['id'],
                'facility_type' => $type,
                'name' => (string) $f['name'],
                'level' => $level,
                'levelName' => $cfg['levels'][$level] ?? $cfg['name'],
                'maxLevel' => Rules::MAX_LEVEL,
                'status' => (string) $f['status'],
                'condition_pct' => $cond,
                'ticket_price' => (int) $f['ticket_price'],
                'min_price' => (int) $cfg['min_price'],
                'max_price' => (int) $cfg['max_price'],
                'capacity' => Rules::capacityFor($type, $level),
                'estGuests' => $settle['guests'],
                'estRevenue' => $settle['revenue'],
                'estRevenueText' => currency($settle['revenue']),
                'upkeep' => Rules::upkeepFor($type, $level),
                'upkeepText' => currency(Rules::upkeepFor($type, $level)),
                'totalRevenue' => (int) $f['total_revenue'],
                'totalRevenueText' => currency((int) $f['total_revenue']),
                'icon' => $cfg['icon'],
                'eventName' => $cfg['event_name'],
                'nextLevel' => $nextLevel,
                'nextLevelName' => $nextLevel ? ($cfg['levels'][$nextLevel] ?? null) : null,
                'upgradeCost' => $upgradeCost,
                'upgradeCostText' => currency($upgradeCost),
                'repairCost' => $repairCost,
                'repairCostText' => currency($repairCost),
                'salvage' => $salvage,
                'salvageText' => currency($salvage),
            ];
        }, $rows);

        $catalog = [];
        foreach (Rules::CATALOG as $type => $cfg) {
            $built = $countsByType[$type] ?? 0;
            $catalog[] = [
                'type' => $type,
                'name' => $cfg['name'],
                'icon' => $cfg['icon'],
                'desc' => $cfg['desc'],
                'cost' => (int) $cfg['build_cost'],
                'costText' => currency((int) $cfg['build_cost']),
                'upkeep' => (int) $cfg['upkeep'],
                'upkeepText' => currency((int) $cfg['upkeep']),
                'capacity' => (int) $cfg['capacity'],
                'defaultPrice' => (int) $cfg['default_price'],
                'defaultPriceText' => currency((int) $cfg['default_price']),
                'visitorDraw' => (int) $cfg['spectator_draw'],
                'eventName' => $cfg['event_name'],
                'built' => $built,
                'maxAllowed' => Rules::MAX_PER_TYPE,
                'canBuild' => $built < Rules::MAX_PER_TYPE && $cash >= (int) $cfg['build_cost'],
            ];
        }

        return [
            'cash' => $cash,
            'cashText' => currency($cash),
            'stats' => [
                'openCount' => $openCount,
                'totalCount' => count($facilities),
                'estRevenueText' => currency($totalRev),
                'totalUpkeepText' => currency($totalUpkeep),
                'estNetText' => currency($totalRev - $totalUpkeep),
                'estGuests' => $totalGuests,
            ],
            'facilities' => $facilities,
            'catalog' => $catalog,
        ];
    }

    public function build()
    {
        $userId = (int) auth()->id();
        $type = (string) $this->request->getPost('facility_type');
        $cfg = Rules::CATALOG[$type] ?? null;
        if (!$cfg) {
            return redirect()->back()->with('error', 'Unknown winter sport facility.');
        }

        $db = db_connect();
        $existing = $db->table('winter_sport_facilities')->where('user_id', $userId)->where('facility_type', $type)->countAllResults();
        if ($existing >= Rules::MAX_PER_TYPE) {
            return redirect()->back()->with('error', 'You can build at most ' . Rules::MAX_PER_TYPE . ' ' . $cfg['name'] . ' venues.');
        }

        $cost = (int) $cfg['build_cost'];
        $name = $existing === 0 ? $cfg['name'] : $cfg['name'] . ' #' . ($existing + 1);
        $now = date('Y-m-d H:i:s');

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Built ' . $name)) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash to build ' . $name . ' (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->insert([
            'user_id' => $userId,
            'facility_type' => $type,
            'name' => $name,
            'level' => 1,
            'status' => 'open',
            'condition_pct' => 100,
            'ticket_price' => (int) $cfg['default_price'],
            'daily_revenue' => 0,
            'daily_upkeep' => (int) $cfg['upkeep'],
            'total_revenue' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Built ' . $name . ' for ' . currency($cost), 'fa-solid ' . $cfg['icon']);
        return redirect()->to('/winter-sports')->with('success', $name . ' built and open for guests.');
    }

    public function upgrade(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $level = (int) $f['level'];
        if ($level >= Rules::MAX_LEVEL) {
            return redirect()->back()->with('error', $f['name'] . ' is already at max level.');
        }

        $type = (string) $f['facility_type'];
        $cost = Rules::upgradeCost($type, $level);
        $nextLevel = $level + 1;

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Upgraded ' . $f['name'] . ' to Level ' . $nextLevel)) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash to upgrade (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'level' => $nextLevel,
            'daily_upkeep' => Rules::upkeepFor($type, $nextLevel),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Upgraded ' . $f['name'] . ' to Level ' . $nextLevel . ' (' . currency($cost) . ')', 'fa-solid fa-arrow-up');
        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' upgraded to Level ' . $nextLevel . '.');
    }

    public function price(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $cfg = Rules::CATALOG[$f['facility_type']] ?? null;
        if (!$cfg) return redirect()->back()->with('error', 'Invalid facility type.');

        $price = (int) $this->request->getPost('ticket_price');
        $price = max((int) $cfg['min_price'], min((int) $cfg['max_price'], $price));

        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'ticket_price' => $price,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' entry price set to ' . currency($price) . '.');
    }

    public function repair(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $cost = Rules::repairCost((string) $f['facility_type'], (int) $f['condition_pct']);
        if ($cost <= 0) {
            return redirect()->back()->with('info', $f['name'] . ' is already in full condition.');
        }

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Repaired ' . $f['name'])) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash to repair (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'condition_pct' => 100,
            'status' => $f['status'] === 'maintenance' ? 'open' : $f['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' repaired to 100%.');
    }

    public function toggle(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        if ($f['status'] === 'maintenance') {
            return redirect()->back()->with('error', $f['name'] . ' needs repairs before reopening.');
        }

        $next = $f['status'] === 'open' ? 'closed' : 'open';
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'status' => $next,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' is now ' . $next . '.');
    }

    public function demolish(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $refund = Rules::salvageValue((string) $f['facility_type'], (int) $f['level']);
        $db->transStart();
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->delete();
        if ($db->affectedRows() === 1 && $refund > 0) {
            Ledger::credit($userId, $refund, 'Winter Sports', 'Demolished ' . $f['name'] . ' (50% salvage)');
        }
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Demolished ' . $f['name'] . ' (+' . currency($refund) . ')', 'fa-solid fa-trash-can');
        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' demolished. Refunded ' . currency($refund) . '.');
    }
}
