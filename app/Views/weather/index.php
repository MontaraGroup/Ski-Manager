<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Weather<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
helper('weather');
$wIcons = ['Sunny' => 'fa-sun text-warning', 'Partly Cloudy' => 'fa-cloud-sun text-info', 'Cloudy' => 'fa-cloud text-base-content/50', 'Light Snow' => 'fa-snowflake text-info', 'Heavy Snow' => 'fa-snowflake text-primary', 'Blizzard' => 'fa-wind text-error', 'Freezing Rain' => 'fa-cloud-rain text-error'];
$condColors = ['Sunny' => 'badge-warning', 'Partly Cloudy' => 'badge-info', 'Cloudy' => 'badge-ghost', 'Light Snow' => 'badge-info', 'Heavy Snow' => 'badge-primary', 'Blizzard' => 'badge-error', 'Freezing Rain' => 'badge-error'];
$startDate = getSeasonStartDate();
$seasonDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
$seasonPct = min(100, round(($seasonDay / getSeasonLength()) * 100));
$isOffSeason = $seasonDay > getSeasonLength();
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-cloud-sun mr-2 text-info"></i>Weather & Climate</h1>
            <p class="text-sm text-base-content/50">Day <?= $gameDay ?> · <?= date('g:i A') ?> · <?= $isOffSeason ? 'Off-Season' : 'Season 1' ?></p>
        </div>
    </div>

    <!-- Current Weather -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <div class="text-center md:text-left">
                <i class="fa-solid <?= $wIcons[$weather['condition']] ?? 'fa-cloud' ?> text-5xl md:text-6xl mb-2"></i>
                <div class="text-4xl md:text-5xl font-bold"><?= temp($weather['temp']) ?></div>
                <div class="badge <?= $condColors[$weather['condition']] ?? 'badge-ghost' ?> mt-2"><?= $weather['condition'] ?></div>
                <div class="text-xs text-base-content/40 mt-1">
                    Feels like <?= temp($weather['temp'] - 3) ?>
                    · High <?= temp($weather['base_temp'] + 4) ?> / Low <?= temp($weather['base_temp'] - 4) ?>
                </div>
            </div>
            <div class="flex-1 grid grid-cols-2 md:grid-cols-3 gap-3">
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs text-base-content/50"><i class="fa-solid fa-wind mr-1"></i>Wind</div>
                    <div class="text-xl font-bold"><?= speed($weather['wind']) ?></div>
                    <?php if ($weather['wind'] > 40) : ?><div class="text-xs text-error">Dangerous</div>
                    <?php elseif ($weather['wind'] > 25) : ?><div class="text-xs text-warning">Strong</div>
                    <?php else : ?><div class="text-xs text-success">Calm</div><?php endif ?>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs text-base-content/50"><i class="fa-solid fa-snowflake mr-1"></i>Snowfall</div>
                    <div class="text-xl font-bold"><?= snow($weather['snowfall']) ?></div>
                    <?php if ($weather['snowfall'] > 0) : ?><div class="text-xs text-info">Fresh powder</div>
                    <?php else : ?><div class="text-xs text-base-content/40">None</div><?php endif ?>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs text-base-content/50"><i class="fa-solid fa-layer-group mr-1"></i>Snow Base</div>
                    <div class="text-xl font-bold"><?= snow($weather['snow_base']) ?></div>
                    <?php if ($weather['snow_base'] > 80) : ?><div class="text-xs text-success">Excellent</div>
                    <?php elseif ($weather['snow_base'] > 40) : ?><div class="text-xs text-warning">Fair</div>
                    <?php else : ?><div class="text-xs text-error">Low</div><?php endif ?>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs text-base-content/50"><i class="fa-solid fa-eye mr-1"></i>Visibility</div>
                    <div class="text-xl font-bold"><?= $weather['visibility'] ?></div>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs text-base-content/50"><i class="fa-solid fa-droplet mr-1"></i>Humidity</div>
                    <div class="text-xl font-bold"><?= $weather['humidity'] ?>%</div>
                </div>
                <div class="bg-base-200 rounded-lg p-3">
                    <div class="text-xs text-base-content/50"><i class="fa-solid fa-cable-car mr-1"></i>Lifts</div>
                    <?php if ($weather['wind'] > 40 || $weather['condition'] === 'Blizzard') : ?>
                        <div class="text-xl font-bold text-error">Suspended</div>
                    <?php elseif ($weather['wind'] > 30) : ?>
                        <div class="text-xl font-bold text-warning">Limited</div>
                    <?php else : ?>
                        <div class="text-xl font-bold text-success">Normal</div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div></div>

    <!-- Hourly Forecast -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-clock mr-1"></i>24-Hour Forecast</h2>
        <div class="overflow-x-auto">
            <div class="flex gap-1" style="min-width:720px">
                <?php foreach ($hourly as $h) : ?>
                <?php $isNow = $h['hour'] === $currentHour; ?>
                <div class="flex-1 text-center rounded-lg p-1.5 <?= $isNow ? 'bg-primary/20 ring-1 ring-primary' : '' ?>">
                    <div class="text-[10px] text-base-content/40 <?= $isNow ? 'font-bold text-primary' : '' ?>"><?= $isNow ? 'NOW' : $h['label'] ?></div>
                    <div class="text-xs font-bold mt-0.5 <?= $h['temp'] <= -2 ? 'text-info' : ($h['temp'] >= 0 ? 'text-warning' : '') ?>"><?= temp($h['temp']) ?></div>
                    <div class="mt-0.5">
                        <?php if ($h['can_snow']) : ?>
                        <div class="w-full h-1 rounded bg-info/40"></div>
                        <?php else : ?>
                        <div class="w-full h-1 rounded bg-base-300"></div>
                        <?php endif ?>
                    </div>
                    <div class="text-[9px] text-base-content/30 mt-0.5"><?= speed($h['wind']) ?></div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
        <div class="flex items-center gap-4 mt-2 text-xs text-base-content/50">
            <span class="flex items-center gap-1"><span class="w-3 h-1 rounded bg-info/40 inline-block"></span> Snowmaking OK</span>
            <span class="flex items-center gap-1"><span class="w-3 h-1 rounded bg-base-300 inline-block"></span> Too warm</span>
            <?php if (!empty($snowWindow)) : ?>
            <span><i class="fa-solid fa-snowflake text-info mr-1"></i><?= count($snowWindow) ?>h snowmaking window today</span>
            <?php else : ?>
            <span class="text-error"><i class="fa-solid fa-ban mr-1"></i>No snowmaking window today</span>
            <?php endif ?>
        </div>
    </div></div>

    <!-- Snowmaking Conditions -->
    <div class="card bg-<?= $weather['temp'] <= -2 ? 'success' : 'warning' ?>/10 border border-<?= $weather['temp'] <= -2 ? 'success' : 'warning' ?>/30 shadow-sm mb-6"><div class="card-body p-3">
        <div class="flex items-center gap-2 text-sm">
            <i class="fa-solid fa-snowflake <?= $weather['temp'] <= -2 ? 'text-success' : 'text-warning' ?>"></i>
            <?php if ($weather['temp'] <= -2) : ?>
                <span><strong>Snowmaking:</strong> Active right now (<?= temp($weather['temp']) ?>). <?= count($snowWindow) ?> hours of production today.</span>
            <?php elseif (!empty($snowWindow)) : ?>
                <span><strong>Snowmaking:</strong> Too warm now, but <?= count($snowWindow) ?> hours available later (overnight lows reach <?= temp($weather['base_temp'] - 4) ?>).</span>
            <?php else : ?>
                <span><strong>Snowmaking:</strong> Too warm all day. No production possible today.</span>
            <?php endif ?>
        </div>
    </div></div>

    <!-- Season Progress -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex justify-between text-sm mb-1">
            <span><i class="fa-solid fa-calendar mr-1"></i>Season Progress</span>
            <span class="text-base-content/50">Day <?= $seasonDay ?> / <?= getSeasonLength() ?></span>
        </div>
        <progress class="progress progress-primary w-full mb-2" value="<?= min($seasonDay, getSeasonLength()) ?>" max="<?= getSeasonLength() ?>"></progress>
        <div class="flex justify-between text-xs text-base-content/40">
            <span>Season Start</span>
            <span><?= $seasonPct ?>% complete</span>
            <span>Off-Season</span>
        </div>
    </div></div>

    <!-- 5-Day Forecast -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-bold text-base mb-4"><i class="fa-solid fa-calendar-days mr-1"></i>5-Day Forecast</h2>
        <div class="grid grid-cols-5 gap-2 md:gap-3">
            <?php foreach ($forecast as $day) : ?>
            <div class="bg-base-200 rounded-lg p-2 md:p-3 text-center">
                <div class="text-xs text-base-content/50 mb-1">+<?= $day['day'] ?>d</div>
                <i class="fa-solid <?= $wIcons[$day['condition']] ?? 'fa-cloud' ?> text-xl md:text-2xl mb-1"></i>
                <div class="text-sm md:text-lg font-bold"><?= temp($day['temp']) ?></div>
                <div class="text-[10px] text-base-content/40"><?= temp($day['temp'] + 4) ?> / <?= temp($day['temp'] - 4) ?></div>
                <div class="text-xs text-base-content/50 hidden md:block"><?= $day['condition'] ?></div>
                <?php if ($day['snowfall'] > 0) : ?>
                    <div class="text-xs text-info mt-1">+<?= snow($day['snowfall']) ?></div>
                <?php endif ?>
                <?php if ($day['temp'] <= -2) : ?>
                    <div class="text-xs text-success mt-0.5"><i class="fa-solid fa-snowflake"></i></div>
                <?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
    </div></div>

    <!-- Impact & Tips -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-chart-line mr-1"></i>Today's Impact</h2>
            <div class="space-y-2 text-sm">
                <?php
                $visitorImpact = ['Sunny' => '+20%', 'Partly Cloudy' => '0%', 'Cloudy' => '0%', 'Light Snow' => '+10%', 'Heavy Snow' => '-10%', 'Blizzard' => '-50%', 'Freezing Rain' => '-30%'];
                $revenueImpact = ['Sunny' => '+15%', 'Partly Cloudy' => '0%', 'Cloudy' => '0%', 'Light Snow' => '+5%', 'Heavy Snow' => '-5%', 'Blizzard' => '-40%', 'Freezing Rain' => '-25%'];
                $vi = $visitorImpact[$weather['condition']] ?? '0%';
                $ri = $revenueImpact[$weather['condition']] ?? '0%';
                $viClass = str_starts_with($vi, '+') ? 'text-success' : (str_starts_with($vi, '-') ? 'text-error' : '');
                $riClass = str_starts_with($ri, '+') ? 'text-success' : (str_starts_with($ri, '-') ? 'text-error' : '');
                ?>
                <div class="flex justify-between"><span class="text-base-content/60">Visitors</span><span class="font-semibold <?= $viClass ?>"><?= $vi ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/60">Revenue</span><span class="font-semibold <?= $riClass ?>"><?= $ri ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/60">Snowmaking</span><span class="font-semibold <?= $weather['temp'] <= -2 ? 'text-success' : (!empty($snowWindow) ? 'text-warning' : 'text-error') ?>"><?= $weather['temp'] <= -2 ? 'Active now' : (!empty($snowWindow) ? count($snowWindow) . 'h window' : 'Unavailable') ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/60">Lift Ops</span><span class="font-semibold <?= $weather['wind'] > 40 ? 'text-error' : ($weather['wind'] > 30 ? 'text-warning' : 'text-success') ?>"><?= $weather['wind'] > 40 ? 'Suspended' : ($weather['wind'] > 30 ? 'Limited' : 'Normal') ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/60">Slope Decay</span><span class="font-semibold <?= $weather['temp'] >= 0 ? 'text-error' : 'text-success' ?>"><?= $weather['temp'] >= 0 ? 'Accelerated (warm)' : 'Normal' ?></span></div>
            </div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i>Weather Tips</h2>
            <div class="space-y-2 text-xs text-base-content/60">
                <?php if ($weather['condition'] === 'Sunny') : ?>
                    <p><i class="fa-solid fa-sun mr-1 text-warning"></i>Great day for visitors, but snow is melting. Run cannons when temps drop overnight.</p>
                <?php elseif ($weather['condition'] === 'Blizzard') : ?>
                    <p><i class="fa-solid fa-wind mr-1 text-error"></i>Blizzard — lifts suspended. Low revenue but your snow base is building naturally.</p>
                <?php elseif (in_array($weather['condition'], ['Light Snow', 'Heavy Snow'])) : ?>
                    <p><i class="fa-solid fa-snowflake mr-1 text-info"></i>Natural snowfall — save on snowmaking costs today.</p>
                <?php elseif ($weather['condition'] === 'Freezing Rain') : ?>
                    <p><i class="fa-solid fa-cloud-rain mr-1 text-error"></i>Freezing rain creates icy slopes. Grooming is essential today.</p>
                <?php else : ?>
                    <p><i class="fa-solid fa-cloud mr-1"></i>Steady conditions. Normal operations.</p>
                <?php endif ?>
                <?php if ($weather['wind'] > 25) : ?>
                    <p><i class="fa-solid fa-wind mr-1"></i>High winds affecting exposed lifts.</p>
                <?php endif ?>
                <?php if ($weather['snow_base'] < 30) : ?>
                    <p><i class="fa-solid fa-triangle-exclamation mr-1 text-warning"></i>Snow base critically low. Prioritize snowmaking.</p>
                <?php endif ?>
                <?php if (!empty($snowWindow) && $weather['temp'] > -2) : ?>
                    <p><i class="fa-solid fa-clock mr-1 text-info"></i>Temps drop below freezing overnight — schedule snowmaking for those hours.</p>
                <?php endif ?>
            </div>
        </div></div>
    </div>
</div>
<?= $this->endSection() ?>
