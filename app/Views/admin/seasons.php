<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Season Planner<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-calendar-plus mr-2 text-primary"></i>Season Planner</h1>
    </div>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <h2 class="font-bold text-sm mb-3">All Seasons</h2>
        <div class="overflow-x-auto"><table class="table table-sm">
            <thead><tr><th>#</th><th>Name</th><th>Resort</th><th>Start</th><th>Days</th><th>Winter</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($seasons as $s) : ?>
                <tr class="<?= $s['active'] ? 'bg-success/10' : '' ?>">
                    <td><?= $s['season_number'] ?></td><td class="font-semibold"><?= esc($s['name']) ?></td><td><?= esc($s['resort_map']) ?></td><td class="text-xs"><?= $s['start_date'] ?></td><td><?= $s['duration_days'] ?></td><td><?= $s['winter_days'] ?></td>
                    <td><?= $s['active'] ? '<span class="badge badge-success badge-sm">Active</span>' : '<span class="badge badge-ghost badge-sm">Planned</span>' ?></td>
                    <td><?php if (!$s['active']) : ?><form action="/admin/seasons/activate/<?= $s['id'] ?>" method="post" class="inline" onsubmit="return confirm('Activate this season?')"><?= csrf_field() ?><button class="btn btn-success btn-xs">Activate</button></form><?php endif ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table></div>
    </div></div>
    <div class="card bg-base-100 shadow-sm"><div class="card-body">
        <h2 class="font-bold text-sm mb-3">Plan Next Season</h2>
        <form action="/admin/seasons/create" method="post">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <div><label class="label text-xs">Season #</label><input type="number" name="season_number" value="<?= count($seasons) + 1 ?>" class="input input-sm input-bordered w-full" required></div>
                <div><label class="label text-xs">Name</label><input type="text" name="name" placeholder="Season 2: Deer Valley" class="input input-sm input-bordered w-full" required></div>
                <div><label class="label text-xs">Resort</label><select name="resort_map" class="select select-sm select-bordered w-full"><?php foreach ($resortMaps as $key => $name) : ?><option value="<?= $key ?>"><?= esc($name) ?></option><?php endforeach ?></select></div>
                <div><label class="label text-xs">Start Date</label><input type="date" name="start_date" class="input input-sm input-bordered w-full" required></div>
                <div><label class="label text-xs">Total Days</label><input type="number" name="duration_days" value="135" class="input input-sm input-bordered w-full" required></div>
                <div><label class="label text-xs">Winter Days</label><input type="number" name="winter_days" value="100" class="input input-sm input-bordered w-full" required></div>
            </div>
            <button class="btn btn-primary btn-sm w-full">Create Season</button>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>
