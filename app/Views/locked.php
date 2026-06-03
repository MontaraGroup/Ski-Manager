<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Locked<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-lg mx-auto p-4 lg:p-8">
    <div class="card bg-base-100 shadow-xl border border-base-300">
        <div class="card-body text-center py-16">
            <div class="w-20 h-20 rounded-full bg-warning/10 flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-lock text-3xl text-warning"></i>
            </div>
            <h1 class="text-2xl font-bold mb-2"><?= esc($unlock['label'] ?? 'Feature') ?> Locked</h1>
            <p class="text-base-content/60 mb-6">Complete an achievement to unlock this feature.</p>

            <div class="card bg-base-200 border border-base-300 mx-auto max-w-xs">
                <div class="card-body p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
                            <i class="<?= esc($unlock['icon'] ?? 'fa-solid fa-trophy') ?> text-warning"></i>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-bold text-sm"><?= esc($unlock['name'] ?? 'Achievement') ?></div>
                            <div class="text-xs text-base-content/50"><?= esc($unlock['desc'] ?? '') ?></div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <progress class="progress progress-warning flex-1" value="<?= $unlock['progress'] ?? 0 ?>" max="<?= $unlock['target'] ?? 1 ?>"></progress>
                        <span class="text-xs font-mono"><?= $unlock['progress'] ?? 0 ?>/<?= $unlock['target'] ?? 1 ?></span>
                    </div>
                </div>
            </div>

            <a href="/achievements" class="btn btn-warning btn-sm mt-6 gap-1"><i class="fa-solid fa-trophy"></i> View Achievements</a>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
