<?php

namespace App\Libraries;

use App\Controllers\ResortMap;

/**
 * Economy v2 season start: every resort is cleared and restarts with its difficulty's starting cash
 * plus a legacy bonus (Economy::legacyBonus) earned from last season's net worth.
 * Achievements, Génépis and boosts, alliances, history and settings are kept.
 */
class SeasonReset
{
    /** Per-player game state that a new season clears */
    public const RESET_TABLES = [
        'player_items', 'buildings', 'staff', 'equipment', 'snow_cannons', 'night_skiing',
        'marketing_campaigns', 'loans', 'insurance', 'regulations', 'environmental',
        'energy_management', 'water_management', 'vip_guests', 'parking', 'terrain_parks',
        'scenic_lifts', 'lift_tickets', 'ticket_sales', 'avalanche_zones', 'staff_candidates',
    ];

    /** Net worth, starting cash and legacy bonus for every player, richest first */
    public static function plan(): array
    {
        $db = db_connect();
        $startCash = [];
        foreach ($db->table('difficulty_config')->where('config_key', 'starting_cash')->get()->getResultArray() as $r) {
            $startCash[$r['difficulty']] = (int) $r['config_value'];
        }

        // A building's value is every level it was built or upgraded through
        $levelCost = [];
        foreach ($db->table('building_levels')->orderBy('level')->get()->getResultArray() as $l) {
            $levelCost[$l['type_key']][(int) $l['level']] = ($levelCost[$l['type_key']][(int) $l['level'] - 1] ?? 0) + (int) $l['cost'];
        }
        $assets = [];
        foreach ($db->table('buildings')->select('user_id, building_type, level')->get()->getResultArray() as $b) {
            $assets[(int) $b['user_id']] = ($assets[(int) $b['user_id']] ?? 0) + ($levelCost[$b['building_type']][(int) $b['level']] ?? 0);
        }
        foreach ($db->table('player_items')->get()->getResultArray() as $i) {
            $assets[(int) $i['user_id']] = ($assets[(int) $i['user_id']] ?? 0) + ResortMap::buildCost($i);
        }
        if ($db->tableExists('equipment_catalog')) {
            // Looked up in PHP: the two tables' model_key columns have different collations, so SQL can't join them
            $catalog = array_column($db->table('equipment_catalog')->select('model_key, cost')->get()->getResultArray(), 'cost', 'model_key');
            foreach ($db->table('equipment')->select('user_id, model_key')->get()->getResultArray() as $e) {
                $assets[(int) $e['user_id']] = ($assets[(int) $e['user_id']] ?? 0) + (int) ($catalog[$e['model_key']] ?? 0);
            }
        }
        $debt = array_column($db->query("SELECT user_id, SUM(remaining) d FROM loans WHERE status = 'active' GROUP BY user_id")->getResultArray(), 'd', 'user_id');

        $plan = [];
        foreach ($db->table('player_finances')->select('user_id, cash, difficulty')->get()->getResultArray() as $f) {
            $uid = (int) $f['user_id'];
            // Assets count at half what they cost: that's roughly what they'd sell for
            $netWorth = (int) $f['cash'] + (int) round(($assets[$uid] ?? 0) * 0.5) - (int) ($debt[$uid] ?? 0);
            $start = $startCash[$f['difficulty'] ?? 'standard'] ?? 500000;
            $plan[] = ['user_id' => $uid, 'difficulty' => $f['difficulty'] ?? 'standard', 'net_worth' => $netWorth,
                'starting_cash' => $start, 'legacy_bonus' => Economy::legacyBonus($netWorth, $start)];
        }
        usort($plan, fn($a, $b) => $b['net_worth'] <=> $a['net_worth']);
        return $plan;
    }

    public static function alreadyApplied(int $seasonNumber): bool
    {
        return db_connect()->table('season_legacy')->where('season_number', $seasonNumber)->countAllResults() > 0;
    }

    /**
     * Clear every resort for $seasonNumber. Runs inside the caller's transaction when there is one.
     * @return array the plan that was applied
     */
    public static function apply(int $seasonNumber, string $seasonName): array
    {
        $db = db_connect();
        if (self::alreadyApplied($seasonNumber)) {
            throw new \RuntimeException("Season {$seasonNumber} resorts were already reset");
        }
        $plan = self::plan();
        $now = date('Y-m-d H:i:s');

        $db->table('season_legacy')->insertBatch(array_map(fn($p) => [
            'season_number' => $seasonNumber, 'user_id' => $p['user_id'], 'net_worth' => $p['net_worth'],
            'legacy_bonus' => $p['legacy_bonus'], 'starting_cash' => $p['starting_cash'], 'created_at' => $now,
        ], $plan));

        foreach (self::RESET_TABLES as $table) {
            if ($db->tableExists($table)) $db->table($table)->emptyTable();
        }
        if ($db->tableExists('tournaments')) {
            $db->table('tournaments')->where('status !=', 'ended')->update(['status' => 'ended']);
        }

        foreach ($plan as $p) {
            $db->table('player_finances')->where('user_id', $p['user_id'])->update([
                'cash' => $p['starting_cash'] + $p['legacy_bonus'], 'total_income' => 0, 'total_expenses' => 0,
                'daily_visitors' => 0, 'reputation' => 0, 'resort_open' => 1, 'last_tick' => null, 'updated_at' => $now,
            ]);
            $bonus = $p['legacy_bonus'] > 0 ? ' Your legacy bonus from last season: ' . currency($p['legacy_bonus']) . '.' : '';
            notify($p['user_id'], 'season', $seasonName . ' starts fresh',
                'Every resort starts over with ' . currency($p['starting_cash'] + $p['legacy_bonus']) . ' under the new economy.' . $bonus
                . ' Ticket prices now affect demand, lifts limit how many skiers you can carry, and buildings earn by how full they are.',
                'fa-solid fa-mountain-sun', '/map');
        }
        return $plan;
    }

    /** Start the active season over on day 1 (its weather is regenerated from day 1 by App\Libraries\Weather) */
    public static function restartActiveSeason(): array
    {
        $db = db_connect();
        $season = $db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
        if (!$season) throw new \RuntimeException('No active season');

        $db->table('weather')->emptyTable();
        $db->table('seasons')->where('id', (int) $season['id'])->update(['start_date' => date('Y-m-d')]);
        return $season;
    }
}
