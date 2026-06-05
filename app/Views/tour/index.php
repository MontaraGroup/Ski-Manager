<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($owner['username']) ?>'s Resort - Tour<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$qualityColors = ['powder' => 'badge-primary', 'groomed' => 'badge-success', 'packed' => 'badge-info', 'icy' => 'badge-warning', 'bare' => 'badge-error'];
$qualityIcons = ['powder' => 'fa-snowflake', 'groomed' => 'fa-check-circle', 'packed' => 'fa-compress', 'icy' => 'fa-icicles', 'bare' => 'fa-mountain'];
$diffColors = ['green' => 'badge-success', 'blue' => 'badge-info', 'black' => 'badge-neutral', 'double_black' => 'badge-error'];
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="/leaderboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-arrow-left"></i></a>
                <h1 class="text-2xl md:text-3xl font-bold"><i class="fa-solid fa-binoculars mr-2 text-primary"></i><?= esc($owner['username']) ?>'s Resort</h1>
            </div>
            <p class="text-base-content/60 text-sm ml-10">
                <?php for ($i = 0; $i < 5; $i++) : ?>
                    <i class="fa-solid fa-star text-xs <?= $i < $rating['stars'] ? 'text-warning' : 'text-base-content/20' ?>"></i>
                <?php endfor ?>
                <span class="ml-1"><?= $rating['score'] ?>/<?= $rating['max'] ?> rating</span>
            </p>
        </div>
        <div class="flex items-center gap-2 mt-3 md:mt-0">
            <form action="/tour/<?= $owner['id'] ?>/like" method="post">
                <?= csrf_field() ?>
                <button class="btn <?= $hasLiked ? 'btn-error' : 'btn-outline' ?> btn-sm gap-1">
                    <i class="fa-<?= $hasLiked ? 'solid' : 'regular' ?> fa-heart"></i>
                    <?= $hasLiked ? 'Liked' : 'Like' ?>
                    <span class="badge badge-sm ml-1"><?= $likeCount ?></span>
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-info"><?= $openSlopes ?></div><div class="text-xs text-base-content/50">Open Slopes</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><?= $openLifts ?></div><div class="text-xs text-base-content/50">Open Lifts</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><?= $staffCount ?></div><div class="text-xs text-base-content/50">Staff</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-primary"><?= $buildingCount ?></div><div class="text-xs text-base-content/50">Buildings</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-secondary"><?= $parks ?></div><div class="text-xs text-base-content/50">Parks</div></div></div>
    </div>

    <?php foreach ($sectors as $sectorNum => $sector) : ?>
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body p-4">
            <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-mountain mr-1"></i>Sector <?= $sectorNum ?></h2>
            <?php if (!empty($sector['slopes'])) : ?>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Slope</th><th>Difficulty</th><th>Snow</th><th>Condition</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($sector['slopes'] as $slope) : ?>
                        <tr>
                            <td class="font-medium"><?= esc($slope['name']) ?></td>
                            <td><span class="badge <?= $diffColors[$slope['difficulty']] ?? 'badge-ghost' ?> badge-sm"><?= ucfirst($slope['difficulty'] ?? '-') ?></span></td>
                            <td><span class="badge <?= $qualityColors[$slope['snow_quality'] ?? 'packed'] ?> badge-sm gap-1"><i class="fa-solid <?= $qualityIcons[$slope['snow_quality'] ?? 'packed'] ?> text-[10px]"></i><?= ucfirst($slope['snow_quality'] ?? 'packed') ?></span></td>
                            <td><progress class="progress <?= $slope['condition_pct'] >= 60 ? 'progress-success' : ($slope['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $slope['condition_pct'] ?>" max="100"></progress> <span class="text-xs"><?= $slope['condition_pct'] ?>%</span></td>
                            <td><span class="badge <?= $slope['status'] === 'open' ? 'badge-success' : 'badge-error' ?> badge-sm"><?= ucfirst($slope['status']) ?></span></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php endif ?>
            <?php if (!empty($sector['lifts'])) : ?>
            <div class="overflow-x-auto mt-2">
                <table class="table table-sm">
                    <thead><tr><th>Lift</th><th>Type</th><th>Capacity</th><th>Condition</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($sector['lifts'] as $lift) : ?>
                        <tr>
                            <td class="font-medium"><?= esc($lift['name']) ?></td>
                            <td class="text-sm"><?= ucfirst(str_replace('_', ' ', $lift['subtype'])) ?></td>
                            <td><?= $lift['capacity'] ?>/hr</td>
                            <td><progress class="progress <?= $lift['condition_pct'] >= 60 ? 'progress-success' : ($lift['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $lift['condition_pct'] ?>" max="100"></progress> <span class="text-xs"><?= $lift['condition_pct'] ?>%</span></td>
                            <td><span class="badge <?= $lift['status'] === 'open' ? 'badge-success' : 'badge-error' ?> badge-sm"><?= ucfirst($lift['status']) ?></span></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
            <?php endif ?>
        </div>
    </div>
    <?php endforeach ?>

    <?php if (empty($sectors)) : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body text-center text-base-content/40 py-12"><i class="fa-solid fa-mountain text-4xl mb-3"></i><p>This resort hasn't built anything yet.</p></div></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
