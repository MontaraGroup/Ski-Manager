<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Staff<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold">Staff</h1>
                <p class="text-sm text-base-content/50"><?= count($staff) ?> employees - <?= currency($totalSalary) ?>/day total salary</p>
            </div>
        </div>
        <a href="/morale" class="btn btn-outline btn-sm"><i class="fa-solid fa-face-smile mr-1"></i>Morale</a>
        <a href="/staff/hire" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus mr-1"></i>Hire Staff</a>
    </div>

    <?php if (session('success')) : ?>
        <div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div>
    <?php endif ?>
    <?php if (session('error')) : ?>
        <div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div>
    <?php endif ?>

    <?php if (empty($staff)) : ?>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body text-center py-12">
                <i class="fa-solid fa-users text-4xl text-base-content/20 mb-4"></i>
                <h3 class="font-semibold text-lg">No staff hired yet</h3>
                <p class="text-sm text-base-content/50 mb-4">Hire staff to keep your resort running smoothly.</p>
                <a href="/staff/hire" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus mr-1"></i>Hire Your First Employee</a>
            </div>
        </div>
    <?php else : ?>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-3 text-center">
                    <div class="text-2xl font-bold"><?= count($staff) ?></div>
                    <div class="text-xs text-base-content/50">Total Staff</div>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-3 text-center">
                    <div class="text-2xl font-bold"><?= currency($totalSalary) ?></div>
                    <div class="text-xs text-base-content/50">Daily Salary</div>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-3 text-center">
                    <?php $avgMorale = count($staff) > 0 ? round(array_sum(array_column($staff, 'morale')) / count($staff)) : 0; ?>
                    <div class="text-2xl font-bold"><?= $avgMorale ?>%</div>
                    <div class="text-xs text-base-content/50">Avg Morale</div>
                </div>
            </div>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-3 text-center">
                    <?php $avgLevel = count($staff) > 0 ? round(array_sum(array_column($staff, 'level')) / count($staff), 1) : 0; ?>
                    <div class="text-2xl font-bold"><?= $avgLevel ?></div>
                    <div class="text-xs text-base-content/50">Avg Level</div>
                </div>
            </div>
        </div>

        <!-- Staff Table -->
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Level</th>
                                <th>Morale</th>
                                <th>Salary</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $member) : ?>
                            <?php $role = $roles[$member['role']] ?? ['name' => $member['role'], 'icon' => 'fa-solid fa-user', 'color' => '']; ?>
                            <tr>
                                <td class="font-semibold"><?= esc($member['name']) ?></td>
                                <td>
                                    <span class="<?= $role['color'] ?>"><i class="<?= $role['icon'] ?> mr-1"></i></span>
                                    <?= $role['name'] ?>
                                </td>
                                <td><span class="badge badge-neutral badge-sm">Lv.<?= $member['level'] ?></span></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <progress class="progress <?= $member['morale'] > 70 ? 'progress-success' : ($member['morale'] > 40 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $member['morale'] ?>" max="100"></progress>
                                        <span class="text-xs"><?= $member['morale'] ?>%</span>
                                    </div>
                                </td>
                                <td><?= currency($member['salary']) ?></td>
                                <td>
                                    <?php if ($member['status'] === 'active') : ?>
                                        <span class="badge badge-success badge-sm">Active</span>
                                    <?php elseif ($member['status'] === 'resting') : ?>
                                        <span class="badge badge-warning badge-sm">Resting</span>
                                    <?php elseif ($member['status'] === 'training') : ?>
                                        <span class="badge badge-info badge-sm">Training</span>
                                    <?php endif ?>
                                </td>
                                <td class="text-xs text-base-content/50"><?= $member['assigned_to'] ? esc($member['assigned_to']) : '-' ?></td>
                                <td>
                                    <form action="/staff/fire/<?= $member['id'] ?>" method="post" onsubmit="return confirm('Fire <?= esc($member['name']) ?>? This cannot be undone.')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-user-minus"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif ?>

</div>
<?= $this->endSection() ?>
