<?php

namespace App\Controllers;

use App\Models\WeatherModel;

class Weather extends BaseController
{
    public function index(): string
    {
        helper('weather');
        $weatherData = getCurrentWeather();
        $baseTemp = (int) $weatherData['base_temp'];
        $hourly = getHourlyForecast($baseTemp, (int) $weatherData['base_wind'], $weatherData['condition_name']);
        $snowWindow = snowmakingWindow($baseTemp);

        $weather = [
            'temp' => $weatherData['temp'],
            'base_temp' => $baseTemp,
            'condition' => $weatherData['condition'],
            'wind' => $weatherData['wind'],
            'snowfall' => $weatherData['snowfall'],
            'visibility' => $weatherData['visibility'],
            'humidity' => $weatherData['humidity'],
            'snow_base' => $weatherData['snow_base'],
        ];

        $forecast = is_array($weatherData['forecast']) ? $weatherData['forecast'] : (json_decode($weatherData['forecast'] ?? '[]', true) ?? []);

        return view('weather/index', [
            'weather' => $weather,
            'forecast' => $forecast,
            'hourly' => $hourly,
            'snowWindow' => $snowWindow,
            'gameDay' => $weatherData['game_day'],
            'currentHour' => (int) date('G'),
        ]);
    }

    private function generateAndSave(WeatherModel $model, array $resort, int $day, ?array $prev): void
    {
        $seed = crc32('skimanager-weather-day-' . $day);
        mt_srand($seed);

        $seasonLength = function_exists('getSeasonLength') ? getSeasonLength() : 135;
        $winterDays = function_exists('getWinterDays') ? getWinterDays() : 100;
        $seasonDay = (($day - 1) % $seasonLength) + 1;
        $isSummer = $seasonDay > $winterDays;
        $isDeepWinter = $seasonDay >= 30 && $seasonDay <= ($winterDays - 20);

        if ($isSummer) {
            $temp = mt_rand(18, 25);
            $conditions = ['Sunny', 'Partly Cloudy', 'Cloudy'];
            $weights = [50, 35, 15];
        } elseif ($isDeepWinter) {
            $baseTemp = ['low' => -4, 'medium' => -10, 'high' => -16];
            $temp = ($baseTemp[$resort['altitude']] ?? -10) + mt_rand(-4, 4);
            $conditions = ['Sunny', 'Partly Cloudy', 'Cloudy', 'Light Snow', 'Heavy Snow', 'Blizzard'];
            $weights = [10, 15, 15, 25, 20, 15];
        } else {
            $baseTemp = ['low' => -1, 'medium' => -6, 'high' => -12];
            $temp = ($baseTemp[$resort['altitude']] ?? -6) + mt_rand(-4, 4);
            $conditions = ['Sunny', 'Partly Cloudy', 'Cloudy', 'Light Snow', 'Heavy Snow', 'Freezing Rain'];
            $weights = [20, 25, 20, 20, 10, 5];
        }

        $roll = mt_rand(1, array_sum($weights));
        $cumulative = 0;
        $condition = $conditions[0];
        foreach ($conditions as $i => $c) {
            $cumulative += $weights[$i];
            if ($roll <= $cumulative) { $condition = $c; break; }
        }

        $windSpeeds = ['north' => mt_rand(5, 20), 'east' => mt_rand(10, 30), 'south' => mt_rand(5, 15), 'west' => mt_rand(10, 35)];
        $wind = $windSpeeds[$resort['aspect']] ?? mt_rand(8, 22);

        $snowfall = 0;
        if (!$isSummer && in_array($condition, ['Light Snow', 'Heavy Snow', 'Blizzard'])) {
            $snowfall = $condition === 'Light Snow' ? mt_rand(1, 5) : ($condition === 'Heavy Snow' ? mt_rand(5, 15) : mt_rand(15, 30));
        }

        $visibilityMap = ['Sunny' => 'Excellent', 'Partly Cloudy' => 'Good', 'Cloudy' => 'Good', 'Light Snow' => 'Moderate', 'Heavy Snow' => 'Poor', 'Blizzard' => 'Very Poor', 'Freezing Rain' => 'Poor'];

        $prevBase = $prev ? (int) $prev['snow_base'] : ($isSummer ? 0 : 50);
        $snowBase = $isSummer ? max(0, $prevBase - mt_rand(30, 60)) : max(0, $prevBase + $snowfall - ($condition === 'Sunny' ? mt_rand(1, 3) : 0));

        $forecast = [];
        for ($d = 1; $d <= 5; $d++) {
            $fDay = $day + $d;
            $fSeasonDay = (($fDay - 1) % $seasonLength) + 1;
            $fIsSummer = $fSeasonDay > $winterDays;
            mt_srand(crc32('skimanager-weather-day-' . $fDay));
            if ($fIsSummer) {
                $fTemp = mt_rand(18, 25);
                $fConds = ['Sunny', 'Partly Cloudy', 'Cloudy'];
                $cond = $fConds[mt_rand(0, count($fConds) - 1)];
                $fSnow = 0;
            } else {
                $fTemp = $temp + mt_rand(-3, 3);
                if ($fTemp <= -5) { $fConds = ['Sunny','Partly Cloudy','Cloudy','Light Snow','Heavy Snow','Blizzard']; }
                elseif ($fTemp <= 0) { $fConds = ['Sunny','Partly Cloudy','Cloudy','Light Snow','Freezing Rain']; }
                else { $fConds = ['Sunny','Partly Cloudy','Cloudy']; }
                $cond = $fConds[mt_rand(0, count($fConds) - 1)];
                $fSnow = match($cond) { 'Light Snow' => mt_rand(1, 5), 'Heavy Snow' => mt_rand(6, 15), 'Blizzard' => mt_rand(15, 30), default => 0 };
            }
            $forecast[] = [
                'day' => $d,
                'temp' => $fTemp,
                'condition' => $cond,
                'snowfall' => $fSnow,
            ];
        }

        $model->insert([
            'game_day' => $day,
            'temp' => $temp,
            'condition_name' => $condition,
            'wind' => $wind,
            'snowfall' => $snowfall,
            'visibility' => $visibilityMap[$condition] ?? 'Good',
            'humidity' => mt_rand(40, 95),
            'snow_base' => $snowBase,
            'forecast' => json_encode($forecast),
        ]);
    }
}
