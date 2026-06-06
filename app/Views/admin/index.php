<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Panel<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved mr-2 text-error"></i>Admin</h1>
            <span class="badge badge-outline">Day <?= $gameDay ?></span>
        </div>
        <div class="flex gap-2">
            <a href="/admin/broadcast" class="btn btn-warning btn-sm gap-1"><i class="fa-solid fa-bullhorn"></i>Broadcast</a>
            <a href="/admin/settings" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-gear"></i>Settings</a>
            <a href="/admin/economy" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-chart-line"></i>Economy</a>
            <a href="/admin/activity" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-clock-rotate-left"></i>Logs</a>
            <form action="/admin/trigger-tick" method="post" class="inline" onsubmit="return confirm('Run game tick now?')"><?= csrf_field() ?><button class="btn btn-error btn-sm gap-1"><i class="fa-solid fa-play"></i>Run Tick</button></form>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalUsers ?></div><div class="text-xs text-base-content/50">Players</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><?= currency($totalCash) ?></div><div class="text-xs text-base-content/50">Total Cash</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalStaff ?></div><div class="text-xs text-base-content/50">Staff</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalBuildings ?></div><div class="text-xs text-base-content/50">Buildings</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalItems ?></div><div class="text-xs text-base-content/50">Slopes/Lifts</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><?= $totalLoans ?></div><div class="text-xs text-base-content/50">Active Loans</div></div></div>
    </div>

    <?php if ($weather) : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-3">
        <div class="flex items-center gap-3 text-sm">
            <span class="font-semibold">Today:</span>
            <span><?= $weather['condition_name'] ?> · <?= temp($weather['temp']) ?> · Wind: <?= speed($weather['wind']) ?> · Snow base: <?= snow($weather['snow_base']) ?></span>
        </div>
    </div></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Players</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>ID</th><th>Username</th><th>Cash</th><th>Staff</th><th>Buildings</th><th>Items</th><th>Joined</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u) : ?>
                        <tr class="<?= isset($u['active']) && !$u['active'] ? 'opacity-40' : '' ?>">
                            <td><?= $u['id'] ?></td>
                            <td class="font-semibold"><?= esc($u['username']) ?> <?= isset($u['active']) && !$u['active'] ? '<span class="badge badge-error badge-xs">Banned</span>' : '' ?></td>
                            <td class="font-mono text-xs"><?= currency($u['cash']) ?></td>
                            <td><?= $u['staff_count'] ?></td>
                            <td><?= $u['building_count'] ?></td>
                            <td><?= $u['item_count'] ?></td>
                            <td class="text-xs text-base-content/50"><?= date('M j', strtotime($u['created_at'])) ?></td>
                            <td class="flex gap-1">
                                <a href="/admin/user/<?= $u['id'] ?>" class="btn btn-ghost btn-xs" aria-label="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                <?php if ((int) $u['id'] !== 1) : ?>
                                    <?php if (isset($u['active']) && !$u['active']) : ?>
                                        <form action="/admin/unban/<?= $u['id'] ?>" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-success" aria-label="Unban"><i class="fa-solid fa-unlock"></i></button></form>
                                    <?php else : ?>
                                        <form action="/admin/ban/<?= $u['id'] ?>" method="post" class="inline" onsubmit="return confirm('Ban this user?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-warning" aria-label="Ban"><i class="fa-solid fa-ban"></i></button></form>
                                    <?php endif ?>
                                    <form action="/admin/delete/<?= $u['id'] ?>" method="post" class="inline" onsubmit="return confirm('Permanently delete?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error" aria-label="Delete"><i class="fa-solid fa-trash"></i></button></form>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div></div></div>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3">Global Activity</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0">
                <div class="divide-y divide-base-300 max-h-96 overflow-y-auto">
                <?php foreach ($recentLogs as $log) : ?>
                    <div class="flex items-center gap-2 p-2 text-xs">
                        <i class="<?= $log['icon'] ?> text-base-content/50 w-4 text-center"></i>
                        <span class="font-semibold text-primary shrink-0"><?= esc($log['username']) ?></span>
                        <span class="flex-1 truncate"><?= esc($log['message']) ?></span>
                        <span class="text-base-content/40 shrink-0">D<?= $log['game_day'] ?></span>
                    </div>
                <?php endforeach ?>
                </div>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
