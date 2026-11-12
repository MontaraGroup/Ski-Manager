<?php

namespace App\Libraries;

/**
 * Achievement progress for a player. sync() makes sure every achievement_defs row exists for
 * the player, recomputes progress from live data and marks new completions (completion is
 * permanent; rewards are claimed on /achievements). Run by the nightly tick and the page.
 */
class Achievements
{
    /** Page groups, matched by key prefix in order */
    public const CATEGORIES = [
        'Resort' => ['lifts_', 'slopes_', 'first_park', 'parks_', 'rating_', 'first_parking', 'parking_'],
        'Guests' => ['visitors_', 'first_vip', 'vip_'],
        'Staff' => ['first_staff', 'staff_', 'all_roles', 'high_morale'],
        'Buildings' => ['first_building', 'buildings_', 'hotels_', 'restaurants_'],
        'Equipment & utilities' => ['first_cannon', 'groomers_', 'snowmakers_', 'all_brands', 'first_energy', 'first_water', 'green_energy', 'eco_'],
        'Money' => ['cash_', 'first_loan', 'no_debt', 'first_campaign', 'campaigns_'],
        'Safety & rules' => ['full_coverage', 'safety_', 'all_insurance', 'full_compliance', 'zero_fines'],
        'Milestones' => ['login_', 'day_', 'survive_summer', 'summer_profit', 'season_'],
        'Community' => ['roadmap_'],
    ];

    public static function category(string $key): string
    {
        foreach (self::CATEGORIES as $name => $prefixes) {
            foreach ($prefixes as $p) {
                if (str_starts_with($key, $p)) return $name;
            }
        }
        return 'Other';
    }

    /** Achievements that are awarded, not measured (left as they are) */
    private const AWARDED = ['season_1_pioneer'];

    /**
     * Bring a player's achievements up to date.
     * @return array newly completed achievement rows
     */
    public static function sync(int $userId, bool $notify = true): array
    {
        $db = db_connect();
        $defs = $db->table('achievement_defs')->orderBy('sort_order')->get()->getResultArray();
        $rows = array_column($db->table('achievements')->where('user_id', $userId)->get()->getResultArray(), null, 'achievement_key');

        $now = date('Y-m-d H:i:s');
        foreach ($defs as $d) {
            $row = $rows[$d['achievement_key']] ?? null;
            if ($row) {
                // Player rows copy the definition; keep the wording, goal and reward current
                $copy = ['name' => $d['name'], 'description' => $d['description'], 'icon' => $d['icon'], 'target' => (int) $d['target'], 'reward_amount' => (int) $d['reward']];
                $changed = array_filter($copy, fn($v, $k) => (string) $row[$k] !== (string) $v, ARRAY_FILTER_USE_BOTH);
                if ($changed && !(int) $row['claimed']) {
                    $db->table('achievements')->where('id', $row['id'])->update($changed);
                    $rows[$d['achievement_key']] = array_merge($row, $changed);
                }
                continue;
            }
            if (in_array($d['achievement_key'], self::AWARDED, true)) continue;
            $db->table('achievements')->insert([
                'user_id' => $userId, 'achievement_key' => $d['achievement_key'], 'name' => $d['name'], 'description' => $d['description'],
                'icon' => $d['icon'], 'progress' => 0, 'target' => (int) $d['target'], 'reward_amount' => (int) $d['reward'],
                'completed' => 0, 'claimed' => 0, 'created_at' => $now,
            ]);
            $rows[$d['achievement_key']] = $db->table('achievements')->where('id', $db->insertID())->get()->getRowArray();
        }

        $metrics = self::metrics($userId);
        $newly = [];
        foreach ($rows as $key => $a) {
            if (in_array($key, self::AWARDED, true) || !array_key_exists($key, $metrics)) continue;
            $target = max(1, (int) $a['target']);
            $done = (int) $a['completed'] === 1;
            // Completion is permanent: dropping below a cash or staff goal later doesn't take it away
            $progress = $done ? $target : min($target, max(0, (int) $metrics[$key]));
            $update = ['progress' => $progress];
            if (!$done && $progress >= $target) {
                $update['completed'] = 1;
                $newly[] = $a;
            }
            if ((int) $a['progress'] !== $progress || isset($update['completed'])) {
                $db->table('achievements')->where('id', $a['id'])->update($update);
            }
        }

        if ($notify && $newly) {
            $total = array_sum(array_map(fn($a) => (int) $a['reward_amount'], $newly));
            $title = count($newly) === 1 ? 'Achievement: ' . $newly[0]['name'] : count($newly) . ' achievements completed';
            notify($userId, 'achievement', $title, 'Claim ' . currency($total) . ' on the achievements page.', 'fa-solid fa-award', '/achievements');
        }
        return $newly;
    }

    /** Current value of every measured achievement, keyed like achievement_defs */
    public static function metrics(int $userId): array
    {
        $db = db_connect();
        $count = fn(string $table, array $where = []) => $db->table($table)->where('user_id', $userId)->where($where)->countAllResults();

        $staff = $db->table('staff')->where('user_id', $userId)->where('status !=', 'fired')->get()->getResultArray();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray() ?? [];
        $buildings = $count('buildings');
        $snowmakers = $count('equipment', ['equipment_type' => 'snowmaker']);
        $loans = $count('loans');
        $openSlopes = $count('player_items', ['item_type' => 'slope', 'status' => 'open']);
        $parkingCap = (int) ($db->table('parking')->selectSum('capacity')->where('user_id', $userId)->where('status', 'open')->get()->getRow('capacity') ?? 0);
        $bonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        // A streak only counts while unbroken (claimed today or yesterday)
        $streak = $bonus && ($bonus['last_claim_date'] ?? '') >= date('Y-m-d', strtotime('-1 day')) ? (int) $bonus['streak'] : 0;
        $eco = $db->table('environmental')->where('user_id', $userId)->get()->getRowArray();

        // Safety, the same way the nightly tick scores it for accidents
        $stations = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'ski_patrol')->where('status', 'open')->get()->getResultArray();
        $patrolCapacity = array_sum(array_column($stations, 'capacity'));
        $assigned = fn(string $role) => count(array_filter($staff, fn($s) => $s['role'] === $role && $s['status'] === 'active' && !empty($s['assigned_to'])));
        $coverage = $openSlopes > 0 ? min(100, (int) round($patrolCapacity / $openSlopes * 100)) : 0;
        $safety = $openSlopes > 0 ? min(100, (int) round($coverage * 0.4 + $assigned('ski_patrol') * 8 + $assigned('medic') * 12 + count($stations) * 10)) : 0;

        // Renewables alone cover the energy demand
        $energy = $db->table('energy_management')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $renewable = array_sum(array_map(fn($e) => in_array($e['source_type'], ['solar', 'wind'], true) ? (int) $e['output_kwh'] : 0, $energy));
        $fossil = count(array_filter($energy, fn($e) => !in_array($e['source_type'], ['solar', 'wind'], true)));
        $demand = Utilities::energyDemand(
            $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->where('status', 'active')->get()->getResultArray(),
            $count('night_skiing', ['status' => 'active']),
            $db->table('player_items')->select('subtype, length_meters')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->get()->getResultArray(),
            $db->table('buildings')->select('level')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray(),
        )['total'];

        // Days since the last inspection fine, counted from when the player first faced regulations
        $firstReg = $db->table('regulations')->selectMin('created_at')->where('user_id', $userId)->get()->getRow('created_at');
        $lastFine = $db->table('activity_log')->selectMax('created_at')->where('user_id', $userId)->where('category', 'inspection')->get()->getRow('created_at');
        $cleanSince = max(strtotime((string) $firstReg) ?: time(), strtotime((string) $lastFine) ?: 0);

        // Summer: reached it this season, and a summer day with a profit
        $winterDays = getWinterDays();
        $inSummer = getSeasonDay() > $winterDays;
        $summerProfit = $inSummer ? (int) $db->query(
            "SELECT COUNT(*) n FROM (SELECT game_day, SUM(IF(type = 'income', amount, -amount)) net FROM financial_transactions
             WHERE user_id = ? AND game_day > ? AND created_at >= ? AND category IN ('Daily Income', 'Daily Expenses') GROUP BY game_day) d WHERE d.net > 0",
            [$userId, $winterDays, getSeasonStartDate() . ' 00:00:00']
        )->getRow('n') : 0;

        $joined = $db->table('users')->select('created_at')->where('id', $userId)->get()->getRow('created_at');
        $daysPlayed = $joined ? (int) floor((time() - strtotime($joined)) / 86400) : 0;
        $morale = array_column(array_filter($staff, fn($s) => $s['status'] === 'active'), 'morale');
        $cash = (int) ($finance['cash'] ?? 0);
        $visitors = (int) ($finance['daily_visitors'] ?? 0);
        $vips = $count('vip_guests');
        $roadmapVotes = $db->tableExists('roadmap_vote_history') ? $count('roadmap_vote_history') : 0;
        $ideas = fn(array $statuses) => $db->table('roadmap_items')->where('author_id', $userId)->whereIn('status', $statuses)->countAllResults();
        $ideasAccepted = $ideas(['planned', 'in_progress', 'completed']);
        $ideasShipped = $ideas(['completed']);

        return [
            'first_staff' => count($staff), 'staff_10' => count($staff), 'staff_25' => count($staff), 'staff_50' => count($staff), 'staff_100' => count($staff),
            'all_roles' => count(array_unique(array_column($staff, 'role'))),
            'high_morale' => $morale && min($morale) >= 80 ? 1 : 0,
            'first_building' => $buildings, 'buildings_10' => $buildings, 'buildings_25' => $buildings, 'buildings_50' => $buildings,
            'hotels_5' => $count('buildings', ['building_type' => 'hotel']), 'restaurants_5' => $count('buildings', ['building_type' => 'restaurant']),
            'first_cannon' => $snowmakers, 'snowmakers_10' => $snowmakers, 'groomers_5' => $count('equipment', ['equipment_type' => 'groomer']),
            'all_brands' => count($db->table('equipment')->select('brand')->distinct()->where('user_id', $userId)->get()->getResultArray()),
            'first_energy' => $count('energy_management'), 'first_water' => $count('water_management'),
            'green_energy' => $renewable > 0 && $fossil === 0 && $renewable >= $demand ? 1 : 0,
            'eco_80' => (int) ($eco['eco_score'] ?? 0),
            'lifts_5' => $count('player_items', ['item_type' => 'lift']), 'lifts_10' => $count('player_items', ['item_type' => 'lift']),
            'slopes_10' => $count('player_items', ['item_type' => 'slope']), 'slopes_25' => $count('player_items', ['item_type' => 'slope']),
            'first_park' => $count('terrain_parks'), 'parks_5' => $count('terrain_parks'),
            'rating_5' => (int) (resortRating($userId)['stars'] ?? 0),
            'first_parking' => $parkingCap > 0 ? 1 : 0, 'parking_1000' => $parkingCap,
            'visitors_500' => $visitors, 'visitors_1000' => $visitors, 'visitors_5000' => $visitors,
            'first_vip' => $vips, 'vip_10' => $vips, 'vip_royalty' => $count('vip_guests', ['vip_type' => 'royalty']),
            'cash_100k' => $cash, 'cash_1m' => $cash, 'cash_10m' => $cash,
            'first_loan' => $loans, 'no_debt' => $loans > 0 && $count('loans', ['status' => 'active']) === 0 ? 1 : 0,
            'first_campaign' => $count('marketing_campaigns'), 'campaigns_10' => $count('marketing_campaigns'),
            'full_coverage' => $coverage, 'safety_80' => $safety,
            'all_insurance' => $count('insurance', ['active' => 1]),
            'full_compliance' => $count('regulations', ['compliant' => 1]),
            'zero_fines' => $firstReg ? (int) floor((time() - $cleanSince) / 86400) : 0,
            'login_7' => $streak, 'login_30' => $streak,
            'day_30' => $daysPlayed, 'day_100' => $daysPlayed, 'day_365' => $daysPlayed,
            'survive_summer' => $inSummer ? 1 : 0, 'summer_profit' => $summerProfit > 0 ? 1 : 0,
            // Roadmap: items ever voted on (unvoting doesn't take it back) and the player's own ideas
            'roadmap_first_vote' => $roadmapVotes, 'roadmap_voter' => $roadmapVotes,
            'roadmap_idea_accepted' => $ideasAccepted, 'roadmap_idea_shipped' => $ideasShipped,
        ];
    }

    /**
     * Pay out a completed achievement once. Claiming sets claimed=1 only if it was 0,
     * so a double submit can't pay twice.
     */
    public static function claim(int $userId, array $ids): array
    {
        $db = db_connect();
        $paid = [];
        $db->transStart();
        foreach ($ids as $id) {
            $a = $db->table('achievements')->where('id', $id)->where('user_id', $userId)->where('completed', 1)->where('claimed', 0)->get()->getRowArray();
            if (!$a) continue;
            $db->table('achievements')->where('id', $id)->where('claimed', 0)->update(['claimed' => 1]);
            if ($db->affectedRows() !== 1) continue;
            $paid[] = $a;
        }
        $total = array_sum(array_map(fn($a) => (int) $a['reward_amount'], $paid));
        if ($total > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', 'cash + ' . $total, false)->update();
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Achievements',
                'description' => count($paid) === 1 ? 'Reward: ' . $paid[0]['name'] : 'Rewards for ' . count($paid) . ' achievements',
                'amount' => $total, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();
        return ['paid' => $paid, 'total' => $total];
    }
}
