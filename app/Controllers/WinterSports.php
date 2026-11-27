<?php

namespace App\Controllers;

use App\Libraries\Ledger;
use App\Libraries\WinterSports as Rules;

/**
 * /winter-sports: build, price, upgrade, equip add-ons, set session mode, resurface,
 * repair, toggle and demolish winter sport activities & venues.
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
        $weather = function_exists('getCurrentWeather') ? getCurrentWeather() : [];
        $tempC = isset($weather['temp']) ? (int) $weather['temp'] : null;
        $windKmh = isset($weather['wind_speed']) ? (int) $weather['wind_speed'] : null;

        $rows = $db->tableExists('winter_sport_facilities')
            ? $db->table('winter_sport_facilities')->where('user_id', $userId)->orderBy('id', 'ASC')->get()->getResultArray()
            : [];

        $countsByType = [];
        $totalRev = 0;
        $totalTicketRev = 0;
        $totalAddonRev = 0;
        $totalUpkeep = 0;
        $totalGuests = 0;
        $totalVisitorBoost = 0;
        $openCount = 0;

        $facilities = array_map(function (array $f) use (
            &$countsByType, &$totalRev, &$totalTicketRev, &$totalAddonRev,
            &$totalUpkeep, &$totalGuests, &$totalVisitorBoost, &$openCount,
            $visitors, $isWinter, $tempC, $windKmh
        ) {
            $type = (string) $f['facility_type'];
            $countsByType[$type] = ($countsByType[$type] ?? 0) + 1;
            $cfg = Rules::CATALOG[$type] ?? Rules::CATALOG['ice_rink'];
            $level = max(1, min(Rules::MAX_LEVEL, (int) $f['level']));
            $cond = (int) $f['condition_pct'];
            $iceQuality = (int) ($f['ice_quality'] ?? 90);
            $installed = Rules::parseUpgrades($f['upgrades'] ?? null);
            $sessionMode = (string) ($f['session_mode'] ?? 'standard');
            if (!isset(Rules::SESSION_MODES[$sessionMode])) $sessionMode = 'standard';

            $settle = Rules::settleDay($f, $visitors, $isWinter, $tempC, $windKmh);

            if ($f['status'] === 'open') {
                $openCount++;
                $totalRev += $settle['revenue'];
                $totalTicketRev += $settle['ticketRevenue'];
                $totalAddonRev += $settle['rentalRevenue'] + $settle['concessionRevenue'];
                $totalUpkeep += $settle['upkeep'];
                $totalGuests += $settle['guests'];
                $totalVisitorBoost += $settle['visitorBoost'];
            }

            $nextLevel = $level < Rules::MAX_LEVEL ? $level + 1 : null;
            $upgradeCost = $nextLevel ? Rules::upgradeCost($type, $level) : 0;
            $repairCost = Rules::repairCost($type, $cond);
            $resurfaceCost = $iceQuality < 95 ? (int) ($cfg['resurface_cost'] ?? 400) : 0;
            $salvage = Rules::salvageValue($type, $level, $installed);
            $upkeep = Rules::upkeepFor($type, $level, $installed, $sessionMode);

            $addons = [];
            foreach (Rules::UPGRADES as $uKey => $u) {
                $has = in_array($uKey, $installed, true);
                $addons[] = [
                    'key' => $uKey,
                    'name' => $u['name'],
                    'icon' => $u['icon'],
                    'desc' => $u['desc'],
                    'cost' => (int) $u['cost'],
                    'costText' => currency((int) $u['cost']),
                    'upkeepText' => currency((int) $u['upkeep']),
                    'installed' => $has,
                ];
            }

            $modes = [];
            $canExtend = !empty($cfg['indoor']) || in_array('floodlights', $installed, true);
            foreach (Rules::SESSION_MODES as $mKey => $m) {
                $modes[] = [
                    'key' => $mKey,
                    'label' => $m['label'],
                    'desc' => $m['desc'],
                    'active' => $sessionMode === $mKey,
                    'locked' => $mKey === 'extended' && !$canExtend,
                ];
            }

            return [
                'id' => (int) $f['id'],
                'facility_type' => $type,
                'name' => (string) $f['name'],
                'surface' => (string) ($cfg['surface'] ?? 'Ice'),
                'weather' => (string) ($cfg['weather'] ?? 'Outdoor'),
                'weatherNote' => $settle['weatherNote'],
                'level' => $level,
                'levelName' => $cfg['levels'][$level] ?? $cfg['name'],
                'maxLevel' => Rules::MAX_LEVEL,
                'status' => (string) $f['status'],
                'condition_pct' => $cond,
                'ice_quality' => $iceQuality,
                'session_mode' => $sessionMode,
                'modes' => $modes,
                'addons' => $addons,
                'ticket_price' => (int) $f['ticket_price'],
                'min_price' => (int) $cfg['min_price'],
                'max_price' => (int) $cfg['max_price'],
                'capacity' => Rules::capacityFor($type, $level, $sessionMode),
                'estGuests' => $settle['guests'],
                'estTicketText' => currency($settle['ticketRevenue']),
                'estAddonText' => currency($settle['rentalRevenue'] + $settle['concessionRevenue']),
                'estRevenue' => $settle['revenue'],
                'estRevenueText' => currency($settle['revenue']),
                'upkeep' => $upkeep,
                'upkeepText' => currency($upkeep),
                'visitorBoost' => $settle['visitorBoost'],
                'lastGuests' => (int) ($f['last_guests'] ?? 0),
                'lastTicketText' => currency((int) ($f['last_ticket_rev'] ?? 0)),
                'lastAddonText' => currency((int) ($f['last_rental_rev'] ?? 0)),
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
                'resurfaceCost' => $resurfaceCost,
                'resurfaceCostText' => currency($resurfaceCost),
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
                'surface' => $cfg['surface'],
                'weather' => $cfg['weather'],
                'cost' => (int) $cfg['build_cost'],
                'costText' => currency((int) $cfg['build_cost']),
                'upkeep' => (int) $cfg['upkeep'],
                'upkeepText' => currency((int) $cfg['upkeep']),
                'capacity' => (int) $cfg['capacity'],
                'defaultPrice' => (int) $cfg['default_price'],
                'defaultPriceText' => currency((int) $cfg['default_price']),
                'visitorDraw' => (int) $cfg['spectator_draw'],
                'eventName' => $cfg['event_name'],
                'levels' => array_values($cfg['levels']),
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
                'estTicketText' => currency($totalTicketRev),
                'estAddonText' => currency($totalAddonRev),
                'totalUpkeepText' => currency($totalUpkeep),
                'estNetText' => currency($totalRev - $totalUpkeep),
                'estGuests' => $totalGuests,
                'visitorBoost' => $totalVisitorBoost,
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
            'upgrades' => null,
            'session_mode' => 'standard',
            'ice_quality' => 100,
            'ticket_price' => (int) $cfg['default_price'],
            'daily_revenue' => 0,
            'daily_upkeep' => (int) $cfg['upkeep'],
            'last_guests' => 0,
            'last_ticket_rev' => 0,
            'last_rental_rev' => 0,
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
        $installed = Rules::parseUpgrades($f['upgrades'] ?? null);
        $mode = (string) ($f['session_mode'] ?? 'standard');

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Upgraded ' . $f['name'] . ' to Level ' . $nextLevel)) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash to upgrade (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'level' => $nextLevel,
            'daily_upkeep' => Rules::upkeepFor($type, $nextLevel, $installed, $mode),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Upgraded ' . $f['name'] . ' to Level ' . $nextLevel . ' (' . currency($cost) . ')', 'fa-solid fa-arrow-up');
        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' upgraded to Level ' . $nextLevel . '.');
    }

    public function installUpgrade(int $id)
    {
        $userId = (int) auth()->id();
        $addon = (string) $this->request->getPost('upgrade');
        $u = Rules::UPGRADES[$addon] ?? null;
        if (!$u) return redirect()->back()->with('error', 'Unknown facility upgrade.');

        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $installed = Rules::parseUpgrades($f['upgrades'] ?? null);
        if (in_array($addon, $installed, true)) {
            return redirect()->back()->with('info', $u['name'] . ' is already installed on ' . $f['name'] . '.');
        }

        $cost = (int) $u['cost'];
        $installed[] = $addon;
        $mode = (string) ($f['session_mode'] ?? 'standard');

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Installed ' . $u['name'] . ' on ' . $f['name'])) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash for ' . $u['name'] . ' (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'upgrades' => implode(',', $installed),
            'daily_upkeep' => Rules::upkeepFor((string) $f['facility_type'], (int) $f['level'], $installed, $mode),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Installed ' . $u['name'] . ' on ' . $f['name'], 'fa-solid ' . $u['icon']);
        return redirect()->to('/winter-sports')->with('success', $u['name'] . ' installed on ' . $f['name'] . '.');
    }

    public function setMode(int $id)
    {
        $userId = (int) auth()->id();
        $mode = (string) $this->request->getPost('session_mode');
        if (!isset(Rules::SESSION_MODES[$mode])) {
            return redirect()->back()->with('error', 'Invalid session mode.');
        }

        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $cfg = Rules::CATALOG[$f['facility_type']] ?? [];
        $installed = Rules::parseUpgrades($f['upgrades'] ?? null);
        if ($mode === 'extended' && empty($cfg['indoor']) && !in_array('floodlights', $installed, true)) {
            return redirect()->back()->with('error', 'Install Evening Floodlights before enabling Day + Evening sessions.');
        }

        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'session_mode' => $mode,
            'daily_upkeep' => Rules::upkeepFor((string) $f['facility_type'], (int) $f['level'], $installed, $mode),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' schedule set to ' . Rules::SESSION_MODES[$mode]['label'] . '.');
    }

    public function resurface(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $cfg = Rules::CATALOG[$f['facility_type']] ?? null;
        if (!$cfg) return redirect()->back()->with('error', 'Invalid facility type.');

        if ((int) ($f['ice_quality'] ?? 100) >= 95) {
            return redirect()->back()->with('info', $f['name'] . ' surface is already fresh.');
        }

        $cost = (int) ($cfg['resurface_cost'] ?? 400);
        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Resurfaced ' . $f['name'])) {
            $db->transRollback();
            return redirect()->back()->with('error', 'Not enough cash to resurface (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'ice_quality' => 100,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        return redirect()->to('/winter-sports')->with('success', $f['name'] . ' resurfaced to 100% quality.');
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

        $installed = Rules::parseUpgrades($f['upgrades'] ?? null);
        $refund = Rules::salvageValue((string) $f['facility_type'], (int) $f['level'], $installed);
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
