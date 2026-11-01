<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Analysis · Day <?= (int) $report['game_day'] ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Controllers\ResortAnalysis as RA;
$overall = (int) ($data['overall_score'] ?? 0);
$tone = fn(?int $s) => $s === null ? 'base-content' : ($s >= 70 ? 'success' : ($s >= 40 ? 'warning' : 'error'));
$v1Labels = ['infrastructure' => ['Infrastructure', 'fa-road', 'text-info'], 'staffing' => ['Staffing', 'fa-users', 'text-warning'], 'finances' => ['Finances', 'fa-coins', 'text-success'],
    'amenities' => ['Amenities', 'fa-building', 'text-primary'], 'equipment' => ['Equipment', 'fa-toolbox', 'text-warning'], 'resources' => ['Resources', 'fa-bolt', 'text-error'], 'safety' => ['Safety', 'fa-shield-halved', 'text-success']];
$recIcon = ['critical' => ['fa-circle-xmark text-error', 'badge-error', 'Urgent'], 'warning' => ['fa-triangle-exclamation text-warning', 'badge-warning', 'Important'], 'info' => ['fa-lightbulb text-info', 'badge-info', 'Idea']];
$recs = $data['recommendations'] ?? [];
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex flex-wrap items-center gap-3 mb-6">
        <a href="/resort-analysis" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to resort analysis"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-clipboard-check mr-2 text-primary" aria-hidden="true"></i>Resort analysis · day <?= (int) $report['game_day'] ?></h1>
            <p class="text-sm text-base-content/70">Ordered <?= date('M j, Y', strtotime($report['created_at'])) ?><?= !empty($data['measured_day']) ? ' · based on day ' . (int) $data['measured_day'] . '\'s results' : '' ?></p>
        </div>
        <a href="/resort-analysis/pdf/<?= (int) $report['id'] ?>" class="btn btn-outline btn-sm gap-1 ml-auto"><i class="fa-solid fa-file-pdf text-error" aria-hidden="true"></i> PDF</a>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <!-- Overall -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-5 flex-row items-center gap-5">
        <div class="radial-progress text-<?= $tone($overall) ?> shrink-0" style="--value:<?= $overall ?>;--size:5.5rem;--thickness:6px" role="img" aria-label="Overall score <?= $overall ?> out of 100"><span class="text-xl font-bold"><?= $overall ?></span></div>
        <div>
            <h2 class="text-lg font-bold"><?= $overall >= 80 ? 'Running very well' : ($overall >= 60 ? 'Solid, with clear wins available' : ($overall >= 40 ? 'Several things holding you back' : 'Needs attention now')) ?></h2>
            <p class="text-sm text-base-content/70">
                <?php $c = count(array_filter($recs, fn($r) => $r['type'] === 'critical')); $w = count(array_filter($recs, fn($r) => $r['type'] === 'warning')); ?>
                <?= $c ? $c . ' urgent issue' . ($c === 1 ? '' : 's') . ', ' : '' ?><?= $w ? $w . ' important, ' : '' ?><?= count($recs) ?> recommendation<?= count($recs) === 1 ? '' : 's' ?> in total.
            </p>
        </div>
    </div></div>

    <!-- What to do -->
    <section class="mb-6" aria-labelledby="todo-h">
        <h2 id="todo-h" class="text-lg font-bold mb-3">What to do next</h2>
        <?php if (!$recs) : ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Nothing stands out: your resort is in great shape.</span></div>
        <?php else : ?>
            <div class="space-y-2">
            <?php foreach ($recs as $rec) : [$icon, $badge, $label] = $recIcon[$rec['type']] ?? $recIcon['info']; ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 flex-row items-start gap-3">
                    <i class="fa-solid <?= $icon ?> text-lg mt-0.5" aria-hidden="true"></i>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2"><span class="badge badge-sm <?= $badge ?>"><?= $label ?></span><span class="text-xs text-base-content/70"><?= esc($rec['area']) ?></span></div>
                        <p class="text-sm mt-1"><?= esc($rec['text']) ?></p>
                    </div>
                    <?php if (!empty($rec['link'])) : ?><a href="<?= esc($rec['link'], 'attr') ?>" class="btn btn-ghost btn-xs shrink-0">Go <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a><?php endif ?>
                </div></div>
            <?php endforeach ?>
            </div>
        <?php endif ?>
    </section>

    <!-- Areas -->
    <section aria-labelledby="areas-h">
        <h2 id="areas-h" class="text-lg font-bold mb-3">Area by area</h2>
        <?php if (!empty($data['sections'])) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach (array_intersect_key(RA::AREAS, $data['sections']) as $key => [$name, $icon, $color]) : $sec = $data['sections'][$key]; $s = $sec['score']; ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 gap-2">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold"><i class="fa-solid <?= $icon ?> <?= $color ?> mr-2" aria-hidden="true"></i><?= $name ?></h3>
                    <span class="font-bold text-<?= $tone($s) ?>"><?= $s === null ? '–' : $s . '/100' ?></span>
                </div>
                <?php if ($s !== null) : ?><progress class="progress progress-<?= $tone($s) ?> w-full h-1.5" value="<?= $s ?>" max="100" aria-label="<?= $name ?> score"></progress><?php endif ?>
                <p class="text-sm text-base-content/80"><?= esc($sec['summary']) ?></p>
                <?php if (!empty($sec['metrics'])) : ?>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
                    <?php foreach ($sec['metrics'] as [$k, $v]) : ?><dt class="text-base-content/70"><?= esc($k) ?></dt><dd class="text-right font-semibold"><?= esc($v) ?></dd><?php endforeach ?>
                </dl>
                <?php endif ?>
            </div></div>
            <?php endforeach ?>
        </div>
        <?php else : /* reports from before the October 2026 rework */ ?>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <?php foreach (($data['scores'] ?? []) as $cat => $s) : [$name, $icon, $color] = $v1Labels[$cat] ?? [ucfirst($cat), 'fa-circle', '']; ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
                <i class="fa-solid <?= $icon ?> <?= $color ?> text-xl" aria-hidden="true"></i>
                <div class="text-2xl font-bold"><?= (int) $s ?></div>
                <div class="text-xs text-base-content/70"><?= $name ?></div>
            </div></div>
            <?php endforeach ?>
        </div>
        <p class="text-xs text-base-content/70 mt-2">This is an older report format. New reports are based on your resort's daily results.</p>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
