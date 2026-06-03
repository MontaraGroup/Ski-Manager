<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Night Skiing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-moon mr-2 text-warning"></i>Night Skiing</h1>
                <p class="text-sm text-base-content/50">Extend operating hours with lighting systems - more visitors, more revenue</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Hero Status -->
    <?php $activeLights = array_filter($lights, fn($l) => $l['status'] === 'active'); ?>
    <div class="card bg-gradient-to-br from-slate-900 to-indigo-900 text-white shadow-xl mb-6">
        <div class="card-body p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div>
                    <div class="text-3xl font-bold text-amber-300"><?= $totalCoverage ?>%</div>
                    <div class="text-xs text-white/60 mt-1">Slope Coverage</div>
                    <progress class="progress progress-warning w-full mt-2" value="<?= min($totalCoverage, 100) ?>" max="100"></progress>
                </div>
                <div>
                    <div class="text-3xl font-bold text-emerald-300">+<?= $extraRevenue ?>%</div>
                    <div class="text-xs text-white/60 mt-1">Revenue Boost</div>
                    <div class="text-xs text-white/40 mt-1"><?= $nightHours ?>h extra per day</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-amber-200"><?= count($activeLights) ?>/<?= count($lights) ?></div>
                    <div class="text-xs text-white/60 mt-1">Lights Active</div>
                    <div class="text-xs text-white/40 mt-1"><?= $mechanics ?> mechanic<?= $mechanics !== 1 ? 's' : '' ?> available</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-red-300"><?= currency($totalEnergy) ?></div>
                    <div class="text-xs text-white/60 mt-1">Energy Cost/Day</div>
                    <div class="text-xs text-white/40 mt-1">from active lights</div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($totalCoverage < 50) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span>Low coverage! Below 50% means limited night skiing. Add more lights to attract evening visitors.</span></div>
    <?php elseif ($totalCoverage >= 100) : ?>
    <div class="alert alert-success mb-4"><i class="fa-solid fa-check-circle"></i><span>Full coverage! Your slopes are fully lit for maximum night skiing revenue.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Installed Lights -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i> Installed Lights</h2>
            <?php if (empty($lights)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-moon text-5xl text-base-content/15 mb-3"></i>
                    <p class="font-semibold">No lights installed</p>
                    <p class="text-sm text-base-content/50 mt-1">Install your first light from the shop on the right</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($lights as $light) : ?>
                    <?php $isOn = $light['status'] === 'active'; $cond = (int) $light['condition_pct']; ?>
                    <div class="card bg-base-100 shadow-sm <?= $isOn ? '' : '' ?>">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl <?= $isOn ? 'bg-warning/20' : 'bg-base-200' ?> flex items-center justify-center">
                                    <i class="fa-solid fa-lightbulb text-lg <?= $isOn ? 'text-warning' : 'text-base-content/30' ?>"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-sm"><?= esc($light['light_name']) ?></span>
                                        <span class="badge badge-xs <?= $isOn ? 'badge-warning' : 'badge-ghost' ?>"><?= $isOn ? 'ON' : 'OFF' ?></span>
                                    </div>
                                    <div class="flex items-center gap-3 text-xs text-base-content/50 mt-1">
                                        <span><i class="fa-solid fa-signal mr-1"></i><?= $light['coverage'] ?>% coverage</span>
                                        <span><i class="fa-solid fa-bolt mr-1"></i><?= currency($light['energy_cost']) ?>/day</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> w-24" value="<?= $cond ?>" max="100"></progress>
                                        <span class="text-xs font-mono"><?= $cond ?>%</span>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <form action="/night-skiing/toggle/<?= $light['id'] ?>" method="post"><?= csrf_field() ?>
                                        <button class="btn btn-sm <?= $isOn ? 'btn-ghost' : 'btn-warning' ?>"><i class="fa-solid fa-power-off"></i></button>
                                    </form>
                                    <?php if ($cond < 100) : ?>
                                    <form action="/night-skiing/repair/<?= $light['id'] ?>" method="post"><?= csrf_field() ?>
                                        <button class="btn btn-sm btn-info"><i class="fa-solid fa-wrench"></i></button>
                                    </form>
                                    <?php endif ?>
                                    <form action="/night-skiing/sell/<?= $light['id'] ?>" method="post" onsubmit="return confirm('Remove this light?')"><?= csrf_field() ?>
                                        <button class="btn btn-sm btn-ghost text-error"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Shop -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-shop mr-1"></i> Light Shop</h2>
            <div class="space-y-2">
            <?php foreach ($lightTypes as $key => $lt) : ?>
                <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="<?= $lt['icon'] ?> text-warning"></i>
                            <span class="font-semibold text-sm"><?= $lt['name'] ?></span>
                        </div>
                        <p class="text-xs text-base-content/60 mb-2"><?= $lt['desc'] ?></p>
                        <div class="grid grid-cols-3 gap-1 text-xs text-center mb-2">
                            <div class="bg-base-200 rounded p-1"><div class="font-bold"><?= $lt['coverage'] ?>%</div><div class="text-base-content/50">Cover</div></div>
                            <div class="bg-base-200 rounded p-1"><div class="font-bold"><?= currency($lt['energy']) ?></div><div class="text-base-content/50">Energy</div></div>
                            <div class="bg-base-200 rounded p-1"><div class="font-bold"><?= currency($lt['cost']) ?></div><div class="text-base-content/50">Cost</div></div>
                        </div>
                        <form action="/night-skiing/buy" method="post"><?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= $key ?>">
                            <button class="btn btn-primary btn-xs w-full"><i class="fa-solid fa-plus mr-1"></i> Install - <?= currency($lt['cost']) ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach ?>
            </div>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-3">
                <h3 class="font-semibold text-xs mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>How It Works</h3>
                <ul class="text-xs text-base-content/60 space-y-1">
                    <li><i class="fa-solid fa-moon mr-1 text-warning"></i> Adds <?= $nightHours ?> hours of evening skiing</li>
                    <li><i class="fa-solid fa-users mr-1"></i> Up to +30% more daily visitors</li>
                    <li><i class="fa-solid fa-bolt mr-1 text-error"></i> Active lights consume energy daily</li>
                    <li><i class="fa-solid fa-wrench mr-1"></i> Lights degrade - mechanics help maintain</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
