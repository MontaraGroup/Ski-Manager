<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Génépis<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-seedling mr-2 text-success"></i>Génépis</h1>
            <p class="text-sm text-base-content/50">Your premium currency — earn through gameplay, spend on boosts</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Balance -->
    <div class="card bg-gradient-to-r from-success/20 to-primary/20 shadow-sm mb-6">
        <div class="card-body p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="text-xs text-base-content/50 uppercase tracking-wider">Your Balance</div>
                    <div class="text-5xl font-bold text-success mt-1"><i class="fa-solid fa-seedling mr-2"></i><?= number_format((int) $genepis['balance']) ?></div>
                </div>
                <div class="flex gap-6 text-sm">
                    <div><div class="text-base-content/50">Total Earned</div><div class="font-bold text-success"><?= number_format((int) $genepis['total_earned']) ?></div></div>
                    <div><div class="text-base-content/50">Total Spent</div><div class="font-bold text-error"><?= number_format((int) $genepis['total_spent']) ?></div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Shop -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-shop mr-1"></i>Génépis Shop</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($shopItems as $key => $item) : ?>
                <div class="card bg-base-100 shadow-sm">
                    <div class="card-body p-4">
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                <i class="<?= $item['icon'] ?> text-lg <?= $item['color'] ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm"><?= $item['name'] ?></div>
                                <div class="text-xs text-base-content/50 mb-2"><?= $item['desc'] ?></div>
                                <form action="/genepis/buy" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="item" value="<?= $key ?>">
                                    <button type="submit" class="btn btn-sm <?= (int) $genepis['balance'] >= $item['cost'] ? 'btn-success' : 'btn-disabled' ?> gap-1" <?= (int) $genepis['balance'] < $item['cost'] ? 'disabled' : '' ?> onclick="return confirm('Spend <?= $item['cost'] ?> Génépis on <?= $item['name'] ?>?')">
                                        <i class="fa-solid fa-seedling"></i><?= $item['cost'] ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- How to Earn -->
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

            <!-- Transaction History -->
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i>History</h2>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($log)) : ?>
                        <div class="text-center py-6 text-base-content/40 text-sm">No transactions yet</div>
                    <?php else : ?>
                        <div class="divide-y divide-base-300">
                        <?php foreach ($log as $entry) : ?>
                            <div class="flex items-center gap-2 p-3">
                                <i class="fa-solid fa-<?= $entry['type'] === 'earned' ? 'circle-plus text-success' : 'circle-minus text-error' ?> text-sm"></i>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs truncate"><?= esc($entry['reason']) ?></div>
                                </div>
                                <div class="text-xs font-mono <?= $entry['type'] === 'earned' ? 'text-success' : 'text-error' ?> shrink-0">
                                    <?= $entry['type'] === 'earned' ? '+' : '-' ?><?= $entry['amount'] ?>
                                </div>
                            </div>
                        <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
