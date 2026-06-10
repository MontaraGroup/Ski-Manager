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

    private function getShopItems(): array
    {
        return [
            // Instant effects
            'snow_dump' => ['name' => 'Instant Snow Dump', 'desc' => 'Add 30cm of fresh powder to all slopes', 'cost' => 40, 'icon' => 'fa-solid fa-snowflake', 'color' => 'text-primary', 'duration' => null, 'category' => 'Instant'],
            'repair_all' => ['name' => 'Repair Everything', 'desc' => 'All slopes, lifts, equipment, and buildings to 100%', 'cost' => 60, 'icon' => 'fa-solid fa-wrench', 'color' => 'text-warning', 'duration' => null, 'category' => 'Instant'],
            'reputation_boost' => ['name' => 'PR Campaign', 'desc' => '+500 visitors instantly', 'cost' => 80, 'icon' => 'fa-solid fa-star', 'color' => 'text-warning', 'duration' => null, 'category' => 'Instant'],
            'cash_injection' => ['name' => 'Cash Injection', 'desc' => 'Receive $100,000 cash immediately', 'cost' => 150, 'icon' => 'fa-solid fa-money-bill-transfer', 'color' => 'text-success', 'duration' => null, 'category' => 'Instant'],
            'groom_all' => ['name' => 'Perfect Grooming', 'desc' => 'All slopes groomed to 100% condition', 'cost' => 35, 'icon' => 'fa-solid fa-tractor', 'color' => 'text-success', 'duration' => null, 'category' => 'Instant'],
            'morale_boost' => ['name' => 'Staff Party', 'desc' => 'All staff morale set to 100%', 'cost' => 45, 'icon' => 'fa-solid fa-champagne-glasses', 'color' => 'text-info', 'duration' => null, 'category' => 'Instant'],
            // Timed boosts
            'speed_boost' => ['name' => 'Speed Boost', 'desc' => 'Builds complete 50% faster', 'cost' => 50, 'icon' => 'fa-solid fa-forward-fast', 'color' => 'text-warning', 'duration' => 24, 'category' => 'Timed Boost'],
            'revenue_boost' => ['name' => 'Revenue Boost', 'desc' => '+25% revenue from all sources', 'cost' => 75, 'icon' => 'fa-solid fa-coins', 'color' => 'text-success', 'duration' => 24, 'category' => 'Timed Boost'],
            'visitor_boost' => ['name' => 'Visitor Surge', 'desc' => '+50% daily visitors', 'cost' => 100, 'icon' => 'fa-solid fa-people-group', 'color' => 'text-info', 'duration' => 24, 'category' => 'Timed Boost'],
            'skip_weather' => ['name' => 'Weather Control', 'desc' => 'Perfect ski weather for 3 days', 'cost' => 120, 'icon' => 'fa-solid fa-sun', 'color' => 'text-warning', 'duration' => 72, 'category' => 'Timed Boost'],
            'vip_magnet' => ['name' => 'VIP Magnet', 'desc' => 'Attract 3 VIP guests in 24 hours', 'cost' => 90, 'icon' => 'fa-solid fa-gem', 'color' => 'text-secondary', 'duration' => 24, 'category' => 'Timed Boost'],
            'double_xp' => ['name' => 'Double XP', 'desc' => 'Staff earn double XP for 24 hours', 'cost' => 55, 'icon' => 'fa-solid fa-arrow-up-right-dots', 'color' => 'text-primary', 'duration' => 24, 'category' => 'Timed Boost'],
            // Permanent upgrades
            'extra_loan' => ['name' => 'Extra Loan Slot', 'desc' => 'Unlock a 4th active loan slot permanently', 'cost' => 200, 'icon' => 'fa-solid fa-landmark', 'color' => 'text-primary', 'duration' => null, 'category' => 'Permanent'],
            'auto_groom' => ['name' => 'Auto-Grooming', 'desc' => 'Slopes auto-groom when below 50% (permanent)', 'cost' => 300, 'icon' => 'fa-solid fa-robot', 'color' => 'text-info', 'duration' => null, 'category' => 'Permanent'],
            'premium_name' => ['name' => 'Custom Resort Badge', 'desc' => 'Gold badge next to your name on leaderboard', 'cost' => 500, 'icon' => 'fa-solid fa-certificate', 'color' => 'text-warning', 'duration' => null, 'category' => 'Permanent'],
        ];
    }

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $genepis = $this->getOrCreate($userId);
        $log = $db->table('genepis_log')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(20)->get()->getResultArray();

        $activeBoosts = $db->table('player_boosts')->where('user_id', $userId)->where('expires_at >', date('Y-m-d H:i:s'))->get()->getResultArray();

        $earnMethods = [
            ['name' => 'Daily Login Bonus', 'desc' => 'Earn Génépis from your daily streak', 'amount' => '1-5/day', 'icon' => 'fa-solid fa-fire', 'link' => '/daily-bonus'],
            ['name' => 'Achievements', 'desc' => 'Complete achievements for rewards', 'amount' => 'Varies', 'icon' => 'fa-solid fa-award', 'link' => '/achievements'],
            ['name' => 'Tournaments', 'desc' => 'Win tournaments to earn Génépis', 'amount' => '10-100', 'icon' => 'fa-solid fa-trophy', 'link' => '/tournaments'],
            ['name' => 'Eco Score', 'desc' => 'Maintain 80+ eco score', 'amount' => '2/day', 'icon' => 'fa-solid fa-leaf', 'link' => '/environment'],
            ['name' => 'Full Compliance', 'desc' => 'Stay compliant with all regulations', 'amount' => '1/day', 'icon' => 'fa-solid fa-building-columns', 'link' => '/government'],
            ['name' => 'VIP Guests', 'desc' => 'Attract VIP visitors to your resort', 'amount' => '5-20', 'icon' => 'fa-solid fa-star', 'link' => '/vip-guests'],
            ['name' => 'Resort Tours', 'desc' => 'Get likes on your resort from other players', 'amount' => '1/like', 'icon' => 'fa-solid fa-heart', 'link' => '/settings'],
        ];

        return view('genepis/index', [
            'genepis' => $genepis,
            'log' => $log,
            'shopItems' => $this->getShopItems(),
            'earnMethods' => $earnMethods,
            'activeBoosts' => $activeBoosts,
        ]);
    }

    public function buy()
    {
        $userId = auth()->id();
        $db = db_connect();
        $item = $this->request->getPost('item');
        $shopItems = $this->getShopItems();

        if (!isset($shopItems[$item])) {
            return redirect()->back()->with('error', 'Invalid item.');
        }

        $genepis = $this->getOrCreate($userId);
        $cfg = $shopItems[$item];
        $cost = $cfg['cost'];

        if ((int) $genepis['balance'] < $cost) {
            return redirect()->back()->with('error', 'Not enough Génépis. Need ' . $cost . ', have ' . $genepis['balance'] . '.');
        }

        // Check if timed boost already active
        if ($cfg['duration']) {
            $existing = $db->table('player_boosts')->where('user_id', $userId)->where('boost_type', $item)->where('expires_at >', date('Y-m-d H:i:s'))->countAllResults();
            if ($existing > 0) {
                return redirect()->back()->with('error', $cfg['name'] . ' is already active.');
            }
        }

        // Deduct genepis
        $db->table('genepis')->where('user_id', $userId)->update([
            'balance' => (int) $genepis['balance'] - $cost,
            'total_spent' => (int) $genepis['total_spent'] + $cost,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $db->table('genepis_log')->insert([
            'user_id' => $userId, 'amount' => $cost, 'type' => 'spent',
            'reason' => 'Purchased: ' . $cfg['name'], 'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Apply effect
        $this->applyEffect($userId, $item, $cfg);

        log_activity($userId, 'Génépis', 'Used ' . $cost . ' Génépis for ' . $cfg['name'], 'fa-solid fa-seedling');
        return redirect()->to('/genepis')->with('success', $cfg['name'] . ' activated! (-' . $cost . ' Génépis)');
    }

    private function applyEffect(int $userId, string $item, array $cfg): void
    {
        $db = db_connect();

        // Timed boosts
        if ($cfg['duration']) {
            $db->table('player_boosts')->insert([
                'user_id' => $userId,
                'boost_type' => $item,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+' . $cfg['duration'] . ' hours')),
            ]);
        }

        switch ($item) {
            case 'cash_injection':
                $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + 100000', false)->update();
                break;

            case 'snow_dump':
                $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->set('snow_depth', 'LEAST(snow_depth + 30, 150)', false)->update();
                $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->update(['snow_quality' => 'powder']);
                break;

            case 'repair_all':
                $db->table('player_items')->where('user_id', $userId)->update(['condition_pct' => 100]);
                $db->table('equipment')->where('user_id', $userId)->where('status', 'broken')->update(['status' => 'off', 'condition_pct' => 100]);
                $db->table('equipment')->where('user_id', $userId)->where('status !=', 'broken')->update(['condition_pct' => 100]);
                $db->table('buildings')->where('user_id', $userId)->update(['condition_pct' => 100]);
                break;

            case 'reputation_boost':
                $db->table('player_finances')->where('user_id', $userId)->set('daily_visitors', 'daily_visitors + 500', false)->update();
                break;

            case 'groom_all':
                $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->update(['condition_pct' => 100]);
                break;

            case 'morale_boost':
                $db->table('staff')->where('user_id', $userId)->where('status', 'active')->update(['morale' => 100]);
                break;

            case 'extra_loan':
                // Tracked via player_boosts as permanent (far future expiry)
                $db->table('player_boosts')->insert(['user_id' => $userId, 'boost_type' => 'extra_loan', 'expires_at' => '2099-12-31 23:59:59']);
                break;

            case 'auto_groom':
                $db->table('player_boosts')->insert(['user_id' => $userId, 'boost_type' => 'auto_groom', 'expires_at' => '2099-12-31 23:59:59']);
                break;

            case 'premium_name':
                $db->table('player_boosts')->insert(['user_id' => $userId, 'boost_type' => 'premium_name', 'expires_at' => '2099-12-31 23:59:59']);
                break;
        }
    }

    public function exchange()
    {
        $userId = auth()->id();
        $db = db_connect();
        $amount = (int) $this->request->getPost('amount');
        $direction = $this->request->getPost('direction');

        if ($amount <= 0) return redirect()->back()->with('error', 'Invalid amount.');

        $genepis = $this->getOrCreate($userId);
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();

        if ($direction === 'to_cash') {
            if ((int) $genepis['balance'] < $amount) return redirect()->back()->with('error', 'Not enough Génépis.');
            $cashValue = $amount * 1000;
            $db->table('genepis')->where('user_id', $userId)->update([
                'balance' => (int) $genepis['balance'] - $amount,
                'total_spent' => (int) $genepis['total_spent'] + $amount,
            ]);
            $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $cashValue, false)->update();
            $db->table('genepis_log')->insert(['user_id' => $userId, 'amount' => $amount, 'type' => 'spent', 'reason' => 'Exchanged for ' . currency($cashValue), 'created_at' => date('Y-m-d H:i:s')]);
            log_activity($userId, 'Génépis', 'Exchanged ' . $amount . ' Génépis for ' . currency($cashValue), 'fa-solid fa-seedling');
            return redirect()->to('/genepis')->with('success', 'Exchanged ' . $amount . ' Génépis for ' . currency($cashValue) . '!');
        }

        return redirect()->back()->with('error', 'Invalid exchange.');
    }
}
