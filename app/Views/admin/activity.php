<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Activity Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-clock-rotate-left mr-2"></i>Activity Log</h1>
        <span class="badge badge-outline"><?= number_format($total) ?> entries</span>
    </div>

    <form action="/admin/activity" method="get" class="flex gap-2 mb-4">
        <input type="text" name="filter" value="<?= esc($filter) ?>" class="input input-bordered input-sm flex-1" placeholder="Search messages...">
        <input type="number" name="user" value="<?= esc($userFilter) ?>" class="input input-bordered input-sm w-24" placeholder="User ID">
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-search"></i></button>
        <?php if ($filter || $userFilter) : ?><a href="/admin/activity" class="btn btn-ghost btn-sm">Clear</a><?php endif ?>
    </form>

    <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table table-xs">
            <thead><tr><th>Time</th><th>Player</th><th>Day</th><th>Category</th><th>Message</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log) : ?>
                <tr>
                    <td class="text-base-content/50 whitespace-nowrap"><?= date('M j H:i', strtotime($log['created_at'])) ?></td>
                    <td><a href="/admin/user/<?= $log['user_id'] ?>" class="link font-semibold"><?= esc($log['username']) ?></a></td>
                    <td>D<?= $log['game_day'] ?></td>
                    <td><span class="badge badge-ghost badge-xs"><?= esc($log['category']) ?></span></td>
                    <td><i class="<?= $log['icon'] ?? 'fa-solid fa-circle' ?> mr-1 text-base-content/50"></i><?= esc($log['message']) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div></div></div>

    <?php $totalPages = ceil($total / $perPage); if ($totalPages > 1) : ?>
    <div class="flex justify-center gap-1 mt-4">
        <?php for ($p = 1; $p <= $totalPages; $p++) : ?>
            <a href="/admin/activity?page=<?= $p ?>&filter=<?= urlencode($filter) ?>&user=<?= urlencode($userFilter) ?>" class="btn btn-xs <?= $p === $page ? 'btn-primary' : 'btn-ghost' ?>"><?= $p ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
