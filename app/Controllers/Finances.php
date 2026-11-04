<?php

namespace App\Controllers;

use App\Models\FinanceModel;
use App\Models\TransactionModel;
use App\Models\StaffModel;
use App\Models\MarketingModel;
use App\Models\NightSkiingModel;

class Finances extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $financeModel = new FinanceModel();
        $transModel = new TransactionModel();

        $finance = $financeModel->where('user_id', $userId)->first();
        if (!$finance) {
            $diff = session('difficulty') ?? 'standard';
            $startingCash = match($diff) { 'easy' => 1000000, 'hard' => 200000, default => 500000 };
            $financeModel->insert(['user_id' => $userId, 'cash' => $startingCash, 'difficulty' => $diff]);
            $finance = $financeModel->where('user_id', $userId)->first();
        }

        $staffModel = new StaffModel();
        $marketingModel = new MarketingModel();
        $lightModel = new NightSkiingModel();
        $db = db_connect();

        $staff = $staffModel->where('user_id', $userId)->where('status', 'active')->findAll();
        $campaigns = $marketingModel->where('user_id', $userId)->where('status', 'active')->findAll();
        $cannons = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->where('status', 'active')->get()->getResultArray();
        $lights = $lightModel->where('user_id', $userId)->where('status', 'active')->findAll();

        $dailySalaries = array_sum(array_column($staff, 'salary'));
        $dailyMarketing = array_sum(array_column($campaigns, 'daily_cost'));
        $dailyCannons = array_sum(array_column($cannons, 'energy_cost'));
        $dailyLights = array_sum(array_column($lights, 'energy_cost'));
        $totalDailyExpenses = $dailySalaries + $dailyMarketing + $dailyCannons + $dailyLights;

        $recentTransactions = $transModel->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(20)->findAll();

        $startDate = getSeasonStartDate();
        $today = date('Y-m-d');
        $gameDay = max(1, (int) ((strtotime($today) - strtotime($startDate)) / 86400) + 1);

        $db = db_connect();
        $lastIncome = $db->table('financial_transactions')->where('user_id', $userId)->where('type', 'income')->where('game_day', $gameDay)->where('created_at >=', getSeasonStartDate() . ' 00:00:00')->selectSum('amount')->get()->getRowArray();
        $prevIncome = $db->table('financial_transactions')->where('user_id', $userId)->where('type', 'income')->where('game_day', max(1, $gameDay - 1))->where('created_at >=', getSeasonStartDate() . ' 00:00:00')->selectSum('amount')->get()->getRowArray();
        $estimatedIncome = (int) ($lastIncome['amount'] ?? $prevIncome['amount'] ?? 0);

        $ticketIncome = 0;
        $tickets = $db->table('lift_tickets')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
        $visitors = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $visitorCount = (int) ($visitors['daily_visitors'] ?? 0);
        foreach ($tickets as $tk) {
            if ($tk['ticket_type'] === 'full_day') $ticketIncome = (int) $tk['price'] * $visitorCount;
        }

        $hotelIncome = 0;
        $hotels = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'hotel')->where('status', 'open')->get()->getResultArray();
        foreach ($hotels as $h) { $hotelIncome += (int) ($h['revenue_per_day'] ?? 0); }

        $restaurantIncome = 0;
        $restaurants = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'restaurant')->where('status', 'open')->get()->getResultArray();
        foreach ($restaurants as $r) { $restaurantIncome += (int) ($r['revenue_per_day'] ?? 0); }

        $rentalIncome = 0;
        $rentals = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'rental')->where('status', 'open')->get()->getResultArray();
        foreach ($rentals as $r) { $rentalIncome += (int) ($r['revenue_per_day'] ?? 0); }

        // Economy v2: last tick's real ticket revenue, and buildings earn by how full they were
        $lastTick = featureEnabled('economy_v2') ? json_decode((string) ($visitors['last_tick'] ?? ''), true) : null;
        if ($lastTick) {
            $ticketIncome = (int) ($lastTick['tickets'] ?? 0);
            $occ = $lastTick['occupancy'] ?? [];
            $hotelIncome = (int) round($hotelIncome * ($occ['hotel'] ?? 0));
            $restaurantIncome = (int) round($restaurantIncome * ($occ['restaurant'] ?? 0));
            $rentalIncome = (int) round($rentalIncome * ($occ['rental'] ?? 0));
        }

        $loanPayments = 0;
        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        foreach ($loans as $l) { $loanPayments += (int) ($l['daily_payment'] ?? 0); }

        $equipmentCost = 0;
        $equip = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        foreach ($equip as $e) { $equipmentCost += (int) ($e['fuel_cost'] ?? $e['daily_cost'] ?? 0); }

        // Totals as the old view worked them out: the rows above are estimates; with economy v2 the
        // last tick's real totals win and what the rows don't cover shows as "other"
        $totalIncome = $ticketIncome + $hotelIncome + $restaurantIncome + $rentalIncome;
        $totalExpenses = $totalDailyExpenses + $equipmentCost + $loanPayments;
        $otherIncome = $liftSlopeRunning = $otherCosts = null;
        if (!empty($lastTick)) {
            $otherIncome = max(0, (int) $lastTick['income'] - $totalIncome);
            $totalIncome = (int) $lastTick['income'];
            $liftSlopeRunning = (int) ($lastTick['lift_slope_running'] ?? 0);
            $otherCosts = max(0, (int) $lastTick['expenses'] - $totalExpenses - $liftSlopeRunning);
            $totalExpenses = (int) $lastTick['expenses'];
        }
        $profit = $totalIncome - $totalExpenses;
        $cash = (int) $finance['cash'];
        $lifetime = (int) $finance['total_income'] - (int) $finance['total_expenses'];
        $signed = fn(int $v) => ($v >= 0 ? '+' : '') . currency($v);

        $income = [['Lift tickets', $ticketIncome], ['Restaurants', $restaurantIncome], ['Hotels', $hotelIncome], ['Rentals', $rentalIncome]];
        if ($otherIncome !== null) $income[] = ['Other (shops, transport, ski school, parking, VIPs)', $otherIncome];
        $expenses = [['Staff (' . count($staff) . ')', $dailySalaries, 'fa-users'], ['Equipment', $equipmentCost, 'fa-toolbox'], ['Loans', $loanPayments, 'fa-landmark'],
            ['Marketing', $dailyMarketing, 'fa-bullhorn'], ['Snowmaking', $dailyCannons, 'fa-snowflake'], ['Night skiing', $dailyLights, 'fa-moon']];
        if ($liftSlopeRunning !== null) {
            $expenses[] = ['Lifts and slopes running', $liftSlopeRunning, 'fa-elevator'];
            $expenses[] = ['Other (upkeep, utilities, insurance, fines)', $otherCosts, 'fa-receipt'];
        }

        return view('finances/index', ['page' => [
            'gameDay' => $gameDay,
            'cash' => ['text' => currency($cash), 'negative' => $cash < 0],
            'income' => ['source' => !empty($lastTick) ? 'last game day' : 'estimated', 'total' => currency($totalIncome),
                'rows' => array_map(fn($r) => ['label' => $r[0], 'amount' => currency((int) $r[1])], $income)],
            'expenses' => ['total' => currency($totalExpenses),
                'rows' => array_map(fn($r) => ['label' => $r[0], 'amount' => currency((int) $r[1]), 'icon' => $r[2]], $expenses)],
            'profit' => ['text' => $signed($profit), 'positive' => $profit >= 0,
                'note' => $profit < 0 ? floor($cash / abs($profit)) . ' days of cash left at this rate' : ($profit > 0 ? 'Cash is growing' : null)],
            'lifetime' => ['income' => currency((int) $finance['total_income']), 'expenses' => currency((int) $finance['total_expenses']),
                'net' => $signed($lifetime), 'positive' => $lifetime >= 0],
            'transactions' => array_map(fn($t) => ['id' => (int) $t['id'], 'day' => (int) $t['game_day'], 'category' => (string) $t['category'],
                'description' => (string) $t['description'], 'amount' => ($t['type'] === 'income' ? '+' : '-') . currency((int) $t['amount']),
                'income' => $t['type'] === 'income'], $recentTransactions),
        ]]);
    }
}
