<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Terrain Parks - Freestyle Facilities<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8 space-y-6">

    <!-- Header & Navigation -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to Dashboard">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl lg:text-3xl font-extrabold tracking-tight">
                        <i class="fa-solid fa-person-snowboarding text-warning mr-2"></i>Terrain Parks
                    </h1>
                    <span class="badge badge-warning badge-sm font-semibold">Freestyle</span>
                </div>
                <p class="text-sm text-base-content/70 mt-0.5">
                    Design and manage halfpipes, kickers, and jib parks to attract snowboarders and freestyle skiers.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto">
            <div class="bg-base-200/80 px-3 py-1.5 rounded-lg border border-base-300 text-xs">
                <span class="text-base-content/60">Available Cash:</span>
                <span class="font-bold font-mono text-success ml-1"><?= currency($cash) ?></span>
            </div>
            <a href="/staff/hire" class="btn btn-outline btn-sm gap-1.5">
                <i class="fa-solid fa-user-plus text-xs"></i> Hire Staff
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (session('success')) : ?>
        <div class="alert alert-success shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-lg"></i>
                <span class="text-sm font-medium"><?= esc(session('success')) ?></span>
            </div>
        </div>
    <?php endif ?>
    <?php if (session('error')) : ?>
        <div class="alert alert-error shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-lg"></i>
                <span class="text-sm font-medium"><?= esc(session('error')) ?></span>
            </div>
        </div>
    <?php endif ?>

    <!-- Overview Stats Matrix -->
    <?php
        $openParks      = array_filter($parks, fn($p) => $p['status'] === 'open');
        $buildingParks  = array_filter($parks, fn($p) => $p['status'] === 'under_construction');
        $maintParks     = array_filter($parks, fn($p) => $p['status'] === 'maintenance');
        $avgCondition   = count($parks) > 0 ? (int) round(array_sum(array_column($parks, 'condition_pct')) / count($parks)) : 100;
        $totalCapacity  = 0;
        foreach ($openParks as $op) {
            $cfg = \App\Models\TerrainParkModel::getConfig($op['park_type'], $op['size']);
            $totalCapacity += $cfg['capacity'] ?? 0;
        }
    ?>
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3.5 text-center">
                <span class="text-xs font-semibold text-base-content/60">Active Parks</span>
                <div class="text-2xl font-black text-success mt-0.5"><?= count($openParks) ?></div>
                <span class="text-[11px] text-base-content/50"><?= count($buildingParks) ?> building · <?= count($maintParks) ?> down</span>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3.5 text-center">
                <span class="text-xs font-semibold text-base-content/60">Daily Riders</span>
                <div class="text-2xl font-black text-info mt-0.5"><?= number_format($totalVisitors) ?></div>
                <span class="text-[11px] text-base-content/50">Cap: <?= number_format($totalCapacity) ?>/day</span>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3.5 text-center">
                <span class="text-xs font-semibold text-base-content/60">Park Admissions</span>
                <div class="text-2xl font-black text-success mt-0.5"><?= currency($totalDailyRevenue) ?></div>
                <span class="text-[11px] text-base-content/50">~<?= currency(25) ?>/rider pass</span>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3.5 text-center">
                <span class="text-xs font-semibold text-base-content/60">Daily Upkeep</span>
                <div class="text-2xl font-black text-error mt-0.5"><?= currency($totalDailyUpkeep) ?></div>
                <span class="text-[11px] text-base-content/50">Maintenance & grooming</span>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3.5 text-center">
                <span class="text-xs font-semibold text-base-content/60">Net Profit/Loss</span>
                <div class="text-2xl font-black <?= $netDailyProfit >= 0 ? 'text-success' : 'text-error' ?> mt-0.5">
                    <?= ($netDailyProfit >= 0 ? '+' : '') . currency($netDailyProfit) ?>
                </div>
                <span class="text-[11px] text-base-content/50">Per daily game tick</span>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-3.5 text-center">
                <span class="text-xs font-semibold text-base-content/60">Avg Condition</span>
                <div class="text-2xl font-black <?= $avgCondition >= 70 ? 'text-success' : ($avgCondition >= 40 ? 'text-warning' : 'text-error') ?> mt-0.5">
                    <?= $avgCondition ?>%
                </div>
                <span class="text-[11px] text-base-content/50">Decay: -<?= number_format($dailyDecay, 1) ?>%/day</span>
            </div>
        </div>
    </div>

    <!-- Park Crew Efficiency Banner -->
    <div class="card bg-base-100 shadow-sm border border-base-200 overflow-hidden">
        <div class="card-body p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-base-200">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-warning/15 flex items-center justify-center text-warning">
                        <i class="fa-solid fa-users-gear text-base"></i>
                    </div>
                    <div>
                        <h2 class="font-bold text-sm leading-tight">Park Crew & Maintenance Team</h2>
                        <p class="text-xs text-base-content/60">Crew shapers tune jumps, salt rails, and slow down condition degradation.</p>
                    </div>
                </div>
                <form action="/terrain-parks/hire-crew" method="post" class="flex items-center gap-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning btn-sm gap-1.5 shadow-sm">
                        <i class="fa-solid fa-user-plus text-xs"></i> Hire Shaper (<?= currency(2000) ?>)
                    </button>
                </form>
            </div>

            <?php if (!empty($parkCrew)) : ?>
                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-base-content/70">
                    <span class="badge badge-success badge-sm gap-1">
                        <i class="fa-solid fa-shield-halved text-[10px]"></i>
                        <?= count($parkCrew) ?> Shaper(s) Active
                    </span>
                    <span>Decay slowed by <strong>+<?= number_format($crewSavings, 1) ?>%</strong> per tick</span>
                    <span class="hidden sm:inline">·</span>
                    <span>Total Crew Payroll: <strong><?= currency(array_sum(array_column($parkCrew, 'salary'))) ?>/day</strong></span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2.5 mt-3">
                    <?php foreach ($parkCrew as $crew) : ?>
                        <div class="flex items-center gap-2.5 bg-base-200/60 border border-base-300/40 rounded-xl p-2.5">
                            <div class="w-8 h-8 rounded-lg bg-base-300/70 flex items-center justify-center font-bold text-xs">
                                <i class="fa-solid fa-user text-warning text-xs"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold truncate"><?= esc($crew['name']) ?></div>
                                <div class="text-[11px] text-base-content/60 flex items-center gap-1.5">
                                    <span>
                                        <?php for ($i = 0; $i < min($crew['level'], 5); $i++) : ?>
                                            <i class="fa-solid fa-star text-warning text-[9px]"></i>
                                        <?php endfor ?>
                                    </span>
                                    <span>· <?= $crew['morale'] ?>% morale</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php else : ?>
                <div class="alert alert-warning/15 border border-warning/30 mt-3 p-3 text-xs flex items-center gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-warning text-sm"></i>
                    <span><strong>No Park Crew Assigned:</strong> Without park shapers, your features suffer maximum decay of <strong>-2.0% per day</strong>. Hire shapers to keep features running in prime condition!</span>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Active Features List -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-bold flex items-center gap-2">
                <i class="fa-solid fa-mountain-sun text-warning"></i>Your Terrain Park Features
                <span class="badge badge-neutral badge-sm"><?= count($parks) ?></span>
            </h2>
            <?php if (!empty($parks)) : ?>
                <a href="#build-section" class="btn btn-primary btn-xs gap-1">
                    <i class="fa-solid fa-plus"></i> Build More
                </a>
            <?php endif ?>
        </div>

        <?php if (!empty($parks)) : ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($parks as $park) : ?>
                    <?php
                        $cond           = (float) $park['condition_pct'];
                        $isBuilding     = $park['status'] === 'under_construction';
                        $isMaint        = $park['status'] === 'maintenance';
                        $isOpen         = $park['status'] === 'open';
                        $currentSize    = $park['size'];
                        $nextSize       = \App\Models\TerrainParkModel::getNextSize($currentSize);
                        $cfg            = \App\Models\TerrainParkModel::getConfig($park['park_type'], $currentSize);
                        $repairCost     = max(50, (int) round(($cfg['cost'] ?? 20000) * 0.15 * (1 - ($cond / 100))));
                        $refundCost     = (int) round(($cfg['cost'] ?? 20000) * 0.25);
                        $lastEventDay   = (int) ($park['last_event_day'] ?? 0);
                        $canHostEvent   = $isOpen && $cond >= 70 && ($lastEventDay === 0 || ($gameDay - $lastEventDay) >= 3);
                    ?>
                    <div class="card bg-base-100 shadow-sm border <?= $isBuilding ? 'border-warning/50' : ($isMaint ? 'border-error/50' : 'border-base-200') ?> transition-all hover:border-base-300">
                        <div class="card-body p-4 sm:p-5">
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 rounded-xl bg-warning/10 border border-warning/20 flex items-center justify-center text-warning text-xl shrink-0">
                                        <i class="fa-solid <?= \App\Models\TerrainParkModel::getIcon($park['park_type']) ?>"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-extrabold text-base leading-tight"><?= esc($park['name']) ?></h3>
                                        <div class="flex flex-wrap items-center gap-1.5 mt-1 text-xs text-base-content/60">
                                            <span class="badge badge-outline badge-xs font-semibold"><?= \App\Models\TerrainParkModel::getLabel($park['park_type']) ?></span>
                                            <span class="badge badge-ghost badge-xs uppercase font-mono font-bold"><?= $park['size'] ?> Tier</span>
                                            <?php if (!empty($park['slope_id']) && isset($slopeMap[$park['slope_id']])) : ?>
                                                <span class="badge badge-info/10 text-info border-info/20 badge-xs font-medium">
                                                    <i class="fa-solid fa-mountain mr-1"></i><?= esc($slopeMap[$park['slope_id']]) ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="badge badge-neutral badge-xs">Standalone Zone</span>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right shrink-0">
                                    <?php if ($isBuilding) : ?>
                                        <span class="badge badge-warning badge-sm font-semibold gap-1">
                                            <i class="fa-solid fa-hammer text-[10px]"></i> Building (<?= $park['build_days_left'] ?>d)
                                        </span>
                                    <?php elseif ($isMaint) : ?>
                                        <span class="badge badge-error badge-sm font-semibold gap-1">
                                            <i class="fa-solid fa-triangle-exclamation text-[10px]"></i> Needs Repair
                                        </span>
                                    <?php elseif ($isOpen) : ?>
                                        <span class="badge badge-success badge-sm font-semibold gap-1">
                                            <i class="fa-solid fa-circle-check text-[10px]"></i> Open
                                        </span>
                                    <?php else : ?>
                                        <span class="badge badge-ghost badge-sm font-semibold">Closed</span>
                                    <?php endif ?>
                                </div>
                            </div>

                            <!-- Metrics Strip -->
                            <div class="grid grid-cols-4 gap-2 text-center bg-base-200/50 rounded-xl p-2.5 my-3 border border-base-300/40">
                                <div>
                                    <div class="text-[10px] text-base-content/60 uppercase font-semibold">Riders/Day</div>
                                    <div class="text-sm font-black font-mono text-info mt-0.5"><?= number_format($park['daily_visitors']) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-base-content/60 uppercase font-semibold">Revenue</div>
                                    <div class="text-sm font-black font-mono text-success mt-0.5"><?= currency($park['daily_revenue'] ?? 0) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-base-content/60 uppercase font-semibold">Upkeep</div>
                                    <div class="text-sm font-black font-mono text-error mt-0.5">-<?= currency($cfg['upkeep'] ?? 0) ?></div>
                                </div>
                                <div>
                                    <div class="text-[10px] text-base-content/60 uppercase font-semibold">Popularity</div>
                                    <div class="text-sm font-black font-mono text-warning mt-0.5"><?= $park['popularity'] ?>%</div>
                                </div>
                            </div>

                            <!-- Condition Gauge -->
                            <div class="space-y-1 mb-4">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-base-content/60 font-medium">Feature Integrity:</span>
                                    <span class="font-mono font-bold <?= $cond >= 70 ? 'text-success' : ($cond >= 40 ? 'text-warning' : 'text-error') ?>">
                                        <?= number_format($cond, 1) ?>%
                                        <?php if ($cond < 30) : ?><span class="text-error text-[10px] uppercase font-bold ml-1">(Closed for safety)</span><?php endif ?>
                                    </span>
                                </div>
                                <div class="w-full bg-base-300 rounded-full h-2 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 <?= $cond >= 70 ? 'bg-success' : ($cond >= 40 ? 'bg-warning' : 'bg-error') ?>" style="width: <?= min(100, max(5, $cond)) ?>%"></div>
                                </div>
                            </div>

                            <!-- Action Bar -->
                            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-base-200">
                                <div class="flex items-center gap-1.5">
                                    <?php if (! $isBuilding) : ?>
                                        <!-- Open / Close Toggle -->
                                        <form action="/terrain-parks/toggle/<?= $park['id'] ?>" method="post">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs <?= $isOpen ? 'btn-ghost' : 'btn-success' ?> gap-1">
                                                <i class="fa-solid <?= $isOpen ? 'fa-pause' : 'fa-play' ?>"></i>
                                                <?= $isOpen ? 'Close' : 'Open' ?>
                                            </button>
                                        </form>

                                        <!-- Repair Button -->
                                        <?php if ($cond < 99.5) : ?>
                                            <button type="button" onclick="document.getElementById('repair-modal-<?= $park['id'] ?>').showModal()" class="btn btn-xs btn-outline btn-info gap-1">
                                                <i class="fa-solid fa-wrench"></i> Repair (<?= currency($repairCost) ?>)
                                            </button>
                                        <?php endif ?>

                                        <!-- Host Freestyle Event Button -->
                                        <button type="button" onclick="document.getElementById('event-modal-<?= $park['id'] ?>').showModal()" class="btn btn-xs btn-outline btn-warning gap-1" <?= ! $canHostEvent ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-trophy"></i> Host Jam
                                        </button>
                                    <?php endif ?>
                                </div>

                                <div class="flex items-center gap-1.5 ml-auto">
                                    <!-- Upgrade Button -->
                                    <?php if ($nextSize && ! $isBuilding) : ?>
                                        <?php
                                            $nextCfg  = \App\Models\TerrainParkModel::getConfig($park['park_type'], $nextSize);
                                            $costDiff = max(1000, ($nextCfg['cost'] ?? 0) - ($cfg['cost'] ?? 0));
                                        ?>
                                        <button type="button" onclick="document.getElementById('upgrade-modal-<?= $park['id'] ?>').showModal()" class="btn btn-xs btn-primary gap-1">
                                            <i class="fa-solid fa-arrow-up"></i> Upgrade to <?= ucfirst($nextSize) ?>
                                        </button>
                                    <?php endif ?>

                                    <!-- Demolish Button -->
                                    <button type="button" onclick="document.getElementById('demolish-modal-<?= $park['id'] ?>').showModal()" class="btn btn-xs btn-ghost text-error" aria-label="Demolish feature">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modals for Feature Actions -->
                    <!-- Repair Modal -->
                    <dialog id="repair-modal-<?= $park['id'] ?>" class="modal modal-bottom sm:modal-middle">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <i class="fa-solid fa-wrench text-info"></i> Repair <?= esc($park['name']) ?>
                            </h3>
                            <p class="py-2 text-sm text-base-content/70">
                                Restores snow surface, replaces damaged rails, and grooms kickers back to 100% condition.
                            </p>
                            <div class="bg-base-200 p-3 rounded-lg text-sm space-y-1 my-2">
                                <div class="flex justify-between"><span>Current Condition:</span><span class="font-bold font-mono"><?= number_format($cond, 1) ?>%</span></div>
                                <div class="flex justify-between"><span>Repair Cost:</span><span class="font-bold font-mono text-error"><?= currency($repairCost) ?></span></div>
                                <div class="flex justify-between"><span>Cash After Repair:</span><span class="font-bold font-mono <?= ($cash - $repairCost) >= 0 ? 'text-success' : 'text-error' ?>"><?= currency($cash - $repairCost) ?></span></div>
                            </div>
                            <div class="modal-action">
                                <form method="dialog"><button class="btn btn-sm btn-ghost">Cancel</button></form>
                                <form action="/terrain-parks/repair/<?= $park['id'] ?>" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-info" <?= $cash < $repairCost ? 'disabled' : '' ?>>
                                        Confirm Repair
                                    </button>
                                </form>
                            </div>
                        </div>
                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                    </dialog>

                    <!-- Upgrade Modal -->
                    <?php if ($nextSize) : ?>
                    <dialog id="upgrade-modal-<?= $park['id'] ?>" class="modal modal-bottom sm:modal-middle">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <i class="fa-solid fa-arrow-up text-primary"></i> Upgrade to <?= ucfirst($nextSize) ?> Tier
                            </h3>
                            <p class="py-2 text-sm text-base-content/70">
                                Expanding <?= esc($park['name']) ?> increases visitor capacity, doubles trick diversity, and unlocks higher popularity.
                            </p>
                            <div class="bg-base-200 p-3 rounded-lg text-sm space-y-1.5 my-2">
                                <div class="flex justify-between text-xs text-base-content/60"><span>New Capacity:</span><strong class="text-success"><?= $nextCfg['capacity'] ?? 0 ?> riders/day</strong></div>
                                <div class="flex justify-between text-xs text-base-content/60"><span>Construction Duration:</span><strong><?= max(1, ($nextCfg['build_days'] ?? 1) - ($cfg['build_days'] ?? 0)) ?> day(s)</strong></div>
                                <div class="flex justify-between pt-1 border-t border-base-300"><span>Upgrade Price:</span><strong class="font-mono text-primary"><?= currency($costDiff) ?></strong></div>
                            </div>
                            <div class="modal-action">
                                <form method="dialog"><button class="btn btn-sm btn-ghost">Cancel</button></form>
                                <form action="/terrain-parks/upgrade/<?= $park['id'] ?>" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-primary" <?= $cash < $costDiff ? 'disabled' : '' ?>>
                                        Confirm Upgrade (<?= currency($costDiff) ?>)
                                    </button>
                                </form>
                            </div>
                        </div>
                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                    </dialog>
                    <?php endif ?>

                    <!-- Host Jam Modal -->
                    <dialog id="event-modal-<?= $park['id'] ?>" class="modal modal-bottom sm:modal-middle">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg flex items-center gap-2">
                                <i class="fa-solid fa-trophy text-warning"></i> Host Freestyle Park Jam
                            </h3>
                            <p class="py-2 text-sm text-base-content/70">
                                Sponsor a rail jam or big air showcase with local pros. Boosts park popularity by <strong>+15% to +25%</strong> and generates guest buzz across your entire resort.
                            </p>
                            <div class="bg-base-200 p-3 rounded-lg text-sm space-y-1 my-2">
                                <div class="flex justify-between"><span>Event Purse & Setup:</span><span class="font-bold font-mono text-warning"><?= currency(2500) ?></span></div>
                                <div class="flex justify-between"><span>Feature Wear:</span><span class="font-bold font-mono text-error">-8% condition</span></div>
                                <div class="flex justify-between"><span>Cooldown:</span><span>3 game days between events</span></div>
                            </div>
                            <div class="modal-action">
                                <form method="dialog"><button class="btn btn-sm btn-ghost">Cancel</button></form>
                                <form action="/terrain-parks/host-event/<?= $park['id'] ?>" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-warning" <?= ($cash < 2500 || ! $canHostEvent) ? 'disabled' : '' ?>>
                                        Host Competition (<?= currency(2500) ?>)
                                    </button>
                                </form>
                            </div>
                        </div>
                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                    </dialog>

                    <!-- Demolish Modal -->
                    <dialog id="demolish-modal-<?= $park['id'] ?>" class="modal modal-bottom sm:modal-middle">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg text-error flex items-center gap-2">
                                <i class="fa-solid fa-triangle-exclamation"></i> Demolish <?= esc($park['name']) ?>?
                            </h3>
                            <p class="py-2 text-sm text-base-content/70">
                                This will permanently remove the feature from your mountain. You will salvage <strong><?= currency($refundCost) ?></strong> (25% refund).
                            </p>
                            <div class="modal-action">
                                <form method="dialog"><button class="btn btn-sm btn-ghost">Cancel</button></form>
                                <form action="/terrain-parks/demolish/<?= $park['id'] ?>" method="post">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-error">
                                        Demolish & Salvage
                                    </button>
                                </form>
                            </div>
                        </div>
                        <form method="dialog" class="modal-backdrop"><button>close</button></form>
                    </dialog>
                <?php endforeach ?>
            </div>
        <?php else : ?>
            <div class="card bg-base-100 shadow-sm border border-base-200 text-center py-12 px-4">
                <div class="w-16 h-16 rounded-2xl bg-warning/10 text-warning flex items-center justify-center mx-auto mb-4 text-3xl">
                    <i class="fa-solid fa-person-snowboarding"></i>
                </div>
                <h3 class="text-lg font-bold">No Freestyle Features Built Yet</h3>
                <p class="text-sm text-base-content/60 max-w-md mx-auto mt-1 mb-4">
                    Terrain parks turn quiet slopes into revenue generators. Build your first halfpipe, jump line, or rail garden below to attract riders!
                </p>
                <div>
                    <a href="#build-section" class="btn btn-warning btn-sm gap-2">
                        <i class="fa-solid fa-hammer"></i> Build First Feature
                    </a>
                </div>
            </div>
        <?php endif ?>
    </div>

    <!-- Interactive Feature Builder Section -->
    <div id="build-section" class="card bg-base-100 shadow-sm border border-base-200">
        <div class="card-body p-4 sm:p-6">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center font-bold">
                    <i class="fa-solid fa-hammer text-sm"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold">Construct New Feature</h2>
                    <p class="text-xs text-base-content/60">Select a feature type, size tier, and optionally attach it to an existing mountain slope.</p>
                </div>
            </div>

            <form action="/terrain-parks/build" method="post" id="parkBuildForm" class="space-y-5">
                <?= csrf_field() ?>

                <!-- Step 1: Feature Type Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-2">
                        1. Select Feature Type
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3" id="typeCardsContainer">
                        <?php foreach ($parkConfig as $key => $cfg) : ?>
                            <label class="relative flex flex-col p-3.5 rounded-xl border border-base-300 bg-base-200/40 hover:bg-base-200 cursor-pointer transition-all feature-type-card" data-type="<?= $key ?>">
                                <input type="radio" name="park_type" value="<?= $key ?>" class="sr-only" <?= $key === 'jump_line' ? 'checked' : '' ?> required>
                                <div class="flex items-center gap-2 mb-1.5">
                                    <div class="w-8 h-8 rounded-lg bg-warning/15 text-warning flex items-center justify-center text-sm shrink-0">
                                        <i class="fa-solid <?= \App\Models\TerrainParkModel::getIcon($key) ?>"></i>
                                    </div>
                                    <span class="font-bold text-sm"><?= esc($cfg['label']) ?></span>
                                </div>
                                <p class="text-xs text-base-content/60 flex-1"><?= esc($cfg['description'] ?? '') ?></p>
                            </label>
                        <?php endforeach ?>
                    </div>
                </div>

                <!-- Step 2: Tier / Size Selector -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-base-content/70 mb-2">
                        2. Select Size Tier
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-base-300 bg-base-200/40 hover:bg-base-200 cursor-pointer transition-all size-card" data-size="small">
                            <input type="radio" name="size" value="small" class="radio radio-primary radio-sm">
                            <div>
                                <div class="font-bold text-sm">Small Tier</div>
                                <div class="text-[11px] text-base-content/60">Starter setup · Fast build</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-base-300 bg-base-200/40 hover:bg-base-200 cursor-pointer transition-all size-card" data-size="medium">
                            <input type="radio" name="size" value="medium" class="radio radio-primary radio-sm" checked>
                            <div>
                                <div class="font-bold text-sm">Medium Tier</div>
                                <div class="text-[11px] text-base-content/60">Balanced riders & revenue</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3 rounded-xl border border-base-300 bg-base-200/40 hover:bg-base-200 cursor-pointer transition-all size-card" data-size="large">
                            <input type="radio" name="size" value="large" class="radio radio-primary radio-sm">
                            <div>
                                <div class="font-bold text-sm">Large / Pro Tier</div>
                                <div class="text-[11px] text-base-content/60">Championship scale · Max riders</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Name & Slope Placement -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <span class="block text-xs text-base-content/60 mb-1" id="name-hint">Custom Name (optional)</span>
                        <input
                            type="text"
                            name="name"
                            id="parkNameInput"
                            class="input input-bordered input-sm w-full"
                            placeholder="e.g. Eagle Peak Stunt Park"
                            maxlength="100"
                            autocomplete="off"
                            aria-describedby="name-hint"
                        >
                    </div>

                    <div>
                        <span class="block text-xs text-base-content/60 mb-1" id="slope-hint">Attach to Slope</span>
                        <select name="slope_id" id="slopeSelect" class="select select-bordered select-sm w-full" aria-describedby="slope-hint">
                            <option value="">Standalone Freestyle Zone</option>
                            <?php foreach ($slopes as $slope) : ?>
                                <option value="<?= $slope['id'] ?>"><?= esc($slope['name'] ?? ('Slope #' . $slope['id'])) ?> (<?= ucfirst($slope['difficulty'] ?? 'Standard') ?>)</option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>

                <!-- Live Cost & Specifications Preview -->
                <div id="liveSpecsPreview" class="bg-base-200/80 border border-base-300 rounded-xl p-4">
                    <!-- Populated by JavaScript below -->
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-between gap-4 pt-2">
                    <div id="affordabilityNotice" class="text-xs"></div>
                    <button type="submit" id="buildSubmitBtn" class="btn btn-primary btn-sm gap-2 px-5">
                        <i class="fa-solid fa-hammer"></i> Begin Construction
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Interactive Client-side Script -->
<script>
(() => {
    const config = <?= json_encode($parkConfig) ?>;
    const playerCash = <?= (int) $cash ?>;

    const form = document.getElementById('parkBuildForm');
    const preview = document.getElementById('liveSpecsPreview');
    const submitBtn = document.getElementById('buildSubmitBtn');
    const notice = document.getElementById('affordabilityNotice');
    const typeCards = document.querySelectorAll('.feature-type-card');
    const sizeCards = document.querySelectorAll('.size-card');

    function getSelectedType() {
        const checked = form.querySelector('input[name="park_type"]:checked');
        return checked ? checked.value : 'jump_line';
    }

    function getSelectedSize() {
        const checked = form.querySelector('input[name="size"]:checked');
        return checked ? checked.value : 'medium';
    }

    function updatePreview() {
        const type = getSelectedType();
        const size = getSelectedSize();

        // Highlight active visual cards
        typeCards.forEach(c => {
            const isMatch = c.dataset.type === type;
            c.classList.toggle('border-primary', isMatch);
            c.classList.toggle('bg-primary/5', isMatch);
        });

        sizeCards.forEach(c => {
            const isMatch = c.dataset.size === size;
            c.classList.toggle('border-primary', isMatch);
            c.classList.toggle('bg-primary/5', isMatch);
        });

        if (!config[type] || !config[type].sizes[size]) {
            preview.innerHTML = '<div class="text-xs text-base-content/50">Configuration unavailable</div>';
            return;
        }

        const data = config[type].sizes[size];
        const canAfford = playerCash >= data.cost;

        preview.innerHTML = `
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="space-y-0.5">
                    <span class="text-base-content/60">Construction Cost</span>
                    <div class="text-base font-extrabold font-mono ${canAfford ? 'text-primary' : 'text-error'}">
                        $${data.cost.toLocaleString()}
                    </div>
                </div>
                <div class="space-y-0.5">
                    <span class="text-base-content/60">Build Timeline</span>
                    <div class="text-base font-extrabold font-mono text-warning">
                        ${data.build_days} Day${data.build_days > 1 ? 's' : ''}
                    </div>
                </div>
                <div class="space-y-0.5">
                    <span class="text-base-content/60">Daily Upkeep</span>
                    <div class="text-base font-extrabold font-mono text-base-content">
                        $${data.upkeep.toLocaleString()}/day
                    </div>
                </div>
                <div class="space-y-0.5">
                    <span class="text-base-content/60">Rider Capacity</span>
                    <div class="text-base font-extrabold font-mono text-success">
                        ${data.capacity} riders/day
                    </div>
                </div>
            </div>
        `;

        if (canAfford) {
            submitBtn.disabled = false;
            notice.innerHTML = `<span class="text-success font-medium"><i class="fa-solid fa-circle-check mr-1"></i>Sufficient funds available</span>`;
        } else {
            submitBtn.disabled = true;
            const diff = data.cost - playerCash;
            notice.innerHTML = `<span class="text-error font-medium"><i class="fa-solid fa-circle-exclamation mr-1"></i>Short by $${diff.toLocaleString()} to build this tier</span>`;
        }
    }

    form.querySelectorAll('input[name="park_type"]').forEach(r => r.addEventListener('change', updatePreview));
    form.querySelectorAll('input[name="size"]').forEach(r => r.addEventListener('change', updatePreview));

    // Allow clicking card to check radio
    typeCards.forEach(c => {
        c.addEventListener('click', () => {
            const radio = c.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                updatePreview();
            }
        });
    });

    sizeCards.forEach(c => {
        c.addEventListener('click', () => {
            const radio = c.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                updatePreview();
            }
        });
    });

    updatePreview();
})();
</script>
<?= $this->endSection() ?>
