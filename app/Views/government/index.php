<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Government & Regulations<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-building-columns mr-2 text-primary"></i>Government & Regulations</h1>
                <p class="text-sm text-base-content/50">Stay compliant to avoid inspections, fines, and reputation damage</p>
            </div>
        </div>
        <?php if ($compliant < $total) : ?>
        <form action="/government/comply-all" method="post" onsubmit="return confirm('Comply with all regulations? Daily cost will be <?= currency($totalPossibleCost) ?>.')">
            <?= csrf_field() ?>
            <button class="btn btn-success btn-sm gap-1"><i class="fa-solid fa-check-double"></i> Comply All</button>
        </form>
        <?php endif ?>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Compliance Score -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex items-center gap-6">
            <div class="radial-progress text-<?= $complianceScore >= 80 ? 'success' : ($complianceScore >= 50 ? 'warning' : 'error') ?>" style="--value:<?= $complianceScore ?>;--size:5rem;--thickness:4px;" role="progressbar">
                <span class="text-lg font-bold"><?= $complianceScore ?>%</span>
            </div>
            <div class="flex-1">
                <h2 class="text-lg font-bold">Compliance Score</h2>
                <p class="text-sm text-base-content/60"><?= $complianceScore >= 100 ? 'Perfect! All regulations met.' : ($complianceScore >= 75 ? 'Good standing with minor gaps.' : ($complianceScore >= 50 ? 'Moderate risk - several regulations not met.' : 'High risk - major compliance issues.')) ?></p>
            </div>
        </div>
    </div></div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold <?= $compliant === $total ? 'text-success' : 'text-warning' ?>"><?= $compliant ?>/<?= $total ?></div>
            <div class="text-xs text-base-content/50">Compliant</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-warning"><?= currency($totalCost) ?></div>
            <div class="text-xs text-base-content/50">Daily Cost</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold <?= $inspectionChance > 20 ? 'text-error' : ($inspectionChance > 0 ? 'text-warning' : 'text-success') ?>"><?= $inspectionChance ?>%</div>
            <div class="text-xs text-base-content/50">Inspection Risk</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-error"><?= currency($totalRisk) ?></div>
            <div class="text-xs text-base-content/50">Max Fine</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-error">-<?= $visitorPenalty ?>%</div>
            <div class="text-xs text-base-content/50">Visitor Penalty</div>
        </div></div>
    </div>

    <!-- Warnings -->
    <?php if ($compliant === $total) : ?>
        <div class="alert alert-success mb-6"><i class="fa-solid fa-circle-check"></i><span>Fully compliant! All bonuses active, zero inspection risk.</span></div>
    <?php elseif ($inspectionChance > 20) : ?>
        <div class="alert alert-error mb-6"><i class="fa-solid fa-triangle-exclamation"></i><div>
            <div class="font-semibold">High risk of government inspection!</div>
            <div class="text-xs"><?= $inspectionChance ?>% daily chance. Fines up to <?= currency($totalRisk) ?>. -<?= $reputationPenalty ?> rep/day, -<?= $visitorPenalty ?>% visitors.</div>
        </div></div>
    <?php elseif ($inspectionChance > 0) : ?>
        <div class="alert alert-warning mb-6"><i class="fa-solid fa-circle-exclamation"></i><div>
            <div class="font-semibold">Non-compliance detected</div>
            <div class="text-xs"><?= $inspectionChance ?>% inspection risk. -<?= $reputationPenalty ?> rep/day. -<?= $visitorPenalty ?>% visitors.</div>
        </div></div>
    <?php endif ?>

    <!-- Regulations by Tier -->
    <?php
    $tierNames = [1 => ['Basic Regulations', 'Required for all resorts', 'badge-primary'], 2 => ['Advanced Standards', 'Unlock additional benefits', 'badge-secondary'], 3 => ['Premium Compliance', 'Top-tier resort requirements', 'badge-accent']];
    foreach ($tiers as $tierNum => $tierRegs) :
        if (empty($tierRegs)) continue;
        $tierInfo = $tierNames[$tierNum];
    ?>
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <h2 class="text-lg font-bold"><?= $tierInfo[0] ?></h2>
            <span class="badge <?= $tierInfo[2] ?> badge-sm">Tier <?= $tierNum ?></span>
            <span class="text-xs text-base-content/50">- <?= $tierInfo[1] ?></span>
        </div>
        <div class="space-y-2">
        <?php foreach ($tierRegs as $reg) : ?>
            <?php $cfg = $regConfig[$reg['regulation_type']] ?? []; ?>
            <div class="card bg-base-100 shadow-sm <?= !$reg['compliant'] ? '' : '' ?>">
                <div class="card-body p-4">
                    <div class="flex flex-col md:flex-row md:items-center gap-3">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="w-10 h-10 rounded-lg <?= $reg['compliant'] ? 'bg-success/10' : 'bg-error/10' ?> flex items-center justify-center shrink-0">
                                <i class="<?= $cfg['icon'] ?? 'fa-solid fa-gavel' ?> text-lg <?= $reg['compliant'] ? 'text-success' : 'text-error' ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="font-semibold text-sm"><?= esc($reg['name']) ?></div>
                                <?php if ($reg['compliant']) : ?>
                                    <div class="text-xs text-success mt-0.5"><i class="fa-solid fa-check mr-1"></i><?= $cfg['benefit'] ?? 'Compliant' ?></div>
                                <?php else : ?>
                                    <div class="text-xs text-error mt-0.5"><i class="fa-solid fa-xmark mr-1"></i>Risk: <?= currency((int)$reg['penalty_risk']) ?> fine per inspection</div>
                                <?php endif ?>
                                <div class="flex items-center gap-3 text-xs text-base-content/50 mt-1">
                                    <span><i class="fa-solid fa-coins mr-1"></i><?= currency((int)$reg['compliance_cost']) ?>/day</span>
                                    <span><i class="fa-solid fa-gavel mr-1"></i><?= currency((int)$reg['penalty_risk']) ?> penalty</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <?php if ($reg['compliant']) : ?>
                                <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-circle-check"></i>Compliant</span>
                            <?php else : ?>
                                <span class="badge badge-error badge-sm gap-1"><i class="fa-solid fa-triangle-exclamation"></i>Violation</span>
                            <?php endif ?>
                            <form action="/government/toggle/<?= $reg['id'] ?>" method="post" class="inline"><?= csrf_field() ?>
                                <button class="btn btn-<?= $reg['compliant'] ? 'ghost' : 'success' ?> btn-sm"
                                    <?php if ($reg['compliant']) : ?>onclick="return confirm('Opting out will expose you to fines. Are you sure?')"<?php endif ?>>
                                    <i class="fa-solid <?= $reg['compliant'] ? 'fa-toggle-on' : 'fa-toggle-off' ?> mr-1"></i>
                                    <?= $reg['compliant'] ? 'Opt Out' : 'Comply' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>

    <!-- Inspection History -->
    <?php if (!empty($inspections)) : ?>
    <div class="card bg-base-100 shadow-sm mt-6"><div class="card-body p-4">
        <h3 class="font-semibold text-base mb-3"><i class="fa-solid fa-clipboard-list mr-1"></i> Inspection History</h3>
        <div class="space-y-2">
        <?php foreach ($inspections as $insp) : ?>
            <div class="flex items-center gap-2 text-sm">
                <i class="<?= $insp['icon'] ?? 'fa-solid fa-gavel' ?> text-warning w-5 text-center"></i>
                <span class="flex-1"><?= esc($insp['message']) ?></span>
                <span class="text-xs text-base-content/40">Day <?= $insp['game_day'] ?></span>
            </div>
        <?php endforeach ?>
        </div>
    </div></div>
    <?php endif ?>

    <!-- How It Works -->
    <div class="card bg-base-100 shadow-sm mt-6"><div class="card-body p-4">
        <h3 class="font-semibold text-base mb-3"><i class="fa-solid fa-circle-info mr-1 text-info"></i> How Regulations Work</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-base-content/60">
            <div class="space-y-1.5">
                <div><i class="fa-solid fa-gavel mr-1 text-warning"></i> Each violation adds <strong>5%</strong> daily inspection chance</div>
                <div><i class="fa-solid fa-star mr-1 text-error"></i> Each violation costs <strong>15</strong> reputation per day</div>
                <div><i class="fa-solid fa-users mr-1 text-error"></i> Each violation reduces visitors by <strong>3%</strong></div>
            </div>
            <div class="space-y-1.5">
                <div><i class="fa-solid fa-coins mr-1 text-warning"></i> If inspected, you pay fines for <strong>each</strong> violation</div>
                <div><i class="fa-solid fa-circle-check mr-1 text-success"></i> Full compliance = <strong>0%</strong> risk + all bonuses</div>
                <div><i class="fa-solid fa-seedling mr-1 text-success"></i> Environmental compliance earns daily <strong>Génépis</strong></div>
            </div>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
