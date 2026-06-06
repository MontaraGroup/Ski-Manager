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
            <a href="/admin/audit" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-shield-halved"></i>Audit</a>
            <a href="/admin/compare" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-code-compare"></i>Compare</a>
            <a href="/admin/changelogs" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-newspaper"></i>Changelogs</a>
            <a href="/admin/export-players" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-download"></i>Export CSV</a>
            <form action="/admin/trigger-tick" method="post" class="inline" onsubmit="return confirm('Run game tick now?')"><?= csrf_field() ?><button class="btn btn-error btn-sm gap-1"><i class="fa-solid fa-play"></i>Run Tick</button></form>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <?php $__season = db_connect()->table("seasons")->where("active", 1)->get()->getRowArray(); ?>
    <div class="flex gap-2 mb-4">
        <form action="/admin/maintenance" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-sm <?= ($__season["maintenance"] ?? 0) ? "btn-error" : "btn-outline" ?> gap-1"><i class="fa-solid fa-wrench"></i><?= ($__season["maintenance"] ?? 0) ? "Maintenance ON" : "Maintenance Off" ?></button></form>
        <form action="/admin/toggle-env" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-sm <?= ENVIRONMENT === 'development' ? 'btn-warning' : 'btn-outline' ?> gap-1"><i class="fa-solid fa-code"></i><?= ENVIRONMENT === 'development' ? 'DEV Mode' : 'PROD Mode' ?></button></form>
        <div class="badge badge-outline gap-1 self-center"><i class="fa-solid fa-clock"></i> Day <?= $gameDay ?> / <?= $__season["duration_days"] ?? 135 ?></div>
        <div class="badge badge-outline gap-1 self-center"><i class="fa-solid fa-calendar"></i> Started <?= $__season["start_date"] ?? "N/A" ?></div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalUsers ?></div><div class="text-xs text-base-content/50">Players</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><?= currency($totalCash) ?></div><div class="text-xs text-base-content/50">Total Cash</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalStaff ?></div><div class="text-xs text-base-content/50">Staff</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalBuildings ?></div><div class="text-xs text-base-content/50">Buildings</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalItems ?></div><div class="text-xs text-base-content/50">Slopes/Lifts</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><?= $totalLoans ?></div><div class="text-xs text-base-content/50">Active Loans</div></div></div>
        <?php $__avgCash = $totalUsers > 0 ? (int)($totalCash / $totalUsers) : 0; $__lastTick = db_connect()->table("activity_log")->orderBy("created_at","DESC")->limit(1)->get()->getRowArray(); ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-info"><?= currency($__avgCash) ?></div><div class="text-xs text-base-content/50">Avg Cash</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold <?= $__lastTick ? "text-success" : "text-error" ?>"><?= $__lastTick ? "D" . $__lastTick["game_day"] : "N/A" ?></div><div class="text-xs text-base-content/50">Last Tick</div></div></div>
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
            <input type="text" id="playerSearch" class="input input-sm input-bordered w-full mb-3" placeholder="Search players...">
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

    <!-- Quick Actions Row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Weather Override -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-cloud-sun mr-1 text-info"></i>Set Weather</h2>
            <form action="/admin/weather" method="post"><?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div><label class="label text-xs py-0">Condition</label>
                    <select name="condition" class="select select-bordered select-xs w-full">
                        <option value="sunny">Sunny</option><option value="cloudy">Cloudy</option>
                        <option value="snow" selected>Snow</option><option value="heavy_snow">Heavy Snow</option>
                        <option value="blizzard">Blizzard</option><option value="rain">Rain</option>
                        <option value="fog">Fog</option><option value="windy">Windy</option>
                    </select></div>
                    <div><label class="label text-xs py-0">Temp (C)</label>
                    <input type="number" name="temp" value="-5" class="input input-bordered input-xs w-full" min="-30" max="30"></div>
                    <div><label class="label text-xs py-0">Wind (km/h)</label>
                    <input type="number" name="wind" value="15" class="input input-bordered input-xs w-full" min="0" max="120"></div>
                    <div><label class="label text-xs py-0">Snow Base (cm)</label>
                    <input type="number" name="snow_base" value="50" class="input input-bordered input-xs w-full" min="0" max="300"></div>
                </div>
                <button class="btn btn-info btn-sm w-full gap-1"><i class="fa-solid fa-cloud-sun"></i>Apply Weather</button>
            </form>
        </div></div>

        <!-- Bulk Actions -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-bolt mr-1 text-warning"></i>Bulk Actions</h2>
            <div class="space-y-2">
                <form action="/admin/add-cash-all" method="post" class="flex gap-2" onsubmit="return confirm('Add cash to ALL players?')"><?= csrf_field() ?>
                    <input type="number" name="amount" value="10000" class="input input-bordered input-xs flex-1" placeholder="Amount">
                    <button class="btn btn-success btn-xs gap-1"><i class="fa-solid fa-coins"></i>Cash All</button>
                </form>
                <form action="/admin/grant-achievement" method="post" class="flex gap-2"><?= csrf_field() ?>
                    <input type="text" name="achievement" class="input input-bordered input-xs flex-1" placeholder="Achievement key">
                    <input type="number" name="user_id" class="input input-bordered input-xs w-20" placeholder="User ID">
                    <button class="btn btn-warning btn-xs gap-1"><i class="fa-solid fa-trophy"></i>Grant</button>
                </form>
            </div>
        </div></div>

        <!-- Server Info -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-server mr-1 text-primary"></i>Server</h2>
            <div class="space-y-1 text-xs">
                <div class="flex justify-between"><span class="text-base-content/50">PHP</span><span class="font-mono"><?= PHP_VERSION ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">CI4</span><span class="font-mono"><?= \CodeIgniter\CodeIgniter::CI_VERSION ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Memory</span><span class="font-mono"><?= round(memory_get_peak_usage(true) / 1048576, 1) ?> MB</span></div>
                <div class="flex justify-between"><span class="text-base-content/50">DB</span><span class="font-mono">MySQL <?= db_connect()->getVersion() ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Timezone</span><span class="font-mono"><?= date_default_timezone_get() ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Time</span><span class="font-mono"><?= date("Y-m-d H:i:s") ?></span></div>
            </div>
            <a href="/admin/errors" class="btn btn-outline btn-xs w-full mt-3 gap-1"><i class="fa-solid fa-bug"></i>View Error Log</a>
        </div></div>

    </div>
<?= $this->endSection() ?>
<script data-cfasync="false">
document.getElementById('playerSearch').addEventListener('input',function(){
    var q=this.value.toLowerCase();
    document.querySelectorAll('table.table tbody tr').forEach(function(r){
        r.style.display=r.textContent.toLowerCase().includes(q)?'':'none';
    });
});
</script>
