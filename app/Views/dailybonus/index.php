<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Daily Bonus<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-fire mr-2 text-warning"></i>Daily Login Bonus</h1>
            <p class="text-sm text-base-content/50">Log in every day to keep your streak and earn bigger rewards</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><i class="fa-solid fa-circle-check"></i><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= session('error') ?></span></div><?php endif ?>

    <?php $streak = (int) $bonus['streak']; $maxDay = !empty($rewards) ? max(array_keys($rewards)) : 7; $pct = $maxDay > 0 ? round(min($streak, $maxDay) / $maxDay * 100) : 0; ?>

    <div class="aura aura-dual rounded-2xl mb-6 w-full">
        <div class="card bg-base-100 border border-base-200 shadow-sm w-full"><div class="card-body p-6">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-fire mr-1 text-warning"></i>Current Streak</div>
                <div class="text-3xl font-bold text-warning"><?= $streak ?><span class="text-base font-normal text-base-content/50"> day<?= $streak === 1 ? '' : 's' ?></span></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-sack-dollar mr-1 text-success"></i>Total Claimed</div>
                <div class="text-3xl font-bold text-success"><?= currency((int) $bonus['total_claimed']) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-trophy mr-1 text-primary"></i>Next Reward</div>
                <div class="text-3xl font-bold text-primary"><?= currency((int) $nextReward) ?></div>
            </div>
        </div>
        <div class="mt-4">
            <div class="flex items-center justify-between text-xs text-base-content/50 mb-1">
                <span>Streak progress</span><span><?= min($streak, $maxDay) ?>/<?= $maxDay ?> days</span>
            </div>
            <progress class="progress progress-warning w-full" value="<?= $pct ?>" max="100"></progress>
        </div>
    </div></div>
    </div>

    <?php if ($canClaim && $streak > 0 && !$claimedYesterday) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span>You missed a day - claiming now restarts your streak at Day 1.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-7 gap-2 mb-6">
    <?php foreach ($rewards as $day => $amount) : ?>
        <?php
            $past = $day <= $streak;
            $isClaimNext = $day === min($maxDay, $streak + 1);
            $highlight = $canClaim && $isClaimNext;
        ?>
        <div class="card border-2 shadow-sm <?= $past ? 'bg-warning/20 border-warning' : ($highlight ? 'bg-primary/15 border-primary' : 'bg-base-100 border-base-300') ?>">
            <div class="card-body p-2 text-center gap-1">
                <div class="text-[10px] font-semibold text-base-content/60">Day <?= $day ?></div>
                <div class="w-7 h-7 mx-auto rounded-full flex items-center justify-center <?= $past ? 'bg-warning text-warning-content' : ($highlight ? 'bg-primary text-primary-content' : 'bg-base-200 text-base-content/40') ?>">
                    <?php if ($past) : ?><i class="fa-solid fa-check text-xs"></i><?php elseif ($day === $maxDay) : ?><i class="fa-solid fa-star text-xs"></i><?php else : ?><i class="fa-solid fa-gift text-xs"></i><?php endif ?>
                </div>
                <div class="text-xs font-bold <?= $past ? 'text-warning' : ($highlight ? 'text-primary' : 'text-base-content/50') ?>"><?= currency($amount) ?></div>
                <?php if ($day === $maxDay) : ?><div class="text-[9px] font-bold text-warning">BONUS</div><?php endif ?>
            </div>
        </div>
    <?php endforeach ?>
    </div>

    <?php if ($canClaim) : ?>
        <form action="/daily-bonus/claim" method="post" class="text-center"><?= csrf_field() ?>
            <button class="btn btn-warning btn-lg gap-2"><i class="fa-solid fa-gift"></i>Claim <?= currency((int) $nextReward) ?></button>
            <p class="text-xs text-base-content/50 mt-2">Claiming adds the reward straight to your cash balance</p>
        </form>
    <?php else : ?>
        <div class="text-center">
            <div class="btn btn-disabled btn-lg gap-2"><i class="fa-solid fa-clock"></i>Already claimed - come back tomorrow!</div>
            <p class="text-xs text-base-content/50 mt-2">Return on the next game day to continue your streak</p>
        </div>
    <?php endif ?>

    <div class="card bg-base-100 shadow-sm mt-6"><div class="card-body p-4">
        <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>How streaks work</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-base-content/60">
            <div><i class="fa-solid fa-calendar-day mr-1 text-warning"></i> Claim once per game day to grow your streak</div>
            <div><i class="fa-solid fa-arrow-up mr-1 text-success"></i> Rewards increase as your streak gets longer</div>
            <div><i class="fa-solid fa-star mr-1 text-warning"></i> Day <?= $maxDay ?> pays the biggest bonus</div>
            <div><i class="fa-solid fa-rotate-left mr-1 text-error"></i> Miss a day and the streak resets to Day 1</div>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
