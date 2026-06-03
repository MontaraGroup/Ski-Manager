<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Emergency & Rescue<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $riskLevel = $safetyScore >= 80 ? 'Low' : ($safetyScore >= 50 ? 'Moderate' : 'High');
    $riskColor = $safetyScore >= 80 ? 'text-success' : ($safetyScore >= 50 ? 'text-warning' : 'text-error');
    $riskBg = $safetyScore >= 80 ? 'from-success/5 to-success/10 border-success/20' : ($safetyScore >= 50 ? 'from-warning/5 to-warning/10 border-warning/20' : 'from-error/5 to-error/10 border-error/20');
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-truck-medical mr-2 text-error"></i>Emergency & Rescue</h1>
                <p class="text-sm text-base-content/50">Safety operations, medical response, and risk management</p>
            </div>
        </div>
    </div>

    <!-- Safety Overview -->
    <div class="card bg-gradient-to-br <?= $riskBg ?> shadow-sm border mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="text-center md:text-left">
                    <div class="text-xs text-base-content/50 mb-1">Safety Score</div>
                    <div class="radial-progress <?= $riskColor ?> text-lg" style="--value:<?= $safetyScore ?>;--size:3.5rem;--thickness:4px;" role="progressbar"><?= $safetyScore ?></div>
                    <div class="text-xs font-bold <?= $riskColor ?> mt-1"><?= $riskLevel ?> Risk</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Slope Coverage</div>
                    <div class="text-3xl font-bold"><?= $coverageRatio ?>%</div>
                    <div class="text-xs text-base-content/50"><?= $totalCoverage ?>/<?= $slopes ?> slopes</div>
                    <progress class="progress <?= $coverageRatio >= 100 ? 'progress-success' : ($coverageRatio >= 50 ? 'progress-warning' : 'progress-error') ?> w-full mt-1" value="<?= $coverageRatio ?>" max="100"></progress>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Patrol Stations</div>
                    <div class="text-3xl font-bold"><?= $activeStations ?></div>
                    <div class="text-xs text-base-content/50"><?= count($patrolStations) ?> total</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Response Team</div>
                    <div class="text-3xl font-bold"><?= $patrolStaff + count($medics) ?></div>
                    <div class="text-xs text-base-content/50"><?= $patrolStaff ?> patrol, <?= count($medics) ?> medics</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Insurance</div>
                    <div class="text-3xl font-bold"><?= count($insurance) ?></div>
                    <div class="text-xs text-base-content/50">active policies</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($coverageRatio < 100) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><div>
        <p class="font-bold">Incomplete Coverage</p>
        <p class="text-sm"><?= $slopes - $totalCoverage ?> slope<?= ($slopes - $totalCoverage) > 1 ? 's have' : ' has' ?> no patrol coverage. Accidents on uncovered slopes cause lawsuits and reputation damage.</p>
    </div></div>
    <?php endif ?>

    <?php if ($patrolStaff === 0) : ?>
    <div class="alert alert-error mb-4"><i class="fa-solid fa-user-shield"></i><span>No ski patrol staff on duty. <a href="/staff/hire" class="link font-bold">Hire ski patrol</a> for emergency response.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <!-- Quick Actions -->
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-bolt mr-1"></i> Emergency Services</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
                <a href="/ski-patrol" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="card-body p-4 text-center">
                        <div class="w-14 h-14 rounded-xl bg-error/10 flex items-center justify-center mx-auto mb-2">
                            <i class="fa-solid fa-shield-halved text-2xl text-error"></i>
                        </div>
                        <div class="font-bold text-sm">Ski Patrol</div>
                        <div class="text-xs text-base-content/50 mb-2"><?= $activeStations ?> station<?= $activeStations !== 1 ? 's' : '' ?> active</div>
                        <div class="badge <?= $activeStations > 0 ? 'badge-success' : 'badge-error' ?> badge-sm"><?= $activeStations > 0 ? 'Operational' : 'No Stations' ?></div>
                    </div>
                </a>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4 text-center">
                        <div class="w-14 h-14 rounded-xl bg-warning/10 flex items-center justify-center mx-auto mb-2">
                            <i class="fa-solid fa-kit-medical text-2xl text-warning"></i>
                        </div>
                        <div class="font-bold text-sm">Medical Team</div>
                        <div class="text-xs text-base-content/50 mb-2"><?= count($medics) ?> medic<?= count($medics) !== 1 ? 's' : '' ?>, <?= $patrolStaff ?> patrol</div>
                        <?php if (count($medics) > 0) : ?>
                            <div class="flex flex-wrap gap-1 justify-center">
                                <?php foreach (array_slice($medics, 0, 3) as $m) : ?>
                                    <span class="badge badge-ghost badge-xs"><?= esc($m['name']) ?></span>
                                <?php endforeach ?>
                                <?php if (count($medics) > 3) : ?><span class="badge badge-ghost badge-xs">+<?= count($medics) - 3 ?></span><?php endif ?>
                            </div>
                        <?php else : ?>
                            <a href="/staff/hire" class="btn btn-warning btn-xs">Hire Medics</a>
                        <?php endif ?>
                    </div>
                </div>
                <a href="/insurance" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="card-body p-4 text-center">
                        <div class="w-14 h-14 rounded-xl bg-info/10 flex items-center justify-center mx-auto mb-2">
                            <i class="fa-solid fa-file-shield text-2xl text-info"></i>
                        </div>
                        <div class="font-bold text-sm">Insurance</div>
                        <div class="text-xs text-base-content/50 mb-2"><?= count($insurance) ?> polic<?= count($insurance) !== 1 ? 'ies' : 'y' ?> active</div>
                        <?php $totalCoverageAmt = array_sum(array_column($insurance, 'coverage_amount')); ?>
                        <div class="badge badge-info badge-sm"><?= currency($totalCoverageAmt) ?> covered</div>
                    </div>
                </a>
            </div>

            <!-- Incident Log -->
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-clipboard-list mr-1"></i> Incident Log</h2>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($incidents)) : ?>
                        <div class="text-center py-10">
                            <i class="fa-solid fa-circle-check text-success text-3xl mb-2"></i>
                            <p class="font-semibold">All Clear</p>
                            <p class="text-sm text-base-content/50">No incidents recorded. Keep your safety coverage up.</p>
                        </div>
                    <?php else : ?>
                        <div class="overflow-x-auto">
                            <table class="table table-sm">
                                <thead><tr><th>When</th><th>Type</th><th>Details</th></tr></thead>
                                <tbody>
                                <?php foreach ($incidents as $inc) : ?>
                                    <?php $typeColor = str_contains($inc['category'], 'accident') ? 'badge-error' : (str_contains($inc['category'], 'rescue') ? 'badge-warning' : 'badge-info'); ?>
                                    <tr>
                                        <td class="text-xs whitespace-nowrap"><?= timeAgo($inc['created_at']) ?></td>
                                        <td><span class="badge <?= $typeColor ?> badge-xs"><?= esc(ucfirst($inc['category'])) ?></span></td>
                                        <td class="text-xs"><?= esc($inc['description']) ?></td>
                                    </tr>
                                <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

        <div>
            <!-- Safety Checklist -->
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-list-check mr-1"></i> Safety Checklist</h2>
            <div class="card bg-base-100 shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="space-y-2">
                        <?php $checks = [
                            ['done' => $activeStations > 0, 'text' => 'Build a patrol station', 'link' => '/ski-patrol'],
                            ['done' => $patrolStaff > 0, 'text' => 'Hire ski patrol staff', 'link' => '/staff/hire'],
                            ['done' => count($medics) > 0, 'text' => 'Hire a medic', 'link' => '/staff/hire'],
                            ['done' => $coverageRatio >= 100, 'text' => 'Full slope coverage', 'link' => '/ski-patrol'],
                            ['done' => count($insurance) >= 3, 'text' => 'Activate 3+ insurance policies', 'link' => '/insurance'],
                            ['done' => $safetyScore >= 80, 'text' => 'Reach 80+ safety score', 'link' => null],
                        ]; ?>
                        <?php foreach ($checks as $check) : ?>
                            <div class="flex items-center gap-2">
                                <?php if ($check['done']) : ?>
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span class="text-sm line-through text-base-content/40"><?= $check['text'] ?></span>
                                <?php else : ?>
                                    <i class="fa-regular fa-circle text-base-content/30"></i>
                                    <?php if ($check['link']) : ?><a href="<?= $check['link'] ?>" class="text-sm link link-primary"><?= $check['text'] ?></a>
                                    <?php else : ?><span class="text-sm"><?= $check['text'] ?></span><?php endif ?>
                                <?php endif ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-link mr-1"></i> Related</h2>
            <div class="space-y-2">
                <a href="/ski-patrol" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
                    <i class="fa-solid fa-shield-halved text-error text-lg"></i>
                    <div class="flex-1"><div class="text-sm font-bold">Patrol Stations</div><div class="text-xs text-base-content/50">Build and manage stations</div></div>
                    <i class="fa-solid fa-chevron-right text-base-content/30"></i>
                </div></a>
                <a href="/staff/hire" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
                    <i class="fa-solid fa-user-plus text-primary text-lg"></i>
                    <div class="flex-1"><div class="text-sm font-bold">Hire Staff</div><div class="text-xs text-base-content/50">Patrol, medics, instructors</div></div>
                    <i class="fa-solid fa-chevron-right text-base-content/30"></i>
                </div></a>
                <a href="/insurance" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
                    <i class="fa-solid fa-file-shield text-info text-lg"></i>
                    <div class="flex-1"><div class="text-sm font-bold">Insurance</div><div class="text-xs text-base-content/50">Liability and accident coverage</div></div>
                    <i class="fa-solid fa-chevron-right text-base-content/30"></i>
                </div></a>
                <a href="/government" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-3 flex-row items-center gap-3">
                    <i class="fa-solid fa-building-columns text-warning text-lg"></i>
                    <div class="flex-1"><div class="text-sm font-bold">Safety Regulations</div><div class="text-xs text-base-content/50">Compliance reduces penalties</div></div>
                    <i class="fa-solid fa-chevron-right text-base-content/30"></i>
                </div></a>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
