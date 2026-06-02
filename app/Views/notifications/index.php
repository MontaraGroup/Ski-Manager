<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-4xl">
    <h1 class="text-3xl font-bold mb-6"><i class="fa-solid fa-bell mr-2"></i> Notifications</h1>

    <?php if (empty($notifications)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
            <i class="fa-solid fa-bell-slash text-4xl text-base-content/20 mb-3"></i>
            <p class="text-base-content/60">No notifications yet.</p>
        </div></div>
    <?php else : ?>
        <div class="space-y-2">
        <?php foreach ($notifications as $n) : ?>
            <a href="<?= $n['link'] ?? '/notifications' ?>" class="card bg-base-100 shadow-sm hover:bg-base-200 transition-colors block <?= $n['is_read'] ? 'opacity-60' : '' ?>">
                <div class="card-body p-3 flex-row items-start gap-3">
                    <i class="<?= $n['icon'] ?> text-lg mt-0.5 w-6 text-center shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm"><?= esc($n['title']) ?></div>
                        <div class="text-xs text-base-content/60 mt-0.5"><?= esc($n['message']) ?></div>
                        <div class="text-xs text-base-content/40 mt-1"><?= date('M j, g:i A', strtotime($n['created_at'])) ?></div>
                    </div>
                    <span class="shrink-0 mt-1"><i class="fa-solid fa-chevron-right text-base-content/30"></i></span>
                </div>
            </a>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
