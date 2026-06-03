<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Transportation<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $routeInfo = [
        1 => ['name' => 'Shuttle', 'vehicle' => 'fa-solid fa-van-shuttle', 'color' => 'text-accent', 'desc' => 'Town loop shuttle', 'freq' => 'Every 30 min', 'range' => '5 km radius'],
        2 => ['name' => 'Express', 'vehicle' => 'fa-solid fa-bus', 'color' => 'text-blue-500', 'desc' => 'Regional express coach', 'freq' => 'Every 60 min', 'range' => '50 km radius'],
        3 => ['name' => 'Aerial Tram', 'vehicle' => 'fa-solid fa-cable-car', 'color' => 'text-purple-500', 'desc' => 'Inter-resort aerial link', 'freq' => 'Continuous', 'range' => 'Resort-to-resort'],
    ];
    $activeRoutes = count(array_filter($buildings, fn($b) => $b['status'] === 'open'));
    $networkScore = min(100, $activeRoutes * 25 + $totalCapacity / 10);
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-bus mr-2 text-accent"></i>Transportation Network</h1>
                <p class="text-sm text-base-content/50">Move visitors to and across your resort</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="card bg-gradient-to-br from-accent/5 to-accent/10 shadow-sm border border-accent/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div><div class="text-xs text-base-content/50 mb-1">Routes</div><div class="text-3xl font-bold"><?= count($buildings) ?></div><div class="text-xs text-base-content/50"><?= $activeRoutes ?> active</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Daily Riders</div><div class="text-3xl font-bold"><?= number_format($totalCapacity) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Fare Revenue</div><div class="text-3xl font-bold text-success"><?= currency($totalRevenue) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Operating Cost</div><div class="text-3xl font-bold text-error"><?= currency($totalUpkeep) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Network Score</div><div class="text-3xl font-bold"><?= round($networkScore) ?></div><progress class="progress progress-accent w-full mt-1" value="<?= $networkScore ?>" max="100"></progress></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-route mr-1"></i> Route Network</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12"><i class="fa-solid fa-bus text-5xl text-base-content/15 mb-3"></i><p class="font-semibold">No transport routes</p><p class="text-sm text-base-content/50 mt-1">Without transport, visitors must drive. Many won't bother.</p></div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $ri = $routeInfo[$b['level']] ?? $routeInfo[1]; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl <?= $isOpen ? 'bg-accent/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                                <i class="<?= $ri['vehicle'] ?> text-2xl <?= $isOpen ? $ri['color'] : 'text-base-content/30' ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div><span class="font-bold"><?= esc($b['name']) ?></span> <span class="badge badge-outline badge-xs"><?= $ri['name'] ?></span></div>
                                    <span class="badge <?= $isOpen ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $isOpen ? 'Running' : 'Stopped' ?></span>
                                </div>
                                <div class="flex gap-3 text-xs text-base-content/50 mb-3">
                                    <span><i class="fa-solid fa-clock mr-1"></i><?= $ri['freq'] ?></span>
                                    <span><i class="fa-solid fa-location-dot mr-1"></i><?= $ri['range'] ?></span>
                                    <span><i class="fa-solid fa-users mr-1"></i><?= $b['capacity'] ?> riders/day</span>
                                </div>
                                <div class="grid grid-cols-4 gap-1 text-center text-xs mb-3">
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $b['capacity'] ?></div><div class="text-[10px] text-base-content/50">Capacity</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-success"><?= currency($b['revenue_per_day']) ?></div><div class="text-[10px] text-base-content/50">Fares</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-error"><?= currency($b['upkeep_per_day']) ?></div><div class="text-[10px] text-base-content/50">Fuel/Ops</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $cond ?>%</div><div class="text-[10px] text-base-content/50">Cond.</div></div>
                                </div>
                                <div class="flex gap-1">
                                    <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-accent' ?>"><?= $isOpen ? 'Stop Route' : 'Start Route' ?></button></form>
                                    <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Upgrade route?')"><?= csrf_field() ?><button class="btn btn-info btn-xs gap-1"><i class="fa-solid fa-arrow-up"></i> Upgrade</button></form><?php endif ?>
                                    <form action="/buildings/sell/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Remove route?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-plus mr-1"></i> Add Route</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : $ri = $routeInfo[$lvl]; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?><input type="hidden" name="type" value="transportation"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-1"><i class="<?= $ri['vehicle'] ?> <?= $ri['color'] ?>"></i><span class="font-semibold text-sm"><?= $info['name'] ?></span></div>
                        <p class="text-xs text-base-content/50 mb-1"><?= $ri['desc'] ?> - <?= $ri['freq'] ?></p>
                        <div class="flex justify-between text-xs"><span><?= $info['capacity'] ?> riders/day</span><span class="font-bold text-primary"><?= currency($info['cost']) ?></span></div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>
            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-3">
                <h3 class="font-semibold text-xs mb-2"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i>Transport Tips</h3>
                <ul class="text-xs text-base-content/60 space-y-1">
                    <li><i class="fa-solid fa-van-shuttle mr-1"></i> Shuttles are cheap and serve local visitors</li>
                    <li><i class="fa-solid fa-bus mr-1"></i> Express coaches bring visitors from further away</li>
                    <li><i class="fa-solid fa-cable-car mr-1"></i> Aerial trams handle peak-season crowds efficiently</li>
                    <li><i class="fa-solid fa-car mr-1"></i> Good transport reduces <a href="/parking" class="link link-primary">parking</a> pressure</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
