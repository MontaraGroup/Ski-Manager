<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Snowmaking<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Snowmaking as SM;
use App\Libraries\Weather as W;
use App\Libraries\Grooming as G;
$coveredCount = count($coverage);
$openCount = count($openSlopes);
$uncovered = $openCount - $coveredCount;
$stateBadge = [
    'running' => ['badge-info', 'Running'], 'idle' => ['badge-warning', 'Idle: too warm'],
    'off' => ['badge-ghost', 'Off'], 'broken' => ['badge-error', 'Broken'],
];
$tempLimit = fn(?int $max) => $max === null ? 'any temperature' : temp($max) . ' or colder';
$diffBadge = ['green' => 'bg-success', 'blue' => 'bg-info', 'red' => 'bg-error', 'black' => 'bg-neutral', 'double_black' => 'bg-neutral'];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-snowflake mr-2 text-info" aria-hidden="true"></i>Snowmaking</h1>
                <p class="text-sm text-base-content/70">Snow guns keep slopes skiable when the natural base is thin</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($guns) : ?>
            <form action="/snowmaking/toggle-all" method="post"><?= csrf_field() ?>
                <input type="hidden" name="action" value="on">
                <button class="btn btn-info btn-sm gap-1"><i class="fa-solid fa-power-off" aria-hidden="true"></i> All on</button>
            </form>
            <form action="/snowmaking/toggle-all" method="post"><?= csrf_field() ?>
                <input type="hidden" name="action" value="off">
                <button class="btn btn-ghost btn-sm gap-1"><i class="fa-solid fa-power-off" aria-hidden="true"></i> All off</button>
            </form>
            <?php endif ?>
            <a href="/equipment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-shop" aria-hidden="true"></i> Buy snow guns</a>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Today -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Today's temperature</div>
            <div class="text-2xl font-bold"><?= temp($baseTemp) ?></div>
            <div class="text-xs text-base-content/70">night <?= temp($baseTemp - 4) ?> · day <?= temp($baseTemp + 4) ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Snowmaking window</div>
            <div class="text-2xl font-bold <?= $standardHours ? 'text-info' : 'text-error' ?>"><?= $standardHours ? $standardHours . ' h' : 'Too warm' ?></div>
            <div class="text-xs text-base-content/70">hours at <?= temp(W::SNOWMAKING_MAX_TEMP) ?> or colder</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Natural snow base</div>
            <div class="text-2xl font-bold <?= $thinBase ? 'text-warning' : 'text-success' ?>"><?= snow($snowBase) ?></div>
            <div class="text-xs <?= $thinBase ? 'text-warning' : 'text-base-content/70' ?>"><?= $thinBase ? 'Thin: under ' . snow(W::THIN_BASE) : 'Deep enough: guns optional' ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Slopes covered today</div>
            <div class="text-2xl font-bold <?= $uncovered > 0 && $thinBase ? 'text-warning' : '' ?>"><?= $coveredCount ?> / <?= $openCount ?></div>
            <div class="text-xs text-base-content/70"><?= count($running) ?> of <?= count($guns) ?> gun<?= count($guns) === 1 ? '' : 's' ?> running · room for <?= $capacity ?></div>
        </div></div>
    </div>

    <?php if ($thinBase && $openCount > 0) : ?>
    <div class="alert <?= $uncovered > 0 ? 'alert-warning' : 'alert-success' ?> mb-4">
        <i class="fa-solid <?= $uncovered > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' ?>" aria-hidden="true"></i>
        <span>
            <?php if ($uncovered <= 0) : ?>
                The natural base is thin, but every open slope has a snow gun today.
            <?php elseif (!$guns) : ?>
                The natural base is thin: all <?= $openCount ?> open slopes wear an extra <?= G::THIN_BASE_EXTRA ?>% a day. Buy snow guns to protect them.
            <?php elseif (!$standardHours && !$running) : ?>
                The natural base is thin and it's too warm for your guns today: <?= $uncovered ?> slope<?= $uncovered === 1 ? '' : 's' ?> wear an extra <?= G::THIN_BASE_EXTRA ?>% a day.
            <?php else : ?>
                The natural base is thin: <?= $uncovered ?> uncovered slope<?= $uncovered === 1 ? '' : 's' ?> wear an extra <?= G::THIN_BASE_EXTRA ?>% a day.
                <?= count($running) < count(array_filter($guns, fn($g) => $g['state'] !== 'broken')) ? 'Switch on more guns or buy' : 'Buy' ?> guns with more capacity.
            <?php endif ?>
        </span>
    </div>
    <?php endif ?>

    <!-- Next 5 days -->
    <?php if ($outlook) : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-bold mb-3">Next 5 days</h2>
        <div class="grid grid-cols-5 gap-2">
            <?php foreach ($outlook as $o) : ?>
            <div class="bg-base-200 rounded-lg p-2 text-center">
                <div class="text-xs text-base-content/70">Day <?= $o['day'] ?></div>
                <i class="fa-solid <?= weatherIcon($o['condition']) ?> text-lg my-1" aria-hidden="true"></i>
                <div class="font-bold text-sm"><?= temp($o['temp']) ?></div>
                <?php if ($o['snowfall'] > 0) : ?><div class="text-xs text-info">+<?= snow($o['snowfall']) ?></div><?php endif ?>
                <div class="text-[11px] mt-1 <?= $o['hours'] ? 'text-info' : 'text-error' ?>"><?= $o['hours'] ? $o['hours'] . ' h guns' : 'too warm' ?></div>
                <?php if (!$o['hours'] && $o['guns']) : ?><div class="text-[11px] text-success"><?= $o['guns'] ?> of yours can run</div><?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
        <p class="text-xs text-base-content/70 mt-2">Hours a standard gun can run. Guns left on simply wait out warm days at no cost.</p>
    </div></div>
    <?php endif ?>

    <!-- Snow guns -->
    <h2 class="text-lg font-bold mb-3">Snow guns</h2>
    <?php if (!$guns) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-10">
            <i class="fa-solid fa-snowflake text-5xl text-base-content/20 mb-3" aria-hidden="true"></i>
            <p class="font-semibold">No snow guns yet</p>
            <p class="text-sm text-base-content/70 mt-1">Each gun covers one or more slopes on cold days. Buy them in the <a href="/equipment" class="link link-primary">equipment shop</a>.</p>
        </div></div>
    <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($guns as $g) : $cond = (int) $g['condition_pct']; [$badge, $label] = $stateBadge[$g['state']]; ?>
            <div class="card bg-base-100 shadow-sm <?= $g['state'] === 'broken' ? 'border border-error/40' : '' ?>">
                <div class="card-body p-4 gap-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold truncate"><?= esc($g['name']) ?></div>
                            <div class="text-xs text-base-content/70"><?= esc($g['brand']) ?> · works at <?= $tempLimit($g['max_temp']) ?></div>
                        </div>
                        <span class="badge badge-sm <?= $badge ?> shrink-0"><?= $label ?></span>
                    </div>

                    <dl class="grid grid-cols-3 gap-2 text-xs text-center">
                        <div class="bg-base-200 rounded p-1.5"><dt class="text-base-content/70">Covers</dt><dd class="font-bold"><?= (int) $g['capacity'] ?> slope<?= (int) $g['capacity'] === 1 ? '' : 's' ?></dd></div>
                        <div class="bg-base-200 rounded p-1.5"><dt class="text-base-content/70">Running cost</dt><dd class="font-bold"><?= currency($g['operating_cost']) ?>/day</dd></div>
                        <div class="bg-base-200 rounded p-1.5"><dt class="text-base-content/70">Uses</dt><dd class="font-bold"><?= number_format((int) $g['energy_kwh']) ?> kWh · <?= number_format((int) $g['water_liters'] / 1000, 1) ?> m³</dd></div>
                    </dl>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-base-content/70 w-12">Health</span>
                        <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $cond ?>" max="100" aria-label="Health <?= $cond ?>%"></progress>
                        <span class="text-xs font-mono w-9 text-right"><?= $cond ?>%</span>
                    </div>

                    <div class="text-xs">
                        <?php if ($g['state'] === 'running') : ?>
                            <span class="text-info"><i class="fa-solid fa-snowflake mr-1" aria-hidden="true"></i><?= $g['hours'] ?> h today, covering
                            <?= $g['covers'] ? esc(implode(', ', array_map(fn($id) => $slopeNames[$id] ?? '?', $g['covers']))) : 'nothing (no open slopes left)' ?></span>
                        <?php elseif ($g['state'] === 'idle') : ?>
                            <span class="text-warning">Waiting for a colder day: no cost or wear today.</span>
                        <?php elseif ($g['state'] === 'off') : ?>
                            <span class="text-base-content/70">Switched off.</span>
                        <?php else : ?>
                            <span class="text-error">Broken: repair it to use it again.</span>
                        <?php endif ?>
                    </div>
                    <div class="text-xs text-base-content/70">
                        <i class="fa-solid fa-user mr-1" aria-hidden="true"></i><?= $g['crew'] ? 'Crew: ' . esc($g['crew']) . ' (−' . SM::CREW_WEAR_RELIEF . '% wear a day)' : 'No crew assigned' ?>
                    </div>

                    <form action="/snowmaking/assign" method="post" class="flex gap-2"><?= csrf_field() ?>
                        <input type="hidden" name="cannon_id" value="<?= (int) $g['id'] ?>">
                        <label class="sr-only" for="prio-<?= (int) $g['id'] ?>">Priority slope for <?= esc($g['name']) ?></label>
                        <select id="prio-<?= (int) $g['id'] ?>" name="slope_id" class="select select-bordered select-xs flex-1 min-w-0">
                            <option value="">Auto: worst slopes first</option>
                            <?php foreach ($slopes as $sl) : ?>
                            <option value="<?= (int) $sl['id'] ?>" <?= $g['priority'] === (int) $sl['id'] ? 'selected' : '' ?>>First: <?= esc($sl['name']) ?><?= $sl['status'] !== 'open' ? ' (' . esc($sl['status']) . ')' : '' ?></option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-xs" aria-label="Save priority slope"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                    </form>

                    <div class="flex gap-1">
                        <?php if ($g['state'] !== 'broken') : ?>
                        <form action="/snowmaking/toggle/<?= (int) $g['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                            <button class="btn btn-xs w-full <?= $g['status'] === 'active' ? 'btn-ghost' : 'btn-info' ?>"><i class="fa-solid fa-power-off mr-1" aria-hidden="true"></i><?= $g['status'] === 'active' ? 'Switch off' : 'Switch on' ?></button>
                        </form>
                        <?php endif ?>
                        <?php if ($cond < 100) : ?>
                        <form action="/snowmaking/repair/<?= (int) $g['id'] ?>" method="post" class="<?= $g['state'] === 'broken' ? 'flex-1' : '' ?>" data-confirm="Repair <?= esc($g['name']) ?> to 100% for <?= currency($g['repair_cost']) ?>?"><?= csrf_field() ?>
                            <button class="btn btn-xs btn-outline gap-1 w-full"><i class="fa-solid fa-wrench" aria-hidden="true"></i> Repair <?= currency($g['repair_cost']) ?></button>
                        </form>
                        <?php endif ?>
                        <form action="/snowmaking/sell/<?= (int) $g['id'] ?>" method="post" data-confirm="Sell <?= esc($g['name']) ?> for <?= currency($g['resale']) ?>?"><?= csrf_field() ?>
                            <button class="btn btn-xs btn-ghost text-error gap-1"><i class="fa-solid fa-coins" aria-hidden="true"></i> Sell <?= currency($g['resale']) ?></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Slope coverage -->
    <?php if ($slopes) : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-bold mb-1">Slope coverage today</h2>
        <p class="text-xs text-base-content/70 mb-3">Each gun takes its priority slope first, then the worst-condition slopes no other gun covers.</p>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Slope</th><th>Condition</th><th>Snow gun</th><th class="text-right">Base wear</th></tr></thead>
                <tbody>
                <?php foreach ($slopes as $sl) : $id = (int) $sl['id']; $isOpen = $sl['status'] === 'open'; $gunId = $coverage[$id] ?? null; ?>
                    <tr class="<?= $isOpen ? '' : 'opacity-50' ?>">
                        <td><span class="inline-block w-2 h-2 rounded-full mr-2 <?= $diffBadge[$sl['difficulty'] ?? ''] ?? 'bg-base-300' ?>" aria-hidden="true"></span><?= esc($sl['name']) ?></td>
                        <td><span class="<?= (int) $sl['condition_pct'] < 40 ? 'text-error' : ((int) $sl['condition_pct'] < 70 ? 'text-warning' : '') ?>"><?= (int) $sl['condition_pct'] ?>%</span></td>
                        <td>
                            <?php if (!$isOpen) : ?><span class="text-base-content/70"><?= esc(ucfirst($sl['status'])) ?></span>
                            <?php elseif ($gunId) : $gun = array_values(array_filter($guns, fn($g) => (int) $g['id'] === $gunId))[0]; ?>
                                <span class="text-info"><i class="fa-solid fa-snowflake mr-1" aria-hidden="true"></i><?= esc($gun['name']) ?></span><?php if ($gun['priority'] === $id) : ?> <span class="badge badge-xs badge-outline">priority</span><?php endif ?>
                            <?php else : ?><span class="text-base-content/70">Not covered</span><?php endif ?>
                        </td>
                        <td class="text-right"><?= !$isOpen ? '' : ($gunId || !$thinBase ? '<span class="text-success">normal</span>' : '<span class="text-warning">+' . G::THIN_BASE_EXTRA . '%/day</span>') ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div></div>
    <?php endif ?>

    <!-- Costs, energy, water -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Running cost today</div>
            <div class="text-2xl font-bold"><?= currency($operatingCost + $energy['import'] + $water['import']) ?></div>
            <div class="text-xs text-base-content/70"><?= currency($operatingCost) ?> guns<?= $energy['import'] ? ' + ' . currency($energy['import']) . ' grid power' : '' ?><?= $water['import'] ? ' + ' . currency($water['import']) . ' bought water' : '' ?></div>
        </div></div>
        <?php foreach (['energy' => ['fa-bolt text-warning', 'Energy', 'kWh', '/energy'], 'water' => ['fa-droplet text-info', 'Water', 'L', '/water']] as $k => [$icon, $title, $unit, $link]) : $r = $$k; if ($r['hidden']) continue;
            $use = $k === 'energy' ? $r['total'] : $r['need']; $ok = $r['supply'] >= $use; ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs text-base-content/70"><i class="fa-solid <?= $icon ?> mr-1" aria-hidden="true"></i><?= $title ?> for snowmaking</span>
                <a href="<?= $link ?>" class="link link-primary text-xs">Manage</a>
            </div>
            <div class="text-2xl font-bold"><?= number_format($r['need']) ?> <span class="text-sm font-normal"><?= $unit ?></span></div>
            <div class="text-xs <?= $ok ? 'text-success' : 'text-warning' ?>">
                <?= $k === 'energy' ? 'Resort total ' . number_format($use) . ' kWh, ' : '' ?>your sources make <?= number_format($r['supply']) ?> <?= $unit ?>
                <?= $ok ? '' : '· the rest is bought' ?>
            </div>
        </div></div>
        <?php endforeach ?>
    </div>

    <?php if (!$crew && $guns) : ?>
    <div class="alert mb-6"><i class="fa-solid fa-user-plus text-info" aria-hidden="true"></i><span>Snowmaking crew is optional: a crew member assigned to a gun cuts its wear by <?= SM::CREW_WEAR_RELIEF ?>% a day. <a href="/staff/hire" class="link link-primary">Hire snowmakers</a>, then assign them on the <a href="/staff" class="link link-primary">staff page</a>.</span></div>
    <?php endif ?>

    <details class="card bg-base-100 shadow-sm mb-6">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How snowmaking works</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>When the natural base is under <?= snow(W::THIN_BASE) ?>, slopes wear an extra <?= G::THIN_BASE_EXTRA ?>% a day unless a snow gun covers them. With a deep base, guns don't change wear.</li>
            <li>A switched-on gun runs on any day with at least one hour cold enough for its model (most need <?= temp(W::SNOWMAKING_MAX_TEMP) ?>; some work up to <?= temp(0) ?>, and the SnowFactory at any temperature). On warmer days it waits at no cost.</li>
            <li>Each running gun covers as many open slopes as its capacity: its priority slope first, then the worst-condition slopes left.</li>
            <li>Running guns cost their daily running cost and use energy and water; anything your own <a href="/energy" class="link">energy</a> and <a href="/water" class="link">water</a> sources don't supply is bought.</li>
            <li>Guns wear 1–3% a day while running and break at 0%. Repairs cost up to <?= (int) (\App\Libraries\Fleet::REPAIR_SHARE * 100) ?>% of the gun's price, depending on wear; selling returns up to <?= (int) (\App\Libraries\Fleet::RESALE_SHARE * 100) ?>%.</li>
            <li>Covered slopes stay packed on warm sunny days instead of going bare.</li>
        </ul>
    </details>

    <div class="flex flex-wrap gap-2">
        <a href="/equipment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-toolbox" aria-hidden="true"></i> Equipment shop</a>
        <a href="/weather" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-cloud-sun" aria-hidden="true"></i> Weather</a>
        <a href="/grooming" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-tractor" aria-hidden="true"></i> Grooming</a>
        <a href="/staff" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-users" aria-hidden="true"></i> Staff</a>
    </div>
</div>
<?= $this->endSection() ?>
