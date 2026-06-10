<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Analysis<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-clipboard-check mr-2 text-primary"></i>Resort Analysis</h1>
                <p class="text-sm text-base-content/50">Expert assessment of your resort's performance</p>
            </div>
        </div>
        <?php if ($todayReport) : ?>
            <a href="/resort-analysis/view/<?= $todayReport['id'] ?>" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-eye"></i> View Latest</a>
        <?php else : ?>
            <form action="/resort-analysis/order" method="post" data-confirm="Order analysis for <?= $cost ?> Genepis?"><?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm gap-1" <?= ($genepis['balance'] ?? 0) < $cost ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-seedling"></i> Order (<?= $cost ?> Genepis)
                </button>
            </form>
        <?php endif ?>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Latest Report Preview -->
    <?php if (!empty($reports)) : ?>
        <?php $latest = $reports[0]; $data = json_decode($latest['report_data'], true); ?>
        <div class="card bg-gradient-to-br from-primary/10 to-info/10 shadow-sm mb-6"><div class="card-body p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-bold text-sm">Latest Report — Day <?= $latest['game_day'] ?></h2>
                <span class="text-xs text-base-content/50"><?= date('M j, Y', strtotime($latest['created_at'])) ?></span>
            </div>
            <div class="flex items-center gap-6 mb-4">
                <div class="radial-progress text-<?= ($data['overall_score'] ?? 0) >= 70 ? 'success' : (($data['overall_score'] ?? 0) >= 40 ? 'warning' : 'error') ?>" style="--value:<?= $data['overall_score'] ?? 0 ?>;--size:5rem;--thickness:4px;" role="progressbar">
                    <span class="text-lg font-bold"><?= $data['overall_score'] ?? 0 ?>%</span>
                </div>
                <div class="flex-1">
                    <div class="text-2xl font-bold"><?= $data['overall_score'] ?? 0 ?>% Overall</div>
                    <p class="text-sm text-base-content/60"><?= ($data['overall_score'] ?? 0) >= 80 ? 'Excellent resort performance.' : (($data['overall_score'] ?? 0) >= 60 ? 'Good, but room to improve.' : (($data['overall_score'] ?? 0) >= 40 ? 'Needs attention in several areas.' : 'Significant improvements needed.')) ?></p>
                </div>
            </div>
            <!-- Category bars -->
            <?php
                $catLabels = ['infrastructure' => ['Infrastructure', 'fa-road', 'text-info'], 'staffing' => ['Staffing', 'fa-users', 'text-warning'], 'finances' => ['Finances', 'fa-coins', 'text-success'], 'amenities' => ['Amenities', 'fa-building', 'text-primary'], 'equipment' => ['Equipment', 'fa-toolbox', 'text-warning'], 'resources' => ['Resources', 'fa-bolt', 'text-error'], 'safety' => ['Safety', 'fa-shield-halved', 'text-success']];
            ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                <?php foreach (($data['scores'] ?? []) as $cat => $score) : ?>
                    <?php $label = $catLabels[$cat] ?? [ucfirst($cat), 'fa-circle', '']; ?>
                    <div class="text-center">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-base-content/50"><i class="fa-solid <?= $label[1] ?> <?= $label[2] ?> mr-1"></i><?= $label[0] ?></span>
                            <span class="font-bold <?= $score >= 70 ? 'text-success' : ($score >= 40 ? 'text-warning' : 'text-error') ?>"><?= $score ?>%</span>
                        </div>
                        <progress class="progress <?= $score >= 70 ? 'progress-success' : ($score >= 40 ? 'progress-warning' : 'progress-error') ?> w-full h-1.5" value="<?= $score ?>" max="100"></progress>
                    </div>
                <?php endforeach ?>
            </div>
            <!-- Top recommendations -->
            <?php if (!empty($data['recommendations'])) : ?>
            <div class="border-t border-base-300 pt-3">
                <div class="text-xs font-semibold text-base-content/40 uppercase mb-2">Top Recommendations</div>
                <div class="space-y-1">
                    <?php foreach (array_slice($data['recommendations'], 0, 3) as $rec) : ?>
                    <div class="flex items-start gap-2 text-sm">
                        <i class="fa-solid fa-<?= ($rec['priority'] ?? '') === 'high' ? 'circle-exclamation text-error' : (($rec['priority'] ?? '') === 'medium' ? 'circle-info text-warning' : 'circle-check text-success') ?> text-xs mt-1"></i>
                        <span class="text-base-content/70"><?= esc($rec['text'] ?? $rec['message'] ?? '') ?></span>
                    </div>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>
            <div class="mt-3">
                <a href="/resort-analysis/view/<?= $latest['id'] ?>" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-arrow-right"></i> Full Report</a>
                <a href="/resort-analysis/pdf/<?= $latest['id'] ?>" class="btn btn-ghost btn-sm gap-1"><i class="fa-solid fa-file-pdf text-error"></i> PDF</a>
            </div>
        </div></div>
    <?php endif ?>

    <!-- Order New -->
    <?php if (!$todayReport) : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-sm"><i class="fa-solid fa-plus-circle mr-1 text-primary"></i>Order New Report</h2>
                <p class="text-xs text-base-content/50 mt-1">Cost: <strong><?= $cost ?> Genepis</strong> · Balance: <strong><?= $genepis['balance'] ?? 0 ?> <i class="fa-solid fa-seedling text-success"></i></strong></p>
            </div>
            <form action="/resort-analysis/order" method="post" data-confirm="Order analysis for <?= $cost ?> Genepis?"><?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm gap-1" <?= ($genepis['balance'] ?? 0) < $cost ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-seedling"></i> Order
                </button>
            </form>
        </div>
    </div></div>
    <?php endif ?>

    <!-- Report History -->
    <?php if (count($reports) > 1) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-folder-open mr-1"></i> Past Reports</h2>
    <div class="space-y-2">
        <?php foreach (array_slice($reports, 1) as $r) : ?>
            <?php $rData = json_decode($r['report_data'], true); $rScore = $rData['overall_score'] ?? 0; ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 flex-row items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="radial-progress text-<?= $rScore >= 70 ? 'success' : ($rScore >= 40 ? 'warning' : 'error') ?> text-sm" style="--value:<?= $rScore ?>;--size:2.5rem;--thickness:3px;"><?= $rScore ?>%</div>
                    <div>
                        <div class="font-semibold text-sm">Day <?= $r['game_day'] ?></div>
                        <div class="text-xs text-base-content/50"><?= date('M j', strtotime($r['created_at'])) ?> · <?= count($rData['recommendations'] ?? []) ?> recommendations</div>
                    </div>
                </div>
                <div class="flex gap-1">
                    <a href="/resort-analysis/view/<?= $r['id'] ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-eye"></i></a>
                    <a href="/resort-analysis/pdf/<?= $r['id'] ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-file-pdf text-error"></i></a>
                </div>
            </div></div>
        <?php endforeach ?>
    </div>
    <?php elseif (empty($reports)) : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
        <i class="fa-solid fa-clipboard-check text-4xl text-base-content/20 mb-3"></i>
        <p class="font-semibold">No reports yet</p>
        <p class="text-sm text-base-content/50 mt-1">Order your first analysis to get detailed insights on your resort.</p>
    </div></div>
    <?php endif ?>

    <!-- What's Analyzed -->
    <div class="collapse collapse-arrow bg-base-100 shadow-sm mt-6">
        <input type="checkbox" />
        <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i> What's Analyzed</div>
        <div class="collapse-content text-sm text-base-content/70">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-2">
                <div class="flex items-center gap-2"><i class="fa-solid fa-road text-info"></i><span>Infrastructure</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-users text-warning"></i><span>Staffing</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-coins text-success"></i><span>Finances</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-building text-primary"></i><span>Amenities</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-toolbox text-warning"></i><span>Equipment</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-bolt text-error"></i><span>Resources</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-shield-halved text-success"></i><span>Safety</span></div>
                <div class="flex items-center gap-2"><i class="fa-solid fa-lightbulb text-warning"></i><span>Recommendations</span></div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
