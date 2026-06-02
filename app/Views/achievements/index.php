<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Achievements<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div><h1 class="text-2xl font-bold"><i class="fa-solid fa-award mr-2 text-warning"></i>Achievements</h1><p class="text-sm text-base-content/50"><?= $completed ?>/<?= $total ?> completed</p></div>
    </div>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <progress class="progress progress-warning w-full mb-6" value="<?= $completed ?>" max="<?= $total ?>"></progress>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
    <?php foreach ($achievements as $a) : ?>
        <div class="card bg-base-100 shadow-sm <?= $a['completed'] && $a['claimed'] ? 'opacity-60' : '' ?>"><div class="card-body p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg <?= $a['completed'] ? 'bg-warning/20' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                    <i class="<?= $a['icon'] ?> text-lg <?= $a['completed'] ? 'text-warning' : 'text-base-content/30' ?>"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-sm"><?= esc($a['name']) ?></div>
                    <div class="text-xs text-base-content/50"><?= esc($a['description']) ?></div>
                    <div class="flex items-center gap-2 mt-1">
                        <progress class="progress <?= $a['completed'] ? 'progress-warning' : 'progress-primary' ?> w-24" value="<?= $a['progress'] ?>" max="<?= $a['target'] ?>"></progress>
                        <span class="text-xs font-mono"><?= $a['progress'] ?>/<?= $a['target'] ?></span>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <div class="text-xs font-bold text-warning"><?= currency((int)$a['reward_amount']) ?></div>
                    <?php if ($a['completed'] && !$a['claimed']) : ?>
                        <form action="/achievements/claim/<?= $a['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-warning btn-xs mt-1">Claim</button></form>
                    <?php elseif ($a['claimed']) : ?>
                        <span class="badge badge-ghost badge-xs mt-1">Claimed</span>
                    <?php endif ?>
                </div>
            </div>
        </div></div>
    <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
