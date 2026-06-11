<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Staff<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-users mr-2 text-info"></i>Staff</h1>
                <p class="text-sm text-base-content/50"><?= count($staff) ?> employees, <?= currency($totalSalary) ?>/day</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="/morale" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-face-smile"></i>Morale</a>
            <a href="/staff/hire" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-user-plus"></i>Hire</a>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <?php if (empty($staff)) : ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
            <i class="fa-solid fa-users text-4xl text-base-content/20 mb-3"></i>
            <p class="font-semibold">No staff hired yet</p>
            <p class="text-sm text-base-content/50 mt-1">Your resort needs people to run. Start hiring.</p>
            <a href="/staff/hire" class="btn btn-primary btn-sm mt-3 gap-1"><i class="fa-solid fa-user-plus"></i>Hire First Employee</a>
        </div></div>
    <?php else : ?>

        <?php
            $avgMorale = round(array_sum(array_column($staff, 'morale')) / count($staff));
            $roleCounts = [];
            foreach ($staff as $s) { $roleCounts[$s['role']] = ($roleCounts[$s['role']] ?? 0) + 1; }
            $lowMorale = count(array_filter($staff, fn($s) => (int)$s['morale'] < 40));
        ?>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
                <div class="text-2xl font-bold"><?= count($staff) ?></div>
                <div class="text-xs text-base-content/50">Total</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-error"><?= currency($totalSalary) ?></div>
                <div class="text-xs text-base-content/50">Daily Cost</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
                <div class="text-2xl font-bold <?= $avgMorale >= 70 ? 'text-success' : ($avgMorale >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgMorale ?>%</div>
                <div class="text-xs text-base-content/50">Avg Morale</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-success"><?= $assigned ?></div>
                <div class="text-xs text-base-content/50">Assigned</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
                <div class="text-2xl font-bold <?= $unassigned > 0 ? 'text-warning' : 'text-success' ?>"><?= $unassigned ?></div>
                <div class="text-xs text-base-content/50">Unassigned</div>
            </div></div>
        </div>

        <!-- Alerts -->
        <?php if ($lowMorale > 0) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-face-frown"></i><span><?= $lowMorale ?> staff member<?= $lowMorale > 1 ? 's have' : ' has' ?> low morale. <a href="/morale" class="link font-semibold">Boost morale</a></span></div>
        <?php endif ?>
        <?php if ($unassigned > 0) : ?>
        <div class="alert alert-info mb-4"><i class="fa-solid fa-circle-info"></i><span><?= $unassigned ?> unassigned staff. Use Auto-Assign or assign manually below.</span></div>
        <?php endif ?>

        <!-- Role Breakdown -->
        <div class="flex flex-wrap gap-2 mb-4">
            <button class="btn btn-sm btn-primary role-filter" data-role="all" onclick="filterRole('all')">All (<?= count($staff) ?>)</button>
            <?php foreach ($roleCounts as $role => $cnt) : ?>
            <?php $ri = $roles[$role] ?? ['name' => ucfirst($role), 'icon' => 'fa-solid fa-user']; ?>
            <button class="btn btn-sm btn-ghost role-filter" data-role="<?= $role ?>" onclick="filterRole('<?= $role ?>')">
                <i class="<?= $ri['icon'] ?> text-xs"></i> <?= $ri['name'] ?> (<?= $cnt ?>)
            </button>
            <?php endforeach ?>
        </div>

        <!-- Actions -->
        <div class="flex gap-2 mb-4">
            <form action="/staff/auto-assign" method="post"><?= csrf_field() ?>
                <button class="btn btn-xs btn-info gap-1"><i class="fa-solid fa-wand-magic-sparkles"></i>Auto-Assign</button>
            </form>
            <form action="/staff/clear-assignments" method="post" data-confirm="Clear all staff assignments?"><?= csrf_field() ?>
                <button class="btn btn-xs btn-ghost gap-1"><i class="fa-solid fa-eraser"></i>Clear All</button>
            </form>
        </div>

        <!-- Staff Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" id="staffGrid">
            <?php foreach ($staff as $member) : ?>
            <?php
                $role = $roles[$member['role']] ?? ['name' => $member['role'], 'icon' => 'fa-solid fa-user', 'color' => ''];
                $morale = (int) $member['morale'];
                $isAssigned = !empty($member['assigned_to']);
            ?>
            <div class="card bg-base-100 shadow-sm staff-card <?= $morale < 40 ? 'border border-error/30' : '' ?>" data-role="<?= $member['role'] ?>">
                <div class="card-body p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="avatar placeholder">
                            <div class="bg-<?= $isAssigned ? 'success' : 'warning' ?>/10 text-<?= $isAssigned ? 'success' : 'warning' ?> rounded-full w-10 h-10 flex items-center justify-center">
                                <i class="<?= $role['icon'] ?>"></i>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm truncate"><?= esc($member['name']) ?></div>
                            <div class="flex items-center gap-2 text-xs text-base-content/50">
                                <span><?= $role['name'] ?></span>
                                <span>·</span>
                                <span class="flex items-center gap-0.5"><?php for($i=0;$i<min($member['level'],5);$i++): ?><i class="fa-solid fa-star text-warning text-[8px]"></i><?php endfor ?> Lv.<?= $member['level'] ?></span>
                            </div>
                        </div>
                        <span class="badge badge-xs <?= $member['status'] === 'active' ? 'badge-success' : ($member['status'] === 'training' ? 'badge-info' : 'badge-warning') ?>"><?= ucfirst($member['status']) ?></span>
                    </div>

                    <!-- Morale + Salary -->
                    <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                        <div class="bg-base-200 rounded-lg p-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-base-content/50">Morale</span>
                                <span class="font-bold <?= $morale >= 70 ? 'text-success' : ($morale >= 40 ? 'text-warning' : 'text-error') ?>"><?= $morale ?>%</span>
                            </div>
                            <progress class="progress <?= $morale >= 70 ? 'progress-success' : ($morale >= 40 ? 'progress-warning' : 'progress-error') ?> w-full h-1" value="<?= $morale ?>" max="100"></progress>
                        </div>
                        <div class="bg-base-200 rounded-lg p-2 text-center">
                            <div class="text-base-content/50">Salary</div>
                            <div class="font-bold"><?= currency($member['salary']) ?>/day</div>
                        </div>
                    </div>

                    <!-- Assignment -->
                    <div class="text-xs mb-3">
                        <?php if ($isAssigned) : ?>
                            <div class="flex items-center gap-1 text-success"><i class="fa-solid fa-circle-check text-[10px]"></i><span class="truncate"><?= esc($member['assigned_to']) ?></span></div>
                        <?php else : ?>
                            <div class="flex items-center gap-1 text-warning"><i class="fa-solid fa-circle-exclamation text-[10px]"></i>Unassigned</div>
                        <?php endif ?>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-1">
                        <form action="/staff/train/<?= $member['id'] ?>" method="post" class="flex-1" data-confirm="Train <?= esc($member['name']) ?> for <?= currency(5000) ?>? They'll level up."><?= csrf_field() ?>
                            <button class="btn btn-xs w-full btn-outline btn-info gap-1"><i class="fa-solid fa-graduation-cap"></i>Train</button>
                        </form>
                        <form action="/staff/fire/<?= $member['id'] ?>" method="post" data-confirm="Fire <?= esc($member['name']) ?>?"><?= csrf_field() ?>
                            <button class="btn btn-xs btn-ghost text-error"><i class="fa-solid fa-user-minus"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>

    <?php endif ?>
</div>

<script>
function filterRole(role) {
    document.querySelectorAll('.role-filter').forEach(function(b) {
        b.classList.remove('btn-primary'); b.classList.add('btn-ghost');
    });
    document.querySelector('.role-filter[data-role="'+role+'"]').classList.add('btn-primary');
    document.querySelector('.role-filter[data-role="'+role+'"]').classList.remove('btn-ghost');
    document.querySelectorAll('.staff-card').forEach(function(c) {
        c.style.display = role === 'all' || c.dataset.role === role ? '' : 'none';
    });
}
</script>
<?= $this->endSection() ?>
