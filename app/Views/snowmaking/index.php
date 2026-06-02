<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Snowmaking<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-snowflake mr-2 text-info"></i>Snowmaking</h1>
                <p class="text-sm text-base-content/50">Manage your snow cannons and artificial snow production</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?>
        <div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div>
    <?php endif ?>
    <?php if (session('error')) : ?>
        <div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div>
    <?php endif ?>

    <!-- Temperature Warning -->
    <?php if (!$canMakeSnow) : ?>
        <div class="alert alert-warning mb-4">
            <i class="fa-solid fa-temperature-high"></i>
            <span>Temperature is <?= temp($temp) ?> — too warm for snowmaking. Cannons need <?= temp(-2) ?> or below to operate.</span>
        </div>
    <?php else : ?>
        <div class="alert alert-info mb-4">
            <i class="fa-solid fa-snowflake"></i>
            <span>Current temperature: <?= temp($temp) ?> — conditions are good for snowmaking!</span>
        </div>
    <?php endif ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold"><?= count($cannons) ?></div>
                <div class="text-xs text-base-content/50">Total Cannons</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-info"><?= count(array_filter($cannons, fn($c) => $c['status'] === 'active')) ?></div>
                <div class="text-xs text-base-content/50">Active</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-success"><?= snow($totalOutput) ?>/day</div>
                <div class="text-xs text-base-content/50">Snow Output</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-warning"><?= currency($totalEnergy) ?></div>
                <div class="text-xs text-base-content/50">Energy Cost/Day</div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-2xl font-bold"><?= count($snowmakers) ?></div>
                <div class="text-xs text-base-content/50">Snowmakers on Staff</div>
            </div>
        </div>
    </div>

    <?php if (count($snowmakers) === 0) : ?>
        <div class="alert alert-warning mb-4">
            <i class="fa-solid fa-user-slash"></i>
            <span>You have no snowmaker staff. <a href="/staff/hire" class="link font-semibold">Hire snowmakers</a> to operate your cannons.</span>
        </div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Cannons List -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Your Snow Cannons</h2>

            <?php if (empty($cannons)) : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body text-center py-12">
                        <i class="fa-solid fa-snowflake text-4xl text-base-content/20 mb-4"></i>
                        <h3 class="font-semibold text-lg">No snow cannons</h3>
                        <p class="text-sm text-base-content/50 mb-4">Buy snow cannons to produce artificial snow and keep your slopes covered.</p>
                    </div>
                </div>
            <?php else : ?>
                <div class="space-y-3">
                    <?php foreach ($cannons as $cannon) : ?>
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body p-4">
                            <div class="flex flex-col md:flex-row md:items-center gap-3">
                                <div class="flex items-center gap-3 flex-1">
                                    <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                        <?php if ($cannon['status'] === 'active') : ?>
                                            <i class="fa-solid fa-snowflake text-info text-lg animate-spin" style="animation-duration:3s"></i>
                                        <?php elseif ($cannon['status'] === 'broken') : ?>
                                            <i class="fa-solid fa-triangle-exclamation text-error text-lg"></i>
                                        <?php else : ?>
                                            <i class="fa-solid fa-snowflake text-base-content/30 text-lg"></i>
                                        <?php endif ?>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-sm"><?= esc($cannon['cannon_name']) ?></div>
                                        <div class="text-xs text-base-content/50">
                                            Lv.<?= $cannon['level'] ?> — <?= snow($cannon['output_per_day']) ?>/day — <?= currency($cannon['energy_cost']) ?>/day energy
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Condition -->
                                    <div class="flex items-center gap-1 min-w-[80px]">
                                        <progress class="progress <?= $cannon['condition_pct'] > 50 ? 'progress-success' : ($cannon['condition_pct'] > 20 ? 'progress-warning' : 'progress-error') ?> w-14" value="<?= $cannon['condition_pct'] ?>" max="100"></progress>
                                        <span class="text-xs"><?= $cannon['condition_pct'] ?>%</span>
                                    </div>

                                    <!-- Status -->
                                    <?php if ($cannon['status'] === 'active') : ?>
                                        <span class="badge badge-info badge-sm">Running</span>
                                    <?php elseif ($cannon['status'] === 'broken') : ?>
                                        <span class="badge badge-error badge-sm">Broken</span>
                                    <?php else : ?>
                                        <span class="badge badge-ghost badge-sm">Off</span>
                                    <?php endif ?>

                                    <!-- Actions -->
                                    <?php if ($cannon['status'] === 'broken') : ?>
                                        <form action="/snowmaking/repair/<?= $cannon['id'] ?>" method="post" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-warning btn-xs"><i class="fa-solid fa-wrench mr-1"></i>Repair</button>
                                        </form>
                                    <?php else : ?>
                                        <form action="/snowmaking/toggle/<?= $cannon['id'] ?>" method="post" class="inline">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-<?= $cannon['status'] === 'active' ? 'ghost' : 'info' ?> btn-xs">
                                                <i class="fa-solid fa-power-off mr-1"></i><?= $cannon['status'] === 'active' ? 'Off' : 'On' ?>
                                            </button>
                                        </form>
                                    <?php endif ?>

                                    <form action="/snowmaking/sell/<?= $cannon['id'] ?>" method="post" class="inline" onsubmit="return confirm('Sell this cannon? You will get 50% back.')">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-xs text-error" aria-label="Sell"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Buy Cannons -->
        <div>
            <h2 class="text-lg font-bold mb-3">Buy Snow Cannon</h2>
            <div class="space-y-2">
                <?php foreach ($cannonTypes as $lvl => $type) : ?>
                <form action="/snowmaking/buy" method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="level" value="<?= $lvl ?>">
                    <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left">
                        <div class="card-body p-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="font-semibold text-sm"><?= $type['name'] ?></div>
                                    <div class="text-xs text-base-content/50">
                                        <?= snow($type['output']) ?>/day output — <?= currency($type['energy']) ?>/day energy
                                    </div>
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

            <!-- Info -->
            <div class="card bg-base-100 shadow-sm mt-4">
                <div class="card-body p-4">
                    <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>How Snowmaking Works</h3>
                    <ul class="text-xs text-base-content/60 space-y-1.5">
                        <li><i class="fa-solid fa-temperature-low mr-1"></i>Requires <?= temp(-2) ?> or below</li>
                        <li><i class="fa-solid fa-user mr-1"></i>Each cannon needs a snowmaker on staff</li>
                        <li><i class="fa-solid fa-bolt mr-1"></i>Active cannons consume energy daily</li>
                        <li><i class="fa-solid fa-droplet mr-1"></i>Water usage affects operating cost</li>
                        <li><i class="fa-solid fa-wrench mr-1"></i>Cannons degrade over time and need repair</li>
                        <li><i class="fa-solid fa-snowflake mr-1"></i>Output adds to your snow base each day</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
<?= $this->endSection() ?>
