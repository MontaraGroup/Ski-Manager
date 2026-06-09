<?php

function hourlyTemp(int $baseTemp): int
{
    $hour = (int) date('G');
    $curve = [-4,-4,-3,-3,-4,-4,-3,-2,-1,0,1,2,3,4,4,3,2,1,0,-1,-2,-2,-3,-3];
    return $baseTemp + $curve[$hour];
}

function getHourlyForecast(int $baseTemp, int $baseWind, string $condition): array
{
    $hours = [];
    $curve = [-4,-4,-3,-3,-4,-4,-3,-2,-1,0,1,2,3,4,4,3,2,1,0,-1,-2,-2,-3,-3];
    $windCurve = [0.7,0.6,0.6,0.5,0.5,0.6,0.7,0.8,0.9,1.0,1.1,1.2,1.3,1.3,1.2,1.1,1.0,0.9,0.8,0.8,0.7,0.7,0.7,0.7];
    for ($h = 0; $h < 24; $h++) {
        $t = $baseTemp + $curve[$h];
        $w = (int) round($baseWind * $windCurve[$h]);
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
