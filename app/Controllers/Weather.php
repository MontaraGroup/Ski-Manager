<?php

namespace App\Controllers;

use App\Models\WeatherModel;

class Weather extends BaseController
{
    public function index(): string
    {
        $model = new WeatherModel();

        $startDate = getSeasonStartDate();
        $today = date('Y-m-d');
        $gameDay = max(1, (int) ((strtotime($today) - strtotime($startDate)) / 86400) + 1);

        $current = $model->where('game_day', $gameDay)->first();

        if (!$current) {
            $resort = ['altitude' => 'medium', 'aspect' => 'north'];
            $prev = $model->orderBy('game_day', 'DESC')->first();
            $this->generateAndSave($model, $resort, $gameDay, $prev);
            $current = $model->where('game_day', $gameDay)->first();
        }

        $weather = [
            'temp' => (int) $current['temp'],
            'condition' => $current['condition_name'],
            'wind' => (int) $current['wind'],
            'snowfall' => (int) $current['snowfall'],
            'visibility' => $current['visibility'],
            'humidity' => (int) $current['humidity'],
            'snow_base' => (int) $current['snow_base'],
        ];

        $forecast = json_decode($current['forecast'], true) ?? [];

        return view('weather/index', [
            'weather' => $weather,
            'forecast' => $forecast,
            'gameDay' => $gameDay,
        ]);
    }

    private function generateAndSave(WeatherModel $model, array $resort, int $day, ?array $prev): void
    {
        $seed = crc32('skimanager-weather-day-' . $day);
        mt_srand($seed);

        $baseTemp = ['low' => -2, 'medium' => -8, 'high' => -14];
        $temp = ($baseTemp[$resort['altitude']] ?? -8) + mt_rand(-5, 5);

        $conditions = ['Sunny', 'Partly Cloudy', 'Cloudy', 'Light Snow', 'Heavy Snow', 'Blizzard', 'Freezing Rain'];
        $weights = [15, 20, 20, 25, 10, 5, 5];
        $roll = mt_rand(1, 100);
        $cumulative = 0;
        $condition = 'Cloudy';
        foreach ($conditions as $i => $c) {
            $cumulative += $weights[$i];
            if ($roll <= $cumulative) { $condition = $c; break; }
        }

        $windSpeeds = ['north' => mt_rand(5, 25), 'east' => mt_rand(10, 40), 'south' => mt_rand(5, 20), 'west' => mt_rand(15, 50)];
        $wind = $windSpeeds[$resort['aspect']] ?? mt_rand(10, 30);

        $snowfall = 0;
        if (in_array($condition, ['Light Snow', 'Heavy Snow', 'Blizzard'])) {
            $snowfall = $condition === 'Light Snow' ? mt_rand(1, 5) : ($condition === 'Heavy Snow' ? mt_rand(5, 15) : mt_rand(15, 30));
        }

        $visibilityMap = ['Sunny' => 'Excellent', 'Partly Cloudy' => 'Good', 'Cloudy' => 'Good', 'Light Snow' => 'Moderate', 'Heavy Snow' => 'Poor', 'Blizzard' => 'Very Poor', 'Freezing Rain' => 'Poor'];

        $prevBase = $prev ? (int) $prev['snow_base'] : 50;
        $snowBase = max(0, $prevBase + $snowfall - ($condition === 'Sunny' ? mt_rand(1, 3) : 0));

        $forecast = [];
        for ($d = 1; $d <= 5; $d++) {
            $fSeed = crc32('skimanager-weather-day-' . ($day + $d));
            mt_srand($fSeed);
            $cond = $conditions[mt_rand(0, count($conditions) - 1)];
            $forecast[] = [
                'day' => $d,
                'temp' => $temp + mt_rand(-3, 3),
                'condition' => $cond,
                'snowfall' => in_array($cond, ['Light Snow', 'Heavy Snow', 'Blizzard']) ? mt_rand(1, 20) : 0,
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
