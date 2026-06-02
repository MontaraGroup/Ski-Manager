<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Marketing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-bullhorn mr-2 text-primary"></i>Marketing</h1>
                <p class="text-sm text-base-content/50">Launch campaigns to attract visitors and build reputation</p>
            </div>
        </div>
    </div>
    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= count($campaigns) ?></div><div class="text-xs text-base-content/50">Active Campaigns</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-warning"><?= currency($totalCost) ?></div><div class="text-xs text-base-content/50">Daily Cost</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success">+<?= $totalVisitorBoost ?>%</div><div class="text-xs text-base-content/50">Visitor Boost</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-info">+<?= $totalRepBoost ?></div><div class="text-xs text-base-content/50">Reputation/Day</div></div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Active Campaigns</h2>
            <?php if (empty($campaigns)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-bullhorn text-4xl text-base-content/20 mb-4"></i>
                    <h3 class="font-semibold text-lg">No active campaigns</h3>
                    <p class="text-sm text-base-content/50">Launch a marketing campaign to attract more visitors.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($campaigns as $c) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex flex-col md:flex-row md:items-center gap-3">
                            <div class="flex-1">
                                <div class="font-semibold"><?= esc($c['name']) ?></div>
                                <div class="text-xs text-base-content/50"><?= currency($c['daily_cost']) ?>/day — +<?= $c['visitor_boost'] ?>% visitors — +<?= $c['reputation_boost'] ?> rep/day — <?= $c['days_remaining'] ?> days left</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($c['status'] === 'active') : ?>
                                    <span class="badge badge-success badge-sm">Active</span>
                                <?php else : ?>
                                    <span class="badge badge-warning badge-sm">Paused</span>
                                <?php endif ?>
                                <form action="/marketing/toggle/<?= $c['id'] ?>" method="post" class="inline"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs"><i class="fa-solid fa-<?= $c['status'] === 'active' ? 'pause' : 'play' ?>"></i></button>
                                </form>
                                <form action="/marketing/cancel/<?= $c['id'] ?>" method="post" class="inline" onsubmit="return confirm('Cancel this campaign?')"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs text-error" aria-label="Delete"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                </form>
                            </div>
                        </div>
                        <progress class="progress progress-primary w-full mt-2" value="<?= $c['days_remaining'] ?>" max="60"></progress>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3">Launch Campaign</h2>
            <div class="space-y-2">
            <?php foreach ($campaignTypes as $key => $type) : ?>
                <form action="/marketing/launch" method="post"><?= csrf_field() ?>
                    <input type="hidden" name="type" value="<?= $key ?>">
                    <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                    <i class="<?= $type['icon'] ?> text-primary"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm"><?= $type['name'] ?></div>
                                    <div class="text-xs text-base-content/50"><?= $type['desc'] ?></div>
                                    <div class="text-xs text-base-content/50">+<?= $type['visitors'] ?>% visitors — +<?= $type['rep'] ?> rep — <?= $type['days'] ?> days</div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-primary text-sm"><?= currency($type['price']) ?></div>
                                    <div class="text-xs text-base-content/50"><?= currency($type['cost']) ?>/day</div>
                                </div>
                            </div>
                        </div>
                    </button>
                </form>
            <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
