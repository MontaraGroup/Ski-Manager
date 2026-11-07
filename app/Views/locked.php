<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Locked<?= $this->endSection() ?>
<?= $this->section('no_ads') ?>1<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$fallbackLocked = ob_start();
?>
<div class="max-w-lg mx-auto p-4 lg:p-8">
    <div class="rounded-2xl border border-border bg-card p-6 sm:p-10 text-center shadow-xl">
        <div class="w-20 h-20 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
            <i class="fa-solid fa-lock text-3xl text-warning"></i>
        </div>
        <h1 class="text-2xl font-bold mb-2"><?= esc($unlock['label'] ?? 'Feature') ?> Locked</h1>
        <p class="text-muted mb-6">Complete an achievement to unlock this feature.</p>

        <div class="rounded-xl border border-border/80 bg-default/40 mx-auto max-w-xs p-4 text-left">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
                    <i class="<?= esc($unlock['icon'] ?? 'fa-solid fa-trophy') ?> text-warning"></i>
                </div>
                <div class="text-left flex-1 min-w-0">
                    <div class="font-bold text-sm truncate"><?= esc($unlock['name'] ?? 'Achievement') ?></div>
                    <div class="text-xs text-muted"><?= esc($unlock['desc'] ?? '') ?></div>
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <div class="h-2 flex-1 rounded-full bg-default overflow-hidden">
                    <div class="h-full rounded-full bg-warning" style="width: <?= min(100, round((($unlock['progress'] ?? 0) / max(1, $unlock['target'] ?? 1)) * 100)) ?>%"></div>
                </div>
                <span class="text-xs font-mono text-muted"><?= $unlock['progress'] ?? 0 ?>/<?= $unlock['target'] ?? 1 ?></span>
            </div>
        </div>

        <div class="mt-8">
            <a href="/achievements" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center gap-2 rounded-xl bg-warning px-5 py-2.5 text-sm font-semibold text-warning-foreground hover:bg-warning/90">
                <i class="fa-solid fa-trophy"></i> View Achievements
            </a>
        </div>
    </div>
</div>
<?php $fallbackHtml = ob_get_clean(); ?>
<?= island('LockedPage', ['unlock' => $unlock ?? []], $fallbackHtml) ?>
<?= $this->endSection() ?>
