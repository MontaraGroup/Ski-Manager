<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Compliance<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-scale-balanced mr-2 text-primary"></i>Compliance & Risk</h1>
            <p class="text-sm text-base-content/50">Insurance, regulations, and environmental management</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        <a href="?tab=insurance" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow <?= $tab === 'insurance' ? 'border-2 border-primary' : '' ?>"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-shield-halved text-success text-xl mb-1"></i>
            <div class="text-lg font-bold"><?= $activeInsCount ?? 0 ?>/<?= count($policies ?? []) ?></div>
            <div class="text-xs text-base-content/50">Insurance</div>
            <div class="text-xs text-success"><?= currency($totalPremium ?? 0) ?>/day</div>
        </div></a>
        <a href="?tab=government" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow <?= $tab === 'government' ? 'border-2 border-primary' : '' ?>"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-building-columns text-warning text-xl mb-1"></i>
            <div class="text-lg font-bold <?= ($complianceScore ?? 100) >= 80 ? 'text-success' : (($complianceScore ?? 100) >= 50 ? 'text-warning' : 'text-error') ?>"><?= $complianceScore ?? 100 ?>%</div>
            <div class="text-xs text-base-content/50">Compliance</div>
            <div class="text-xs"><?= $compliant ?? 0 ?>/<?= count($regs ?? []) ?> compliant</div>
        </div></a>
        <a href="?tab=environment" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow <?= $tab === 'environment' ? 'border-2 border-primary' : '' ?>"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-leaf text-success text-xl mb-1"></i>
            <div class="text-lg font-bold <?= ((int)($env['eco_score'] ?? 50)) >= 70 ? 'text-success' : (((int)($env['eco_score'] ?? 50)) >= 40 ? 'text-warning' : 'text-error') ?>"><?= (int)($env['eco_score'] ?? 50) ?></div>
            <div class="text-xs text-base-content/50">Eco Score</div>
            <div class="text-xs"><?= count($ecoUpgrades ?? []) ?> upgrades</div>
        </div></a>
    </div>

    <!-- Tab Content -->
    <?php if ($tab === 'insurance') : ?>
        <!-- Insurance -->
        <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-shield-halved mr-1 text-success"></i>Insurance Policies</h2>
        <?php if (empty($policies)) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-8">
                <i class="fa-solid fa-shield-halved text-3xl text-base-content/20 mb-2"></i>
                <p class="text-sm text-base-content/50">No insurance policies available.</p>
            </div></div>
        <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($policies as $p) : ?>
            <?php $isActive = ($p['active'] ?? 0) == 1; ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved <?= $isActive ? 'text-success' : 'text-base-content/30' ?>"></i>
                        <span class="font-semibold text-sm"><?= esc($p['name'] ?? $p['insurance_type'] ?? '') ?></span>
                    </div>
                    <span class="badge badge-sm <?= $isActive ? 'badge-success' : 'badge-ghost' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span>
                </div>
                <div class="flex items-center gap-3 text-xs text-base-content/50 mb-2">
                    <span>Premium: <?= currency($p['premium'] ?? 0) ?>/day</span>
                    <span>Coverage: <?= currency($p['coverage'] ?? 0) ?></span>
                </div>
                <form action="/insurance/toggle/<?= $p['id'] ?>" method="post"><?= csrf_field() ?>
                    <button class="btn btn-xs w-full <?= $isActive ? 'btn-ghost' : 'btn-success' ?> gap-1">
                        <i class="fa-solid <?= $isActive ? 'fa-pause' : 'fa-play' ?>"></i> <?= $isActive ? 'Deactivate' : 'Activate' ?>
                    </button>
                </form>
            </div></div>
            <?php endforeach ?>
        </div>
        <?php endif ?>

    <?php elseif ($tab === 'government') : ?>
        <!-- Government -->
        <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-building-columns mr-1 text-warning"></i>Regulations</h2>
        <?php if (empty($regs)) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-8">
                <i class="fa-solid fa-building-columns text-3xl text-base-content/20 mb-2"></i>
                <p class="text-sm text-base-content/50">No regulations to manage.</p>
            </div></div>
        <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
            <?php foreach ($regs as $r) : ?>
            <?php $isCompliant = ($r['compliant'] ?? 0) == 1; ?>
            <div class="card bg-base-100 shadow-sm <?= !$isCompliant ? 'border border-error/30' : '' ?>"><div class="card-body p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid <?= $isCompliant ? 'fa-circle-check text-success' : 'fa-circle-xmark text-error' ?>"></i>
                        <span class="font-semibold text-sm"><?= esc($r['name'] ?? $r['regulation_type'] ?? '') ?></span>
                    </div>
                    <span class="badge badge-sm <?= $isCompliant ? 'badge-success' : 'badge-error' ?>"><?= $isCompliant ? 'Compliant' : 'Non-Compliant' ?></span>
                </div>
                <div class="text-xs text-base-content/50 mb-2"><?= esc($r['description'] ?? '') ?></div>
                <div class="text-xs text-base-content/50">Cost: <?= currency($r['compliance_cost'] ?? 0) ?>/day</div>
                <?php if (!$isCompliant) : ?>
                <form action="/government/comply/<?= $r['id'] ?>" method="post" class="mt-2"><?= csrf_field() ?>
                    <button class="btn btn-xs btn-success w-full gap-1"><i class="fa-solid fa-check"></i> Comply (<?= currency($r['compliance_cost'] ?? 0) ?>/day)</button>
                </form>
                <?php else : ?>
                <form action="/government/revoke/<?= $r['id'] ?>" method="post" class="mt-2"><?= csrf_field() ?>
                    <button class="btn btn-xs btn-ghost w-full gap-1">Stop Compliance</button>
                </form>
                <?php endif ?>
            </div></div>
            <?php endforeach ?>
        </div>

        <?php if (!empty($inspections)) : ?>
        <h3 class="font-bold text-sm mb-2"><i class="fa-solid fa-magnifying-glass mr-1"></i>Recent Inspections</h3>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
            <table class="table table-sm">
                <thead><tr><th>Date</th><th>Result</th><th>Fine</th></tr></thead>
                <tbody>
                <?php foreach ($inspections as $i) : ?>
                <tr>
                    <td class="text-xs"><?= date('M j', strtotime($i['created_at'])) ?></td>
                    <td><span class="badge badge-xs <?= ($i['passed'] ?? 0) ? 'badge-success' : 'badge-error' ?>"><?= ($i['passed'] ?? 0) ? 'Passed' : 'Failed' ?></span></td>
                    <td class="text-xs <?= ($i['fine'] ?? 0) > 0 ? 'text-error' : '' ?>"><?= ($i['fine'] ?? 0) > 0 ? currency($i['fine']) : '-' ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div></div></div>
        <?php endif ?>
        <?php endif ?>

    <?php elseif ($tab === 'environment') : ?>
        <!-- Environment -->
        <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-leaf mr-1 text-success"></i>Environmental Management</h2>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
            <div class="flex items-center gap-4">
                <div class="radial-progress text-<?= ((int)($env['eco_score'] ?? 50)) >= 70 ? 'success' : (((int)($env['eco_score'] ?? 50)) >= 40 ? 'warning' : 'error') ?>" style="--value:<?= (int)($env['eco_score'] ?? 50) ?>;--size:4rem;--thickness:4px;"><?= (int)($env['eco_score'] ?? 50) ?></div>
                <div>
                    <div class="text-lg font-bold">Eco Score: <?= (int)($env['eco_score'] ?? 50) ?>/100</div>
                    <div class="text-sm text-base-content/50"><?= ((int)($env['eco_score'] ?? 50)) >= 80 ? 'Excellent! Earning Genepis daily.' : (((int)($env['eco_score'] ?? 50)) >= 50 ? 'Good, but room to improve.' : 'Low score. Consider eco upgrades.') ?></div>
                </div>
            </div>
        </div></div>

        <?php if (!empty($ecoUpgrades)) : ?>
        <h3 class="font-bold text-sm mb-2">Your Upgrades</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
            <?php foreach ($ecoUpgrades as $u) : ?>
            <div class="flex items-center gap-2 bg-base-100 rounded-lg p-3 shadow-sm">
                <i class="fa-solid fa-leaf text-success"></i>
                <span class="text-sm"><?= esc($u['name'] ?? $u['upgrade_type'] ?? '') ?></span>
            </div>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <a href="/environment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-arrow-right"></i> Full Environment Page</a>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
