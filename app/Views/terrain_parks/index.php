<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-6xl">
    <h1 class="text-3xl font-bold mb-6"><i class="fa-solid fa-mountain-sun mr-2"></i> Terrain Parks</h1>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success mb-4"><i class="fa-solid fa-check-circle mr-2"></i><?= session()->getFlashdata('success') ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-error mb-4"><i class="fa-solid fa-circle-exclamation mr-2"></i><?= session()->getFlashdata('error') ?></div>
    <?php endif ?>

    <!-- Park Crew -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h2 class="card-title"><i class="fa-solid fa-users-gear mr-2"></i> Park Crew</h2>
                <form action="/terrain-parks/hire-crew" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-user-plus mr-1"></i> Hire Park Crew (<?= currency(2000) ?>)</button>
                </form>
            </div>
            <?php if (empty($parkCrew)) : ?>
                <p class="text-base-content/60 mt-2">No park crew hired yet. Park features need dedicated crew to maintain - without them, condition decays faster.</p>
            <?php else : ?>
                <div class="overflow-x-auto mt-2">
                    <table class="table table-sm">
                        <thead><tr><th>Name</th><th>Skill</th><th>Morale</th><th>Salary</th></tr></thead>
                        <tbody>
                            <?php foreach ($parkCrew as $crew) : ?>
                                <tr>
                                    <td><?= esc($crew['name']) ?></td>
                                    <td><?php for ($i = 0; $i < $crew['level']; $i++) : ?><i class="fa-solid fa-star text-warning text-xs"></i><?php endfor ?></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <progress class="progress <?= $crew['morale'] >= 60 ? 'progress-success' : ($crew['morale'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $crew['morale'] ?>" max="100"></progress>
                                            <span class="text-xs"><?= $crew['morale'] ?>%</span>
                                        </div>
                                    </td>
                                    <td><?= currency($crew['salary']) ?>/day</td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Build New -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body">
            <h2 class="card-title"><i class="fa-solid fa-hammer mr-2"></i> Build New Feature</h2>
            <form action="/terrain-parks/build" method="post" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-3">
                <?= csrf_field() ?>
                <div class="form-control">
                    <label class="label" for="name"><span class="label-text">Name (optional)</span></label>
                    <input type="text" name="name" id="name" class="input input-bordered input-sm" placeholder="e.g. Stunt Zone" maxlength="100">
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Type</span></label>
                    <select name="park_type" id="parkTypeSelect" class="select select-bordered select-sm" required>
                        <?php foreach ($parkConfig as $key => $cfg) : ?>
                            <option value="<?= $key ?>"><?= $cfg['label'] ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-control">
                    <label class="label"><span class="label-text">Size</span></label>
                    <select name="size" id="parkSizeSelect" class="select select-bordered select-sm" required>
                        <option value="small">Small</option>
                        <option value="medium">Medium</option>
                        <option value="large">Large</option>
                    </select>
                </div>
                <div class="form-control">
                    <label for="slope_id" class="label"><span class="label-text">On Slope (optional)</span></label>
                    <select name="slope_id" id="slope_id" class="select select-bordered select-sm">
                        <option value="">- Standalone -</option>
                        <?php foreach ($slopes as $slope) : ?>
                            <option value="<?= $slope['id'] ?>"><?= esc($slope['name'] ?? 'Slope #' . $slope['id']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-span-full">
                    <div id="parkCostPreview" class="text-sm text-base-content/70 mb-2"></div>
                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-hammer mr-1"></i> Build</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Existing Parks -->
    <?php if (empty($parks)) : ?>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body text-center py-12">
                <i class="fa-solid fa-mountain-sun text-4xl text-base-content/30 mb-3"></i>
                <p class="text-base-content/60">No terrain park features built yet. Build your first one above!</p>
            </div>
        </div>
    <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($parks as $park) : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title text-lg">
                                <i class="fa-solid <?= \App\Models\TerrainParkModel::getIcon($park['park_type']) ?> mr-1"></i>
                                <?= esc($park['name']) ?>
                            </h3>
                            <div class="badge <?= $park['status'] === 'open' ? 'badge-success' : ($park['status'] === 'under_construction' ? 'badge-warning' : 'badge-ghost') ?>">
                                <?= $park['status'] === 'under_construction' ? 'Building (' . $park['build_days_left'] . 'd)' : ucfirst($park['status']) ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                            <div><span class="text-base-content/60">Type:</span> <?= \App\Models\TerrainParkModel::getLabel($park['park_type']) ?></div>
                            <div><span class="text-base-content/60">Size:</span> <?= ucfirst($park['size']) ?></div>
                            <div><span class="text-base-content/60">Popularity:</span> <?= $park['popularity'] ?></div>
                            <div><span class="text-base-content/60">Daily visitors:</span> <?= $park['daily_visitors'] ?></div>
                        </div>
                        <div class="mt-2">
                            <div class="flex items-center justify-between text-sm mb-1"><span>Condition</span><span><?= number_format($park['condition_pct'], 0) ?>%</span></div>
                            <progress class="progress <?= $park['condition_pct'] >= 60 ? 'progress-success' : ($park['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-full" value="<?= $park['condition_pct'] ?>" max="100"></progress>
                        </div>
                        <?php if ($park['status'] !== 'under_construction') : ?>
                            <div class="card-actions justify-end mt-3">
                                <form action="/terrain-parks/toggle/<?= $park['id'] ?>" method="post" class="inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm <?= $park['status'] === 'open' ? 'btn-warning' : 'btn-success' ?>"><i class="fa-solid <?= $park['status'] === 'open' ? 'fa-pause' : 'fa-play' ?> mr-1"></i> <?= $park['status'] === 'open' ? 'Close' : 'Open' ?></button></form>
                                <?php if ($park['condition_pct'] < 100) : ?>
                                    <form action="/terrain-parks/repair/<?= $park['id'] ?>" method="post" class="inline"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-info"><i class="fa-solid fa-wrench mr-1"></i> Repair</button></form>
                                <?php endif ?>
                                <form action="/terrain-parks/demolish/<?= $park['id'] ?>" method="post" class="inline" onsubmit="return confirm('Demolish this park feature? You\'ll get 25% refund.')"><?= csrf_field() ?><button type="submit" class="btn btn-sm btn-error btn-outline"><i class="fa-solid fa-trash mr-1"></i> Demolish</button></form>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>

<script>
const parkConfig = <?= json_encode($parkConfig) ?>;
const typeSelect = document.getElementById('parkTypeSelect');
const sizeSelect = document.getElementById('parkSizeSelect');
const preview = document.getElementById('parkCostPreview');
function updatePreview() {
    const type = typeSelect.value, size = sizeSelect.value;
    if (parkConfig[type] && parkConfig[type].sizes[size]) {
        const c = parkConfig[type].sizes[size];
        preview.innerHTML = 'Cost: <strong>$'+c.cost.toLocaleString()+'</strong> · Build: <strong>'+c.build_days+' days</strong> · Upkeep: <strong>$'+c.upkeep.toLocaleString()+'/day</strong> · Capacity: <strong>'+c.capacity+' riders/day</strong>';
    }
}
typeSelect.addEventListener('change', updatePreview);
sizeSelect.addEventListener('change', updatePreview);
updatePreview();
</script>
<?= $this->endSection() ?>
