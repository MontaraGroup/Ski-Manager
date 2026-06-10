<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Support Inbox<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-headset mr-2 text-primary"></i>Support Inbox</h1>
    </div>

    <?php if (empty($conversations)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
            <i class="fa-solid fa-inbox text-4xl text-base-content/20 mb-3"></i>
            <p class="text-base-content/50">No support messages yet.</p>
        </div></div>
    <?php else : ?>
        <div class="space-y-2">
        <?php foreach ($conversations as $conv) : ?>
            <a href="/admin/support/<?= $conv['user_id'] ?>" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow block">
                <div class="card-body p-3 flex-row items-center gap-3">
                    <div class="avatar placeholder"><div class="bg-neutral text-neutral-content rounded-full w-10 h-10 flex items-center justify-center text-sm"><?= strtoupper(substr($conv['username'] ?? '?', 0, 2)) ?></div></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold"><?= esc($conv['username'] ?? 'User #' . $conv['user_id']) ?></span>
                            <?php if ($conv['unread'] > 0) : ?><span class="badge badge-error badge-xs"><?= $conv['unread'] ?></span><?php endif ?>
                        </div>
                        <div class="text-xs text-base-content/50 truncate"><?= esc($conv['last_message'] ?? '') ?></div>
                    </div>
                    <div class="text-right"><div class="text-xs text-base-content/40"><?= $conv['last_at'] ? date('M j, g:ia', strtotime($conv['last_at'])) : '' ?></div><?php if ($conv['unread'] > 0) : ?><div class="text-xs text-error font-semibold"><?= $conv['unread'] ?> unread</div><?php endif ?></div>
                </div>
            </a>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
