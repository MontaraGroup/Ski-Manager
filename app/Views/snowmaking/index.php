<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Snowmaking<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-snowflake mr-2 text-info"></i>Snowmaking</h1>
                <p class="text-sm text-base-content/50">Produce artificial snow to keep your slopes covered</p>
            </div>
        </div>
        <a href="/equipment" class="btn btn-info btn-sm gap-1"><i class="fa-solid fa-shop"></i> Buy Snow Cannons</a>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Resource Status -->
    <?php
        $energySources = db_connect()->table("energy_management")->where("user_id", auth()->id())->where("status", "active")->get()->getResultArray();
        $waterSources = db_connect()->table("water_management")->where("user_id", auth()->id())->where("status", "active")->get()->getResultArray();
        $energySupply = array_sum(array_column($energySources, "output_kwh"));
        $waterSupply = array_sum(array_column($waterSources, "output_liters"));
        $energyNeeded = $totalEnergy;
        $waterNeeded = $totalWater;
    ?>
    <div class="grid grid-cols-2 gap-3 mb-4">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-solid fa-bolt text-warning"></i><span class="text-xs font-semibold">Energy</span></div>
                <a href="/energy" class="link link-primary text-xs">Manage</a>
            </div>
            <div class="flex items-center justify-between mt-1 text-xs"><span>Supply: <?= number_format($energySupply) ?> kWh</span><span class="<?= $energySupply >= $energyNeeded ? "text-success" : "text-error" ?>">Need: <?= number_format($energyNeeded) ?> kWh</span></div>
            <progress class="progress <?= $energySupply >= $energyNeeded ? "progress-success" : "progress-error" ?> w-full mt-1" value="<?= min($energySupply, $energyNeeded) ?>" max="<?= max(1, $energyNeeded) ?>"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-solid fa-droplet text-info"></i><span class="text-xs font-semibold">Water</span></div>
                <a href="/water" class="link link-primary text-xs">Manage</a>
            </div>
            <div class="flex items-center justify-between mt-1 text-xs"><span>Supply: <?= number_format($waterSupply) ?> L</span><span class="<?= $waterSupply >= $waterNeeded ? "text-success" : "text-error" ?>">Need: <?= number_format($waterNeeded) ?> L</span></div>
            <progress class="progress <?= $waterSupply >= $waterNeeded ? "progress-success" : "progress-error" ?> w-full mt-1" value="<?= min($waterSupply, $waterNeeded) ?>" max="<?= max(1, $waterNeeded) ?>"></progress>
        </div></div>
    </div>

    <!-- Temperature Status -->
    <div class="card shadow-xl mb-6 <?= $canMakeSnow ? 'bg-gradient-to-br from-sky-100 to-blue-200 dark:from-sky-900 dark:to-blue-950' : 'bg-gradient-to-br from-orange-100 to-red-200 dark:from-orange-900 dark:to-red-950' ?>">
        <div class="card-body p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">
                        <?= $canMakeSnow ? '<i class="fa-solid fa-snowflake text-info"></i>' : '<i class="fa-solid fa-temperature-high text-error"></i>' ?>
                    </div>
                    <div>
                        <div class="text-3xl font-bold"><?= temp($temp) ?></div>
                        <div class="text-sm <?= $canMakeSnow ? 'text-info' : 'text-error' ?> font-semibold">
                            <?= $canMakeSnow ? 'Snowmaking conditions: OPTIMAL' : 'Too warm for snowmaking (needs &le; -2&deg;C)' ?>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold"><?= count($snowmakers) ?> snowmaker<?= count($snowmakers) !== 1 ? 's' : '' ?></div>
                    <div class="text-xs opacity-60">on duty</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Stats -->
    <?php $activeCannons = array_filter($cannons, fn($c) => $c['status'] === 'active'); ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-snowflake text-info text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= $totalOutput ?></div>
            <div class="text-xs text-base-content/50"><?= isImperial() ? "in" : "cm" ?>cm&sup3;/day outputsup3;/day output</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-gauge-high text-warning text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= count($activeCannons) ?>/<?= count($cannons) ?></div>
            <div class="text-xs text-base-content/50">cannons running</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-bolt text-error text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= number_format($totalEnergy) ?></div>
            <div class="text-xs text-base-content/50">kWh/day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-droplet text-blue-400 text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= number_format($totalWater) ?> L</div>
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
            <?php $isOn = $cannon['status'] === 'active'; $cond = (int) $cannon['condition_pct']; ?>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-3">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg <?= $isOn ? ($canMakeSnow ? 'bg-info/20' : 'bg-warning/20') : 'bg-base-200' ?> flex items-center justify-center">
                                <i class="fa-solid fa-snowflake <?= $isOn ? ($canMakeSnow ? 'text-info' : 'text-warning') : 'text-base-content/30' ?>"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm"><?= esc($cannon['name']) ?></div>
                                <div class="text-xs text-base-content/50"><?= esc($cannon['brand']) ?></div>
                            </div>
                        </div>
                        <span class="badge badge-xs <?= $isOn ? ($canMakeSnow ? 'badge-info' : 'badge-warning') : 'badge-ghost' ?>"><?= $isOn ? ($canMakeSnow ? 'PRODUCING' : 'ON (too warm)') : 'OFF' ?></span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-xs text-center">
                        <div><span class="font-bold"><?= $cannon['output_per_day'] ?></span><br><span class="text-base-content/50">Output</span></div>
                        <div><span class="font-bold"><?= number_format($cannon['energy_kwh']) ?></span><br><span class="text-base-content/50">kWh</span></div>
                        <div><span class="font-bold"><?= number_format($cannon['water_liters']) ?>L</span><br><span class="text-base-content/50">Water</span></div>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $cond ?>" max="100"></progress>
                        <span class="text-xs font-mono"><?= $cond ?>%</span>
                    </div>
                    <div class="flex gap-1 mt-2">
                        <form action="/snowmaking/toggle/<?= $cannon['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                            <button class="btn btn-xs w-full <?= $isOn ? 'btn-ghost' : 'btn-info' ?>"><i class="fa-solid fa-power-off mr-1"></i><?= $isOn ? 'Turn Off' : 'Turn On' ?></button>
                        </form>
                        <?php if ($cond < 100) : ?><form action="/snowmaking/repair/<?= $cannon['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-xs btn-outline"><i class="fa-solid fa-wrench"></i></button></form><?php endif ?>
                        <form action="/snowmaking/sell/<?= $cannon['id'] ?>" method="post" onsubmit="return confirm('Sell this cannon?')"><?= csrf_field() ?><button class="btn btn-xs btn-ghost text-error"><i class="fa-solid fa-trash"></i></button></form>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>

    <!-- Snowmaker Staff -->
    <?php if (!empty($snowmakers)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-users mr-1"></i> Snowmaker Crew</h2>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr><th>Name</th><th>Level</th><th>Morale</th></tr></thead>
            <tbody>
            <?php foreach ($snowmakers as $sm) : ?>
                <tr>
                    <td class="font-semibold"><?= esc($sm['name']) ?></td>
                    <td>Lv.<?= $sm['level'] ?></td>
                    <td><div class="flex items-center gap-2"><progress class="progress <?= $sm['morale'] >= 60 ? 'progress-success' : ($sm['morale'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $sm['morale'] ?>" max="100"></progress><span class="text-xs"><?= $sm['morale'] ?>%</span></div></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div></div></div>
    <?php endif ?>

    <!-- Related Systems -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-link mr-1"></i> Related Systems</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <a href="/equipment" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid fa-toolbox text-warning text-xl"></i>
            <div><div class="text-sm font-bold">Equipment Shop</div><div class="text-xs text-base-content/50">Buy snow cannons and groomers</div></div>
            <i class="fa-solid fa-chevron-right text-base-content/30 ml-auto"></i>
        </div></a>
        <a href="/energy" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid fa-bolt text-warning text-xl"></i>
            <div><div class="text-sm font-bold">Energy Management</div><div class="text-xs text-base-content/50">Power supply for your cannons</div></div>
            <i class="fa-solid fa-chevron-right text-base-content/30 ml-auto"></i>
        </div></a>
        <a href="/water" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid fa-droplet text-info text-xl"></i>
            <div><div class="text-sm font-bold">Water Management</div><div class="text-xs text-base-content/50">Water supply for snow production</div></div>
            <i class="fa-solid fa-chevron-right text-base-content/30 ml-auto"></i>
        </div></a>
    </div>
</div>
<?= $this->endSection() ?>
