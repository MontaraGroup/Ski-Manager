<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-4xl">
    <h1 class="text-3xl font-bold mb-2"><i class="fa-solid fa-clipboard-check mr-2 text-primary"></i> Resort Analysis</h1>
    <p class="text-base-content/60 mb-6">Order a complete analysis of your resort. A group of experts will produce a concise report with all possible improvements to increase profitability.</p>

    <?php if (session()->getFlashdata('success')) : ?><div class="alert alert-success mb-4"><?= session()->getFlashdata('success') ?></div><?php endif ?>
    <?php if (session()->getFlashdata('error')) : ?><div class="alert alert-error mb-4"><?= session()->getFlashdata('error') ?></div><?php endif ?>

    <!-- Order Section -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <div class="flex items-center justify-between flex-wrap gap-3">
            <div>
                <h2 class="card-title text-base"><i class="fa-solid fa-file-lines mr-1"></i> Order Report</h2>
                <p class="text-sm text-base-content/60 mt-1">Cost: <strong><?= $cost ?> Génépis</strong> · Your balance: <strong><?= $genepis['balance'] ?? 0 ?> <i class="fa-solid fa-seedling text-success"></i></strong></p>
            </div>
            <?php if ($todayReport) : ?>
                <div class="badge badge-info gap-1"><i class="fa-solid fa-check"></i> Today's report ready</div>
            <?php else : ?>
                <form action="/resort-analysis/order" method="post" onsubmit="return confirm('Order report for <?= $cost ?> Génépis?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-primary btn-sm gap-1" <?= ($genepis['balance'] ?? 0) < $cost ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-seedling"></i> Order Analysis (<?= $cost ?> <i class="fa-solid fa-seedling"></i>)
                    </button>
                </form>
            <?php endif ?>
        </div>
    </div></div>

    <!-- Past Reports -->
    <?php if (!empty($reports)) : ?>
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-folder-open mr-1"></i> Your Reports</h2>
    <div class="space-y-2">
        <?php foreach ($reports as $r) : ?>
            <?php $data = json_decode($r['report_data'], true); ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 flex-row items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="radial-progress text-<?= ($data['overall_score'] ?? 0) >= 70 ? 'success' : (($data['overall_score'] ?? 0) >= 40 ? 'warning' : 'error') ?> text-sm" style="--value:<?= $data['overall_score'] ?? 0 ?>;--size:3rem;--thickness:3px;"><?= $data['overall_score'] ?? 0 ?>%</div>
                    <div>
                        <div class="font-semibold">Day <?= $r['game_day'] ?> Report</div>
                        <div class="text-xs text-base-content/50"><?= date('M j, Y', strtotime($r['created_at'])) ?> · <?= count($data['recommendations'] ?? []) ?> recommendations</div>
                    </div>
                </div>
                <a href="/resort-analysis/view/<?= $r['id'] ?>" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-eye"></i> View</a>
            </div></div>
        <?php endforeach ?>
    </div>
    <?php else : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
        <i class="fa-solid fa-clipboard-check text-4xl text-base-content/20 mb-3"></i>
        <p class="text-base-content/60">No reports yet. Order your first analysis above!</p>
    </div></div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
