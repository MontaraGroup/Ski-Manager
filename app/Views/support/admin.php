<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Support Inbox<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-headset mr-2 text-primary"></i>Support Inbox</h1>
            <?php $totalUnread = array_sum(array_map(fn($c) => (int) $c['unread'], $conversations ?? [])); ?>
            <p class="text-sm text-base-content/50"><?= count($conversations ?? []) ?> conversation<?= count($conversations ?? []) === 1 ? '' : 's' ?><?= $totalUnread > 0 ? ' · ' . $totalUnread . ' unread' : '' ?></p>
        </div>
    </div>

    <?php if (empty($conversations)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
            <i class="fa-solid fa-inbox text-5xl text-base-content/15 mb-3"></i>
            <p class="font-semibold">No support messages yet</p>
            <p class="text-sm text-base-content/50 mt-1">Player messages will appear here.</p>
        </div></div>
    <?php else : ?>
        <div class="space-y-2">
        <?php foreach ($conversations as $conv) : ?>
            <?php $unread = (int) $conv['unread']; ?>
            <a href="/admin/support/<?= $conv['user_id'] ?>" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow block <?= $unread > 0 ? 'border-l-4 border-error' : '' ?>">
                <div class="card-body p-3 flex-row items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-neutral text-neutral-content flex items-center justify-center text-sm font-semibold shrink-0"><?= strtoupper(substr($conv['username'] ?? '?', 0, 2)) ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold <?= $unread > 0 ? 'text-error' : '' ?>"><?= esc($conv['username'] ?? 'User #' . $conv['user_id']) ?></span>
                            <?php if ($unread > 0) : ?><span class="badge badge-error badge-xs"><?= $unread ?></span><?php endif ?>
                        </div>
                        <div class="text-xs text-base-content/50 truncate <?= $unread > 0 ? 'font-medium text-base-content/70' : '' ?>"><?= esc($conv['last_message'] ?? '') ?></div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs text-base-content/40"><?= $conv['last_at'] ? (function_exists('timeAgo') ? timeAgo($conv['last_at']) : date('M j, g:ia', strtotime($conv['last_at']))) : '' ?></div>
                        <?php if ($unread > 0) : ?><div class="text-xs text-error font-semibold"><?= $unread ?> unread</div><?php endif ?>
                    </div>
                </div>
            </a>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
