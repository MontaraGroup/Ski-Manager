<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Real Estate<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\RealEstate as R;
$m = $market['value'];
$used = $plots - $plotsFree;
$nextPlotAt = $plots < R::MAX_PLOTS ? ($plots - R::BASE_PLOTS + 1) * R::SLOPES_PER_PLOT : null;
$signed = fn(float $v) => ($v >= 0 ? '+' : '−') . number_format(abs($v), 2);
$badge = ['leased' => ['badge-success', 'Leased'], 'vacant' => ['badge-ghost', 'Vacant'], 'building' => ['badge-warning', 'Building'], 'sold' => ['badge-info', 'Sold'], 'broken' => ['badge-error', 'Needs repair']];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-city mr-2 text-success" aria-hidden="true"></i>Real estate</h1>
            <p class="text-sm text-base-content/70">Develop properties on your plots, then lease them for rent or sell them while your market is strong.</p>
        </div>
    </div>

    <!-- Market + plots -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm lg:col-span-2"><div class="card-body p-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div class="text-xs text-base-content/70">Property market</div>
                    <div class="text-4xl font-bold <?= $m >= 1.2 ? 'text-success' : ($m >= 0.9 ? '' : 'text-warning') ?>"><?= number_format($m, 2) ?>×</div>
                    <div class="text-xs text-base-content/70">Rents and sale prices are multiplied by this</div>
                </div>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <dt class="text-base-content/70">Base</dt><dd class="text-right font-mono"><?= number_format($market['parts']['base'], 2) ?></dd>
                    <dt class="text-base-content/70">Rating <?= str_repeat('★', $stars) ?: '–' ?></dt><dd class="text-right font-mono"><?= $signed($market['parts']['rating']) ?></dd>
                    <dt class="text-base-content/70"><?= $openSlopes ?> open slopes</dt><dd class="text-right font-mono"><?= $signed($market['parts']['slopes']) ?></dd>
                    <dt class="text-base-content/70">Slopes at <?= $avgCond ?>%</dt><dd class="text-right font-mono"><?= $signed($market['parts']['condition']) ?></dd>
                </dl>
            </div>
            <p class="text-xs text-base-content/70 mt-3">It rises with your star rating (+0.15 a star), open slopes (+0.02 each, up to 15) and slope condition above 60%, and falls when slopes wear down. Range 0.5× to 1.8×.</p>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Plots</div>
            <div class="text-4xl font-bold"><?= $used ?> <span class="text-lg font-normal text-base-content/70">of <?= $plots ?> used</span></div>
            <div class="flex gap-1 mt-2" aria-hidden="true">
                <?php for ($i = 0; $i < $plots; $i++) : ?><span class="h-2 flex-1 rounded <?= $i < $used ? 'bg-success' : 'bg-base-300' ?>"></span><?php endfor ?>
            </div>
            <p class="text-xs text-base-content/70 mt-2">
                <?= $nextPlotAt !== null ? 'Next plot at ' . $nextPlotAt . ' open slopes.' : 'All ' . R::MAX_PLOTS . ' plots unlocked.' ?>
                Sold properties keep their plot.
            </p>
        </div></div>
    </div>

    <!-- Income -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Rent per day</div>
            <div class="text-2xl font-bold text-success"><?= currency($rentPerDay) ?></div>
            <div class="text-xs text-base-content/70"><?= $isWinter ? 'Winter rates' : 'Summer: ' . round(R::SUMMER_RENT * 100) . '% of winter' ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Upkeep per day</div>
            <div class="text-2xl font-bold text-error"><?= currency($upkeepPerDay) ?></div>
            <div class="text-xs text-base-content/70">Leased properties only</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Management fees per day</div>
            <div class="text-2xl font-bold text-success"><?= currency($feesPerDay) ?></div>
            <div class="text-xs text-base-content/70"><?= round(R::MANAGEMENT_FEE * 100, 1) ?>% of sale prices</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Sold this season</div>
            <div class="text-2xl font-bold"><?= currency($salesTotal) ?></div>
            <div class="text-xs text-base-content/70">from <?= count(array_filter($props, fn($p) => $p['state'] === 'sold')) ?> sale(s)</div>
        </div></div>
    </div>

    <!-- Develop -->
    <h2 class="text-lg font-bold mb-3">Develop</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <?php foreach ($projects as $lvl => $pr) : $gain = $pr['sale_now'] - $pr['cost']; ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h3 class="font-semibold"><i class="<?= $pr['icon'] ?> mr-1 text-success" aria-hidden="true"></i><?= esc($pr['name']) ?></h3>
            <dl class="text-sm grid grid-cols-2 gap-y-1 mt-2">
                <dt class="text-base-content/70">Build</dt><dd class="text-right"><?= currency($pr['cost']) ?> · <?= $pr['days'] ?> days</dd>
                <dt class="text-base-content/70">Rent now</dt><dd class="text-right"><?= currency($pr['rent_now']) ?>/day</dd>
                <dt class="text-base-content/70">After upkeep</dt><dd class="text-right <?= $pr['net_now'] > 0 ? 'text-success' : 'text-error' ?>"><?= currency($pr['net_now']) ?>/day</dd>
                <dt class="text-base-content/70">Pays back</dt><dd class="text-right"><?= $pr['payback'] ? $pr['payback'] . ' days' : 'never' ?></dd>
                <dt class="text-base-content/70">Sells today for</dt><dd class="text-right <?= $gain >= 0 ? 'text-success' : 'text-warning' ?>"><?= currency($pr['sale_now']) ?> (<?= $gain >= 0 ? '+' : '' ?><?= round($gain / $pr['cost'] * 100) ?>%)</dd>
            </dl>
            <form action="/real-estate/develop" method="post" class="mt-3"><?= csrf_field() ?>
                <input type="hidden" name="level" value="<?= (int) $lvl ?>">
                <?php if ($plotsFree <= 0) : ?>
                <button type="button" class="btn btn-sm w-full" disabled>No free plot</button>
                <?php else : ?>
                <button class="btn btn-primary btn-sm w-full" <?= $cash < $pr['cost'] ? 'disabled title="Not enough cash"' : '' ?>>Build · <?= currency($pr['cost']) ?></button>
                <?php endif ?>
            </form>
        </div></div>
        <?php endforeach ?>
    </div>

    <!-- Portfolio -->
    <h2 class="text-lg font-bold mb-3">Your properties</h2>
    <?php if (!$props) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-8 text-sm text-base-content/70">Nothing built yet. A chalet is the cheapest way in.</div></div>
    <?php else : ?>
    <div class="space-y-2 mb-6">
        <?php foreach ($props as $p) : [$bc, $bl] = $badge[$p['state']]; $c = (int) $p['condition_pct']; $profit = $p['sale_now'] - $p['project']['cost']; ?>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="flex flex-wrap items-center gap-3">
                <i class="<?= $p['project']['icon'] ?> text-xl <?= $p['state'] === 'sold' ? 'text-info' : 'text-success' ?> w-6 text-center" aria-hidden="true"></i>
                <div class="flex-1 min-w-40">
                    <div class="font-semibold"><?= esc($p['name']) ?> <span class="badge badge-sm <?= $bc ?> ml-1"><?= $p['state'] === 'building' ? (int) $p['build_days_left'] . ' days left' : $bl ?></span></div>
                    <div class="text-xs text-base-content/70">
                        <?php if ($p['state'] === 'sold') : ?>Sold for <?= currency((int) $p['sale_price']) ?> · you earn <?= currency($p['fee']) ?>/day managing it
                        <?php elseif ($p['state'] === 'building') : ?>Leased out automatically when finished
                        <?php elseif ($p['state'] === 'leased') : ?>Rent <?= currency($p['rent']) ?>/day · upkeep <?= currency((int) $p['upkeep_per_day']) ?>/day · condition <?= $c ?>%
                        <?php elseif ($p['state'] === 'broken') : ?>Worn out: no rent until it's repaired
                        <?php else : ?>Vacant: no rent, no upkeep · condition <?= $c ?>%<?php endif ?>
                    </div>
                </div>
                <?php if (!in_array($p['state'], ['sold', 'building'], true)) : ?>
                <div class="flex flex-wrap gap-2">
                    <?php if ($p['state'] !== 'broken') : ?>
                    <form action="/real-estate/sell/<?= (int) $p['id'] ?>" method="post" data-confirm="Sell <?= esc($p['name'], 'attr') ?> for <?= esc(currency($p['sale_now']), 'attr') ?>? It stays on its plot and pays you <?= esc(currency(R::managementFee($p['sale_now'])), 'attr') ?>/day in fees."><?= csrf_field() ?>
                        <button class="btn btn-sm btn-success">Sell · <?= currency($p['sale_now']) ?> <span class="text-xs opacity-80">(<?= $profit >= 0 ? '+' : '' ?><?= currency($profit) ?>)</span></button>
                    </form>
                    <form action="/real-estate/toggle/<?= (int) $p['id'] ?>" method="post"><?= csrf_field() ?>
                        <button class="btn btn-sm btn-ghost"><?= $p['state'] === 'leased' ? 'Make vacant' : 'Lease out' ?></button>
                    </form>
                    <?php endif ?>
                    <?php if ($c < 100) : ?>
                    <form action="/real-estate/repair/<?= (int) $p['id'] ?>" method="post"><?= csrf_field() ?>
                        <button class="btn btn-sm btn-info" <?= $cash < $p['repair'] ? 'disabled title="Not enough cash"' : '' ?>>Repair · <?= currency($p['repair']) ?></button>
                    </form>
                    <?php endif ?>
                </div>
                <?php endif ?>
            </div>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <details class="card bg-base-100 shadow-sm">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How real estate works</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>You have <?= R::BASE_PLOTS ?> plots, plus one for every <?= R::SLOPES_PER_PLOT ?> open slopes (up to <?= R::MAX_PLOTS ?>). Each property uses one plot for the rest of the season, sold or not.</li>
            <li>Construction takes 3 to 8 days. Finished properties are leased out automatically.</li>
            <li>Leased: rent × market every day (<?= round(R::SUMMER_RENT * 100) ?>% in summer), less upkeep. Rent drops as condition wears down; repair to restore it.</li>
            <li>Sold: you get build cost × <?= R::SALE_PREMIUM ?> × market at once (less if worn), then <?= round(R::MANAGEMENT_FEE * 100, 1) ?>% of the price every day as a management fee.</li>
            <li>Vacant properties cost nothing and earn nothing.</li>
        </ul>
    </details>
</div>
<?= $this->endSection() ?>
