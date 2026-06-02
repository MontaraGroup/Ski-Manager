<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Night Skiing<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-moon mr-2 text-warning"></i>Night Skiing</h1>
                <p class="text-sm text-base-content/50">Install lights to extend your operating hours into the evening</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?>
        <div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div>
    <?php endif ?>
    <?php if (session('error')) : ?>
        <div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div>
    <?php endif ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold"><?= count($lights) ?></div>
                <div class="text-xs text-base-content/50">Total Lights</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-warning"><?= count(array_filter($lights, fn($l) => $l['status'] === 'active')) ?></div>
                <div class="text-xs text-base-content/50">Active</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-info"><?= $totalCoverage ?>%</div>
                <div class="text-xs text-base-content/50">Slope Coverage</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-success">+<?= $extraRevenue ?>%</div>
                <div class="text-xs text-base-content/50">Extra Revenue</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-warning"><?= currency($totalEnergy) ?></div>
                <div class="text-xs text-base-content/50">Energy/Night</div>
            </div>
        </div>
    </div>

    <!-- Coverage meter -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body p-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-semibold">Slope Lighting Coverage</span>
                <span><?= min(100, $totalCoverage) ?>%</span>
            </div>
            <progress class="progress <?= $totalCoverage >= 75 ? 'progress-success' : ($totalCoverage >= 40 ? 'progress-warning' : 'progress-error') ?> w-full" value="<?= min(100, $totalCoverage) ?>" max="100"></progress>
            <div class="flex justify-between text-xs text-base-content/50 mt-1">
                <span>0% — No night skiing</span>
                <span>50% — Partial operations</span>
                <span>100% — Full night skiing</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Lights List -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Installed Lights</h2>

            <?php if (empty($lights)) : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body text-center py-12">
                        <i class="fa-solid fa-lightbulb text-4xl text-base-content/20 mb-4"></i>
                        <h3 class="font-semibold text-lg">No lights installed</h3>
                        <p class="text-sm text-base-content/50 mb-4">Install lights to offer night skiing and earn extra revenue from evening visitors.</p>
                    </div>
                </div>
            <?php else : ?>
                <div class="space-y-3">
                    <?php foreach ($lights as $light) : ?>
                    <?php
                        $typeIcons = [
                            'basic_flood' => 'fa-solid fa-lightbulb',
                            'led_tower' => 'fa-solid fa-tower-broadcast',
                            'stadium_light' => 'fa-solid fa-bolt',
                            'smart_led' => 'fa-solid fa-microchip',
                            'aurora_system' => 'fa-solid fa-wand-magic-sparkles',
                        ];
                        $icon = $typeIcons[$light['light_type']] ?? 'fa-solid fa-lightbulb';
                    ?>
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body p-4">
                            <div class="flex flex-col md:flex-row md:items-center gap-3">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                        <?php if ($light['status'] === 'active') : ?>
                                            <i class="<?= $icon ?> text-warning text-lg animate-pulse"></i>
                                        <?php elseif ($light['status'] === 'broken') : ?>
                                            <i class="fa-solid fa-triangle-exclamation text-error text-lg"></i>
                                        <?php else : ?>
                                            <i class="<?= $icon ?> text-base-content/30 text-lg"></i>
                                        <?php endif ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-sm"><?= esc($light['light_name']) ?></div>
                                        <div class="text-xs text-base-content/50">
                                            +<?= $light['coverage'] ?>% coverage — <?= currency($light['energy_cost']) ?>/night energy
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="flex items-center gap-1 min-w-[80px]">
                                        <progress class="progress <?= $light['condition_pct'] > 50 ? 'progress-success' : ($light['condition_pct'] > 20 ? 'progress-warning' : 'progress-error') ?> w-14" value="<?= $light['condition_pct'] ?>" max="100"></progress>
                                        <span class="text-xs"><?= $light['condition_pct'] ?>%</span>
                                    </div>

                                    <?php if ($light['status'] === 'active') : ?>
                                        <span class="badge badge-warning badge-sm">On</span>
                                    <?php elseif ($light['status'] === 'broken') : ?>
                                        <span class="badge badge-error badge-sm">Broken</span>
                                    <?php else : ?>
                                        <span class="badge badge-ghost badge-sm">Off</span>
                                    <?php endif ?>

                                    <?php if ($light['status'] === 'broken') : ?>
                                        <form action="/night-skiing/repair/<?= $light['id'] ?>" method="post" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-warning btn-xs"><i class="fa-solid fa-wrench mr-1"></i>Repair</button>
                                        </form>
                                    <?php else : ?>
                                        <form action="/night-skiing/toggle/<?= $light['id'] ?>" method="post" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-<?= $light['status'] === 'active' ? 'ghost' : 'warning' ?> btn-xs">
                                                <i class="fa-solid fa-power-off mr-1"></i><?= $light['status'] === 'active' ? 'Off' : 'On' ?>
                                            </button>
                                        </form>
                                    <?php endif ?>

                                    <form action="/night-skiing/sell/<?= $light['id'] ?>" method="post" class="inline" onsubmit="return confirm('Remove this light?')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-xs text-error" aria-label="Remove"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Buy Lights -->
        <div>
            <h2 class="text-lg font-bold mb-3">Install Lighting</h2>
            <div class="space-y-2">
                <?php foreach ($lightTypes as $key => $type) : ?>
                <form action="/night-skiing/buy" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="<?= $key ?>">
                    <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                    <i class="<?= $type['icon'] ?> text-warning"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm"><?= $type['name'] ?></div>
                                    <div class="text-xs text-base-content/50"><?= $type['desc'] ?></div>
                                    <div class="text-xs text-base-content/50">+<?= $type['coverage'] ?>% coverage — <?= currency($type['energy']) ?>/night</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-primary text-sm"><?= currency($type['cost']) ?></div>
                                </div>
                            </div>
                        </div>
                    </button>
                </form>
                <?php endforeach ?>
            </div>

            <div class="card bg-base-100 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Night Skiing Benefits</h3>
                    <ul class="text-xs text-base-content/60 space-y-1.5">
                        <li><i class="fa-solid fa-clock mr-1"></i>Extends operations by <?= $nightHours ?> hours</li>
                        <li><i class="fa-solid fa-users mr-1"></i>Up to +30% extra daily visitors</li>
                        <li><i class="fa-solid fa-coins mr-1"></i>Evening visitors pay full ticket price</li>
                        <li><i class="fa-solid fa-star mr-1"></i>100% coverage unlocks premium night passes</li>
                        <li><i class="fa-solid fa-bolt mr-1"></i>Lights consume energy each night</li>
                        <li><i class="fa-solid fa-wrench mr-1"></i>Require mechanics to maintain</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
