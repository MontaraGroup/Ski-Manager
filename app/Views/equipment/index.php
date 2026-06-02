<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Equipment Shop<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-shop mr-2 text-primary"></i>Equipment Shop</h1>
            <p class="text-sm text-base-content/50">Buy snow groomers and snowmaking machines from real brands</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= count($ownedGroomers) ?></div><div class="text-xs text-base-content/50">Groomers</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= count($ownedSnowmakers) ?></div><div class="text-xs text-base-content/50">Snow Machines</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= count($equipment) ?></div><div class="text-xs text-base-content/50">Total Fleet</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><?= currency($totalFuel) ?></div><div class="text-xs text-base-content/50">Fuel/Day</div></div></div>
    </div>

    <!-- Owned Equipment -->
    <?php if (!empty($equipment)) : ?>
    <div class="mb-8">
        <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-warehouse mr-1"></i>Your Fleet</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($equipment as $eq) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg <?= $eq['status'] === 'active' ? ($eq['equipment_type'] === 'groomer' ? 'bg-success/20' : 'bg-info/20') : 'bg-base-200' ?> flex items-center justify-center">
                        <i class="<?= $eq['equipment_type'] === 'groomer' ? 'fa-solid fa-truck-monster' : 'fa-solid fa-snowflake' ?> <?= $eq['status'] === 'active' ? ($eq['equipment_type'] === 'groomer' ? 'text-success' : 'text-info') : 'text-base-content/30' ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-semibold truncate"><?= esc($eq['name']) ?></div>
                        <div class="text-xs text-base-content/50"><?= esc($eq['brand']) ?> · <?= currency((int)$eq['fuel_cost']) ?>/day</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress <?= (int)$eq['condition_pct'] > 50 ? 'progress-success' : ((int)$eq['condition_pct'] > 20 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $eq['condition_pct'] ?>" max="100"></progress>
                    <span class="text-xs"><?= $eq['condition_pct'] ?>%</span>
                    <?php if ($eq['status'] === 'active') : ?><span class="badge badge-success badge-xs">On</span>
                    <?php elseif ($eq['status'] === 'broken') : ?><span class="badge badge-error badge-xs">Broken</span>
                    <?php else : ?><span class="badge badge-ghost badge-xs">Off</span><?php endif ?>
                </div>
                <div class="flex gap-1">
                    <?php if ($eq['status'] === 'broken') : ?>
                        <form action="/equipment/repair/<?= $eq['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-warning btn-xs w-full"><i class="fa-solid fa-wrench mr-1"></i>Repair</button></form>
                    <?php else : ?>
                        <form action="/equipment/toggle/<?= $eq['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-<?= $eq['status'] === 'active' ? 'ghost' : 'success' ?> btn-xs w-full"><i class="fa-solid fa-power-off mr-1"></i><?= $eq['status'] === 'active' ? 'Off' : 'On' ?></button></form>
                    <?php endif ?>
                    <form action="/equipment/sell/<?= $eq['id'] ?>" method="post" onsubmit="return confirm('Sell <?= esc($eq['name']) ?>?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error" aria-label="Sell"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></button></form>
                </div>
            </div></div>
        <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

    <!-- Shop: Groomers -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-truck-monster mr-1 text-success"></i>Snow Groomers</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-8">
    <?php foreach ($groomers as $key => $g) : ?>
        <form action="/equipment/buy" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="model" value="<?= $key ?>">
            <input type="hidden" name="type" value="groomer">
            <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left" onclick="return confirm('Buy <?= $g['name'] ?> for <?= currency($g['cost']) ?>?')">
                <div class="card-body p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-success/20 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-truck-monster text-xl text-success"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold"><?= $g['name'] ?></div>
                            <div class="text-xs text-base-content/60"><?= $g['brand'] ?></div>
                            <div class="text-xs text-base-content/50 mt-0.5"><?= $g['desc'] ?></div>
                            <div class="flex gap-3 mt-1 text-xs">
                                <span><i class="fa-solid fa-gauge-high mr-1"></i><?= $g['capacity'] ?> slopes</span>
                                <span><i class="fa-solid fa-gas-pump mr-1"></i><?= currency($g['fuel']) ?>/day</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-bold text-primary"><?= currency($g['cost']) ?></div>
                        </div>
                    </div>
                </div>
            </button>
        </form>
    <?php endforeach ?>
    </div>

    <!-- Shop: Snowmakers -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-snowflake mr-1 text-info"></i>Snowmaking Machines</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <?php foreach ($snowmakers as $key => $s) : ?>
        <form action="/equipment/buy" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="model" value="<?= $key ?>">
            <input type="hidden" name="type" value="snowmaker">
            <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left" onclick="return confirm('Buy <?= $s['name'] ?> for <?= currency($s['cost']) ?>?')">
                <div class="card-body p-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-info/20 flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-snowflake text-xl text-info"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold"><?= $s['name'] ?></div>
                            <div class="text-xs text-base-content/60"><?= $s['brand'] ?></div>
                            <div class="text-xs text-base-content/50 mt-0.5"><?= $s['desc'] ?></div>
                            <div class="flex gap-3 mt-1 text-xs">
                                <span><i class="fa-solid fa-snowflake mr-1"></i><?= snow($s['capacity']) ?>/day</span>
                                <span><i class="fa-solid fa-bolt mr-1"></i><?= currency($s['fuel']) ?>/day</span>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="font-bold text-primary"><?= currency($s['cost']) ?></div>
                        </div>
                    </div>
                </div>
            </button>
        </form>
    <?php endforeach ?>
    </div>

</div>
<?= $this->endSection() ?>
