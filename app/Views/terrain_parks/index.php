<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Terrain Parks<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-person-snowboarding mr-2 text-warning"></i>Terrain Parks</h1>
                <p class="text-sm text-base-content/50">Build halfpipes, jump lines, rail gardens, and more</p>
            </div>
        </div>
        <a href="/staff/hire" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-user-plus"></i> Hire Crew</a>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Stats -->
    <?php
        $openParks = array_filter($parks, fn($p) => $p['status'] === 'open');
        $buildingParks = array_filter($parks, fn($p) => $p['status'] === 'under_construction');
        $totalVisitors = array_sum(array_column($parks, 'daily_visitors'));
        $avgCondition = count($parks) > 0 ? round(array_sum(array_column($parks, 'condition_pct')) / count($parks)) : 0;
    ?>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success"><?= count($openParks) ?></div>
            <div class="text-xs text-base-content/50">Open</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-warning"><?= count($buildingParks) ?></div>
            <div class="text-xs text-base-content/50">Building</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-info"><?= $totalVisitors ?></div>
            <div class="text-xs text-base-content/50">Daily Riders</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($parkCrew) ?></div>
            <div class="text-xs text-base-content/50">Crew</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold <?= $avgCondition >= 70 ? 'text-success' : ($avgCondition >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgCondition ?>%</div>
            <div class="text-xs text-base-content/50">Avg Condition</div>
        </div></div>
    </div>

    <!-- Park Crew -->
    <?php if (!empty($parkCrew)) : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-users-gear mr-1"></i>Park Crew</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
        <?php foreach ($parkCrew as $crew) : ?>
            <div class="flex items-center gap-2 bg-base-200 rounded-lg p-2">
                <div class="w-8 h-8 rounded-full bg-warning/10 flex items-center justify-center">
                    <i class="fa-solid fa-user text-warning text-xs"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold truncate"><?= esc($crew['name']) ?></div>
                    <div class="text-xs text-base-content/50">
                        <?php for ($i = 0; $i < min($crew['level'], 5); $i++) : ?><i class="fa-solid fa-star text-warning text-[8px]"></i><?php endfor ?>
                        · <?= $crew['morale'] ?>% · <?= currency($crew['salary']) ?>/day
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    </div></div>
    <?php else : ?>
    <div class="alert alert-warning mb-6"><i class="fa-solid fa-user-slash"></i><span>No park crew. <a href="/staff/hire" class="link font-semibold">Hire park crew</a> to maintain features and slow condition decay.</span></div>
    <?php endif ?>

    <!-- Existing Parks -->
    <?php if (!empty($parks)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-person-snowboarding mr-1"></i>Your Features</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <?php foreach ($parks as $park) : ?>
            <?php $cond = (int) $park['condition_pct']; ?>
            <div class="card bg-base-100 shadow-sm <?= $park['status'] === 'under_construction' ? 'border border-warning/30' : '' ?> <?= $cond < 30 ? 'border border-error/30' : '' ?>"><div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center">
                            <i class="fa-solid <?= \App\Models\TerrainParkModel::getIcon($park['park_type']) ?> text-warning text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold"><?= esc($park['name']) ?></div>
                            <div class="text-xs text-base-content/50"><?= \App\Models\TerrainParkModel::getLabel($park['park_type']) ?> · <?= ucfirst($park['size']) ?></div>
                        </div>
                    </div>
                    <span class="badge badge-sm <?= $park['status'] === 'open' ? 'badge-success' : ($park['status'] === 'under_construction' ? 'badge-warning' : 'badge-ghost') ?>">
                        <?= $park['status'] === 'under_construction' ? 'Building (' . $park['build_days_left'] . 'd)' : ucfirst($park['status']) ?>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                    <div class="bg-base-200 rounded p-2 text-center"><div class="font-bold text-base"><?= $park['daily_visitors'] ?></div><div class="text-base-content/50">Riders/day</div></div>
                    <div class="bg-base-200 rounded p-2 text-center"><div class="font-bold text-base"><?= $park['popularity'] ?></div><div class="text-base-content/50">Popularity</div></div>
                </div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs text-base-content/50 w-16">Condition</span>
                    <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $cond ?>" max="100"></progress>
                    <span class="text-xs font-mono w-8 text-right"><?= $cond ?>%</span>
                </div>
                <?php if ($park['status'] !== 'under_construction') : ?>
                <div class="flex gap-1">
                    <form action="/terrain-parks/toggle/<?= $park['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                        <button class="btn btn-xs w-full <?= $park['status'] === 'open' ? 'btn-ghost' : 'btn-success' ?> gap-1">
                            <i class="fa-solid <?= $park['status'] === 'open' ? 'fa-pause' : 'fa-play' ?>"></i> <?= $park['status'] === 'open' ? 'Close' : 'Open' ?>
                        </button>
                    </form>
                    <?php if ($cond < 100) : ?>
                    <form action="/terrain-parks/repair/<?= $park['id'] ?>" method="post" data-confirm="Repair this feature?"><?= csrf_field() ?>
                        <button class="btn btn-xs btn-outline btn-info gap-1"><i class="fa-solid fa-wrench"></i></button>
                    </form>
                    <?php endif ?>
                    <form action="/terrain-parks/demolish/<?= $park['id'] ?>" method="post" data-confirm="Demolish <?= esc($park['name']) ?>? You'll get 25% refund."><?= csrf_field() ?>
                        <button class="btn btn-xs btn-ghost text-error"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </div>
                <?php endif ?>
            </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Build New -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-hammer mr-1"></i>Build New Feature</h2>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <form action="/terrain-parks/build" method="post">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="label py-0"><span class="label-text text-xs">Name (optional)</span></label>
                    <input type="text" name="name" class="input input-bordered input-sm w-full" placeholder="e.g. Stunt Zone" maxlength="100">
                </div>
                <div>
                    <label class="label py-0"><span class="label-text text-xs">Type</span></label>
                    <select name="park_type" id="parkTypeSelect" class="select select-bordered select-sm w-full" required>
                        <?php foreach ($parkConfig as $key => $cfg) : ?>
                            <option value="<?= $key ?>"><?= $cfg['label'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div>
                    <label class="label py-0"><span class="label-text text-xs">Size</span></label>
                    <select name="size" id="parkSizeSelect" class="select select-bordered select-sm w-full" required>
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
                <div>
                    <label class="label py-0"><span class="label-text text-xs">On Slope</span></label>
                    <select name="slope_id" class="select select-bordered select-sm w-full">
                        <option value="">Standalone</option>
                        <?php foreach ($slopes as $slope) : ?>
                            <option value="<?= $slope['id'] ?>"><?= esc($slope['name'] ?? 'Slope #' . $slope['id']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
            <div id="parkCostPreview" class="text-sm text-base-content/60 mb-3 bg-base-200 rounded-lg p-2"></div>
            <button type="submit" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-hammer"></i> Build</button>
        </form>
    </div></div>

    <?php if (empty($parks)) : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
        <i class="fa-solid fa-person-snowboarding text-4xl text-base-content/20 mb-3"></i>
        <p class="font-semibold">No terrain park features yet</p>
        <p class="text-sm text-base-content/50 mt-1">Build your first halfpipe, jump line, or rail garden above!</p>
    </div></div>
    <?php endif ?>
</div>

<script>
var parkConfig = <?= json_encode($parkConfig) ?>;
var typeSelect = document.getElementById('parkTypeSelect');
var sizeSelect = document.getElementById('parkSizeSelect');
var preview = document.getElementById('parkCostPreview');
function updatePreview() {
    var type = typeSelect.value, size = sizeSelect.value;
    if (parkConfig[type] && parkConfig[type].sizes[size]) {
        var c = parkConfig[type].sizes[size];
        preview.innerHTML = '<div class="flex gap-4 text-xs"><span><i class="fa-solid fa-coins mr-1 text-warning"></i>Cost: <strong>$'+c.cost.toLocaleString()+'</strong></span><span><i class="fa-solid fa-clock mr-1 text-info"></i>Build: <strong>'+c.build_days+' days</strong></span><span><i class="fa-solid fa-gas-pump mr-1 text-error"></i>Upkeep: <strong>$'+c.upkeep.toLocaleString()+'/day</strong></span><span><i class="fa-solid fa-people-group mr-1 text-success"></i>Capacity: <strong>'+c.capacity+' riders/day</strong></span></div>';
    }
}
typeSelect.addEventListener('change', updatePreview);
sizeSelect.addEventListener('change', updatePreview);
updatePreview();
</script>
<?= $this->endSection() ?>
