<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($guide[0]) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php use App\Controllers\Guides as G; ?>
<article class="max-w-3xl mx-auto p-4 lg:p-8">
    <nav class="text-sm text-base-content/70 mb-3" aria-label="Breadcrumb"><a href="/guides" class="link">Guides</a> / <?= esc($guide[0]) ?></nav>
    <h1 class="text-3xl font-bold mb-2"><?= esc($guide[0]) ?></h1>
    <p class="text-lg text-base-content/70 mb-6"><?= esc($guide[1]) ?></p>
    <div class="guide-body space-y-4 leading-relaxed">
        <?= view('guides/articles/' . $slug) ?>
    </div>
    <nav class="flex justify-between gap-3 mt-8 pt-6 border-t border-base-300" aria-label="More guides">
        <?php if ($prev) : ?><a href="/guides/<?= $prev ?>" class="link">← <?= esc(G::GUIDES[$prev][0]) ?></a><?php else : ?><span></span><?php endif ?>
        <?php if ($next) : ?><a href="/guides/<?= $next ?>" class="link text-right"><?= esc(G::GUIDES[$next][0]) ?> →</a><?php endif ?>
    </nav>
    <?php if (!auth()->loggedIn()) : ?>
    <div class="card bg-base-100 shadow-sm mt-8"><div class="card-body p-5 sm:flex-row sm:items-center sm:justify-between gap-3">
        <div><div class="font-bold">Run your own resort</div><div class="text-sm text-base-content/70">Ski Manager is free and plays in your browser.</div></div>
        <a href="/register" class="btn btn-primary">Play free</a>
    </div></div>
    <?php endif ?>
</article>
<?= $this->endSection() ?>
