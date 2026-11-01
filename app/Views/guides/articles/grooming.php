<?php use App\Libraries\Grooming as G; use App\Libraries\Economy as E; use App\Libraries\Weather as W; $dl = ['green' => 'Green', 'blue' => 'Blue', 'red' => 'Red', 'black' => 'Black', 'double_black' => 'Double black']; ?>
<p>Every open slope loses condition each day, and worn slopes attract fewer skiers: a slope at 50% keeps <?= (int) round(E::conditionFactor(50) * 100) ?>% of its skiers, and at 0% only <?= (int) round(E::conditionFactor(0) * 100) ?>%. At 0% it closes until it's groomed.</p>

<h2 class="text-xl font-bold pt-2">How fast slopes wear</h2>
<ul class="list-disc pl-6 space-y-1">
    <li>By difficulty, per day: <?= implode(', ', array_map(fn($d, $v) => ($dl[$d] ?? $d) . ' ' . $v . '%', array_keys(G::DECAY), G::DECAY)) ?>.</li>
    <li>+<?= G::WARM_EXTRA ?>% on days above 0°C or with rain.</li>
    <li>−<?= G::FRESH_SNOW_RELIEF ?>% after 5 cm or more of fresh snow.</li>
    <li>+<?= G::THIN_BASE_EXTRA ?>% when the natural snow base is under <?= snow(W::THIN_BASE) ?>, unless a snow gun covers the slope.</li>
</ul>

<h2 class="text-xl font-bold pt-2">The night shift</h2>
<p>Each night, every groomer that is switched on <b>and has a groomer operator</b> grooms its capacity in slopes, worst first. Each pass restores <?= G::RESTORE_BASE ?>% plus <?= G::RESTORE_PER_LEVEL ?>% per average operator level, up to <?= G::RESTORE_MAX ?>%. A groomer without an operator stays parked and costs nothing. Groomed slopes also count as "groomed" snow for the day, which skiers like.</p>
<p>You can also send a groomer out during the day from the <a href="/grooming" class="link link-primary">grooming page</a>; daytime runs cost fuel (about <?= currency((int) round(1000 * G::EXTRA_RUN_PER_M)) ?> per km of slope).</p>

<h2 class="text-xl font-bold pt-2">How many groomers?</h2>
<p>Roughly: enough capacity to groom your worst third of slopes every night. If the same slopes keep sliding below 50%, add a groomer (and an operator) or train your operators.</p>
