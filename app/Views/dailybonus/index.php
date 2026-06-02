<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Daily Bonus<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-fire mr-2 text-warning"></i>Daily Login Bonus</h1>
    </div>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center">
        <div class="text-xs text-base-content/50">Current Streak</div>
        <div class="text-4xl font-bold text-warning"><?= $bonus['streak'] ?> <span class="text-lg">days</span></div>
        <div class="text-sm text-base-content/50 mt-1">Total claimed: <?= currency((int)$bonus['total_claimed']) ?></div>
    </div></div>
    <div class="grid grid-cols-7 gap-2 mb-6">
    <?php foreach ($rewards as $day => $amount) : ?>
        <?php $past = $day <= (int)$bonus['streak']; $current = $day === (int)$bonus['streak'] + 1; ?>
        <div class="card <?= $past ? 'bg-warning/20 border-warning' : ($current ? 'bg-primary/20 border-primary' : 'bg-base-100') ?> shadow-sm border-2 <?= !$past && !$current ? 'border-base-300' : '' ?>">
            <div class="card-body p-2 text-center">
                <div class="text-xs font-semibold">Day <?= $day ?></div>
                <div class="text-sm font-bold <?= $past ? 'text-warning' : '' ?>"><?= currency($amount) ?></div>
                <?php if ($past) : ?><i class="fa-solid fa-check text-warning text-xs"></i><?php endif ?>
                <?php if ($day === 7) : ?><div class="text-xs text-warning">BONUS</div><?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
    </div>
    <?php if ($canClaim) : ?>
        <form action="/daily-bonus/claim" method="post" class="text-center"><?= csrf_field() ?>
            <button class="btn btn-warning btn-lg"><i class="fa-solid fa-gift mr-2"></i>Claim <?= currency($nextReward) ?></button>
        </form>
    <?php else : ?>
        <div class="text-center"><div class="btn btn-disabled btn-lg"><i class="fa-solid fa-clock mr-2"></i>Come back tomorrow!</div></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
