<?php

function hourlyTemp(int $baseTemp): int
{
    $hour = (int) date('G');
    $curve = [-4,-4,-3,-3,-4,-4,-3,-2,-1,0,1,2,3,4,4,3,2,1,0,-1,-2,-2,-3,-3];
    return $baseTemp + ($curve[$hour] ?? 0);
}

function getHourlyForecast(int $baseTemp, int $baseWind, string $condition): array
{
    $hours = [];
    $curve = [-4,-4,-3,-3,-4,-4,-3,-2,-1,0,1,2,3,4,4,3,2,1,0,-1,-2,-2,-3,-3];
    $windCurve = [0.7,0.6,0.6,0.5,0.5,0.6,0.7,0.8,0.9,1.0,1.1,1.2,1.3,1.3,1.2,1.1,1.0,0.9,0.8,0.8,0.7,0.7,0.7,0.7];
    for ($h = 0; $h < 24; $h++) {
        $t = $baseTemp + ($curve[$h] ?? 0);
        $w = (int) round($baseWind * ($windCurve[$h] ?? 1.0));
        $hours[] = [
            'hour' => $h,
            'label' => ($h === 0 ? '12a' : ($h < 12 ? $h . 'a' : ($h === 12 ? '12p' : ($h - 12) . 'p'))),
            'temp' => $t,
            'wind' => $w,
            'can_snow' => $t <= -1,
            'condition' => $condition,
        ];
    }
    return $hours;
}

function snowmakingWindow(int $baseTemp): array
{
    $curve = [-4,-4,-3,-3,-4,-4,-3,-2,-1,0,1,2,3,4,4,3,2,1,0,-1,-2,-2,-3,-3];
    $window = [];
    foreach ($curve as $h => $offset) {
        if (($baseTemp + $offset) <= -2) $window[] = $h;
    }
    return $window;
}

function weatherEmoji(string $condition): string
{
    return match($condition) {
        'Sunny' => '☀️',
        'Partly Cloudy' => '⛅',
        'Cloudy' => '☁️',
        'Light Snow' => '🌨️',
        'Heavy Snow' => '❄️',
        'Blizzard' => '🌪️',
        'Freezing Rain' => '🌧️',
        default => '☁️',
    };
}

function weatherIcon(string $condition): string
{
    return match($condition) {
        'Sunny' => 'fa-sun text-warning',
        'Partly Cloudy' => 'fa-cloud-sun text-warning',
        'Cloudy' => 'fa-cloud text-base-content/50',
        'Light Snow' => 'fa-snowflake text-info',
        'Heavy Snow' => 'fa-snowflake text-info',
        'Blizzard' => 'fa-wind text-info',
        'Freezing Rain' => 'fa-cloud-rain text-primary',
        default => 'fa-cloud-sun text-warning',
    };
}

function getCurrentWeather(): array
{
    static $weatherData = null;
    if ($weatherData !== null) {
        return $weatherData;
    }

    $db = db_connect();
    $gameDay = function_exists('getGameDay') ? getGameDay() : 1;

    // 1. ALWAYS query for today's active game day
    $row = $db->table('weather')->where('game_day', $gameDay)->get()->getRowArray();

    if (!$row) {
        // Fallback: lookup latest game_day <= today
        $row = $db->table('weather')->where('game_day <=', $gameDay)->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();

        // If none, take any latest
        if (!$row) {
            $row = $db->table('weather')->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();
        }
    }

    $baseTemp = $row ? (int)$row['temp'] : -5;
    $currentTemp = hourlyTemp($baseTemp);
    $condition = $row['condition_name'] ?? 'Partly Cloudy';
    $baseWind = $row ? (int)$row['wind'] : 15;
    $hour = (int) date('G');
    $windCurve = [0.7,0.6,0.6,0.5,0.5,0.6,0.7,0.8,0.9,1.0,1.1,1.2,1.3,1.3,1.2,1.1,1.0,0.9,0.8,0.8,0.7,0.7,0.7,0.7];
    $currentWind = (int) round($baseWind * ($windCurve[$hour] ?? 1.0));
    $snowBase = $row ? (int)$row['snow_base'] : 50;
    $snowfall = $row ? (int)($row['snowfall'] ?? 0) : 0;
    $humidity = $row ? (int)($row['humidity'] ?? 70) : 70;
    $visibility = $row['visibility'] ?? 'Good';
    $canMakeSnow = $currentTemp <= -2;

    $rawForecast = $row['forecast'] ?? '[]';
    $forecastArr = is_string($rawForecast) ? (json_decode($rawForecast, true) ?? []) : (is_array($rawForecast) ? $rawForecast : []);

    $weatherData = [
        'id'                 => $row['id'] ?? null,
        'game_day'           => $row['game_day'] ?? $gameDay,
        'base_temp'          => $baseTemp,
        'temp'               => $currentTemp,           // Active hourly temperature (int)
        'temp_celsius'       => $currentTemp,
        'temp_formatted'     => temp($currentTemp),     // Unified formatted string (e.g. 27°F or -3°C)
        'condition'          => $condition,
        'condition_name'     => $condition,
        'icon'               => weatherIcon($condition),
        'emoji'              => weatherEmoji($condition),
        'base_wind'          => $baseWind,
        'wind'               => $currentWind,           // Active hourly wind (int)
        'wind_formatted'     => speed($currentWind),
        'snowfall'           => $snowfall,
        'snowfall_formatted' => snow($snowfall),
        'snow_base'          => $snowBase,
        'snow_formatted'     => snow($snowBase),
        'visibility'         => $visibility,
        'humidity'           => $humidity,
        'can_make_snow'      => $canMakeSnow,
        'forecast'           => $forecastArr,
        'forecast_raw'       => is_string($rawForecast) ? $rawForecast : json_encode($forecastArr),
    ];

    return $weatherData;
}
