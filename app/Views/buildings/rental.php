<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski Rentals<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $gearLevels = [
        1 => ['name' => 'Basic', 'items' => [['icon' => 'fa-solid fa-person-skiing', 'name' => 'Entry Skis'], ['icon' => 'fa-solid fa-shoe-prints', 'name' => 'Boots'], ['icon' => 'fa-solid fa-grip-lines', 'name' => 'Poles']], 'avg_price' => 25],
        2 => ['name' => 'Standard', 'items' => [['icon' => 'fa-solid fa-person-skiing', 'name' => 'All-Mountain Skis'], ['icon' => 'fa-solid fa-person-snowboarding', 'name' => 'Snowboards'], ['icon' => 'fa-solid fa-helmet-safety', 'name' => 'Helmets'], ['icon' => 'fa-solid fa-glasses', 'name' => 'Goggles']], 'avg_price' => 45],
        3 => ['name' => 'Premium', 'items' => [['icon' => 'fa-solid fa-person-skiing', 'name' => 'Race Skis'], ['icon' => 'fa-solid fa-person-snowboarding', 'name' => 'Pro Boards'], ['icon' => 'fa-solid fa-helmet-safety', 'name' => 'MIPS Helmets'], ['icon' => 'fa-solid fa-vest', 'name' => 'Impact Vests'], ['icon' => 'fa-solid fa-wand-magic-sparkles', 'name' => 'Custom Fitting']], 'avg_price' => 85],
    ];
    $totalSetsInUse = min($totalCapacity, round($totalCapacity * 0.7));
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-person-skiing mr-2 text-info"></i>Ski Rentals</h1>
                <p class="text-sm text-base-content/50">Rent equipment to visitors who need gear</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="card bg-gradient-to-br from-info/5 to-info/10 shadow-sm border border-info/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div><div class="text-xs text-base-content/50 mb-1">Rental Shops</div><div class="text-3xl font-bold"><?= count($buildings) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Gear Sets</div><div class="text-3xl font-bold"><?= $totalCapacity ?></div><div class="text-xs text-base-content/50"><?= $totalSetsInUse ?> currently rented</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Rental Revenue</div><div class="text-3xl font-bold text-success"><?= currency($totalRevenue) ?></div><div class="text-xs text-base-content/50">per day</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Utilization</div><div class="text-3xl font-bold"><?= $totalCapacity > 0 ? round($totalSetsInUse / $totalCapacity * 100) : 0 ?>%</div><progress class="progress progress-info w-full mt-1" value="<?= $totalSetsInUse ?>" max="<?= max(1, $totalCapacity) ?>"></progress></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Your Rental Shops</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12"><i class="fa-solid fa-person-skiing text-5xl text-base-content/15 mb-3"></i><p class="font-semibold">No rental shops</p><p class="text-sm text-base-content/50 mt-1">About 40% of visitors need rental gear. Don't miss that revenue.</p></div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $gl = $gearLevels[$b['level']] ?? $gearLevels[1]; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg <?= $isOpen ? 'bg-info/10' : 'bg-base-200' ?> flex items-center justify-center"><i class="fa-solid fa-person-skiing <?= $isOpen ? 'text-info' : 'text-base-content/30' ?> text-lg"></i></div>
                                <div><div class="font-bold"><?= esc($b['name']) ?></div><span class="badge badge-sm <?= $isOpen ? 'badge-success' : 'badge-ghost' ?>"><?= $isOpen ? 'Open' : 'Closed' ?></span> <span class="badge badge-outline badge-xs"><?= $gl['name'] ?> Tier</span></div>
                            </div>
                        </div>
                        <!-- Gear Available -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <?php foreach ($gl['items'] as $item) : ?>
                                <div class="flex items-center gap-1 bg-base-200 rounded-full px-2 py-1 text-xs"><i class="<?= $item['icon'] ?> text-info"></i> <?= $item['name'] ?></div>
                            <?php endforeach ?>
                        </div>
                        <div class="grid grid-cols-4 gap-1 text-center text-xs mb-3">
                            <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $b['capacity'] ?></div><div class="text-[10px] text-base-content/50">Sets</div></div>
                            <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= currency($gl['avg_price']) ?></div><div class="text-[10px] text-base-content/50">Avg/Set</div></div>
                            <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-success"><?= currency($b['revenue_per_day']) ?></div><div class="text-[10px] text-base-content/50">Revenue</div></div>
                            <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $cond ?>%</div><div class="text-[10px] text-base-content/50">Cond.</div></div>
                        </div>
                        <div class="flex gap-1">
                            <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-info' ?>"><i class="fa-solid fa-power-off mr-1"></i><?= $isOpen ? 'Close' : 'Open' ?></button></form>
                            <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Upgrade gear tier?')"><?= csrf_field() ?><button class="btn btn-info btn-xs gap-1"><i class="fa-solid fa-arrow-up"></i></button></form><?php endif ?>
                            <form action="/buildings/sell/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Sell?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
        <div>
            <h2 class="text-lg font-bold mb-3">Open Rental Shop</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : $gl = $gearLevels[$lvl]; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?><input type="hidden" name="type" value="rental"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left"><div class="card-body p-3">
                        <div class="font-semibold text-sm mb-1"><?= $info['name'] ?></div>
                        <div class="flex flex-wrap gap-1 mb-2"><?php foreach (array_slice($gl['items'], 0, 3) as $item) : ?><span class="badge badge-ghost badge-xs"><i class="<?= $item['icon'] ?> mr-1"></i><?= $item['name'] ?></span><?php endforeach ?></div>
                        <div class="flex justify-between text-xs"><span><?= $info['capacity'] ?> sets - ~<?= currency($gl['avg_price']) ?>/rental</span><span class="font-bold text-primary"><?= currency($info['cost']) ?></span></div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
