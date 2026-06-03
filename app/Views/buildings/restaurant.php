<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Restaurants<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $db = db_connect(); $userId = auth()->id();
    $chefs = $db->table('staff')->where('user_id', $userId)->where('role', 'chef')->where('status', 'active')->get()->getResultArray();
    $avgChefLevel = count($chefs) > 0 ? round(array_sum(array_column($chefs, 'level')) / count($chefs), 1) : 0;
    $foodQuality = min(100, 40 + ($avgChefLevel * 15) + (count($chefs) * 5));
    $openRestaurants = array_filter($buildings, fn($b) => $b['status'] === 'open');
    $mealPeriods = ['Breakfast (7-10)', 'Lunch (11-14)', 'Apres-Ski (15-18)', 'Dinner (18-22)'];
    $cuisineMap = [1 => ['name' => 'Fast Food', 'icon' => 'fa-solid fa-burger', 'color' => 'text-warning', 'menu' => ['Burgers', 'Fries', 'Hot Dogs', 'Pizza Slices', 'Soft Drinks']],
        2 => ['name' => 'Alpine Cuisine', 'icon' => 'fa-solid fa-cheese', 'color' => 'text-amber-600', 'menu' => ['Fondue', 'Raclette', 'Tartiflette', 'Rosti', 'Gluhwein']],
        3 => ['name' => 'Fine Dining', 'icon' => 'fa-solid fa-wine-glass', 'color' => 'text-purple-500', 'menu' => ['Tasting Menu', 'Wagyu Beef', 'Truffle Risotto', 'Wine Pairing', 'Souffle']]];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-utensils mr-2 text-warning"></i>Restaurants & Dining</h1>
                <p class="text-sm text-base-content/50">Feed visitors across <?= count($mealPeriods) ?> daily meal periods</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Kitchen Dashboard -->
    <div class="card bg-gradient-to-br from-warning/5 to-warning/10 shadow-sm border border-warning/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Venues</div>
                    <div class="text-3xl font-bold"><?= count($buildings) ?></div>
                    <div class="text-xs text-base-content/50"><?= count($openRestaurants) ?> open</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Total Seats</div>
                    <div class="text-3xl font-bold"><?= number_format($totalCapacity) ?></div>
                    <div class="text-xs text-base-content/50">across all venues</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Food Quality</div>
                    <div class="text-3xl font-bold <?= $foodQuality >= 70 ? 'text-success' : ($foodQuality >= 40 ? 'text-warning' : 'text-error') ?>"><?= $foodQuality ?>%</div>
                    <progress class="progress <?= $foodQuality >= 70 ? 'progress-success' : 'progress-warning' ?> w-full mt-1" value="<?= $foodQuality ?>" max="100"></progress>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Chefs</div>
                    <div class="text-3xl font-bold"><?= count($chefs) ?></div>
                    <div class="text-xs text-base-content/50">Avg Lv.<?= $avgChefLevel ?></div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Net Profit</div>
                    <div class="text-3xl font-bold text-success"><?= currency($totalRevenue - $totalUpkeep) ?></div>
                    <div class="text-xs text-base-content/50">per day</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Meal Periods -->
    <div class="grid grid-cols-4 gap-2 mb-6">
        <?php $periodIcons = ['fa-solid fa-mug-hot', 'fa-solid fa-sun', 'fa-solid fa-beer-mug-empty', 'fa-solid fa-moon']; ?>
        <?php foreach ($mealPeriods as $i => $period) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="<?= $periodIcons[$i] ?> text-lg text-warning mb-1"></i>
            <div class="text-xs font-semibold"><?= explode(' ', $period)[0] ?></div>
            <div class="text-[10px] text-base-content/50"><?= explode(' ', $period, 2)[1] ?? '' ?></div>
        </div></div>
        <?php endforeach ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Your Restaurants</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-utensils text-5xl text-base-content/15 mb-3"></i>
                    <p class="font-semibold">No restaurants yet</p>
                    <p class="text-sm text-base-content/50 mt-1">Hungry visitors leave sooner. Build dining venues to keep them on the mountain.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $c = $cuisineMap[$b['level']] ?? $cuisineMap[1]; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl <?= $isOpen ? 'bg-warning/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                                <i class="<?= $c['icon'] ?> text-2xl <?= $isOpen ? $c['color'] : 'text-base-content/30' ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div><span class="font-bold"><?= esc($b['name']) ?></span> <span class="badge badge-outline badge-xs ml-1"><?= $c['name'] ?></span></div>
                                    <span class="badge <?= $isOpen ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $isOpen ? 'Serving' : 'Closed' ?></span>
                                </div>
                                <!-- Mini Menu -->
                                <div class="flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($c['menu'] as $dish) : ?>
                                        <span class="badge badge-ghost badge-xs"><?= $dish ?></span>
                                    <?php endforeach ?>
                                </div>
                                <div class="grid grid-cols-4 gap-1 text-center text-xs mb-3">
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $b['capacity'] ?></div><div class="text-[10px] text-base-content/50">Seats</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-success"><?= currency($b['revenue_per_day']) ?></div><div class="text-[10px] text-base-content/50">Revenue</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-error"><?= currency($b['upkeep_per_day']) ?></div><div class="text-[10px] text-base-content/50">Costs</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $cond ?>%</div><div class="text-[10px] text-base-content/50">Cond.</div></div>
                                </div>
                                <div class="flex gap-1">
                                    <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-warning' ?>"><i class="fa-solid fa-power-off mr-1"></i><?= $isOpen ? 'Close' : 'Open' ?></button></form>
                                    <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Upgrade cuisine?')"><?= csrf_field() ?><button class="btn btn-info btn-xs gap-1"><i class="fa-solid fa-arrow-up"></i> Upgrade</button></form><?php endif ?>
                                    <form action="/buildings/sell/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Sell?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3">Open New Venue</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : $c = $cuisineMap[$lvl]; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?><input type="hidden" name="type" value="restaurant"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-1">
                            <i class="<?= $c['icon'] ?> <?= $c['color'] ?>"></i>
                            <span class="font-semibold text-sm"><?= $info['name'] ?></span>
                        </div>
                        <div class="flex flex-wrap gap-1 mb-2"><?php foreach (array_slice($c['menu'], 0, 3) as $dish) : ?><span class="badge badge-ghost badge-xs"><?= $dish ?></span><?php endforeach ?></div>
                        <div class="flex justify-between text-xs">
                            <span><?= $info['capacity'] ?> seats - <?= currency($info['revenue']) ?>/day</span>
                            <span class="font-bold text-primary"><?= currency($info['cost']) ?></span>
                        </div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>

            <?php if (count($chefs) === 0) : ?>
            <div class="alert alert-warning mt-4 text-xs"><i class="fa-solid fa-hat-chef"></i><div><p class="font-bold">No chefs hired</p><p><a href="/staff/hire" class="link">Hire chefs</a> to improve food quality and revenue.</p></div></div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
