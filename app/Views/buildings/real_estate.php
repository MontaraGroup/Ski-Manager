<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Real Estate<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $totalValue = 0; $totalMonthly = 0;
    foreach ($buildings as $b) { $totalValue += ($def['levels'][$b['level']]['cost'] ?? 0); $totalMonthly += $b['revenue_per_day'] * 30; }
    $annualROI = $totalValue > 0 ? round(($totalRevenue - $totalUpkeep) * 365 / $totalValue * 100, 1) : 0;
    $paybackDays = ($totalRevenue - $totalUpkeep) > 0 ? round($totalValue / ($totalRevenue - $totalUpkeep)) : 0;
    $propTypes = [1 => ['name' => 'Chalet', 'icon' => 'fa-solid fa-house', 'desc' => 'Cozy mountain retreat'], 2 => ['name' => 'Apartments', 'icon' => 'fa-solid fa-building', 'desc' => 'Multi-unit rental block'], 3 => ['name' => 'Luxury Villa', 'icon' => 'fa-solid fa-landmark', 'desc' => 'High-end estate']];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-city mr-2 text-success"></i>Real Estate Portfolio</h1>
                <p class="text-sm text-base-content/50">Invest in properties for passive rental income</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Portfolio Overview -->
    <div class="card bg-gradient-to-br from-success/5 to-success/10 shadow-sm border border-success/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div><div class="text-xs text-base-content/50 mb-1">Portfolio Value</div><div class="text-2xl font-bold"><?= currency($totalValue) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Monthly Income</div><div class="text-2xl font-bold text-success"><?= currency($totalMonthly) ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Annual ROI</div><div class="text-2xl font-bold <?= $annualROI > 10 ? 'text-success' : ($annualROI > 5 ? 'text-warning' : 'text-error') ?>"><?= $annualROI ?>%</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Payback Period</div><div class="text-2xl font-bold"><?= $paybackDays ?></div><div class="text-xs text-base-content/50">game days</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Tenants</div><div class="text-2xl font-bold"><?= $totalCapacity ?></div><div class="text-xs text-base-content/50">across <?= count($buildings) ?> properties</div></div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-chart-line mr-1"></i> Properties</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12"><i class="fa-solid fa-city text-5xl text-base-content/15 mb-3"></i><p class="font-semibold">No properties yet</p><p class="text-sm text-base-content/50 mt-1">Real estate is the safest long-term investment. Steady income, low risk.</p></div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $pt = $propTypes[$b['level']] ?? $propTypes[1]; $propValue = $def['levels'][$b['level']]['cost'] ?? 0; $dailyProfit = $b['revenue_per_day'] - $b['upkeep_per_day']; $propROI = $propValue > 0 ? round($dailyProfit * 365 / $propValue * 100, 1) : 0; $propPayback = $dailyProfit > 0 ? round($propValue / $dailyProfit) : 999; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl <?= $isOpen ? 'bg-success/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                                <i class="<?= $pt['icon'] ?> text-2xl <?= $isOpen ? 'text-success' : 'text-base-content/30' ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div><span class="font-bold"><?= esc($b['name']) ?></span></div>
                                    <span class="badge <?= $isOpen ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $isOpen ? 'Leased' : 'Vacant' ?></span>
                                </div>
                                <div class="text-xs text-base-content/50 mb-2"><?= $pt['desc'] ?> - <?= $b['capacity'] ?> tenant<?= $b['capacity'] > 1 ? 's' : '' ?></div>
                                <div class="grid grid-cols-5 gap-1 text-center text-xs mb-3">
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= currency($propValue) ?></div><div class="text-[10px] text-base-content/50">Value</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-success"><?= currency($b['revenue_per_day']) ?></div><div class="text-[10px] text-base-content/50">Rent/Day</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-success"><?= currency($dailyProfit) ?></div><div class="text-[10px] text-base-content/50">Profit</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold <?= $propROI > 10 ? 'text-success' : 'text-warning' ?>"><?= $propROI ?>%</div><div class="text-[10px] text-base-content/50">ROI/yr</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $propPayback ?>d</div><div class="text-[10px] text-base-content/50">Payback</div></div>
                                </div>
                                <div class="flex items-center gap-2 mb-2"><span class="text-xs text-base-content/50">Condition</span><progress class="progress <?= $cond >= 60 ? 'progress-success' : 'progress-warning' ?> flex-1" value="<?= $cond ?>" max="100"></progress><span class="text-xs font-mono"><?= $cond ?>%</span></div>
                                <div class="flex gap-1">
                                    <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-success' ?>"><?= $isOpen ? 'Vacate' : 'Lease Out' ?></button></form>
                                    <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Renovate property?')"><?= csrf_field() ?><button class="btn btn-info btn-xs gap-1"><i class="fa-solid fa-arrow-up"></i> Renovate</button></form><?php endif ?>
                                    <form action="/buildings/sell/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Sell property?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-hammer mr-1"></i> Invest</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : $pt = $propTypes[$lvl]; $roi = $info['cost'] > 0 ? round(($info['revenue'] - $info['upkeep']) * 365 / $info['cost'] * 100, 1) : 0; $payback = ($info['revenue'] - $info['upkeep']) > 0 ? round($info['cost'] / ($info['revenue'] - $info['upkeep'])) : 999; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?><input type="hidden" name="type" value="real_estate"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-1"><i class="<?= $pt['icon'] ?> text-success"></i><span class="font-semibold text-sm"><?= $info['name'] ?></span></div>
                        <p class="text-xs text-base-content/50 mb-2"><?= $pt['desc'] ?></p>
                        <div class="grid grid-cols-3 gap-1 text-xs text-center">
                            <div><span class="font-bold text-success"><?= $roi ?>%</span><div class="text-[10px] text-base-content/50">Annual ROI</div></div>
                            <div><span class="font-bold"><?= $payback ?>d</span><div class="text-[10px] text-base-content/50">Payback</div></div>
                            <div><span class="font-bold text-primary"><?= currency($info['cost']) ?></span><div class="text-[10px] text-base-content/50">Price</div></div>
                        </div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>
            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-3">
                <h3 class="font-semibold text-xs mb-2"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i>Investment Tips</h3>
                <ul class="text-xs text-base-content/60 space-y-1">
                    <li><i class="fa-solid fa-chart-line mr-1 text-success"></i> Apartments have the best ROI for their price</li>
                    <li><i class="fa-solid fa-landmark mr-1 text-warning"></i> Luxury villas generate the most per-unit income</li>
                    <li><i class="fa-solid fa-wrench mr-1"></i> Keep condition high to avoid tenant complaints</li>
                    <li><i class="fa-solid fa-shield-halved mr-1"></i> <a href="/insurance" class="link link-primary">Property insurance</a> protects your investments</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
