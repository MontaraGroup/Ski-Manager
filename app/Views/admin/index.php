<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Panel<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8 pb-12">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-3">
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved mr-2 text-error"></i>Admin</h1>
            <span class="badge badge-outline">Day <?= $gameDay ?></span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/broadcast" class="btn btn-warning btn-sm gap-1"><i class="fa-solid fa-bullhorn"></i>Broadcast</a>
            <a href="/admin/settings" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-gear"></i>Settings</a>
            <a href="/admin/economy" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-chart-line"></i>Economy</a>
            <a href="/admin/activity" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-clock-rotate-left"></i>Logs</a>
            <a href="/admin/audit" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-shield-halved"></i>Audit</a>
            <a href="/admin/compare" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-code-compare"></i>Compare</a>
            <a href="/admin/changelogs" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-newspaper"></i>Changelogs</a>
            <a href="/admin/export-players" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-download"></i>Export</a>
            <a href="/admin/features" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-toggle-on"></i>Flags</a>
            <a href="/admin/suspicious" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-triangle-exclamation"></i>Suspicious</a>
            <a href="/admin/seasons" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-calendar-plus"></i>Seasons</a>
            <a href="/admin/support" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-headset"></i>Support<?php $__unreadSupport = db_connect()->table("support_messages")->where("sender", "player")->where("is_read", 0)->countAllResults(); if ($__unreadSupport > 0) : ?> <span class="badge badge-error badge-xs"><?= $__unreadSupport ?></span><?php endif ?></a>
            <form action="/admin/trigger-tick" method="post" class="inline" onsubmit="return confirm('Run game tick now?')"><?= csrf_field() ?><button class="btn btn-error btn-sm gap-1"><i class="fa-solid fa-play"></i>Run Tick</button></form>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <?php $__season = db_connect()->table("seasons")->where("active", 1)->get()->getRowArray(); ?>
    <div class="flex flex-wrap gap-2 mb-4">
        <form action="/admin/maintenance" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-sm <?= ($__season["maintenance"] ?? 0) ? "btn-error" : "btn-outline" ?> gap-1"><i class="fa-solid fa-wrench"></i><?= ($__season["maintenance"] ?? 0) ? "Maintenance ON" : "Maintenance Off" ?></button></form>
        <form action="/admin/toggle-env" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-sm <?= ENVIRONMENT === 'development' ? 'btn-warning' : 'btn-outline' ?> gap-1"><i class="fa-solid fa-code"></i><?= ENVIRONMENT === 'development' ? 'DEV Mode' : 'PROD Mode' ?></button></form>
        <div class="badge badge-outline gap-1 self-center"><i class="fa-solid fa-clock"></i> Day <?= $gameDay ?> / <?= $__season["duration_days"] ?? 135 ?></div>
        <div class="badge badge-outline gap-1 self-center"><i class="fa-solid fa-calendar"></i> Started <?= $__season["start_date"] ?? "N/A" ?></div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><i class="fa-solid fa-users text-base-content/30 mr-1"></i><?= $totalUsers ?></div><div class="text-xs text-base-content/50">Players</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><i class="fa-solid fa-signal mr-1"></i><?= $onlineRecent ?></div><div class="text-xs text-base-content/50">Active 24h</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><?= currency($totalCash) ?></div><div class="text-xs text-base-content/50">Total Cash</div></div></div>
        <?php $__avgCash = $totalUsers > 0 ? (int)($totalCash / $totalUsers) : 0; $__lastTick = db_connect()->table("activity_log")->orderBy("created_at","DESC")->limit(1)->get()->getRowArray(); ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-info"><?= currency($__avgCash) ?></div><div class="text-xs text-base-content/50">Avg Cash</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><i class="fa-solid fa-file-invoice-dollar mr-1"></i><?= $totalLoans ?></div><div class="text-xs text-base-content/50">Active Loans</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalStaff ?></div><div class="text-xs text-base-content/50">Staff</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalBuildings ?></div><div class="text-xs text-base-content/50">Buildings</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalItems ?></div><div class="text-xs text-base-content/50">Slopes/Lifts</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= $totalParking + $totalParks ?></div><div class="text-xs text-base-content/50">Parking/Parks</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold <?= $__lastTick ? "text-success" : "text-error" ?>"><?= $__lastTick ? "D" . $__lastTick["game_day"] : "N/A" ?></div><div class="text-xs text-base-content/50">Last Tick</div></div></div>
    </div>

    <?php if ($weather) : ?>
    <?php
        $__cond = $weather['condition_name'] ?? '';
        $__wicon = match(true) {
            stripos($__cond, 'blizzard') !== false => 'fa-wind text-info',
            stripos($__cond, 'heavy snow') !== false => 'fa-snowflake text-info',
            stripos($__cond, 'snow') !== false => 'fa-snowflake text-info',
            stripos($__cond, 'rain') !== false => 'fa-cloud-rain text-info',
            stripos($__cond, 'fog') !== false => 'fa-smog text-base-content/50',
            stripos($__cond, 'cloud') !== false => 'fa-cloud text-base-content/50',
            stripos($__cond, 'sun') !== false => 'fa-sun text-warning',
            default => 'fa-cloud-sun text-warning',
        };
    ?>
    <div class="card bg-gradient-to-r from-info/10 to-primary/10 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?= $__wicon ?> text-3xl"></i>
                <div>
                    <div class="font-bold"><?= esc($__cond) ?></div>
                    <div class="text-xs text-base-content/50">Today on the mountain · Day <?= $gameDay ?></div>
                </div>
            </div>
            <div class="flex items-center gap-5 text-sm">
                <div class="text-center"><div class="font-bold text-lg"><?= temp($weather['temp']) ?></div><div class="text-[10px] text-base-content/50"><i class="fa-solid fa-temperature-half mr-1"></i>Temp</div></div>
                <div class="text-center"><div class="font-bold text-lg"><?= speed($weather['wind']) ?></div><div class="text-[10px] text-base-content/50"><i class="fa-solid fa-wind mr-1"></i>Wind</div></div>
                <div class="text-center"><div class="font-bold text-lg"><?= snow($weather['snow_base']) ?></div><div class="text-[10px] text-base-content/50"><i class="fa-solid fa-layer-group mr-1"></i>Snow Base</div></div>
            </div>
        </div>
    </div></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold"><i class="fa-solid fa-users mr-1"></i>Players <span class="badge badge-ghost badge-sm"><?= count($users) ?></span></h2>
                <input type="text" id="playerSearch" placeholder="Search players..." class="input input-bordered input-xs w-48">
            </div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-auto max-h-[600px]">
                <table class="table table-sm table-pin-rows">
                    <thead class="sticky top-0 bg-base-100 z-10"><tr><th>ID</th><th>Username</th><th>Cash</th><th>Staff</th><th>Buildings</th><th>Items</th><th>Joined</th><th>Last Active</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $u) : ?>
                        <tr class="player-row <?= isset($u['active']) && !$u['active'] ? 'opacity-40' : '' ?>">
                            <td><?= $u['id'] ?></td>
                            <td class="font-semibold"><?= esc($u['username']) ?> <?= isset($u['active']) && !$u['active'] ? '<span class="badge badge-error badge-xs">Banned</span>' : '' ?></td>
                            <td class="font-mono text-xs"><?= currency($u['cash']) ?></td>
                            <td><?= $u['staff_count'] ?></td>
                            <td><?= $u['building_count'] ?></td>
                            <td><?= $u['item_count'] ?></td>
                            <td class="text-xs text-base-content/50"><?= date('M j', strtotime($u['created_at'])) ?></td>
                            <td class="text-xs <?= isset($u['last_active']) && $u['last_active'] && strtotime($u['last_active']) > strtotime('-1 hour') ? 'text-success font-semibold' : 'text-base-content/50' ?>"><?php $__online = isset($u['last_active']) && $u['last_active'] && strtotime($u['last_active']) > strtotime('-15 minutes'); ?><?php if ($__online) : ?><span class="inline-block w-2 h-2 rounded-full bg-success mr-1" title="Online now"></span><?php endif ?><?= isset($u['last_active']) && $u['last_active'] ? date('M j, g:ia', strtotime($u['last_active'])) : 'Never' ?></td>
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
                    <tr id="noResults" style="display:none"><td colspan="9" class="text-center text-base-content/40 py-6">No players match your search.</td></tr></tbody>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-cloud-sun mr-1 text-info"></i>Set Weather</h2>
            <form action="/admin/weather" method="post"><?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-2 mb-3">
                    <div><label class="label text-xs py-0">Condition</label>
                    <select name="condition" class="select select-bordered select-xs w-full">
                        <option value="Sunny">Sunny</option>
                        <option value="Partly Cloudy">Partly Cloudy</option>
                        <option value="Cloudy">Cloudy</option>
                        <option value="Light Snow" selected>Light Snow</option>
                        <option value="Heavy Snow">Heavy Snow</option>
                        <option value="Blizzard">Blizzard</option>
                        <option value="Freezing Rain">Freezing Rain</option>
                    </select></div>
                    <div><label class="label text-xs py-0">Temp (C)</label>
                    <input type="number" name="temp" value="-5" class="input input-bordered input-xs w-full" min="-30" max="30"></div>
                    <div><label class="label text-xs py-0">Wind (km/h)</label>
                    <input type="number" name="wind" value="15" class="input input-bordered input-xs w-full" min="0" max="120"></div>
                    <div><label class="label text-xs py-0">Snowfall (cm)</label>
                    <input type="number" name="snowfall" value="0" class="input input-bordered input-xs w-full" min="0" max="50"></div>
                    <div><label class="label text-xs py-0">Snow Base (cm)</label>
                    <input type="number" name="snow_base" value="50" class="input input-bordered input-xs w-full" min="0" max="300"></div>
                </div>
                <button class="btn btn-info btn-sm w-full gap-1"><i class="fa-solid fa-cloud-sun"></i>Apply Weather</button>
            </form>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-bolt mr-1 text-warning"></i>Bulk Actions <span class="badge badge-warning badge-xs">All Players</span></h2>
            <div class="space-y-3">
                <form action="/admin/add-cash-all" method="post" onsubmit="return confirm('Add ' + this.amount.value + ' cash to ALL players? This affects the economy and leaderboard.')"><?= csrf_field() ?>
                    <label class="label text-xs py-0"><span><i class="fa-solid fa-coins text-success mr-1"></i>Give Cash to Everyone</span></label>
                    <div class="join w-full">
                        <input type="number" name="amount" value="10000" min="1" max="10000000" class="input input-bordered input-xs join-item flex-1" placeholder="Amount">
                        <button class="btn btn-success btn-xs join-item gap-1"><i class="fa-solid fa-paper-plane"></i>Give</button>
                    </div>
                </form>
                <form action="/admin/add-genepis-all" method="post" onsubmit="return confirm('Add ' + this.amount.value + ' Genepis to ALL players?')"><?= csrf_field() ?>
                    <label class="label text-xs py-0"><span><i class="fa-solid fa-seedling text-info mr-1"></i>Give Genepis to Everyone</span></label>
                    <div class="join w-full">
                        <input type="number" name="amount" value="100" min="1" max="100000" class="input input-bordered input-xs join-item flex-1" placeholder="Amount">
                        <button class="btn btn-info btn-xs join-item gap-1"><i class="fa-solid fa-paper-plane"></i>Give</button>
                    </div>
                </form>
                <div class="divider my-1 text-[10px] text-base-content/40">Single Player</div>
                <form action="/admin/grant-achievement" method="post" onsubmit="return confirm('Grant achievement to user #' + this.user_id.value + '?')"><?= csrf_field() ?>
                    <label class="label text-xs py-0"><span><i class="fa-solid fa-trophy text-warning mr-1"></i>Grant Achievement</span></label>
                    <div class="join w-full">
                        <input type="number" name="user_id" min="1" class="input input-bordered input-xs join-item flex-1" placeholder="User ID" required>
                        <input type="number" name="achievement_id" min="1" class="input input-bordered input-xs join-item w-24" placeholder="Ach. ID" required>
                        <button class="btn btn-warning btn-xs join-item gap-1"><i class="fa-solid fa-check"></i>Grant</button>
                    </div>
                </form>
            </div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-server mr-1 text-primary"></i>Server Health</h2>
            <?php
                $__diskTotal = @disk_total_space("/") ?: 0;
                $__diskFree  = @disk_free_space("/") ?: 0;
                $__diskUsed  = $__diskTotal - $__diskFree;
                $__diskPct   = $__diskTotal > 0 ? round($__diskUsed / $__diskTotal * 100) : 0;
                $__load      = function_exists("sys_getloadavg") ? sys_getloadavg() : [0,0,0];
                $__cores     = (int) (@shell_exec("nproc") ?: 1); if ($__cores < 1) $__cores = 1;
                $__memLimit  = ini_get("memory_limit");
                $__memPeak   = round(memory_get_peak_usage(true) / 1048576, 1);
                $__dbName    = db_connect()->getDatabase();
                $__dbSize    = db_connect()->query("SELECT ROUND(SUM(data_length + index_length)/1048576, 1) AS mb FROM information_schema.tables WHERE table_schema = ?", [$__dbName])->getRowArray()["mb"] ?? 0;
                $__gb = fn($b) => number_format($b / 1073741824, 1) . " GB";
            ?>
            <!-- Disk -->
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1"><span class="text-base-content/50"><i class="fa-solid fa-hard-drive mr-1"></i>Disk</span><span class="font-mono"><?= $__gb($__diskUsed) ?> / <?= $__gb($__diskTotal) ?></span></div>
                <progress class="progress <?= $__diskPct > 85 ? "progress-error" : ($__diskPct > 70 ? "progress-warning" : "progress-success") ?> w-full" value="<?= $__diskPct ?>" max="100"></progress>
            </div>
            <!-- Load -->
            <div class="mb-3">
                <div class="flex justify-between text-xs mb-1"><span class="text-base-content/50"><i class="fa-solid fa-gauge-high mr-1"></i>Load (1/5/15m)</span><span class="font-mono <?= $__load[0] > $__cores ? "text-error" : "" ?>"><?= implode(" / ", array_map(fn($l) => number_format($l, 2), $__load)) ?></span></div>
                <div class="text-[10px] text-base-content/40"><?= $__cores ?> CPU core<?= $__cores === 1 ? "" : "s" ?></div>
            </div>
            <div class="space-y-1 text-xs border-t border-base-300 pt-2">
                <div class="flex justify-between"><span class="text-base-content/50">PHP</span><span class="font-mono"><?= PHP_VERSION ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">CI4</span><span class="font-mono"><?= \CodeIgniter\CodeIgniter::CI_VERSION ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Memory (peak/limit)</span><span class="font-mono"><?= $__memPeak ?>M / <?= $__memLimit ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Database</span><span class="font-mono">MySQL <?= db_connect()->getVersion() ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">DB Size</span><span class="font-mono"><?= $__dbSize ?> MB</span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Timezone</span><span class="font-mono"><?= date_default_timezone_get() ?></span></div>
                <div class="flex justify-between"><span class="text-base-content/50">Server Time</span><span class="font-mono"><?= date("g:ia") ?></span></div>
            </div>
            <a href="/admin/errors" class="btn btn-outline btn-xs w-full mt-3 gap-1"><i class="fa-solid fa-bug"></i>View Error Log</a>
        </div></div>
    </div>
</div>
<script>
document.getElementById('playerSearch').addEventListener('input', function(){
    var q = this.value.toLowerCase();
    var shown = 0;
    document.querySelectorAll('table.table tbody tr.player-row').forEach(function(r){
        var match = r.textContent.toLowerCase().includes(q);
        r.style.display = match ? '' : 'none';
        if (match) shown++;
    });
    var empty = document.getElementById('noResults');
    if (empty) empty.style.display = shown === 0 ? '' : 'none';
});
</script>
<?= $this->endSection() ?>
