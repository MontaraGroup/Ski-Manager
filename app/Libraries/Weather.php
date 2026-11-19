<?php

namespace App\Libraries;

/**
 * Mountain weather. Each day is generated from a fixed seed (season + day), so the 5-day forecast
 * is exactly what will happen. Temperatures follow the season (early winter, deep winter, spring,
 * summer) plus multi-day weather fronts; precipitation type follows the temperature; the snow base
 * builds with snowfall and melts with warmth and rain. Rows live in the `weather` table and are
 * generated on demand (ensure()), so a missing day can never happen.
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
    public const WIND_HOLD_FACTOR = 0.75;

    public static function demandFactor(string $condition, int $wind): float
    {
        return (self::DEMAND[$condition] ?? 1.0) * ($wind >= self::WIND_HOLD ? self::WIND_HOLD_FACTOR : 1.0);
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
            $db->table('weather')->insert([
                'game_day' => $d, 'temp' => $w['temp'], 'condition_name' => $w['condition'], 'wind' => $w['wind'],
                'snowfall' => $w['snowfall'], 'visibility' => $w['visibility'], 'humidity' => $w['humidity'],
                'snow_base' => $base, 'forecast' => json_encode(self::forecast($season, $d, $winterDays)), 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** The next five days exactly as they will be generated */
    public static function forecast(int $season, int $fromDay, int $winterDays): array
    {
        $out = [];
        for ($i = 1; $i <= 5; $i++) {
            $w = self::day($season, $fromDay + $i, $winterDays);
            $out[] = ['day' => $i, 'temp' => $w['temp'], 'condition' => $w['condition'], 'snowfall' => $w['snowfall'], 'wind' => $w['wind'], 'humidity' => $w['humidity']];
        }
        return $out;
    }
}
