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

        $startDate = '2026-06-01';
        $today = date('Y-m-d');
        $gameDay = max(1, (int) ((strtotime($today) - strtotime($startDate)) / 86400) + 1);

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
        ]);
    }
}
