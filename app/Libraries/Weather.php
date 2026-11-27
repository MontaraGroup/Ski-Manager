<?php

namespace App\Libraries;

/**
 * Mountain weather. Each day is generated from a fixed seed (season + day), so what the tick settles
 * is fixed in advance. Temperatures follow the season (early winter, deep winter, spring, summer)
 * plus multi-day weather fronts; precipitation type follows the temperature; the snow base builds
 * with snowfall and melts with warmth and rain. Rows live in the `weather` table (one per game day)
 * and are generated on demand (ensure()), so a missing day can never happen.
 *
 * The nightly tick at 00:05 settles the game day that starts then, so during day N the day that
 * matters for planning is N + 1 (settleDay()). Players see that day exactly; later days come as
 * ranges that widen with distance (outlook()). Weather Pro (App\Libraries\WeatherPro) sees further
 * and with narrower ranges.
 */
class Weather
{
    public const START_BASE = 20;          // cm of natural snow on day 1: early season leans on snow guns
    public const THIN_BASE = 30;           // below this, slopes without snowmaking wear faster
    public const FRONT_DAYS = 4;           // a weather front lasts this many days
    public const SNOWMAKING_MAX_TEMP = -2; // snow guns need an hour at or below this

    public const VISIBILITY = [
        'Sunny' => 'Excellent', 'Partly Cloudy' => 'Good', 'Cloudy' => 'Good', 'Light Snow' => 'Moderate',
        'Heavy Snow' => 'Poor', 'Blizzard' => 'Very Poor', 'Freezing Rain' => 'Poor', 'Rain' => 'Moderate',
    ];

    /** Share of skiers who still come in this weather (economy v2) */
    public const DEMAND = [
        'Sunny' => 1.10, 'Partly Cloudy' => 1.05, 'Cloudy' => 1.00, 'Light Snow' => 1.05,
        'Heavy Snow' => 0.85, 'Blizzard' => 0.50, 'Freezing Rain' => 0.75, 'Rain' => 0.70,
    ];
    public const WIND_HOLD = 50;            // km/h: lifts go on wind hold
    public const WIND_HOLD_FACTOR = 0.75;   // skiers when the whole day is on wind hold (old rule, flag off)
    /** PostHog flag: wind stops chairlifts, gondolas and cable cars hour by hour instead of WIND_HOLD_FACTOR */
    public const WIND_RULE_FLAG = 'wind-lift-hold';
    /** Lifts that keep running in any wind */
    public const WIND_PROOF_LIFTS = ['button'];
    /** Hours lifts run (Economy::LIFT_HOURS of them) */
    public const LIFT_DAY = [10, 11, 12, 13, 14, 15];

    /** Offsets from the day's base temperature and multipliers of its wind, hour 0..23 */
    public const TEMP_CURVE = [-4, -4, -3, -3, -4, -4, -3, -2, -1, 0, 1, 2, 3, 4, 4, 3, 2, 1, 0, -1, -2, -2, -3, -3];
    public const WIND_CURVE = [0.7, 0.6, 0.6, 0.5, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0, 1.1, 1.2, 1.3, 1.3, 1.2, 1.1, 1.0, 0.9, 0.8, 0.8, 0.7, 0.7, 0.7, 0.7];

    /** Forecast reach: days shown, and how many of them are exact */
    public const FREE_DAYS = 5;
    public const FREE_EXACT = 2;
    public const PRO_DAYS = 14;
    public const PRO_EXACT = 3;

    /**
     * Share of skiers who come in this weather. With the wind rule on, wind no longer scares skiers
     * off here: it stops lifts instead (exposedLiftShare()).
     */
    public static function demandFactor(string $condition, int $wind, bool $windRule = false): float
    {
        return (self::DEMAND[$condition] ?? 1.0) * (!$windRule && $wind >= self::WIND_HOLD ? self::WIND_HOLD_FACTOR : 1.0);
    }

    /** Whether the wind rule applies to this player */
    public static function windRule(int $userId): bool
    {
        return function_exists('featureEnabledFor') && featureEnabledFor(self::WIND_RULE_FLAG, $userId);
    }

    /** Lift hours with wind at or over WIND_HOLD, for a day with this base wind */
    public static function windHoldHours(int $baseWind): int
    {
        return count(array_filter(self::LIFT_DAY, fn($h) => (int) round($baseWind * self::WIND_CURVE[$h]) >= self::WIND_HOLD));
    }

    /** Share of the lift day that chairlifts, gondolas and cable cars run */
    public static function exposedLiftShare(int $baseWind): float
    {
        return 1 - self::windHoldHours($baseWind) / count(self::LIFT_DAY);
    }

    /** Weather Control (Génépis): clear, calm and cold enough for snow guns, whatever was coming */
    public static function withBoosts(array $w, array $boostTypes): array
    {
        if (!in_array('skip_weather', $boostTypes, true)) return $w;
        return array_merge($w, [
            'condition_name' => 'Sunny', 'wind' => min((int) ($w['wind'] ?? 0), 10), 'temp' => min((int) ($w['temp'] ?? -5), -3),
        ]);
    }

    /** Day of the season (1..season length) for a game day, as the pages number days */
    public static function seasonDayOf(int $gameDay): int
    {
        return ($gameDay - 1) % max(1, getSeasonLength()) + 1;
    }

    /** The game day tonight's tick settles */
    public static function settleDay(): int
    {
        return getGameDay() + 1;
    }

    /**
     * The weather row the next tick settles, with the player's Weather Control applied if it is
     * still running then. Same keys as a `weather` row.
     */
    public static function forSettle(?int $userId = null): array
    {
        $day = self::settleDay();
        self::ensure($day);
        $db = db_connect();
        $row = $db->table('weather')->where('game_day', $day)->get()->getRowArray()
            ?: ($db->table('weather')->where('game_day <=', $day)->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray() ?: []);
        if ($userId) {
            $settleAt = date('Y-m-d H:i:s', strtotime(\App\Libraries\Forecast::nextSettleAt()));
            $boosts = array_column($db->table('player_boosts')->select('boost_type')->where('user_id', $userId)->where('expires_at >', $settleAt)->get()->getResultArray(), 'boost_type');
            $row = self::withBoosts($row, $boosts);
        }
        return $row;
    }

    public static function phase(int $seasonDay, int $winterDays): string
    {
        return match (true) {
            $seasonDay > $winterDays => 'summer',
            $seasonDay <= 20 => 'early',
            $seasonDay <= $winterDays - 25 => 'deep',
            default => 'spring',
        };
    }

    /** Average daytime temperature for the day of the season */
    private static function meanTemp(int $seasonDay, int $winterDays): float
    {
        if ($seasonDay > $winterDays) return 17 + 3 * sin(($seasonDay - $winterDays) / 10);
        $points = [[1, -3], [30, -9], [$winterDays - 30, -8], [$winterDays, 2]];
        for ($i = 1; $i < count($points); $i++) {
            [$x0, $y0] = $points[$i - 1]; [$x1, $y1] = $points[$i];
            if ($seasonDay <= $x1) return $y0 + ($y1 - $y0) * ($seasonDay - $x0) / max(1, $x1 - $x0);
        }
        return 2;
    }

    /** One day's weather (without the snow base, which depends on the days before) */
    public static function day(int $season, int $day, int $winterDays): array
    {
        $seasonDay = $day; // game days run 1..season length
        $phase = self::phase($seasonDay, $winterDays);

        mt_srand(crc32('skimanager-weather-s' . $season . '-front-' . intdiv($day - 1, self::FRONT_DAYS)));
        $frontTemp = mt_rand(-4, 4);
        $frontStorm = mt_rand(-20, 20);

        mt_srand(crc32('skimanager-weather-s' . $season . '-day-' . $day));
        $temp = (int) round(self::meanTemp($seasonDay, $winterDays) + $frontTemp + mt_rand(-3, 3));
        $roll = mt_rand(0, 99);
        $intensity = mt_rand(0, 99);
        $amount = mt_rand(0, 1000) / 1000;
        $windRoll = mt_rand(0, 1000) / 1000;
        $humidRoll = mt_rand(0, 1000) / 1000;
        mt_srand(); // don't leave the global generator seeded

        $precipChance = ['early' => 45, 'deep' => 45, 'spring' => 35, 'summer' => 20][$phase] + $frontStorm;
        if ($roll < $precipChance) {
            if ($temp <= -1) {
                $condition = $intensity < 55 ? 'Light Snow' : ($intensity < 88 || $temp > -4 ? 'Heavy Snow' : 'Blizzard');
            } else {
                $condition = $temp <= 1 ? 'Freezing Rain' : 'Rain';
            }
        } else {
            $condition = $intensity < 40 ? 'Sunny' : ($intensity < 70 ? 'Partly Cloudy' : 'Cloudy');
        }

        $snowfall = match ($condition) {
            'Light Snow' => 2 + (int) round(4 * $amount),
            'Heavy Snow' => 8 + (int) round(10 * $amount),
            'Blizzard' => 18 + (int) round(17 * $amount),
            default => 0,
        };
        [$wMin, $wMax] = match ($condition) { 'Blizzard' => [45, 70], 'Heavy Snow' => [15, 35], 'Rain', 'Freezing Rain' => [10, 30], default => [5, 25] };
        [$hMin, $hMax] = match ($condition) { 'Sunny' => [35, 55], 'Partly Cloudy' => [50, 70], 'Cloudy' => [65, 85], default => [80, 98] };

        return [
            'temp' => $temp, 'condition' => $condition, 'snowfall' => $snowfall,
            'wind' => (int) round($wMin + ($wMax - $wMin) * $windRoll),
            'humidity' => (int) round($hMin + ($hMax - $hMin) * $humidRoll),
            'visibility' => self::VISIBILITY[$condition],
        ];
    }

    /** Snow base after a day: fresh snow adds, warmth and rain melt, deep snow settles */
    public static function nextBase(int $prevBase, array $w, bool $summer): int
    {
        $settle = $prevBase > 60 ? 1 : 0;
        return max(0, (int) round($prevBase + $w['snowfall'] - self::melt($w, $summer) - $settle));
    }

    /** cm of snow a day's warmth and rain melt (natural base and made snow alike) */
    public static function melt(array $w, bool $summer): float
    {
        $temp = (int) ($w['temp'] ?? 0);
        $condition = (string) ($w['condition'] ?? $w['condition_name'] ?? '');
        $melt = $temp > 0 ? 2 * $temp * ($condition === 'Sunny' ? 1.5 : 1) : 0;
        if (in_array($condition, ['Rain', 'Freezing Rain'], true)) $melt += 6;
        if ($summer) $melt = max($melt, 8);
        return $melt;
    }

    /** Make sure every day of the current season up to $upTo has a weather row */
    public static function ensure(int $upTo): void
    {
        $db = db_connect();
        $season = function_exists('getSeasonNumber') ? (int) getSeasonNumber() : 1;
        $winterDays = getWinterDays();
        $upTo = max(1, min($upTo, getSeasonLength() + 1));
        $rows = array_column($db->table('weather')->select('game_day, snow_base')->where('game_day <=', $upTo)->get()->getResultArray(), 'snow_base', 'game_day');
        if (count($rows) >= $upTo) return;

        $base = self::START_BASE;
        for ($d = 1; $d <= $upTo; $d++) {
            if (isset($rows[$d])) { $base = (int) $rows[$d]; continue; }
            $w = self::day($season, $d, $winterDays);
            $base = $d === 1 ? self::START_BASE + $w['snowfall'] : self::nextBase($base, $w, self::phase($d, $winterDays) === 'summer');
            // Two requests can generate the same day at once; the unique key on game_day keeps one
            $db->table('weather')->ignore(true)->insert([
                'game_day' => $d, 'temp' => $w['temp'], 'condition_name' => $w['condition'], 'wind' => $w['wind'],
                'snowfall' => $w['snowfall'], 'visibility' => $w['visibility'], 'humidity' => $w['humidity'],
                'snow_base' => $base, 'forecast' => json_encode(self::forecast($season, $d, $winterDays)), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** The next five days of this season exactly as they will be generated (stored on each row) */
    public static function forecast(int $season, int $fromDay, int $winterDays): array
    {
        $out = [];
        for ($i = 1; $i <= 5 && $fromDay + $i <= getSeasonLength(); $i++) {
            $w = self::day($season, $fromDay + $i, $winterDays);
            $out[] = ['day' => $i, 'temp' => $w['temp'], 'condition' => $w['condition'], 'snowfall' => $w['snowfall'], 'wind' => $w['wind'], 'humidity' => $w['humidity']];
        }
        return $out;
    }
    /**
     * The weather a game day will really have: its stored row when there is one (an admin may have
     * changed it), else the seeded day. Keys: temp, condition, snowfall, wind, humidity.
     */
    private static function truth(int $season, int $gameDay, int $winterDays, array $rows): array
    {
        if (isset($rows[$gameDay])) {
            $r = $rows[$gameDay];
            return ['temp' => (int) $r['temp'], 'condition' => (string) $r['condition_name'], 'snowfall' => (int) $r['snowfall'],
                'wind' => (int) $r['wind'], 'humidity' => (int) $r['humidity'], 'snow_base' => (int) $r['snow_base']];
        }
        return self::day($season, $gameDay, $winterDays);
    }

    /**
     * The forecast players see, from tonight's settle day on, up to the end of the season.
     * The first FREE_EXACT (Pro: PRO_EXACT) days are exact. Later days are ranges that always hold
     * the real value but widen with distance, and a chance of precipitation that can be wrong far
     * out. The ranges are seeded by season, day and distance, so every player sees the same forecast
     * and it sharpens as the day comes closer.
     *
     * @return list<array{gameDay: int, lead: int, exact: bool, confidence: int, condition: string,
     *   tempLow: int, tempHigh: int, tempMid: int, snowLow: int, snowHigh: int, precipChance: int,
     *   windLow: int, windHigh: int, windHold: string, humidity: int, base: int}>
     *   windHold: 'yes' | 'likely' | 'possible' | 'no'; base: projected natural snow base, cm
     */
    public static function outlook(bool $pro = false, ?int $fromDay = null): array
    {
        $fromDay ??= self::settleDay();
        $days = $pro ? self::PRO_DAYS : self::FREE_DAYS;
        $exactDays = $pro ? self::PRO_EXACT : self::FREE_EXACT;
        $season = function_exists('getSeasonNumber') ? (int) getSeasonNumber() : 1;
        $winterDays = getWinterDays();
        $lastDay = min($fromDay + $days - 1, getSeasonLength());
        self::ensure($fromDay);
        $rows = array_column(db_connect()->table('weather')->where('game_day >=', $fromDay - 1)->where('game_day <=', $lastDay)
            ->get()->getResultArray(), null, 'game_day');
        $base = (int) ($rows[$fromDay - 1]['snow_base'] ?? $rows[$fromDay]['snow_base'] ?? self::START_BASE);

        $out = [];
        for ($d = $fromDay; $d <= $lastDay; $d++) {
            $lead = $d - $fromDay + 1;
            $t = self::truth($season, $d, $winterDays, $rows);
            $far = max(0, $lead - $exactDays);
            $wet = $t['snowfall'] > 0 || in_array($t['condition'], ['Rain', 'Freezing Rain'], true);
            $summer = self::phase($d, $winterDays) === 'summer';

            if ($far === 0) {
                $f = ['exact' => true, 'confidence' => 100, 'condition' => $t['condition'],
                    'tempLow' => $t['temp'], 'tempHigh' => $t['temp'], 'snowLow' => $t['snowfall'], 'snowHigh' => $t['snowfall'],
                    'precipChance' => $wet ? 100 : 0, 'windLow' => $t['wind'], 'windHigh' => $t['wind']];
            } else {
                mt_srand(crc32('skimanager-outlook-s' . $season . '-d' . $d . '-l' . $lead . ($pro ? '-pro' : '')));
                $tSpread = $pro ? (int) ceil($far / 2) : $far;
                $tLow = $t['temp'] - mt_rand(0, 2 * $tSpread);
                $wSpread = (int) ceil(($pro ? 1.5 : 3) * $far);
                $wLow = max(0, $t['wind'] - mt_rand(0, 2 * $wSpread));
                $miss = mt_rand(0, min(60, ($pro ? 5 : 8) * $far));
                $snowSpread = min(0.8, ($pro ? 0.08 : 0.15) * $far);
                $wrongSnow = mt_rand(1, 5);
                mt_srand();

                $chance = (int) (round(($wet ? 100 - $miss : $miss) / 10) * 10);
                $tMid = (int) round($tLow + $tSpread);
                if ($chance >= 50) {
                    // Wet, or forecast wet when it won't be: the type follows the temperature
                    $condition = $wet ? $t['condition'] : ($tMid <= -1 ? 'Light Snow' : ($tMid <= 1 ? 'Freezing Rain' : 'Rain'));
                    $snow = $wet ? $t['snowfall'] : ($tMid <= -1 ? $wrongSnow : 0);
                } else {
                    $condition = $wet ? 'Cloudy' : $t['condition'];
                    $snow = 0;
                }
                $f = ['exact' => false, 'confidence' => max(30, 95 - ($pro ? 5 : 10) * ($far - 1)), 'condition' => $condition,
                    'tempLow' => $tLow, 'tempHigh' => $tLow + 2 * $tSpread,
                    'snowLow' => (int) floor($snow * (1 - $snowSpread)), 'snowHigh' => (int) ceil($snow * (1 + $snowSpread)),
                    'precipChance' => $chance, 'windLow' => $wLow, 'windHigh' => $wLow + 2 * $wSpread];
            }
            $f['tempMid'] = (int) round(($f['tempLow'] + $f['tempHigh']) / 2);
            $f['windHold'] = $f['windLow'] >= self::WIND_HOLD ? ($f['exact'] ? 'yes' : 'likely') : ($f['windHigh'] >= self::WIND_HOLD ? 'possible' : 'no');
            $f['humidity'] = (int) $t['humidity'];
            // Snow base from what the forecast says, so it doesn't give away the hidden days
            $base = $f['exact'] && isset($t['snow_base']) ? (int) $t['snow_base'] : self::nextBase($base, [
                'snowfall' => (int) round(($f['snowLow'] + $f['snowHigh']) / 2), 'temp' => $f['tempMid'], 'condition' => $f['condition'],
            ], $summer);
            $f['base'] = $base;
            $out[] = ['gameDay' => $d, 'lead' => $lead] + $f;
        }
        return $out;
    }

    /**
     * What to watch out for in an outlook: storms, wind holds, thaws and rain.
     * @return list<array{gameDay: int, kind: string, text: string}> kind: storm | wind | thaw
     */
    public static function callouts(array $outlook): array
    {
        $out = [];
        foreach ($outlook as $f) {
            $sure = $f['exact'] ? '' : ' (' . $f['precipChance'] . '% chance)';
            if ($f['snowHigh'] >= 15 || $f['condition'] === 'Blizzard') {
                $out[] = ['gameDay' => $f['gameDay'], 'kind' => 'storm', 'text' => ($f['condition'] === 'Blizzard' ? 'Blizzard' : 'Big snowfall') . ': '
                    . ($f['snowLow'] === $f['snowHigh'] ? snow($f['snowHigh']) : snow($f['snowLow']) . ' to ' . snow($f['snowHigh'])) . $sure . '. Powder after, fewer skiers during.'];
            }
            // 'possible' stays on the day's card: far out it would flag half the days
            if (in_array($f['windHold'], ['yes', 'likely'], true)) {
                $out[] = ['gameDay' => $f['gameDay'], 'kind' => 'wind', 'text' => $f['windHold'] === 'yes'
                    ? 'Wind hold: chairlifts and gondolas stop for ' . self::windHoldHours($f['windLow']) . ' of ' . count(self::LIFT_DAY) . ' lift hours.'
                    : 'Wind hold likely: ' . speed($f['windLow']) . ' to ' . speed($f['windHigh']) . '.'];
            }
            if (in_array($f['condition'], ['Rain', 'Freezing Rain'], true) || $f['tempLow'] > 0) {
                $out[] = ['gameDay' => $f['gameDay'], 'kind' => 'thaw', 'text' => (in_array($f['condition'], ['Rain', 'Freezing Rain'], true) ? 'Rain' : 'Thaw')
                    . $sure . ': the base melts and slopes without snow guns go icy or bare.'];
            }
        }
        return $out;
    }
}
