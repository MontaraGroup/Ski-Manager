<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Feature Flags<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$live = array_filter($flags, fn($f) => !str_starts_with($f['flag_key'], 'beta_'));
$beta = array_filter($flags, fn($f) => str_starts_with($f['flag_key'], 'beta_'));
$labels = [0 => ['Off', 'badge-error', 'fa-circle-xmark text-error'], 1 => ['Admin', 'badge-warning', 'fa-user-shield text-warning'], 2 => ['Everyone', 'badge-success', 'fa-circle-check text-success']];
$onCount = count(array_filter($flags, fn($f) => (int)$f['enabled'] === 2));
$adminCount = count(array_filter($flags, fn($f) => (int)$f['enabled'] === 1));
$offCount = count(array_filter($flags, fn($f) => (int)$f['enabled'] === 0));
?>
<div class="max-w-4xl mx-auto p-4 lg:p-8 pb-12">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-toggle-on mr-2 text-success"></i>Feature Flags</h1>
                <p class="text-sm text-base-content/50"><?= count($flags) ?> features · <?= $onCount ?> live · <?= $adminCount ?> admin · <?= $offCount ?> off</p>
            </div>
        </div>
        <div class="flex gap-2">
            <form action="/admin/features/enable-all" method="post" data-confirm="Enable ALL features for everyone?"><?= csrf_field() ?>
                <button class="btn btn-success btn-sm gap-1"><i class="fa-solid fa-toggle-on"></i> All On</button>
            </form>
            <form action="/admin/features/disable-all-beta" method="post" data-confirm="Disable all beta features?"><?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm gap-1"><i class="fa-solid fa-flask"></i> Reset Beta</button>
            </form>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="card bg-success/10 border border-success/30"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success"><?= $onCount ?></div>
            <div class="text-xs text-base-content/50">Everyone</div>
        </div></div>
        <div class="card bg-warning/10 border border-warning/30"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-warning"><?= $adminCount ?></div>
            <div class="text-xs text-base-content/50">Admin Only</div>
        </div></div>
        <div class="card bg-error/10 border border-error/30"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-error"><?= $offCount ?></div>
            <div class="text-xs text-base-content/50">Disabled</div>
        </div></div>
    </div>

    <!-- Search -->
    <div class="mb-4">
        <input type="text" id="flagSearch" placeholder="Search features..." class="input input-bordered input-sm w-full" oninput="filterFlags(this.value)">
    </div>

    <!-- Live Features -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-circle-check mr-1 text-success"></i>Live Features <span class="badge badge-ghost badge-sm"><?= count($live) ?></span></h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-8" id="liveFlags">
    <?php foreach ($live as $f) : $lvl = (int) $f['enabled']; ?>
        <div class="card bg-base-100 shadow-sm flag-item" data-name="<?= strtolower(esc($f['name'])) ?>"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid <?= $labels[$lvl][2] ?> text-lg w-6 text-center"></i>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm truncate"><?= esc($f['name']) ?></div>
                <div class="text-xs text-base-content/40 truncate"><?= esc($f['description']) ?></div>
            </div>
            <form action="/admin/features/toggle/<?= $f['id'] ?>" method="post" class="flex items-center gap-1">
                <?= csrf_field() ?>
                <span class="badge badge-xs <?= $labels[$lvl][1] ?>"><?= $labels[$lvl][0] ?></span>
                <button class="btn btn-ghost btn-xs btn-circle" title="Cycle: Off → Admin → Everyone"><i class="fa-solid fa-arrows-rotate text-xs"></i></button>
            </form>
        </div></div>
    <?php endforeach ?>
    </div>

    <!-- Beta Features -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-flask mr-1 text-warning"></i>Beta Features <span class="badge badge-ghost badge-sm"><?= count($beta) ?></span></h2>
    <p class="text-xs text-base-content/40 mb-3">Cycle: Off → Admin Only → Everyone → Off</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="betaFlags">
    <?php foreach ($beta as $f) : $lvl = (int) $f['enabled']; ?>
        <div class="card bg-base-100 shadow-sm border border-warning/10 flag-item" data-name="<?= strtolower(esc(str_replace('[BETA] ', '', $f['name']))) ?>"><div class="card-body p-3 flex-row items-center gap-3">
            <i class="fa-solid <?= $labels[$lvl][2] ?> text-lg w-6 text-center"></i>
            <div class="flex-1 min-w-0">
                <div class="font-semibold text-sm truncate"><?= esc(str_replace('[BETA] ', '', $f['name'])) ?></div>
                <div class="text-xs text-base-content/40 truncate"><?= esc($f['description']) ?></div>
            </div>
            <form action="/admin/features/toggle/<?= $f['id'] ?>" method="post" class="flex items-center gap-1">
                <?= csrf_field() ?>
                <span class="badge badge-xs <?= $labels[$lvl][1] ?>"><?= $labels[$lvl][0] ?></span>
                <button class="btn btn-ghost btn-xs btn-circle" title="Cycle: Off → Admin → Everyone → Off"><i class="fa-solid fa-arrows-rotate text-xs"></i></button>
            </form>
        </div></div>
    <?php endforeach ?>
    </div>

    <!-- Legend -->
    <div class="mt-6 flex items-center gap-4 text-xs text-base-content/40">
        <span><i class="fa-solid fa-circle-check text-success mr-1"></i>Everyone can access</span>
        <span><i class="fa-solid fa-user-shield text-warning mr-1"></i>Admin testing only</span>
        <span><i class="fa-solid fa-circle-xmark text-error mr-1"></i>Completely disabled</span>
    </div>
</div>

<script>
function filterFlags(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.flag-item').forEach(function(el) {
        el.style.display = !q || el.dataset.name.includes(q) ? '' : 'none';
    });
}
</script>
<?= $this->endSection() ?>
