<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Snowmaking<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-snowflake mr-2 text-info"></i>Snowmaking</h1>
                <p class="text-sm text-base-content/50">Produce artificial snow to keep your slopes covered</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Temperature Status -->
    <div class="card shadow-xl mb-6 <?= $canMakeSnow ? 'bg-gradient-to-br from-sky-100 to-blue-200 dark:from-sky-900 dark:to-blue-950' : 'bg-gradient-to-br from-orange-100 to-red-200 dark:from-orange-900 dark:to-red-950' ?>">
        <div class="card-body p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="text-5xl">
                        <?= $canMakeSnow ? '<i class="fa-solid fa-snowflake text-info"></i>' : '<i class="fa-solid fa-temperature-high text-error"></i>' ?>
                    </div>
                    <div>
                        <div class="text-3xl font-bold"><?= temp($temp) ?></div>
                        <div class="text-sm <?= $canMakeSnow ? 'text-info' : 'text-error' ?> font-semibold">
                            <?= $canMakeSnow ? 'Snowmaking conditions: OPTIMAL' : 'Too warm for snowmaking (needs ≤ -2°C)' ?>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-sm font-semibold"><?= count($snowmakers) ?> snowmaker<?= count($snowmakers) !== 1 ? 's' : '' ?></div>
                    <div class="text-xs opacity-60">on duty</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Production Stats -->
    <?php $activeCannons = array_filter($cannons, fn($c) => $c['status'] === 'active'); ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-snowflake text-info text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= $totalOutput ?></div>
            <div class="text-xs text-base-content/50">cm³/day output</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-gauge-high text-warning text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= count($activeCannons) ?>/<?= count($cannons) ?></div>
            <div class="text-xs text-base-content/50">cannons running</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-bolt text-error text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= currency($totalEnergy) ?></div>
            <div class="text-xs text-base-content/50">energy/day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <i class="fa-solid fa-droplet text-blue-400 text-xl mb-1"></i>
            <div class="text-2xl font-bold"><?= number_format($totalWater) ?> L</div>
            <div class="text-xs text-base-content/50">water/day</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cannons -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-snowflake mr-1 text-info"></i> Snow Cannons</h2>
            <?php if (empty($cannons)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-snowflake text-5xl text-base-content/15 mb-3"></i>
                    <p class="font-semibold">No snow cannons</p>
                    <p class="text-sm text-base-content/50 mt-1">Buy your first cannon to start producing snow</p>
                </div></div>
            <?php else : ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($cannons as $cannon) : ?>
                    <?php $isOn = $cannon['status'] === 'active'; $cond = (int) $cannon['condition_pct']; ?>
                    <div class="card bg-base-100 shadow-sm <?= $isOn && $canMakeSnow ? '' : ($isOn && !$canMakeSnow ? '' : '') ?>">
                        <div class="card-body p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg <?= $isOn ? ($canMakeSnow ? 'bg-info/20' : 'bg-warning/20') : 'bg-base-200' ?> flex items-center justify-center">
                                        <i class="fa-solid fa-snowflake <?= $isOn ? ($canMakeSnow ? 'text-info' : 'text-warning') : 'text-base-content/30' ?>"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm"><?= esc($cannon['cannon_name']) ?></div>
                                        <span class="badge badge-xs <?= $isOn ? ($canMakeSnow ? 'badge-info' : 'badge-warning') : 'badge-ghost' ?>"><?= $isOn ? ($canMakeSnow ? 'PRODUCING' : 'ON (too warm)') : 'OFF' ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-xs text-center">
                                <div><span class="font-bold"><?= $cannon['output_per_day'] ?></span><br><span class="text-base-content/50">Output</span></div>
                                <div><span class="font-bold"><?= currency($cannon['energy_cost']) ?></span><br><span class="text-base-content/50">Energy</span></div>
                                <div><span class="font-bold"><?= number_format($cannon['water_usage']) ?>L</span><br><span class="text-base-content/50">Water</span></div>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $cond ?>" max="100"></progress>
                                <span class="text-xs font-mono"><?= $cond ?>%</span>
                            </div>
                            <div class="flex gap-1 mt-2">
                                <form action="/snowmaking/toggle/<?= $cannon['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                                    <button class="btn btn-xs w-full <?= $isOn ? 'btn-ghost' : 'btn-info' ?>"><i class="fa-solid fa-power-off mr-1"></i><?= $isOn ? 'Turn Off' : 'Turn On' ?></button>
                                </form>
                                <?php if ($cond < 100) : ?><form action="/snowmaking/repair/<?= $cannon['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-xs btn-outline"><i class="fa-solid fa-wrench"></i></button></form><?php endif ?>
                                <form action="/snowmaking/sell/<?= $cannon['id'] ?>" method="post" onsubmit="return confirm('Sell this cannon?')"><?= csrf_field() ?><button class="btn btn-xs btn-ghost text-error"><i class="fa-solid fa-trash"></i></button></form>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
                </div>
            <?php endif ?>

            <!-- Snowmaker Staff -->
            <?php if (!empty($snowmakers)) : ?>
            <h2 class="text-lg font-bold mt-6 mb-3"><i class="fa-solid fa-users mr-1"></i> Snowmaker Crew</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Name</th><th>Level</th><th>Morale</th></tr></thead>
                    <tbody>
                    <?php foreach ($snowmakers as $sm) : ?>
                        <tr>
                            <td class="font-semibold"><?= esc($sm['name']) ?></td>
                            <td>Lv.<?= $sm['level'] ?></td>
                            <td><div class="flex items-center gap-2"><progress class="progress <?= $sm['morale'] >= 60 ? 'progress-success' : ($sm['morale'] >= 30 ? 'progress-warning' : 'progress-error') ?> w-16" value="<?= $sm['morale'] ?>" max="100"></progress><span class="text-xs"><?= $sm['morale'] ?>%</span></div></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div></div></div>
            <?php endif ?>
        </div>

        <!-- Shop -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-shop mr-1"></i> Buy Cannons</h2>
            <div class="space-y-2">
            <?php foreach ($cannonTypes as $level => $ct) : ?>
                <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="card-body p-3">
                        <div class="font-semibold text-sm mb-1"><?= $ct['name'] ?></div>
                        <div class="grid grid-cols-2 gap-1 text-xs mb-2">
                            <div><i class="fa-solid fa-snowflake text-info mr-1"></i><?= $ct['output'] ?> cm³/day</div>
                            <div><i class="fa-solid fa-bolt text-error mr-1"></i><?= currency($ct['energy']) ?>/day</div>
                            <div><i class="fa-solid fa-droplet text-blue-400 mr-1"></i><?= number_format($ct['water']) ?> L/day</div>
                            <div><i class="fa-solid fa-coins text-warning mr-1"></i><?= currency($ct['cost']) ?></div>
                        </div>
                        <form action="/snowmaking/buy" method="post"><?= csrf_field() ?>
                            <input type="hidden" name="level" value="<?= $level ?>">
                            <button class="btn btn-info btn-xs w-full"><i class="fa-solid fa-plus mr-1"></i> Buy - <?= currency($ct['cost']) ?></button>
                        </form>
                    </div>
                </div>
            <?php endforeach ?>
            </div>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-3">
                <h3 class="font-semibold text-xs mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Snowmaking Guide</h3>
                <ul class="text-xs text-base-content/60 space-y-1">
                    <li><i class="fa-solid fa-temperature-low mr-1 text-info"></i> Requires temperature ≤ -2°C</li>
                    <li><i class="fa-solid fa-droplet mr-1 text-blue-400"></i> Needs water supply - check <a href="/water" class="link link-primary">Water Management</a></li>
                    <li><i class="fa-solid fa-bolt mr-1 text-error"></i> Uses energy - check <a href="/energy" class="link link-primary">Energy Management</a></li>
                    <li><i class="fa-solid fa-mountain mr-1"></i> Snow output improves slope conditions</li>
                    <li><i class="fa-solid fa-users mr-1"></i> <a href="/staff/hire" class="link link-primary">Hire snowmakers</a> for better efficiency</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
