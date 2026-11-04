<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Analysis<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Controllers\ResortAnalysis as RA;
$tone = fn(int $s) => $s >= 70 ? 'success' : ($s >= 40 ? 'warning' : 'error');
$canAfford = $balance >= $cost;
?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-clipboard-check mr-2 text-primary" aria-hidden="true"></i>Resort Analysis</h1>
            <p class="text-sm text-base-content/70">A consultant reads yesterday's results and tells you what to fix first</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <!-- Order -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-5">
        <?php if ($todayReport) : ?>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div><h2 class="font-bold">Today's report is ready</h2><p class="text-sm text-base-content/70">You can order the next one tomorrow (day <?= $gameDay + 1 ?>).</p></div>
                <a href="/resort-analysis/view/<?= (int) $todayReport['id'] ?>" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-eye" aria-hidden="true"></i> Open today's report</a>
            </div>
        <?php else : ?>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold">Order today's report</h2>
                    <p class="text-sm text-base-content/70"><?= $cost ?> Génépis · you have <?= $balance ?> <i class="fa-solid fa-seedling text-success" aria-hidden="true"></i> · one report per game day</p>
                </div>
                <form action="/resort-analysis/order" method="post" data-confirm="Order today's resort analysis for <?= $cost ?> Génépis?"><?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm gap-1" <?= $canAfford ? '' : 'disabled' ?>><i class="fa-solid fa-seedling" aria-hidden="true"></i> Order for <?= $cost ?> Génépis</button>
                </form>
            </div>
            <?php if (!$canAfford) : ?><p class="text-xs text-base-content/70 mt-2">You need <?= $cost - $balance ?> more Génépis. <a href="/genepis" class="link link-primary">Get Génépis</a></p><?php endif ?>
        <?php endif ?>
        <ul class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-4 text-xs text-base-content/70">
            <?php foreach (RA::AREAS as [$name, $icon, $color]) : ?><li><i class="fa-solid <?= $icon ?> <?= $color ?> mr-1 w-4" aria-hidden="true"></i><?= $name ?></li><?php endforeach ?>
        </ul>
    </div></div>

    <!-- Reports -->
    <?php if ($reports) : ?>
    <section aria-labelledby="reports-h">
        <h2 id="reports-h" class="text-lg font-bold mb-3">Your reports</h2>
        <div class="space-y-2">
        <?php foreach ($reports as $i => $r) : $d = $r['data']; $score = (int) ($d['overall_score'] ?? 0); $recs = $d['recommendations'] ?? [];
            $urgent = count(array_filter($recs, fn($x) => ($x['type'] ?? '') === 'critical')); ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 gap-2">
                <div class="flex items-center gap-3">
                    <div class="radial-progress text-<?= $tone($score) ?> text-xs shrink-0" style="--value:<?= $score ?>;--size:2.75rem;--thickness:3px" role="img" aria-label="Score <?= $score ?>"><?= $score ?></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold">Day <?= (int) $r['game_day'] ?><?= $i === 0 ? ' <span class="badge badge-sm badge-ghost">latest</span>' : '' ?></div>
                        <div class="text-xs text-base-content/70"><?= date('M j, Y', strtotime($r['created_at'])) ?> · <?= count($recs) ?> recommendation<?= count($recs) === 1 ? '' : 's' ?><?= $urgent ? ' · <span class="text-error">' . $urgent . ' urgent</span>' : '' ?></div>
                    </div>
                    <a href="/resort-analysis/view/<?= (int) $r['id'] ?>" class="btn btn-ghost btn-xs gap-1">Open</a>
                    <a href="/resort-analysis/pdf/<?= (int) $r['id'] ?>" class="btn btn-ghost btn-xs" aria-label="Download day <?= (int) $r['game_day'] ?> report as PDF"><i class="fa-solid fa-file-pdf text-error" aria-hidden="true"></i></a>
                </div>
                <?php if ($i === 0 && $recs) : ?>
                <ul class="text-sm space-y-1 border-t border-base-300 pt-2">
                    <?php foreach (array_slice($recs, 0, 3) as $rec) : ?>
                    <li class="flex items-start gap-2"><i class="fa-solid <?= ['critical' => 'fa-circle-xmark text-error', 'warning' => 'fa-triangle-exclamation text-warning'][$rec['type']] ?? 'fa-lightbulb text-info' ?> mt-0.5" aria-hidden="true"></i><span class="text-base-content/80"><?= esc($rec['text']) ?></span></li>
                    <?php endforeach ?>
                </ul>
                <?php endif ?>
            </div></div>
        <?php endforeach ?>
        </div>
    </section>
    <?php else : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-10">
        <i class="fa-solid fa-clipboard-check text-4xl text-base-content/20 mb-3" aria-hidden="true"></i>
        <p class="font-semibold">No reports yet</p>
        <p class="text-sm text-base-content/70 mt-1">Your first report shows where your resort is losing skiers and money.</p>
    </div></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
