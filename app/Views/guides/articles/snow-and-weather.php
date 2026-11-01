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
<p>The natural base starts the season at <?= snow(W::START_BASE) ?>, grows with snowfall and shrinks with warm days and rain. While it's under <?= snow(W::THIN_BASE) ?>, slopes wear faster unless a snow gun covers them.</p>

<h2 class="text-xl font-bold pt-2">Snow guns</h2>
<ul class="list-disc pl-6 space-y-1">
    <li>A gun that's switched on runs on any day with at least one hour at <?= temp(W::SNOWMAKING_MAX_TEMP) ?> or colder (a few models work up to <?= temp(0) ?>, and the all-weather SnowFactory at any temperature). On warmer days it waits and costs nothing.</li>
    <li>Each running gun covers as many slopes as its capacity: the slope you give it priority on first, then the worst slopes left.</li>
    <li>Running guns use energy and water; whatever your own sources don't supply is bought.</li>
    <li>A snowmaker assigned to a gun cuts its wear by <?= S::CREW_WEAR_RELIEF ?>% a day.</li>
</ul>
<p>Guns matter most early in the season and in spring, when the base is thin. In a deep midwinter base they don't change wear at all.</p>
