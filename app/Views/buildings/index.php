<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= $def['label'] ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="<?= $def['icon'] ?> mr-2 <?= $def['color'] ?>"></i><?= $def['label'] ?></h1>
                <p class="text-sm text-base-content/50"><?= $def['desc'] ?></p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($buildings) ?></div>
            <div class="text-xs text-base-content/50">Total <?= $def['label'] ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-info"><?= number_format($totalCapacity) ?></div>
            <div class="text-xs text-base-content/50"><?= $type === 'ski_patrol' ? 'Sectors Covered' : 'Total Capacity' ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success"><?= currency($totalRevenue) ?></div>
            <div class="text-xs text-base-content/50">Revenue/Day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-error"><?= currency($totalUpkeep) ?></div>
            <div class="text-xs text-base-content/50">Upkeep/Day</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Your <?= $def['label'] ?></h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="<?= $def['icon'] ?> text-4xl text-base-content/20 mb-4"></i>
                    <h3 class="font-semibold text-lg">No <?= strtolower($def['label']) ?> built yet</h3>
                    <p class="text-sm text-base-content/50">Build your first <?= strtolower($def['singular']) ?> to get started.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex flex-col md:flex-row md:items-center gap-3">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                    <i class="<?= $def['icon'] ?> <?= $b['status'] === 'open' ? $def['color'] : 'text-base-content/30' ?> text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-sm"><?= esc($b['name']) ?></div>
                                    <div class="text-xs text-base-content/50">
                                        Lv.<?= $b['level'] ?> -
                                        <?= $type === 'ski_patrol' ? $b['capacity'] . ' sector(s)' : 'Cap: ' . $b['capacity'] ?> -
                                        <?php if ($b['revenue_per_day'] > 0) : ?><?= currency($b['revenue_per_day']) ?>/day income - <?php endif ?>
                                        <?= currency($b['upkeep_per_day']) ?>/day upkeep
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="flex items-center gap-1 min-w-[80px]">
                                    <progress class="progress <?= $b['condition_pct'] > 50 ? 'progress-success' : ($b['condition_pct'] > 20 ? 'progress-warning' : 'progress-error') ?> w-14" value="<?= $b['condition_pct'] ?>" max="100"></progress>
                                    <span class="text-xs"><?= $b['condition_pct'] ?>%</span>
                                </div>
                                <?php if ($b['status'] === 'open') : ?>
                                    <span class="badge badge-success badge-sm">Open</span>
                                <?php elseif ($b['status'] === 'closed') : ?>
                                    <span class="badge badge-ghost badge-sm">Closed</span>
                                <?php elseif ($b['status'] === 'broken') : ?>
                                    <span class="badge badge-error badge-sm">Broken</span>
                                <?php endif ?>
                                <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="inline"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs" aria-label="Toggle on/off"><i class="fa-solid fa-power-off" aria-hidden="true"></i></button>
                                </form>
                                <?php if ($b['level'] < 3) : ?>
                                    <form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" class="inline" onsubmit="return confirm('Upgrade to level <?= $b['level'] + 1 ?>?')"><?= csrf_field() ?>
                                        <button class="btn btn-info btn-xs"><i class="fa-solid fa-arrow-up mr-1"></i>Upgrade</button>
                                    </form>
                                <?php endif ?>
                                <form action="/buildings/sell/<?= $b['id'] ?>" method="post" class="inline" onsubmit="return confirm('Sell <?= esc($b['name']) ?>?')"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs text-error" aria-label="Sell"><i class="fa-solid fa-money-bill-wave" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3">Build New</h2>
            <div class="space-y-2">
            <?php foreach ($def['levels'] as $lvl => $info) : ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?>
                    <input type="hidden" name="type" value="<?= $type ?>">
                    <input type="hidden" name="level" value="<?= $lvl ?>">
                    <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                    <span class="badge badge-neutral badge-xs">Lv.<?= $lvl ?></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm"><?= $info['name'] ?></div>
                                    <div class="text-xs text-base-content/50">
                                        <?= $type === 'ski_patrol' ? $info['capacity'] . ' sector(s)' : 'Capacity: ' . $info['capacity'] ?> -
                                        <?php if ($info['revenue'] > 0) : ?><?= currency($info['revenue']) ?>/day - <?php endif ?>
                                        <?= currency($info['upkeep']) ?>/day upkeep
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-primary text-sm"><?= currency($info['cost']) ?></div>
                                </div>
                            </div>
                        </div>
                    </button>
                </form>
            <?php endforeach ?>
            </div>

            <?php if ($type === 'ski_patrol') : ?>
            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-4">
                <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Ski Patrol Info</h3>
                <ul class="text-xs text-base-content/60 space-y-1.5">
                    <li><i class="fa-solid fa-shield-halved mr-1"></i>Each station covers a number of sectors</li>
                    <li><i class="fa-solid fa-user-shield mr-1"></i>Requires ski patrol staff to operate</li>
                    <li><i class="fa-solid fa-star mr-1"></i>Uncovered slopes reduce reputation</li>
                    <li><i class="fa-solid fa-triangle-exclamation mr-1"></i>No patrol = higher accident risk</li>
                </ul>
            </div></div>
            <?php elseif ($type === 'hotel') : ?>
            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-4">
                <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Hotel Info</h3>
                <ul class="text-xs text-base-content/60 space-y-1.5">
                    <li><i class="fa-solid fa-bed mr-1"></i>Hotels let visitors stay overnight</li>
                    <li><i class="fa-solid fa-coins mr-1"></i>Generate passive accommodation revenue</li>
                    <li><i class="fa-solid fa-users mr-1"></i>More rooms = more multi-day visitors</li>
                    <li><i class="fa-solid fa-bell-concierge mr-1"></i>Require receptionists to operate</li>
                </ul>
            </div></div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
