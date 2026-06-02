<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-6xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold"><i class="fa-solid fa-bolt mr-2 text-warning"></i> Energy Management</h1>
        <a href="/water" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-droplet"></i> Water</a>
    </div>

    <?php if (session()->getFlashdata('success')) : ?><div class="alert alert-success mb-4"><?= session()->getFlashdata('success') ?></div><?php endif ?>
    <?php if (session()->getFlashdata('error')) : ?><div class="alert alert-error mb-4"><?= session()->getFlashdata('error') ?></div><?php endif ?>

    <!-- Supply vs Demand -->
    <div class="stats stats-horizontal shadow-sm w-full mb-6 bg-base-100">
        <div class="stat"><div class="stat-title">Production</div><div class="stat-value text-success"><?= number_format($totalOutput) ?></div><div class="stat-desc">kWh/day</div></div>
        <div class="stat"><div class="stat-title">Demand</div><div class="stat-value <?= $totalDemand > $totalOutput ? 'text-error' : '' ?>"><?= number_format($totalDemand) ?></div><div class="stat-desc">kWh/day</div></div>
        <div class="stat"><div class="stat-title">Balance</div><div class="stat-value <?= $totalOutput - $totalDemand >= 0 ? 'text-success' : 'text-error' ?>"><?= ($totalOutput - $totalDemand >= 0 ? '+' : '') . number_format($totalOutput - $totalDemand) ?></div><div class="stat-desc">kWh surplus/deficit</div></div>
        <div class="stat"><div class="stat-title">Daily Cost</div><div class="stat-value text-warning"><?= currency($totalUpkeep) ?></div><div class="stat-desc">upkeep</div></div>
    </div>

    <?php if ($totalDemand > $totalOutput) : ?>
    <div class="alert alert-error mb-6"><i class="fa-solid fa-triangle-exclamation"></i><span><strong>Energy deficit!</strong> Your resort needs <?= number_format($totalDemand - $totalOutput) ?> more kWh/day. Snowmaking and night skiing may be affected.</span></div>
    <?php endif ?>

    <!-- Demand Breakdown -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="card-title text-base mb-3"><i class="fa-solid fa-chart-pie mr-1"></i> Demand Breakdown</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center">
            <div class="bg-base-200 rounded-lg p-3"><div class="text-lg font-bold"><?= number_format($snowmakingDraw) ?></div><div class="text-xs text-base-content/50"><i class="fa-solid fa-snowflake mr-1"></i>Snowmaking</div></div>
            <div class="bg-base-200 rounded-lg p-3"><div class="text-lg font-bold"><?= number_format($nightSkiDraw) ?></div><div class="text-xs text-base-content/50"><i class="fa-solid fa-moon mr-1"></i>Night Skiing</div></div>
            <div class="bg-base-200 rounded-lg p-3"><div class="text-lg font-bold"><?= number_format($liftDraw) ?></div><div class="text-xs text-base-content/50"><i class="fa-solid fa-cable-car mr-1"></i>Lifts</div></div>
            <div class="bg-base-200 rounded-lg p-3"><div class="text-lg font-bold"><?= number_format($buildingDraw) ?></div><div class="text-xs text-base-content/50"><i class="fa-solid fa-building mr-1"></i>Buildings</div></div>
        </div>
    </div></div>

    <!-- Build New -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <h2 class="card-title text-base"><i class="fa-solid fa-hammer mr-1"></i> Add Energy Source</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
            <?php foreach ($energyConfig as $key => $cfg) : ?>
            <div class="card bg-base-200/50 border border-base-300"><div class="card-body p-3">
                <h3 class="font-semibold text-sm"><i class="<?= $cfg['icon'] ?> mr-1"></i> <?= $cfg['label'] ?></h3>
                <div class="text-xs space-y-1 mt-2">
                    <div>Output: <strong><?= number_format($cfg['output']) ?> kWh/day</strong></div>
                    <div>Cost: <strong><?= currency($cfg['cost']) ?></strong></div>
                    <div>Upkeep: <strong><?= currency($cfg['upkeep']) ?>/day</strong></div>
                    <div>Build: <strong><?= $cfg['build_days'] ?> day<?= $cfg['build_days'] > 1 ? 's' : '' ?></strong></div>
                </div>
                <form action="/energy/build" method="post" class="mt-2"><?= csrf_field() ?><input type="hidden" name="source_type" value="<?= $key ?>"><input type="text" name="name" class="input input-bordered input-xs w-full mb-1" placeholder="Name (optional)"><button type="submit" class="btn btn-primary btn-xs w-full">Build</button></form>
            </div></div>
            <?php endforeach ?>
        </div>
    </div></div>

    <!-- Existing Sources -->
    <?php if (!empty($sources)) : ?>
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-list mr-1"></i> Your Sources</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($sources as $s) : ?>
        <?php $cfg = $energyConfig[$s['source_type']] ?? []; ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold"><i class="<?= $cfg['icon'] ?? 'fa-solid fa-bolt' ?> mr-1"></i> <?= esc($s['name']) ?></h3>
                <div class="badge <?= $s['status'] === 'active' ? 'badge-success' : ($s['status'] === 'under_construction' ? 'badge-warning' : ($s['status'] === 'broken' ? 'badge-error' : 'badge-ghost')) ?>"><?= $s['status'] === 'under_construction' ? $s['build_days_left'] . 'd left' : ucfirst($s['status']) ?></div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                <div><span class="text-base-content/50">Output:</span> <?= number_format($s['output_kwh']) ?> kWh</div>
                <div><span class="text-base-content/50">Upkeep:</span> <?= currency($cfg['upkeep'] ?? 0) ?>/day</div>
            </div>
            <div class="mt-2"><div class="flex justify-between text-xs mb-1"><span>Condition</span><span><?= number_format($s['condition_pct'], 0) ?>%</span></div><progress class="progress <?= $s['condition_pct'] >= 60 ? 'progress-success' : ($s['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-full" value="<?= $s['condition_pct'] ?>" max="100"></progress></div>
            <?php if ($s['status'] !== 'under_construction') : ?>
            <div class="card-actions justify-end mt-3">
                <form action="/energy/toggle/<?= $s['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm <?= $s['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>"><i class="fa-solid <?= $s['status'] === 'active' ? 'fa-pause' : 'fa-play' ?> mr-1"></i><?= $s['status'] === 'active' ? 'Off' : 'On' ?></button></form>
                <?php if ($s['condition_pct'] < 100) : ?><form action="/energy/repair/<?= $s['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-sm btn-info"><i class="fa-solid fa-wrench mr-1"></i>Repair</button></form><?php endif ?>
            </div>
            <?php endif ?>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
