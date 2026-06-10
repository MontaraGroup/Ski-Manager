<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Génépis<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-seedling mr-2 text-success"></i>Génépis</h1>
            <p class="text-sm text-base-content/50">Earn through gameplay, spend on boosts and upgrades</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Balance -->
    <div class="card bg-gradient-to-r from-success/20 to-primary/20 shadow-sm mb-6"><div class="card-body p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="text-xs text-base-content/50 uppercase tracking-wider">Your Balance</div>
                <div class="text-5xl font-bold text-success mt-1"><i class="fa-solid fa-seedling mr-2"></i><?= number_format((int) $genepis['balance']) ?></div>
            </div>
            <div class="flex gap-6 text-sm">
                <div><div class="text-base-content/50">Earned</div><div class="font-bold text-success"><?= number_format((int) $genepis['total_earned']) ?></div></div>
                <div><div class="text-base-content/50">Spent</div><div class="font-bold text-error"><?= number_format((int) $genepis['total_spent']) ?></div></div>
                <div><div class="text-base-content/50">Rate</div><div class="font-bold">1 <i class="fa-solid fa-seedling text-success text-xs"></i> = <?= currency(1000) ?></div></div>
            </div>
        </div>
    </div></div>

    <!-- Active Boosts -->
    <?php if (!empty($activeBoosts)) : ?>
    <div class="card bg-warning/10 border border-warning/30 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-bolt mr-1 text-warning"></i>Active Boosts</h2>
        <div class="flex flex-wrap gap-3">
            <?php foreach ($activeBoosts as $boost) : ?>
            <?php
                $remaining = max(0, strtotime($boost['expires_at']) - time());
                $isPermanent = strtotime($boost['expires_at']) > strtotime('2090-01-01');
                $hours = floor($remaining / 3600);
                $mins = floor(($remaining % 3600) / 60);
                $boostInfo = $shopItems[$boost['boost_type']] ?? null;
            ?>
            <?php if ($boostInfo) : ?>
            <div class="flex items-center gap-2 bg-base-100 rounded-lg px-3 py-2">
                <i class="<?= $boostInfo['icon'] ?> <?= $boostInfo['color'] ?>"></i>
                <div>
                    <div class="text-xs font-semibold"><?= $boostInfo['name'] ?></div>
                    <div class="text-xs text-base-content/50"><?= $isPermanent ? 'Permanent' : $hours . 'h ' . $mins . 'm left' ?></div>
                </div>
            </div>
            <?php endif ?>
            <?php endforeach ?>
        </div>
    </div></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Shop -->
        <div class="lg:col-span-2">
            <?php
                $categories = [];
                foreach ($shopItems as $key => $item) { $categories[$item['category']][$key] = $item; }
                $catIcons = ['Instant' => 'fa-solid fa-bolt text-warning', 'Timed Boost' => 'fa-solid fa-clock text-info', 'Permanent' => 'fa-solid fa-infinity text-primary'];
            ?>
            <?php foreach ($categories as $catName => $items) : ?>
            <h2 class="text-lg font-bold mb-3"><i class="<?= $catIcons[$catName] ?? 'fa-solid fa-shop' ?> mr-1"></i><?= $catName ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
            <?php foreach ($items as $key => $item) : ?>
                <?php
                    $canAfford = (int) $genepis['balance'] >= $item['cost'];
                    $isActive = false;
                    foreach ($activeBoosts as $ab) { if ($ab['boost_type'] === $key) { $isActive = true; break; } }
                    $isPermanentOwned = $isActive && in_array($item['category'], ['Permanent']);
                ?>
                <div class="card bg-base-100 shadow-sm <?= $isActive ? 'border border-warning/50' : '' ?> <?= $item['category'] === 'Permanent' ? 'border-primary/20' : '' ?>"><div class="card-body p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                            <i class="<?= $item['icon'] ?> text-lg <?= $item['color'] ?>"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <div class="font-semibold text-sm"><?= $item['name'] ?></div>
                                <?php if ($item['duration'] && $item['duration'] < 100) : ?><span class="badge badge-ghost badge-xs"><?= $item['duration'] ?>h</span><?php endif ?>
                                <?php if ($item['category'] === 'Permanent') : ?><span class="badge badge-primary badge-xs">Forever</span><?php endif ?>
                                <?php if ($isPermanentOwned) : ?><span class="badge badge-success badge-xs">Owned</span><?php elseif ($isActive) : ?><span class="badge badge-warning badge-xs">Active</span><?php endif ?>
                            </div>
                            <div class="text-xs text-base-content/50 mb-2"><?= $item['desc'] ?></div>
                            <?php if ($isPermanentOwned) : ?>
                                <button class="btn btn-sm btn-disabled gap-1" disabled><i class="fa-solid fa-check"></i> Owned</button>
                            <?php elseif ($isActive) : ?>
                                <button class="btn btn-sm btn-disabled gap-1" disabled><i class="fa-solid fa-clock"></i> Active</button>
                            <?php else : ?>
                            <form action="/genepis/buy" method="post" data-confirm="Spend <?= $item['cost'] ?> Génépis on <?= $item['name'] ?>?"><?= csrf_field() ?>
                                <input type="hidden" name="item" value="<?= $key ?>">
                                <button type="submit" class="btn btn-sm <?= $canAfford ? 'btn-success' : 'btn-disabled' ?> gap-1" <?= !$canAfford ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-seedling"></i><?= $item['cost'] ?>
                                </button>
                            </form>
                            <?php endif ?>
                        </div>
                    </div>
                </div></div>
            <?php endforeach ?>
            </div>
            <?php endforeach ?>

            <!-- Exchange -->
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-right-left mr-1 text-success"></i>Exchange</h2>
            <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
                <div class="flex items-center gap-3 mb-3">
                    <i class="fa-solid fa-seedling text-success text-xl"></i>
                    <i class="fa-solid fa-arrow-right text-base-content/30"></i>
                    <i class="fa-solid fa-money-bill-wave text-success text-xl"></i>
                    <span class="text-sm text-base-content/50">1 Génépis = <?= currency(1000) ?></span>
                </div>
                <form action="/genepis/exchange" method="post" class="flex gap-2" data-confirm="Convert Génépis to cash?"><?= csrf_field() ?>
                    <input type="hidden" name="direction" value="to_cash">
                    <input type="number" name="amount" min="1" max="<?= (int) $genepis['balance'] ?>" placeholder="Amount" class="input input-bordered input-sm flex-1" required>
                    <button type="submit" class="btn btn-success btn-sm gap-1" <?= (int) $genepis['balance'] < 1 ? 'disabled' : '' ?>><i class="fa-solid fa-exchange-alt"></i> Convert</button>
                </form>
                <div class="text-xs text-base-content/40 mt-2">Convert your Génépis into resort cash at a fixed rate</div>
            </div></div>
        </div>

        <!-- Sidebar -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-circle-plus mr-1 text-success"></i>How to Earn</h2>
            <div class="space-y-2 mb-6">
            <?php foreach ($earnMethods as $method) : ?>
                <a href="<?= $method['link'] ?>" class="flex items-center gap-3 bg-base-100 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-8 h-8 rounded-lg bg-success/20 flex items-center justify-center shrink-0">
                        <i class="<?= $method['icon'] ?> text-success"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold text-sm"><?= $method['name'] ?></div>
                        <div class="text-xs text-base-content/50"><?= $method['desc'] ?></div>
                    </div>
                    <div class="badge badge-success badge-sm shrink-0"><?= $method['amount'] ?></div>
                </a>
            <?php endforeach ?>
            </div>

            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i>History</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0">
                <?php if (empty($log)) : ?>
                    <div class="text-center py-6 text-base-content/40 text-sm">No transactions yet</div>
                <?php else : ?>
                    <div class="divide-y divide-base-300">
                    <?php foreach ($log as $entry) : ?>
                        <div class="flex items-center gap-2 p-3">
                            <i class="fa-solid fa-<?= $entry['type'] === 'earned' ? 'circle-plus text-success' : 'circle-minus text-error' ?> text-sm"></i>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs truncate"><?= esc($entry['reason']) ?></div>
                                <div class="text-[10px] text-base-content/30"><?= date('M j, g:ia', strtotime($entry['created_at'])) ?></div>
                            </div>
                            <div class="text-xs font-mono <?= $entry['type'] === 'earned' ? 'text-success' : 'text-error' ?> shrink-0">
                                <?= $entry['type'] === 'earned' ? '+' : '-' ?><?= $entry['amount'] ?>
                            </div>
                        </div>
                    <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div></div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
