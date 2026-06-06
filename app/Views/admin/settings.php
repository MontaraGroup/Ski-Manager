<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Game Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$db = db_connect();
$season = $db->table('seasons')->where('active', 1)->get()->getRowArray();
$sectors = $db->table('resort_sectors')->where('resort_map', $season['resort_map'] ?? 'ParkCity')->orderBy('sort_order')->get()->getResultArray();
$segCount = $db->table('map_segments')->where('active', 1)->where('resort_map', $season['resort_map'] ?? 'ParkCity')->countAllResults();
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-gear mr-2"></i>Game Settings</h1>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Season Controls -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-calendar-days mr-1 text-primary"></i>Season Settings</h2>
            <?php if ($season) : ?>
            <form action="/admin/season" method="post">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="label text-xs">Name</label>
                        <input type="text" name="name" value="<?= esc($season['name']) ?>" class="input input-sm input-bordered w-full">
                    </div>
                    <div>
                        <label class="label text-xs">Start Date</label>
                        <input type="date" name="start_date" value="<?= $season['start_date'] ?>" class="input input-sm input-bordered w-full">
                    </div>
                    <div>
                        <label class="label text-xs">Total Days</label>
                        <input type="number" name="duration_days" value="<?= $season['duration_days'] ?>" class="input input-sm input-bordered w-full">
                    </div>
                    <div>
                        <label class="label text-xs">Winter Days</label>
                        <input type="number" name="winter_days" value="<?= $season['winter_days'] ?>" class="input input-sm input-bordered w-full">
                    </div>
                </div>
                <div class="flex justify-between items-center text-xs text-base-content/50 mb-3">
                    <span>Resort: <?= esc($season['resort_map']) ?></span>
                    <span>Season #<?= $season['season_number'] ?></span>
                </div>
                <button class="btn btn-primary btn-sm w-full">Save Season</button>
            </form>
            <?php else : ?>
            <p class="text-sm text-base-content/50">No active season.</p>
            <?php endif ?>
        </div></div>

        <!-- Sector Release -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-layer-group mr-1 text-warning"></i>Sectors</h2>
            <?php if (empty($sectors)) : ?>
                <p class="text-sm text-base-content/50">No sectors found.</p>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($sectors as $sec) : ?>
                    <div class="flex items-center justify-between p-2 rounded-lg bg-base-200/50">
                        <div class="flex items-center gap-2">
                            <span style="width:10px;height:10px;border-radius:50%;background:<?= esc($sec['color']) ?>;display:inline-block"></span>
                            <span class="text-sm font-medium"><?= esc($sec['name']) ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="badge badge-xs <?= $sec['visible'] ? 'badge-success' : 'badge-ghost' ?>"><?= $sec['visible'] ? 'Visible' : 'Hidden' ?></span>
                            <form action="/admin/sector-release/<?= $sec['id'] ?>" method="post" class="inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-xs <?= $sec['released'] ? 'btn-success' : 'btn-outline' ?>">
                                    <?= $sec['released'] ? 'Released' : 'Locked' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div></div>

        <!-- Recent Weather -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-cloud-sun mr-1 text-info"></i>Recent Weather</h2>
            <div class="overflow-x-auto"><table class="table table-xs">
                <thead><tr><th>Day</th><th>Condition</th><th>Temp</th><th>Wind</th><th>Snow</th></tr></thead>
                <tbody>
                <?php foreach ($weather as $w) : ?>
                    <tr><td><?= $w['game_day'] ?></td><td><?= $w['condition_name'] ?></td><td><?= temp($w['temp']) ?></td><td><?= speed($w['wind']) ?></td><td><?= snow($w['snow_base']) ?></td></tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
        </div></div>

        <!-- Map Segments -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-map mr-1 text-primary"></i>Map Segments</h2>
            <p class="text-sm text-base-content/50 mb-2"><?= $segCount ?> active segments</p>
            <a href="/map" class="btn btn-outline btn-sm">Manage Map</a>
        </div></div>

        <!-- Quick Links -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-link mr-1"></i>Quick Links</h2>
            <div class="flex flex-wrap gap-2">
                <a href="/admin/errors" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-bug"></i>Error Log</a>
                <a href="/admin/broadcast" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-bullhorn"></i>Broadcast</a>
                <a href="/admin/economy" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-chart-line"></i>Economy</a>
                <a href="/admin/activity" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-clock-rotate-left"></i>Logs</a>
            </div>
        </div></div>

        <!-- Tournaments -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-trophy mr-1 text-warning"></i>Tournaments</h2>
            <div class="space-y-1">
            <?php foreach ($tournaments as $t) : ?>
                <div class="flex justify-between text-xs"><span><?= esc($t['name']) ?></span><span class="badge badge-<?= $t['status'] === 'active' ? 'success' : ($t['status'] === 'upcoming' ? 'info' : 'ghost') ?> badge-xs"><?= $t['status'] ?></span></div>
            <?php endforeach ?>
            </div>
        </div></div>

    </div>
</div>
<?= $this->endSection() ?>
