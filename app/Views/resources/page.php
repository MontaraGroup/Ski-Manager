<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $category === 'energy' ? 'Energy' : 'Water' ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$isEnergy = $category === 'energy';
$fmt = fn(int $n) => number_format($n) . ' ' . $unit;
$labels = ['snowmaking' => ['Snowmaking', 'fa-snowflake'], 'night_skiing' => ['Night skiing', 'fa-moon'], 'lifts' => ['Lifts', 'fa-cable-car'], 'buildings' => ['Buildings', 'fa-building']];
// currency() rounds to whole units, so the per-unit tariff is formatted here
$tariffLabel = $isEnergy
    ? (isImperial() ? '$' . number_format($tariff, 2) : number_format($tariff, 2, ',', '') . ' €') . ' per kWh'
    : currency((int) round($tariff * 1000)) . ' per 1,000 L';
$statusBadge = fn($s) => match ($s) { 'active' => ['badge-success', 'On'], 'off' => ['badge-ghost', 'Off'], 'broken' => ['badge-error', 'Broken'], default => ['badge-warning', 'Building'] };
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid <?= $isEnergy ? 'fa-bolt text-warning' : 'fa-droplet text-info' ?> mr-2" aria-hidden="true"></i><?= $isEnergy ? 'Energy' : 'Water' ?></h1>
                <p class="text-sm text-base-content/70">Your sources cover part of the demand; the rest is bought from the <?= $isEnergy ? 'grid' : 'water network' ?> at <?= $tariffLabel ?>.</p>
            </div>
        </div>
        <a href="/<?= $isEnergy ? 'water' : 'energy' ?>" class="btn btn-outline btn-sm gap-1"><i class="fa-solid <?= $isEnergy ? 'fa-droplet' : 'fa-bolt' ?>" aria-hidden="true"></i> <?= $isEnergy ? 'Water' : 'Energy' ?></a>
    </div>

    <!-- What you pay per day -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Demand</div>
            <div class="text-2xl font-bold"><?= $fmt($demand['total']) ?></div>
            <div class="text-xs text-base-content/70">per day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Your sources produce</div>
            <div class="text-2xl font-bold text-success"><?= $fmt($output) ?></div>
            <div class="text-xs text-base-content/70">per day, from sources switched on</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Imported</div>
            <div class="text-2xl font-bold <?= $deficit > 0 ? 'text-warning' : '' ?>"><?= $fmt($deficit) ?></div>
            <div class="text-xs text-base-content/70"><?= currency($importCost) ?>/day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Total cost per day</div>
            <div class="text-2xl font-bold text-error"><?= currency($importCost + $upkeep) ?></div>
            <div class="text-xs text-base-content/70"><?= currency($upkeep) ?> upkeep + <?= currency($importCost) ?> imports</div>
        </div></div>
    </div>

    <!-- Demand breakdown -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="text-sm font-bold mb-3">Where the demand comes from</h2>
        <?php $parts = array_diff_key($demand, ['total' => 1]); ?>
        <div class="grid grid-cols-2 md:grid-cols-<?= count($parts) ?> gap-3 text-center">
            <?php foreach ($parts as $key => $value) : ?>
            <div class="bg-base-200 rounded-lg p-3">
                <div class="text-lg font-bold"><?= $fmt($value) ?></div>
                <div class="text-xs text-base-content/70"><i class="fa-solid <?= $labels[$key][1] ?> mr-1" aria-hidden="true"></i><?= $labels[$key][0] ?></div>
            </div>
            <?php endforeach ?>
        </div>
        <p class="text-xs text-base-content/70 mt-3">
            <?php if ($isEnergy) : $k = \App\Libraries\Utilities::LIFT_KWH_PER_M; $b = \App\Libraries\Utilities::BUILDING_KWH; ?>Open lifts use the most: <?= $k['button'] ?> kWh per metre a day for a button lift up to <?= $k['cable_car'] ?> for a cable car. Each open building uses <?= $b[1] ?>/<?= $b[2] ?>/<?= $b[3] ?> kWh by level, each lit night-skiing area <?= \App\Libraries\Utilities::DRAW_NIGHT_LIGHT ?> kWh, and running snow guns their rated energy.
            <?php else : ?>Only running snow guns use water, each at its rated litres per day.<?php endif ?>
            A shortfall is simply bought in; nothing shuts down.
        </p>
    </div></div>

    <!-- Your sources -->
    <h2 class="text-lg font-bold mb-3">Your sources</h2>
    <?php if (!$sources) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-6 text-sm text-base-content/70">No sources yet: everything is imported. Build one below.</div></div>
    <?php else : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <?php foreach ($sources as $s) : [$badgeClass, $badgeLabel] = $statusBadge($s['status']); $cond = (float) $s['condition_pct']; $cfg = $config[$s['source_type']] ?? ['icon' => '', 'label' => $s['source_type']]; ?>
        <div class="card bg-base-100 shadow-sm <?= $s['status'] === 'broken' ? 'border border-error/30' : '' ?>"><div class="card-body p-4">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="font-semibold truncate"><i class="<?= esc($cfg['icon'], 'attr') ?> mr-1" aria-hidden="true"></i><?= esc($s['name']) ?></div>
                    <div class="text-xs text-base-content/70"><?= esc($cfg['label']) ?> · <?= $fmt((int) $s[$outputCol]) ?>/day · <?= currency($s['upkeep']) ?>/day upkeep</div>
                </div>
                <span class="badge badge-sm <?= $badgeClass ?>"><?= $s['status'] === 'under_construction' ? (int) $s['build_days_left'] . 'd left' : $badgeLabel ?></span>
            </div>
            <?php if ($s['status'] === 'active') : ?>
            <div class="text-xs mt-2 <?= $s['value'] >= 0 ? 'text-success' : 'text-warning' ?>">
                <?php if ($s['used_output'] < (int) $s[$outputCol]) : ?>Only <?= $fmt($s['used_output']) ?> of its output is needed. <?php endif ?>
                <?= $s['value'] >= 0 ? 'Saves ' . currency($s['value']) . '/day compared with importing' : 'Costs ' . currency(-$s['value']) . '/day more than importing' ?> the same amount.
            </div>
            <?php endif ?>
            <?php if ($s['status'] !== 'under_construction') : ?>
            <div class="flex items-center gap-2 mt-2">
                <span class="text-xs text-base-content/70 w-16">Condition</span>
                <progress class="progress progress-<?= $cond >= 60 ? 'success' : ($cond >= 30 ? 'warning' : 'error') ?> flex-1 h-1.5" value="<?= $cond ?>" max="100" aria-label="Condition <?= round($cond) ?>%"></progress>
                <span class="text-xs font-mono w-10 text-right"><?= round($cond) ?>%</span>
            </div>
            <div class="flex justify-end gap-2 mt-3">
                <?php if (in_array($s['status'], ['active', 'off'], true)) : ?>
                <form action="/<?= $category ?>/toggle/<?= (int) $s['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm <?= $s['status'] === 'active' ? 'btn-ghost' : 'btn-success' ?>"><i class="fa-solid <?= $s['status'] === 'active' ? 'fa-power-off' : 'fa-play' ?> mr-1" aria-hidden="true"></i><?= $s['status'] === 'active' ? 'Switch off' : 'Switch on' ?></button></form>
                <?php endif ?>
                <?php if ($cond < 100) : ?>
                <form action="/<?= $category ?>/repair/<?= (int) $s['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-info" <?= $cash < $s['repair_cost'] ? 'disabled title="Not enough cash"' : '' ?>><i class="fa-solid fa-wrench mr-1" aria-hidden="true"></i>Repair · <?= currency($s['repair_cost']) ?></button></form>
                <?php endif ?>
            </div>
            <?php endif ?>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Build -->
    <h2 class="text-lg font-bold mb-3">Build a source</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <?php foreach ($config as $key => $cfg) :
            $up = (int) round($cfg['upkeep'] * $costMult);
            $worth = \App\Libraries\Utilities::importCost($category, min($cfg['output'], max($deficit, 0))) - $up;
        ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h3 class="font-semibold text-sm"><i class="<?= esc($cfg['icon'], 'attr') ?> mr-1" aria-hidden="true"></i><?= esc($cfg['label']) ?></h3>
            <dl class="text-xs grid grid-cols-2 gap-y-1 mt-2">
                <dt class="text-base-content/70">Output</dt><dd class="text-right font-semibold"><?= $fmt($cfg['output']) ?>/day</dd>
                <dt class="text-base-content/70">Upkeep</dt><dd class="text-right font-semibold"><?= currency($up) ?>/day</dd>
                <dt class="text-base-content/70">Build</dt><dd class="text-right font-semibold"><?= currency($cfg['cost']) ?> · <?= $cfg['build_days'] ?>d</dd>
                <dt class="text-base-content/70">Pays back</dt><dd class="text-right font-semibold"><?php $m = \App\Libraries\Utilities::importCost($category, $cfg['output']) - $up; ?><?= $m > 0 ? '~' . (int) ceil($cfg['cost'] / $m) . ' days at full use' : 'never' ?></dd>
                <dt class="text-base-content/70">Today it'd</dt><dd class="text-right font-semibold <?= $worth >= 0 ? 'text-success' : 'text-warning' ?>"><?= $worth >= 0 ? 'save ' . currency($worth) : 'cost ' . currency(-$worth) ?>/day</dd>
            </dl>
            <form action="/<?= $category ?>/build" method="post" class="mt-3 space-y-1.5"><?= csrf_field() ?>
                <input type="hidden" name="source_type" value="<?= esc($key, 'attr') ?>">
                <input type="text" name="name" maxlength="60" class="input input-bordered input-sm w-full" placeholder="Name (optional)" aria-label="Name for the new <?= esc($cfg['label'], 'attr') ?>">
                <?php $atLimit = isset(\App\Libraries\Utilities::LIMIT[$key]) && ($owned[$key] ?? 0) >= \App\Libraries\Utilities::LIMIT[$key]; ?>
                <?php if ($atLimit) : ?>
                <button type="button" class="btn btn-sm w-full" disabled>You have one already</button>
                <?php else : ?>
                <button type="submit" class="btn btn-primary btn-sm w-full" <?= $cash < $cfg['cost'] ? 'disabled title="Not enough cash"' : '' ?>>Build · <?= currency($cfg['cost']) ?></button>
                <?php endif ?>
            </form>
        </div></div>
        <?php endforeach ?>
    </div>
    <p class="text-xs text-base-content/70">"Today it'd save/cost" compares the source's upkeep with importing the part of today's shortfall it would cover. Sources wear slowly while on; a source at 0% breaks and must be repaired. Switched-off sources cost nothing.</p>
</div>
<?= $this->endSection() ?>
