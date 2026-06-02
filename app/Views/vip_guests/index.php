<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 max-w-6xl">
    <h1 class="text-3xl font-bold mb-6"><i class="fa-solid fa-star mr-2 text-warning"></i> VIP Guests</h1>

    <?php if (empty($activeVips)) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-12">
            <i class="fa-solid fa-star text-4xl text-base-content/20 mb-3"></i>
            <p class="text-base-content/60">No VIP guests right now. Keep improving your resort — they show up when your resort meets their standards!</p>
        </div></div>
    <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($activeVips as $vip) : ?>
            <?php $type = $vipTypes[$vip['vip_type']] ?? null; ?>
            <div class="card bg-base-100 shadow-sm border-l-4 <?= $vip['status'] === 'visiting' ? 'border-success' : 'border-warning' ?>">
                <div class="card-body">
                    <div class="flex items-center justify-between">
                        <h3 class="card-title text-lg">
                            <i class="<?= $type['icon'] ?? 'fa-solid fa-star' ?> <?= $type['color'] ?? '' ?> mr-1"></i>
                            <?= esc($vip['name']) ?>
                        </h3>
                        <div class="badge <?= $vip['status'] === 'visiting' ? 'badge-success' : 'badge-warning' ?>"><?= ucfirst($vip['status']) ?></div>
                    </div>
                    <div class="text-sm text-base-content/60 mt-1"><?= ucfirst(str_replace('_', ' ', $vip['vip_type'])) ?></div>
                    <div class="grid grid-cols-2 gap-2 text-sm mt-3">
                        <div><span class="text-base-content/50">Reward:</span> <span class="font-bold text-success"><?= currency($vip['reward_amount']) ?></span></div>
                        <div><span class="text-base-content/50">Rep bonus:</span> <span class="font-bold text-warning">+<?= $vip['reputation_bonus'] ?></span></div>
                        <div><span class="text-base-content/50">Days left:</span> <span class="font-bold"><?= $vip['days_remaining'] ?></span></div>
                        <div><span class="text-base-content/50">Arrived:</span> Day <?= $vip['game_day_arrived'] ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>

    <?php if (!empty($pastVips)) : ?>
    <h2 class="text-xl font-semibold mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Past Visits</h2>
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr><th>Guest</th><th>Type</th><th>Result</th><th>Reward</th><th>Rep</th><th>Day</th></tr></thead>
            <tbody>
            <?php foreach ($pastVips as $vip) : ?>
                <?php $type = $vipTypes[$vip['vip_type']] ?? null; ?>
                <tr>
                    <td><i class="<?= $type['icon'] ?? 'fa-solid fa-star' ?> <?= $type['color'] ?? '' ?> mr-1"></i><?= esc($vip['name']) ?></td>
                    <td class="text-xs"><?= ucfirst(str_replace('_', ' ', $vip['vip_type'])) ?></td>
                    <td><span class="badge badge-xs <?= $vip['status'] === 'satisfied' ? 'badge-success' : 'badge-error' ?>"><?= ucfirst($vip['status']) ?></span></td>
                    <td class="font-mono text-xs"><?= $vip['status'] === 'satisfied' ? currency($vip['reward_amount']) : '-' ?></td>
                    <td><?= $vip['status'] === 'satisfied' ? '+' . $vip['reputation_bonus'] : '0' ?></td>
                    <td class="text-xs text-base-content/50">D<?= $vip['game_day_arrived'] ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div></div></div>
    <?php endif ?>

    <div class="card bg-base-100 shadow-sm mt-6"><div class="card-body">
        <h2 class="card-title text-base"><i class="fa-solid fa-circle-info mr-1 text-info"></i> How VIP Guests Work</h2>
        <ul class="text-sm space-y-1 mt-2 text-base-content/70">
            <li><i class="fa-solid fa-check text-success mr-1"></i> VIPs arrive randomly based on your resort quality</li>
            <li><i class="fa-solid fa-check text-success mr-1"></i> Each has requirements — enough slopes, staff, buildings, etc.</li>
            <li><i class="fa-solid fa-check text-success mr-1"></i> Meet their needs during their stay = cash reward + reputation boost</li>
            <li><i class="fa-solid fa-check text-success mr-1"></i> Fail to meet requirements = they leave disappointed, no reward</li>
            <li><i class="fa-solid fa-check text-success mr-1"></i> Rarer VIPs have bigger rewards but tougher requirements</li>
        </ul>
    </div></div>
</div>
<?= $this->endSection() ?>
