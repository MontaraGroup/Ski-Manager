<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski Patrol<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $db = db_connect(); $userId = auth()->id();
    $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->get()->getResultArray();
    $patrolStaff = $db->table('staff')->where('user_id', $userId)->where('role', 'ski_patrol')->where('status', 'active')->get()->getResultArray();
    $medics = $db->table('staff')->where('user_id', $userId)->where('role', 'medic')->where('status', 'active')->get()->getResultArray();
    $openStations = array_filter($buildings, fn($b) => $b['status'] === 'open');
    $slopeCount = count($slopes);
    $coverageRatio = $slopeCount > 0 ? min(100, round($totalCapacity / $slopeCount * 100)) : 100;
    $safetyScore = min(100, round($coverageRatio * 0.4 + count($patrolStaff) * 8 + count($medics) * 12 + count($openStations) * 10));
    $riskLevel = $safetyScore >= 80 ? 'Low' : ($safetyScore >= 50 ? 'Moderate' : 'High');
    $riskColor = $safetyScore >= 80 ? 'text-success' : ($safetyScore >= 50 ? 'text-warning' : 'text-error');
    $recentIncidents = $db->table('activity_log')->where('user_id', $userId)->groupStart()->like('category', 'accident')->orLike('category', 'rescue')->orLike('category', 'inspection')->groupEnd()->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray();
    $stationTypes = [1 => ['name' => 'First Aid Post', 'icon' => 'fa-solid fa-kit-medical', 'desc' => 'Basic first aid, radio comms'], 2 => ['name' => 'Patrol Station', 'icon' => 'fa-solid fa-shield-halved', 'desc' => 'Full medical suite, toboggan, AED'], 3 => ['name' => 'Rescue Center', 'icon' => 'fa-solid fa-helicopter', 'desc' => 'Helipad, trauma response, ICU']];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-shield-halved mr-2 text-error"></i>Ski Patrol Command</h1>
                <p class="text-sm text-base-content/50">Safety operations and emergency response</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Safety Dashboard -->
    <div class="card bg-gradient-to-br from-error/5 to-error/10 shadow-sm border border-error/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Safety Score</div>
                    <div class="radial-progress <?= $riskColor ?> text-lg" style="--value:<?= $safetyScore ?>;--size:3.5rem;--thickness:4px;" role="progressbar"><?= $safetyScore ?></div>
                </div>
                <div><div class="text-xs text-base-content/50 mb-1">Risk Level</div><div class="text-2xl font-bold <?= $riskColor ?>"><?= $riskLevel ?></div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Coverage</div><div class="text-2xl font-bold"><?= $coverageRatio ?>%</div><div class="text-xs text-base-content/50"><?= $totalCapacity ?>/<?= $slopeCount ?> slopes</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Patrol</div><div class="text-2xl font-bold"><?= count($patrolStaff) ?></div><div class="text-xs text-base-content/50">on duty</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Medics</div><div class="text-2xl font-bold"><?= count($medics) ?></div><div class="text-xs text-base-content/50">on standby</div></div>
                <div><div class="text-xs text-base-content/50 mb-1">Stations</div><div class="text-2xl font-bold"><?= count($buildings) ?></div><div class="text-xs text-base-content/50"><?= count($openStations) ?> active</div></div>
            </div>
        </div>
    </div>

    <?php if ($coverageRatio < 100) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><div>
        <p class="font-bold">Insufficient Coverage</p>
        <p class="text-sm"><?= $slopeCount - $totalCapacity ?> slope<?= ($slopeCount - $totalCapacity) > 1 ? 's' : '' ?> have no patrol coverage. Accidents on uncovered slopes lead to lawsuits and reputation damage.</p>
    </div></div>
    <?php endif ?>

    <?php if (count($patrolStaff) === 0 && !empty($buildings)) : ?>
    <div class="alert alert-error mb-4"><i class="fa-solid fa-user-shield"></i><span>Stations built but no patrol staff. <a href="/staff/hire" class="link font-bold">Hire ski patrol</a> immediately.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-location-dot mr-1"></i> Patrol Stations</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12"><i class="fa-solid fa-shield-halved text-5xl text-base-content/15 mb-3"></i><p class="font-semibold">No patrol stations</p><p class="text-sm text-base-content/50 mt-1">Your resort has zero emergency coverage. Build a station now.</p></div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $st = $stationTypes[$b['level']] ?? $stationTypes[1]; ?>
                    <div class="card bg-base-100 shadow-sm <?= !$isOpen ? 'opacity-60' : '' ?>"><div class="card-body p-4">
                        <div class="flex items-start gap-4">
                            <div class="w-14 h-14 rounded-xl <?= $isOpen ? 'bg-error/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                                <i class="<?= $st['icon'] ?> text-2xl <?= $isOpen ? 'text-error' : 'text-base-content/30' ?>"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div><span class="font-bold"><?= esc($b['name']) ?></span></div>
                                    <span class="badge <?= $isOpen ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $isOpen ? 'Active' : 'Inactive' ?></span>
                                </div>
                                <div class="text-xs text-base-content/50 mb-2"><i class="<?= $st['icon'] ?> mr-1"></i><?= $st['desc'] ?></div>
                                <div class="grid grid-cols-3 gap-1 text-center text-xs mb-3">
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $b['capacity'] ?></div><div class="text-[10px] text-base-content/50">Sectors</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-error"><?= currency($b['upkeep_per_day']) ?></div><div class="text-[10px] text-base-content/50">Daily Cost</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $cond ?>%</div><div class="text-[10px] text-base-content/50">Condition</div></div>
                                </div>
                                <div class="flex gap-1">
                                    <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?><button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-error' ?>"><?= $isOpen ? 'Deactivate' : 'Activate' ?></button></form>
                                    <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" data-confirm="Upgrade this station?"><?= csrf_field() ?><button class="btn btn-info btn-xs gap-1"><i class="fa-solid fa-arrow-up"></i> Upgrade</button></form><?php endif ?>
                                    <form action="/buildings/sell/<?= $b['id'] ?>" method="post" data-confirm="Remove this station?"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>

            <!-- Incident Log -->
            <?php if (!empty($recentIncidents)) : ?>
            <h2 class="text-lg font-bold mt-6 mb-3"><i class="fa-solid fa-clipboard-list mr-1"></i> Recent Incidents</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Date</th><th>Type</th><th>Details</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentIncidents as $inc) : ?>
                        <tr><td class="text-xs"><?= timeAgo($inc['created_at']) ?></td><td><span class="badge badge-ghost badge-xs"><?= esc($inc['category']) ?></span></td><td class="text-xs"><?= esc($inc['description']) ?></td></tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div></div></div>
            <?php endif ?>
        </div>
        <div>
            <h2 class="text-lg font-bold mb-3">Build Station</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : $st = $stationTypes[$lvl]; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?><input type="hidden" name="type" value="ski_patrol"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left" onclick="return confirm('Build this station?')"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-1"><i class="<?= $st['icon'] ?> text-error"></i><span class="font-semibold text-sm"><?= $info['name'] ?></span></div>
                        <p class="text-xs text-base-content/50 mb-1"><?= $st['desc'] ?></p>
                        <div class="flex justify-between text-xs"><span>Covers <?= $info['capacity'] ?> sector<?= $info['capacity'] > 1 ? 's' : '' ?></span><span class="font-bold text-primary"><?= currency($info['cost']) ?></span></div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>

            <!-- Staff Panel -->
            <h2 class="text-lg font-bold mt-6 mb-3">Safety Team</h2>
            <div class="space-y-2">
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><i class="fa-solid fa-user-shield text-error"></i><div><div class="text-sm font-bold">Ski Patrol</div><div class="text-xs text-base-content/50"><?= count($patrolStaff) ?> on duty</div></div></div>
                        <a href="/staff/hire" class="btn btn-xs btn-outline">Hire</a>
                    </div>
                    <?php if (!empty($patrolStaff)) : ?><div class="flex flex-wrap gap-1 mt-2"><?php foreach (array_slice($patrolStaff, 0, 6) as $sp) : ?><span class="badge badge-ghost badge-xs"><?= esc($sp['name']) ?> Lv.<?= $sp['level'] ?></span><?php endforeach ?><?php if (count($patrolStaff) > 6) : ?><span class="badge badge-ghost badge-xs">+<?= count($patrolStaff) - 6 ?> more</span><?php endif ?></div><?php endif ?>
                </div></div>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2"><i class="fa-solid fa-kit-medical text-success"></i><div><div class="text-sm font-bold">Medics</div><div class="text-xs text-base-content/50"><?= count($medics) ?> on standby</div></div></div>
                        <a href="/staff/hire" class="btn btn-xs btn-outline">Hire</a>
                    </div>
                    <?php if (!empty($medics)) : ?><div class="flex flex-wrap gap-1 mt-2"><?php foreach (array_slice($medics, 0, 6) as $m) : ?><span class="badge badge-ghost badge-xs"><?= esc($m['name']) ?> Lv.<?= $m['level'] ?></span><?php endforeach ?></div><?php endif ?>
                </div></div>
            </div>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-3">
                <h3 class="font-semibold text-xs mb-2"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i>Safety Compliance</h3>
                <ul class="text-xs text-base-content/60 space-y-1">
                    <li><i class="fa-solid fa-file-shield mr-1"></i> <a href="/compliance?tab=government" class="link link-primary">Safety regulations</a> reduce accident penalties</li>
                    <li><i class="fa-solid fa-shield-halved mr-1"></i> <a href="/compliance?tab=insurance" class="link link-primary">Liability insurance</a> covers lawsuit costs</li>
                    <li><i class="fa-solid fa-mountain mr-1"></i> Each slope needs patrol coverage</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
