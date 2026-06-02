<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-6xl">
    <h1 class="text-3xl font-bold mb-6"><i class="fa-solid fa-square-parking mr-2"></i> Parking & Transit</h1>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success mb-4"><i class="fa-solid fa-check-circle mr-2"></i><?= session()->getFlashdata('success') ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-error mb-4"><i class="fa-solid fa-circle-exclamation mr-2"></i><?= session()->getFlashdata('error') ?></div>
    <?php endif ?>

    <!-- Overview Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Total Capacity</div>
            <div class="text-xl font-bold text-primary"><?= number_format($totalCapacity) ?></div>
            <div class="text-xs text-base-content/50">parking spots</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Occupied Today</div>
            <div class="text-xl font-bold"><?= number_format($totalOccupied) ?></div>
            <div class="text-xs text-base-content/50"><?= $totalCapacity > 0 ? round($totalOccupied / $totalCapacity * 100) : 0 ?>% full</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Daily Revenue</div>
            <div class="text-xl font-bold text-success"><?= currency($totalRevenue) ?></div>
            <div class="text-xs text-base-content/50">from parking fees</div></div></div>
    </div>

    <!-- Build New -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body">
            <h2 class="card-title"><i class="fa-solid fa-hammer mr-2"></i> Build New Facility</h2>
            <form action="/parking/build" method="post">
                <?= csrf_field() ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                    <div class="form-control">
                        <label class="label"><span class="label-text">Name (optional)</span></label>
                        <input type="text" name="name" class="input input-bordered input-sm" placeholder="e.g. Main Lot" maxlength="100">
                    </div>
                    <div class="form-control">
                        <label class="label"><span class="label-text">Type</span></label>
                        <select name="parking_type" id="parkingTypeSelect" class="select select-bordered select-sm" required>
                            <?php foreach ($parkingConfig as $key => $cfg) : ?>
                                <option value="<?= $key ?>"><?= $cfg['label'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-control justify-end">
                        <div id="parkingCostPreview" class="text-sm text-base-content/70 mb-2"></div>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-hammer mr-1"></i> Build</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Type Reference Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php foreach ($parkingConfig as $key => $cfg) : ?>
            <div class="card bg-base-200/50 border border-base-300">
                <div class="card-body p-4">
                    <h3 class="font-semibold"><i class="fa-solid <?= $cfg['icon'] ?> mr-1"></i> <?= $cfg['label'] ?></h3>
                    <div class="text-sm space-y-1 mt-1">
                        <div>Cost: <strong><?= currency($cfg['cost']) ?></strong></div>
                        <div>Capacity: <strong><?= $cfg['capacity'] ?> spots</strong></div>
                        <div>Upkeep: <strong><?= currency($cfg['upkeep']) ?>/day</strong></div>
                        <div>Default fee: <strong><?= currency($cfg['default_fee']) ?></strong></div>
                        <div>Build time: <strong><?= $cfg['build_days'] ?> day<?= $cfg['build_days'] > 1 ? 's' : '' ?></strong></div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <!-- Existing Facilities -->
    <?php if (empty($facilities)) : ?>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body text-center py-12">
                <i class="fa-solid fa-square-parking text-4xl text-base-content/30 mb-3"></i>
                <p class="text-base-content/60">No parking facilities yet. Without parking, your visitor capacity is limited!</p>
            </div>
        </div>
    <?php else : ?>
        <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-list mr-2"></i> Your Facilities</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($facilities as $f) : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title text-lg">
                                <i class="fa-solid <?= \App\Models\ParkingModel::getIcon($f['parking_type']) ?> mr-1"></i>
                                <?= esc($f['name']) ?>
                            </h3>
                            <div class="badge <?= $f['status'] === 'open' ? 'badge-success' : ($f['status'] === 'under_construction' ? 'badge-warning' : ($f['status'] === 'full' ? 'badge-error' : 'badge-ghost')) ?>">
                                <?= $f['status'] === 'under_construction' ? 'Building (' . $f['build_days_left'] . 'd)' : ucfirst($f['status']) ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                            <div><span class="text-base-content/60">Type:</span> <?= \App\Models\ParkingModel::getLabel($f['parking_type']) ?></div>
                            <div><span class="text-base-content/60">Capacity:</span> <?= number_format($f['capacity']) ?> spots</div>
                            <div><span class="text-base-content/60">Occupied:</span> <?= number_format($f['occupied']) ?> (<?= $f['capacity'] > 0 ? round($f['occupied'] / $f['capacity'] * 100) : 0 ?>%)</div>
                            <div><span class="text-base-content/60">Today's revenue:</span> <?= currency($f['daily_revenue']) ?></div>
                        </div>
                        <div class="mt-2">
                            <div class="flex items-center justify-between text-sm mb-1"><span>Occupancy</span><span><?= $f['capacity'] > 0 ? round($f['occupied'] / $f['capacity'] * 100) : 0 ?>%</span></div>
                            <progress class="progress progress-info w-full" value="<?= $f['occupied'] ?>" max="<?= $f['capacity'] ?>"></progress>
                        </div>
                        <div class="mt-1">
                            <div class="flex items-center justify-between text-sm mb-1"><span>Condition</span><span><?= number_format($f['condition_pct'], 0) ?>%</span></div>
                            <progress class="progress <?= $f['condition_pct'] >= 60 ? 'progress-success' : ($f['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-full" value="<?= $f['condition_pct'] ?>" max="100"></progress>
                        </div>
                        <?php if ($f['status'] !== 'under_construction') : ?>
                            <form action="/parking/update-fee/<?= $f['id'] ?>" method="post" class="flex items-end gap-2 mt-3">
                                <?= csrf_field() ?>
                                <div class="form-control flex-1">
                                    <label class="label"><span class="label-text text-xs">Daily fee per car</span></label>
                                    <input type="number" name="fee" value="<?= $f['fee_per_day'] ?>" min="0" max="100" step="0.50" class="input input-bordered input-sm">
                                </div>
                                <button type="submit" class="btn btn-sm btn-ghost"><i class="fa-solid fa-check"></i></button>
                            </form>
                            <div class="card-actions justify-end mt-3">
                                <form action="/parking/toggle/<?= $f['id'] ?>" method="post" class="inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm <?= $f['status'] === 'open' || $f['status'] === 'full' ? 'btn-warning' : 'btn-success' ?>"><i class="fa-solid <?= $f['status'] === 'open' || $f['status'] === 'full' ? 'fa-pause' : 'fa-play' ?> mr-1"></i> <?= $f['status'] === 'open' || $f['status'] === 'full' ? 'Close' : 'Open' ?></button></form>
                                <?php if ($f['condition_pct'] < 100) : ?>
                                    <form action="/parking/repair/<?= $f['id'] ?>" method="post" class="inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-info"><i class="fa-solid fa-wrench mr-1"></i> Repair</button></form>
                                <?php endif ?>
                                <form action="/parking/demolish/<?= $f['id'] ?>" method="post" class="inline" onsubmit="return confirm('Demolish this facility? You\'ll get 20% refund.')"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-error btn-outline"><i class="fa-solid fa-trash mr-1"></i> Demolish</button></form>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script>
const parkingConfig = <?= json_encode($parkingConfig) ?>;
const select = document.getElementById('parkingTypeSelect');
const preview = document.getElementById('parkingCostPreview');
function updatePreview() {
    const c = parkingConfig[select.value];
    if (c) preview.innerHTML = 'Cost: <strong>$'+c.cost.toLocaleString()+'</strong> · '+c.capacity+' spots · '+c.build_days+' day build · $'+c.upkeep.toLocaleString()+'/day upkeep';
}
select.addEventListener('change', updatePreview);
updatePreview();
</script>
<?= $this->endSection() ?>
