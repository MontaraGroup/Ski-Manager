<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Weather<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$wIcons = ['Sunny' => 'fa-sun text-warning', 'Partly Cloudy' => 'fa-cloud-sun text-info', 'Cloudy' => 'fa-cloud text-base-content/50', 'Light Snow' => 'fa-snowflake text-info', 'Heavy Snow' => 'fa-snowflake text-primary', 'Blizzard' => 'fa-wind text-error', 'Freezing Rain' => 'fa-cloud-rain text-error'];
$condColors = ['Sunny' => 'badge-warning', 'Partly Cloudy' => 'badge-info', 'Cloudy' => 'badge-ghost', 'Light Snow' => 'badge-info', 'Heavy Snow' => 'badge-primary', 'Blizzard' => 'badge-error', 'Freezing Rain' => 'badge-error'];
$startDate = '2026-06-01';
$seasonDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
$seasonPct = min(100, round(($seasonDay / 135) * 100));
$isOffSeason = $seasonDay > 135;
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-cloud-sun mr-2 text-info"></i>Weather & Climate</h1>
            <p class="text-sm text-base-content/50">Day <?= $gameDay ?> - <?= $isOffSeason ? 'Off-Season' : 'Season 1' ?></p>
        </div>
    </div>

    <!-- Current Weather -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <div class="text-center md:text-left">
                <i class="fa-solid <?= $wIcons[$weather['condition']] ?? 'fa-cloud' ?> text-5xl md:text-6xl mb-2"></i>
                <div class="text-4xl md:text-5xl font-bold"><?= temp($weather['temp']) ?></div>
                <div class="badge <?= $condColors[$weather['condition']] ?? 'badge-ghost' ?> mt-2"><?= $weather['condition'] ?></div>
                <div class="text-xs text-base-content/40 mt-1">Feels like <?= temp($weather['temp'] - 3) ?></div>
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

    <!-- Snowmaking Conditions -->
    <div class="card bg-<?= $weather['temp'] <= -2 ? 'success' : 'warning' ?>/10 border border-<?= $weather['temp'] <= -2 ? 'success' : 'warning' ?>/30 shadow-sm mb-6"><div class="card-body p-3">
        <div class="flex items-center gap-2 text-sm">
            <i class="fa-solid fa-snowflake <?= $weather['temp'] <= -2 ? 'text-success' : 'text-warning' ?>"></i>
            <?php if ($weather['temp'] <= -2) : ?>
                <span><strong>Snowmaking:</strong> Conditions are good (<?= temp($weather['temp']) ?>). Snow cannons can operate.</span>
            <?php else : ?>
                <span><strong>Snowmaking:</strong> Too warm (<?= temp($weather['temp']) ?>). Need <?= temp(-2) ?> or below.</span>
            <?php endif ?>
        </div>
    </div></div>

    <!-- Season Progress -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex justify-between text-sm mb-1">
            <span><i class="fa-solid fa-calendar mr-1"></i>Season Progress</span>
            <span class="text-base-content/50">Day <?= $seasonDay ?> / 135</span>
        </div>
        <progress class="progress progress-primary w-full mb-2" value="<?= min($seasonDay, 135) ?>" max="135"></progress>
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
                <div class="flex justify-between"><span class="text-base-content/60">Snowmaking</span><span class="font-semibold <?= $weather['temp'] <= -2 ? 'text-success' : 'text-error' ?>"><?= $weather['temp'] <= -2 ? 'Available' : 'Unavailable' ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/60">Lift Ops</span><span class="font-semibold <?= $weather['wind'] > 40 ? 'text-error' : ($weather['wind'] > 30 ? 'text-warning' : 'text-success') ?>"><?= $weather['wind'] > 40 ? 'Suspended' : ($weather['wind'] > 30 ? 'Limited' : 'Normal') ?></span></div>
            </div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i>Weather Tips</h2>
            <div class="space-y-2 text-xs text-base-content/60">
                <?php if ($weather['condition'] === 'Sunny') : ?>
                    <p><i class="fa-solid fa-sun mr-1 text-warning"></i>Great day for visitors, but snow is melting. Consider running snow cannons tonight if temps drop.</p>
                <?php elseif ($weather['condition'] === 'Blizzard') : ?>
                    <p><i class="fa-solid fa-wind mr-1 text-error"></i>Blizzard conditions - lifts are likely suspended. Revenue will be low but your snow base is building up.</p>
                <?php elseif (in_array($weather['condition'], ['Light Snow', 'Heavy Snow'])) : ?>
                    <p><i class="fa-solid fa-snowflake mr-1 text-info"></i>Natural snowfall means you can save on snowmaking costs today. Great for slope conditions.</p>
                <?php elseif ($weather['condition'] === 'Freezing Rain') : ?>
                    <p><i class="fa-solid fa-cloud-rain mr-1 text-error"></i>Freezing rain creates ice on slopes. Grooming is essential today to maintain safe conditions.</p>
                <?php else : ?>
                    <p><i class="fa-solid fa-cloud mr-1"></i>Average conditions. A good day for steady operations.</p>
                <?php endif ?>
                <?php if ($weather['wind'] > 25) : ?>
                    <p><i class="fa-solid fa-wind mr-1"></i>High winds may affect exposed lifts. Monitor conditions throughout the day.</p>
                <?php endif ?>
                <?php if ($weather['snow_base'] < 30) : ?>
                    <p><i class="fa-solid fa-triangle-exclamation mr-1 text-warning"></i>Snow base is critically low. Prioritize snowmaking to keep slopes open.</p>
                <?php endif ?>
            </div>
        </div></div>
    </div>
</div>
<?= $this->endSection() ?>
