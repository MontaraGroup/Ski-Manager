<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Parking & Transit<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-square-parking mr-2 text-info"></i>Parking & Transit</h1>
            <p class="text-sm text-base-content/50">Build lots and garages to handle visitor traffic</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Stats -->
    <?php $occupancyPct = $totalCapacity > 0 ? round($totalOccupied / $totalCapacity * 100) : 0; ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-primary"><?= number_format($totalCapacity) ?></div>
            <div class="text-xs text-base-content/50">Total Spots</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold <?= $occupancyPct > 90 ? 'text-error' : ($occupancyPct > 70 ? 'text-warning' : 'text-success') ?>"><?= $occupancyPct ?>%</div>
            <div class="text-xs text-base-content/50">Occupied</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success"><?= currency($totalRevenue) ?></div>
            <div class="text-xs text-base-content/50">Daily Revenue</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($facilities) ?></div>
            <div class="text-xs text-base-content/50">Facilities</div>
        </div></div>
    </div>

    <?php if ($occupancyPct > 90 && count($facilities) > 0) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span>Parking is nearly full! Visitors are being turned away. Build more capacity.</span></div>
    <?php endif ?>

    <!-- Existing Facilities -->
    <?php if (!empty($facilities)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-list mr-1"></i>Your Facilities</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <?php foreach ($facilities as $f) : ?>
            <?php $fOccPct = $f['capacity'] > 0 ? round($f['occupied'] / $f['capacity'] * 100) : 0; ?>
            <div class="card bg-base-100 shadow-sm <?= $f['status'] === 'under_construction' ? 'border border-warning/30' : '' ?>"><div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center">
                            <i class="fa-solid <?= \App\Models\ParkingModel::getIcon($f['parking_type']) ?> text-info text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold"><?= esc($f['name']) ?></div>
                            <div class="text-xs text-base-content/50"><?= \App\Models\ParkingModel::getLabel($f['parking_type']) ?> · <?= number_format($f['capacity']) ?> spots</div>
                        </div>
                    </div>
                    <span class="badge badge-sm <?= $f['status'] === 'open' ? 'badge-success' : ($f['status'] === 'under_construction' ? 'badge-warning' : ($f['status'] === 'full' ? 'badge-error' : 'badge-ghost')) ?>">
                        <?= $f['status'] === 'under_construction' ? 'Building (' . $f['build_days_left'] . 'd)' : ucfirst($f['status']) ?>
                    </span>
                </div>
                <?php if ($f['status'] !== 'under_construction') : ?>
                <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                    <div class="bg-base-200 rounded p-2 text-center"><div class="font-bold text-base"><?= number_format($f['occupied']) ?>/<?= number_format($f['capacity']) ?></div><div class="text-base-content/50">Occupied (<?= $fOccPct ?>%)</div></div>
                    <div class="bg-base-200 rounded p-2 text-center"><div class="font-bold text-base text-success"><?= currency($f['daily_revenue']) ?></div><div class="text-base-content/50">Revenue</div></div>
                </div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-xs text-base-content/50 w-16">Occupancy</span>
                    <progress class="progress progress-info flex-1 h-1.5" value="<?= $f['occupied'] ?>" max="<?= max(1, $f['capacity']) ?>"></progress>
                </div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-xs text-base-content/50 w-16">Condition</span>
                    <progress class="progress <?= $f['condition_pct'] >= 60 ? 'progress-success' : ($f['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $f['condition_pct'] ?>" max="100"></progress>
                    <span class="text-xs font-mono w-8 text-right"><?= (int)$f['condition_pct'] ?>%</span>
                </div>
                <form action="/parking/update-fee/<?= $f['id'] ?>" method="post" class="flex items-center gap-2 mb-2">
                    <?= csrf_field() ?>
                    <span class="text-xs text-base-content/50">Fee:</span>
                    <input type="number" name="fee" value="<?= $f['fee_per_day'] ?>" min="0" max="100" step="1" class="input input-bordered input-xs w-20">
                    <span class="text-xs text-base-content/50">/day</span>
                    <button type="submit" class="btn btn-ghost btn-xs"><i class="fa-solid fa-check text-success"></i></button>
                </form>
                <div class="flex gap-1">
                    <form action="/parking/toggle/<?= $f['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                        <button class="btn btn-xs w-full <?= $f['status'] === 'open' || $f['status'] === 'full' ? 'btn-ghost' : 'btn-success' ?> gap-1">
                            <i class="fa-solid <?= $f['status'] === 'open' || $f['status'] === 'full' ? 'fa-pause' : 'fa-play' ?>"></i>
                            <?= $f['status'] === 'open' || $f['status'] === 'full' ? 'Close' : 'Open' ?>
                        </button>
                    </form>
                    <?php if ($f['condition_pct'] < 100) : ?>
                    <form action="/parking/repair/<?= $f['id'] ?>" method="post" data-confirm="Repair this facility?"><?= csrf_field() ?>
                        <button class="btn btn-xs btn-outline btn-info"><i class="fa-solid fa-wrench"></i></button>
                    </form>
                    <?php endif ?>
                    <form action="/parking/demolish/<?= $f['id'] ?>" method="post" data-confirm="Demolish <?= esc($f['name']) ?>? You'll get 20% refund."><?= csrf_field() ?>
                        <button class="btn btn-xs btn-ghost text-error"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
                <?php endif ?>
            </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Build New -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-hammer mr-1"></i>Build New Facility</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <?php foreach ($parkingConfig as $key => $cfg) : ?>
            <div class="card bg-base-100 shadow-sm hover:shadow-lg transition-all group">
                <div class="bg-gradient-to-br from-info/5 to-info/15 p-5 text-center rounded-t-2xl">
                    <i class="fa-solid <?= $cfg['icon'] ?> text-4xl text-info/40 group-hover:text-info/60 transition-colors"></i>
                </div>
                <div class="card-body p-4">
                    <h3 class="font-bold text-sm"><?= $cfg['label'] ?></h3>
                    <div class="grid grid-cols-2 gap-1 text-xs mt-2 mb-3">
                        <div class="bg-base-200 rounded p-1.5 text-center"><div class="font-bold"><?= $cfg['capacity'] ?></div><div class="text-base-content/50">Spots</div></div>
                        <div class="bg-base-200 rounded p-1.5 text-center"><div class="font-bold"><?= currency($cfg['upkeep']) ?></div><div class="text-base-content/50">Per Day</div></div>
                    </div>
                    <div class="flex items-center justify-between border-t border-base-300 pt-3">
                        <div>
                            <div class="text-xs text-base-content/40 line-through"><?= currency((int)($cfg['cost'] * 1.2)) ?></div>
                            <div class="text-lg font-bold text-info"><?= currency($cfg['cost']) ?></div>
                        </div>
                        <form action="/parking/build" method="post" data-confirm="Build <?= $cfg['label'] ?> for <?= currency($cfg['cost']) ?>?"><?= csrf_field() ?>
                            <input type="hidden" name="parking_type" value="<?= $key ?>">
                            <button class="btn btn-info btn-sm gap-1"><i class="fa-solid fa-cart-plus"></i> Build</button>
                        </form>
                    </div>
                    <div class="text-xs text-base-content/40 mt-1"><?= $cfg['build_days'] ?> day<?= $cfg['build_days'] > 1 ? 's' : '' ?> to build</div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <?php if (empty($facilities)) : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
        <i class="fa-solid fa-square-parking text-4xl text-base-content/20 mb-3"></i>
        <p class="font-semibold">No parking facilities yet</p>
        <p class="text-sm text-base-content/50 mt-1">Without parking, your visitor capacity is limited. Build your first lot above!</p>
    </div></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
