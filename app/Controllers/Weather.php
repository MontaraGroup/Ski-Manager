<?php

namespace App\Controllers;

use App\Libraries\Grooming as G;
use App\Libraries\Weather as W;

/**
 * /weather: today's conditions and what they actually do in the game, the next five days
 * (generated exactly as they will happen, see App\Libraries\Weather) and the snow base so far.
 */
class Weather extends BaseController
{
    public function index(): string
    {
        helper('weather');
        $now = getCurrentWeather();
        $baseTemp = (int) $now['base_temp'];
        $condition = (string) $now['condition_name'];
        $gameDay = getGameDay();
        $winterDays = getWinterDays();

        $history = db_connect()->table('weather')->select('game_day, snow_base, snowfall, temp, condition_name')
            ->where('game_day <=', $gameDay)->where('game_day >', $gameDay - 21)->orderBy('game_day')->get()->getResultArray();

        $warm = $baseTemp > 0 || in_array($condition, ['Rain', 'Freezing Rain'], true);
        $quality = match (true) {
            (int) $now['snowfall'] >= 10 => ['Powder', 'Fresh powder on every slope: skiers love it (+3% each).', 'success'],
            in_array($condition, ['Rain', 'Freezing Rain'], true) => ['Icy', 'Rain freezes on the snow: icy slopes put skiers off (−2% each).', 'error'],
            $condition === 'Sunny' && $baseTemp >= 0 => ['Bare patches', 'Sun and warmth melt the surface; slopes without snow guns go bare (−5% each).', 'warning'],
            default => ['Packed or groomed', 'Normal snow; slopes groomed overnight count as groomed (+2% each).', 'info'],
        };

        $seasonDay = getSeasonDay();
        $hourly = getHourlyForecast($baseTemp, (int) $now['base_wind'], $condition);
        $snowWindow = snowmakingWindow($baseTemp);
        $forecast = is_array($now['forecast']) ? $now['forecast'] : [];
        $demand = W::demandFactor($condition, (int) $now['base_wind']);
        $windHold = (int) $now['base_wind'] >= W::WIND_HOLD;
        $freshSnow = (int) $now['snowfall'] >= 5;
        $thinBase = (int) $now['snow_base'] < W::THIN_BASE;
        $currentHour = (int) date('G');
        $pct = fn(float $f) => ($f >= 1 ? '+' : '−') . abs((int) round(($f - 1) * 100)) . '%';
        $icon = fn(string $c) => strtok(weatherIcon($c), ' ');
        $tone = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'link'];
        $wear = ($warm ? '+' . G::WARM_EXTRA . '% (warm or rain)' : 'normal temperature')
            . ($freshSnow ? ', −' . G::FRESH_SNOW_RELIEF . '% thanks to fresh snow' : '')
            . ($thinBase ? ', +' . G::THIN_BASE_EXTRA . '% on slopes without snowmaking (thin base)' : '') . '.';
        $demandList = [];
        foreach (W::DEMAND as $c => $f) $demandList[] = $c . ' ' . ($f == 1 ? '±0%' : $pct($f));

        return view('weather/index', ['page' => [
            'subtitle' => getSeasonName() . ' · day ' . $seasonDay . ' of ' . getSeasonLength() . ' · '
                . ['early' => 'Early winter', 'deep' => 'Deep winter', 'spring' => 'Spring', 'summer' => 'Summer'][W::phase($seasonDay, $winterDays)],
            'now' => [
                'icon' => $icon($condition), 'condition' => $condition, 'temp' => (string) $now['temp_formatted'],
                'high' => temp($baseTemp + 4), 'low' => temp($baseTemp - 4),
                'snowfall' => (string) $now['snowfall_formatted'], 'snowBase' => (string) $now['snow_formatted'],
                'thinBase' => $thinBase, 'thinLimit' => snow(W::THIN_BASE),
                'wind' => speed((int) $now['base_wind']), 'windHold' => $windHold,
                'visibility' => (string) $now['visibility'], 'humidity' => (int) $now['humidity'] . '%',
                'snowmakingHours' => count($snowWindow), 'snowmakingTemp' => temp(W::SNOWMAKING_MAX_TEMP),
            ],
            'effects' => [
                ['icon' => 'fa-person-skiing', 'tone' => $demand >= 1 ? 'success' : 'danger',
                 'title' => 'Skiers ' . ($demand == 1 ? 'unchanged' : $pct($demand)),
                 'text' => $condition . ($windHold ? ', with lifts on wind hold' : '') . '. ' . ($demand >= 1 ? 'Good skiing weather.' : 'Fewer people come out in this.')],
                ['icon' => 'fa-snowflake', 'tone' => $tone[$quality[2]], 'title' => 'Snow: ' . $quality[0], 'text' => $quality[1]],
                ['icon' => 'fa-mountain', 'tone' => ($warm || $thinBase) ? 'warning' : 'success', 'title' => 'Slope wear', 'text' => $wear, 'link' => ['/grooming', 'Grooming']],
                ['icon' => 'fa-fan', 'tone' => $snowWindow ? 'success' : 'danger', 'title' => 'Snow guns',
                 'text' => $snowWindow ? 'Cold enough for ' . count($snowWindow) . ' hours, so running guns protect the slopes they cover from a thin base.' : 'Too warm all day, so they can\'t protect slopes today.',
                 'link' => ['/snowmaking', 'Snowmaking']],
            ],
            'hourly' => array_map(fn($h) => [
                'label' => $h['hour'] === $currentHour ? 'Now' : (string) $h['label'], 'isNow' => $h['hour'] === $currentHour,
                'temp' => temp((int) $h['temp']), 'canMake' => $h['temp'] <= W::SNOWMAKING_MAX_TEMP, 'warm' => $h['temp'] > 0,
            ], $hourly),
            'forecast' => array_map(function ($f) use ($seasonDay, $pct, $icon) {
                $ff = W::demandFactor($f['condition'], (int) ($f['wind'] ?? 0));
                return ['day' => $seasonDay + (int) $f['day'], 'icon' => $icon($f['condition']), 'condition' => (string) $f['condition'],
                    'temp' => temp((int) $f['temp']), 'snowfall' => (int) $f['snowfall'] > 0 ? '+' . snow((int) $f['snowfall']) : null,
                    'skiers' => $ff == 1 ? 'Skiers unchanged' : 'Skiers ' . $pct($ff), 'skiersUp' => $ff >= 1];
            }, $forecast),
            'history' => array_map(fn($h) => [
                'day' => (int) $h['game_day'], 'base' => (int) $h['snow_base'], 'baseText' => snow((int) $h['snow_base']),
                'fresh' => (int) $h['snowfall'] ? snow((int) $h['snowfall']) : null, 'thin' => (int) $h['snow_base'] < W::THIN_BASE,
            ], $history),
            'maxBase' => max(array_merge([W::THIN_BASE * 2], array_map('intval', array_column($history, 'snow_base')))),
            'help' => [
                'The season runs early winter, deep winter (coldest), spring (thawing), then summer from day ' . ($winterDays + 1) . '. Weather fronts last a few days.',
                'Below freezing it snows (light, heavy or a blizzard); around 0 to 1°C it\'s freezing rain, above that rain.',
                'The snow base grows with snowfall and shrinks with warm days and rain. Under ' . snow(W::THIN_BASE) . ', slopes need snow guns.',
                'Skiers by weather: ' . implode(', ', $demandList) . '; wind of ' . speed(W::WIND_HOLD) . ' or more puts lifts on hold (' . $pct(W::WIND_HOLD_FACTOR) . ').',
            ],
        ]]);
    }
}
