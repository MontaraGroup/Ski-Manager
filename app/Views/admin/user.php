<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin - <?= esc($user['username']) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><?= esc($user['username']) ?></h1>
        <span class="badge badge-ghost">ID: <?= $user['id'] ?></span>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-coins mr-1 text-warning"></i>Cash</h2>
            <div class="text-2xl font-bold mb-3"><?= currency($finance ? (int) $finance['cash'] : 0) ?></div>
            <form action="/admin/cash" method="post" class="flex gap-2"><?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="number" name="cash" value="<?= $finance ? $finance['cash'] : 500000 ?>" class="input input-bordered input-sm flex-1">
                <button type="submit" class="btn btn-primary btn-sm">Set</button>
            </form>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-seedling mr-1 text-success"></i>Génépis</h2>
            <div class="text-2xl font-bold mb-3"><?= $genepis ? number_format((int) $genepis['balance']) : 0 ?></div>
            <?php if ($genepis) : ?>
            <form action="/admin/genepis" method="post" class="flex gap-2"><?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="number" name="genepis" value="<?= $genepis['balance'] ?>" class="input input-bordered input-sm flex-1">
                <button type="submit" class="btn btn-primary btn-sm">Set</button>
            </form>
            <?php endif ?>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-user mr-1"></i>Info</h2>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between"><span class="text-base-content/50">Email</span><span><?= esc($identity['secret'] ?? 'N/A') ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Joined</span><span><?= date('M j, Y', strtotime($user['created_at'])) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Staff</span><span><?= count($staff) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Buildings</span><span><?= count($buildings) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Slopes/Lifts</span><span><?= count($items) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Active Loans</span><span><?= count($loans) ?></span></div>
            </div>
        </div></div>
    </div>

    <div class="card bg-base-100 shadow-sm"><div class="card-body">
        <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Activity (last 30)</h2>
        <div class="divide-y divide-base-300 max-h-80 overflow-y-auto">
        <?php foreach ($logs as $log) : ?>
            <div class="flex items-center gap-2 py-1.5"><i class="<?= $log['icon'] ?> text-xs text-base-content/50 w-4 text-center"></i><span class="flex-1 text-sm truncate"><?= esc($log['message']) ?></span><span class="text-xs text-base-content/40">D<?= $log['game_day'] ?></span></div>
        <?php endforeach ?>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
