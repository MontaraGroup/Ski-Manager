<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Game Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-gear mr-2"></i>Game Settings</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-cloud-sun mr-1 text-info"></i>Recent Weather</h2>
            <div class="overflow-x-auto"><table class="table table-xs">
                <thead><tr><th>Day</th><th>Condition</th><th>Temp</th><th>Wind</th><th>Snow Base</th></tr></thead>
                <tbody>
                <?php foreach ($weather as $w) : ?>
                    <tr><td><?= $w['game_day'] ?></td><td><?= $w['condition_name'] ?></td><td><?= $w['temp'] ?>°C</td><td><?= $w['wind'] ?> km/h</td><td><?= $w['snow_base'] ?> cm</td></tr>
                <?php endforeach ?>
                </tbody>
            </table></div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-map mr-1 text-primary"></i>Map Segments</h2>
            <p class="text-sm text-base-content/50 mb-2"><?= count($segments) ?> active segments</p>
            <a href="/map" class="btn btn-outline btn-sm">Manage Map</a>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-trophy mr-1 text-warning"></i>Tournaments</h2>
            <div class="space-y-1">
            <?php foreach ($tournaments as $t) : ?>
                <div class="flex justify-between text-xs"><span><?= esc($t['name']) ?></span><span class="badge badge-<?= $t['status'] === 'active' ? 'success' : ($t['status'] === 'upcoming' ? 'info' : 'ghost') ?> badge-xs"><?= $t['status'] ?></span></div>
            <?php endforeach ?>
            </div>
        </div></div>

        <div class="card bg-base-100 shadow-sm"><div class="card-body">
            <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-calendar-days mr-1"></i>Special Events</h2>
            <div class="space-y-1">
            <?php foreach ($events as $e) : ?>
                <div class="flex justify-between text-xs"><span><?= esc($e['name']) ?> (Day <?= $e['game_day'] ?>)</span><span class="badge badge-<?= $e['active'] ? 'success' : 'ghost' ?> badge-xs"><?= $e['active'] ? 'Active' : 'Inactive' ?></span></div>
            <?php endforeach ?>
            </div>
        </div></div>
    </div>
</div>
<?= $this->endSection() ?>
