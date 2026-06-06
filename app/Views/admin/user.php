<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin - <?= esc($user['username']) ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <h1 class="text-2xl font-bold"><?= esc($user['username']) ?></h1>
            <span class="badge badge-ghost">ID: <?= $user['id'] ?></span>
            <?php if (isset($user['active']) && !$user['active']) : ?><span class="badge badge-error">Banned</span><?php endif ?>
        </div>
        <?php if ((int) $user['id'] !== 1) : ?>
        <div class="flex gap-2">
            <?php if (isset($user['active']) && !$user['active']) : ?>
                <form action="/admin/unban/<?= $user['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-success btn-sm gap-1"><i class="fa-solid fa-unlock"></i>Unban</button></form>
            <?php else : ?>
                <form action="/admin/ban/<?= $user['id'] ?>" method="post" onsubmit="return confirm('Ban this user?')"><?= csrf_field() ?><button class="btn btn-warning btn-sm gap-1"><i class="fa-solid fa-ban"></i>Ban</button></form>
            <?php endif ?>
            <form action="/admin/reset/<?= $user['id'] ?>" method="post" onsubmit="return confirm('Reset all progress for this user?')"><?= csrf_field() ?><button class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-rotate-left"></i>Reset</button></form>
            <form action="/admin/delete/<?= $user['id'] ?>" method="post" onsubmit="return confirm('Permanently delete this user? This cannot be undone.')"><?= csrf_field() ?><button class="btn btn-error btn-sm gap-1"><i class="fa-solid fa-trash"></i>Delete</button></form>
                <a href="/admin/impersonate/<?= $user['id'] ?>" class="btn btn-outline btn-sm gap-1" onclick="return confirm('Log in as this user?')"><i class="fa-solid fa-user-secret"></i>Impersonate</a>
        </div>
        <?php endif ?>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Top Row: Cash, Genepis, Reputation, Info -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-xs mb-2 text-base-content/50"><i class="fa-solid fa-coins mr-1 text-warning"></i>Cash</h2>
            <div class="text-xl font-bold mb-2"><?= currency($finance ? (int) $finance['cash'] : 0) ?></div>
            <form action="/admin/cash" method="post" class="flex gap-1"><?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="number" name="cash" value="<?= $finance ? $finance['cash'] : 500000 ?>" class="input input-bordered input-xs flex-1">
                <button class="btn btn-primary btn-xs">Set</button>
            </form>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-xs mb-2 text-base-content/50"><i class="fa-solid fa-seedling mr-1 text-success"></i>Genepis</h2>
            <div class="text-xl font-bold mb-2"><?= $genepis ? number_format((int) $genepis['balance']) : 0 ?></div>
            <?php if ($genepis) : ?>
            <form action="/admin/genepis" method="post" class="flex gap-1"><?= csrf_field() ?>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                <input type="number" name="genepis" value="<?= $genepis['balance'] ?>" class="input input-bordered input-xs flex-1">
                <button class="btn btn-primary btn-xs">Set</button>
            </form>
            <?php endif ?>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-xs mb-2 text-base-content/50"><i class="fa-solid fa-mountain mr-1 text-primary"></i>Resort</h2>
            <div class="space-y-1 text-xs mb-2">
                <div class="flex justify-between"><span class="text-base-content/50">Map</span><span><?= esc($finance["resort_map"] ?? "N/A") ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Difficulty</span><span class="badge badge-xs"><?= esc($finance["difficulty"] ?? "standard") ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Units</span><span><?= esc($finance["units"] ?? "metric") ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Open</span><span><?= ($finance["resort_open"] ?? 1) ? "Yes" : "No" ?></span></div>
            </div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-xs mb-2 text-base-content/50"><i class="fa-solid fa-user mr-1"></i>Info</h2>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between"><span class="text-base-content/50">Email</span><span class="truncate ml-2"><?= esc($identity['secret'] ?? 'N/A') ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Joined</span><span><?= date('M j, Y', strtotime($user['created_at'])) ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Difficulty</span><span><?= esc($finance['difficulty'] ?? 'standard') ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Units</span><span><?= esc($finance['units'] ?? 'metric') ?></span></div>
            </div>
        </div></div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-lg font-bold"><?= count($staff) ?></div><div class="text-xs text-base-content/50">Staff</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-lg font-bold"><?= count($buildings) ?></div><div class="text-xs text-base-content/50">Buildings</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-lg font-bold"><?= count($items) ?></div><div class="text-xs text-base-content/50">Lifts/Slopes</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-lg font-bold"><?= count($loans) ?></div><div class="text-xs text-base-content/50">Loans</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-lg font-bold"><?= count($achievements) ?></div><div class="text-xs text-base-content/50">Achievements</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-lg font-bold"><?= count($parking) + count($terrainParks) ?></div><div class="text-xs text-base-content/50">Facilities</div></div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Staff -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-users mr-1 text-info"></i>Staff (<?= count($staff) ?>)</h2>
            <?php if (empty($staff)) : ?>
                <p class="text-xs text-base-content/50">No staff hired.</p>
            <?php else : ?>
            <div class="overflow-y-auto max-h-48"><table class="table table-xs">
                <thead><tr><th>Role</th><th>Name</th><th>Salary</th><th>Morale</th></tr></thead>
                <tbody>
                <?php foreach ($staff as $s) : ?>
                    <tr><td class="text-xs"><?= esc($s['role'] ?? $s['type'] ?? '') ?></td><td class="text-xs"><?= esc($s['name'] ?? '') ?></td><td class="text-xs font-mono"><?= currency((int) ($s['salary'] ?? $s['daily_salary'] ?? 0)) ?></td><td class="text-xs"><?= $s['morale'] ?? '-' ?>%</td></tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
            <?php endif ?>
        </div></div>

        <!-- Lifts & Slopes -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-mountain mr-1 text-success"></i>Lifts & Slopes (<?= count($items) ?>)</h2>
            <?php if (empty($items)) : ?>
                <p class="text-xs text-base-content/50">Nothing built yet.</p>
            <?php else : ?>
            <div class="overflow-y-auto max-h-48"><table class="table table-xs">
                <thead><tr><th>Name</th><th>Type</th><th>Length</th><th>Status</th></tr></thead>
                <tbody>
                <?php foreach ($items as $it) : ?>
                    <tr><td class="text-xs"><?= esc($it['name'] ?? 'Unnamed') ?></td><td class="text-xs"><?= esc($it['item_type'] ?? $it['type'] ?? '') ?></td><td class="text-xs"><?= distance((int) ($it['length_meters'] ?? 0)) ?></td><td><span class="badge badge-xs badge-<?= ($it['status'] ?? 'open') === 'open' ? 'success' : 'warning' ?>"><?= $it['status'] ?? 'open' ?></span></td></tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
            <?php endif ?>
        </div></div>

        <!-- Buildings -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-building mr-1 text-warning"></i>Buildings (<?= count($buildings) ?>)</h2>
            <?php if (empty($buildings)) : ?>
                <p class="text-xs text-base-content/50">No buildings.</p>
            <?php else : ?>
            <div class="overflow-y-auto max-h-48"><table class="table table-xs">
                <thead><tr><th>Name</th><th>Type</th><th>Level</th></tr></thead>
                <tbody>
                <?php foreach ($buildings as $b) : ?>
                    <tr><td class="text-xs"><?= esc($b['name'] ?? '') ?></td><td class="text-xs"><?= esc($b['type'] ?? $b['building_type'] ?? '') ?></td><td class="text-xs"><?= $b['level'] ?? 1 ?></td></tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
            <?php endif ?>
        </div></div>

        <!-- Loans -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-landmark mr-1 text-error"></i>Active Loans (<?= count($loans) ?>)</h2>
            <?php if (empty($loans)) : ?>
                <p class="text-xs text-base-content/50">No active loans.</p>
            <?php else : ?>
            <div class="overflow-y-auto max-h-48">
                <?php foreach ($loans as $l) : ?>
                <div class="flex justify-between items-center py-1.5 border-b border-base-300 text-xs">
                    <span><?= esc($l['loan_type'] ?? 'Loan') ?></span>
                    <span class="font-mono"><?= currency((int) ($l['remaining'] ?? $l['amount'] ?? 0)) ?></span>
                    <span><?= $l['days_remaining'] ?? '?' ?> days left</span>
                </div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div></div>
    </div>

    <!-- Achievements -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-trophy mr-1 text-warning"></i>Achievements (<?= count($achievements) ?>)</h2>
        <?php if (empty($achievements)) : ?>
            <p class="text-xs text-base-content/50">No achievements earned.</p>
        <?php else : ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($achievements as $a) : ?>
                <span class="badge badge-sm badge-outline"><?= esc($a['achievement_key'] ?? $a['name'] ?? $a['id']) ?></span>
            <?php endforeach ?>
        </div>
        <?php endif ?>
    </div></div>

    <!-- Tutorial Progress -->
    <?php if ($tutorial) : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-sm"><i class="fa-solid fa-graduation-cap mr-1"></i>Tutorial</h2>
            <div class="flex items-center gap-2">
                <span class="text-xs text-base-content/50">Step <?= $tutorial['current_step'] ?? '?' ?></span>
                <form action="/admin/reset-tutorial/<?= $user['id'] ?>" method="post" onsubmit="return confirm('Reset tutorial progress?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs">Reset</button></form>
            </div>
        </div>
    </div></div>
    <?php endif ?>

    <!-- Activity Log -->
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
        <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Activity (last 50)</h2>
        <div class="divide-y divide-base-300 max-h-96 overflow-y-auto">
        <?php foreach ($logs as $log) : ?>
            <div class="flex items-center gap-2 py-1.5"><i class="<?= $log['icon'] ?> text-xs text-base-content/50 w-4 text-center"></i><span class="flex-1 text-sm truncate"><?= esc($log['message']) ?></span><span class="text-xs text-base-content/40 shrink-0">D<?= $log['game_day'] ?></span></div>
        <?php endforeach ?>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
