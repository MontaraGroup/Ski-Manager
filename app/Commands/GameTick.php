<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class GameTick extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'game:tick';
    protected $description = 'Process daily game tick — revenue, expenses, weather, morale, conditions';

    public function run(array $params)
    {
        $db = db_connect();
        $startDate = '2026-06-01';
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        CLI::write("Game Tick — Day {$gameDay}", 'green');

        $users = $db->table('users')->get()->getResultArray();

        foreach ($users as $user) {
            $userId = (int) $user['id'];
            CLI::write("Processing user {$userId}...", 'yellow');

            $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            if (!$finance) {
                $db->table('player_finances')->insert(['user_id' => $userId, 'cash' => 500000, 'total_income' => 0, 'total_expenses' => 0]);
                $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            }

            $cash = (int) $finance['cash'];
            $dayIncome = 0;
            $dayExpenses = 0;

            // --- Staff salaries ---
            $staff = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            $staffCost = array_sum(array_column($staff, 'salary'));
            $dayExpenses += $staffCost;

            // --- Staff morale decay ---
            foreach ($staff as $s) {
                $newMorale = max(0, (int) $s['morale'] - 2);
                $db->table('staff')->where('id', $s['id'])->update(['morale' => $newMorale]);
                if ($newMorale <= 10 && rand(1, 100) <= 20) {
                    $db->table('staff')->where('id', $s['id'])->update(['status' => 'fired']);
                    $db->table('activity_log')->insert([
                        'user_id' => $userId, 'game_day' => $gameDay,
                        'category' => 'Staff', 'message' => $s['name'] . ' quit due to low morale',
                        'icon' => 'fa-solid fa-user-minus', 'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            }

            // --- Building revenue and upkeep ---
            $buildings = $db->table('buildings')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray();
            foreach ($buildings as $b) {
                $dayIncome += (int) $b['revenue_per_day'];
                $dayExpenses += (int) $b['upkeep_per_day'];
                $newCondition = max(0, (int) $b['condition_pct'] - rand(0, 2));
                $db->table('buildings')->where('id', $b['id'])->update(['condition_pct' => $newCondition]);
                if ($newCondition <= 0) {
                    $db->table('buildings')->where('id', $b['id'])->update(['status' => 'broken']);
                }
            }

            // --- Marketing campaigns ---
            $campaigns = $db->table('marketing_campaigns')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($campaigns as $c) {
                $dayExpenses += (int) $c['daily_cost'];
                $remaining = (int) $c['days_remaining'] - 1;
                if ($remaining <= 0) {
                    $db->table('marketing_campaigns')->where('id', $c['id'])->update(['status' => 'expired', 'days_remaining' => 0]);
                } else {
                    $db->table('marketing_campaigns')->where('id', $c['id'])->update(['days_remaining' => $remaining]);
                }
            }

            // --- Snowmaking energy ---
            $cannons = $db->table('snow_cannons')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($cannons as $c) {
                $dayExpenses += (int) $c['energy_cost'];
                $newCond = max(0, (int) $c['condition_pct'] - rand(1, 3));
                $db->table('snow_cannons')->where('id', $c['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('snow_cannons')->where('id', $c['id'])->update(['status' => 'broken']);
                }
            }

            // --- Night skiing energy ---
            $lights = $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($lights as $l) {
                $dayExpenses += (int) $l['energy_cost'];
                $newCond = max(0, (int) $l['condition_pct'] - rand(0, 2));
                $db->table('night_skiing')->where('id', $l['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('night_skiing')->where('id', $l['id'])->update(['status' => 'broken']);
                }
            }

            // --- Equipment fuel ---
            $equipment = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
            foreach ($equipment as $e) {
                $dayExpenses += (int) $e['fuel_cost'];
                $newCond = max(0, (int) $e['condition_pct'] - rand(1, 3));
                $db->table('equipment')->where('id', $e['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('equipment')->where('id', $e['id'])->update(['status' => 'broken']);
                }
            }

            // --- Insurance premiums ---
            $insurance = $db->table('insurance')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
            foreach ($insurance as $i) {
                $dayExpenses += (int) $i['premium_per_day'];
            }

            // --- Government compliance ---
            $regs = $db->table('regulations')->where('user_id', $userId)->get()->getResultArray();
            foreach ($regs as $r) {
                if ($r['compliant']) {
                    $dayExpenses += (int) $r['compliance_cost'];
                } else {
                    if (rand(1, 100) <= 5) {
                        $fine = (int) $r['penalty_risk'];
                        $dayExpenses += $fine;
                        $db->table('activity_log')->insert([
                            'user_id' => $userId, 'game_day' => $gameDay,
                            'category' => 'Government', 'message' => 'Inspection fine: ' . $r['name'] . ' — ' . number_format($fine) . '€',
                            'icon' => 'fa-solid fa-gavel', 'created_at' => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }

            // --- Loan payments ---
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

            // --- Lift/slope condition decay ---
            $items = $db->table('player_items')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray();
            foreach ($items as $item) {
                $decay = rand(0, 2);
                $newCond = max(0, (int) $item['condition_pct'] - $decay);
                $db->table('player_items')->where('id', $item['id'])->update(['condition_pct' => $newCond]);
                if ($newCond <= 0) {
                    $db->table('player_items')->where('id', $item['id'])->update(['status' => 'broken']);
                }
            }

            // --- Ticket revenue estimate ---
            $openSlopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->countAllResults();
            $openLifts = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults();
            $tickets = $db->table('lift_tickets')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
            $avgPrice = count($tickets) > 0 ? array_sum(array_column($tickets, 'price')) / count($tickets) : 0;

            $baseVisitors = ($openSlopes * 50) + ($openLifts * 30);
            $marketingBoost = array_sum(array_column($campaigns, 'visitor_boost'));
            $visitors = (int) round($baseVisitors * (1 + $marketingBoost / 100));
            $ticketRevenue = (int) round($visitors * $avgPrice * 0.6);
            $dayIncome += $ticketRevenue;

            // --- Update finances ---
            $newCash = $cash + $dayIncome - $dayExpenses;
            $db->table('player_finances')->where('user_id', $userId)->update([
                'cash' => $newCash,
                'total_income' => (int) $finance['total_income'] + $dayIncome,

                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            // --- Log transaction ---
            if ($dayIncome > 0) {
                $db->table('financial_transactions')->insert([
                    'user_id' => $userId, 'game_day' => $gameDay,
                    'category' => 'Daily Income', 'description' => 'Tickets, buildings, and other revenue',
                    'amount' => $dayIncome, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            if ($dayExpenses > 0) {
                $db->table('financial_transactions')->insert([
                    'user_id' => $userId, 'game_day' => $gameDay,
                    'category' => 'Daily Expenses', 'description' => 'Staff, energy, upkeep, loans, insurance',
                    'amount' => $dayExpenses, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
                ]);
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
                } else {
                    $terrainParkModel->update($park['id'], ['build_days_left' => $daysLeft]);
                }
                continue;
            }

            if ($park['status'] !== 'open') {
                continue;
            }

            $tpConfig = \App\Models\TerrainParkModel::getConfig($park['park_type'], $park['size']);
            $tpUpkeep = $tpConfig['upkeep'] ?? 0;
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$tpUpkeep}", false)->update();

            $parkCrewCount = $db->table('staff')->where('user_id', $userId)->where('role', 'park_crew')->where('status', 'active')->countAllResults(false);
            $tpBaseDecay = 2.0;
            $tpCrewReduction = min($parkCrewCount * 0.4, 1.5);
            $tpDecay = max(0.3, $tpBaseDecay - $tpCrewReduction);
            $tpNewCondition = max(0, $park['condition_pct'] - $tpDecay);

            $tpConditionMult = $tpNewCondition / 100;
            $tpDailyVisitors = round(($tpConfig['popularity_base'] ?? 0) * $tpConditionMult * (1 + $park['popularity'] / 100));

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
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash - {$lotUpkeep}", false)->update();

            $lotConditionDecay = $lotConfig['condition_decay'] ?? 0.3;
            $lotNewCondition = max(0, $lot['condition_pct'] - $lotConditionDecay);

            $lotShare = $totalParkingCapacity > 0
                ? round($visitors * ($lot['capacity'] / $totalParkingCapacity))
                : 0;
            $lotOccupied = min($lotShare, $lot['capacity']);

            $lotDailyRevenue = $lotOccupied * $lot['fee_per_day'];
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash + {$lotDailyRevenue}", false)->update();

            $lotNewStatus = ($lotOccupied >= $lot['capacity'] && $lot['capacity'] > 0) ? 'full' : 'open';

            $parkingModel->update($lot['id'], [
                'occupied' => $lotOccupied,
                'daily_revenue' => $lotDailyRevenue,
                'condition_pct' => $lotNewCondition,
                'status' => $lotNewStatus,
            ]);
        }

        if ($totalParkingCapacity > 0 && isset($visitors) && $visitors > $totalParkingCapacity) {
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
        // EQUIPMENT DURABILITY
        // ==============================
        $userEquipment = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        foreach ($userEquipment as $eq) {
            $eqDecay = $eq['equipment_type'] === 'groomer' ? rand(2, 5) : rand(1, 3);
            $eqNewCond = max(0, (int) $eq['condition_pct'] - $eqDecay);
            if ($eqNewCond <= 0) {
                $db->table('equipment')->where('id', $eq['id'])->update(['condition_pct' => 0, 'status' => 'broken']);
                log_activity($userId, 'equipment_broken', $eq['name'] . ' has broken down!');
                notify($userId, 'equipment', $eq['name'] . ' broke down!', 'Repair it before it can operate again.', 'fa-solid fa-triangle-exclamation', '/equipment');
            } elseif ($eqNewCond <= 20 && (int) $eq['condition_pct'] > 20) {
                $db->table('equipment')->where('id', $eq['id'])->update(['condition_pct' => $eqNewCond]);
                notify($userId, 'equipment', $eq['name'] . ' needs maintenance', 'Condition at ' . $eqNewCond . '%. Repair soon.', 'fa-solid fa-wrench', '/equipment');
            } else {
                $db->table('equipment')->where('id', $eq['id'])->update(['condition_pct' => $eqNewCond]);
            }
        }
            // --- Daily activity log ---
            $db->table('activity_log')->insert([
                'user_id' => $userId, 'game_day' => $gameDay,
                'category' => 'Daily Report',
                'message' => "Day {$gameDay}: +" . number_format($dayIncome) . '€ income, -' . number_format($dayExpenses) . '€ expenses, ' . $visitors . ' visitors',
                'icon' => 'fa-solid fa-chart-line', 'created_at' => date('Y-m-d H:i:s'),
            ]);

            CLI::write("  Income: {$dayIncome} | Expenses: {$dayExpenses} | Visitors: {$visitors} | Cash: {$newCash}", 'white');
        }

        // --- Generate weather for tomorrow if not exists ---
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
            $seasonDay = (($gameDay - 1) % 135) + 1;
            $isDeepWinter = $seasonDay >= 30 && $seasonDay <= 80;
            $isSummer = $seasonDay > 100;
            if ($isSummer) { $temp = mt_rand(10, 25); } elseif ($isDeepWinter) { $temp = mt_rand(-15, -2); } else { $temp = mt_rand(-8, 5); }
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
