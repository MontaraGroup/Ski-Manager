<?php use App\Libraries\Weather as W; use App\Libraries\Snowmaking as S; ?>
<p>Each season runs through early winter, a cold deep winter, a thawing spring and a summer off-season. Weather comes in fronts that last a few days, and the forecast on the <a href="/weather" class="link link-primary">weather page</a> is exact: plan around it.</p>

<h2 class="text-xl font-bold pt-2">Weather and skiers</h2>
<p>The day's weather changes how many skiers come:</p>
<ul class="list-disc pl-6 space-y-1">
    <?php foreach (W::DEMAND as $c => $f) : ?><li><?= esc($c) ?>: <?= $f == 1 ? 'normal' : (($f > 1 ? '+' : '−') . abs((int) round(($f - 1) * 100)) . '%') ?></li><?php endforeach ?>
    <li>Wind of <?= W::WIND_HOLD ?> km/h or more puts lifts on hold: another −<?= (int) round((1 - W::WIND_HOLD_FACTOR) * 100) ?>%.</li>
</ul>
<p>Below freezing it snows; around 0–1°C it's freezing rain, and above that, rain. Rain leaves slopes icy and wears them like a warm day.</p>

<h2 class="text-xl font-bold pt-2">The snow base</h2>
<p>The natural base starts the season at <?= snow(W::START_BASE) ?>, grows with snowfall and shrinks with warm days and rain. Each slope's base is the natural base plus its own made snow; under <?= snow(W::THIN_BASE) ?> a slope wears faster.</p>

<h2 class="text-xl font-bold pt-2">Snow guns</h2>
<ul class="list-disc pl-6 space-y-1">
    <li>Guns work off the wet-bulb temperature, which mixes air temperature and humidity. A standard gun needs an hour at <?= temp(W::SNOWMAKING_MAX_TEMP) ?> wet-bulb or colder; a few models work up to <?= temp(0) ?>, and the all-weather SnowFactory at any temperature. Dry air lets guns run on days just above freezing; damp air shortens the window.</li>
    <li>Right at its limit a gun makes <?= S::MIN_EFFICIENCY ?>% of its snow; <?= S::RAMP ?>°C colder it makes all of it.</li>
    <li>Give each slope a target base. In auto, a gun runs only while one of its slopes is under target, and only as long as it needs to. In max, it runs every cold hour: use it to build a deep base before a warm spell.</li>
    <li>A gun covers up to its capacity in slopes (its priority slope first, then the slopes furthest under target) and shares its snow between them.</li>
    <li>Running cost, energy and water count only for the hours a gun runs. Whatever your own sources don't supply is bought.</li>
    <li>A snowmaker assigned to a gun gives it full output (+<?= S::CREW_LEVEL_BONUS ?>% per level) and <?= S::CREW_WEAR_RELIEF ?>% less wear a day. Without one, a gun makes <?= S::UNCREWED_OUTPUT ?>% of its snow, unless it has the automation kit.</li>
    <li>Upgrades: ceramic nozzles let a gun work <?= S::NOZZLE_BONUS ?>°C warmer; the automation kit gives full output without a crew member.</li>
    <li>Made snow melts in warm weather and rain like natural snow. A slope holds up to <?= snow(S::MAX_MADE) ?> of it.</li>
</ul>
<p>Guns matter most early in the season and in spring, when the natural base is thin. In a deep midwinter base, auto guns stand by and cost nothing.</p>
