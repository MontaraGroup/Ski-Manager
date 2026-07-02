<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Notification Center<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight flex items-center gap-3">
                <i class="fa-solid fa-bell text-primary animate-pulse"></i>
                Notification Center
            </h1>
            <p class="text-sm text-base-content/60 mt-1">Manage your resort alerts, system logs, and structural updates.</p>
        </div>

        <?php if (!empty($notifications)) : ?>
            <div class="dropdown dropdown-end self-start md:self-center">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm border-base-300 gap-2">
                    <i class="fa-solid fa-sliders"></i> Bulk Actions <i class="fa-solid fa-chevron-down text-xs"></i>
                </div>
                <ul tabindex="0" class="dropdown-content menu p-2 shadow-lg bg-base-100 rounded-box w-52 z-10 border border-base-200">
                    <li><a href="/notifications/read-all" class="text-sm"><i class="fa-solid fa-check-double text-success w-4"></i>Mark all read</a></li>
                    <li>
                        <form action="/notifications/delete-read" method="post" data-confirm="Clear all read notifications from your logs?" class="w-full">
                            <?= csrf_field() ?><button class="w-full text-left flex items-center gap-2"><i class="fa-solid fa-broom text-warning w-4"></i>Clear read</button>
                        </form>
                    </li>
                    <div class="divider my-1"></div>
                    <li>
                        <form action="/notifications/delete-all" method="post" data-confirm="Wipe your entire notification archive? This cannot be undone." class="w-full">
                            <?= csrf_field() ?><button class="w-full text-left text-error flex items-center gap-2"><i class="fa-solid fa-trash w-4"></i>Delete all</button>
                        </form>
                    </li>
                </ul>
            </div>
        <?php endif ?>
    </div>

    <div class="flex items-center gap-2 mb-6 bg-base-200 p-1.5 rounded-xl w-fit">
        <a href="/notifications" class="btn btn-sm px-4 rounded-lg border-none <?= $filter === 'all' ? 'btn-primary shadow-sm text-primary-content' : 'btn-ghost text-base-content/70' ?>">
            All Logs <div class="badge badge-sm <?= $filter === 'all' ? 'badge-accent' : 'badge-ghost' ?> ml-1"><?= $totalCount ?></div>
        </a>
        <a href="/notifications?filter=unread" class="btn btn-sm px-4 rounded-lg border-none <?= $filter === 'unread' ? 'btn-primary shadow-sm text-primary-content' : 'btn-ghost text-base-content/70' ?>">
            Unread <div class="badge badge-sm <?= $filter === 'unread' ? 'badge-accent' : 'badge-ghost' ?> ml-1"><?= $unreadCount ?></div>
        </a>
    </div>

    <?php if (empty($notifications)) : ?>
        <div class="card bg-base-100 border border-base-200 shadow-sm py-16 text-center">
            <div class="card-body max-w-sm mx-auto items-center justify-center">
                <div class="w-16 h-16 rounded-full bg-base-200 flex items-center justify-center mb-4 text-base-content/40">
                    <i class="fa-solid fa-folder-open text-2xl"></i>
                </div>
                <h3 class="text-lg font-bold">All caught up!</h3>
                <p class="text-sm text-base-content/50"><?= $filter === 'unread' ? 'No unread alerts waiting for attention right now.' : 'Your resort alert dashboard is completely clean.' ?></p>
                <?php if ($filter === 'unread') : ?>
                    <a href="/notifications" class="btn btn-sm btn-outline mt-4">View History Archive</a>
                <?php endif ?>
            </div>
        </div>
    <?php else : ?>
        <div class="space-y-3" id="mainNotificationFeed">
            <?php foreach ($notifications as $n) : 
                $isRead = (bool)$n['is_read'];
                $borderAccent = 'border-l-base-300';
                $iconColor = 'text-base-content/40';
                
                // Explicitly string cast to prevent null errors on missing properties
                $iconString = strtolower((string)($n['icon'] ?? ''));
                $titleString = strtolower((string)($n['title'] ?? ''));

                if (!$isRead) {
                    $iconColor = 'text-primary';
                    if (str_contains($iconString, 'snowflake') || str_contains($titleString, 'weather')) {
                        $borderAccent = 'border-l-info';
                        $iconColor = 'text-info';
                    } elseif (str_contains($iconString, 'trophy') || str_contains($iconString, 'medal')) {
                        $borderAccent = 'border-l-accent';
                        $iconColor = 'text-accent';
                    } elseif (str_contains($iconString, 'exclamation') || str_contains($iconString, 'triangle')) {
                        $borderAccent = 'border-l-error';
                        $iconColor = 'text-error';
                    } else {
                        $borderAccent = 'border-l-primary';
                    }
                }
            ?>
                <div class="card bg-base-100 border border-base-200 border-l-4 <?= $borderAccent ?> shadow-sm hover:shadow transition-all group relative <?= $isRead ? 'opacity-65' : '' ?>">
                    <div class="card-body p-4 flex flex-row items-start gap-4 justify-between">
                        <a href="<?= esc($n['link'] ?? '/notifications') ?>" class="flex gap-4 items-start flex-1 min-w-0 no-underline text-current">
                            <div class="w-10 h-10 rounded-xl bg-base-200/60 shrink-0 flex items-center justify-center group-hover:bg-base-200 transition-colors">
                                <i class="<?= esc($n['icon'] ?? 'fa-solid fa-bell') ?> text-base <?= $iconColor ?>"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-baseline justify-between gap-2">
                                    <h3 class="font-bold text-sm md:text-base text-base-content group-hover:text-primary transition-colors truncate">
                                        <?= esc($n['title']) ?>
                                    </h3>
                                </div>
                                <p class="text-xs md:text-sm text-base-content/70 mt-0.5 whitespace-normal break-words leading-relaxed">
                                    <?= esc($n['message']) ?>
                                </p>
                                <span class="text-[10px] uppercase font-bold tracking-wider text-base-content/40 block mt-2">
                                    <i class="fa-regular fa-clock mr-1"></i><?= date('M d, H:i', strtotime($n['created_at'])) ?>
                                </span>
                            </div>
                        </a>
                        
                        <div class="flex items-center self-center shrink-0 ml-2">
                            <?php if (!$isRead) : ?>
                                <span class="w-2.5 h-2.5 rounded-full bg-primary ring-4 ring-primary/20 block"></span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("form[action*=\"/notifications/\"]").forEach(form => {
        form.addEventListener("submit", (e) => {
            e.preventDefault();
            const confirmationMsg = form.getAttribute("data-confirm");
            if (confirmationMsg && !confirm(confirmationMsg)) return;

            fetch(form.getAttribute("action"), {
                method: "POST",
                body: new FormData(form),
                headers: { "X-Requested-With": "XMLHttpRequest" }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                }
            });
        });
    });
});
</script>
<?= $this->endSection() ?>
