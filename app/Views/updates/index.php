<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Game Updates<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $typeMeta = [
        'New Features' => ['badge' => 'badge-success', 'icon' => 'fa-plus',   'dot' => 'bg-success'],
        'Improvements' => ['badge' => 'badge-info',    'icon' => 'fa-arrow-up','dot' => 'bg-info'],
        'Bug Fixes'    => ['badge' => 'badge-warning', 'icon' => 'fa-wrench',  'dot' => 'bg-warning'],
        'Removed'      => ['badge' => 'badge-error',   'icon' => 'fa-minus',   'dot' => 'bg-error'],
    ];
?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold mb-1">Game Updates</h1>
        <p class="text-base-content/60">What's new in Ski Manager. Follow our progress and see what's shipped.</p>
    </div>

    <?php if (empty($updates)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
            <i class="fa-solid fa-newspaper text-5xl text-base-content/15 mb-3"></i>
            <p class="font-semibold">No updates yet</p>
            <p class="text-sm text-base-content/50 mt-1">Check back soon - we ship often.</p>
        </div></div>
    <?php else : ?>
        <div class="relative border-l-2 border-base-300 ml-3 space-y-8">
        <?php foreach ($updates as $i => $update) : ?>
            <?php $latest = $i === 0; ?>
            <div class="relative pl-6">
                <!-- Timeline dot -->
                <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 <?= $latest ? 'bg-primary' : 'bg-base-300' ?>"></span>

                <div class="card bg-base-100 shadow-sm border <?= $latest ? 'border-primary' : 'border-base-300' ?>">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <span class="badge <?= $latest ? 'badge-primary' : 'badge-ghost' ?> font-mono">v<?= esc($update['version']) ?></span>
                            <?php if ($latest) : ?><span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-star text-[10px]"></i>Latest</span><?php endif ?>
                            <span class="badge badge-outline badge-sm"><?= esc(ucfirst($update['type'])) ?></span>
                            <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i><?= date('F j, Y', strtotime($update['released_at'])) ?></span>
                        </div>

                        <h2 class="text-lg font-bold"><?= esc($update['title']) ?></h2>
                        <?php if (!empty($update['description'])) : ?>
                            <p class="text-sm text-base-content/70 mt-1"><?= esc($update['description']) ?></p>
                        <?php endif ?>

                        <?php if (!empty($update['categories'])) : ?>
                        <div class="mt-4 space-y-4">
                            <?php foreach ($update['categories'] as $category => $items) : ?>
                                <?php $meta = $typeMeta[$category] ?? ['badge' => 'badge-ghost', 'icon' => 'fa-circle', 'dot' => 'bg-base-300']; ?>
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="badge <?= $meta['badge'] ?> badge-sm gap-1"><i class="fa-solid <?= $meta['icon'] ?> text-[10px]"></i><?= esc($category) ?></span>
                                        <span class="text-xs text-base-content/40"><?= count($items) ?></span>
                                    </div>
                                    <ul class="space-y-1.5 ml-1">
                                        <?php foreach ($items as $item) : ?>
                                        <li class="flex items-start gap-2 text-sm text-base-content/80">
                                            <span class="w-1.5 h-1.5 rounded-full <?= $meta['dot'] ?> mt-1.5 shrink-0"></span>
                                            <span><?= esc($item['text']) ?></span>
                                        </li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
