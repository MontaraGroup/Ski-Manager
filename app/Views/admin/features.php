<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Feature Flags<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$live = array_filter($flags, fn($f) => !str_starts_with($f['flag_key'], 'beta_'));
$beta = array_filter($flags, fn($f) => str_starts_with($f['flag_key'], 'beta_'));
?>
<div class="max-w-4xl mx-auto p-4 lg:p-8 pb-12">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-toggle-on mr-2 text-success"></i>Feature Flags</h1>
        <span class="badge badge-outline"><?= count($flags) ?> total</span>
    </div>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-circle-check mr-1 text-success"></i>Live Features</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-8">
    <?php foreach ($live as $f) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 flex-row items-center justify-between">
            <div>
                <div class="font-semibold text-sm"><?= esc($f['name']) ?></div>
                <div class="text-xs text-base-content/50"><?= esc($f['description']) ?></div>
            </div>
            <form action="/admin/features/toggle/<?= $f['id'] ?>" method="post">
                <?= csrf_field() ?>
                <input type="checkbox" class="toggle toggle-success" onchange="this.form.submit()" <?= $f['enabled'] ? 'checked' : '' ?>>
            </form>
        </div></div>
    <?php endforeach ?>
    </div>

    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-flask mr-1 text-warning"></i>Beta Features</h2>
    <p class="text-sm text-base-content/50 mb-3">Experimental features. Enable at your own risk — some may be incomplete.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <?php foreach ($beta as $f) : ?>
        <div class="card bg-base-100 shadow-sm border border-warning/20"><div class="card-body p-4 flex-row items-center justify-between">
            <div>
                <div class="font-semibold text-sm"><?= esc(str_replace('[BETA] ', '', $f['name'])) ?></div>
                <div class="text-xs text-base-content/50"><?= esc($f['description']) ?></div>
            </div>
            <form action="/admin/features/toggle/<?= $f['id'] ?>" method="post">
                <?= csrf_field() ?>
                <input type="checkbox" class="toggle toggle-warning" onchange="this.form.submit()" <?= $f['enabled'] ? 'checked' : '' ?>>
            </form>
        </div></div>
    <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
