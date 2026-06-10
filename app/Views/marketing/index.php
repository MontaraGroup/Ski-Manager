<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Marketing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-bullhorn mr-2 text-primary"></i>Marketing</h1>
            <p class="text-sm text-base-content/50">Launch campaigns to attract visitors and build reputation</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($campaigns) ?></div>
            <div class="text-xs text-base-content/50">Active</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-error"><?= currency($totalCost) ?></div>
            <div class="text-xs text-base-content/50">Daily Cost</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success">+<?= $totalVisitorBoost ?>%</div>
            <div class="text-xs text-base-content/50">Visitor Boost</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-info">+<?= $totalRepBoost ?></div>
            <div class="text-xs text-base-content/50">Rep/Day</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Active Campaigns -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-signal mr-1"></i>Active Campaigns</h2>
            <?php if (empty($campaigns)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-bullhorn text-4xl text-base-content/20 mb-3"></i>
                    <p class="font-semibold">No active campaigns</p>
                    <p class="text-sm text-base-content/50 mt-1">Launch a campaign from the right panel to attract more visitors.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($campaigns as $c) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg <?= $c['status'] === 'active' ? 'bg-success/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-bullhorn <?= $c['status'] === 'active' ? 'text-success' : 'text-base-content/30' ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-sm"><?= esc($c['name']) ?></span>
                                    <span class="badge badge-xs <?= $c['status'] === 'active' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($c['status']) ?></span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-base-content/50 mt-0.5">
                                    <span><i class="fa-solid fa-coins mr-1"></i><?= currency($c['daily_cost']) ?>/day</span>
                                    <span><i class="fa-solid fa-people-group mr-1"></i>+<?= $c['visitor_boost'] ?>%</span>
                                    <span><i class="fa-solid fa-star mr-1"></i>+<?= $c['reputation_boost'] ?></span>
                                    <span><i class="fa-solid fa-clock mr-1"></i><?= $c['days_remaining'] ?>d left</span>
                                </div>
                                <progress class="progress progress-primary w-full h-1 mt-1.5" value="<?= $c['days_remaining'] ?>" max="60"></progress>
                            </div>
                            <div class="flex items-center gap-1 shrink-0">
                                <form action="/marketing/toggle/<?= $c['id'] ?>" method="post"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs btn-circle"><i class="fa-solid fa-<?= $c['status'] === 'active' ? 'pause' : 'play' ?>"></i></button>
                                </form>
                                <form action="/marketing/cancel/<?= $c['id'] ?>" method="post" data-confirm="Cancel this campaign?"><?= csrf_field() ?>
                                    <button class="btn btn-ghost btn-xs btn-circle text-error"><i class="fa-solid fa-xmark"></i></button>
                                </form>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Launch Campaign -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-rocket mr-1"></i>Launch Campaign</h2>
            <div class="space-y-2">
            <?php foreach ($campaignTypes as $key => $type) : ?>
                <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="card-body p-3">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center shrink-0">
                                <i class="<?= $type['icon'] ?? 'fa-solid fa-bullhorn' ?> text-primary"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm"><?= $type['name'] ?></div>
                            </div>
                            <div class="font-bold text-primary text-sm"><?= currency($type['cost'] ?? 0) ?>/day</div>
                        </div>
                        <div class="flex items-center gap-3 text-xs text-base-content/50 mb-2">
                            <span class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-people-group"></i>+<?= $type['visitors'] ?? 0 ?>%</span>
                            <span class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-star"></i>+<?= $type['rep'] ?? 0 ?></span>
                        </div>
                        <form action="/marketing/launch" method="post" data-confirm="Launch <?= $type['name'] ?> for <?= currency($type['cost'] ?? 0) ?>/day?"><?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= $key ?>">
                            <button class="btn btn-primary btn-xs w-full gap-1"><i class="fa-solid fa-rocket"></i> Launch</button>
                        </form>
                    </div>
                </div>
            <?php endforeach ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
