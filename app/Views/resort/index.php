<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$altitudeLabels = ['low' => altitude('low'), 'medium' => altitude('medium'), 'high' => altitude('high')];
$aspectLabels = ['north' => 'North-facing', 'east' => 'East-facing', 'south' => 'South-facing', 'west' => 'West-facing'];
$buildCost = ['low' => 'x1.00', 'medium' => 'x1.15', 'high' => 'x1.30'];
$windRisk = ['north' => 'Low', 'east' => 'Moderate', 'south' => 'Low', 'west' => 'High'];
$windColor = ['north' => 'text-success', 'east' => 'text-warning', 'south' => 'text-success', 'west' => 'text-error'];
$diffColors = ['green' => 'badge-success', 'blue' => 'badge-info', 'red' => 'badge-error', 'black' => 'badge-neutral'];
?>
<div class="max-w-7xl mx-auto p-4 lg:p-8">

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>

    <!-- Resort Header -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="avatar placeholder"><div class="bg-primary text-primary-content rounded-full w-14 h-14 flex items-center justify-center"><i class="fa-solid fa-mountain-sun text-2xl"></i></div></div>
                <div>
                    <h1 class="text-2xl font-bold"><?= esc($resort['name']) ?></h1>
                    <p class="text-sm text-base-content/50"><?= $resort['location'] ? esc($resort['location']) : 'Park City, Utah' ?></p>
                    <div class="flex items-center gap-2 mt-1">
                        <?php if ($resort['is_open']) : ?><span class="badge badge-success badge-sm">Open</span><?php else : ?><span class="badge badge-error badge-sm">Closed</span><?php endif ?>
                    </div>
                </div>
            </div>
            <div class="flex gap-2">
                <form action="/resort/toggle-resort" method="post" class="inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm <?= $resort["is_open"] ? "btn-error" : "btn-success" ?> gap-1"><i class="fa-solid <?= $resort["is_open"] ? "fa-door-closed" : "fa-door-open" ?>"></i><?= $resort["is_open"] ? "Close Resort" : "Open Resort" ?></button></form>
                <a href="/resort/edit" class="btn btn-outline btn-sm"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit</a>
                <a href="/map" class="btn btn-primary btn-sm"><i class="fa-solid fa-map mr-1"></i>Trail Map</a>
            </div>
        </div>
    </div></div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold text-info"><?= $openSlopes ?></div><div class="text-xs text-base-content/50">Open Slopes</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold text-success"><?= $openLifts ?></div><div class="text-xs text-base-content/50">Open Lifts</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold text-warning"><?= $staffCount ?></div><div class="text-xs text-base-content/50">Staff</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center"><div class="text-3xl font-bold text-primary"><?= $buildingCount ?></div><div class="text-xs text-base-content/50">Buildings</div></div></div>
    </div>

    <!-- Conditions & Revenue -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <?php
            $__wdb = db_connect();
            $__w = $__wdb->table('weather')->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();
            $__currentTemp = function_exists('hourlyTemp') && $__w ? hourlyTemp((int)$__w['temp']) : ($__w ? (int)$__w['temp'] : 0);
            $__snowBase = $__w ? (int)$__w['snow_base'] : 0;
            $__avgCond = 0; $__condCount = 0;
            foreach (($sectors ?? []) as $sec) {
                foreach (array_merge($sec['slopes'] ?? [], $sec['lifts'] ?? []) as $itm) {
                    $__avgCond += (int)($itm['condition_pct'] ?? 100); $__condCount++;
                }
            }
            $__avgCond = $__condCount > 0 ? round($__avgCond / $__condCount) : 100;
            $__fin = $__wdb->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
        ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-<?= $__currentTemp <= -5 ? 'snowflake text-info' : ($__currentTemp <= 0 ? 'cloud text-base-content/50' : 'sun text-warning') ?> text-lg"></i>
                <div>
                    <div class="text-lg font-bold"><?= temp($__currentTemp) ?></div>
                    <div class="text-xs text-base-content/50"><?= $__w['condition_name'] ?? 'Clear' ?></div>
                </div>
            </div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-layer-group text-info text-lg"></i>
                <div>
                    <div class="text-lg font-bold"><?= snow($__snowBase) ?></div>
                    <div class="text-xs text-base-content/50">Snow Base</div>
                </div>
            </div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-heart-pulse <?= $__avgCond >= 70 ? 'text-success' : ($__avgCond >= 40 ? 'text-warning' : 'text-error') ?> text-lg"></i>
                <div>
                    <div class="text-lg font-bold"><?= $__avgCond ?>%</div>
                    <div class="text-xs text-base-content/50">Avg Condition</div>
                </div>
            </div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-money-bill-wave text-success text-lg"></i>
                <div>
                    <div class="text-lg font-bold"><?= currency((int)($__fin['cash'] ?? 0)) ?></div>
                    <div class="text-xs text-base-content/50">Balance</div>
                </div>
            </div>
        </div></div>
    </div>

    <!-- Sectors -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold"><i class="fa-solid fa-mountain mr-1"></i>Sectors</h2>
            <div class="flex gap-1">
                <form action="/resort/open-all" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-success btn-xs gap-1"><i class="fa-solid fa-door-open"></i> Open All</button></form>
                <form action="/resort/close-all" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-xs gap-1"><i class="fa-solid fa-door-closed"></i> Close All</button></form>
            </div>
        </div>

        <?php if (empty($sectors)) : ?>
            <div class="alert alert-info mb-4"><i class="fa-solid fa-info-circle"></i><span>No slopes or lifts built yet. Visit the <a href="/map" class="link font-semibold">Trail Map</a> to start building.</span></div>
        <?php else : ?>
            <?php foreach ($sectors as $sectorNum => $sector) : ?>
            <div class="collapse collapse-arrow bg-base-100 shadow-sm mb-2">
                <input type="checkbox" checked />
                <div class="collapse-title font-semibold text-sm">
                    <i class="fa-solid fa-mountain mr-1"></i><?= $sector['name'] ?? 'Sector ' . $sectorNum ?>
                    <span class="badge badge-sm badge-ghost ml-2"><?= count($sector['lifts']) ?> lifts, <?= count($sector['slopes']) ?> slopes</span>
                </div>
                <div class="collapse-content">

                    <?php if (!empty($sector['lifts'])) : ?>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-2 mt-2"><i class="fa-solid fa-cable-car mr-1"></i>Lifts</h4>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Name</th><th>Type</th><th>Level</th><th>Condition</th><th>Length</th><th>Capacity</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($sector['lifts'] as $lift) : ?>
                                <tr>
                                    <td class="font-semibold"><?= esc($lift['name']) ?></td>
                                    <td><?= ucwords(str_replace('_', ' ', $lift['subtype'])) ?></td>
                                    <td><span class="badge badge-neutral badge-sm">Lv.<?= $lift['level'] ?></span></td>
                                    <td>
                                        <div class="flex items-center gap-1">
                                            <progress class="progress <?= (int)$lift['condition_pct'] > 50 ? 'progress-success' : ((int)$lift['condition_pct'] > 20 ? 'progress-warning' : 'progress-error') ?> w-14" value="<?= $lift['condition_pct'] ?>" max="100"></progress>
                                            <span class="text-xs"><?= $lift['condition_pct'] ?>%</span>
                                        </div>
                                    </td>
                                    <td><?= distance((int)$lift['length_meters']) ?></td>
                                    <td><?= number_format((int)$lift['capacity']) ?> s/h</td>
                                    <td>
                                        <?php if ($lift['status'] === 'open') : ?><span class="badge badge-success badge-sm">Open</span>
                                        <?php elseif ($lift['status'] === 'closed') : ?><span class="badge badge-ghost badge-sm">Closed</span>
                                        <?php elseif ($lift['status'] === 'broken') : ?><span class="badge badge-error badge-sm">Broken</span>
                                        <?php else : ?><span class="badge badge-info badge-sm">Building</span><?php endif ?>
                                    </td>
                                    <td>
                                        <form action="/resort/toggle/<?= $lift['id'] ?>" method="post" class="inline"><?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-xs" aria-label="Toggle on/off"><i class="fa-solid fa-power-off" aria-hidden="true"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif ?>

                    <?php if (!empty($sector['slopes'])) : ?>
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-base-content/50 mb-2 mt-4"><i class="fa-solid fa-person-skiing mr-1"></i>Slopes</h4>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Name</th><th>Type</th><th>Difficulty</th><th>Condition</th><th>Snow</th><th>Length</th><th>Status</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ($sector['slopes'] as $slope) : ?>
                                <tr>
                                    <td class="font-semibold"><?= esc($slope['name']) ?></td>
                                    <td><?= ucwords(str_replace('_', ' ', $slope['subtype'])) ?></td>
                                    <td><span class="badge <?= $diffColors[$slope['difficulty']] ?? 'badge-ghost' ?> badge-sm"><?= ucfirst($slope['difficulty'] ?? '-') ?></span></td>
                                    <td>
                                        <div class="flex items-center gap-1">
                                            <progress class="progress <?= (int)$slope['condition_pct'] > 50 ? 'progress-success' : ((int)$slope['condition_pct'] > 20 ? 'progress-warning' : 'progress-error') ?> w-14" value="<?= $slope['condition_pct'] ?>" max="100"></progress>
                                            <span class="text-xs"><?= $slope['condition_pct'] ?>%</span>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-xs <?= match($slope['snow_quality'] ?? 'packed') { 'powder' => 'badge-info', 'groomed' => 'badge-success', 'packed' => 'badge-ghost', 'icy' => 'badge-warning', 'bare' => 'badge-error', default => 'badge-ghost' } ?>"><?= ucfirst($slope['snow_quality'] ?? 'packed') ?></span></td>
                                    <td><?= distance((int)$slope['length_meters']) ?></td>
                                    <td>
                                        <?php if ($slope['status'] === 'open') : ?><span class="badge badge-success badge-sm">Open</span>
                                        <?php elseif ($slope['status'] === 'closed') : ?><span class="badge badge-ghost badge-sm">Closed</span>
                                        <?php elseif ($slope['status'] === 'broken') : ?><span class="badge badge-error badge-sm">Broken</span>
                                        <?php else : ?><span class="badge badge-info badge-sm">Building</span><?php endif ?>
                                    </td>
                                    <td>
                                        <form action="/resort/toggle/<?= $slope['id'] ?>" method="post" class="inline"><?= csrf_field() ?>
                                            <button class="btn btn-ghost btn-xs" aria-label="Toggle on/off"><i class="fa-solid fa-power-off" aria-hidden="true"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif ?>

                </div>
            </div>
            <?php endforeach ?>
        <?php endif ?>
    </div>

    <!-- Operations -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-gears mr-1"></i> Operations</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
        <a href="/grooming" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-tractor text-success text-xl mb-1"></i>
            <div class="text-xs font-semibold">Grooming</div>
        </div></a>
        <a href="/snowmaking" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-snowflake text-info text-xl mb-1"></i>
            <div class="text-xs font-semibold">Snowmaking</div>
        </div></a>
        <a href="/staff" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-users text-warning text-xl mb-1"></i>
            <div class="text-xs font-semibold">Staff</div>
        </div></a>
        <a href="/equipment" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-toolbox text-primary text-xl mb-1"></i>
            <div class="text-xs font-semibold">Equipment</div>
        </div></a>
        <a href="/night-skiing" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-moon text-secondary text-xl mb-1"></i>
            <div class="text-xs font-semibold">Night Skiing</div>
        </div></a>
        <a href="/resort-analysis" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-clipboard-check text-accent text-xl mb-1"></i>
            <div class="text-xs font-semibold">Analysis</div>
        </div></a>
    </div>

    <!-- Info Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <a href="/map" class="block text-center group">
                <h3 class="font-semibold text-sm mb-2">Trail Map</h3>
                <img src="/img/<?= esc($resort['resort_map'] ?? 'ParkCity') ?>_low.jpg" alt="Trail Map" class="rounded-lg w-full opacity-80 group-hover:opacity-100 transition-opacity" width="600" height="340" loading="lazy">
                <p class="text-xs text-base-content/50 mt-2">Click to build slopes & lifts</p>
            </a>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="flex items-center justify-between mb-3"><h3 class="font-semibold text-sm">Resort Info</h3><a href="/resort/edit" class="btn btn-ghost btn-xs">Edit</a></div>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-base-content/50">Name</span><span class="font-semibold"><?= esc($resort['name']) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Location</span><span><?= $resort['location'] ? esc($resort['location']) : 'Park City, Utah' ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Map</span><span><?= esc($resort['resort_map'] ?? 'ParkCity') ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Status</span><?php if ($resort['is_open']) : ?><span class="text-success font-semibold">Open</span><?php else : ?><span class="text-error font-semibold">Closed</span><?php endif ?></div>
                <div class="flex justify-between"><span class="text-base-content/50">Sectors</span><span><?= count($sectors) ?></span></div>
            </div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h3 class="font-semibold text-sm mb-3">Quick Stats</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-base-content/50">Total Slopes</span><span class="font-semibold"><?= $openSlopes ?> open</span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Total Lifts</span><span class="font-semibold"><?= $openLifts ?> open</span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Staff</span><span class="font-semibold"><?= $staffCount ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Buildings</span><span class="font-semibold"><?= $buildingCount ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Equipment</span><span class="font-semibold"><?= isset($equipmentCount) ? $equipmentCount : '-' ?></span></div>
            </div>
        </div></div>
    </div>

</div>
<?= $this->endSection() ?>
