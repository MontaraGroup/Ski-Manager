<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\StaffEffects;

class GameTick extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'game:tick';
    protected $description = 'Process daily game tick: revenue, expenses, weather, morale, conditions, VIP guests';

    public function run(array $params)
    {
        $checkInId = null;
        if (class_exists('\\Sentry\\ClientBuilder') && !empty(getenv('SENTRY_DSN'))) {
            try {
                $checkInId = \Sentry\captureCheckIn(
                    'gametick-cron',
                    \Sentry\CheckInStatus::inProgress()
                );
            } catch (\Throwable $t) {}
        }

        if (function_exists('isMaintenanceMode') && isMaintenanceMode()) {
            CLI::write('Maintenance mode is active: Season 2 development underway. Game tick paused to safeguard player finances.', 'yellow');
            return;
        }

        $db = db_connect();
        if (function_exists('checkAutoSeasonRollover')) {
            checkAutoSeasonRollover();
        }
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $seasonDay = (($gameDay - 1) % max(1, getSeasonLength())) + 1;
        $isWinter = $seasonDay <= getWinterDays();
        $seasonNum = function_exists('getSeasonNumber') ? (int) getSeasonNumber() : 1;

        CLI::write("Game Tick: Day {$gameDay} (Season day {$seasonDay}, " . ($isWinter ? 'Winter' : 'Summer') . ")", 'green');
        // Today's and tomorrow's weather (App\Libraries\Weather generates any missing day)
        \App\Libraries\Weather::ensure($gameDay + 1);

        // Pre-fetch shared daily weather once to avoid O(N) redundant queries
        $todayWeather = $db->table('weather')->where('game_day', $gameDay)->get()->getRowArray()
            ?: ($db->table('weather')->where('game_day <=', $gameDay)->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray() ?: []);

        // Pre-fetch difficulty configs once to eliminate redundant table queries inside loop
        $allDiffConfigs = [];
        $diffRows = $db->table('difficulty_config')->get()->getResultArray();
        foreach ($diffRows as $dr) {
            $allDiffConfigs[$dr['difficulty']][$dr['config_key']] = $dr['config_value'];
        }

        // Process users in bounded batches to prevent memory exhaustion and execution timeouts
        $totalUsers = $db->table('users')->countAllResults();
        $batchSize = 100;
        $offset = 0;

        while ($offset < $totalUsers) {
            $users = $db->table('users')->select('id')->orderBy('id', 'ASC')->limit($batchSize, $offset)->get()->getResultArray();
            if (empty($users)) {
                break;
            }

            foreach ($users as $user) {
                $userId = (int) $user['id'];
                CLI::write("Processing user {$userId}...", 'yellow');
                try {
                    // CI4 keeps a failed transaction status until reset, so one player's error
                    // used to roll back every player processed after them
                    $db->resetTransStatus();
                    $db->transBegin();

                    $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
                    if (!$finance) {
                        $db->table('player_finances')->insert(['user_id' => $userId, 'cash' => 500000, 'total_income' => 0, 'total_expenses' => 0, 'difficulty' => 'standard', 'resort_map' => 'ParkCity']);
                        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
                    }

                    $cash = (int) $finance['cash'];
                    $weather = $todayWeather;
                    $resortOpen = (int) ($finance['resort_open'] ?? 1) === 1;
                    $dayIncome = 0;
                    $dayExpenses = 0;

                    // Detect player dormancy: away for >= 48 hours (at least 2 consecutive missed cycles)
                    $lastActiveStr = $finance['last_active'] ?? null;
                    $userRow = $db->table('users')->select('created_at')->where('id', $userId)->get()->getRowArray();
                    $createdTs = !empty($userRow['created_at']) ? strtotime($userRow['created_at']) : time();
                    $lastActiveTs = !empty($lastActiveStr) ? strtotime($lastActiveStr) : $createdTs;
                    $isDormant = (time() - $lastActiveTs) >= 172800;

                    // Load difficulty config from pre-fetched lookup map
                    $diff = $finance['difficulty'] ?? 'standard';
                    $diffConfig = $allDiffConfigs[$diff] ?? ($allDiffConfigs['standard'] ?? []);
                    $revMult = ((int) ($diffConfig['revenue_multiplier'] ?? 100)) / 100;
                    $costMult = ((int) ($diffConfig['cost_multiplier'] ?? 100)) / 100;
                    $decayMult = ((int) ($diffConfig['decay_multiplier'] ?? 100)) / 100;
                    $moraleDec = (int) ($diffConfig['morale_decay'] ?? 2);
                    $inspChance = (int) ($diffConfig['inspection_chance'] ?? 5);
                    $visMult = ((int) ($diffConfig['visitor_multiplier'] ?? 100)) / 100;
                    $vipBonus = (int) ($diffConfig['vip_chance_bonus'] ?? 0);
                    // Economy v2 (App\Libraries\Economy): demand-priced tickets, lift capacity and running costs, building occupancy
                    $v2 = featureEnabledFor('economy_v2', $userId);
                    $breakdown = [];

            // Active Génépis perks, loaded before any revenue so the revenue boost applies to all of it
            $activeBoostRows = $db->table('player_boosts')->where('user_id', $userId)->where('expires_at >', date('Y-m-d H:i:s'))->get()->getResultArray();
            $activeBoostTypes = array_column($activeBoostRows, 'boost_type');
            if (in_array('revenue_boost', $activeBoostTypes)) {
                $revMult *= 1.25;
            }

            // ==============================
            // STAFF ASSIGNMENT INDEX
            // ==============================
            $allStaff = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            $assignedByRole = [];
            $unassignedByRole = [];
            foreach ($allStaff as $s) {
                if (!empty($s['assigned_to'])) {
                    $assignedByRole[$s['role']][] = $s;
                } else {
                    $unassignedByRole[$s['role']][] = $s;
                }
            }
            $countAssigned = fn($role) => count($assignedByRole[$role] ?? []);
            $countTotal = fn($role) => count($assignedByRole[$role] ?? []) + count($unassignedByRole[$role] ?? []);

            // ==============================
            // STAFF SALARIES + MORALE
            // ==============================
            $staffCost = array_sum(array_column($allStaff, 'salary'));
            $dayExpenses += (int) round($staffCost * $costMult);
            $breakdown['salaries'] = (int) round($staffCost * $costMult);

            foreach ($allStaff as $s) {
                $moraleChange = -$moraleDec;
                if (!empty($s['assigned_to'])) $moraleChange += 1;
                // Inactivity caretaker mode: freeze morale decay so staff don't quit while manager is away
                if ($isDormant) {
                    $moraleChange = max(0, $moraleChange);
                }
                $newMorale = max(0, min(100, (int) $s['morale'] + $moraleChange));
                $db->table('staff')->where('id', $s['id'])->update(['morale' => $newMorale]);
                if ($newMorale <= 10 && rand(1, 100) <= 20) {
                    $db->table('staff')->where('id', $s['id'])->update(['status' => 'fired', 'assigned_to' => null]);
                    log_activity($userId, 'Staff', $s['name'] . ' quit due to low morale', 'fa-solid fa-user-minus');
                }
            }

            // ==============================
            // BUILDING REVENUE + UPKEEP (staff bonuses)
            // ==============================
            $buildings = $db->table('buildings')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray();
            $breakdown['building_upkeep'] = 0;
            foreach ($buildings as $b) {
                $revenue = (int) $b['revenue_per_day'];
                $type = $b['building_type'];

                // Staff assignment bonuses: +15% revenue per assigned staff
                if ($type === 'hotel') $revenue = (int) round($revenue * (1 + $countAssigned('receptionist') * StaffEffects::RECEPTIONIST_REVENUE_BOOST));
                if ($type === 'restaurant') $revenue = (int) round($revenue * (1 + $countAssigned('chef') * StaffEffects::CHEF_REVENUE_BOOST));

                // v2 pays building revenue after visitors are known (it depends on how full they are)
                if ($resortOpen && !$v2) $dayIncome += (int) round($revenue * $revMult); // closed resort: no guests, upkeep still due
                $dayExpenses += (int) round($b['upkeep_per_day'] * $costMult);
                $breakdown['building_upkeep'] += (int) round($b['upkeep_per_day'] * $costMult);
                $bDecay = $isDormant ? 0 : (int) round(rand(0, 2) * $decayMult);
                $newCondition = max(0, (int) $b['condition_pct'] - $bDecay);
                $db->table('buildings')->where('id', $b['id'])->update(['condition_pct' => $newCondition]);
                if ($newCondition <= 0) {
                    $db->table('buildings')->where('id', $b['id'])->update(['status' => 'broken']);
                }
            }

            // ==============================
            // MARKETING CAMPAIGNS
            // ==============================
            $campaigns = $db->table('marketing_campaigns')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($campaigns as $c) {
                $dayExpenses += (int) round($c['daily_cost'] * $costMult);
                $remaining = (int) $c['days_remaining'] - 1;
                if ($remaining <= 0) {
                    $db->table('marketing_campaigns')->where('id', $c['id'])->update(['status' => 'expired', 'days_remaining' => 0]);
                } else {
                    $db->table('marketing_campaigns')->where('id', $c['id'])->update(['days_remaining' => $remaining]);
                }
            }

            // ==============================
            // EQUIPMENT (groomers + snowmakers): fuel, decay, staff bonuses
            // ==============================
            $equipment = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            // Snow guns only run (and cost or wear) on days cold enough for their model
            $baseTemp = (int) ($weather['temp'] ?? 0);
            $equipment = array_values(array_filter($equipment, fn($e) => $e['equipment_type'] !== 'snowmaker' || \App\Libraries\Snowmaking::runs($e, $baseTemp)));
            // v2: groomers without an operator stay parked (no fuel, no wear); the night shift below uses the same fleet
            if ($v2) {
                $crewedGroomers = array_flip(array_map(fn($m) => (int) $m['id'], \App\Libraries\Grooming::fleet(
                    array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer'),
                    array_values(array_filter($allStaff, fn($s) => $s['role'] === 'groomer'))
                )['machines']));
                $equipment = array_values(array_filter($equipment, fn($e) => $e['equipment_type'] !== 'groomer' || isset($crewedGroomers[(int) $e['id']])));
            }
            $crewedGuns = array_flip(array_column(array_filter($allStaff, fn($s) => $s['role'] === 'snowmaker' && !empty($s['assigned_to'])), 'assigned_to'));
            foreach ($equipment as $e) {
                // In standby mode, inactive resort does not burn idle fuel
                if (!$isDormant) {
                    $dayExpenses += (int) round($e['fuel_cost'] * $costMult);
                }

                // Assigned operators reduce decay
                $baseDecay = $isDormant ? 0 : (int) round(($e['equipment_type'] === 'groomer' ? rand(2, 5) : rand(1, 3)) * $decayMult);
                if ($e['equipment_type'] === 'groomer' && $countAssigned('groomer') > 0) {
                    $baseDecay = max(1, $baseDecay - 1);
                }
                if ($e['equipment_type'] === 'snowmaker' && isset($crewedGuns[$e['name']])) {
                    $baseDecay = max(1, $baseDecay - \App\Libraries\Snowmaking::CREW_WEAR_RELIEF);
                }

                $eqNewCond = max(0, (int) $e['condition_pct'] - $baseDecay);
                if ($eqNewCond <= 0) {
                    $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => 0, 'status' => 'broken']);
                    log_activity($userId, 'equipment_broken', $e['name'] . ' has broken down!', 'fa-solid fa-triangle-exclamation');
                    notify($userId, 'equipment_breakdown', $e['name'] . ' broke down!', 'Repair it before it can operate again.', 'fa-solid fa-triangle-exclamation', '/equipment');
                } elseif ($eqNewCond <= 20 && (int) $e['condition_pct'] > 20) {
                    $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => $eqNewCond]);
                    notify($userId, 'equipment_wear', $e['name'] . ' needs maintenance', 'Condition at ' . $eqNewCond . '%. Repair soon.', 'fa-solid fa-wrench', '/equipment');
                } else {
                    $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => $eqNewCond]);
                }
            }

            // Snow guns running today: their energy and water count toward demand below
            $activeSnowmakers = array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker');
            $snowmakingEnergy = array_sum(array_column($activeSnowmakers, 'energy_kwh'));
            $snowmakingWater = array_sum(array_column($activeSnowmakers, 'water_liters'));
            // Energy cost is tracked via fuel_cost already; add energy draw as info

            // ==============================
            // NIGHT SKIING
            // ==============================
            $lights = $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($lights as $l) {
                $dayExpenses += (int) $l['energy_cost'];
                $newCond = max(0, (int) $l['condition_pct'] - rand(0, 2));
                $db->table('night_skiing')->where('id', $l['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('night_skiing')->where('id', $l['id'])->update(['status' => 'broken']);
                }
            }

            // ==============================
            // INSURANCE PREMIUMS
            // ==============================
            $insurance = $db->table('insurance')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
            foreach ($insurance as $i) {
                $dayExpenses += (int) $i['premium_per_day'];
            }

            // ==============================
            // GOVERNMENT COMPLIANCE
            // ==============================
            $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
            foreach ($regs as $r) {
                if ($r['compliant']) {
                    $dayExpenses += (int) $r['compliance_cost'];
                } else {
                    if (rand(1, 100) <= $inspChance) {
                        $fine = (int) $r['penalty_risk'];
                        $dayExpenses += $fine;
                        log_activity($userId, 'inspection', 'Inspection fine: ' . $r['name'] . ': ' . number_format($fine) . '€', 'fa-solid fa-gavel');
                    }
                }
            }

            // ==============================
            // LOAN PAYMENTS
            // ==============================
            $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($loans as $loan) {
                $payment = min((int) $loan['daily_payment'], max(0, (int) $loan['remaining']));
                $dayExpenses += $payment;
                $remaining = max(0, (int) $loan['remaining'] - $payment);
                $daysLeft = max(0, (int) $loan['days_remaining'] - 1);
                if ($remaining <= 0 || $daysLeft <= 0) {
                    $db->table('loans')->where('id', $loan['id'])->update(['status' => 'paid', 'remaining' => 0, 'days_remaining' => 0]);
                } else {
                    $db->table('loans')->where('id', $loan['id'])->update(['remaining' => $remaining, 'days_remaining' => $daysLeft]);
                }
            }

            // ==============================
            // LIFT/SLOPE CONDITION DECAY (mechanics reduce lift decay)
            // ==============================
            $items = $db->table('player_items')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray();
            // Snow guns protect the slopes they cover from a thin natural base (cold days only)
            $snowCovered = $v2 ? \App\Libraries\Snowmaking::coverage(array_filter($items, fn($i) => $i['item_type'] === 'slope'), $activeSnowmakers, $baseTemp) : [];
            // A mechanic looks after the lift they're assigned to (assigned_to holds the lift's name)
            $mechanicLifts = array_flip(array_column($assignedByRole['mechanic'] ?? [], 'assigned_to'));
            foreach ($items as $item) {
                $decay = $isDormant ? 0 : rand(0, 2);
                if (!$isDormant) {
                    if ($item['item_type'] === 'lift' && isset($mechanicLifts[$item['name'] ?? 'Lift #' . $item['id']])) {
                        $decay = max(0, $decay - StaffEffects::MECHANIC_LIFT_WEAR_RELIEF);
                    }
                    // v2: slopes wear by difficulty and weather (App\Libraries\Grooming); groomers restore them below
                    if ($v2 && $item['item_type'] === 'slope') {
                        $decay = \App\Libraries\Grooming::decay($item, $weather, isset($snowCovered[(int) $item['id']]));
                    }
                }
                $newCond = max(0, (int) $item['condition_pct'] - $decay);
                $db->table('player_items')->where('id', $item['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('player_items')->where('id', $item['id'])->update(['status' => 'broken']);
                }
            }

            // ==============================
            $activeGroomers = count(array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer' && $e['status'] === 'active'));

            // v2 NIGHTLY GROOMING: each machine with an operator grooms its capacity in slopes, worst first
            $groomedToday = [];
            if ($v2) {
                $operators = array_values(array_filter($allStaff, fn($s) => $s['role'] === 'groomer'));
                $fleet = \App\Libraries\Grooming::fleet(array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer'), $operators);
                if ($fleet['capacity'] > 0) {
                    $slopeRows = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->whereIn('status', ['open', 'broken'])->get()->getResultArray();
                    $restore = \App\Libraries\Grooming::restore($operators);
                    foreach (\App\Libraries\Grooming::pick($slopeRows, $fleet['capacity']) as $sl) {
                        $db->table('player_items')->where('id', $sl['id'])->update([
                            'condition_pct' => min(100, (int) $sl['condition_pct'] + $restore), 'groomed_day' => $gameDay, 'groomed_manual' => 0, 'status' => 'open',
                        ]);
                        $groomedToday[(int) $sl['id']] = true;
                    }
                    if ($groomedToday) {
                        log_activity($userId, 'Grooming', 'Night shift groomed ' . count($groomedToday) . ' slope' . (count($groomedToday) === 1 ? '' : 's') . ' (+' . $restore . '% each)', 'fa-solid fa-tractor');
                    }
                }
            }
            // AUTO-GROOM PERK
            // ==============================
            $hasAutoGroom = in_array('auto_groom', $activeBoostTypes);

            if ($hasAutoGroom && $activeGroomers > 0) {
                $lowSlopes = $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->where('condition_pct <', 50)->get()->getResultArray();
                foreach ($lowSlopes as $ls) {
                    $boost = min(100, (int) $ls['condition_pct'] + 15);
                    $db->table('player_items')->where('id', $ls['id'])->update(['condition_pct' => $boost]);
                }
                if (count($lowSlopes) > 0) {
                    log_activity($userId, 'Grooming', 'Auto-groom: ' . count($lowSlopes) . ' slopes restored', 'fa-solid fa-robot');
                }
            }

            // ==============================
            // SNOW QUALITY UPDATE
            // ==============================
            $hasSnowmaking = count($activeSnowmakers) > 0;
            $slopesForQuality = $db->table("player_items")->where("user_id", $userId)->where("item_type", "slope")->where("status", "open")->get()->getResultArray();
            $todayQualities = [];
            foreach ($slopesForQuality as $sl) {
                $q = "packed";
                if (($weather["snowfall"] ?? 0) >= 10) { $q = "powder"; }
                elseif ($v2) {
                    // v2: only slopes the night shift actually groomed count as groomed
                    if (isset($groomedToday[(int) $sl['id']])) { $q = "groomed"; }
                    elseif (in_array($weather["condition_name"] ?? "", ["Freezing Rain", "Rain"], true)) { $q = "icy"; }
                    elseif (($weather["condition_name"] ?? "") === "Sunny" && ($weather["temp"] ?? -5) >= 0) { $q = isset($snowCovered[(int) $sl['id']]) ? "packed" : "bare"; }
                }
                elseif (($weather["snowfall"] ?? 0) >= 1) { $q = $activeGroomers > 0 ? "groomed" : "packed"; }
                elseif (($weather["condition_name"] ?? "") === "Freezing Rain") { $q = "icy"; }
                elseif (($weather["condition_name"] ?? "") === "Sunny" && ($weather["temp"] ?? -5) >= 0) { $q = $hasSnowmaking ? "packed" : "bare"; }
                elseif ($activeGroomers > 0) { $q = "groomed"; }
                $db->table("player_items")->where("id", $sl["id"])->update(["snow_quality" => $q]);
                $todayQualities[] = $q;
            }

            // ==============================
            // VISITOR CALCULATION (with staff bonuses, eco, tournaments & boosts)
            // ==============================
            $openSlopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->countAllResults();
            $openLifts = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults();
            $tickets = $db->table('lift_tickets')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
            $avgPrice = count($tickets) > 0 ? array_sum(array_column($tickets, 'price')) / count($tickets) : 0;

            $baseVisitors = ($openSlopes * 50) + ($openLifts * 30);
            $marketingBoost = array_sum(array_column($campaigns, 'visitor_boost'));
            // Manager boost: +5% per assigned manager
            $managerBoost = $countAssigned('manager') * StaffEffects::MANAGER_SKIER_BOOST;
            // Instructor boost: attracts families
            $instructorBoost = $countAssigned('instructor') * StaffEffects::INSTRUCTOR_SKIER_BOOST;
            // Patrol boost: safety reputation
            $patrolBoost = $countAssigned('ski_patrol') * StaffEffects::SKI_PATROL_SKIER_BOOST;

            $snowQualityBonus = 0; foreach ($todayQualities as $sq) { $snowQualityBonus += match($sq) { "powder" => 3, "groomed" => 2, "packed" => 0, "icy" => -2, "bare" => -5, default => 0 }; }

            // Alliance Multi-Pass & Sister Resort synergy
            $allianceBonus = 0;
            if ($db->tableExists('alliance_members')) {
                $allMember = $db->table('alliance_members')->where('user_id', $userId)->get()->getRowArray();
                if ($allMember) {
                    $alliance = $db->table('alliances')->where('id', $allMember['alliance_id'])->get()->getRowArray();
                    if ($alliance) {
                        $passTierBoost = match((int)($alliance['pass_tier'] ?? 1)) {
                            1 => 5, 2 => 10, 3 => 15, 4 => 20, default => 5
                        };
                        $sisterCount = max(0, $db->table('alliance_members')->where('alliance_id', $alliance['id'])->countAllResults() - 1);
                        $synergyBoost = min(10, $sisterCount * 1);
                        $allianceBonus = $passTierBoost + $synergyBoost;
                        $db->table('alliance_members')->where('id', $allMember['id'])->set('cross_skiers_generated', 'cross_skiers_generated + ' . (int)round($baseVisitors * ($allianceBonus / 100)), false)->update();
                    }
                }
            }

            // Eco Score Bonus & Environmental Compliance
            $env = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();
            $ecoBonus = 0;
            if ($env) {
                $ecoScore = (int) ($env['eco_score'] ?? 50);
                if ($ecoScore >= 80) {
                    $ecoBonus = 5; // +5% visitor boost for eco-friendly resort
                    // Award 2 Genepis daily (matches promise in UI and Genepis catalog)
                    $db->table('genepis')->where('user_id', $userId)->set('balance', 'balance + 2', false)->set('total_earned', 'total_earned + 2', false)->update();
                    $db->table('genepis_log')->insert([
                        'user_id' => $userId,
                        'amount' => 2,
                        'type' => 'earned',
                        'reason' => 'Daily Eco Score reward (Score: ' . $ecoScore . ')',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                } elseif ($ecoScore < 30) {
                    $ecoFine = 2500;
                    $dayExpenses += $ecoFine;
                    log_activity($userId, 'Government', 'Environmental fine: ' . currency($ecoFine) . ' due to critically low Eco Score (' . $ecoScore . '/100)', 'fa-solid fa-leaf');
                }
            }

            // Tournaments visitor impact
            $activeTournaments = $db->table('tournaments')
                ->where('host_id', $userId)
                ->where('start_day <=', $gameDay)
                ->where('end_day >=', $gameDay)
                ->where('status !=', 'ended')
                ->get()->getResultArray();

            // Mark running events as active: the cancel button refunds only events that haven't started
            if ($activeTournaments) {
                $db->table('tournaments')->whereIn('id', array_column($activeTournaments, 'id'))->where('status', 'upcoming')->update(['status' => 'active']);
            }

            $tournamentVisitors = 0;
            foreach ($activeTournaments as $trn) {
                $tVisitors = (int) ($trn['visitors_boost'] ?? 0);
                $tournamentVisitors += $tVisitors;
                log_activity($userId, 'Tournament', $trn['name'] . ' in progress: +' . number_format($tVisitors) . ' event visitors today', 'fa-solid fa-trophy');
            }

            // Conclude ended tournaments
            $endedTournaments = $db->table('tournaments')
                ->where('host_id', $userId)
                ->where('end_day <', $gameDay)
                ->where('status !=', 'ended')
                ->get()->getResultArray();
            foreach ($endedTournaments as $et) {
                $db->table('tournaments')->where('id', $et['id'])->update(['status' => 'ended']);
                log_activity($userId, 'Tournament', $et['name'] . ' has concluded successfully!', 'fa-solid fa-medal');
            }

            $totalBoost = $marketingBoost + $managerBoost + $instructorBoost + $patrolBoost + $snowQualityBonus + $allianceBonus + $ecoBonus;
            if ($v2) {
                // Demand comes from the slopes, is damped by ticket prices and the regional market,
                // and can't exceed what the open lifts carry
                $weatherFactor = \App\Libraries\Weather::demandFactor((string) ($weather['condition_name'] ?? ''), (int) ($weather['wind'] ?? 0));
                $demand = \App\Libraries\Economy::slopeDemand($slopesForQuality) * (1 + $totalBoost / 100) * $visMult * $weatherFactor;
                if (in_array('visitor_boost', $activeBoostTypes)) $demand *= 1.5;
                $demand = \App\Libraries\Economy::marketCap($demand);
                $allTickets = $db->table('lift_tickets')->where('user_id', $userId)->get()->getResultArray();
                $ticketTerms = \App\Libraries\Economy::ticketTerms($allTickets);
                $summerTourists = $isWinter ? 0 : (int) round($demand * \App\Libraries\Economy::SUMMER_TOURIST_SHARE);
                $visitors = (int) round($demand * $ticketTerms['demand'] * ($isWinter ? 1 : 0.15)) + $tournamentVisitors;
                $liftRows = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->get()->getResultArray();
                $liftCapacity = \App\Libraries\Economy::liftDailyCapacity($liftRows);
                $turnedAwayByLifts = max(0, $visitors - $liftCapacity);
                $visitors = min($visitors, $liftCapacity);
                if (!$resortOpen) { $visitors = 0; $summerTourists = 0; }
                if ($turnedAwayByLifts > 0 && $resortOpen) {
                    log_activity($userId, 'lift_capacity', number_format($turnedAwayByLifts) . ' skiers stayed away: your lifts carry about ' . number_format($liftCapacity) . ' a day.', 'fa-solid fa-elevator');
                }

                $ticketRevenue = (int) round($visitors * $ticketTerms['per_visit']);
                $dayIncome += (int) round($ticketRevenue * $revMult);
                $breakdown += ['tickets' => (int) round($ticketRevenue * $revMult), 'demand' => (int) round($demand), 'weather_factor' => $weatherFactor, 'price_demand' => round($ticketTerms['demand'], 3),
                    'per_visit' => round($ticketTerms['per_visit'], 2), 'lift_capacity' => $liftCapacity, 'turned_away' => $turnedAwayByLifts];
                if ($visitors > 0) {
                    $prices = array_column($allTickets, 'price', 'ticket_type') ?: \App\Libraries\Economy::TICKET_REFERENCE;
                    $sales = [];
                    foreach ($ticketTerms['mix'] as $type => $share) {
                        $skiers = (int) round($visitors * $share);
                        if ($skiers <= 0) continue;
                        $sales[] = ['user_id' => $userId, 'game_day' => $gameDay, 'ticket_type' => $type, 'quantity' => $skiers,
                            'revenue' => (int) round($skiers * $prices[$type] / (\App\Libraries\Economy::TICKET_VISITS[$type] ?? 1) * $revMult),
                            'created_at' => date('Y-m-d H:i:s')];
                    }
                    if ($sales) $db->table('ticket_sales')->insertBatch($sales);
                }

                // Buildings earn in proportion to how full they are; a staffed one earns the staff bonus.
                // Real estate has its own market (below), so it's left out here.
                $guestBuildings = array_values(array_filter($buildings, fn($b) => $b['building_type'] !== 'real_estate'));
                $util = \App\Libraries\Economy::buildingUtilization($guestBuildings, $visitors, $summerTourists);
                $perType = array_count_values(array_column($guestBuildings, 'building_type'));
                $buildingRevenue = 0;
                foreach ($guestBuildings as $b) {
                    $t = $b['building_type'];
                    $buildingRevenue += (int) $b['revenue_per_day'] * ($util[$t] ?? 0) * \App\Libraries\Economy::staffBonus($t, $perType[$t] ?? 0, $assignedByRole);
                }
                $buildingRevenue = $resortOpen ? (int) round($buildingRevenue * $revMult) : 0;
                $dayIncome += $buildingRevenue;
                $breakdown['buildings'] = $buildingRevenue;
                $breakdown['occupancy'] = array_map(fn($u) => round($u, 3), $util);

                // Real estate (App\Libraries\RealEstate): construction, rent on leased properties, fees on sold ones
                $estate = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'real_estate')->get()->getResultArray();
                if ($estate) {
                    $conds = array_map(fn($sl) => (int) $sl['condition_pct'], $slopesForQuality);
                    $market = \App\Libraries\RealEstate::market((int) (resortRating($userId)['stars'] ?? 0), $openSlopes, $conds ? (int) round(array_sum($conds) / count($conds)) : 0)['value'];
                    $rent = 0; $fees = 0;
                    foreach ($estate as $re) {
                        if ($re['status'] === 'upgrading') {
                            $left = max(0, (int) $re['build_days_left'] - 1);
                            // A finished property is leased out straight away; the owner can sell it any time
                            $db->table('buildings')->where('id', $re['id'])->update($left > 0 ? ['build_days_left' => $left] : ['build_days_left' => 0, 'status' => 'open']);
                            if ($left === 0) notify($userId, 'real_estate', $re['name'] . ' is finished', 'It\'s leased out and earning rent. You can also sell it on the property market.', 'fa-solid fa-city', '/real-estate');
                        } elseif ($re['tenure'] === 'sold') {
                            $fees += \App\Libraries\RealEstate::managementFee((int) $re['sale_price']);
                        } elseif ($re['status'] === 'open') {
                            $rent += \App\Libraries\RealEstate::rent((int) $re['level'], $market, (int) $re['condition_pct'], $isWinter);
                        }
                    }
                    $estateIncome = (int) round(($rent + $fees) * $revMult);
                    $dayIncome += $estateIncome;
                    $breakdown['real_estate'] = $estateIncome;
                    $breakdown['property_market'] = $market;
                }

                // Lifts and slopes cost money to run
                $allItems = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
                $runningCost = (int) round(\App\Libraries\Economy::runningCost($allItems) * $costMult);
                $dayExpenses += $runningCost;
                $breakdown['lift_slope_running'] = $runningCost;
            } else {
            $visitors = (int) round($baseVisitors * (1 + $totalBoost / 100) * $visMult) + $tournamentVisitors;

            // Apply Génépi visitor boost perk
            if (in_array('visitor_boost', $activeBoostTypes)) {
                $visitors = (int) round($visitors * 1.5);
            }

            // Summer reduction
            if (!$isWinter) $visitors = (int) round($visitors * 0.15);

            // A resort its owner has closed gets no guests (so no tickets, lessons, parking or VIPs); costs still apply
            if (!$resortOpen) $visitors = 0;

            $ticketRevenue = (int) round($visitors * $avgPrice * 0.6);
            $dayIncome += (int) round($ticketRevenue * $revMult);
            }

            // ==============================
            // SKI LESSONS / SKI SCHOOL REVENUE
            // ==============================
            $instructorCount = $countAssigned('instructor');
            if ($instructorCount > 0 && $visitors > 0 && $openSlopes > 0) {
                $lessonCapacity = $instructorCount * StaffEffects::LESSONS_PER_INSTRUCTOR;
                $lessonDemand = (int) round($visitors * StaffEffects::LESSON_DEMAND_SHARE);
                $studentsTaught = min($lessonCapacity, $lessonDemand);
                if ($studentsTaught > 0) {
                    $avgLessonFee = StaffEffects::LESSON_FEE;
                    $lessonRevenue = (int) round($studentsTaught * $avgLessonFee * $revMult);
                    $dayIncome += $lessonRevenue;
                    $classesTaught = (int) ceil($studentsTaught / 5);
                    log_activity($userId, 'ski_school', 'Ski School: ' . $instructorCount . ' instructor(s) taught ' . $studentsTaught . ' students in ' . $classesTaught . ' classes (+' . currency($lessonRevenue) . ')', 'fa-solid fa-chalkboard-user');
                }
            }

            // ==============================
            // SCENIC LIFTS REVENUE
            // ==============================
            $scenicLifts = $db->table('scenic_lifts')->where('user_id', $userId)->get()->getResultArray();
            $scenicTotalIncome = 0;
            foreach ($scenicLifts as $sl) {
                $liftItem = $db->table('player_items')->where('id', $sl['item_id'])->where('user_id', $userId)->where('status', 'open')->get()->getRowArray();
                if (!$liftItem) continue;

                // Riders x ticket price (fewer ride at higher prices) + upgrades; winter brings fewer tourists
                $scenicTotalIncome += (int) round(\App\Libraries\Scenic::dailyRevenue($sl, !$isWinter) * $revMult);
            }
            if ($scenicTotalIncome > 0 && $resortOpen) {
                $dayIncome += $scenicTotalIncome;
            }

            // ==============================
            // TERRAIN PARKS
            // ==============================
            $terrainParkModel = new \App\Models\TerrainParkModel();
            $userParks = $terrainParkModel->where('user_id', $userId)->findAll();

            foreach ($userParks as $park) {
                if ($park['status'] === 'under_construction') {
                    $daysLeft = $park['build_days_left'] - 1;
                    if ($daysLeft <= 0) {
                        $terrainParkModel->update($park['id'], ['build_days_left' => 0, 'status' => 'open']);
                        log_activity($userId, 'terrain_park_complete', $park['name'] . ' construction complete!');
                        notify($userId, 'terrain_park', $park['name'] . ' is ready!', 'Your terrain park feature has finished construction.', 'fa-solid fa-person-snowboarding', '/terrain-parks');
                    } else {
                        $terrainParkModel->update($park['id'], ['build_days_left' => $daysLeft]);
                    }
                    continue;
                }
                if ($park['status'] !== 'open') continue;

                $tpConfig = \App\Models\TerrainParkModel::getConfig($park['park_type'], $park['size']);
                $tpUpkeep = $tpConfig['upkeep'] ?? 0;
                $dayExpenses += $tpUpkeep;

                $parkCrewCount = $countAssigned('park_crew');
                $tpBaseDecay = 2.0;
                $tpCrewReduction = min($parkCrewCount * StaffEffects::PARK_CREW_WEAR_RELIEF, StaffEffects::PARK_CREW_CAP);
                $tpDecay = max(0.3, $tpBaseDecay - $tpCrewReduction);
                $tpNewCondition = max(0, $park['condition_pct'] - $tpDecay);

                $tpConditionMult = $tpNewCondition / 100;
                $tpDailyVisitors = round(($tpConfig['popularity_base'] ?? 0) * $tpConditionMult * (1 + ($park['popularity'] ?? 0) / 100));

                $tpNewStatus = $tpNewCondition < 30 ? 'maintenance' : 'open';
                if ($tpNewStatus === 'maintenance' && $park['status'] === 'open') {
                    log_activity($userId, 'terrain_park_maintenance', $park['name'] . ' closed for maintenance: condition too low.');
                }

                $terrainParkModel->update($park['id'], [
                    'condition_pct' => $tpNewCondition,
                    'daily_visitors' => $tpDailyVisitors,
                    'status' => $tpNewStatus,
                ]);
            }

            // ==============================
            // PARKING & TRANSIT
            // ==============================
            $parkingModel = new \App\Models\ParkingModel();
            $userParking = $parkingModel->where('user_id', $userId)->findAll();
            $totalParkingCapacity = \App\Models\ParkingModel::getTotalCapacity($userParking);

            foreach ($userParking as $lot) {
                if ($lot['status'] === 'under_construction') {
                    $lotDaysLeft = $lot['build_days_left'] - 1;
                    if ($lotDaysLeft <= 0) {
                        $parkingModel->update($lot['id'], ['build_days_left' => 0, 'status' => 'open']);
                        log_activity($userId, 'parking_complete', $lot['name'] . ' construction complete!');
                    } else {
                        $parkingModel->update($lot['id'], ['build_days_left' => $lotDaysLeft]);
                    }
                    continue;
                }
                if ($lot['status'] === 'closed') {
                    $parkingModel->update($lot['id'], ['occupied' => 0, 'daily_revenue' => 0]);
                    continue;
                }

                $lotConfig = \App\Models\ParkingModel::getConfig($lot['parking_type']);
                $lotUpkeep = $lotConfig['upkeep'] ?? 0;
                $dayExpenses += $lotUpkeep;

                $lotConditionDecay = $lotConfig['condition_decay'] ?? 0.3;
                $lotNewCondition = max(0, $lot['condition_pct'] - $lotConditionDecay);

                // Cars, not visitors, and fewer when the fee is high (a fee of 100 used to fill the lot anyway)
                $lotOccupied = \App\Models\ParkingModel::carsParked($lot, $visitors, $totalParkingCapacity);
                $lotDailyRevenue = (int) round($lotOccupied * (float) $lot['fee_per_day'] * $revMult);
                $dayIncome += $lotDailyRevenue;

                $lotNewStatus = ($lotOccupied >= $lot['capacity'] && $lot['capacity'] > 0) ? 'full' : 'open';
                $parkingModel->update($lot['id'], [
                    'occupied' => $lotOccupied, 'daily_revenue' => $lotDailyRevenue,
                    'condition_pct' => $lotNewCondition, 'status' => $lotNewStatus,
                ]);
            }

            $carsArriving = (int) round($visitors / \App\Models\ParkingModel::PEOPLE_PER_CAR);
            if ($totalParkingCapacity > 0 && $carsArriving > $totalParkingCapacity) {
                log_activity($userId, 'parking_bottleneck', number_format($carsArriving - $totalParkingCapacity) . ' cars found no space in your lots: those parking fees were lost.');
            }

            // ==============================
            // ENERGY & WATER CONSTRUCTION + DECAY + GRID TARIFFS
            // ==============================
            // Source types, upkeep and wear come from resource_types (App\Libraries\Utilities), same as the pages
            $resourceConfig = ['energy' => \App\Libraries\Utilities::config('energy'), 'water' => \App\Libraries\Utilities::config('water')];

            $totalEnergyOutput = 0;
            $totalWaterOutput = 0;

            foreach (['energy_management' => 'energy', 'water_management' => 'water'] as $resTable => $resType) {
                $resSources = $db->table($resTable)->where('user_id', $userId)->get()->getResultArray();
                foreach ($resSources as $res) {
                    if ($res['status'] === 'under_construction') {
                        $resDays = $res['build_days_left'] - 1;
                        if ($resDays <= 0) {
                            $db->table($resTable)->where('id', $res['id'])->update(['build_days_left' => 0, 'status' => 'active']);
                            log_activity($userId, $resType . '_complete', $res['name'] . ' construction complete!');
                            notify($userId, $resType, $res['name'] . ' is ready!', 'Your ' . $res['name'] . ' has finished construction and is now active.', 'fa-solid fa-check-circle', '/' . $resType);
                        } else {
                            $db->table($resTable)->where('id', $res['id'])->update(['build_days_left' => $resDays]);
                        }
                        continue;
                    }
                    if ($res['status'] !== 'active') continue;

                    // Bill facility upkeep
                    $resCfg = $resourceConfig[$resType][$res['source_type']] ?? ['upkeep' => (int) $res['cost_per_day'], 'decay' => 0.2];
                    $dayExpenses += (int) round($resCfg['upkeep'] * $costMult);

                    // Track active generation
                    if ($resType === 'energy') $totalEnergyOutput += (int) ($res['output_kwh'] ?? 0);
                    if ($resType === 'water') $totalWaterOutput += (int) ($res['output_liters'] ?? 0);

                    $resDecay = $resCfg['decay'] * $decayMult;
                    $resNewCond = max(0, $res['condition_pct'] - $resDecay);
                    if ($resNewCond <= 0) {
                        $db->table($resTable)->where('id', $res['id'])->update(['condition_pct' => 0, 'status' => 'broken']);
                        notify($userId, $resType . '_breakdown', $res['name'] . ' broke down!', 'Repair it to restore ' . $resType . ' production.', 'fa-solid fa-triangle-exclamation', '/' . $resType);
                    } else {
                        $db->table($resTable)->where('id', $res['id'])->update(['condition_pct' => $resNewCond]);
                    }
                }
            }

            // Energy demand vs output: grid import if deficit. Difficulties that hide the energy/water
            // pages (easy) can't build sources, so they aren't billed for imports either.
            $hiddenPages = array_map('trim', explode(',', (string) ($diffConfig['hidden_pages'] ?? '')));
            $openLiftRows = $db->table('player_items')->select('subtype, length_meters')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->get()->getResultArray();
            $totalEnergyDemand = \App\Libraries\Utilities::energyDemand($activeSnowmakers, count($lights), $openLiftRows, $buildings)['total'];
            if ($totalEnergyDemand > $totalEnergyOutput && !in_array('energy', $hiddenPages, true)) {
                $energyDeficit = $totalEnergyDemand - $totalEnergyOutput;
                $gridTariff = \App\Libraries\Utilities::importCost('energy', $energyDeficit);
                $dayExpenses += $gridTariff;
                if ($gridTariff >= 250) {
                    log_activity($userId, 'energy_grid', 'Grid Import: Drew ' . number_format($energyDeficit) . ' kWh from municipal grid (' . currency($gridTariff) . ')', 'fa-solid fa-bolt');
                }
            }

            // Water demand vs output: municipal reservoir import if deficit
            if ($snowmakingWater > $totalWaterOutput && !in_array('water', $hiddenPages, true)) {
                $waterDeficit = $snowmakingWater - $totalWaterOutput;
                $dayExpenses += \App\Libraries\Utilities::importCost('water', $waterDeficit);
            }

            // ==============================
            // VIP GUEST ARRIVALS
            // ==============================
            $ratingData = function_exists('resortRating') ? resortRating($userId) : ['score' => 2];
            // Stars (1-5). The 0-200 'score' made the chance 100% and every VIP type possible for nearly everyone
            $resortRating = (int) ($ratingData['stars'] ?? 2);
            $vipMagnetBonus = in_array('vip_magnet', $activeBoostTypes) ? 100 : 0;
            // VIPs only visit a resort that had skiers today (they used to tip empty, slope-less resorts)
            $vipChance = $resortOpen && $visitors > 0 ? min(100, ($resortRating * 8) + $vipBonus + $vipMagnetBonus + ($visitors > 200 ? 10 : 0) + ($countTotal('manager') * 3)) : 0;
            if (rand(1, 100) <= $vipChance) {
                $vipTypes = [
                    ['type' => 'celebrity', 'name' => 'Celebrity Guest', 'icon' => 'fa-solid fa-star', 'cash_min' => 5000, 'cash_max' => 20000, 'rep' => 5],
                    ['type' => 'film_crew', 'name' => 'Film Production Crew', 'icon' => 'fa-solid fa-video', 'cash_min' => 10000, 'cash_max' => 50000, 'rep' => 10],
                    ['type' => 'influencer', 'name' => 'Social Media Influencer', 'icon' => 'fa-solid fa-camera', 'cash_min' => 2000, 'cash_max' => 8000, 'rep' => 8],
                    ['type' => 'ski_team', 'name' => 'National Ski Team', 'icon' => 'fa-solid fa-medal', 'cash_min' => 8000, 'cash_max' => 25000, 'rep' => 12],
                    ['type' => 'journalist', 'name' => 'Travel Journalist', 'icon' => 'fa-solid fa-newspaper', 'cash_min' => 1000, 'cash_max' => 5000, 'rep' => 15],
                    ['type' => 'corporate', 'name' => 'Corporate Retreat Group', 'icon' => 'fa-solid fa-briefcase', 'cash_min' => 15000, 'cash_max' => 40000, 'rep' => 3],
                    ['type' => 'royalty', 'name' => 'European Royalty', 'icon' => 'fa-solid fa-crown', 'cash_min' => 25000, 'cash_max' => 100000, 'rep' => 20],
                ];

                // Higher rating = chance of rarer VIPs
                $maxIndex = min(count($vipTypes) - 1, (int) floor($resortRating * 1.5));
                $vip = $vipTypes[rand(0, $maxIndex)];
                $vipCash = $v2 ? \App\Libraries\Economy::vipReward((int) round($ticketRevenue * $revMult), $vip['cash_max']) : rand($vip['cash_min'], $vip['cash_max']);
                $breakdown['vip'] = $vipCash;

                $vipNames = ['Von Schneider', 'Beaumont', 'Larsson', 'Tanaka', 'De Rossi', 'Petrov', 'Ashworth', 'Magnusson', 'Dubois', 'Van den Berg'];
                $vipFirstNames = ['Alexander', 'Isabella', 'Viktor', 'Sophia', 'Marcus', 'Elena', 'James', 'Yuki', 'Charlotte', 'Erik'];
                $vipFullName = $vipFirstNames[array_rand($vipFirstNames)] . ' ' . $vipNames[array_rand($vipNames)];

                $stayDays = rand(1, 5);
                $db->table('vip_guests')->insert([
                    'user_id' => $userId,
                    'name' => $vipFullName,
                    'vip_type' => $vip['type'],
                    'game_day_arrived' => $gameDay,
                    'days_remaining' => $stayDays,
                    'reward_amount' => $vipCash,
                    'reputation_bonus' => $vip['rep'],
                    'status' => 'visiting',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                $dayIncome += $vipCash;
                log_activity($userId, 'vip_arrival', $vip['name'] . ' ' . $vipFullName . ' has arrived! +' . number_format($vipCash) . '€', $vip['icon']);
                notify($userId, 'vip', $vip['name'] . ' arrived!', $vipFullName . ' is visiting your resort for ' . $stayDays . ' days. +' . number_format($vipCash) . '€', $vip['icon'], '/vip-guests');
            }

            // Process departing VIPs with satisfaction check
            $departingVips = $db->table('vip_guests')->where('user_id', $userId)->where('status', 'visiting')->get()->getResultArray();
            foreach ($departingVips as $vg) {
                $newDays = (int) $vg['days_remaining'] - 1;
                if ($newDays <= 0) {
                    $vType = $vg['vip_type'];
                    $vReqs = \App\Controllers\VipGuests::VIP_TYPES[$vType]['requirements'] ?? [];
                    $satisfied = true;
                    if (!empty($vReqs['min_slopes']) && $openSlopes < $vReqs['min_slopes']) $satisfied = false;
                    if (!empty($vReqs['min_staff']) && count($allStaff) < $vReqs['min_staff']) $satisfied = false;
                    if (!empty($vReqs['min_buildings']) && count($buildings) < $vReqs['min_buildings']) $satisfied = false;
                    if (!empty($vReqs['min_lifts']) && $openLifts < $vReqs['min_lifts']) $satisfied = false;

                    $finalStatus = $satisfied ? 'satisfied' : 'disappointed';
                    $db->table('vip_guests')->where('id', $vg['id'])->update(['status' => $finalStatus, 'days_remaining' => 0]);
                    if ($satisfied) {
                        $db->table('player_finances')->where('user_id', $userId)->set('reputation', 'reputation + ' . (int) $vg['reputation_bonus'], false)->update();
                        log_activity($userId, 'vip_satisfied', $vg['name'] . ' departed thoroughly satisfied with your resort! +' . (int)$vg['reputation_bonus'] . ' Reputation', 'fa-solid fa-star');
                    } else {
                        log_activity($userId, 'vip_departure', $vg['name'] . ' departed disappointed: the resort lacked the amenities they asked for.', 'fa-solid fa-plane-departure');
                    }
                } else {
                    $db->table('vip_guests')->where('id', $vg['id'])->update(['days_remaining' => $newDays]);
                }
            }

            // ==============================
            // SAFETY, PATROL & ACCIDENTS
            // ==============================
            $patrolStations = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'ski_patrol')->where('status', 'open')->get()->getResultArray();
            $patrolStaffCount = $countAssigned('ski_patrol');
            $medicCount = $countAssigned('medic');

            $patrolCapacity = array_sum(array_column($patrolStations, 'capacity'));
            $coverageRatio = $openSlopes > 0 ? min(100, round($patrolCapacity / $openSlopes * 100)) : 100;
            $safetyScore = min(100, round($coverageRatio * 0.4 + $patrolStaffCount * 8 + $medicCount * 12 + count($patrolStations) * 10));

            $accidentRisk = max(1, min(14, (int) round(12 - ($safetyScore / 10))));
            if ($visitors > 50 && $isWinter && rand(1, 100) <= $accidentRisk) {
                $isMinor = rand(1, 100) <= 65;
                if ($isMinor && ($medicCount > 0 || $patrolStaffCount > 0)) {
                    log_activity($userId, 'rescue', 'Ski patrol & medics promptly treated an injured skier on Slope #' . rand(1, max(1, $openSlopes)) . '. Fast response prevented liability.', 'fa-solid fa-kit-medical');
                } else {
                    $accidentCost = rand(StaffEffects::ACCIDENT_COST_MIN, StaffEffects::ACCIDENT_COST_MAX);
                    $liabilityPolicy = $db->table('insurance')->where('user_id', $userId)->where('active', 1)
                        ->groupStart()->where('policy_type', 'liability')->orLike('name', 'Liability')->groupEnd()
                        ->get()->getRowArray();
                    if ($liabilityPolicy) {
                        $claimPayout = (int) round($accidentCost * 0.85);
                        $netExpense = $accidentCost - $claimPayout;
                        $dayExpenses += $netExpense;
                        log_activity($userId, 'accident', 'Slope accident occurred (' . currency($accidentCost) . ' damage). Liability Insurance paid out ' . currency($claimPayout) . ' claim (net cost: ' . currency($netExpense) . ')', 'fa-solid fa-shield-halved');
                    } else {
                        $dayExpenses += $accidentCost;
                        log_activity($userId, 'accident', 'Slope accident on under-patrolled slope! Liability & medical damages: ' . currency($accidentCost) . ' (no insurance coverage)', 'fa-solid fa-triangle-exclamation');
                    }
                }
            }

            // ==============================
            // UPDATE FINANCES
            // ==============================
            // Standby Protection: if player is dormant, don't let unattended expenses push cash into negative
            if ($isDormant) {
                if ($cash < 25000) {
                    // If already below $25,000, expenses cannot exceed income so debt does not compound
                    $dayExpenses = min($dayExpenses, $dayIncome);
                } elseif (($cash + $dayIncome - $dayExpenses) < 25000) {
                    // Protect cash floor of $25,000 during caretaker standby
                    $dayExpenses = min($dayExpenses, max(0, $cash + $dayIncome - 25000));
                }
            }

            // Apply the day as a change to the current balance: the cash read at the start may be stale
            // if the player bought or sold something while the tick was running
            $newCash = $cash + $dayIncome - $dayExpenses;
            $financeUpdate = [
                'daily_visitors' => $visitors,
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            if ($v2) {
                $known = ($breakdown['tickets'] ?? 0) + ($breakdown['buildings'] ?? 0) + ($breakdown['vip'] ?? 0);
                $knownCost = ($breakdown['salaries'] ?? 0) + ($breakdown['building_upkeep'] ?? 0) + ($breakdown['lift_slope_running'] ?? 0);
                $financeUpdate['last_tick'] = json_encode($breakdown + [
                    'game_day' => $gameDay, 'visitors' => $visitors, 'income' => $dayIncome, 'expenses' => $dayExpenses,
                    'other_income' => $dayIncome - $known, 'other_costs' => $dayExpenses - $knownCost,
                ]);
            }
            $db->table('player_finances')->where('user_id', $userId)
                ->set('cash', 'cash + ' . ((int) $dayIncome - (int) $dayExpenses), false)
                ->set('total_income', 'total_income + ' . (int) $dayIncome, false)
                ->set('total_expenses', 'total_expenses + ' . (int) $dayExpenses, false)
                ->update($financeUpdate);

            // ==============================
            // FINANCIAL TRANSACTIONS LOG
            // ==============================
            if ($dayIncome > 0) {
                $db->table('financial_transactions')->insert([
                    'user_id' => $userId, 'game_day' => $gameDay,
                    'category' => 'Daily Income', 'description' => 'Tickets, buildings, parking, ski school, scenic lifts, VIP guests',
                    'amount' => $dayIncome, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            if ($dayExpenses > 0) {
                $db->table('financial_transactions')->insert([
                    'user_id' => $userId, 'game_day' => $gameDay,
                    'category' => 'Daily Expenses', 'description' => 'Staff, equipment, upkeep, utilities, loans, insurance, compliance',
                    'amount' => $dayExpenses, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // ==============================
            // DAILY REPORT
            // ==============================
            $dormantTag = $isDormant ? ' [Standby Protected]' : '';
            log_activity($userId, 'Daily Report', "Day {$gameDay}{$dormantTag}: +" . number_format($dayIncome) . '€ income, -' . number_format($dayExpenses) . '€ expenses, ' . number_format($visitors) . ' visitors', 'fa-solid fa-chart-line');

            CLI::write("  Income: {$dayIncome} | Expenses: {$dayExpenses}{$dormantTag} | Visitors: {$visitors} | Cash: {$newCash}", "white");

            // Achievements: progress and feature unlocks shouldn't wait for a visit to /achievements
            if (($user['status'] ?? '') !== 'demo') \App\Libraries\Achievements::sync($userId);
                if ($db->transStatus() === false) {
                    $db->transRollback();
                    CLI::write("  Transaction failed for user {$userId}, rolled back.", "red");
                    log_message("error", "GameTick user {$userId} transaction status failed and was rolled back.");
                } else {
                    $db->transCommit();
                    clearResortRatingCache($userId);
                }
            } catch (\Throwable $e) {
                $db->transRollback();
                $db->resetTransStatus();
                CLI::write("  ERROR processing user {$userId}: " . $e->getMessage(), "red");
                log_message("error", "GameTick user {$userId} failed: " . $e->getMessage());
                if (class_exists('\\Sentry\\SentrySdk')) {
                    \Sentry\captureException($e);
                }
            }
        }
            $offset += $batchSize;
        }

        // Storage Maintenance: Prune old activity logs & financial records to keep storage bounded
        try {
            $db->table('activity_log')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();
            $db->table('financial_transactions')->where('created_at <', date('Y-m-d H:i:s', strtotime('-180 days')))->delete();
            $db->table('ticket_sales')->where('created_at <', date('Y-m-d H:i:s', strtotime('-180 days')))->delete();
            // Notifications: read ones after 30 days, everything after 90
            $db->table('notifications')->where('is_read', 1)->where('created_at <', date('Y-m-d H:i:s', strtotime('-30 days')))->delete();
            $db->table('notifications')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();
        } catch (\Throwable $e) {
            // Non-blocking maintenance cleanup
        }

        if ($checkInId !== null) {
            try {
                \Sentry\captureCheckIn(
                    'gametick-cron',
                    \Sentry\CheckInStatus::ok(),
                    null,
                    $checkInId
                );
            } catch (\Throwable $t) {}
        }

        CLI::write('Game tick complete!', 'green');
    }
}
