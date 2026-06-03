<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Insurance<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved mr-2 text-info"></i>Insurance</h1>
    </div>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $activeCount ?>/<?= count($policies) ?></div><div class="text-xs text-base-content/50">Active Policies</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><?= currency($totalPremium) ?></div><div class="text-xs text-base-content/50">Daily Premiums</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><?= currency($totalCoverage) ?></div><div class="text-xs text-base-content/50">Total Coverage</div></div></div>
    </div>
    <div class="space-y-3">
    <?php foreach ($policies as $p) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <div class="flex items-center gap-3 flex-1">
                    <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center"><i class="<?= $icons[$p['policy_type']] ?? 'fa-solid fa-shield' ?> text-lg <?= $p['active'] ? 'text-info' : 'text-base-content/30' ?>"></i></div>
                    <div><div class="font-semibold text-sm"><?= esc($p['name']) ?></div><div class="text-xs text-base-content/50">Premium: <?= currency((int)$p['premium_per_day']) ?>/day - Coverage: <?= currency((int)$p['coverage_amount']) ?></div></div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="badge <?= $p['active'] ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $p['active'] ? 'Active' : 'Inactive' ?></span>
                    <form action="/insurance/toggle/<?= $p['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-<?= $p['active'] ? 'ghost' : 'info' ?> btn-xs"><?= $p['active'] ? 'Cancel' : 'Activate' ?></button></form>
                </div>
            </div>
        </div></div>
    <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
