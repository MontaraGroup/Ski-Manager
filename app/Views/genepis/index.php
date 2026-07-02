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

    <div class="aura aura-gold rounded-2xl mb-6 w-full">
        <div class="card bg-base-100 border border-base-200 shadow-sm w-full">
            <div class="card-body p-6">
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
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <div class="space-y-6 md:col-span-1">
            <div class="card bg-base-100 border border-base-200 shadow-sm">
                <div class="card-body p-6">
                    <h3 class="font-bold text-lg mb-2"><i class="fa-solid fa-money-bill-transfer mr-2 text-success"></i>Exchange Cash</h3>
                    <p class="text-xs text-base-content/60 mb-4">Liquidate your available Genepis into operational ski resort cash metrics.</p>
                    <form action="/genepis/exchange" method="post" class="flex flex-col gap-3" data-confirm="Convert Génépis to cash?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="direction" value="to_cash">
                        <input type="number" name="amount" min="1" max="<?= (int) $genepis['balance'] ?>" placeholder="Amount" class="input input-bordered input-sm w-full" required>
                        <button type="submit" class="btn btn-success btn-sm gap-1 w-full" <?= (int) $genepis['balance'] < 1 ? 'disabled' : '' ?>><i class="fa-solid fa-exchange-alt"></i> Convert</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="md:col-span-2 space-y-6">
            
            <?php 
            $categories = [
                'Permanent' => ['title' => 'Permanent Upgrades', 'icon' => 'fa-arrow-up-right-dots text-primary', 'btn' => 'btn-primary'],
                'Timed Boost' => ['title' => 'Timed Boosts', 'icon' => 'fa-bolt text-warning', 'btn' => 'btn-warning'],
                'Instant' => ['title' => 'Instant Effects', 'icon' => 'fa-bolt-lightning text-error', 'btn' => 'btn-error']
            ];
            ?>

            <?php foreach ($categories as $catKey => $catMeta): ?>
                <div class="card bg-base-100 border border-base-200 shadow-sm">
                    <div class="card-body p-6">
                        <h3 class="font-bold text-lg mb-4"><i class="fa-solid <?= $catMeta['icon'] ?> mr-2"></i><?= $catMeta['title'] ?></h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php 
                            $hasItems = false;
                            foreach ($shopItems as $itemKey => $item): 
                                if ($item['category'] !== $catKey) continue;
                                $hasItems = true;
                                $canAfford = (int)$genepis['balance'] >= $item['cost'];
                            ?>
                                <div class="p-4 bg-base-200 rounded-xl border border-base-300 flex flex-col justify-between">
                                    <div>
                                        <div class="flex items-start gap-2 mb-1">
                                            <i class="<?= esc($item['icon']) ?> <?= esc($item['color']) ?> mt-1 text-sm shrink-0"></i>
                                            <h4 class="font-bold text-sm leading-tight"><?= esc($item['name']) ?></h4>
                                        </div>
                                        <p class="text-xs text-base-content/60 mb-3 ml-6"><?= esc($item['desc']) ?></p>
                                    </div>
                                    <div class="flex items-center justify-between mt-2 ml-6">
                                        <span class="text-xs font-semibold text-success"><i class="fa-solid fa-seedling mr-1"></i><?= number_format((int)$item['cost']) ?></span>
                                        <form action="/genepis/buy" method="post" data-confirm="Spend <?= $item['cost'] ?> Génépis on <?= esc($item['name']) ?>?">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="item" value="<?= esc($itemKey) ?>">
                                            <button type="submit" class="btn <?= $catMeta['btn'] ?> btn-xs" <?= !$canAfford ? 'disabled' : '' ?>>Buy</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (!$hasItems): ?>
                                <p class="text-xs text-base-content/50 col-span-2">No active elements in this category container.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

</div>
<?= $this->endSection() ?>
