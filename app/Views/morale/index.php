<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Staff Morale<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/staff" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-face-smile mr-2 text-warning"></i>Staff Morale</h1>
            <p class="text-sm text-base-content/50">Keep your team happy to maximize productivity</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Morale Overview -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div class="text-center md:text-left">
                    <div class="text-xs text-base-content/50 uppercase tracking-wider">Average Morale</div>
                    <div class="text-5xl font-bold mt-1 <?= $avgMorale >= 70 ? 'text-success' : ($avgMorale >= 40 ? 'text-warning' : 'text-error') ?>"><?= $avgMorale ?>%</div>
                    <progress class="progress <?= $avgMorale >= 70 ? 'progress-success' : ($avgMorale >= 40 ? 'progress-warning' : 'progress-error') ?> w-48 mt-2" value="<?= $avgMorale ?>" max="100"></progress>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center text-sm">
                    <div>
                        <div class="text-2xl font-bold"><?= count($staff) ?></div>
                        <div class="text-xs text-base-content/50">Total Staff</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-success"><?= count($highMorale) ?></div>
                        <div class="text-xs text-base-content/50">Happy (80%+)</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-error"><?= count($lowMorale) ?></div>
                        <div class="text-xs text-base-content/50">Unhappy (<50%)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($avgMorale < 50 && count($staff) > 0) : ?>
        <div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-triangle-exclamation"></i><span>Morale is critically low! Unhappy staff work slower and may quit. Take action now.</span></div>
    <?php elseif ($avgMorale < 70 && count($staff) > 0) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-circle-exclamation"></i><span>Morale could be better. Consider boosting your team's spirits.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Morale Actions -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-hand-holding-heart mr-1"></i>Boost Morale</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($boosts as $key => $boost) : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                <i class="<?= $boost['icon'] ?> text-lg <?= $boost['color'] ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm"><?= $boost['name'] ?></div>
                                <div class="text-xs text-base-content/50 mb-2"><?= $boost['desc'] ?></div>
                                <div class="flex items-center justify-between">
                                    <span class="badge badge-success badge-sm">+<?= $boost['morale'] ?> morale</span>
                                    <form action="/morale/boost" method="post"><?= csrf_field() ?>
                                        <input type="hidden" name="action" value="<?= $key ?>">
                                        <button type="submit" class="btn btn-sm btn-primary gap-1" <?= count($staff) === 0 ? 'disabled' : '' ?> onclick="return confirm('<?= $boost['name'] ?><?= $boost['cost'] > 0 ? ' for ' . currency($boost['cost']) : '' ?>?')">
                                            <?php if ($boost['cost'] > 0) : ?>
                                                <i class="fa-solid fa-coins"></i><?= currency($boost['cost']) ?>
                                            <?php else : ?>
                                                <i class="fa-solid fa-check"></i>Apply
                                            <?php endif ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
            </div>
        </div>

        <!-- Staff List -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-users mr-1"></i>Staff Morale</h2>
            <?php if (empty($staff)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-8">
                    <p class="text-sm text-base-content/50"><a href="/staff/hire" class="link link-primary">Hire staff</a> first.</p>
                </div></div>
            <?php else : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-0">
                        <div class="divide-y divide-base-300">
                        <?php
                            usort($staff, fn($a, $b) => (int) $a['morale'] - (int) $b['morale']);
                        ?>
                        <?php foreach ($staff as $s) : ?>
                            <div class="flex items-center gap-2 p-3">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold truncate"><?= esc($s['name']) ?></div>
                                    <div class="text-xs text-base-content/50"><?= ucwords(str_replace('_', ' ', $s['role'])) ?></div>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <progress class="progress <?= (int) $s['morale'] >= 70 ? 'progress-success' : ((int) $s['morale'] >= 40 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $s['morale'] ?>" max="100"></progress>
                                    <span class="text-xs font-mono w-8"><?= $s['morale'] ?>%</span>
                                </div>
                            </div>
                        <?php endforeach ?>
                        </div>
                    </div>
                </div>
            <?php endif ?>

            <!-- Morale Effects -->
            <div class="card bg-base-100 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Morale Effects</h3>
                    <ul class="text-xs text-base-content/60 space-y-1.5">
                        <li><span class="text-success font-semibold">80-100%</span> — Staff work faster, +10% efficiency</li>
                        <li><span class="text-info font-semibold">60-79%</span> — Normal performance</li>
                        <li><span class="text-warning font-semibold">40-59%</span> — Slower work, -10% efficiency</li>
                        <li><span class="text-error font-semibold">0-39%</span> — Risk of quitting, -25% efficiency</li>
                        <li class="pt-1"><i class="fa-solid fa-arrow-down mr-1"></i>Morale drops 2/day naturally</li>
                        <li><i class="fa-solid fa-arrow-down mr-1"></i>Low pay, overwork, bad weather lower it faster</li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
