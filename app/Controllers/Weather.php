<?php

namespace App\Controllers;

use App\Libraries\Forecast;
use App\Libraries\Grooming as G;
use App\Libraries\Weather as W;
use App\Libraries\WeatherPro;

/**
 * /weather: the mountain now, what tonight's tick will settle and what it does to the resort, that
 * day hour by hour, the forecast (exact first, then ranges: App\Libraries\Weather::outlook) and the
 * snow base. Weather Pro (App\Libraries\WeatherPro) sees 14 days, the wind hour by hour and where
 * the snow base is heading.
 */
class Weather extends BaseController
{
    public function index(): string
    {
        return view('weather/index', ['page' => $this->pageData()]);
    }

    /** POST /weather/pro: buy Weather Pro, or add 7 days to a running pass */
    public function subscribe()
    {
        $userId = (int) auth()->id();
        if (!WeatherPro::enabledFor($userId)) return redirect()->to('/weather')->with('error', 'Weather Pro is not available yet.');
        $db = db_connect();
        $db->transStart();
        $until = WeatherPro::subscribe($userId);
        $db->transComplete();
        if (!$until) return redirect()->to('/weather')->with('error', 'Not enough Génépis: Weather Pro costs ' . WeatherPro::PRICE . '.');
        log_activity($userId, 'Génépis', 'Used ' . WeatherPro::PRICE . ' Génépis for Weather Pro', 'fa-solid fa-seedling');
        return redirect()->to('/weather')->with('success', 'Weather Pro runs until ' . date('M j, H:i', strtotime($until)) . '.');
    }

    /** POST /weather/pro/renew (on = 1 or 0): switch auto-renew */
    public function renew()
    {
        $userId = (int) auth()->id();
        if (!WeatherPro::enabledFor($userId)) return redirect()->to('/weather')->with('error', 'Weather Pro is not available yet.');
        $on = (string) $this->request->getPost('on') === '1';
        WeatherPro::setAutoRenew($userId, $on);
        return redirect()->to('/weather')->with('success', $on
            ? 'Auto-renew on: ' . WeatherPro::PRICE . ' Génépis every ' . WeatherPro::DAYS . ' days while your balance covers it.'
            : 'Auto-renew off. Weather Pro ends when this pass runs out.');
    }

    /** What the page shows; the mobile API sends the same data (Api\V1\App) */
    protected function pageData(): array
    {
        helper('weather');
        $userId = (int) auth()->id();
        $pro = WeatherPro::status($userId);
        $isPro = $pro['available'] && $pro['active'];
        $windRule = $userId > 0 && W::windRule($userId);

        $now = getCurrentWeather();
        $gameDay = getGameDay();
        $winterDays = getWinterDays();
        $seasonDay = getSeasonDay();

        // Tonight's tick settles the next game day: that's the weather to plan for
        $settle = W::forSettle($userId ?: null);
        $settleDay = W::settleDay();
        $sTemp = (int) ($settle['temp'] ?? 0);
        $sWind = (int) ($settle['wind'] ?? 0);
        $condition = (string) ($settle['condition_name'] ?? 'Cloudy');
        $sSnow = (int) ($settle['snowfall'] ?? 0);
        $sBase = (int) ($settle['snow_base'] ?? 0);
        $boosted = $userId > 0 && db_connect()->table('player_boosts')->where('user_id', $userId)->where('boost_type', 'skip_weather')
            ->where('expires_at >', date('Y-m-d H:i:s', strtotime(Forecast::nextSettleAt())))->countAllResults() > 0;

        $history = db_connect()->table('weather')->select('game_day, snow_base, snowfall')
            ->where('game_day <=', $gameDay)->where('game_day >', $gameDay - 21)->orderBy('game_day')->get()->getResultArray();

        $wet = in_array($condition, ['Rain', 'Freezing Rain'], true);
        $warm = $sTemp > 0 || $wet;
        $quality = match (true) {
            $sSnow >= 10 => ['Powder', 'Fresh powder on every slope: skiers love it (+3% each).', 'success'],
            $wet => ['Icy', 'Rain freezes on the snow: icy slopes put skiers off (−2% each).', 'error'],
            $condition === 'Sunny' && $sTemp >= 0 => ['Bare patches', 'Sun and warmth melt the surface; slopes without made snow go bare (−5% each).', 'warning'],
            default => ['Packed or groomed', 'Normal snow; slopes groomed overnight count as groomed (+2% each).', 'info'],
        };

        $snowWindow = snowmakingWindow($sTemp);
        $demand = W::demandFactor($condition, $sWind, $windRule);
        $holdHours = W::windHoldHours($sWind);
        $windHold = $sWind >= W::WIND_HOLD || ($windRule && $holdHours > 0);
        $freshSnow = $sSnow >= 5;
        $thinBase = $sBase < W::THIN_BASE;
        $pct = fn(float $f) => ($f >= 1 ? '+' : '−') . abs((int) round(($f - 1) * 100)) . '%';
        $icon = fn(string $c) => strtok(weatherIcon($c), ' ');
        $tone = ['success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'link'];
        $wear = ($warm ? '+' . G::WARM_EXTRA . '% (warm or rain)' : 'normal temperature')
            . ($freshSnow ? ', −' . G::FRESH_SNOW_RELIEF . '% thanks to fresh snow' : '')
            . ($thinBase ? ', +' . G::THIN_BASE_EXTRA . '% on slopes without enough made snow (thin base)' : '') . '.';
        $liftDay = count(W::LIFT_DAY);
        $windText = !$windHold ? null : ($windRule
            ? 'Wind stops chairlifts, gondolas and cable cars for ' . $holdHours . ' of ' . $liftDay . ' hours; button lifts keep running.'
            : 'Lifts on wind hold.');
        $demandList = [];
        foreach (W::DEMAND as $c => $f) $demandList[] = $c . ' ' . ($f == 1 ? '±0%' : $pct($f));

        $outlook = W::outlook($isPro);
        // "-9 to -7°C": the unit once, after the second number
        $range = fn(int $lo, int $hi, callable $fmt) => $lo === $hi ? $fmt($lo) : preg_replace('/^(-?[\d.]+).*$/u', '$1', $fmt($lo)) . ' to ' . $fmt($hi);

        $hourly = getHourlyForecast($sTemp, $sWind, $condition);
        $imperial = isImperial();

        return [
            'subtitle' => getSeasonName() . ' · day ' . $seasonDay . ' of ' . getSeasonLength() . ' · '
                . ['early' => 'Early winter', 'deep' => 'Deep winter', 'spring' => 'Spring', 'summer' => 'Summer'][W::phase($seasonDay, $winterDays)],
            'now' => [
                'icon' => $icon((string) $now['condition_name']), 'condition' => (string) $now['condition_name'], 'temp' => (string) $now['temp_formatted'],
                'high' => temp((int) $now['base_temp'] + 4), 'low' => temp((int) $now['base_temp'] - 4),
                'snowfall' => (string) $now['snowfall_formatted'], 'snowBase' => (string) $now['snow_formatted'],
                'thinBase' => (int) $now['snow_base'] < W::THIN_BASE, 'thinLimit' => snow(W::THIN_BASE),
                'wind' => speed((int) $now['base_wind']), 'windHold' => (int) $now['base_wind'] >= W::WIND_HOLD,
                'visibility' => (string) $now['visibility'], 'humidity' => (int) $now['humidity'] . '%',
                'snowmakingHours' => count($snowWindow), 'snowmakingTemp' => temp(W::SNOWMAKING_MAX_TEMP),
            ],
            'settle' => [
                'day' => W::seasonDayOf($settleDay), 'at' => Forecast::nextSettleAt(),
                'icon' => $icon($condition), 'condition' => $condition, 'temp' => temp($sTemp),
                'snowfall' => $sSnow > 0 ? '+' . snow($sSnow) : null, 'snowBase' => snow($sBase), 'wind' => speed($sWind),
                'windHold' => $windHold, 'boosted' => $boosted,
            ],
            'effects' => [
                ['icon' => 'fa-person-skiing', 'tone' => $demand >= 1 && !$windHold ? 'success' : 'danger',
                 'title' => 'Skiers ' . ($demand == 1 ? 'unchanged' : $pct($demand)),
                 'text' => $condition . '. ' . ($demand >= 1 ? 'Good skiing weather.' : 'Fewer people come out in this.') . ($windText ? ' ' . $windText : '')],
                ['icon' => 'fa-snowflake', 'tone' => $tone[$quality[2]], 'title' => 'Snow: ' . $quality[0], 'text' => $quality[1]],
                ['icon' => 'fa-mountain', 'tone' => ($warm || $thinBase) ? 'warning' : 'success', 'title' => 'Slope wear', 'text' => $wear, 'link' => ['/grooming', 'Grooming']],
                ['icon' => 'fa-fan', 'tone' => $snowWindow ? 'success' : 'danger', 'title' => 'Snow guns',
                 'text' => $snowWindow ? 'Cold enough for ' . count($snowWindow) . ' hours, so running guns build up the slopes they cover.' : 'Too warm all day for standard guns.',
                 'link' => ['/snowmaking', 'Snowmaking']],
            ],
            'hourly' => array_map(fn($h) => [
                'hour' => (int) $h['hour'], 'label' => (string) $h['label'], 'isNow' => false,
                // value: the number alone, in the player's units, for the chart
                'temp' => temp((int) $h['temp']), 'value' => $imperial ? (int) round($h['temp'] * 9 / 5 + 32) : (int) $h['temp'],
                'canMake' => $h['temp'] <= W::SNOWMAKING_MAX_TEMP, 'warm' => $h['temp'] > 0,
                'liftHour' => in_array($h['hour'], W::LIFT_DAY, true),
                // Weather Pro: wind each hour, and whether chairlifts stop then
                'wind' => $isPro ? speed((int) $h['wind']) : null,
                'windValue' => $isPro ? ($imperial ? (int) round($h['wind'] * 0.621371) : (int) $h['wind']) : null,
                'liftHold' => $isPro && in_array($h['hour'], W::LIFT_DAY, true) && (int) $h['wind'] >= W::WIND_HOLD,
            ], $hourly),
            'hourlyInfo' => [
                'tempUnit' => $imperial ? '°F' : '°C', 'windUnit' => $imperial ? 'mph' : 'km/h',
                'gunLimit' => temp(W::SNOWMAKING_MAX_TEMP),
                // Lift hours on wind hold tonight: hour by hour with the wind rule, the whole day without it
                'liftHoldHours' => $windRule ? $holdHours : ($sWind >= W::WIND_HOLD ? $liftDay : 0), 'liftHours' => $liftDay,
                'windRule' => $windRule,
            ],
            'forecast' => array_map(function ($f) use ($pct, $icon, $range, $windRule) {
                $ff = W::demandFactor($f['condition'], $f['windLow'], $windRule);
                return [
                    'day' => W::seasonDayOf($f['gameDay']), 'tonight' => $f['lead'] === 1, 'exact' => $f['exact'], 'confidence' => $f['confidence'],
                    'icon' => $icon($f['condition']), 'condition' => (string) $f['condition'],
                    'temp' => $range($f['tempLow'], $f['tempHigh'], 'temp'),
                    'snowfall' => $f['snowHigh'] > 0 ? '+' . $range($f['snowLow'], $f['snowHigh'], 'snow') : null,
                    'precipChance' => $f['exact'] || $f['precipChance'] === 0 ? null : $f['precipChance'],
                    'wind' => $range($f['windLow'], $f['windHigh'], 'speed'),
                    'windHold' => $f['windHold'],
                    'skiers' => $ff == 1 ? 'Skiers unchanged' : 'Skiers ' . $pct($ff), 'skiersUp' => $ff >= 1,
                ];
            }, $outlook),
            'callouts' => array_map(fn($c) => ['day' => W::seasonDayOf($c['gameDay']), 'kind' => $c['kind'], 'text' => $c['text']], W::callouts($outlook)),
            'history' => array_map(fn($h) => [
                'day' => W::seasonDayOf((int) $h['game_day']), 'base' => (int) $h['snow_base'], 'baseText' => snow((int) $h['snow_base']),
                'fresh' => (int) $h['snowfall'] ? snow((int) $h['snowfall']) : null, 'thin' => (int) $h['snow_base'] < W::THIN_BASE,
            ], $history),
            // Weather Pro: where the natural base is heading, from the forecast
            'projection' => $isPro ? array_map(fn($f) => [
                'day' => W::seasonDayOf($f['gameDay']), 'base' => $f['base'], 'baseText' => ($f['exact'] ? '' : '~') . snow($f['base']), 'thin' => $f['base'] < W::THIN_BASE,
            ], $outlook) : [],
            'maxBase' => max(array_merge([W::THIN_BASE * 2], array_map('intval', array_column($history, 'snow_base')), $isPro ? array_column($outlook, 'base') : [])),
            'pro' => $pro + [
                'perks' => [
                    W::PRO_DAYS . '-day forecast, exact for ' . W::PRO_EXACT . ' days, with tighter ranges after',
                    'Wind hour by hour, with the hours chairlifts stop',
                    'Storm, wind and thaw alerts two days ahead',
                    'Where your snow base is heading, and a ' . W::PRO_DAYS . '-day snowmaking outlook',
                ],
            ],
            'help' => [
                'Tonight\'s tick (00:05) settles tomorrow\'s game day, so "tonight" above is the weather your income, snow and slope wear come from. Today\'s weather already settled this morning.',
                'The next ' . ($isPro ? W::PRO_EXACT : W::FREE_EXACT) . ' days are exact. After that the forecast gives ranges that hold the real value and narrow as the day comes closer; the chance of snow or rain can be wrong far out.',
                'The season runs early winter, deep winter (coldest), spring (thawing), then summer from day ' . ($winterDays + 1) . '. Weather fronts last a few days.',
                'Below freezing it snows (light, heavy or a blizzard); around 0 to 1°C it\'s freezing rain, above that rain.',
                'The snow base grows with snowfall and shrinks with warm days and rain. Under ' . snow(W::THIN_BASE) . ', slopes need made snow.',
                'Skiers by weather: ' . implode(', ', $demandList) . '. ' . ($windRule
                    ? 'In each lift hour with wind of ' . speed(W::WIND_HOLD) . ' or more, chairlifts, gondolas and cable cars stop; button lifts keep running.'
                    : 'Wind of ' . speed(W::WIND_HOLD) . ' or more puts lifts on hold (' . $pct(W::WIND_HOLD_FACTOR) . ').'),
            ],
        ];
    }
}
