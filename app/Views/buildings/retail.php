<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Retail<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $merchLevels = [
        1 => ['name' => 'Souvenirs', 'color' => 'text-secondary', 'products' => [['icon' => 'fa-solid fa-mug-hot', 'name' => 'Mugs'], ['icon' => 'fa-solid fa-key', 'name' => 'Keychains'], ['icon' => 'fa-solid fa-image', 'name' => 'Postcards'], ['icon' => 'fa-solid fa-magnet', 'name' => 'Magnets']]],
        2 => ['name' => 'Ski Shop', 'color' => 'text-info', 'products' => [['icon' => 'fa-solid fa-glasses', 'name' => 'Goggles'], ['icon' => 'fa-solid fa-mitten', 'name' => 'Gloves'], ['icon' => 'fa-solid fa-vest', 'name' => 'Jackets'], ['icon' => 'fa-solid fa-socks', 'name' => 'Base Layers'], ['icon' => 'fa-solid fa-helmet-safety', 'name' => 'Helmets']]],
        3 => ['name' => 'Boutique', 'color' => 'text-purple-500', 'products' => [['icon' => 'fa-solid fa-gem', 'name' => 'Jewelry'], ['icon' => 'fa-solid fa-shirt', 'name' => 'Designer Wear'], ['icon' => 'fa-solid fa-bag-shopping', 'name' => 'Luxury Goods'], ['icon' => 'fa-solid fa-clock', 'name' => 'Watches'], ['icon' => 'fa-solid fa-wine-bottle', 'name' => 'Fine Spirits']]],
    ];
    $avgMargin = count($buildings) > 0 ? round(($totalRevenue - $totalUpkeep) / max(1, $totalRevenue) * 100) : 0;
    $totalProducts = 0; foreach ($buildings as $b) { $totalProducts += count($merchLevels[$b['level']]['products'] ?? []); }
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-store mr-2 text-secondary"></i>Retail & Merchandise</h1>
                <p class="text-sm text-base-content/50">Sell souvenirs, apparel, and premium goods</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="card bg-gradient-to-br from-secondary/5 to-secondary/10 shadow-sm border border-secondary/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div><div class="text-xs text-base-content/50 mb-1">Stores</div><div class="text-3xl font-bold"><?= count($buildings) ?></div><div class="text-xs text-base-content/50"><?= $totalProducts ?> product lines</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Daily Sales</div><div class="text-3xl font-bold text-success"><?= currency($totalRevenue) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Profit Margin</div><div class="text-3xl font-bold"><?= $avgMargin ?>%</div><progress class="progress progress-secondary w-full mt-1" value="<?= $avgMargin ?>" max="100"></progress></div>
                <div><div class="text-xs text-base-content/50 mb-1">Net Profit</div><div class="text-3xl font-bold text-success"><?= currency($totalRevenue - $totalUpkeep) ?></div><div class="text-xs text-base-content/50">per day</div></div>
            </div>
        </div>
    </div>

    <!-- Best Sellers Showcase -->
    <?php if (!empty($buildings)) : ?>
    <div class="mb-6">
        <h2 class="text-sm font-bold mb-2 text-base-content/50 uppercase tracking-wide">Product Categories</h2>
        <div class="flex flex-wrap gap-2">
            <?php $shown = []; foreach ($buildings as $b) : $ml = $merchLevels[$b['level']] ?? $merchLevels[1]; foreach ($ml['products'] as $p) : if (!in_array($p['name'], $shown)) : $shown[] = $p['name']; ?>
                <div class="badge badge-lg gap-1 py-3"><i class="<?= $p['icon'] ?>"></i> <?= $p['name'] ?></div>
            <?php endif; endforeach; endforeach ?>
        </div>
    </div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Your Stores</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12"><i class="fa-solid fa-store text-5xl text-base-content/15 mb-3"></i><p class="font-semibold">No stores yet</p><p class="text-sm text-base-content/50 mt-1">Visitors love taking home souvenirs. High margin, low effort.</p></div></div>
            <?php else : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $ml = $merchLevels[$b['level']] ?? $merchLevels[1]; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 rounded-xl <?= $isOpen ? 'bg-secondary/10' : 'bg-base-200' ?> flex items-center justify-center">
                                <i class="fa-solid fa-store text-xl <?= $isOpen ? 'text-secondary' : 'text-base-content/30' ?>"></i>
                            </div>
                            <div>
                                <div class="font-bold text-sm"><?= esc($b['name']) ?></div>
                                <span class="badge badge-sm <?= $isOpen ? 'badge-success' : 'badge-ghost' ?>"><?= $isOpen ? 'Open' : 'Closed' ?></span>
                                <span class="badge badge-outline badge-xs <?= $ml['color'] ?>"><?= $ml['name'] ?></span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1 mb-2">
                            <?php foreach ($ml['products'] as $p) : ?>
                                <span class="badge badge-ghost badge-xs"><i class="<?= $p['icon'] ?> mr-0.5"></i><?= $p['name'] ?></span>
                            <?php endforeach ?>
                        </div>
                        <div class="flex items-center justify-between text-xs mb-3">
                            <span class="text-success font-bold"><?= currency($b['revenue_per_day']) ?>/day</span>
                            <span class="text-base-content/50">Upkeep: <?= currency($b['upkeep_per_day']) ?></span>
                            <span>Cond: <?= $cond ?>%</span>
                        </div>
                        <div class="flex gap-1">
                            <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-secondary' ?>"><?= $isOpen ? 'Close' : 'Open' ?></button></form>
                            <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Upgrade?')"><?= csrf_field() ?><button class="btn btn-info btn-xs"><i class="fa-solid fa-arrow-up"></i></button></form><?php endif ?>
                            <form action="/buildings/sell/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Sell?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
        <div>
            <h2 class="text-lg font-bold mb-3">Open New Store</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : $ml = $merchLevels[$lvl]; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?><input type="hidden" name="type" value="retail"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-1"><i class="fa-solid fa-store <?= $ml['color'] ?>"></i><span class="font-semibold text-sm"><?= $info['name'] ?></span></div>
                        <div class="flex flex-wrap gap-1 mb-2"><?php foreach (array_slice($ml['products'], 0, 3) as $p) : ?><span class="badge badge-ghost badge-xs"><?= $p['name'] ?></span><?php endforeach ?></div>
                        <div class="flex justify-between text-xs"><span><?= currency($info['revenue']) ?>/day revenue</span><span class="font-bold text-primary"><?= currency($info['cost']) ?></span></div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
