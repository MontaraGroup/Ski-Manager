<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Snowmaking<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-snowflake mr-2 text-info"></i>Snowmaking</h1>
                <p class="text-sm text-base-content/50">Produce artificial snow to maintain and build your base</p>
            </div>
        </div>
        <div class="flex gap-2">
            <?php if (!empty($cannons)) : ?>
            <form action="/snowmaking/toggle-all" method="post"><?= csrf_field() ?>
                <input type="hidden" name="action" value="on">
                <button class="btn btn-info btn-sm gap-1"><i class="fa-solid fa-power-off"></i> All On</button>
            </form>
            <form action="/snowmaking/toggle-all" method="post"><?= csrf_field() ?>
                <input type="hidden" name="action" value="off">
                <button class="btn btn-ghost btn-sm gap-1"><i class="fa-solid fa-power-off"></i> All Off</button>
            </form>
            <?php endif ?>
            <a href="/equipment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-shop"></i> Buy Cannons</a>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Temperature + Snow Base -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="card shadow-xl <?= $canMakeSnow ? 'bg-gradient-to-br from-sky-100 to-blue-200 dark:from-sky-900 dark:to-blue-950' : 'bg-gradient-to-br from-orange-100 to-red-200 dark:from-orange-900 dark:to-red-950' ?>">
            <div class="card-body p-5">
                <div class="flex items-center gap-4">
                    <div class="text-4xl">
                        <?= $canMakeSnow ? '<i class="fa-solid fa-snowflake text-info"></i>' : '<i class="fa-solid fa-temperature-high text-error"></i>' ?>
                    </div>
                    <div>
                        <div class="text-3xl font-bold"><?= temp($temp) ?></div>
                        <div class="text-sm font-semibold <?= $canMakeSnow ? 'text-info' : 'text-error' ?>">
                            <?= $canMakeSnow ? ($temp <= -8 ? 'Perfect snowmaking conditions' : 'Snowmaking conditions: OK') : 'Too warm for snowmaking (needs ≤ ' . (isImperial() ? '28°F' : '-2°C') . ')' ?>
                        </div>
                        <?php if ($canMakeSnow && $temp <= -8) : ?>
                        <div class="text-xs opacity-60 mt-1"><i class="fa-solid fa-arrow-up mr-1"></i>Cold bonus: +50% snow quality</div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-sm"><i class="fa-solid fa-mountain-sun mr-1"></i> Snow Base</h3>
                    <span class="text-xs text-base-content/50"><?= $snowBase >= 60 ? 'Excellent' : ($snowBase >= 30 ? 'Adequate' : 'Low — make more snow') ?></span>
                </div>
                <div class="text-3xl font-bold mb-1"><?= snow($snowBase) ?></div>
                <progress class="progress <?= $snowBase >= 60 ? 'progress-info' : ($snowBase >= 30 ? 'progress-warning' : 'progress-error') ?> w-full h-3" value="<?= min($snowBase, 150) ?>" max="150"></progress>
                <div class="flex justify-between text-xs text-base-content/40 mt-1">
                    <span>0</span><span>Min. skiable (30)</span><span>150+</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Status -->
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-solid fa-bolt text-warning"></i><span class="text-xs font-semibold">Energy</span></div>
                <a href="/energy" class="link link-primary text-xs">Manage</a>
            </div>
            <div class="flex items-center justify-between mt-1 text-xs">
                <span>Supply: <?= number_format($energySupply) ?> kWh</span>
                <span class="<?= $energySupply >= $totalEnergy ? 'text-success' : 'text-error' ?>">Need: <?= number_format($totalEnergy) ?> kWh</span>
            </div>
            <progress class="progress <?= $energySupply >= $totalEnergy ? 'progress-success' : 'progress-error' ?> w-full mt-1" value="<?= min($energySupply, max(1, $totalEnergy)) ?>" max="<?= max(1, $totalEnergy) ?>"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-solid fa-droplet text-info"></i><span class="text-xs font-semibold">Water</span></div>
                <a href="/water" class="link link-primary text-xs">Manage</a>
            </div>
            <div class="flex items-center justify-between mt-1 text-xs">
                <span>Supply: <?= number_format($waterSupply) ?> L</span>
                <span class="<?= $waterSupply >= $totalWater ? 'text-success' : 'text-error' ?>">Need: <?= number_format($totalWater) ?> L</span>
            </div>
            <progress class="progress <?= $waterSupply >= $totalWater ? 'progress-success' : 'progress-error' ?> w-full mt-1" value="<?= min($waterSupply, max(1, $totalWater)) ?>" max="<?= max(1, $totalWater) ?>"></progress>
        </div></div>
    </div>

    <!-- Production Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-info"><?= $totalOutput ?></div>
            <div class="text-xs text-base-content/50"><?= isImperial() ? 'in' : 'cm' ?>/day output</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($activeCannons) ?>/<?= count($cannons) ?></div>
            <div class="text-xs text-base-content/50">cannons running</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($snowmakers) ?></div>
            <div class="text-xs text-base-content/50">crew on duty</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-warning"><?= number_format($totalEnergy) ?></div>
            <div class="text-xs text-base-content/50">kWh/day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-blue-400"><?= number_format($totalWater) ?> L</div>
            <div class="text-xs text-base-content/50">water/day</div>
        </div></div>
    </div>

    <!-- Snow Cannons -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-snowflake mr-1 text-info"></i> Snow Cannons</h2>
    <?php if (empty($cannons)) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-12">
            <i class="fa-solid fa-snowflake text-5xl text-base-content/15 mb-3"></i>
            <p class="font-semibold">No snow cannons</p>
            <p class="text-sm text-base-content/50 mt-1">Buy snowmakers from the <a href="/equipment" class="link link-primary">Equipment Shop</a></p>
        </div></div>
    <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($cannons as $cannon) : ?>
            <?php $isOn = $cannon['status'] === 'active'; $cond = (int) $cannon['condition_pct']; $isBroken = $cond <= 0; ?>
            <div class="card bg-base-100 shadow-sm <?= $isBroken ? 'border border-error/30' : '' ?>">
                <div class="card-body p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg <?= $isBroken ? 'bg-error/20' : ($isOn ? ($canMakeSnow ? 'bg-info/20' : 'bg-warning/20') : 'bg-base-200') ?> flex items-center justify-center">
                                <i class="fa-solid fa-<?= $isBroken ? 'wrench text-error' : 'snowflake' ?> <?= $isOn && !$isBroken ? ($canMakeSnow ? 'text-info' : 'text-warning') : 'text-base-content/30' ?>"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm"><?= esc($cannon['name']) ?></div>
                                <div class="text-xs text-base-content/50"><?= esc($cannon['brand']) ?> · <?= currency($cannon['daily_cost'] ?? 0) ?>/day</div>
                            </div>
                        </div>
                        <span class="badge badge-xs <?= $isBroken ? 'badge-error' : ($isOn ? ($canMakeSnow ? 'badge-info' : 'badge-warning') : 'badge-ghost') ?>">
                            <?= $isBroken ? 'BROKEN' : ($isOn ? ($canMakeSnow ? 'PRODUCING' : 'TOO WARM') : 'OFF') ?>
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs text-center mb-2">
                        <div><span class="font-bold text-info"><?= $cannon['output_per_day'] ?> <?= isImperial() ? 'in' : 'cm' ?></span><br><span class="text-base-content/50">Output</span></div>
                        <div><span class="font-bold"><?= number_format($cannon['energy_kwh']) ?></span><br><span class="text-base-content/50">kWh</span></div>
                        <div><span class="font-bold"><?= number_format($cannon['water_liters']) ?>L</span><br><span class="text-base-content/50">Water</span></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-base-content/50 w-10">Health</span>
                        <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $cond ?>" max="100"></progress>
                        <span class="text-xs font-mono w-8 text-right"><?= $cond ?>%</span>
                    </div>
                    <form action="/snowmaking/assign" method="post" class="flex gap-2 mt-2"><?= csrf_field() ?>
                        <input type="hidden" name="cannon_id" value="<?= $cannon['id'] ?>">
                        <select name="slope_id" class="select select-bordered select-xs flex-1">
                            <option value="">- No trail -</option>
                            <?php foreach ($slopes as $sl) : ?>
                            <option value="<?= $sl['id'] ?>" <?= $cannon['assigned_to'] === 'slope_' . $sl['id'] ? 'selected' : '' ?>><?= esc($sl['name']) ?></option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <div class="flex gap-1 mt-1">
                        <?php if (!$isBroken) : ?>
                        <form action="/snowmaking/toggle/<?= $cannon['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                            <button class="btn btn-xs w-full <?= $isOn ? 'btn-ghost' : 'btn-info' ?>"><i class="fa-solid fa-power-off mr-1"></i><?= $isOn ? 'Turn Off' : 'Turn On' ?></button>
                        </form>
                        <?php endif ?>
                        <?php if ($cond < 100) : ?>
                        <form action="/snowmaking/repair/<?= $cannon['id'] ?>" method="post" data-confirm="Repair <?= esc($cannon['name']) ?> for <?= currency($repairCost) ?>?"><?= csrf_field() ?>
                            <button class="btn btn-xs btn-outline gap-1"><i class="fa-solid fa-wrench"></i> <?= currency($repairCost) ?></button>
                        </form>
                        <?php endif ?>
                        <form action="/snowmaking/sell/<?= $cannon['id'] ?>" method="post" data-confirm="Sell <?= esc($cannon['name']) ?>?"><?= csrf_field() ?>
                            <button class="btn btn-xs btn-ghost text-error"><i class="fa-solid fa-coins"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Trail Snow Depth -->
    <?php if (!empty($slopes)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-mountain mr-1"></i> Trail Snow Base</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($slopes as $sl) : ?>
        <?php
            $depth = (int) ($sl['snow_depth'] ?? 50);
            $assigned = array_filter($cannons, fn($c) => $c['assigned_to'] === 'slope_' . $sl['id'] && $c['status'] === 'active');
            $cannonOutput = array_sum(array_column($assigned, 'output_per_day'));
        ?>
        <div class="card bg-base-100 shadow-sm <?= $depth < 20 ? 'border border-error/30' : '' ?>">
            <div class="card-body p-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-semibold text-sm truncate"><?= esc($sl['name']) ?></span>
                    <div class="flex items-center gap-1">
                        <?php if ($depth < 20) : ?><span class="badge badge-xs badge-error">Critical</span><?php endif ?>
                        <span class="badge badge-xs <?= match($sl['snow_quality'] ?? 'packed') { 'powder' => 'badge-info', 'groomed' => 'badge-success', 'packed' => 'badge-ghost', 'icy' => 'badge-warning', 'bare' => 'badge-error', default => 'badge-ghost' } ?>"><?= ucfirst($sl['snow_quality'] ?? 'packed') ?></span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <progress class="progress <?= $depth >= 60 ? 'progress-info' : ($depth >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-2" value="<?= min($depth, 150) ?>" max="150"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right"><?= snow($depth) ?></span>
                </div>
                <div class="flex items-center justify-between mt-1 text-xs text-base-content/50">
                    <span><?= ucwords(str_replace('_', ' ', $sl['subtype'] ?? $sl['item_type'] ?? '')) ?></span>
                    <?php if (count($assigned) > 0) : ?>
                    <span class="text-info"><i class="fa-solid fa-snowflake mr-1"></i><?= count($assigned) ?> cannon<?= count($assigned) > 1 ? 's' : '' ?> · +<?= $cannonOutput ?> <?= isImperial() ? 'in' : 'cm' ?>/day</span>
                    <?php else : ?>
                    <span class="text-base-content/30"><i class="fa-solid fa-snowflake mr-1"></i>No cannons</span>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Snowmaker Crew -->
    <?php if (!empty($snowmakers)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-users mr-1"></i> Snowmaker Crew</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($snowmakers as $sm) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-full bg-info/10 flex items-center justify-center">
                    <i class="fa-solid fa-user text-info text-xs"></i>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-semibold"><?= esc($sm['name']) ?></div>
                    <div class="flex items-center gap-2 text-xs text-base-content/50">
                        <span class="flex items-center gap-0.5"><?php for($i=0;$i<min($sm['level'],5);$i++): ?><i class="fa-solid fa-star text-warning text-[8px]"></i><?php endfor ?></span>
                        <span>Lv.<?= $sm['level'] ?></span>
                        <span>·</span>
                        <span>Morale <?= $sm['morale'] ?>%</span>
                        <?php if ($sm['assigned_to']) : ?><span>·</span><span class="text-info"><?= esc($sm['assigned_to']) ?></span><?php endif ?>
                    </div>
                </div>
            </div>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php else : ?>
    <div class="alert alert-warning mb-6"><i class="fa-solid fa-user-slash"></i><span>No snowmaker crew. <a href="/staff/hire" class="link font-semibold">Hire snowmakers</a> to operate your cannons.</span></div>
    <?php endif ?>

    <!-- Snowmaking Economics -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h3 class="text-sm font-bold mb-3"><i class="fa-solid fa-chart-line mr-1"></i> Daily Costs</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <div class="text-xs text-base-content/50">Equipment</div>
                <div class="text-lg font-bold"><?= currency($dailyEquipCost) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50">Crew Salaries</div>
                <div class="text-lg font-bold"><?= currency($crewCost) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50">Total/Day</div>
                <div class="text-lg font-bold text-error"><?= currency($dailyEquipCost + $crewCost) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50">Cost per <?= isImperial() ? 'in' : 'cm' ?></div>
                <div class="text-lg font-bold"><?= $totalOutput > 0 ? currency((int)(($dailyEquipCost + $crewCost) / max(1, $totalOutput))) : '-' ?></div>
            </div>
        </div>
    </div></div>

    <!-- How It Works -->
    <div class="collapse collapse-arrow bg-base-100 shadow-sm mb-6">
        <input type="checkbox" />
        <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i> How Snowmaking Works</div>
        <div class="collapse-content text-sm text-base-content/70">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide mb-2 text-base-content/40">Temperature</h4>
                    <ul class="space-y-1">
                        <li><i class="fa-solid fa-snowflake text-xs mr-2 text-info"></i>Below <?= isImperial() ? "18°F" : "-8°C" ?>: optimal, +50% snow quality</li>
                        <li><i class="fa-solid fa-check text-xs mr-2 text-success"></i><?= isImperial() ? "18°F to 28°F" : "-8°C to -2°C" ?>: normal operation</li>
                        <li><i class="fa-solid fa-ban text-xs mr-2 text-error"></i>Above <?= isImperial() ? "28°F" : "-2°C" ?>: too warm, cannons idle</li>
                        <li><i class="fa-solid fa-bolt text-xs mr-2 text-warning"></i>Each cannon uses energy + water per day</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide mb-2 text-base-content/40">Production</h4>
                    <ul class="space-y-1">
                        <li><i class="fa-solid fa-mountain text-xs mr-2 text-info"></i>Snow output increases snow base daily</li>
                        <li><i class="fa-solid fa-user text-xs mr-2 text-info"></i>Crew level boosts efficiency (+5%/level)</li>
                        <li><i class="fa-solid fa-wrench text-xs mr-2 text-warning"></i>Low condition reduces output</li>
                        <li><i class="fa-solid fa-coins text-xs mr-2 text-success"></i>Sell unused cannons for 50% refund</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="flex flex-wrap gap-2">
        <a href="/equipment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-toolbox"></i> Equipment Shop</a>
        <a href="/staff/hire" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-user-plus"></i> Hire Crew</a>
        <a href="/energy" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-bolt"></i> Energy</a>
        <a href="/water" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-droplet"></i> Water</a>
        <a href="/grooming" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-tractor"></i> Grooming</a>
    </div>
</div>
<?= $this->endSection() ?>
