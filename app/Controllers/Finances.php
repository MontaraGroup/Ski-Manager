<?php

namespace App\Controllers;

use App\Models\FinanceModel;
use App\Models\TransactionModel;
use App\Models\StaffModel;
use App\Models\MarketingModel;
use App\Models\SnowCannonModel;
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
        $cannonModel = new SnowCannonModel();
        $lightModel = new NightSkiingModel();

        $staff = $staffModel->where('user_id', $userId)->where('status', 'active')->findAll();
        $campaigns = $marketingModel->where('user_id', $userId)->where('status', 'active')->findAll();
        $cannons = $cannonModel->where('user_id', $userId)->where('status', 'active')->findAll();
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
        $lastIncome = $db->table('financial_transactions')->where('user_id', $userId)->where('type', 'income')->where('game_day', $gameDay)->selectSum('amount')->get()->getRowArray();
        $prevIncome = $db->table('financial_transactions')->where('user_id', $userId)->where('type', 'income')->where('game_day', max(1, $gameDay - 1))->selectSum('amount')->get()->getRowArray();
        $estimatedIncome = (int) ($lastIncome['amount'] ?? $prevIncome['amount'] ?? 0);

        $ticketIncome = 0;
        $tickets = $db->table('lift_tickets')->where('user_id', $userId)->where('active', 1)->get()->getResultArray();
        $visitors = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $visitorCount = 84;
        foreach ($tickets as $tk) {
            if ($tk['ticket_type'] === 'full_day') $ticketIncome = (int) $tk['price'] * $visitorCount;
        }

        $hotelIncome = 0;
        $hotels = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'hotel')->where('status', 'open')->get()->getResultArray();
        foreach ($hotels as $h) { $hotelIncome += (int) ($h['daily_revenue'] ?? 0); }

        $restaurantIncome = 0;
        $restaurants = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'restaurant')->where('status', 'open')->get()->getResultArray();
        foreach ($restaurants as $r) { $restaurantIncome += (int) ($r['daily_revenue'] ?? 0); }

        $rentalIncome = 0;
        $rentals = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'rental')->where('status', 'open')->get()->getResultArray();
        foreach ($rentals as $r) { $rentalIncome += (int) ($r['daily_revenue'] ?? 0); }

        $loanPayments = 0;
        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        foreach ($loans as $l) { $loanPayments += (int) ($l['daily_payment'] ?? 0); }

        $equipmentCost = 0;
        $equip = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        foreach ($equip as $e) { $equipmentCost += (int) ($e['fuel_cost'] ?? $e['daily_cost'] ?? 0); }

        return view('finances/index', [
            'finance' => $finance,
            'dailySalaries' => $dailySalaries,
            'dailyMarketing' => $dailyMarketing,
            'dailyCannons' => $dailyCannons,
            'dailyLights' => $dailyLights,
            'totalDailyExpenses' => $totalDailyExpenses,
            'transactions' => $recentTransactions,
            'staffCount' => count($staff),
            'campaignCount' => count($campaigns),
            'gameDay' => $gameDay,
            'ticketIncome' => $ticketIncome,
            'hotelIncome' => $hotelIncome,
            'restaurantIncome' => $restaurantIncome,
            'rentalIncome' => $rentalIncome,
            'estimatedIncome' => $estimatedIncome,
            'loanPayments' => $loanPayments,
            'equipmentCost' => $equipmentCost,
        ]);
    }
}
