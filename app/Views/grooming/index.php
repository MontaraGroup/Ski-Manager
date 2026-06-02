<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Grooming<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-tractor mr-2 text-success"></i>Slope Grooming</h1>
            <p class="text-sm text-base-content/50">Assign groomers to sectors to maintain slope conditions</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($groomers) ?></div>
            <div class="text-xs text-base-content/50">Total Groomers</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold <?= $totalAssigned >= $totalNeeded ? 'text-success' : 'text-warning' ?>"><?= $totalAssigned ?>/<?= $totalNeeded ?></div>
            <div class="text-xs text-base-content/50">Assigned/Needed</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($unassignedGroomers) ?></div>
            <div class="text-xs text-base-content/50">Unassigned</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= $slopeCount ?></div>
            <div class="text-xs text-base-content/50">Total Slopes</div>
        </div></div>
    </div>

    <?php if (empty($groomers)) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-user-slash"></i><span>No groomer operators hired. <a href="/staff/hire" class="link font-semibold">Hire groomers</a> to maintain your slopes.</span></div>
    <?php endif ?>

    <?php if (empty($sectors)) : ?>
        <div class="alert alert-info mb-4"><i class="fa-solid fa-info-circle"></i><span>No slopes built yet. <a href="/map" class="link font-semibold">Build slopes</a> from the Trail Map first.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Sectors -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-mountain mr-1"></i>Sectors</h2>
            <?php if (!empty($sectors)) : ?>
                <div class="space-y-3">
                <?php foreach ($sectors as $sectorNum => $sector) : ?>
                    <?php $covered = $sector['groomers_assigned'] >= $sector['groomers_needed']; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-<?= $covered ? 'circle-check text-success' : 'circle-xmark text-error' ?>"></i>
                                <span class="font-semibold">Sector <?= $sectorNum ?></span>
                                <span class="badge badge-sm badge-ghost"><?= count($sector['slopes']) ?> slopes</span>
                            </div>
                            <div class="text-sm">
                                <span class="font-mono <?= $covered ? 'text-success' : 'text-error' ?>"><?= $sector['groomers_assigned'] ?>/<?= $sector['groomers_needed'] ?></span>
                                <span class="text-xs text-base-content/50 ml-1">groomers</span>
                            </div>
                        </div>
                        <?php if (!$covered) : ?>
                            <div class="text-xs text-error mb-2"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Needs <?= $sector['groomers_needed'] - $sector['groomers_assigned'] ?> more groomer(s) — slopes will degrade faster</div>
                        <?php else : ?>
                            <div class="text-xs text-success mb-2"><i class="fa-solid fa-check mr-1"></i>Fully covered — slopes maintained</div>
                        <?php endif ?>
                        <div class="text-xs text-base-content/50">
                            Slopes: <?= implode(', ', array_map(fn($s) => esc($s['name']), $sector['slopes'])) ?>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Assign Groomers -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-user-gear mr-1"></i>Assign Groomers</h2>

            <?php if (empty($groomers)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-8">
                    <i class="fa-solid fa-tractor text-3xl text-base-content/20 mb-3"></i>
                    <p class="text-sm text-base-content/50"><a href="/staff/hire" class="link link-primary">Hire groomer operators</a></p>
                </div></div>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($groomers as $g) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fa-solid fa-tractor text-success"></i>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold truncate"><?= esc($g['name']) ?></div>
                                <div class="text-xs text-base-content/50">Morale: <?= $g['morale'] ?>%</div>
                            </div>
                        </div>
                        <form action="/grooming/assign" method="post" class="flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="groomer_id" value="<?= $g['id'] ?>">
                            <select name="sector" class="select select-bordered select-xs flex-1">
                                <option value="" <?= !$g['assigned_to'] ? 'selected' : '' ?>>Unassigned</option>
                                <?php foreach ($sectors as $sNum => $sec) : ?>
                                    <option value="<?= $sNum ?>" <?= $g['assigned_to'] === 'sector_' . $sNum ? 'selected' : '' ?>>Sector <?= $sNum ?> (<?= count($sec['slopes']) ?> slopes)</option>
                                <?php endforeach ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-check"></i></button>
                        </form>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-4">
                <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Grooming Info</h3>
                <ul class="text-xs text-base-content/60 space-y-1.5">
                    <li><i class="fa-solid fa-calculator mr-1"></i>1 groomer per 3 slopes needed</li>
                    <li><i class="fa-solid fa-arrow-down mr-1"></i>Ungroomed slopes lose condition daily</li>
                    <li><i class="fa-solid fa-star mr-1"></i>Well-groomed slopes increase visitor satisfaction</li>
                    <li><i class="fa-solid fa-triangle-exclamation mr-1"></i>Slopes below 30% condition close automatically</li>
                </ul>
            </div></div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
