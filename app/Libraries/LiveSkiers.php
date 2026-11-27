<?php

namespace App\Libraries;

/**
 * Computes real-time skier presence on the mountain using the Economy and Weather rules
 * anchored to the resort clock (America/New_York). Powers the live skier counter on /map,
 * StatsBar, and 3D/2D skier traffic distribution.
 */
class LiveSkiers
{
    private static array $requestCache = [];

    /** How long the stats bar's count may be reused across requests (the map itself always computes fresh) */
    private const COUNT_TTL = 30;

    /** Forget this request's snapshots, and the player's cached stats-bar count when $userId is given */
    public static function clearCache(?int $userId = null): void
    {
        self::$requestCache = [];
        if ($userId !== null && $userId > 0 && function_exists('cache')) {
            cache()->delete('live_skiers_count_' . $userId);
        }
    }

    /**
     * Live skier count for the stats bar on every page. Cached for COUNT_TTL seconds per player, so a page
     * load doesn't run the full snapshot (five queries and the weather) for every player.
     */
    public static function countOnly(int $userId): int
    {
        if ($userId <= 0) {
            return 0;
        }
        $key = 'live_skiers_count_' . $userId;
        $cached = function_exists('cache') ? cache()->get($key) : null;
        if (is_int($cached)) {
            return $cached;
        }
        $count = (int) self::snapshot($userId)['liveTotal'];
        if (function_exists('cache')) {
            cache()->save($key, $count, self::COUNT_TTL);
        }
        return $count;
    }

    /**
     * Compute a complete live skier snapshot for a player's resort.
     *
     * @param array|null $preloaded Optional keys: finance, items, buildings, tickets, lights, weather
     */
    public static function snapshot(int $userId, ?array $preloaded = null): array
    {
        if ($preloaded === null && isset(self::$requestCache[$userId])) {
            return self::$requestCache[$userId];
        }

        if ($userId <= 0) {
            return self::emptySnapshot(false, 'closed', false);
        }

        $db = db_connect();
        $finance = $preloaded['finance'] ?? $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (!$finance) {
            return self::emptySnapshot(false, 'closed', false);
        }

        $resortOpen = (bool) ((int) ($finance['resort_open'] ?? 1) === 1);
        $items = $preloaded['items'] ?? $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $buildings = $preloaded['buildings'] ?? $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        $tickets = $preloaded['tickets'] ?? $db->table('lift_tickets')->where('user_id', $userId)->get()->getResultArray();
        $lights = $preloaded['lights'] ?? ($db->tableExists('night_skiing')
            ? $db->table('night_skiing')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray()
            : []);

        $weather = $preloaded['weather'] ?? (function_exists('getCurrentWeather') ? getCurrentWeather() : [
            'condition_name' => 'Partly Cloudy',
            'wind' => 12,
        ]);

        $nightCoverage = 0;
        $nightCount = 0;
        foreach ($lights as $lt) {
            if (($lt['status'] ?? '') === 'active') {
                $nightCount++;
                $nightCoverage += (int) ($lt['coverage'] ?? 0);
            }
        }
        $nightLit = $nightCount > 0;

        $slopeTypes = ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'];
        $openSlopes = array_values(array_filter(
            $items,
            fn(array $i) => in_array((string) ($i['item_type'] ?? ''), $slopeTypes, true) && ($i['status'] ?? '') === 'open'
        ));
        $openLifts = array_values(array_filter(
            $items,
            fn(array $i) => ($i['item_type'] ?? '') === 'lift' && ($i['status'] ?? '') === 'open'
        ));
        $openBuildings = array_values(array_filter(
            $buildings,
            fn(array $b) => ($b['status'] ?? '') === 'open'
        ));

        [$phase, $concurrency, $liftShare, $villageShare] = self::clockPhase($nightLit, $nightCoverage);

        if (!$resortOpen || empty($openSlopes)) {
            $snap = self::emptySnapshot($resortOpen, $resortOpen ? $phase : 'closed', $nightLit, (int) ($finance['daily_visitors'] ?? 0));
            return self::$requestCache[$userId] = $snap;
        }

        // Difficulty visitor multiplier
        $visMult = 1.0;
        if ($db->tableExists('difficulty_config')) {
            $diffRow = $db->table('difficulty_config')
                ->select('config_value')
                ->where('difficulty', $finance['difficulty'] ?? 'standard')
                ->where('config_key', 'visitor_multiplier')
                ->get()->getRowArray();
            if ($diffRow && isset($diffRow['config_value'])) {
                $visMult = max(0.25, ((float) $diffRow['config_value']) / 100.0);
            }
        }

        $isWinter = function_exists('getSeasonDay') && function_exists('getWinterDays')
            ? getSeasonDay() <= getWinterDays()
            : true;

        $condName = (string) ($weather['condition_name'] ?? $weather['condition'] ?? 'Partly Cloudy');
        // The day's wind, as the tick uses it (getCurrentWeather()'s 'wind' is this hour's)
        $wind = (int) ($weather['base_wind'] ?? $weather['wind'] ?? 12);
        $windRule = Weather::windRule($userId);

        // Compute projected daily skiers from Economy + Weather so map builds change counts immediately
        $rawDemand = Economy::slopeDemand($openSlopes) * $visMult * Weather::demandFactor($condName, $wind, $windRule);
        $demand = Economy::marketCap($rawDemand);
        $terms = Economy::ticketTerms($tickets);
        $liftDailyCap = Economy::liftDailyCapacity($openLifts, $windRule ? Weather::exposedLiftShare($wind) : 1.0);
        $seasonFactor = $isWinter ? 1.0 : 0.15;
        $computedDaily = min((int) round($demand * $terms['demand'] * $seasonFactor), $liftDailyCap);

        $dailyLastTick = (int) ($finance['daily_visitors'] ?? 0);
        // Preserve marketing/alliance/VIP momentum from the last tick without masking new builds
        if ($dailyLastTick > 0 && $computedDaily > 0) {
            $tickScale = min(1.5, max(0.85, $dailyLastTick / max(1, $computedDaily)));
            $dailyProjected = min((int) round($computedDaily * $tickScale), $liftDailyCap > 0 ? (int) round($liftDailyCap * 1.15) : $computedDaily);
        } else {
            $dailyProjected = $computedDaily;
        }

        $liveTotal = $dailyProjected > 0 ? max(1, (int) round($dailyProjected * $concurrency)) : 0;
        if ($liveTotal === 0) {
            $snap = self::emptySnapshot($resortOpen, $phase, $nightLit, $dailyLastTick);
            return self::$requestCache[$userId] = $snap;
        }

        // Split liveTotal between slopes, lifts, and village
        if (empty($openBuildings)) {
            $villageShare = 0.0;
        }
        $onLifts = !empty($openLifts) ? (int) round($liveTotal * $liftShare) : 0;
        $inVillage = !empty($openBuildings) ? (int) round($liveTotal * $villageShare) : 0;
        if ($onLifts + $inVillage > $liveTotal) {
            $overflow = $onLifts + $inVillage - $liveTotal;
            $inVillage = max(0, $inVillage - $overflow);
        }
        $onSlopes = max(0, $liveTotal - $onLifts - $inVillage);

        $perSegment = [];

        // Distribute skiers across open slopes proportionally to length draw & condition
        if ($onSlopes > 0 && !empty($openSlopes)) {
            $slopeWeights = [];
            foreach ($openSlopes as $s) {
                $len = max(50, (int) ($s['length_meters'] ?? 400));
                $condPct = (int) ($s['condition_pct'] ?? 100);
                $slopeWeights[] = Economy::slopeDraw($len) * Economy::conditionFactor($condPct);
            }
            $alloc = self::allocateProportional($openSlopes, $slopeWeights, $onSlopes);
            foreach ($openSlopes as $idx => $s) {
                $segId = (int) ($s['segment_id'] ?? 0);
                if ($segId <= 0) {
                    continue;
                }
                $skiers = $alloc[$idx];
                $len = max(100, (int) ($s['length_meters'] ?? 400));
                // Nominal comfortable concurrent density ~ 1 skier per 18m of trail
                $comfortableCap = max(4.0, $len / 18.0);
                $loadPct = min(150, (int) round(($skiers / $comfortableCap) * 100));
                $perSegment[$segId] = [
                    'skiers'  => $skiers,
                    'loadPct' => $loadPct,
                    'queue'   => 0,
                ];
            }
        }

        // Distribute lift riders across open lifts proportionally to hourly capacity
        $totalLiftCapHr = 0;
        if (!empty($openLifts)) {
            $liftWeights = [];
            foreach ($openLifts as $l) {
                $cap = (int) ($l['capacity'] ?? 0) ?: Economy::liftCapacityPerHour((string) ($l['subtype'] ?? 'chair_fixed'), max(1, (int) ($l['seats'] ?? 4)));
                $totalLiftCapHr += $cap;
                $liftWeights[] = (float) max(100, $cap);
            }
            $allocL = self::allocateProportional($openLifts, $liftWeights, $onLifts);
            foreach ($openLifts as $idx => $l) {
                $cap = max(100, (int) ($liftWeights[$idx]));
                $skiers = $allocL[$idx];
                // ~11 cycles per hour when active
                $loadPct = min(140, (int) round(($skiers * 11.0 / $cap) * 100));
                $queue = $loadPct > 65 ? (int) round(($loadPct - 65) * 0.6) : 0;
                $segId = (int) ($l['segment_id'] ?? 0);
                if ($segId > 0) {
                    $perSegment[$segId] = [
                        'skiers'  => $skiers,
                        'loadPct' => $loadPct,
                        'queue'   => $queue,
                    ];
                }
            }
        }

        $liftUtilPct = $totalLiftCapHr > 0
            ? min(140, (int) round(($onLifts * 11.0 / $totalLiftCapHr) * 100))
            : 0;

        // Distribute village guests across open buildings via Economy::buildingUtilization()
        $perBuilding = [];
        if (!empty($openBuildings)) {
            $tourists = $isWinter ? 0 : (int) round($demand * Economy::SUMMER_TOURIST_SHARE);
            $utilByType = Economy::buildingUtilization($openBuildings, $dailyProjected, $tourists);
            $bWeights = [];
            foreach ($openBuildings as $b) {
                $bType = (string) ($b['building_type'] ?? '');
                $cap = max(1, (int) ($b['capacity'] ?? 10));
                $u = max(0.05, (float) ($utilByType[$bType] ?? 0.2));
                $bWeights[] = $cap * $u;
            }
            $allocB = self::allocateProportional($openBuildings, $bWeights, $inVillage);
            foreach ($openBuildings as $idx => $b) {
                $bId = (int) ($b['id'] ?? 0);
                $bType = (string) ($b['building_type'] ?? '');
                $cap = max(1, (int) ($b['capacity'] ?? 1));
                $guests = $allocB[$idx];
                $typeUtilPct = min(100, (int) round(($utilByType[$bType] ?? 0) * 100));
                $perBuilding[$bId] = [
                    'guests'       => $guests,
                    'occupancyPct' => max($typeUtilPct, min(100, (int) round(($guests / $cap) * 100))),
                ];
            }
        }

        $snap = [
            'liveTotal'          => $liveTotal,
            'onSlopes'           => $onSlopes,
            'onLifts'            => $onLifts,
            'inVillage'          => $inVillage,
            'dailyProjected'     => $dailyProjected,
            'dailyLastTick'      => $dailyLastTick,
            'liftUtilizationPct' => $liftUtilPct,
            'resortOpen'         => true,
            'operatingPhase'     => $phase,
            'nightLit'           => $nightLit,
            'serverNow'          => time(),
            'perSegment'         => $perSegment,
            'perBuilding'        => $perBuilding,
        ];

        return self::$requestCache[$userId] = $snap;
    }

    /**
     * @return array{string, float, float, float} [phase, concurrency, liftShare, villageShare]
     */
    private static function clockPhase(bool $nightLit, int $nightCoverage): array
    {
        $tz = new \DateTimeZone(config('App')->appTimezone ?: 'America/New_York');
        $now = new \DateTimeImmutable('now', $tz);
        $hour = (int) $now->format('G') + ((int) $now->format('i')) / 60.0;

        if ($hour >= 8.0 && $hour < 9.5) {
            $t = ($hour - 8.0) / 1.5;
            return ['morning', 0.28 + 0.18 * $t, 0.28, 0.18];
        }
        if ($hour >= 9.5 && $hour < 11.5) {
            return ['peak', 0.50, 0.26, 0.18];
        }
        if ($hour >= 11.5 && $hour < 13.5) {
            return ['lunch', 0.48, 0.20, 0.34];
        }
        if ($hour >= 13.5 && $hour < 16.0) {
            $t = ($hour - 13.5) / 2.5;
            return ['afternoon', 0.44 - 0.10 * $t, 0.25, 0.20];
        }
        if ($hour >= 16.0 && $hour < 21.0) {
            if ($nightLit) {
                $boost = min(0.14, $nightCoverage * 0.004);
                return ['night_skiing', 0.24 + $boost, 0.22, 0.34];
            }
            return ['evening', 0.20, 0.18, 0.38];
        }

        // Late evening / early morning: keep a gentle resort glow rather than dropping to 0
        if ($nightLit) {
            return ['night_skiing', 0.18, 0.20, 0.38];
        }
        return ['evening', 0.14, 0.18, 0.38];
    }

    private static function emptySnapshot(bool $resortOpen, string $phase, bool $nightLit, int $dailyLastTick = 0): array
    {
        return [
            'liveTotal'          => 0,
            'onSlopes'           => 0,
            'onLifts'            => 0,
            'inVillage'          => 0,
            'dailyProjected'     => 0,
            'dailyLastTick'      => $dailyLastTick,
            'liftUtilizationPct' => 0,
            'resortOpen'         => $resortOpen,
            'operatingPhase'     => $phase,
            'nightLit'           => $nightLit,
            'serverNow'          => time(),
            'perSegment'         => [],
            'perBuilding'        => [],
        ];
    }

    /** Integer largest-remainder proportional allocator so parts sum to exact `$total`. */
    private static function allocateProportional(array $items, array $weights, int $total): array
    {
        $n = count($items);
        if ($n === 0 || $total <= 0) {
            return array_fill(0, $n, 0);
        }
        $sumW = array_sum($weights);
        if ($sumW <= 0) {
            $weights = array_fill(0, $n, 1.0);
            $sumW = (float) $n;
        }
        $result = array_fill(0, $n, 0);
        $remainders = [];
        $assigned = 0;
        for ($i = 0; $i < $n; $i++) {
            $exact = ($weights[$i] / $sumW) * $total;
            $floor = (int) floor($exact);
            $result[$i] = $floor;
            $assigned += $floor;
            $remainders[$i] = $exact - $floor;
        }
        $diff = $total - $assigned;
        if ($diff > 0) {
            arsort($remainders);
            foreach (array_keys($remainders) as $idx) {
                if ($diff <= 0) {
                    break;
                }
                $result[$idx]++;
                $diff--;
            }
        }
        return $result;
    }
}
