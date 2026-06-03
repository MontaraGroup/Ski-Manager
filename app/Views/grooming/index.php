<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Grooming Operations<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-tractor mr-2 text-success"></i>Grooming Operations</h1>
                <p class="text-sm text-base-content/50">Monitor slope conditions, assign crews, and manage grooming machines</p>
            </div>
        </div>
        <form action="/grooming/groom-all" method="post"><<?= csrf_field() ?>
            <button class="btn btn-success btn-sm gap-1" <?= count($slopes) === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-tractor"></i> Groom Now</button>
        </form>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Overall Status -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex items-center gap-6">
            <div class="radial-progress text-<?= $overallCondition >= 70 ? 'success' : ($overallCondition >= 40 ? 'warning' : 'error') ?>" style="--value:<?= $overallCondition ?>;--size:5rem;--thickness:4px;" role="progressbar">
                <span class="text-lg font-bold"><?= $overallCondition ?>%</span>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-bold">Overall Slope Condition</h2>
                <p class="text-sm text-base-content/60"><?= $overallCondition >= 80 ? 'Excellent conditions — visitors love it.' : ($overallCondition >= 50 ? 'Acceptable but deteriorating. Schedule grooming soon.' : 'Poor conditions — visitors are complaining. Groom immediately.') ?></p>
            </div>
        </div>
    </div></div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold"><?= $slopeCount ?></div>
            <div class="text-xs text-base-content/50">Slopes</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold"><?= count($groomers) ?></div>
            <div class="text-xs text-base-content/50">Crew</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold <?= $totalAssigned >= $totalNeeded ? 'text-success' : 'text-warning' ?>"><?= $totalAssigned ?>/<?= $totalNeeded ?></div>
            <div class="text-xs text-base-content/50">Assigned</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold text-success"><?= $activeEquipment ?></div>
            <div class="text-xs text-base-content/50">Machines On</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold <?= count($criticalSlopes) > 0 ? 'text-error' : 'text-success' ?>"><?= count($criticalSlopes) ?></div>
            <div class="text-xs text-base-content/50">Critical</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold <?= $brokenEquipment > 0 ? 'text-error' : 'text-success' ?>"><?= $brokenEquipment ?></div>
            <div class="text-xs text-base-content/50">Broken</div>
        </div></div>
    </div>

    <?php if (empty($groomers)) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-user-slash"></i><span>No groomer operators hired. <a href="/staff/hire" class="link font-semibold">Hire groomers</a> to maintain your slopes.</span></div>
    <?php endif ?>
    <?php if (!empty($criticalSlopes)) : ?>
        <div class="alert alert-error mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span><strong><?= count($criticalSlopes) ?> slope(s) in critical condition!</strong> They will close at 0%. Hit "Groom Now" or assign more crew.</span></div>
    <?php endif ?>

    <!-- Slope Condition Map -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-mountain mr-1"></i> Slope Conditions</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($slopes as $slope) : ?>
            <?php $cond = (int) $slope['condition_pct']; ?>
            <div class="card bg-base-100 shadow-sm <?= $cond < 30 ? '' : ($cond < 60 ? '' : '') ?>">
                <div class="card-body p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-semibold text-sm truncate"><?= esc($slope['name']) ?></span>
                        <span class="badge badge-xs <?= $slope['status'] === 'open' ? 'badge-success' : 'badge-error' ?>"><?= ucfirst($slope['status']) ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <progress class="progress <?= $cond >= 70 ? 'progress-success' : ($cond >= 40 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $cond ?>" max="100"></progress>
                        <span class="text-xs font-mono font-bold <?= $cond >= 70 ? 'text-success' : ($cond >= 40 ? 'text-warning' : 'text-error') ?>"><?= $cond ?>%</span>
                    </div>
                    <div class="text-xs text-base-content/50 mt-1">Sector <?= $slope['sector'] ?> · <?= ucwords(str_replace('_', ' ', $slope['subtype'] ?? 'standard')) ?></div>
                </div>
            </div>
        <?php endforeach ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Sector Coverage -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-map-pin mr-1"></i> Sector Coverage</h2>
            <?php if (!empty($sectors)) : ?>
            <div class="space-y-2">
            <?php foreach ($sectors as $sectorNum => $sector) : ?>
                <?php $covered = $sector['groomers_assigned'] >= $sector['groomers_needed']; ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg <?= $covered ? 'bg-success/10' : 'bg-error/10' ?> flex items-center justify-center">
                                <i class="fa-solid fa-<?= $covered ? 'circle-check text-success' : 'circle-xmark text-error' ?>"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-sm">Sector <?= $sectorNum ?></span>
                                <div class="text-xs text-base-content/50"><?= count($sector['slopes']) ?> slopes · Avg <?= $sector['avg_condition'] ?>%</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-mono text-sm <?= $covered ? 'text-success' : 'text-error' ?>"><?= $sector['groomers_assigned'] ?>/<?= $sector['groomers_needed'] ?></div>
                            <div class="text-xs text-base-content/50">crew</div>
                        </div>
                    </div>
                </div></div>
            <?php endforeach ?>
            </div>
            <?php else : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-6 text-sm text-base-content/40"><a href="/map" class="link link-primary">Build slopes</a> to create sectors</div></div>
            <?php endif ?>
        </div>

        <!-- Crew Assignment -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-user-gear mr-1"></i> Crew Assignment</h2>
            <?php if (empty($groomers)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-6">
                    <i class="fa-solid fa-tractor text-3xl text-base-content/20 mb-2"></i>
                    <p class="text-sm text-base-content/50"><a href="/staff/hire" class="link link-primary">Hire groomer operators</a></p>
                </div></div>
            <?php else : ?>
            <div class="space-y-2">
            <?php foreach ($groomers as $g) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                            <i class="fa-solid fa-user text-success text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold truncate"><?= esc($g['name']) ?></div>
                            <div class="flex items-center gap-2 text-xs text-base-content/50">
                                <span>Morale <?= $g['morale'] ?>%</span>
                                <span>· Lv.<?= $g['level'] ?></span>
                            </div>
                        </div>
                    </div>
                    <form action="/grooming/assign" method="post" class="flex gap-2"><?= csrf_field() ?>
                        <input type="hidden" name="groomer_id" value="<?= $g['id'] ?>">
                        <select name="sector" class="select select-bordered select-xs flex-1">
                            <option value="" <?= !$g['assigned_to'] ? 'selected' : '' ?>>— Unassigned —</option>
                            <?php foreach ($sectors as $sNum => $sec) : ?>
                            <option value="<?= $sNum ?>" <?= $g['assigned_to'] === 'sector_' . $sNum ? 'selected' : '' ?>>Sector <?= $sNum ?> (<?= count($sec['slopes']) ?> slopes, <?= $sec['avg_condition'] ?>%)</option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-check"></i></button>
                    </form>
                </div></div>
            <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Equipment Status -->
    <?php if (!empty($equipment)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-truck-monster mr-1 text-warning"></i> Grooming Machines</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($equipment as $eq) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
            <div class="flex items-center justify-between mb-1">
                <span class="font-semibold text-sm"><?= esc($eq['name']) ?></span>
                <span class="badge badge-xs <?= $eq['status'] === 'active' ? 'badge-success' : ($eq['status'] === 'broken' ? 'badge-error' : 'badge-ghost') ?>"><?= ucfirst($eq['status']) ?></span>
            </div>
            <div class="text-xs text-base-content/50"><?= $eq['brand'] ?> · Capacity: <?= $eq['capacity'] ?> slopes</div>
            <div class="flex items-center gap-2 mt-1">
                <progress class="progress <?= $eq['condition_pct'] >= 60 ? 'progress-success' : ($eq['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $eq['condition_pct'] ?>" max="100"></progress>
                <span class="text-xs font-mono"><?= $eq['condition_pct'] ?>%</span>
            </div>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="flex gap-3">
        <a href="/equipment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-toolbox"></i> Equipment Shop</a>
        <a href="/staff/hire" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-user-plus"></i> Hire Crew</a>
    </div>
</div>
<?= $this->endSection() ?>
