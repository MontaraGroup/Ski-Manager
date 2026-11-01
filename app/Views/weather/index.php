<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Weather<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Weather as W;
use App\Libraries\Grooming as G;
$phaseLabel = ['early' => 'Early winter', 'deep' => 'Deep winter', 'spring' => 'Spring', 'summer' => 'Summer'][$phase];
$pct = fn(float $f) => ($f >= 1 ? '+' : '−') . abs((int) round(($f - 1) * 100)) . '%';
$cond = $now['condition_name'];
$maxBase = max(array_merge([W::THIN_BASE * 2], array_column($history, 'snow_base')));
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-cloud-sun mr-2 text-info" aria-hidden="true"></i>Weather</h1>
            <p class="text-sm text-base-content/70"><?= esc(getSeasonName()) ?> · day <?= $seasonDay ?> of <?= $seasonLength ?> · <?= $phaseLabel ?></p>
        </div>
    </div>

    <!-- Now -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <div class="text-center md:text-left md:w-48 shrink-0">
                <i class="fa-solid <?= weatherIcon($cond) ?> text-5xl mb-2" aria-hidden="true"></i>
                <div class="text-4xl font-bold"><?= esc($now['temp_formatted']) ?></div>
                <div class="font-semibold mt-1"><?= esc($cond) ?></div>
                <div class="text-xs text-base-content/70">Day <?= temp((int) $now['base_temp'] + 4) ?> high · night <?= temp((int) $now['base_temp'] - 4) ?> low</div>
            </div>
            <dl class="flex-1 grid grid-cols-2 md:grid-cols-3 gap-3">
                <div class="bg-base-200 rounded-lg p-3"><dt class="text-xs text-base-content/70">New snow today</dt><dd class="text-xl font-bold"><?= esc($now['snowfall_formatted']) ?></dd></div>
                <div class="bg-base-200 rounded-lg p-3"><dt class="text-xs text-base-content/70">Snow base</dt><dd class="text-xl font-bold <?= $thinBase ? 'text-warning' : '' ?>"><?= esc($now['snow_formatted']) ?></dd><?php if ($thinBase) : ?><dd class="text-xs text-warning">Thin (under <?= W::THIN_BASE ?> cm)</dd><?php endif ?></div>
                <div class="bg-base-200 rounded-lg p-3"><dt class="text-xs text-base-content/70">Wind</dt><dd class="text-xl font-bold <?= $windHold ? 'text-error' : '' ?>"><?= speed((int) $now['base_wind']) ?></dd><?php if ($windHold) : ?><dd class="text-xs text-error">Lifts on wind hold</dd><?php endif ?></div>
                <div class="bg-base-200 rounded-lg p-3"><dt class="text-xs text-base-content/70">Visibility</dt><dd class="text-xl font-bold"><?= esc($now['visibility']) ?></dd></div>
                <div class="bg-base-200 rounded-lg p-3"><dt class="text-xs text-base-content/70">Humidity</dt><dd class="text-xl font-bold"><?= (int) $now['humidity'] ?>%</dd></div>
                <div class="bg-base-200 rounded-lg p-3"><dt class="text-xs text-base-content/70">Snowmaking</dt><dd class="text-xl font-bold <?= $snowWindow ? 'text-success' : 'text-error' ?>"><?= $snowWindow ? count($snowWindow) . ' h' : 'Too warm' ?></dd><dd class="text-xs text-base-content/70">at <?= temp(W::SNOWMAKING_MAX_TEMP) ?> or colder</dd></div>
            </dl>
        </div>
    </div></div>

    <!-- What today's weather does -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-bold mb-3">What today's weather does to your resort</h2>
        <ul class="space-y-2 text-sm">
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-person-skiing mt-0.5 w-4 <?= $demandFactor >= 1 ? 'text-success' : 'text-error' ?>" aria-hidden="true"></i>
                <span><b>Skiers <?= $demandFactor == 1 ? 'unchanged' : $pct($demandFactor) ?></b>: <?= esc($cond) ?><?= $windHold ? ', with lifts on wind hold' : '' ?>. <?= $demandFactor >= 1 ? 'Good skiing weather.' : 'Fewer people come out in this.' ?></span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-snowflake mt-0.5 w-4 text-<?= $quality[2] ?>" aria-hidden="true"></i>
                <span><b>Snow: <?= $quality[0] ?></b>. <?= $quality[1] ?></span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-mountain mt-0.5 w-4 <?= ($warm || $thinBase) ? 'text-warning' : 'text-success' ?>" aria-hidden="true"></i>
                <span><b>Slope wear</b>:
                    <?= $warm ? '+' . G::WARM_EXTRA . '% (warm or rain)' : 'normal temperature' ?><?= $freshSnow ? ', −' . G::FRESH_SNOW_RELIEF . '% thanks to fresh snow' : '' ?><?= $thinBase ? ', +' . G::THIN_BASE_EXTRA . '% on slopes without snowmaking (thin base)' : '' ?>.
                    <a href="/grooming" class="link link-primary">Grooming</a></span>
            </li>
            <li class="flex items-start gap-2">
                <i class="fa-solid fa-fan mt-0.5 w-4 <?= $snowWindow ? 'text-success' : 'text-error' ?>" aria-hidden="true"></i>
                <span><b>Snow guns</b>: <?= $snowWindow ? 'cold enough for ' . count($snowWindow) . ' hours, so running guns protect the slopes they cover from a thin base.' : 'too warm all day, so they can\'t protect slopes today.' ?> <a href="/snowmaking" class="link link-primary">Snowmaking</a></span>
            </li>
        </ul>
    </div></div>

    <!-- Hourly -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-bold mb-3">Today, hour by hour</h2>
        <div class="overflow-x-auto"><div class="flex gap-1" style="min-width:720px">
            <?php foreach ($hourly as $h) : $isNow = $h['hour'] === $currentHour; $canMake = $h['temp'] <= W::SNOWMAKING_MAX_TEMP; ?>
            <div class="flex-1 text-center rounded-lg p-1.5 <?= $isNow ? 'bg-primary/20 ring-1 ring-primary' : '' ?>">
                <div class="text-[10px] <?= $isNow ? 'font-bold text-primary' : 'text-base-content/70' ?>"><?= $isNow ? 'Now' : $h['label'] ?></div>
                <div class="text-xs font-bold mt-0.5 <?= $canMake ? 'text-info' : ($h['temp'] > 0 ? 'text-warning' : '') ?>"><?= temp($h['temp']) ?></div>
                <div class="w-full h-1 rounded mt-0.5 <?= $canMake ? 'bg-info/50' : 'bg-base-300' ?>"></div>
            </div>
            <?php endforeach ?>
        </div></div>
        <div class="flex gap-4 mt-2 text-xs text-base-content/70"><span class="flex items-center gap-1"><span class="w-3 h-1 rounded bg-info/50 inline-block"></span> cold enough for snow guns</span><span class="flex items-center gap-1"><span class="w-3 h-1 rounded bg-base-300 inline-block"></span> too warm</span></div>
    </div></div>

    <!-- Forecast -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-bold mb-3">Next 5 days</h2>
        <div class="grid grid-cols-5 gap-2">
            <?php foreach ($forecast as $f) : $ff = W::demandFactor($f['condition'], (int) ($f['wind'] ?? 0)); ?>
            <div class="bg-base-200 rounded-lg p-2 text-center">
                <div class="text-xs text-base-content/70">Day <?= $seasonDay + (int) $f['day'] ?></div>
                <i class="fa-solid <?= weatherIcon($f['condition']) ?> text-xl my-1" aria-hidden="true"></i>
                <div class="font-bold"><?= temp((int) $f['temp']) ?></div>
                <div class="text-[11px] text-base-content/70 hidden sm:block"><?= esc($f['condition']) ?></div>
                <?php if ((int) $f['snowfall'] > 0) : ?><div class="text-xs text-info">+<?= snow((int) $f['snowfall']) ?></div><?php endif ?>
                <div class="text-[11px] <?= $ff >= 1 ? 'text-success' : 'text-error' ?>"><?= $ff == 1 ? 'skiers =' : 'skiers ' . $pct($ff) ?></div>
            </div>
            <?php endforeach ?>
        </div>
        <p class="text-xs text-base-content/70 mt-2">Mountain forecasts here are reliable: plan grooming and snowmaking around them.</p>
    </div></div>

    <!-- Snow base -->
    <?php if (count($history) > 1) : ?>
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-bold mb-3">Snow base, last <?= count($history) ?> days</h2>
        <div class="flex items-end gap-1 h-28" role="img" aria-label="Snow base from <?= (int) $history[0]['snow_base'] ?> to <?= (int) end($history)['snow_base'] ?> centimetres">
            <?php foreach ($history as $h) : $hb = (int) $h['snow_base']; ?>
            <div class="flex-1 flex flex-col justify-end h-full" title="Day <?= (int) $h['game_day'] ?>: <?= $hb ?> cm base<?= (int) $h['snowfall'] ? ', +' . (int) $h['snowfall'] . ' cm new' : '' ?>">
                <div class="rounded-t <?= $hb < W::THIN_BASE ? 'bg-warning/70' : 'bg-info/60' ?>" style="height:<?= max(2, (int) round($hb / $maxBase * 100)) ?>%"></div>
            </div>
            <?php endforeach ?>
        </div>
        <div class="flex justify-between text-xs text-base-content/70 mt-1"><span>Day <?= (int) $history[0]['game_day'] ?></span><span>Today: <?= esc($now['snow_formatted']) ?></span></div>
    </div></div>
    <?php endif ?>

    <details class="card bg-base-100 shadow-sm">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How weather works</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>The season runs early winter → deep winter (coldest) → spring (thawing) → summer from day <?= $winterDays + 1 ?>. Weather fronts last a few days.</li>
            <li>Below freezing it snows (light, heavy or a blizzard); around 0–1°C it's freezing rain, above that rain.</li>
            <li>The snow base grows with snowfall and shrinks with warm days and rain. Under <?= W::THIN_BASE ?> cm, slopes need snow guns.</li>
            <li>Skiers by weather: <?php foreach (W::DEMAND as $c => $f) : ?><?= esc($c) ?> <?= $f == 1 ? '±0%' : $pct($f) ?><?= $c !== array_key_last(W::DEMAND) ? ', ' : '' ?><?php endforeach ?>; wind of <?= W::WIND_HOLD ?> km/h or more puts lifts on hold (<?= $pct(W::WIND_HOLD_FACTOR) ?>).</li>
        </ul>
    </details>
</div>
<?= $this->endSection() ?>
