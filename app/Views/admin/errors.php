<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Error Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin/settings" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-bug mr-2 text-error"></i>Error Log</h1>
        <span class="badge badge-outline"><?= date('M j, Y') ?></span>
    </div>
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
        <?php if (empty($lines)) : ?>
            <p class="text-sm text-success"><i class="fa-solid fa-check mr-1"></i> No errors today.</p>
        <?php else : ?>
            <div class="overflow-x-auto"><pre class="text-xs leading-relaxed text-base-content/70 whitespace-pre-wrap"><?php foreach ($lines as $line) : ?><span class="<?= str_contains($line, 'ERROR') || str_contains($line, 'CRITICAL') ? 'text-error font-semibold' : (str_contains($line, 'WARNING') ? 'text-warning' : '') ?>"><?= esc($line) ?></span>
<?php endforeach ?></pre></div>
        <?php endif ?>
    </div></div>
</div>
<?= $this->endSection() ?>
