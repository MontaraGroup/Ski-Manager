<?php

namespace App\Controllers;

use App\Models\FinanceModel;

class Genepis extends BaseController
{
    private function getOrCreate(int $userId): array
    {
        $db = db_connect();
        $g = $db->table('genepis')->where('user_id', $userId)->get()->getRowArray();
        if (!$g) {
            $db->table('genepis')->insert(['user_id' => $userId, 'balance' => 100, 'total_earned' => 100, 'total_spent' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            $g = $db->table('genepis')->where('user_id', $userId)->get()->getRowArray();
        }
        return $g;
    }

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $genepis = $this->getOrCreate($userId);
        $log = $db->table('genepis_log')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(20)->get()->getResultArray();

        $shopItems = [
            'speed_boost' => ['name' => 'Construction Speed Boost', 'desc' => 'All builds complete 50% faster for 24 hours', 'cost' => 50, 'icon' => 'fa-solid fa-forward-fast', 'color' => 'text-warning'],
            'revenue_boost' => ['name' => 'Revenue Boost', 'desc' => '+25% revenue for 24 hours', 'cost' => 75, 'icon' => 'fa-solid fa-coins', 'color' => 'text-success'],
            'visitor_boost' => ['name' => 'Visitor Surge', 'desc' => '+50% visitors for 24 hours', 'cost' => 100, 'icon' => 'fa-solid fa-people-group', 'color' => 'text-info'],
            'snow_dump' => ['name' => 'Instant Snow Dump', 'desc' => 'Add 30cm of fresh snow immediately', 'cost' => 40, 'icon' => 'fa-solid fa-snowflake', 'color' => 'text-primary'],
            'repair_all' => ['name' => 'Repair Everything', 'desc' => 'All lifts, cannons, and lights restored to 100%', 'cost' => 60, 'icon' => 'fa-solid fa-wrench', 'color' => 'text-warning'],
            'reputation_boost' => ['name' => 'PR Campaign', 'desc' => '+500 reputation instantly', 'cost' => 80, 'icon' => 'fa-solid fa-star', 'color' => 'text-warning'],
            'skip_weather' => ['name' => 'Weather Override', 'desc' => 'Force sunny weather for 3 days', 'cost' => 120, 'icon' => 'fa-solid fa-sun', 'color' => 'text-warning'],
            'cash_injection' => ['name' => 'Cash Injection', 'desc' => 'Receive 100,000 cash immediately', 'cost' => 150, 'icon' => 'fa-solid fa-money-bill-transfer', 'color' => 'text-success'],
        ];

        $earnMethods = [
            ['name' => 'Daily Login Bonus', 'desc' => 'Earn Génépis from your daily streak', 'amount' => '1-5/day', 'icon' => 'fa-solid fa-fire', 'link' => '/daily-bonus'],
            ['name' => 'Achievements', 'desc' => 'Complete achievements for Génépis rewards', 'amount' => 'Varies', 'icon' => 'fa-solid fa-award', 'link' => '/achievements'],
            ['name' => 'Tournaments', 'desc' => 'Win tournaments to earn Génépis', 'amount' => '10-100', 'icon' => 'fa-solid fa-trophy', 'link' => '/tournaments'],
            ['name' => 'Eco Score', 'desc' => 'Maintain 80+ eco score for daily Génépis', 'amount' => '2/day', 'icon' => 'fa-solid fa-leaf', 'link' => '/environment'],
            ['name' => 'Full Compliance', 'desc' => 'Stay compliant with all regulations', 'amount' => '1/day', 'icon' => 'fa-solid fa-building-columns', 'link' => '/government'],
        ];

        return view('genepis/index', [
            'genepis' => $genepis,
            'log' => $log,
            'shopItems' => $shopItems,
            'earnMethods' => $earnMethods,
        ]);
    }

    public function buy()
    {
        $userId = auth()->id();
        $db = db_connect();
        $item = $this->request->getPost('item');

        $costs = [
            'speed_boost' => 50, 'revenue_boost' => 75, 'visitor_boost' => 100,
            'snow_dump' => 40, 'repair_all' => 60, 'reputation_boost' => 80,
            'skip_weather' => 120, 'cash_injection' => 150,
        ];

        $names = [
            'speed_boost' => 'Construction Speed Boost', 'revenue_boost' => 'Revenue Boost',
            'visitor_boost' => 'Visitor Surge', 'snow_dump' => 'Instant Snow Dump',
            'repair_all' => 'Repair Everything', 'reputation_boost' => 'PR Campaign',
            'skip_weather' => 'Weather Override', 'cash_injection' => 'Cash Injection',
        ];

        if (!isset($costs[$item])) {
            return redirect()->back()->with('error', 'Invalid item.');
        }

        $genepis = $this->getOrCreate($userId);
        $cost = $costs[$item];

        if ((int) $genepis['balance'] < $cost) {
            return redirect()->back()->with('error', 'Not enough Génépis. You need ' . $cost . ' but have ' . $genepis['balance'] . '.');
        }

        $db->table('genepis')->where('user_id', $userId)->update([
            'balance' => (int) $genepis['balance'] - $cost,
            'total_spent' => (int) $genepis['total_spent'] + $cost,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $db->table('genepis_log')->insert([
            'user_id' => $userId,
            'amount' => $cost,
            'type' => 'spent',
            'reason' => 'Purchased: ' . $names[$item],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($item === 'cash_injection') {
            $finance = (new FinanceModel())->where('user_id', $userId)->first();
            if ($finance) {
                (new FinanceModel())->update($finance['id'], ['cash' => (int) $finance['cash'] + 100000]);
            }
        }

        log_activity($userId, 'Génépis', 'Used ' . $cost . ' Génépis for ' . $names[$item], 'fa-solid fa-seedling');

        return redirect()->to('/genepis')->with('success', $names[$item] . ' activated! (-' . $cost . ' Génépis)');
    }
}
