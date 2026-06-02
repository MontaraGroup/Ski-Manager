<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Activity Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Activity Log</h1>
    </div>
    <?php if (empty($logs)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12"><i class="fa-solid fa-scroll text-4xl text-base-content/20 mb-4"></i><h3 class="font-semibold">No activity yet</h3><p class="text-sm text-base-content/50">Actions like building, hiring, and purchases will appear here.</p></div></div>
    <?php else : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-0">
            <div class="divide-y divide-base-300">
            <?php foreach ($logs as $log) : ?>
                <div class="flex items-center gap-3 p-3">
                    <div class="w-8 h-8 rounded-full bg-base-200 flex items-center justify-center shrink-0"><i class="<?= $log['icon'] ?> text-sm"></i></div>
                    <div class="flex-1 min-w-0"><div class="text-sm"><?= esc($log['message']) ?></div><div class="text-xs text-base-content/50">Day <?= $log['game_day'] ?> · <?= $log['category'] ?></div></div>
                </div>
            <?php endforeach ?>
            </div>
        </div></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
