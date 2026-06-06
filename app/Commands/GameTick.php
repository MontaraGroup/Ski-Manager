<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GameTick extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'game:tick';
    protected $description = 'Process daily game tick — revenue, expenses, weather, morale, conditions, VIP guests';

    public function run(array $params)
    {
        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        $seasonDay = (($gameDay - 1) % getSeasonLength()) + 1;
        $isWinter = $seasonDay <= getWinterDays();

        CLI::write("Game Tick — Day {$gameDay} (Season day {$seasonDay}, " . ($isWinter ? 'Winter' : 'Summer') . ")", 'green');

        $users = $db->table('users')->get()->getResultArray();

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            CLI::write("Processing user {$userId}...", 'yellow');

            $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            if (!$finance) {
                $db->table('player_finances')->insert(['user_id' => $userId, 'cash' => 500000, 'total_income' => 0, 'total_expenses' => 0, 'difficulty' => 'standard', 'resort_map' => 'Vail']);
                $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            }

            $cash = (int) $finance['cash'];
            $dayIncome = 0;
            $dayExpenses = 0;

            // Load difficulty config
            $diff = $finance['difficulty'] ?? 'standard';
            $diffConfig = [];
            $diffRows = $db->table('difficulty_config')->where('difficulty', $diff)->get()->getResultArray();
            foreach ($diffRows as $dr) { $diffConfig[$dr['config_key']] = $dr['config_value']; }
            $revMult = ((int) ($diffConfig['revenue_multiplier'] ?? 100)) / 100;
            $costMult = ((int) ($diffConfig['cost_multiplier'] ?? 100)) / 100;
            $decayMult = ((int) ($diffConfig['decay_multiplier'] ?? 100)) / 100;
            $moraleDec = (int) ($diffConfig['morale_decay'] ?? 2);
            $inspChance = (int) ($diffConfig['inspection_chance'] ?? 5);
            $visMult = ((int) ($diffConfig['visitor_multiplier'] ?? 100)) / 100;
            $vipBonus = (int) ($diffConfig['vip_chance_bonus'] ?? 0);

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

            foreach ($allStaff as $s) {
                $moraleChange = -$moraleDec;
                if (!empty($s['assigned_to'])) $moraleChange += 1;
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
            foreach ($buildings as $b) {
                $revenue = (int) $b['revenue_per_day'];
                $type = $b['building_type'];

                // Staff assignment bonuses: +15% revenue per assigned staff
                if ($type === 'hotel') $revenue = (int) round($revenue * (1 + $countAssigned('receptionist') * 0.15));
                if ($type === 'restaurant') $revenue = (int) round($revenue * (1 + $countAssigned('chef') * 0.10));

                $dayIncome += (int) round($revenue * $revMult);
                $dayExpenses += (int) round($b['upkeep_per_day'] * $costMult);
                $newCondition = max(0, (int) $b['condition_pct'] - (int) round(rand(0, 2) * $decayMult));
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
            // EQUIPMENT (groomers + snowmakers) — fuel, decay, staff bonuses
            // ==============================
            $equipment = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($equipment as $e) {
                $dayExpenses += (int) round($e['fuel_cost'] * $costMult);

                // Assigned operators reduce decay
                $baseDecay = (int) round(($e['equipment_type'] === 'groomer' ? rand(2, 5) : rand(1, 3)) * $decayMult);
                if ($e['equipment_type'] === 'groomer' && $countAssigned('groomer') > 0) {
                    $baseDecay = max(1, $baseDecay - 1);
                }
                if ($e['equipment_type'] === 'snowmaker' && $countAssigned('snowmaker') > 0) {
                    $baseDecay = max(1, $baseDecay - 1);
                }

                $eqNewCond = max(0, (int) $e['condition_pct'] - $baseDecay);
                if ($eqNewCond <= 0) {
                    $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => 0, 'status' => 'broken']);
                    log_activity($userId, 'equipment_broken', $e['name'] . ' has broken down!', 'fa-solid fa-triangle-exclamation');
                    notify($userId, 'equipment', $e['name'] . ' broke down!', 'Repair it before it can operate again.', 'fa-solid fa-triangle-exclamation', '/equipment');
                } elseif ($eqNewCond <= 20 && (int) $e['condition_pct'] > 20) {
                    $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => $eqNewCond]);
                    notify($userId, 'equipment', $e['name'] . ' needs maintenance', 'Condition at ' . $eqNewCond . '%. Repair soon.', 'fa-solid fa-wrench', '/equipment');
                } else {
                    $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => $eqNewCond]);
                }
            }

            // Snowmaking energy costs from equipment (snowmakers)
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
                        log_activity($userId, 'inspection', 'Inspection fine: ' . $r['name'] . ' — ' . number_format($fine) . '€', 'fa-solid fa-gavel');
                    }
                }
            }

            // ==============================
            // LOAN PAYMENTS
            // ==============================
            $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($loans as $loan) {
                $payment = (int) $loan['daily_payment'];
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
            foreach ($items as $item) {
                $decay = rand(0, 2);
                if ($item['item_type'] === 'lift' && $countAssigned('mechanic') > 0) {
                    $decay = max(0, $decay - 1);
                }
                $newCond = max(0, (int) $item['condition_pct'] - $decay);
                $db->table('player_items')->where('id', $item['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('player_items')->where('id', $item['id'])->update(['status' => 'broken']);
                }
            }

            // ==============================
            // SNOW QUALITY UPDATE
            // ==============================
            $activeGroomers = count(array_filter($equipment, fn($e) => $e["equipment_type"] === "groomer" && $e["status"] === "active"));
            $hasSnowmaking = count($activeSnowmakers) > 0;
            $slopesForQuality = $db->table("player_items")->where("user_id", $userId)->where("item_type", "slope")->where("status", "open")->get()->getResultArray();
            foreach ($slopesForQuality as $sl) {
                $q = "packed";
                if (($weather["snowfall"] ?? 0) >= 10) { $q = "powder"; }
                elseif (($weather["snowfall"] ?? 0) >= 1) { $q = $activeGroomers > 0 ? "groomed" : "packed"; }
                elseif (($weather["condition_name"] ?? "") === "Freezing Rain") { $q = "icy"; }
                elseif (($weather["condition_name"] ?? "") === "Sunny" && ($weather["temp"] ?? -5) >= 0) { $q = $hasSnowmaking ? "packed" : "bare"; }
                elseif ($activeGroomers > 0) { $q = "groomed"; }
                $db->table("player_items")->where("id", $sl["id"])->update(["snow_quality" => $q]);
            }

            // ==============================
            // VISITOR CALCULATION (with staff bonuses)
            // ==============================
            $openSlopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->countAllResults();
            $openLifts = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults();
            $tickets = $db->table('lift_tickets')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
            $avgPrice = count($tickets) > 0 ? array_sum(array_column($tickets, 'price')) / count($tickets) : 0;

            $baseVisitors = ($openSlopes * 50) + ($openLifts * 30);
            $marketingBoost = array_sum(array_column($campaigns, 'visitor_boost'));
            // Manager boost: +5% per assigned manager
            $managerBoost = $countAssigned('manager') * 5;
            // Instructor boost: attracts families
            $instructorBoost = $countAssigned('instructor') * 3;
            // Patrol boost: safety reputation
            $patrolBoost = $countAssigned('ski_patrol') * 2;

            $snowQualityBonus = 0; foreach ($slopesForQuality as $sq) { $snowQualityBonus += match($sq["snow_quality"] ?? "packed") { "powder" => 3, "groomed" => 2, "packed" => 0, "icy" => -2, "bare" => -5, default => 0 }; }
            $totalBoost = $marketingBoost + $managerBoost + $instructorBoost + $patrolBoost + $snowQualityBonus;
            $visitors = (int) round($baseVisitors * (1 + $totalBoost / 100) * $visMult);

            // Summer reduction
            if (!$isWinter) $visitors = (int) round($visitors * 0.15);

            $ticketRevenue = (int) round($visitors * $avgPrice * 0.6);
            $dayIncome += $ticketRevenue;

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
                $tpCrewReduction = min($parkCrewCount * 0.4, 1.5);
                $tpDecay = max(0.3, $tpBaseDecay - $tpCrewReduction);
                $tpNewCondition = max(0, $park['condition_pct'] - $tpDecay);

                $tpConditionMult = $tpNewCondition / 100;
                $tpDailyVisitors = round(($tpConfig['popularity_base'] ?? 0) * $tpConditionMult * (1 + ($park['popularity'] ?? 0) / 100));

                $tpNewStatus = $tpNewCondition < 30 ? 'maintenance' : 'open';
                if ($tpNewStatus === 'maintenance' && $park['status'] === 'open') {
                    log_activity($userId, 'terrain_park_maintenance', $park['name'] . ' closed for maintenance — condition too low.');
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

                $lotShare = $totalParkingCapacity > 0 ? round($visitors * ($lot['capacity'] / $totalParkingCapacity)) : 0;
                $lotOccupied = min($lotShare, $lot['capacity']);
                $lotDailyRevenue = $lotOccupied * $lot['fee_per_day'];
                $dayIncome += $lotDailyRevenue;

                $lotNewStatus = ($lotOccupied >= $lot['capacity'] && $lot['capacity'] > 0) ? 'full' : 'open';
                $parkingModel->update($lot['id'], [
                    'occupied' => $lotOccupied, 'daily_revenue' => $lotDailyRevenue,
                    'condition_pct' => $lotNewCondition, 'status' => $lotNewStatus,
                ]);
            }

            if ($totalParkingCapacity > 0 && $visitors > $totalParkingCapacity) {
                $turnedAway = $visitors - $totalParkingCapacity;
                log_activity($userId, 'parking_bottleneck', number_format($turnedAway) . ' visitors turned away — not enough parking.');
            }

            // ==============================
            // ENERGY & WATER CONSTRUCTION + DECAY
            // ==============================
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
                    $resDecay = $resType === 'energy' ? 0.3 : 0.2;
                    $resNewCond = max(0, $res['condition_pct'] - $resDecay);
                    if ($resNewCond <= 0) {
                        $db->table($resTable)->where('id', $res['id'])->update(['condition_pct' => 0, 'status' => 'broken']);
                        notify($userId, $resType, $res['name'] . ' broke down!', 'Repair it to restore ' . $resType . ' production.', 'fa-solid fa-triangle-exclamation', '/' . $resType);
                    } else {
                        $db->table($resTable)->where('id', $res['id'])->update(['condition_pct' => $resNewCond]);
                    }
                }
            }

            // ==============================
            // VIP GUEST ARRIVALS
            // ==============================
            $ratingData = function_exists('resortRating') ? resortRating($userId) : ['score' => 2];
            $resortRating = (int) ($ratingData['score'] ?? $ratingData['stars'] ?? 2);
            $vipChance = min(50, ($resortRating * 8) + $vipBonus + ($visitors > 200 ? 10 : 0) + ($countTotal('manager') * 3));
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
                $vipCash = rand($vip['cash_min'], $vip['cash_max']);

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
                notify($userId, 'vip', $vip['name'] . ' arrived!', $vipFullName . ' is visiting your resort for ' . $stayDays . ' days. +' . number_format($vipCash) . '€', $vip['icon'], '/dashboard');
            }

            // Process departing VIPs
            $departingVips = $db->table('vip_guests')->where('user_id', $userId)->where('status', 'visiting')->get()->getResultArray();
            foreach ($departingVips as $vg) {
                $newDays = (int) $vg['days_remaining'] - 1;
                if ($newDays <= 0) {
                    $db->table('vip_guests')->where('id', $vg['id'])->update(['status' => 'departed', 'days_remaining' => 0]);
                    log_activity($userId, 'vip_departure', $vg['name'] . ' has departed. Thanks for visiting!', 'fa-solid fa-plane-departure');
                } else {
                    $db->table('vip_guests')->where('id', $vg['id'])->update(['days_remaining' => $newDays]);
                }
            }

            // ==============================
            // UPDATE FINANCES
            // ==============================
            $newCash = $cash + $dayIncome - $dayExpenses;
            $db->table('player_finances')->where('user_id', $userId)->update([
                'cash' => $newCash,
                'total_income' => (int) $finance['total_income'] + $dayIncome,
                'total_expenses' => (int) $finance['total_expenses'] + $dayExpenses,
                'daily_visitors' => $visitors,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // ==============================
            // FINANCIAL TRANSACTIONS LOG
            // ==============================
            if ($dayIncome > 0) {
                $db->table('financial_transactions')->insert([
                    'user_id' => $userId, 'game_day' => $gameDay,
                    'category' => 'Daily Income', 'description' => 'Tickets, buildings, parking, VIP guests',
                    'amount' => $dayIncome, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            if ($dayExpenses > 0) {
                $db->table('financial_transactions')->insert([
                    'user_id' => $userId, 'game_day' => $gameDay,
                    'category' => 'Daily Expenses', 'description' => 'Staff, equipment, upkeep, loans, insurance, compliance',
                    'amount' => $dayExpenses, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // ==============================
            // DAILY REPORT
            // ==============================
            log_activity($userId, 'Daily Report', "Day {$gameDay}: +" . number_format($dayIncome) . '€ income, -' . number_format($dayExpenses) . '€ expenses, ' . number_format($visitors) . ' visitors', 'fa-solid fa-chart-line');

            CLI::write("  Income: {$dayIncome} | Expenses: {$dayExpenses} | Visitors: {$visitors} | Cash: {$newCash}", 'white');
        }

        // ==============================
        // WEATHER GENERATION
        // ==============================
        $tomorrow = $gameDay + 1;
        $weatherExists = $db->table('weather')->where('game_day', $tomorrow)->countAllResults();
        if (!$weatherExists) {
            CLI::write("Generating weather for day {$tomorrow}...", 'cyan');
            $seed = crc32('skimanager-weather-day-' . $tomorrow);
            mt_srand($seed);
            $conditions = ['Sunny', 'Partly Cloudy', 'Cloudy', 'Light Snow', 'Heavy Snow', 'Blizzard', 'Freezing Rain'];
            $weights = [15, 20, 20, 25, 10, 5, 5];
            $roll = mt_rand(1, 100);
            $cumulative = 0;
            $condition = 'Cloudy';
            foreach ($conditions as $i => $c) {
                $cumulative += $weights[$i];
                if ($roll <= $cumulative) { $condition = $c; break; }
            }
            $tomorrowSeasonDay = (($tomorrow - 1) % getSeasonLength()) + 1;
            $winterDays = getWinterDays(); $isDeepWinter = $tomorrowSeasonDay >= 30 && $tomorrowSeasonDay <= ($winterDays - 20);
            $isTomorrowSummer = $tomorrowSeasonDay > getWinterDays();
            if ($isTomorrowSummer) { $temp = mt_rand(12, 28); } elseif ($isDeepWinter) { $temp = mt_rand(-10, 0); } else { $temp = mt_rand(-5, 8); }
            $wind = mt_rand(5, 30);
            $snowfall = in_array($condition, ['Light Snow', 'Heavy Snow', 'Blizzard']) ? mt_rand(1, 20) : 0;
            $prev = $db->table('weather')->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();
            $prevBase = $prev ? (int) $prev['snow_base'] : 50;
            $snowBase = max(0, $prevBase + $snowfall - ($condition === 'Sunny' ? mt_rand(1, 3) : 0));
            $visMap = ['Sunny' => 'Excellent', 'Partly Cloudy' => 'Good', 'Cloudy' => 'Good', 'Light Snow' => 'Moderate', 'Heavy Snow' => 'Poor', 'Blizzard' => 'Very Poor', 'Freezing Rain' => 'Poor'];

            $forecast = [];
            for ($d = 1; $d <= 5; $d++) {
                mt_srand(crc32('skimanager-weather-day-' . ($tomorrow + $d)));
                $fc = $conditions[mt_rand(0, count($conditions) - 1)];
                $forecast[] = ['day' => $d, 'temp' => $temp + mt_rand(-3, 3), 'condition' => $fc, 'snowfall' => in_array($fc, ['Light Snow', 'Heavy Snow', 'Blizzard']) ? mt_rand(1, 20) : 0];
            }

            $db->table('weather')->insert([
                'game_day' => $tomorrow, 'temp' => $temp, 'condition_name' => $condition,
                'wind' => $wind, 'snowfall' => $snowfall, 'visibility' => $visMap[$condition] ?? 'Good',
                'humidity' => mt_rand(40, 95), 'snow_base' => $snowBase,
                'forecast' => json_encode($forecast), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        CLI::write('Game tick complete!', 'green');
    }
}
