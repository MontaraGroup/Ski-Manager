<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski School & Lessons<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-chalkboard-user mr-2 text-info"></i>Ski School</h1>
                <p class="text-sm text-base-content/50">Offer professional lessons to boost resort satisfaction and daily revenue</p>
            </div>
        </div>
        <a href="/staff/hire" class="btn btn-primary btn-sm gap-1"><i class="fa-solid fa-user-plus"></i>Hire Instructors</a>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 text-center">
                <div class="text-3xl font-bold text-info"><?= $activeInstructors ?></div>
                <div class="text-xs text-base-content/50">Active Instructors</div>
                <div class="text-[11px] text-base-content/40 mt-1"><?= $dailyCapacity ?> students daily capacity</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 text-center">
                <div class="text-3xl font-bold"><?= $lessonsToday ?></div>
                <div class="text-xs text-base-content/50">Classes / Day</div>
                <div class="text-[11px] text-base-content/40 mt-1"><?= count($slopes) ?> open slopes available</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm border border-base-200">
            <div class="card-body p-4 text-center">
                <div class="text-3xl font-bold text-success"><?= currency($estimatedRevenue) ?></div>
                <div class="text-xs text-base-content/50">Est. Daily Lesson Income</div>
                <div class="text-[11px] text-success/70 mt-1">Generated during daily simulation</div>
            </div>
        </div>
    </div>

    <?php if ($activeInstructors === 0) : ?>
    <div class="alert alert-warning mb-6">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <span class="font-bold">No active ski instructors on staff.</span>
            <div class="text-xs mt-0.5">Your ski school is currently dormant. <a href="/staff/hire" class="link font-bold">Hire instructors</a> to start teaching beginner through advanced skiers.</div>
        </div>
    </div>
    <?php elseif (!empty($recentLessonLog)) : ?>
    <div class="alert alert-success mb-6">
        <i class="fa-solid fa-circle-check"></i>
        <span><strong>Recent Ski School Activity:</strong> <?= esc($recentLessonLog['description'] ?? '') ?></span>
    </div>
    <?php endif ?>

    <!-- Lesson Programs -->
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
        <div class="card-body p-4">
            <h2 class="font-semibold text-base mb-3"><i class="fa-solid fa-graduation-cap mr-1 text-primary"></i>Lesson Programs & Pricing</h2>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead>
                        <tr class="text-base-content/60 text-xs">
                            <th>Program</th>
                            <th>Duration</th>
                            <th>Ticket Price</th>
                            <th>Instructors</th>
                            <th>Max Class Size</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lessonTypes as $type) : ?>
                        <tr>
                            <td class="font-medium"><i class="<?= $type['icon'] ?> mr-1.5"></i><?= $type['name'] ?></td>
                            <td><?= $type['duration'] ?></td>
                            <td class="font-mono font-bold text-success"><?= currency($type['price']) ?></td>
                            <td><span class="badge badge-ghost badge-sm"><?= $type['instructors'] ?> instructor</span></td>
                            <td><?= $type['max_students'] ?> students</td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hired Instructors Roster -->
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-6">
        <div class="card-body p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-base"><i class="fa-solid fa-users mr-1 text-info"></i>Instructor Roster</h2>
                <a href="/staff" class="btn btn-ghost btn-xs">Manage in Staff <i class="fa-solid fa-arrow-right ml-1"></i></a>
            </div>

            <?php if (empty($instructors)) : ?>
                <div class="text-center py-8 text-base-content/50">
                    <i class="fa-solid fa-person-skiing text-4xl mb-2 text-base-content/20"></i>
                    <p class="font-medium">No instructors hired yet</p>
                    <p class="text-xs mt-1">Visit the <a href="/staff/hire" class="link link-primary">hiring page</a> to recruit instructors.</p>
                </div>
            <?php else : ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($instructors as $inst) : ?>
                    <div class="bg-base-200 rounded-lg p-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-sm"><?= esc($inst['name']) ?></span>
                            <span class="badge <?= ($inst['status'] ?? '') === 'active' ? 'badge-success' : 'badge-ghost' ?> badge-xs"><?= ucfirst($inst['status'] ?? 'active') ?></span>
                        </div>
                        <div class="text-xs text-base-content/60 space-y-0.5">
                            <div><i class="fa-solid fa-medal text-warning text-[10px] mr-1"></i>Level <?= $inst['level'] ?? 1 ?> · Salary <?= currency((int)($inst['salary'] ?? 0)) ?>/day</div>
                            <div><i class="fa-solid fa-face-smile text-[10px] mr-1"></i>Morale: <?= $inst['morale'] ?? 100 ?>%</div>
                            <?php if (!empty($inst['assigned_to'])) : ?>
                            <div class="text-primary text-[11px]"><i class="fa-solid fa-location-dot text-[10px] mr-1"></i><?= esc($inst['assigned_to']) ?></div>
                            <?php else : ?>
                            <div class="text-base-content/40 text-[11px]"><i class="fa-solid fa-location-dot text-[10px] mr-1"></i>General Ski School</div>
                            <?php endif ?>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Ski School Info Alert -->
    <div class="card bg-info/10 border border-info/20">
        <div class="card-body p-4 text-xs text-info-content">
            <h3 class="font-bold text-sm mb-1 text-info flex items-center gap-1.5"><i class="fa-solid fa-circle-info"></i>How Ski School Operates</h3>
            <p>During the daily simulation tick, visitors enrolled in lessons pay full class fees directly into resort revenue. More experienced instructors and higher resort ratings draw larger class enrollments.</p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
