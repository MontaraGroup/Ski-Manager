<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Daily Bonus<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Controllers\DailyBonus as B;
$h = intdiv($secondsToReset, 3600); $m = intdiv($secondsToReset % 3600, 60);
$nextMultPct = (int) round(($next['multiplier'] - 1) * 100);
?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-fire mr-2 text-warning" aria-hidden="true"></i>Daily bonus</h1>
            <p class="text-sm text-base-content/70">Claim once a day. Every day in a row grows your streak, and each full week makes rewards bigger.</p>
        </div>
    </div>

    <!-- Claim -->
    <div class="card bg-base-100 shadow-sm mb-4 <?= !$claimedToday ? 'border border-warning/50' : '' ?>"><div class="card-body p-5 text-center">
        <?php if ($claimedToday) : ?>
            <div class="text-sm text-base-content/70">Claimed today. Tomorrow's bonus:</div>
            <div class="text-3xl font-bold"><?= currency($next['cash']) ?><?= $next['genepis'] ? ' <span class="text-lg text-success">+ ' . $next['genepis'] . ' Génépis</span>' : '' ?></div>
            <div class="text-sm text-base-content/70 mt-1">Unlocks in <span id="resetIn" data-seconds="<?= $secondsToReset ?>"><?= $h ?>h <?= $m ?>m</span> (midnight, server time). Claim before the end of tomorrow to keep your streak.</div>
        <?php else : ?>
            <?php if ($lostStreak) : ?>
            <div role="alert" class="alert alert-warning mb-3 text-sm"><i class="fa-solid fa-heart-crack" aria-hidden="true"></i><span>You missed a day, so your <?= $lostStreak ?>-day streak ended. Today starts a new one.</span></div>
            <?php endif ?>
            <div class="text-sm text-base-content/70">Day <?= $streak + 1 ?> bonus</div>
            <div class="text-4xl font-bold text-warning"><?= currency($next['cash']) ?><?= $next['genepis'] ? ' <span class="text-xl text-success">+ ' . $next['genepis'] . ' Génépis</span>' : '' ?></div>
            <?php if ($nextMultPct > 0) : ?><div class="text-xs text-success mt-1">Includes +<?= $nextMultPct ?>% for week <?= $next['week'] ?> of your streak</div><?php endif ?>
            <form action="/daily-bonus/claim" method="post" class="mt-3"><?= csrf_field() ?>
                <button class="btn btn-warning btn-lg gap-2"><i class="fa-solid fa-gift" aria-hidden="true"></i> Claim</button>
            </form>
            <div class="text-xs text-base-content/70 mt-2">Available until midnight, in <span id="resetIn" data-seconds="<?= $secondsToReset ?>"><?= $h ?>h <?= $m ?>m</span></div>
        <?php endif ?>
    </div></div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-3 mb-4">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center">
            <div class="text-xs text-base-content/70">Current streak</div>
            <div class="text-2xl font-bold text-warning"><?= $streak ?> <span class="text-sm font-normal text-base-content/70">day<?= $streak === 1 ? '' : 's' ?></span></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center">
            <div class="text-xs text-base-content/70">Best streak</div>
            <div class="text-2xl font-bold"><?= max((int) $row['best_streak'], $streak) ?> <span class="text-sm font-normal text-base-content/70">days</span></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-center">
            <div class="text-xs text-base-content/70">Claimed in total</div>
            <div class="text-2xl font-bold text-success"><?= currency((int) $row['total_claimed']) ?></div>
        </div></div>
    </div>

    <!-- This week -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <div class="flex items-baseline justify-between mb-3">
            <h2 class="font-bold">Week <?= $days[0]['week'] ?></h2>
            <span class="text-xs text-base-content/70"><?= $days[0]['multiplier'] > 1 ? 'Rewards ×' . rtrim(rtrim(number_format($days[0]['multiplier'], 2), '0'), '.') : 'Base rewards' ?></span>
        </div>
        <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
            <?php foreach ($days as $d) : ?>
            <div class="rounded-lg border p-2 text-center <?= $d['done'] ? 'bg-warning/15 border-warning/50' : ($d['next'] ? 'border-primary bg-primary/10' : 'border-base-300') ?>">
                <div class="text-[11px] text-base-content/70">Day <?= $d['streak'] ?></div>
                <div class="my-1"><i class="fa-solid <?= $d['done'] ? 'fa-check text-warning' : ($d['genepis'] ? 'fa-star text-success' : 'fa-gift text-base-content/40') ?>" aria-hidden="true"></i></div>
                <div class="text-xs font-bold"><?= currency($d['cash']) ?></div>
                <?php if ($d['genepis']) : ?><div class="text-[10px] text-success">+<?= $d['genepis'] ?> Génépis</div><?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
    </div></div>

    <details class="card bg-base-100 shadow-sm">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How the streak works</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>One claim per day; days change at midnight server time.</li>
            <li>Claim on consecutive days to grow the streak. Skip a day and the next claim starts again at day 1.</li>
            <li>Rewards follow a 7-day cycle (<?= implode(', ', array_map('currency', $cycle)) ?>).</li>
            <li>Each full week of streak adds <?= (int) round(B::WEEK_BONUS * 100) ?>% to every reward, up to ×<?= B::MAX_MULTIPLIER ?> from week <?= (int) ((B::MAX_MULTIPLIER - 1) / B::WEEK_BONUS) + 1 ?>.</li>
            <li>Day 7 of every week also pays <?= B::WEEKLY_GENEPIS ?> Génépis. Streaks of 7 and 30 days complete achievements.</li>
        </ul>
    </details>
</div>
<script>
(function () {
    var el = document.getElementById('resetIn'); if (!el) return;
    var end = Date.now() + parseInt(el.dataset.seconds, 10) * 1000;
    function tick() {
        var s = Math.max(0, Math.round((end - Date.now()) / 1000));
        if (s === 0) { location.reload(); return; }
        el.textContent = Math.floor(s / 3600) + 'h ' + Math.floor(s % 3600 / 60) + 'm';
    }
    tick(); setInterval(tick, 30000);
})();
</script>
<?= $this->endSection() ?>
