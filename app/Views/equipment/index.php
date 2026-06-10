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
    <?php
        $__cash = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray()['cash'] ?? 0;
        $__totalSlopesCap = array_sum(array_map(fn($e) => $e['equipment_type'] === 'groomer' ? (int)$e['capacity'] : 0, $equipment));
        $__slopeCount = db_connect()->table('player_items')->where('user_id', auth()->id())->whereIn('item_type', ['slope','downhill','crosscountry','snowpark','luge'])->countAllResults();
        $__totalSnowOutput = array_sum(array_map(fn($e) => $e['equipment_type'] === 'snowmaker' && $e['status'] === 'active' ? (int)$e['output_per_day'] : 0, $equipment));
    ?>

    <!-- Fleet Coverage -->
    <?php if (!empty($equipment)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <div class="card bg-success/10 border border-success/30 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-solid fa-tractor text-success"></i><span class="text-sm font-semibold">Grooming Capacity</span></div>
                <span class="font-mono text-sm font-bold <?= $__totalSlopesCap >= $__slopeCount ? 'text-success' : 'text-warning' ?>"><?= $__totalSlopesCap ?>/<?= $__slopeCount ?> slopes</span>
            </div>
            <progress class="progress progress-success w-full mt-2 h-1.5" value="<?= min($__totalSlopesCap, $__slopeCount) ?>" max="<?= max(1, $__slopeCount) ?>"></progress>
        </div></div>
        <div class="card bg-info/10 border border-info/30 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-solid fa-snowflake text-info"></i><span class="text-sm font-semibold">Snow Output</span></div>
                <span class="font-mono text-sm font-bold text-info"><?= $__totalSnowOutput ?> <?= isImperial() ? 'in' : 'cm' ?>/day</span>
            </div>
            <progress class="progress progress-info w-full mt-2 h-1.5" value="<?= min($__totalSnowOutput, 20) ?>" max="20"></progress>
        </div></div>
    </div>
    <?php endif ?>

    <div class="text-right text-xs text-base-content/40 mb-2">Your balance: <span class="font-bold text-success"><?= currency($__cash) ?></span></div>

    <!-- Owned Equipment -->
    <?php if (!empty($equipment)) : ?>
    <div class="mb-8">
        <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-warehouse mr-1"></i>Your Fleet</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($equipment as $eq) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-lg <?= $eq['status'] === 'active' ? ($eq['equipment_type'] === 'groomer' ? 'bg-success/20' : 'bg-info/20') : 'bg-base-200' ?> flex items-center justify-center">
                        <i class="<?= $eq['equipment_type'] === 'groomer' ? 'icon-snowcat' : 'fa-solid fa-snowflake' ?> <?= $eq['status'] === 'active' ? ($eq['equipment_type'] === 'groomer' ? 'text-success' : 'text-info') : 'text-base-content/30' ?>"></i>
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
                        <form action="/equipment/repair/<?= $eq['id'] ?>" method="post" class="flex-1" data-confirm="Repair for <?= currency(5000) ?>?"><?= csrf_field() ?><button class="btn btn-warning btn-xs w-full gap-1"><i class="fa-solid fa-wrench"></i>Repair <?= currency(5000) ?></button></form>
                    <?php else : ?>
                        <form action="/equipment/toggle/<?= $eq['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-<?= $eq['status'] === 'active' ? 'ghost' : 'success' ?> btn-xs w-full"><i class="fa-solid fa-power-off mr-1"></i><?= $eq['status'] === 'active' ? 'Off' : 'On' ?></button></form>
                    <?php endif ?>
                    <form action="/equipment/sell/<?= $eq['id'] ?>" method="post" data-confirm="Sell <?= esc($eq['name']) ?>?"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error gap-1"><i class="fa-solid fa-coins"></i></button></form>
                </div>
            </div></div>
        <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>

    <!-- Shop -->
    <div class="flex gap-2 mb-4">
        <button class="btn btn-sm btn-primary shop-tab" data-show="all" onclick="filterShop('all')">All</button>
        <button class="btn btn-sm btn-ghost shop-tab" data-show="groomers" onclick="filterShop('groomers')"><i class="fa-solid fa-truck-monster mr-1"></i>Groomers (<?= count($groomers) ?>)</button>
        <button class="btn btn-sm btn-ghost shop-tab" data-show="snowmakers" onclick="filterShop('snowmakers')"><i class="fa-solid fa-snowflake mr-1"></i>Snowmakers (<?= count($snowmakers) ?>)</button>
    </div>

    <div id="shopGroomers">
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-truck-monster mr-1"></i>Snow Groomers</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    <?php foreach ($groomers as $key => $g) : ?>
        <?php $canBuy = $__cash >= $g['cost']; ?>
        <div class="card bg-base-100 shadow-sm hover:shadow-lg transition-all group">
            <div class="bg-gradient-to-br from-success/5 to-success/15 p-6 text-center rounded-t-2xl">
                <i class="fa-solid fa-truck-monster text-5xl text-success/40 group-hover:text-success/60 transition-colors"></i>
            </div>
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wider"><?= $g['brand'] ?></span>
                    <?php if ($key === 'pb100' || $key === 'prinoth_husky') : ?><span class="badge badge-success badge-xs">Starter</span><?php endif ?>
                    <?php if ($key === 'pb800' || $key === 'prinoth_leitwolf') : ?><span class="badge badge-warning badge-xs">Premium</span><?php endif ?>
                </div>
                <h3 class="font-bold text-lg mb-1"><?= $g['name'] ?></h3>
                <p class="text-xs text-base-content/50 mb-3 line-clamp-2"><?= $g['desc'] ?></p>
                <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                    <div class="bg-base-200 rounded-lg p-2 text-center">
                        <div class="font-bold text-base"><?= $g['capacity'] ?></div>
                        <div class="text-base-content/50">Slopes</div>
                    </div>
                    <div class="bg-base-200 rounded-lg p-2 text-center">
                        <div class="font-bold text-base"><?= currency($g['fuel']) ?></div>
                        <div class="text-base-content/50">Per Day</div>
                    </div>
                </div>
                <div class="border-t border-base-300 pt-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-base-content/40 line-through"><?= currency((int)($g['cost'] * 1.2)) ?></div>
                        <div class="text-xl font-bold text-primary"><?= currency($g['cost']) ?></div>
                    </div>
                    <form action="/equipment/buy" method="post" data-confirm="Buy <?= $g['name'] ?> for <?= currency($g['cost']) ?>?"><?= csrf_field() ?>
                        <input type="hidden" name="model" value="<?= $key ?>">
                        <input type="hidden" name="type" value="groomer">
                        <button type="submit" class="btn btn-primary btn-sm gap-1" <?= !$canBuy ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-cart-plus"></i> <?= $canBuy ? 'Add to Fleet' : 'Not enough' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach ?>
    </div>

    </div>
    <div id="shopSnowmakers">
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-snowflake mr-1 text-info"></i>Snowmaking Machines</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($snowmakers as $key => $s) : ?>
        <?php $canBuy = $__cash >= $s['cost']; ?>
        <div class="card bg-base-100 shadow-sm hover:shadow-lg transition-all group">
            <div class="bg-gradient-to-br from-info/5 to-info/15 p-6 text-center rounded-t-2xl">
                <i class="fa-solid fa-snowflake text-5xl text-info/40 group-hover:text-info/60 transition-colors"></i>
            </div>
            <div class="card-body p-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-semibold text-base-content/40 uppercase tracking-wider"><?= $s['brand'] ?></span>
                    <?php if ($s['cost'] <= 25000) : ?><span class="badge badge-success badge-xs">Budget</span><?php endif ?>
                    <?php if ($s['cost'] >= 95000 && $s['cost'] < 350000) : ?><span class="badge badge-warning badge-xs">Premium</span><?php endif ?>
                    <?php if ($s['cost'] >= 350000) : ?><span class="badge badge-error badge-xs">All-Weather</span><?php endif ?>
                </div>
                <h3 class="font-bold text-lg mb-1"><?= $s['name'] ?></h3>
                <p class="text-xs text-base-content/50 mb-3 line-clamp-2"><?= $s['desc'] ?></p>
                <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                    <div class="bg-base-200 rounded-lg p-2 text-center">
                        <div class="font-bold text-base"><?= snow($s['capacity']) ?></div>
                        <div class="text-base-content/50">Per Day</div>
                    </div>
                    <div class="bg-base-200 rounded-lg p-2 text-center">
                        <div class="font-bold text-base"><?= currency($s['fuel']) ?></div>
                        <div class="text-base-content/50">Energy</div>
                    </div>
                </div>
                <div class="border-t border-base-300 pt-3 flex items-center justify-between">
                    <div>
                        <div class="text-xs text-base-content/40 line-through"><?= currency((int)($s['cost'] * 1.2)) ?></div>
                        <div class="text-xl font-bold text-info"><?= currency($s['cost']) ?></div>
                    </div>
                    <form action="/equipment/buy" method="post" data-confirm="Buy <?= $s['name'] ?> for <?= currency($s['cost']) ?>?"><?= csrf_field() ?>
                        <input type="hidden" name="model" value="<?= $key ?>">
                        <input type="hidden" name="type" value="snowmaker">
                        <button type="submit" class="btn btn-info btn-sm gap-1" <?= !$canBuy ? 'disabled' : '' ?>>
                            <i class="fa-solid fa-cart-plus"></i> <?= $canBuy ? 'Add to Fleet' : 'Not enough' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach ?>
    </div>
    </div>

    <script>
    function filterShop(type) {
        document.querySelectorAll('.shop-tab').forEach(function(b){b.classList.remove('btn-primary');b.classList.add('btn-ghost');});
        document.querySelector('.shop-tab[data-show="'+type+'"]').classList.add('btn-primary');
        document.querySelector('.shop-tab[data-show="'+type+'"]').classList.remove('btn-ghost');
        var g = document.getElementById('shopGroomers');
        var s = document.getElementById('shopSnowmakers');
        if(type==='groomers'){g.style.display='';s.style.display='none';}
        else if(type==='snowmakers'){g.style.display='none';s.style.display='';}
        else{g.style.display='';s.style.display='';}
    }
    </script>

    <!-- Related Systems -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-6 mb-6">
        <a href="/grooming" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid fa-tractor text-success text-xl"></i>
            <div><div class="text-sm font-bold">Grooming</div><div class="text-xs text-base-content/50">Assign groomers to sectors</div></div>
            <i class="fa-solid fa-chevron-right text-base-content/30 ml-auto"></i>
        </div></a>
        <a href="/snowmaking" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid fa-snowflake text-info text-xl"></i>
            <div><div class="text-sm font-bold">Snowmaking</div><div class="text-xs text-base-content/50">Use snowmakers to produce snow</div></div>
            <i class="fa-solid fa-chevron-right text-base-content/30 ml-auto"></i>
        </div></a>
        <a href="/staff" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid fa-users text-warning text-xl"></i>
            <div><div class="text-sm font-bold">Staff</div><div class="text-xs text-base-content/50">Hire operators for your machines</div></div>
            <i class="fa-solid fa-chevron-right text-base-content/30 ml-auto"></i>
        </div></a>
    </div>
</div>
<?= $this->endSection() ?>
