<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>VIP Guests<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="container mx-auto p-4 lg:p-8 max-w-6xl">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-star mr-2 text-warning"></i>VIP Guests</h1>
            <p class="text-sm text-base-content/50">High-profile visitors who pay big and boost your reputation - if you meet their standards</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <?php
        $satisfied = array_filter($pastVips, fn($v) => $v['status'] === 'satisfied');
        $totalEarned = array_sum(array_map(fn($v) => (int) $v['reward_amount'], $satisfied));
    ?>

    <div class="card bg-gradient-to-r from-warning/15 to-warning/5 shadow-sm mb-6"><div class="card-body p-5">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-user-clock mr-1 text-warning"></i>Active VIPs</div>
                <div class="text-2xl font-bold text-warning"><?= count($activeVips) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-circle-check mr-1 text-success"></i>Satisfied</div>
                <div class="text-2xl font-bold text-success"><?= count($satisfied) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-sack-dollar mr-1 text-success"></i>Total Earned</div>
                <div class="text-2xl font-bold text-success"><?= currency($totalEarned) ?></div>
            </div>
        </div>
    </div></div>

    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-user-tie mr-1 text-warning"></i> Active VIPs</h2>
    <?php if (empty($activeVips)) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-12">
            <i class="fa-solid fa-star text-5xl text-base-content/15 mb-3"></i>
            <p class="font-semibold">No VIP guests right now</p>
            <p class="text-sm text-base-content/50 mt-1">Build more slopes, hire staff, and add buildings - VIPs arrive when your resort meets their standards. See what's possible below.</p>
        </div></div>
    <?php else : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($activeVips as $vip) : ?>
            <?php
                $type = $vipTypes[$vip['vip_type']] ?? null;
                $duration = $type['duration'] ?? max(1, (int) $vip['days_remaining']);
                $daysLeft = (int) $vip['days_remaining'];
                $stayPct = $duration > 0 ? round(($duration - $daysLeft) / $duration * 100) : 0;
                $visiting = $vip['status'] === 'visiting';
            ?>
            <div class="card bg-base-100 shadow-sm border-2 <?= $visiting ? 'border-success' : 'border-warning' ?>">
                <div class="card-body p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold flex items-center gap-2">
                            <i class="<?= $type['icon'] ?? 'fa-solid fa-star' ?> <?= $type['color'] ?? 'text-warning' ?>"></i>
                            <?= esc($vip['name']) ?>
                        </h3>
                        <span class="badge <?= $visiting ? 'badge-success' : 'badge-warning' ?> badge-sm"><?= ucfirst($vip['status']) ?></span>
                    </div>
                    <div class="text-xs text-base-content/50 mb-3"><?= ucwords(str_replace('_', ' ', $vip['vip_type'])) ?></div>

                    <div class="grid grid-cols-2 gap-2 mb-3">
                        <div class="bg-base-200 rounded-lg p-2 text-center">
                            <div class="font-bold text-success text-sm"><?= currency((int) $vip['reward_amount']) ?></div>
                            <div class="text-[10px] text-base-content/50">Reward</div>
                        </div>
                        <div class="bg-base-200 rounded-lg p-2 text-center">
                            <div class="font-bold text-warning text-sm">+<?= (int) $vip['reputation_bonus'] ?></div>
                            <div class="text-[10px] text-base-content/50">Reputation</div>
                        </div>
                    </div>

                    <?php if (!empty($type['requirements'])) : ?>
                    <div class="text-xs text-base-content/60 mb-2">
                        <span class="font-semibold">Wants:</span>
                        <?php $reqs = []; foreach ($type['requirements'] as $k => $v) { $reqs[] = $v . ' ' . str_replace(['min_', '_'], ['', ' '], $k); } echo esc(implode(', ', $reqs)); ?>
                    </div>
                    <?php endif ?>

                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="text-base-content/50">Day <?= $vip['game_day_arrived'] ?> arrival</span>
                        <span class="font-semibold"><?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> left</span>
                    </div>
                    <progress class="progress <?= $visiting ? 'progress-success' : 'progress-warning' ?> w-full" value="<?= $stayPct ?>" max="100"></progress>
                </div>
            </div>
        <?php endforeach ?>
        </div>
    <?php endif ?>

    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-address-book mr-1 text-primary"></i> VIP Types You Can Attract</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
    <?php foreach ($vipTypes as $key => $type) : ?>
        <?php
            $rarity = (int) ($type['rarity'] ?? 0);
            if ($rarity >= 20) { $rLabel = 'Common'; $rClass = 'badge-success'; }
            elseif ($rarity >= 10) { $rLabel = 'Uncommon'; $rClass = 'badge-info'; }
            elseif ($rarity >= 5) { $rLabel = 'Rare'; $rClass = 'badge-warning'; }
            else { $rLabel = 'Legendary'; $rClass = 'badge-error'; }
        ?>
        <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 rounded-lg bg-base-200 flex items-center justify-center">
                        <i class="<?= $type['icon'] ?> <?= $type['color'] ?>"></i>
                    </div>
                    <div class="font-bold text-sm"><?= ucwords(str_replace('_', ' ', $key)) ?></div>
                </div>
                <span class="badge <?= $rClass ?> badge-sm"><?= $rLabel ?></span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-xs mb-2">
                <div><span class="text-base-content/50">Reward:</span> <span class="font-bold text-success"><?= currency((int) $type['reward']) ?></span></div>
                <div><span class="text-base-content/50">Rep:</span> <span class="font-bold text-warning">+<?= (int) $type['rep_bonus'] ?></span></div>
                <div><span class="text-base-content/50">Stay:</span> <span class="font-bold"><?= (int) $type['duration'] ?> day<?= (int) $type['duration'] === 1 ? '' : 's' ?></span></div>
            </div>
            <div class="text-xs text-base-content/60">
                <span class="font-semibold">Needs:</span>
                <?php $reqs = []; foreach (($type['requirements'] ?? []) as $k => $v) { $reqs[] = $v . ' ' . str_replace(['min_', '_'], ['', ' '], $k); } echo esc(implode(', ', $reqs)); ?>
            </div>
        </div></div>
    <?php endforeach ?>
    </div>

    <?php if (!empty($pastVips)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Past Visits</h2>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr><th>Guest</th><th>Type</th><th>Result</th><th>Reward</th><th>Rep</th><th>Day</th></tr></thead>
            <tbody>
            <?php foreach ($pastVips as $vip) : ?>
                <?php $type = $vipTypes[$vip['vip_type']] ?? null; $ok = $vip['status'] === 'satisfied'; ?>
                <tr>
                    <td class="font-semibold"><i class="<?= $type['icon'] ?? 'fa-solid fa-star' ?> <?= $type['color'] ?? 'text-warning' ?> mr-1"></i><?= esc($vip['name']) ?></td>
                    <td class="text-xs"><?= ucwords(str_replace('_', ' ', $vip['vip_type'])) ?></td>
                    <td><span class="badge badge-xs <?= $ok ? 'badge-success' : 'badge-error' ?>"><?= ucfirst($vip['status']) ?></span></td>
                    <td class="font-mono text-xs"><?= $ok ? currency((int) $vip['reward_amount']) : '-' ?></td>
                    <td class="text-xs"><?= $ok ? '+' . (int) $vip['reputation_bonus'] : '0' ?></td>
                    <td class="text-xs text-base-content/50">D<?= $vip['game_day_arrived'] ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div></div></div>
    <?php endif ?>

    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
        <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>How VIP Guests Work</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-base-content/60">
            <div><i class="fa-solid fa-dice mr-1 text-primary"></i> VIPs arrive randomly based on your resort quality</div>
            <div><i class="fa-solid fa-list-check mr-1 text-warning"></i> Each has requirements - slopes, staff, buildings, lifts</div>
            <div><i class="fa-solid fa-check mr-1 text-success"></i> Meet their needs during the stay for cash + reputation</div>
            <div><i class="fa-solid fa-xmark mr-1 text-error"></i> Fall short and they leave disappointed - no reward</div>
            <div><i class="fa-solid fa-gem mr-1 text-error"></i> Rarer VIPs pay more but demand more</div>
            <div><i class="fa-solid fa-arrow-trend-up mr-1 text-success"></i> Reputation attracts even bigger names over time</div>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
