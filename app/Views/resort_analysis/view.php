<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-5xl">
    <div class="flex items-center gap-3 mb-6">
        <a href="/resort-analysis" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-clipboard-check mr-2 text-primary"></i> Resort Analysis — Day <?= $report["game_day"] ?></h1>
        <a href="/resort-analysis/pdf/<?= $report["id"] ?>" class="btn btn-outline btn-sm gap-1 ml-auto"><i class="fa-solid fa-file-pdf text-error"></i> Download PDF</a>
    </div>

    <!-- Overall Score -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body flex-row items-center gap-6">
        <div class="radial-progress text-<?= $data['overall_score'] >= 70 ? 'success' : ($data['overall_score'] >= 40 ? 'warning' : 'error') ?>" style="--value:<?= $data['overall_score'] ?>;--size:6rem;--thickness:5px;" role="progressbar">
            <span class="text-xl font-bold"><?= $data['overall_score'] ?>%</span>
        </div>
        <div>
            <h2 class="text-xl font-bold">Overall Score: <?= $data['overall_score'] ?>%</h2>
            <p class="text-sm text-base-content/60"><?= $data['overall_score'] >= 80 ? 'Excellent! Your resort is performing well.' : ($data['overall_score'] >= 60 ? 'Good foundation, but room for improvement.' : ($data['overall_score'] >= 40 ? 'Your resort needs attention in several areas.' : 'Significant improvements needed across the board.')) ?></p>
        </div>
    </div></div>

    <!-- Category Scores -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <?php
        $catLabels = ['infrastructure' => ['Infrastructure', 'fa-road', 'text-info'], 'staffing' => ['Staffing', 'fa-users', 'text-warning'], 'finances' => ['Finances', 'fa-coins', 'text-success'], 'amenities' => ['Amenities', 'fa-building', 'text-primary'], 'equipment' => ['Equipment', 'fa-toolbox', 'text-warning'], 'resources' => ['Resources', 'fa-bolt', 'text-error'], 'safety' => ['Safety', 'fa-shield-halved', 'text-success']];
        foreach ($data['scores'] as $cat => $score) :
            $label = $catLabels[$cat] ?? [ucfirst($cat), 'fa-circle', ''];
        ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid <?= $label[1] ?> <?= $label[2] ?> text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= $score ?>%</div>
            <div class="text-xs text-base-content/50"><?= $label[0] ?></div>
            <progress class="progress <?= $score >= 70 ? 'progress-success' : ($score >= 40 ? 'progress-warning' : 'progress-error') ?> w-full mt-1" value="<?= $score ?>" max="100"></progress>
        </div></div>
        <?php endforeach ?>
    </div>

    <!-- Key Stats -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <h2 class="card-title text-base mb-3"><i class="fa-solid fa-chart-bar mr-1"></i> Snapshot</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
            <div class="flex justify-between"><span class="text-base-content/50">Cash</span><span class="font-semibold"><?= currency($data['stats']['cash']) ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Slopes</span><span class="font-semibold"><?= $data['stats']['open_slopes'] ?>/<?= $data['stats']['slopes'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Lifts</span><span class="font-semibold"><?= $data['stats']['open_lifts'] ?>/<?= $data['stats']['lifts'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Staff</span><span class="font-semibold"><?= $data['stats']['staff'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Avg Morale</span><span class="font-semibold"><?= $data['stats']['avg_morale'] ?>%</span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Buildings</span><span class="font-semibold"><?= $data['stats']['buildings'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Equipment</span><span class="font-semibold"><?= $data['stats']['equipment'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Infra Condition</span><span class="font-semibold"><?= $data['stats']['avg_infra_condition'] ?>%</span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Parking</span><span class="font-semibold"><?= $data['stats']['parking'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Terrain Parks</span><span class="font-semibold"><?= $data['stats']['terrain_parks'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Energy Sources</span><span class="font-semibold"><?= $data['stats']['energy_sources'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Water Sources</span><span class="font-semibold"><?= $data['stats']['water_sources'] ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Insurance</span><span class="font-semibold"><?= $data['stats']['insurance'] ?> policies</span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Total Debt</span><span class="font-semibold text-error"><?= currency($data['stats']['total_debt']) ?></span></div>
            <div class="flex justify-between"><span class="text-base-content/50">Daily Salary</span><span class="font-semibold"><?= currency($data['stats']['daily_salary']) ?></span></div>
        </div>
    </div></div>

    <!-- Recommendations -->
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i> Recommendations (<?= count($data['recommendations']) ?>)</h2>
    <?php if (empty($data['recommendations'])) : ?>
        <div class="alert alert-success"><i class="fa-solid fa-check-circle"></i><span>No recommendations — your resort is in great shape!</span></div>
    <?php else : ?>
        <div class="space-y-2">
        <?php foreach ($data['recommendations'] as $rec) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 flex-row items-start gap-3">
                <i class="fa-solid <?= $rec['type'] === 'critical' ? 'fa-circle-xmark text-error' : ($rec['type'] === 'warning' ? 'fa-triangle-exclamation text-warning' : 'fa-circle-info text-info') ?> text-lg mt-0.5"></i>
                <div>
                    <div class="flex items-center gap-2"><span class="badge badge-xs <?= $rec['type'] === 'critical' ? 'badge-error' : ($rec['type'] === 'warning' ? 'badge-warning' : 'badge-info') ?>"><?= ucfirst($rec['type']) ?></span><span class="text-xs font-semibold text-base-content/50"><?= $rec['area'] ?></span></div>
                    <p class="text-sm mt-1"><?= $rec['text'] ?></p>
                </div>
            </div></div>
        <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
