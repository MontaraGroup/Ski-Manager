<?php

namespace App\Libraries;

/**
 * Snow guns (equipment rows with equipment_type 'snowmaker') and the snow they make.
 *
 * - A gun makes snow in hours whose wet-bulb temperature (air temperature and humidity) is at or
 *   below its model's limit. Output ramps from MIN_EFFICIENCY at the limit to 100% RAMP °C colder.
 * - Each slope keeps its own layer of made snow (player_items.made_snow_cm). Its base is the natural
 *   base plus that layer; App\Libraries\Grooming::decay uses it for the thin-base wear.
 * - Each slope has a target base (snow_target_cm, 0 = no snowmaking). A switched-on gun in 'auto'
 *   runs only while one of its slopes is under target, and only as long as it needs; in 'max' it runs
 *   every cold hour. A gun covers up to `capacity` slopes: its priority slope first, then the
 *   slopes furthest under target. One gun per slope.
 * - Running cost, energy and water scale with the hours a gun actually runs.
 * - A crewed gun (or one with the automation kit) makes full output; crew levels add a bonus.
 * - Made snow melts like the natural base (Weather::melt).
 */
class Snowmaking
{
    /** Warmest wet-bulb hour (°C) each model still makes snow in; other models use Weather::SNOWMAKING_MAX_TEMP */
    public const MAX_TEMP = ['demaclenko_titan5' => 0, 'hkd_impulse' => 0];
    /** Models that make snow at any temperature */
    public const ALL_WEATHER = ['ta_aw50'];

    public const CREW_WEAR_RELIEF = 1;    // a gun with a crew member assigned wears this much less per day
    public const UNCREWED_OUTPUT = 70;    // % output without a crew member or automation kit
    public const CREW_LEVEL_BONUS = 3;    // % output per crew member level
    public const RAMP = 4;                // °C below the limit where output reaches 100%
    public const MIN_EFFICIENCY = 50;     // % output in an hour right at the limit
    public const CM_PER_OUTPUT = 2;       // cm of snow a day per catalog output point, at full output
    public const MAX_MADE = 150;          // cm of made snow a slope can hold
    public const TARGETS = [0, 30, 50, 80];
    public const DEFAULT_TARGET = 30;
    public const MODES = ['auto', 'max'];
    public const NOZZLE_BONUS = 1;        // °C warmer limit with ceramic nozzles
    public const UPGRADES = [
        'nozzles' => ['label' => 'Ceramic nozzles', 'share' => 0.25, 'icon' => 'fa-temperature-arrow-up'],
        'automation' => ['label' => 'Automation kit', 'share' => 0.20, 'icon' => 'fa-microchip'],
    ];

    /** Hour-by-hour offsets from the day's base temperature (same curve as weather_helper) */
    public const HOURLY_OFFSETS = [-4, -4, -3, -3, -4, -4, -3, -2, -1, 0, 1, 2, 3, 4, 4, 3, 2, 1, 0, -1, -2, -2, -3, -3];

    /** Wet-bulb temperature (Stull 2011), °C */
    public static function wetBulb(float $temp, int $humidity): float
    {
        $rh = max(5, min(99, $humidity));
        return $temp * atan(0.151977 * sqrt($rh + 8.313659)) + atan($temp + $rh) - atan($rh - 1.676331)
            + 0.00391838 * $rh ** 1.5 * atan(0.023101 * $rh) - 4.686035;
    }

    /** @return string[] upgrade keys the gun carries */
    public static function upgrades(array $gun): array
    {
        return array_values(array_intersect(array_keys(self::UPGRADES), explode(',', (string) ($gun['upgrades'] ?? ''))));
    }

    public static function has(array $gun, string $upgrade): bool
    {
        return in_array($upgrade, self::upgrades($gun), true);
    }

    public static function upgradeCost(string $upgrade, int $price): int
    {
        return (int) round($price * (self::UPGRADES[$upgrade]['share'] ?? 0));
    }

    /** Warmest wet-bulb °C the gun works in, or null for any temperature */
    public static function maxTemp(array $gun): ?int
    {
        if (in_array($gun['model_key'] ?? '', self::ALL_WEATHER, true)) return null;
        $max = self::MAX_TEMP[$gun['model_key'] ?? ''] ?? Weather::SNOWMAKING_MAX_TEMP;
        return $max + (self::has($gun, 'nozzles') ? self::NOZZLE_BONUS : 0);
    }

    /** @return float[] 24 values: output efficiency 0..1 for each hour (0 = too warm) */
    public static function hourly(array $gun, int $baseTemp, int $humidity): array
    {
        $max = self::maxTemp($gun);
        $out = [];
        foreach (self::HOURLY_OFFSETS as $o) {
            if ($max === null) { $out[] = 1.0; continue; }
            $wb = self::wetBulb($baseTemp + $o, $humidity);
            $out[] = $wb > $max ? 0.0 : min(1.0, (self::MIN_EFFICIENCY + (100 - self::MIN_EFFICIENCY) * ($max - $wb) / self::RAMP) / 100);
        }
        return $out;
    }

    /** Hours the gun can make snow on a day with this base temperature and humidity */
    public static function hours(array $gun, int $baseTemp, int $humidity = 70): int
    {
        return count(array_filter(self::hourly($gun, $baseTemp, $humidity), fn($e) => $e > 0));
    }

    /** Switched on, working and cold enough today (it may still stand by in auto if no slope needs snow) */
    public static function canRun(array $gun, int $baseTemp, int $humidity = 70): bool
    {
        return ($gun['status'] ?? '') === 'active' && (int) ($gun['condition_pct'] ?? 0) > 0 && self::hours($gun, $baseTemp, $humidity) > 0;
    }

    /** Output factor from crew and upgrades (1.0 = full) */
    public static function crewFactor(array $gun, ?int $crewLevel): float
    {
        if ($crewLevel !== null) return 1 + self::CREW_LEVEL_BONUS * max(0, $crewLevel) / 100;
        return self::has($gun, 'automation') ? 1.0 : self::UNCREWED_OUTPUT / 100;
    }

    /** cm a day the gun makes if it runs every cold hour */
    public static function potential(array $gun, int $baseTemp, int $humidity, ?int $crewLevel): float
    {
        return (int) ($gun['output_per_day'] ?? 0) * self::CM_PER_OUTPUT
            * array_sum(self::hourly($gun, $baseTemp, $humidity)) / 24 * self::crewFactor($gun, $crewLevel);
    }

    public static function mode(array $gun): string
    {
        return in_array($gun['snow_mode'] ?? '', self::MODES, true) ? $gun['snow_mode'] : 'auto';
    }

    public static function target(array $slope): int
    {
        return (int) ($slope['snow_target_cm'] ?? self::DEFAULT_TARGET);
    }

    public static function prioritySlope(array $gun): ?int
    {
        return preg_match('/^slope_(\d+)$/', (string) ($gun['assigned_to'] ?? ''), $m) ? (int) $m[1] : null;
    }

    /** @return array<string, int> gun name => crew member level, for active snowmaker staff assigned to a gun */
    public static function crewLevels(array $staff): array
    {
        $out = [];
        foreach ($staff as $s) {
            if (($s['role'] ?? '') === 'snowmaker' && !empty($s['assigned_to']) && ($s['status'] ?? 'active') === 'active') {
                $out[$s['assigned_to']] = max($out[$s['assigned_to']] ?? 0, (int) ($s['level'] ?? 1));
            }
        }
        return $out;
    }

    /**
     * Today's snowmaking. Pure: no reads or writes.
     * @param array $guns all the player's snow guns
     * @param array $slopes open slopes (made_snow_cm already melted for today)
     * @param array $weather temp (base °C), humidity, snow_base
     * @param array<string, int> $crew gun name => crew level (crewLevels())
     * @return array{guns: array<int, array>, made: array<int, float>, covered: array<int, int>, cm: float}
     *   guns: gun id => [hours, hours_run, share (hours_run/24), potential, cm, slopes]; only guns that run
     *   made: slope id => cm added; covered: slope id => gun id
     */
    public static function plan(array $guns, array $slopes, array $weather, array $crew = []): array
    {
        $temp = (int) ($weather['temp'] ?? $weather['base_temp'] ?? 0);
        $humidity = (int) ($weather['humidity'] ?? 70);
        $natural = (int) ($weather['snow_base'] ?? 0);
        $open = [];
        foreach ($slopes as $s) {
            $target = self::target($s);
            if ($target <= 0) continue;
            $made = (float) ($s['made_snow_cm'] ?? 0);
            $open[(int) $s['id']] = ['need' => max(0, $target - $natural - $made), 'base' => $natural + $made, 'room' => max(0, self::MAX_MADE - $made)];
        }

        $ready = [];
        foreach ($guns as $g) {
            if (!self::canRun($g, $temp, $humidity)) continue;
            $p = self::potential($g, $temp, $humidity, $crew[$g['name'] ?? ''] ?? null);
            if ($p <= 0) continue;
            $ready[(int) $g['id']] = ['gun' => $g, 'potential' => $p, 'max' => self::mode($g) === 'max', 'slopes' => []];
        }
        // Biggest guns pick first, so small ones are left for the stragglers
        uasort($ready, fn($a, $b) => $b['potential'] <=> $a['potential']);

        $wants = fn(int $slopeId, array $r) => isset($open[$slopeId]) && $open[$slopeId]['room'] > 0 && ($r['max'] || $open[$slopeId]['need'] > 0);
        $covered = [];
        foreach ($ready as $id => &$r) {
            $p = self::prioritySlope($r['gun']);
            if ($p && !isset($covered[$p]) && $wants($p, $r)) {
                $covered[$p] = $id;
                $r['slopes'][] = $p;
            }
        }
        unset($r);
        $byNeed = array_keys($open);
        usort($byNeed, fn($a, $b) => [$open[$b]['need'], $open[$a]['base']] <=> [$open[$a]['need'], $open[$b]['base']]);
        foreach ($ready as $id => &$r) {
            $cap = max(1, (int) ($r['gun']['capacity'] ?? 1));
            foreach ($byNeed as $sid) {
                if (count($r['slopes']) >= $cap) break;
                if (isset($covered[$sid]) || !$wants($sid, $r)) continue;
                $covered[$sid] = $id;
                $r['slopes'][] = $sid;
            }
        }
        unset($r);

        $out = ['guns' => [], 'made' => [], 'covered' => $covered, 'cm' => 0.0];
        foreach ($ready as $id => $r) {
            if (!$r['slopes']) continue;
            $limit = fn(int $sid) => $r['max'] ? $open[$sid]['room'] : min($open[$sid]['need'], $open[$sid]['room']);
            $wanted = array_sum(array_map($limit, $r['slopes']));
            $cm = min($r['potential'], $wanted);
            // Share it out: slopes that need least get theirs first, the rest split what is left
            $order = $r['slopes'];
            usort($order, fn($a, $b) => $limit($a) <=> $limit($b));
            $left = $cm;
            foreach ($order as $i => $sid) {
                $give = min($limit($sid), $left / (count($order) - $i));
                $out['made'][$sid] = $give;
                $left -= $give;
            }
            $hours = self::hours($r['gun'], $temp, $humidity);
            $hoursRun = $hours * ($r['potential'] > 0 ? $cm / $r['potential'] : 0);
            $out['guns'][$id] = [
                'hours' => $hours, 'hours_run' => round($hoursRun, 1), 'share' => $hoursRun / 24,
                'potential' => $r['potential'], 'cm' => $cm, 'slopes' => $r['slopes'],
            ];
            $out['cm'] += $cm;
        }
        return $out;
    }

    /** A gun row with today's running cost, energy and water scaled to the hours it runs */
    public static function scaled(array $gun, float $share): array
    {
        foreach (['fuel_cost', 'energy_kwh', 'water_liters'] as $k) $gun[$k] = (int) round((int) ($gun[$k] ?? 0) * $share);
        return $gun;
    }

    /**
     * Nightly tick: melt yesterday's made snow, make today's, save it.
     * @return array{guns: array, made: array<int, float>, covered: array<int, int>, cm: float}
     *   guns: the guns that ran, with costs scaled (scaled())
     */
    public static function runDay(int $userId, array $weather, array $guns, array $staff, bool $summer): array
    {
        $db = db_connect();
        $slopes = $db->table('player_items')->select('id, status, made_snow_cm, snow_target_cm')
            ->where('user_id', $userId)->where('item_type', 'slope')->get()->getResultArray();
        $melt = Weather::melt($weather, $summer);
        $before = [];
        foreach ($slopes as &$s) {
            $before[(int) $s['id']] = (int) $s['made_snow_cm'];
            $s['made_snow_cm'] = max(0, (int) $s['made_snow_cm'] - $melt);
        }
        unset($s);

        $plan = self::plan($guns, array_filter($slopes, fn($s) => $s['status'] === 'open'), $weather, self::crewLevels($staff));
        foreach ($slopes as $s) {
            $sid = (int) $s['id'];
            $new = (int) min(self::MAX_MADE, round($s['made_snow_cm'] + ($plan['made'][$sid] ?? 0)));
            if ($new !== $before[$sid]) $db->table('player_items')->where('id', $sid)->update(['made_snow_cm' => $new]);
        }

        $ran = [];
        foreach ($guns as $g) {
            if (isset($plan['guns'][(int) $g['id']])) $ran[] = self::scaled($g, $plan['guns'][(int) $g['id']]['share']);
        }
        $plan['guns'] = $ran;
        return $plan;
    }

    /**
     * Which gun covers each open slope today (what the tick would do if it ran now).
     * @return array<int, int> slope id => gun id
     */
    public static function coverage(array $openSlopes, array $guns, array $weather, array $staff = []): array
    {
        return self::plan($guns, $openSlopes, $weather, self::crewLevels($staff))['covered'];
    }

    /**
     * Today's plan for a player, for pages (the tick melts first and saves; this only reads).
     * @return array plan() plus weather (temp, humidity, snow_base), all guns, open slopes, crew,
     *   and running: the guns that run with their costs scaled
     */
    public static function today(int $userId): array
    {
        helper('weather');
        $db = db_connect();
        $w = getCurrentWeather();
        $weather = ['temp' => (int) $w['base_temp'], 'humidity' => (int) $w['humidity'], 'snow_base' => (int) $w['snow_base']];
        $guns = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->orderBy('id')->get()->getResultArray();
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->get()->getResultArray();
        $staff = $db->table('staff')->where('user_id', $userId)->where('role', 'snowmaker')->where('status', 'active')->get()->getResultArray();
        $plan = self::plan($guns, $slopes, $weather, self::crewLevels($staff));
        $plan['running'] = array_values(array_filter(array_map(fn($g) => isset($plan['guns'][(int) $g['id']]) ? self::scaled($g, $plan['guns'][(int) $g['id']]['share']) : null, $guns)));
        return $plan + ['weather' => $weather, 'all_guns' => $guns, 'open_slopes' => $slopes, 'staff' => $staff];
    }

    /** Natural base plus the slope's made snow, cm */
    public static function base(array $slope, int $naturalBase): int
    {
        return $naturalBase + (int) ($slope['made_snow_cm'] ?? 0);
    }
}
