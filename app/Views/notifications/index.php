<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Notifications<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-bell mr-2"></i>Notifications</h1>
                <p class="text-sm text-base-content/50"><?= $totalCount ?> total · <?= $unreadCount ?> unread</p>
            </div>
        </div>
        <?php if (!empty($notifications)) : ?>
        <div class="dropdown dropdown-end">
            <div tabindex="0" class="btn btn-ghost btn-sm"><i class="fa-solid fa-ellipsis-vertical"></i></div>
            <ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow w-52 z-50 mt-2">
                <?php if ($unreadCount > 0) : ?>
                <li><a href="/notifications/read-all"><i class="fa-solid fa-check-double mr-1"></i>Mark all read</a></li>
                <?php endif ?>
                <li><form action="/notifications/delete-read" method="post" data-confirm="Delete all read notifications?"><?= csrf_field() ?><button class="w-full text-left"><i class="fa-solid fa-broom mr-1"></i>Clear read</button></form></li>
                <li><form action="/notifications/delete-all" method="post" data-confirm="Delete ALL notifications?"><?= csrf_field() ?><button class="w-full text-left text-error"><i class="fa-solid fa-trash mr-1"></i>Delete all</button></form></li>
            </ul>
        </div>
        <?php endif ?>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <!-- Filters -->
    <div class="flex gap-2 mb-4">
        <a href="/notifications" class="btn btn-sm <?= $filter === 'all' ? 'btn-primary' : 'btn-ghost' ?>">All (<?= $totalCount ?>)</a>
        <a href="/notifications?filter=unread" class="btn btn-sm <?= $filter === 'unread' ? 'btn-primary' : 'btn-ghost' ?>">Unread (<?= $unreadCount ?>)</a>
    </div>

    <?php if (empty($notifications)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
            <i class="fa-solid fa-bell-slash text-4xl text-base-content/20 mb-3"></i>
            <p class="text-base-content/60"><?= $filter === 'unread' ? 'No unread notifications.' : 'No notifications yet.' ?></p>
            <?php if ($filter === 'unread') : ?><a href="/notifications" class="link link-primary text-sm mt-2">View all</a><?php endif ?>
        </div></div>
    <?php else : ?>
        <?php
            $grouped = [];
            foreach ($notifications as $n) {
                $day = date('Y-m-d', strtotime($n['created_at']));
                $grouped[$day][] = $n;
            }
        ?>
        <?php foreach ($grouped as $day => $dayNotifs) : ?>
            <?php
                $today = date('Y-m-d');
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                $label = $day === $today ? 'Today' : ($day === $yesterday ? 'Yesterday' : date('M j, Y', strtotime($day)));
            ?>
            <div class="text-xs font-semibold text-base-content/40 uppercase tracking-wider mb-2 mt-4"><?= $label ?></div>
            <div class="space-y-1 mb-2">
            <?php foreach ($dayNotifs as $n) : ?>
                <a href="<?= $n['link'] ?? '/notifications' ?>" class="card bg-base-100 shadow-sm hover:shadow-md transition-all block <?= $n['is_read'] ? 'opacity-50 hover:opacity-80' : 'border-l-4 border-primary' ?>">
                    <div class="card-body p-3 flex-row items-center gap-3">
                        <div class="w-9 h-9 rounded-lg <?= $n['is_read'] ? 'bg-base-200' : 'bg-primary/10' ?> flex items-center justify-center shrink-0">
                            <i class="<?= $n['icon'] ?? 'fa-solid fa-bell' ?> text-sm <?= $n['is_read'] ? 'text-base-content/30' : 'text-primary' ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm <?= $n['is_read'] ? '' : '' ?>"><?= esc($n['title']) ?></div>
                            <div class="text-xs text-base-content/50 truncate"><?= esc($n['message']) ?></div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs text-base-content/40"><?= date('g:ia', strtotime($n['created_at'])) ?></div>
                            <?php if (!$n['is_read']) : ?><div class="w-2 h-2 rounded-full bg-primary ml-auto mt-1"></div><?php endif ?>
                        </div>
                    </div>
                </a>
            <?php endforeach ?>
            </div>
        <?php endforeach ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
