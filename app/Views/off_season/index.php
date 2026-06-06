<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Off-Season<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-6xl">
    <h1 class="text-3xl font-bold mb-2"><i class="fa-solid fa-sun mr-2 text-warning"></i> Off-Season Management</h1>
    <p class="text-base-content/60 mb-6">Summer activities, bulk maintenance, and preparing for next winter.</p>

    <?php if (session()->getFlashdata('success')) : ?><div class="alert alert-success mb-4"><?= session()->getFlashdata('success') ?></div><?php endif ?>
    <?php if (session()->getFlashdata('error')) : ?><div class="alert alert-error mb-4"><?= session()->getFlashdata('error') ?></div><?php endif ?>

    <!-- Season Status -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="card-title text-base">
                <?php if ($isWinter) : ?>
                    <i class="fa-solid fa-snowflake text-info mr-1"></i> Winter Season
                <?php else : ?>
                    <i class="fa-solid fa-sun text-warning mr-1"></i> Summer Season
                <?php endif ?>
            </h2>
            <div class="badge <?= $isWinter ? 'badge-info' : 'badge-warning' ?> badge-lg">
                <?= $isWinter ? "Day {$seasonDay}/100 · {$daysUntilSummer}d until summer" : "Summer day {$summerDay}/35 · {$daysUntilWinter}d until winter" ?>
            </div>
        </div>
        <div class="flex gap-1">
            <div style="flex:100">
                <div class="text-xs text-base-content/50 mb-1">Winter (Days 1-100)</div>
                <progress class="progress progress-info w-full" value="<?= min($seasonDay, getWinterDays()) ?>" max="<?= getWinterDays() ?>"></progress>
            </div>
            <div style="flex:35">
                <div class="text-xs text-base-content/50 mb-1">Summer (<?= getWinterDays() + 1 ?>-<?= getSeasonLength() ?>)</div>
                <progress class="progress progress-warning w-full" value="<?= max(0, $seasonDay - getWinterDays()) ?>" max="<?= getSummerDays() ?>"></progress>
            </div>
        </div>

    <!-- Infrastructure Health -->
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-heart-pulse mr-1 text-error"></i> Infrastructure Health</h2>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-mountain text-info text-xl mb-1"></i>
            <div class="text-2xl font-bold <?= $avgSlopeCond >= 70 ? 'text-success' : ($avgSlopeCond >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgSlopeCond ?>%</div>
            <div class="text-xs text-base-content/50">Slopes (<?= count($slopes) ?>)</div>
            <progress class="progress <?= $avgSlopeCond >= 70 ? 'progress-success' : ($avgSlopeCond >= 40 ? 'progress-warning' : 'progress-error') ?> w-full mt-1" value="<?= $avgSlopeCond ?>" max="<?= getWinterDays() ?>"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-cable-car text-success text-xl mb-1"></i>
            <div class="text-2xl font-bold <?= $avgLiftCond >= 70 ? 'text-success' : ($avgLiftCond >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgLiftCond ?>%</div>
            <div class="text-xs text-base-content/50">Lifts (<?= count($lifts) ?>)</div>
            <progress class="progress <?= $avgLiftCond >= 70 ? 'progress-success' : ($avgLiftCond >= 40 ? 'progress-warning' : 'progress-error') ?> w-full mt-1" value="<?= $avgLiftCond ?>" max="<?= getWinterDays() ?>"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-building text-primary text-xl mb-1"></i>
            <div class="text-2xl font-bold text-success"><?= count($buildings) ?></div>
            <div class="text-xs text-base-content/50">Buildings (<?= count($buildings) ?>)</div>
            <progress class="progress progress-success w-full mt-1" value="100" max="<?= getWinterDays() ?>"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-toolbox text-warning text-xl mb-1"></i>
            <div class="text-2xl font-bold <?= $avgEquipCond >= 70 ? 'text-success' : ($avgEquipCond >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgEquipCond ?>%</div>
            <div class="text-xs text-base-content/50">Equipment (<?= count($equipment) ?>)</div>
            <progress class="progress <?= $avgEquipCond >= 70 ? 'progress-success' : ($avgEquipCond >= 40 ? 'progress-warning' : 'progress-error') ?> w-full mt-1" value="<?= $avgEquipCond ?>" max="<?= getWinterDays() ?>"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-person-snowboarding text-secondary text-xl mb-1"></i>
            <div class="text-2xl font-bold <?= $avgParkCond >= 70 ? 'text-success' : ($avgParkCond >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgParkCond ?>%</div>
            <div class="text-xs text-base-content/50">Parks (<?= count($terrainParks) ?>)</div>
            <progress class="progress <?= $avgParkCond >= 70 ? 'progress-success' : ($avgParkCond >= 40 ? 'progress-warning' : 'progress-error') ?> w-full mt-1" value="<?= $avgParkCond ?>" max="<?= getWinterDays() ?>"></progress>
        </div></div>
    </div>

    <!-- Bulk Maintenance -->
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-wrench mr-1"></i> Bulk Maintenance</h2>
    <p class="text-sm text-base-content/60 mb-3">Repair everything in a category at once. Available year-round but best done during summer when there's less revenue pressure.</p>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($maintenanceTasks as $key => $task) : ?>
            <?php
                $itemCount = 0; $needsRepair = 0;
                switch ($task['target']) {
                    case 'slopes': $itemCount = count($slopes); $needsRepair = count(array_filter($slopes, fn($s) => $s['condition_pct'] < 100)); break;
                    case 'lifts': $itemCount = count($lifts); $needsRepair = count(array_filter($lifts, fn($l) => $l['condition_pct'] < 100)); break;
                    case 'buildings': $itemCount = count($buildings); $needsRepair = $itemCount; break;
                    case 'equipment': $itemCount = count($equipment); $needsRepair = count(array_filter($equipment, fn($e) => $e['condition_pct'] < 100)); break;
                    case 'terrain_parks': $itemCount = count($terrainParks); $needsRepair = count(array_filter($terrainParks, fn($t) => $t['condition_pct'] < 100)); break;
                }
                $totalCost = $needsRepair * $task['cost_per_unit'];
            ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                <h3 class="font-semibold"><i class="<?= $task['icon'] ?> mr-1"></i> <?= $task['name'] ?></h3>
                <p class="text-xs text-base-content/60 mt-1"><?= $task['desc'] ?></p>
                <div class="grid grid-cols-2 gap-2 text-sm mt-3">
                    <div><span class="text-base-content/50">Items:</span> <?= $itemCount ?></div>
                    <div><span class="text-base-content/50">Needs repair:</span> <span class="<?= $needsRepair > 0 ? 'text-warning font-semibold' : 'text-success' ?>"><?= $needsRepair ?></span></div>
                    <div><span class="text-base-content/50">Cost each:</span> <?= currency($task['cost_per_unit']) ?></div>
                    <div><span class="text-base-content/50">Total:</span> <span class="font-semibold"><?= currency($totalCost) ?></span></div>
                </div>
                <form action="/off-season/maintenance" method="post" class="mt-3" onsubmit="return confirm('Run <?= $task['name'] ?> for <?= currency($totalCost) ?>?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="task" value="<?= $key ?>">
                    <button type="submit" class="btn btn-primary btn-sm w-full" <?= $needsRepair === 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-wrench mr-1"></i> <?= $needsRepair > 0 ? 'Run - ' . currency($totalCost) : 'All Good' ?>
                    </button>
                </form>
            </div></div>
        <?php endforeach ?>
    </div>

    <!-- Summer Activities -->
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-sun mr-1 text-warning"></i> Summer Activities</h2>
    <p class="text-sm text-base-content/60 mb-3">Build summer attractions to generate revenue during the off-season. These earn income during summer days (<?= getWinterDays() + 1 ?>-<?= getSeasonLength() ?>) and remain available year-round.</p>

    <?php if (!empty($summerActivities)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <?php foreach ($summerActivities as $act) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold"><?= esc($act['name']) ?></h3>
                <div class="badge <?= $isSummer ? 'badge-success' : 'badge-ghost' ?>"><?= $isSummer ? 'Active' : 'Off-Season' ?></div>
            </div>
            <div class="grid grid-cols-2 gap-2 text-sm mt-2">
                <div><span class="text-base-content/50">Revenue:</span> <?= currency($act['revenue'] ?? 0) ?>/day</div>
                <div><span class="text-base-content/50">Upkeep:</span> <?= currency($act['upkeep'] ?? 0) ?>/day</div>
            </div>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <?php foreach ($activityConfig as $key => $cfg) : ?>
        <div class="card bg-base-200/50 border border-base-300"><div class="card-body p-3">
            <h3 class="font-semibold text-sm"><i class="<?= $cfg['icon'] ?> mr-1"></i> <?= $cfg['name'] ?></h3>
            <p class="text-xs text-base-content/60 mt-1"><?= $cfg['desc'] ?></p>
            <div class="text-xs space-y-1 mt-2">
                <div>Cost: <strong><?= currency($cfg['cost']) ?></strong></div>
                <div>Revenue: <strong><?= currency($cfg['revenue']) ?>/day</strong></div>
                <div>Upkeep: <strong><?= currency($cfg['upkeep']) ?>/day</strong></div>
                <div>Capacity: <strong><?= $cfg['capacity'] ?> guests</strong></div>
            </div>
            <a href="/off-season" class="btn btn-outline btn-xs w-full mt-2">Build via Buildings</a>
        </div></div>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
