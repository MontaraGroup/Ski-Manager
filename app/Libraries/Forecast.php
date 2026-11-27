<?php

namespace App\Libraries;

/**
 * What the next nightly game:tick will pay for tickets, buildings and lift/slope running, worked out from the
 * resort as it is now. The dashboard and /finances show it, so a build, a demolition or a price change moves
 * the numbers straight away. Money still only moves at the tick.
 *
 * Mirrors the visitor and revenue part of Commands/GameTick.php. Bonuses the tick works out from things it
 * changes as it runs (marketing, staff, snow quality, alliances, eco score) are taken from the last tick's
 * `boost_pct`.
 */
class Forecast
{
    /** The nightly game:tick runs at 00:05 in the app's timezone (systemd ski-gametick.timer) */
    public const SETTLE_TIME = '00:05';

    private static array $cache = [];

    /** Next nightly settle as ISO 8601, so the browser can show it in the player's own timezone */
    public static function nextSettleAt(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(config('App')->appTimezone));
        $at  = $now->modify('today ' . self::SETTLE_TIME);
        return ($at <= $now ? $at->modify('+1 day') : $at)->format(DATE_ATOM);
    }

    /**
     * @return array{visitors: int, tickets: int, buildings: int, lift_slope_running: int, occupancy: array<string, float>, turned_away: int}|null
     *         null when the player has no finance row
     */
    public static function nextSettle(int $userId, ?array $finance = null): ?array
    {
        if (array_key_exists($userId, self::$cache)) return self::$cache[$userId];

        $db = db_connect();
        $finance ??= $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (!$finance) return self::$cache[$userId] = null;

        $config = array_column($db->table('difficulty_config')->where('difficulty', $finance['difficulty'] ?? 'standard')->get()->getResultArray(), 'config_value', 'config_key');
        $revMult  = ((int) ($config['revenue_multiplier'] ?? 100)) / 100;
        $costMult = ((int) ($config['cost_multiplier'] ?? 100)) / 100;
        $visMult  = ((int) ($config['visitor_multiplier'] ?? 100)) / 100;

        // The tick settles the game day that starts at 00:05, so use that day's weather and season
        $settleDay = getGameDay() + 1;
        $settleAt  = date('Y-m-d H:i:s', strtotime(self::nextSettleAt()));
        $isWinter  = (getSeasonDay() + 1) <= getWinterDays();

        $boosts = array_column($db->table('player_boosts')->where('user_id', $userId)->where('expires_at >', $settleAt)->get()->getResultArray(), 'boost_type');
        if (in_array('revenue_boost', $boosts, true)) $revMult *= 1.25;

        $weather = $db->table('weather')->where('game_day', $settleDay)->get()->getRowArray() ?: getCurrentWeather();
        if (in_array('skip_weather', $boosts, true)) {
            $weather = array_merge($weather, ['condition_name' => 'Sunny', 'wind' => min((int) ($weather['wind'] ?? 0), 10)]);
        }

        $items  = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $slopes = array_values(array_filter($items, fn($i) => $i['item_type'] === 'slope' && $i['status'] === 'open'));
        $lifts  = array_values(array_filter($items, fn($i) => $i['item_type'] === 'lift' && $i['status'] === 'open'));
        $resortOpen = (int) ($finance['resort_open'] ?? 1) === 1;

        $last = json_decode((string) ($finance['last_tick'] ?? ''), true) ?: [];
        $boostPct = (float) ($last['boost_pct'] ?? 0);

        $demand = Economy::slopeDemand($slopes) * (1 + $boostPct / 100) * $visMult
            * Weather::demandFactor((string) ($weather['condition_name'] ?? ''), (int) ($weather['wind'] ?? 0));
        if (in_array('visitor_boost', $boosts, true)) $demand *= 1.5;
        $demand = Economy::marketCap($demand);

        $terms = Economy::ticketTerms($db->table('lift_tickets')->where('user_id', $userId)->get()->getResultArray());
        $tourists = $isWinter ? 0 : (int) round($demand * Economy::SUMMER_TOURIST_SHARE);

        $eventVisitors = (int) ($db->table('tournaments')->selectSum('visitors_boost')
            ->where('host_id', $userId)->where('start_day <=', $settleDay)->where('end_day >=', $settleDay)->where('status !=', 'ended')
            ->get()->getRow('visitors_boost') ?? 0);

        $visitors = (int) round($demand * $terms['demand'] * ($isWinter ? 1 : 0.15)) + $eventVisitors;
        $liftCapacity = Economy::liftDailyCapacity($lifts);
        $turnedAway = max(0, $visitors - $liftCapacity);
        $visitors = min($visitors, $liftCapacity);
        if (!$resortOpen) { $visitors = 0; $tourists = 0; $turnedAway = 0; }

        $tickets = (int) round((int) round($visitors * $terms['per_visit']) * $revMult);

        $assignedByRole = [];
        foreach ($db->table('staff')->select('role')->where('user_id', $userId)->where('status', 'active')->where('assigned_to IS NOT NULL')->where('assigned_to !=', '')->get()->getResultArray() as $s) {
            $assignedByRole[$s['role']][] = $s;
        }
        $guest = array_values(array_filter(
            $db->table('buildings')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray(),
            fn($b) => !in_array($b['building_type'], ['real_estate', 'ski_patrol'], true)
        ));
        $util = Economy::buildingUtilization($guest, $visitors, $tourists);
        $perType = array_count_values(array_column($guest, 'building_type'));
        $buildings = 0;
        foreach ($guest as $b) {
            $t = $b['building_type'];
            $buildings += (int) $b['revenue_per_day'] * ($util[$t] ?? 0) * Economy::staffBonus($t, $perType[$t] ?? 0, $assignedByRole);
        }
        $buildings = $resortOpen ? (int) round($buildings * $revMult) : 0;

        return self::$cache[$userId] = [
            'visitors'           => $visitors,
            'tickets'            => $tickets,
            'buildings'          => $buildings,
            'lift_slope_running' => (int) round(Economy::runningCost($items) * $costMult),
            'occupancy'          => $util,
            'turned_away'        => $turnedAway,
        ];
    }
}
