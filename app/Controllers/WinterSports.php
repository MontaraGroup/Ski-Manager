<?php

namespace App\Controllers;

use App\Libraries\Achievements as AchievementRules;
use App\Libraries\Ledger;
use App\Libraries\WinterSports as Rules;

/**
 * /winter-sports: 5 distinct winter activities (Ice Rink, Cross-Country Skiing, Ice Hockey Venue,
 * Ski Jump Ramp, Bobsleigh Track), each with its own mechanics, upgrades, and linked tournament.
 */
class WinterSports extends BaseController
{
    public function index(): string
    {
        return view('winter_sports/index', ['page' => $this->pageData()]);
    }

    private function redirectToTab(string $type, string $kind, string $msg)
    {
        $tab = in_array($type, Rules::TYPES, true) ? $type : 'ice_rink';
        return redirect()->to('/winter-sports?tab=' . $tab)->with($kind, $msg);
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
        $snowBaseCm = isset($weather['snow_base']) ? (int) $weather['snow_base'] : null;

        // Connected Cross-Country trails built on /map
        $mapTrailsRaw = $db->table('player_items')
            ->select('name, length_meters')
            ->where('user_id', $userId)
            ->where('subtype', 'crosscountry')
            ->where('status', 'open')
            ->get()
            ->getResultArray();
        $mapTrails = array_map(fn($t) => [
            'name' => (string) ($t['name'] ?: 'Nordic Trail'),
            'km' => round(((int) ($t['length_meters'] ?? 0)) / 1000, 1),
        ], $mapTrailsRaw);
        $mapNordicKm = (int) round(array_sum(array_column($mapTrails, 'km')));

        $rows = $db->tableExists('winter_sport_facilities')
            ? $db->table('winter_sport_facilities')->where('user_id', $userId)->orderBy('id', 'ASC')->get()->getResultArray()
            : [];
        $byType = [];
        foreach ($rows as $r) {
            if (!isset($byType[$r['facility_type']])) {
                $byType[$r['facility_type']] = $r;
            }
        }

        $requestedTab = (string) ($this->request->getGet('tab') ?? '');
        $activeTab = in_array($requestedTab, Rules::TYPES, true)
            ? $requestedTab
            : ($rows ? (string) $rows[0]['facility_type'] : 'ice_rink');

        $totalRev = 0;
        $totalTicketRev = 0;
        $totalAddonRev = 0;
        $totalContractRev = 0;
        $totalUpkeep = 0;
        $totalGuests = 0;
        $totalVisitorBoost = 0;
        $totalKwh = 0;
        $openCount = 0;
        $builtCount = 0;

        $tabs = [];
        foreach (Rules::TYPES as $type) {
            $cfg = Rules::CATALOG[$type];
            $f = $byType[$type] ?? null;
            $built = $f !== null;
            if ($built) $builtCount++;

            $uDefs = Rules::VENUE_UPGRADES[$type] ?? [];
            $venue = null;

            if ($f) {
                $level = max(1, min(Rules::MAX_LEVEL, (int) $f['level']));
                $cond = (int) $f['condition_pct'];
                $iceQuality = (int) ($f['ice_quality'] ?? 90);
                $installed = Rules::parseUpgrades($type, $f['upgrades'] ?? null);
                $config = Rules::parseConfig($type, $f['config_json'] ?? null);

                $settle = Rules::settleDay($f, $visitors, $isWinter, $tempC, $windKmh, $snowBaseCm, $mapNordicKm);
                if ($f['status'] === 'open') {
                    $openCount++;
                    $totalRev += $settle['revenue'];
                    $totalTicketRev += $settle['ticketRevenue'];
                    $totalAddonRev += $settle['addonRevenue'];
                    $totalContractRev += $settle['contractRevenue'];
                    $totalUpkeep += $settle['upkeep'];
                    $totalGuests += $settle['guests'];
                    $totalVisitorBoost += $settle['visitorBoost'];
                    $totalKwh += $settle['kwh'];
                }

                $nextLevel = $level < Rules::MAX_LEVEL ? $level + 1 : null;
                $upgradeCost = $nextLevel ? Rules::upgradeCost($type, $level) : 0;
                $repairCost = Rules::repairCost($type, $cond);
                $resurfaceCost = $iceQuality < 95 ? (int) $cfg['resurface_cost'] : 0;
                $salvage = Rules::salvageValue($type, $level, $installed, $config);

                $addons = [];
                foreach ($uDefs as $uKey => $u) {
                    $addons[] = [
                        'key' => $uKey,
                        'name' => $u['name'],
                        'icon' => $u['icon'],
                        'desc' => $u['desc'],
                        'cost' => (int) $u['cost'],
                        'costText' => currency((int) $u['cost']),
                        'upkeepText' => currency((int) $u['upkeep']),
                        'kwh' => (int) $u['kwh'],
                        'installed' => in_array($uKey, $installed, true),
                    ];
                }

                // Bespoke per-activity payload
                $specific = [];
                if ($type === 'ice_rink') {
                    $hasLights = in_array('string_lights', $installed, true);
                    $slots = $config['slots'] ?? ['morning' => 'open_skate', 'afternoon' => 'lessons', 'evening' => 'open_skate'];
                    $sessionOptions = [];
                    foreach (Rules::RINK_SESSIONS as $sKey => $sDef) {
                        $sessionOptions[] = [
                            'key' => $sKey,
                            'label' => $sDef['label'],
                            'desc' => $sDef['desc'],
                            'eveningOnly' => (bool) $sDef['evening_only'],
                            'needsLights' => (bool) $sDef['needs_lights'],
                            'locked' => (bool) ($sDef['needs_lights'] && !$hasLights),
                        ];
                    }
                    $specific = [
                        'slots' => $slots,
                        'sessionOptions' => $sessionOptions,
                        'rentalPrice' => (int) ($config['rental_price'] ?? 10),
                        'hasSkateShop' => in_array('skate_shop', $installed, true),
                        'hasChiller' => in_array('ice_chiller', $installed, true),
                        'hasLights' => $hasLights,
                    ];
                } elseif ($type === 'cross_country') {
                    $builtLoops = $config['loops'] ?? ['meadow_3k'];
                    $loopList = [];
                    $builtKm = 0;
                    foreach (Rules::NORDIC_LOOPS as $lKey => $lDef) {
                        $isBuilt = in_array($lKey, $builtLoops, true);
                        if ($isBuilt) $builtKm += (int) $lDef['km'];
                        $loopList[] = [
                            'key' => $lKey,
                            'name' => $lDef['name'],
                            'km' => (int) $lDef['km'],
                            'difficulty' => $lDef['difficulty'],
                            'cost' => (int) $lDef['cost'],
                            'costText' => currency((int) $lDef['cost']),
                            'upkeepText' => currency((int) $lDef['upkeep']),
                            'capacity' => (int) $lDef['capacity'],
                            'draw' => (int) $lDef['draw'],
                            'desc' => $lDef['desc'],
                            'built' => $isBuilt,
                        ];
                    }
                    $groomKey = (string) ($config['grooming'] ?? 'classic');
                    $groomOpts = [];
                    foreach (Rules::NORDIC_GROOMING as $gKey => $gDef) {
                        $groomOpts[] = [
                            'key' => $gKey,
                            'label' => $gDef['label'],
                            'desc' => $gDef['desc'],
                            'active' => $groomKey === $gKey,
                        ];
                    }
                    $specific = [
                        'loops' => $loopList,
                        'builtKm' => $builtKm,
                        'mapNordicKm' => $mapNordicKm,
                        'totalKm' => $builtKm + $mapNordicKm,
                        'mapTrails' => $mapTrails,
                        'grooming' => $groomKey,
                        'groomingOptions' => $groomOpts,
                        'rentalPrice' => (int) ($config['rental_price'] ?? 14),
                        'hasWaxBench' => in_array('wax_bench', $installed, true),
                        'hasSnowSupport' => in_array('snow_support', $installed, true),
                        'snowBaseCm' => $snowBaseCm,
                    ];
                } elseif ($type === 'hockey_arena') {
                    $seats = (int) ($cfg['seat_tiers'][$level] ?? 400);
                    $booked = (string) ($config['booked_match'] ?? 'club_rental');
                    $hasProLockers = in_array('pro_lockers', $installed, true);
                    $matchOpts = [];
                    foreach (Rules::HOCKEY_MATCHES as $mKey => $mDef) {
                        $lockedReason = null;
                        if ($level < (int) $mDef['min_level']) {
                            $lockedReason = 'Requires Level ' . $mDef['min_level'] . ' Arena';
                        } elseif (!empty($mDef['needs_pro_lockers']) && !$hasProLockers) {
                            $lockedReason = 'Requires Pro Locker Rooms';
                        }
                        $matchOpts[] = [
                            'key' => $mKey,
                            'name' => $mDef['name'],
                            'desc' => $mDef['desc'],
                            'bookingCost' => (int) $mDef['booking_cost'],
                            'costText' => (int) $mDef['booking_cost'] > 0 ? currency((int) $mDef['booking_cost']) : 'Free',
                            'fillPct' => (int) round((float) $mDef['fill_rate'] * 100),
                            'rep' => (int) $mDef['rep'],
                            'active' => $booked === $mKey,
                            'locked' => $lockedReason !== null,
                            'lockReason' => $lockedReason,
                        ];
                    }
                    $specific = [
                        'seats' => $seats,
                        'bookedMatch' => $booked,
                        'matchesHosted' => (int) ($config['matches_hosted'] ?? 0),
                        'matchOptions' => $matchOpts,
                        'hasDasherSponsors' => in_array('dasher_sponsors', $installed, true),
                    ];
                } elseif ($type === 'ski_jump') {
                    $builtHills = $config['hills'] ?? ['k60'];
                    $hillList = [];
                    foreach (Rules::JUMP_HILLS as $hKey => $hDef) {
                        $hillList[] = [
                            'key' => $hKey,
                            'name' => $hDef['name'],
                            'kPoint' => (int) $hDef['k_point'],
                            'maxM' => (int) $hDef['max_m'],
                            'cost' => (int) $hDef['cost'],
                            'costText' => currency((int) $hDef['cost']),
                            'upkeepText' => currency((int) $hDef['upkeep']),
                            'capacity' => (int) $hDef['capacity'],
                            'draw' => (int) $hDef['draw'],
                            'desc' => $hDef['desc'],
                            'built' => in_array($hKey, $builtHills, true),
                        ];
                    }
                    $allocKey = (string) ($config['allocation'] ?? 'combined');
                    $allocOpts = [];
                    foreach (Rules::JUMP_ALLOCATIONS as $aKey => $aDef) {
                        $allocOpts[] = [
                            'key' => $aKey,
                            'label' => $aDef['label'],
                            'desc' => $aDef['desc'],
                            'active' => $allocKey === $aKey,
                        ];
                    }
                    $specific = [
                        'hills' => $hillList,
                        'allocation' => $allocKey,
                        'allocationOptions' => $allocOpts,
                        'windKmh' => $windKmh,
                        'safeWindKmh' => in_array('wind_nets', $installed, true) ? 55 : 35,
                        'hillRecordM' => (int) ($config['hill_record_m'] ?? 58),
                        'hasSummerMat' => in_array('summer_mat', $installed, true),
                    ];
                } elseif ($type === 'bobsleigh_track') {
                    $modeKey = (string) ($config['mode'] ?? 'split');
                    $coolKey = (string) ($config['cooling'] ?? 'standard');
                    $hasShades = in_array('sun_shades', $installed, true);
                    $modeOpts = [];
                    foreach (Rules::BOB_MODES as $mKey => $mDef) {
                        $modeOpts[] = [
                            'key' => $mKey,
                            'label' => $mDef['label'],
                            'desc' => $mDef['desc'],
                            'active' => $modeKey === $mKey,
                        ];
                    }
                    $coolOpts = [];
                    foreach (Rules::COOLING_LEVELS as $cKey => $cDef) {
                        $cCost = $hasShades ? (int) round((int) $cDef['cost'] * 0.65) : (int) $cDef['cost'];
                        $cKwh = $hasShades ? (int) round((int) $cDef['kwh'] * 0.65) : (int) $cDef['kwh'];
                        $cTemp = (int) $cDef['max_temp'] + ($hasShades ? 4 : 0);
                        $coolOpts[] = [
                            'key' => $cKey,
                            'label' => $cDef['label'],
                            'desc' => $cDef['desc'],
                            'costText' => currency($cCost),
                            'kwh' => $cKwh,
                            'maxTemp' => $cTemp,
                            'active' => $coolKey === $cKey,
                        ];
                    }
                    $specific = [
                        'mode' => $modeKey,
                        'modeOptions' => $modeOpts,
                        'cooling' => $coolKey,
                        'coolingOptions' => $coolOpts,
                        'tempC' => $tempC,
                        'topSpeedKmh' => Rules::trackSpeedKmh($level, $coolKey, $iceQuality),
                        'hasSunShades' => $hasShades,
                    ];
                }

                $venue = [
                    'id' => (int) $f['id'],
                    'name' => (string) $f['name'],
                    'level' => $level,
                    'levelName' => $cfg['levels'][$level] ?? $cfg['name'],
                    'maxLevel' => Rules::MAX_LEVEL,
                    'status' => (string) $f['status'],
                    'condition_pct' => $cond,
                    'ice_quality' => $iceQuality,
                    'ticket_price' => (int) $f['ticket_price'],
                    'min_price' => (int) $cfg['min_price'],
                    'max_price' => (int) $cfg['max_price'],
                    'est' => [
                        'guests' => $settle['guests'],
                        'capacity' => $settle['capacity'],
                        'ticketText' => currency($settle['ticketRevenue']),
                        'addonText' => currency($settle['addonRevenue']),
                        'contractText' => currency($settle['contractRevenue']),
                        'revenueText' => currency($settle['revenue']),
                        'upkeepText' => currency($settle['upkeep']),
                        'netText' => currency($settle['revenue'] - $settle['upkeep']),
                        'kwh' => $settle['kwh'],
                        'visitorBoost' => $settle['visitorBoost'],
                        'repBonus' => $settle['repBonus'],
                        'weatherNote' => $settle['weatherNote'],
                        'highlight' => $settle['highlight'],
                    ],
                    'lastGuests' => (int) ($f['last_guests'] ?? 0),
                    'lastTicketText' => currency((int) ($f['last_ticket_rev'] ?? 0)),
                    'lastAddonText' => currency((int) ($f['last_rental_rev'] ?? 0)),
                    'totalRevenueText' => currency((int) $f['total_revenue']),
                    'nextLevel' => $nextLevel,
                    'nextLevelName' => $nextLevel ? ($cfg['levels'][$nextLevel] ?? null) : null,
                    'upgradeCost' => $upgradeCost,
                    'upgradeCostText' => currency($upgradeCost),
                    'repairCost' => $repairCost,
                    'repairCostText' => currency($repairCost),
                    'resurfaceCost' => $resurfaceCost,
                    'resurfaceCostText' => currency($resurfaceCost),
                    'salvageText' => currency($salvage),
                    'addons' => $addons,
                    'specific' => $specific,
                ];
            }

            $tabs[] = [
                'type' => $type,
                'name' => $cfg['name'],
                'tagline' => $cfg['tagline'],
                'icon' => $cfg['icon'],
                'desc' => $cfg['desc'],
                'surface' => $cfg['surface'],
                'weather' => $cfg['weather'],
                'built' => $built,
                'buildCost' => (int) $cfg['build_cost'],
                'buildCostText' => currency((int) $cfg['build_cost']),
                'upkeepText' => currency((int) $cfg['upkeep']),
                'canBuild' => !$built && $cash >= (int) $cfg['build_cost'],
                'eventName' => $cfg['event_name'],
                'eventKey' => $cfg['event_key'],
                'levels' => array_values($cfg['levels']),
                'upgradePreviews' => array_values(array_map(fn($u) => [
                    'name' => $u['name'],
                    'icon' => $u['icon'],
                    'desc' => $u['desc'],
                    'costText' => currency((int) $u['cost']),
                ], $uDefs)),
                'venue' => $venue,
            ];
        }

        return [
            'cash' => $cash,
            'cashText' => currency($cash),
            'activeTab' => $activeTab,
            'weather' => [
                'tempC' => $tempC,
                'windKmh' => $windKmh,
                'snowBaseCm' => $snowBaseCm,
                'condition' => (string) ($weather['condition_name'] ?? ''),
                'isWinter' => $isWinter,
            ],
            'stats' => [
                'openCount' => $openCount,
                'builtCount' => $builtCount,
                'totalTypes' => count(Rules::TYPES),
                'estGuests' => $totalGuests,
                'estRevenueText' => currency($totalRev),
                'estTicketText' => currency($totalTicketRev),
                'estAddonText' => currency($totalAddonRev + $totalContractRev),
                'totalUpkeepText' => currency($totalUpkeep),
                'estNetText' => currency($totalRev - $totalUpkeep),
                'totalKwh' => $totalKwh,
                'visitorBoost' => $totalVisitorBoost,
            ],
            'tabs' => $tabs,
        ];
    }

    public function build()
    {
        $userId = (int) auth()->id();
        $type = (string) $this->request->getPost('facility_type');
        $cfg = Rules::CATALOG[$type] ?? null;
        if (!$cfg) {
            return redirect()->back()->with('error', 'Unknown winter activity.');
        }

        $db = db_connect();
        $existing = $db->table('winter_sport_facilities')->where('user_id', $userId)->where('facility_type', $type)->countAllResults();
        if ($existing >= Rules::MAX_PER_TYPE) {
            return $this->redirectToTab($type, 'info', $cfg['name'] . ' is already built.');
        }

        $cost = (int) $cfg['build_cost'];
        $name = $cfg['name'];
        $now = date('Y-m-d H:i:s');
        $defaultConfig = Rules::defaultConfig($type);

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Built ' . $name)) {
            $db->transRollback();
            return $this->redirectToTab($type, 'error', 'Not enough cash to build ' . $name . ' (' . currency($cost) . ' needed).');
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
            'config_json' => json_encode($defaultConfig),
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

        AchievementRules::sync($userId);
        log_activity($userId, 'Winter Sports', 'Built ' . $name . ' for ' . currency($cost), 'fa-solid ' . $cfg['icon']);
        return $this->redirectToTab($type, 'success', $name . ' built and open for guests.');
    }

    public function upgrade(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $level = (int) $f['level'];
        if ($level >= Rules::MAX_LEVEL) {
            return $this->redirectToTab($type, 'info', $f['name'] . ' is already at max level.');
        }

        $cost = Rules::upgradeCost($type, $level);
        $nextLevel = $level + 1;

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Upgraded ' . $f['name'] . ' to Level ' . $nextLevel)) {
            $db->transRollback();
            return $this->redirectToTab($type, 'error', 'Not enough cash to upgrade (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'level' => $nextLevel,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Upgraded ' . $f['name'] . ' to Level ' . $nextLevel . ' (' . currency($cost) . ')', 'fa-solid fa-arrow-up');
        return $this->redirectToTab($type, 'success', $f['name'] . ' upgraded to Level ' . $nextLevel . '.');
    }

    public function installUpgrade(int $id)
    {
        $userId = (int) auth()->id();
        $addon = (string) $this->request->getPost('upgrade');

        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $u = Rules::VENUE_UPGRADES[$type][$addon] ?? null;
        if (!$u) return $this->redirectToTab($type, 'error', 'Unknown upgrade for ' . $f['name'] . '.');

        $installed = Rules::parseUpgrades($type, $f['upgrades'] ?? null);
        if (in_array($addon, $installed, true)) {
            return $this->redirectToTab($type, 'info', $u['name'] . ' is already installed.');
        }

        $cost = (int) $u['cost'];
        $installed[] = $addon;

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Installed ' . $u['name'] . ' on ' . $f['name'])) {
            $db->transRollback();
            return $this->redirectToTab($type, 'error', 'Not enough cash for ' . $u['name'] . ' (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'upgrades' => implode(',', $installed),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Installed ' . $u['name'] . ' on ' . $f['name'], 'fa-solid ' . $u['icon']);
        return $this->redirectToTab($type, 'success', $u['name'] . ' installed on ' . $f['name'] . '.');
    }

    /** Bespoke per-activity settings: Ice Rink slots/rental, Cross-Country grooming/rental, Ski Jump allocation, Bobsleigh mode/cooling */
    public function configure(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $installed = Rules::parseUpgrades($type, $f['upgrades'] ?? null);
        $config = Rules::parseConfig($type, $f['config_json'] ?? null);

        if ($type === 'ice_rink') {
            $hasLights = in_array('string_lights', $installed, true);
            $slots = $config['slots'] ?? ['morning' => 'open_skate', 'afternoon' => 'lessons', 'evening' => 'open_skate'];
            foreach (['morning', 'afternoon', 'evening'] as $slot) {
                $val = $this->request->getPost('slot_' . $slot);
                if ($val !== null && isset(Rules::RINK_SESSIONS[$val])) {
                    $def = Rules::RINK_SESSIONS[$val];
                    if (!empty($def['evening_only']) && $slot !== 'evening') {
                        return $this->redirectToTab($type, 'error', $def['label'] . ' can only run in the Evening slot.');
                    }
                    if (!empty($def['needs_lights']) && !$hasLights) {
                        return $this->redirectToTab($type, 'error', 'Install String Lights & Sound first to host Evening Ice Disco.');
                    }
                    $slots[$slot] = $val;
                }
            }
            $config['slots'] = $slots;
            $rental = $this->request->getPost('rental_price');
            if ($rental !== null) {
                $config['rental_price'] = max(3, min(30, (int) $rental));
            }
            $msg = 'Ice Rink schedule and pricing updated.';
        } elseif ($type === 'cross_country') {
            $groom = (string) ($this->request->getPost('grooming') ?? '');
            if ($groom !== '' && isset(Rules::NORDIC_GROOMING[$groom])) {
                $config['grooming'] = $groom;
            }
            $rental = $this->request->getPost('rental_price');
            if ($rental !== null) {
                $config['rental_price'] = max(5, min(40, (int) $rental));
            }
            $msg = 'Nordic grooming settings updated.';
        } elseif ($type === 'ski_jump') {
            $alloc = (string) ($this->request->getPost('allocation') ?? '');
            if ($alloc !== '' && isset(Rules::JUMP_ALLOCATIONS[$alloc])) {
                $config['allocation'] = $alloc;
            }
            $msg = 'Ski Jump schedule updated to ' . (Rules::JUMP_ALLOCATIONS[$config['allocation']]['label'] ?? 'Updated') . '.';
        } elseif ($type === 'bobsleigh_track') {
            $mode = (string) ($this->request->getPost('mode') ?? '');
            if ($mode !== '' && isset(Rules::BOB_MODES[$mode])) {
                $config['mode'] = $mode;
            }
            $cooling = (string) ($this->request->getPost('cooling') ?? '');
            if ($cooling !== '' && isset(Rules::COOLING_LEVELS[$cooling])) {
                $config['cooling'] = $cooling;
            }
            $msg = 'Bobsleigh operations & cooling updated.';
        } else {
            return $this->redirectToTab($type, 'info', 'No custom settings for this venue.');
        }

        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'config_json' => json_encode($config),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->redirectToTab($type, 'success', $msg);
    }

    /** Build an additional Nordic loop (cross_country) or Jump Hill (ski_jump) */
    public function addExpansion(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $config = Rules::parseConfig($type, $f['config_json'] ?? null);

        if ($type === 'cross_country') {
            $key = (string) $this->request->getPost('expansion_key');
            $item = Rules::NORDIC_LOOPS[$key] ?? null;
            if (!$item) return $this->redirectToTab($type, 'error', 'Unknown Nordic trail loop.');
            $list = $config['loops'] ?? ['meadow_3k'];
            if (in_array($key, $list, true)) return $this->redirectToTab($type, 'info', $item['name'] . ' is already built.');
            $cost = (int) $item['cost'];
            $list[] = $key;
            $config['loops'] = $list;
            $label = $item['name'];
        } elseif ($type === 'ski_jump') {
            $key = (string) $this->request->getPost('expansion_key');
            $item = Rules::JUMP_HILLS[$key] ?? null;
            if (!$item) return $this->redirectToTab($type, 'error', 'Unknown jump hill.');
            $list = $config['hills'] ?? ['k60'];
            if (in_array($key, $list, true)) return $this->redirectToTab($type, 'info', $item['name'] . ' is already built.');
            $cost = (int) $item['cost'];
            $list[] = $key;
            $config['hills'] = $list;
            $label = $item['name'];
        } else {
            return $this->redirectToTab($type, 'error', 'This venue does not support trail/hill expansion.');
        }

        $db->transStart();
        if ($cost > 0 && !Ledger::charge($userId, $cost, 'Winter Sports', 'Built ' . $label)) {
            $db->transRollback();
            return $this->redirectToTab($type, 'error', 'Not enough cash for ' . $label . ' (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'config_json' => json_encode($config),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Added ' . $label . ' to ' . $f['name'], 'fa-solid fa-plus');
        return $this->redirectToTab($type, 'success', $label . ' is now open!');
    }

    /** Book a match on the Ice Hockey Venue matchday board */
    public function bookMatch(int $id)
    {
        $userId = (int) auth()->id();
        $matchKey = (string) $this->request->getPost('match_key');
        $match = Rules::HOCKEY_MATCHES[$matchKey] ?? null;
        if (!$match) return redirect()->back()->with('error', 'Unknown hockey match tier.');

        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->where('facility_type', 'hockey_arena')->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Ice Hockey Venue not found.');

        $level = (int) $f['level'];
        $installed = Rules::parseUpgrades('hockey_arena', $f['upgrades'] ?? null);
        if ($level < (int) $match['min_level']) {
            return $this->redirectToTab('hockey_arena', 'error', $match['name'] . ' requires a Level ' . $match['min_level'] . ' Arena.');
        }
        if (!empty($match['needs_pro_lockers']) && !in_array('pro_lockers', $installed, true)) {
            return $this->redirectToTab('hockey_arena', 'error', $match['name'] . ' requires Pro Locker Rooms.');
        }

        $config = Rules::parseConfig('hockey_arena', $f['config_json'] ?? null);
        if (($config['booked_match'] ?? 'club_rental') === $matchKey) {
            return $this->redirectToTab('hockey_arena', 'info', $match['name'] . ' is already scheduled.');
        }

        $cost = (int) $match['booking_cost'];
        $db->transStart();
        if ($cost > 0 && !Ledger::charge($userId, $cost, 'Winter Sports', 'Booked ' . $match['name'])) {
            $db->transRollback();
            return $this->redirectToTab('hockey_arena', 'error', 'Not enough cash to book ' . $match['name'] . ' (' . currency($cost) . ' needed).');
        }
        $config['booked_match'] = $matchKey;
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'config_json' => json_encode($config),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Booked ' . $match['name'] . ' at Ice Hockey Venue', 'fa-solid fa-hockey-puck');
        return $this->redirectToTab('hockey_arena', 'success', $match['name'] . ' booked for tonight\'s matchday.');
    }

    public function resurface(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $cfg = Rules::CATALOG[$type] ?? null;
        if (!$cfg) return redirect()->back()->with('error', 'Invalid facility type.');

        if ((int) ($f['ice_quality'] ?? 100) >= 95) {
            return $this->redirectToTab($type, 'info', $f['name'] . ' surface is already fresh.');
        }

        $cost = (int) ($cfg['resurface_cost'] ?? 400);
        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Resurfaced ' . $f['name'])) {
            $db->transRollback();
            return $this->redirectToTab($type, 'error', 'Not enough cash to resurface (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'ice_quality' => 100,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        return $this->redirectToTab($type, 'success', $f['name'] . ' resurfaced to 100% quality.');
    }

    public function price(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $cfg = Rules::CATALOG[$type] ?? null;
        if (!$cfg) return redirect()->back()->with('error', 'Invalid facility type.');

        $price = (int) $this->request->getPost('ticket_price');
        $price = max((int) $cfg['min_price'], min((int) $cfg['max_price'], $price));

        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'ticket_price' => $price,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirectToTab($type, 'success', $f['name'] . ' entry price set to ' . currency($price) . '.');
    }

    public function repair(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $cost = Rules::repairCost($type, (int) $f['condition_pct']);
        if ($cost <= 0) {
            return $this->redirectToTab($type, 'info', $f['name'] . ' is already in full condition.');
        }

        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Winter Sports', 'Repaired ' . $f['name'])) {
            $db->transRollback();
            return $this->redirectToTab($type, 'error', 'Not enough cash to repair (' . currency($cost) . ' needed).');
        }
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'condition_pct' => 100,
            'status' => $f['status'] === 'maintenance' ? 'open' : $f['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        return $this->redirectToTab($type, 'success', $f['name'] . ' repaired to 100%.');
    }

    public function toggle(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        if ($f['status'] === 'maintenance') {
            return $this->redirectToTab($type, 'error', $f['name'] . ' needs repairs before reopening.');
        }

        $next = $f['status'] === 'open' ? 'closed' : 'open';
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->update([
            'status' => $next,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->redirectToTab($type, 'success', $f['name'] . ' is now ' . $next . '.');
    }

    public function demolish(int $id)
    {
        $userId = (int) auth()->id();
        $db = db_connect();
        $f = $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->get()->getRowArray();
        if (!$f) return redirect()->back()->with('error', 'Facility not found.');

        $type = (string) $f['facility_type'];
        $installed = Rules::parseUpgrades($type, $f['upgrades'] ?? null);
        $config = Rules::parseConfig($type, $f['config_json'] ?? null);
        $refund = Rules::salvageValue($type, (int) $f['level'], $installed, $config);

        $db->transStart();
        $db->table('winter_sport_facilities')->where('id', $id)->where('user_id', $userId)->delete();
        if ($db->affectedRows() === 1 && $refund > 0) {
            Ledger::credit($userId, $refund, 'Winter Sports', 'Demolished ' . $f['name'] . ' (50% salvage)');
        }
        $db->transComplete();

        log_activity($userId, 'Winter Sports', 'Demolished ' . $f['name'] . ' (+' . currency($refund) . ')', 'fa-solid fa-trash-can');
        return $this->redirectToTab($type, 'success', $f['name'] . ' demolished. Refunded ' . currency($refund) . '.');
    }
}
