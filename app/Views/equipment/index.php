<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Equipment<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
use App\Libraries\Fleet;
$states = [
    'running' => ['badge-success', 'Running'], 'no_operator' => ['badge-warning', 'No operator'],
    'idle' => ['badge-warning', 'Idle: too warm'], 'off' => ['badge-ghost', 'Off'], 'broken' => ['badge-error', 'Broken'],
];
$typeLabel = ['groomer' => 'Groomers', 'snowmaker' => 'Snow guns'];
$byType = ['groomer' => array_filter($equipment, fn($e) => $e['equipment_type'] === 'groomer'), 'snowmaker' => array_filter($equipment, fn($e) => $e['equipment_type'] === 'snowmaker')];
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-shop mr-2 text-primary" aria-hidden="true"></i>Equipment</h1>
                <p class="text-sm text-base-content/70">Groomers restore your slopes each night; snow guns protect them when the snow is thin</p>
            </div>
        </div>
        <div class="text-sm">Cash <span class="font-bold <?= $cash < 0 ? 'text-error' : 'text-success' ?>"><?= currency($cash) ?></span></div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <a href="/grooming" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Grooming tonight</div>
            <div class="text-2xl font-bold <?= $slopes && $groomCapacity < $slopes ? 'text-warning' : '' ?>"><?= $groomCapacity ?> <span class="text-sm font-normal">of <?= $slopes ?> slopes</span></div>
            <div class="text-xs text-base-content/70"><?= $operators ?> operator<?= $operators === 1 ? '' : 's' ?> · <?= $groomers ?> groomer<?= $groomers === 1 ? '' : 's' ?></div>
        </div></a>
        <a href="/snowmaking" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Snow guns today</div>
            <div class="text-2xl font-bold"><?= $gunCapacity ?> <span class="text-sm font-normal">slopes covered</span></div>
            <div class="text-xs text-base-content/70"><?= $runningGuns ?> of <?= $guns ?> running</div>
        </div></a>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Running cost</div>
            <div class="text-2xl font-bold"><?= currency($dailyCost) ?><span class="text-sm font-normal">/day</span></div>
            <div class="text-xs text-base-content/70">fuel for machines that run</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Fleet resale value</div>
            <div class="text-2xl font-bold"><?= currency($fleetValue) ?></div>
            <div class="text-xs text-base-content/70"><?= count($equipment) ?> machine<?= count($equipment) === 1 ? '' : 's' ?></div>
        </div></div>
    </div>

    <?php if ($idleGroomers > 0) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-user-slash" aria-hidden="true"></i><span><?= $idleGroomers ?> groomer<?= $idleGroomers === 1 ? ' is' : 's are' ?> switched on with no operator, so <?= $idleGroomers === 1 ? 'it stays' : 'they stay' ?> parked. <a href="/staff/hire" class="link font-semibold">Hire a groomer operator</a> for each machine.</span></div>
    <?php elseif ($slopes > 0 && $groomers === 0) : ?>
    <div class="alert mb-4"><i class="fa-solid fa-circle-info text-info" aria-hidden="true"></i><span>Slopes wear down every day. A groomer with an operator restores the worst ones each night.</span></div>
    <?php endif ?>

    <!-- Fleet -->
    <?php foreach ($byType as $type => $list) : if (!$list) continue; ?>
    <section class="mb-6" aria-labelledby="fleet-<?= $type ?>">
        <div class="flex items-center justify-between mb-3">
            <h2 id="fleet-<?= $type ?>" class="text-lg font-bold">Your <?= strtolower($typeLabel[$type]) ?></h2>
            <a href="<?= $type === 'groomer' ? '/grooming' : '/snowmaking' ?>" class="link link-primary text-sm"><?= $type === 'groomer' ? 'Grooming' : 'Snowmaking' ?> →</a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($list as $eq) : $cond = (int) $eq['condition_pct']; [$badge, $label] = $states[$eq['state']]; ?>
            <div class="card bg-base-100 shadow-sm <?= $eq['state'] === 'broken' ? 'border border-error/40' : '' ?>"><div class="card-body p-4 gap-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-semibold truncate"><?= esc($eq['name']) ?></div>
                        <div class="text-xs text-base-content/70"><?= esc($eq['brand']) ?> · <?= $type === 'groomer' ? 'grooms' : 'covers' ?> <?= (int) $eq['capacity'] ?> slope<?= (int) $eq['capacity'] === 1 ? '' : 's' ?> · <?= currency((int) $eq['fuel_cost']) ?>/day when running</div>
                    </div>
                    <span class="badge badge-sm <?= $badge ?> shrink-0"><?= $label ?></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-base-content/70 w-12">Health</span>
                    <progress class="progress <?= $cond >= 60 ? 'progress-success' : ($cond >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $cond ?>" max="100" aria-label="Health <?= $cond ?>%"></progress>
                    <span class="text-xs font-mono w-9 text-right"><?= $cond ?>%</span>
                </div>
                <div class="text-xs text-base-content/70"><i class="fa-solid fa-user mr-1" aria-hidden="true"></i><?= $eq['crew'] ? esc($eq['crew']) : ($type === 'groomer' ? 'Any free operator can drive it' : 'No crew (optional)') ?></div>
                <div class="flex flex-wrap gap-1">
                    <?php if ($eq['state'] !== 'broken') : ?>
                    <form action="/equipment/toggle/<?= (int) $eq['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                        <button class="btn btn-xs w-full <?= $eq['status'] === 'active' ? 'btn-ghost' : 'btn-success' ?>"><i class="fa-solid fa-power-off mr-1" aria-hidden="true"></i><?= $eq['status'] === 'active' ? 'Switch off' : 'Switch on' ?></button>
                    </form>
                    <?php endif ?>
                    <?php if ($cond < 100) : ?>
                    <form action="/equipment/repair/<?= (int) $eq['id'] ?>" method="post" class="flex-1" data-confirm="Repair <?= esc($eq['name']) ?> to 100% for <?= currency($eq['repair_cost']) ?>?"><?= csrf_field() ?>
                        <button class="btn btn-xs w-full gap-1 <?= $eq['state'] === 'broken' ? 'btn-warning' : 'btn-outline' ?>" <?= $cash < $eq['repair_cost'] ? 'disabled' : '' ?>><i class="fa-solid fa-wrench" aria-hidden="true"></i> Repair <?= currency($eq['repair_cost']) ?></button>
                    </form>
                    <?php endif ?>
                    <form action="/equipment/sell/<?= (int) $eq['id'] ?>" method="post" data-confirm="Sell <?= esc($eq['name']) ?> for <?= currency($eq['resale']) ?>?"><?= csrf_field() ?>
                        <button class="btn btn-xs btn-ghost text-error gap-1"><i class="fa-solid fa-coins" aria-hidden="true"></i> Sell <?= currency($eq['resale']) ?></button>
                    </form>
                </div>
            </div></div>
        <?php endforeach ?>
        </div>
    </section>
    <?php endforeach ?>

    <!-- Shop -->
    <section aria-labelledby="shop-h">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
            <h2 id="shop-h" class="text-lg font-bold">Shop<?php if ($discount) : ?> <span class="badge badge-success badge-sm align-middle">Alliance −<?= $discount ?>%</span><?php endif ?></h2>
            <div class="join" role="group" aria-label="Show">
                <?php foreach ($typeLabel as $type => $l) : ?>
                <button type="button" class="btn btn-sm join-item shop-tab <?= $type === 'groomer' ? 'btn-primary' : '' ?>" data-type="<?= $type ?>" aria-pressed="<?= $type === 'groomer' ? 'true' : 'false' ?>"><?= $l ?> (<?= count($catalog[$type]) ?>)</button>
                <?php endforeach ?>
            </div>
        </div>

        <?php foreach ($catalog as $type => $items) : ?>
        <div class="shop-panel grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" data-type="<?= $type ?>" <?= $type === 'groomer' ? '' : 'hidden' ?>>
            <?php foreach ($items as $c) : $canBuy = $cash >= $c['price']; ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 gap-3">
                <div>
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-semibold text-base-content/70 uppercase tracking-wider"><?= esc($c['brand']) ?></span>
                        <?php if ($c['owned']) : ?><span class="badge badge-ghost badge-sm">You own <?= $c['owned'] ?></span><?php endif ?>
                    </div>
                    <h3 class="font-bold text-lg"><?= esc($c['name']) ?></h3>
                    <p class="text-xs text-base-content/70 line-clamp-2"><?= esc($c['description']) ?></p>
                </div>
                <dl class="grid grid-cols-2 gap-2 text-xs">
                    <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70"><?= $type === 'groomer' ? 'Grooms per night' : 'Covers' ?></dt><dd class="font-bold"><?= (int) $c['capacity'] ?> slope<?= (int) $c['capacity'] === 1 ? '' : 's' ?></dd></div>
                    <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Running cost</dt><dd class="font-bold"><?= currency($c['running_cost']) ?>/day</dd></div>
                    <?php if ($type === 'snowmaker') : ?>
                    <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Works at</dt><dd class="font-bold"><?= $c['max_temp'] === null ? 'Any temperature' : temp($c['max_temp']) . ' or colder' ?></dd></div>
                    <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Uses per day</dt><dd class="font-bold"><?= number_format((int) $c['energy_kwh']) ?> kWh · <?= number_format((int) $c['water_liters'] / 1000, 1) ?> m³</dd></div>
                    <?php else : ?>
                    <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Needs</dt><dd class="font-bold">1 operator</dd></div>
                    <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Cost per slope</dt><dd class="font-bold"><?= currency((int) round($c['price'] / max(1, (int) $c['capacity']))) ?></dd></div>
                    <?php endif ?>
                </dl>
                <div class="flex items-center justify-between gap-2 mt-auto">
                    <div>
                        <?php if ($discount) : ?><div class="text-xs text-base-content/70 line-through"><?= currency((int) $c['cost']) ?></div><?php endif ?>
                        <div class="text-xl font-bold"><?= currency($c['price']) ?></div>
                    </div>
                    <form action="/equipment/buy" method="post" data-confirm="Buy <?= esc($c['name']) ?> for <?= currency($c['price']) ?>?"><?= csrf_field() ?>
                        <input type="hidden" name="model" value="<?= esc($c['model_key']) ?>">
                        <button class="btn btn-primary btn-sm gap-1" <?= $canBuy ? '' : 'disabled' ?>><i class="fa-solid fa-cart-plus" aria-hidden="true"></i> <?= $canBuy ? 'Buy' : 'Need ' . currency($c['price'] - $cash) . ' more' ?></button>
                    </form>
                </div>
            </div></div>
            <?php endforeach ?>
        </div>
        <?php endforeach ?>
    </section>

    <details class="card bg-base-100 shadow-sm mt-6">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How equipment works</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>New machines arrive switched off. Switch them on here, or on the Grooming and Snowmaking pages.</li>
            <li>A groomer runs each night only with an operator (one per machine, biggest machines first) and grooms the worst slopes first. Parked groomers cost nothing.</li>
            <li>A snow gun runs only on days cold enough for its model and covers that many slopes. On warmer days it waits at no cost.</li>
            <li>Running machines cost their running cost each day and wear 1–5% a day; at 0% they break. Repairs cost up to <?= (int) (Fleet::REPAIR_SHARE * 100) ?>% of the price depending on wear, and selling returns up to <?= (int) (Fleet::RESALE_SHARE * 100) ?>% depending on condition.</li>
        </ul>
    </details>
</div>

<script>
document.querySelectorAll('.shop-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.shop-tab').forEach(function (t) {
            var on = t === tab;
            t.classList.toggle('btn-primary', on);
            t.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        document.querySelectorAll('.shop-panel').forEach(function (p) { p.hidden = p.dataset.type !== tab.dataset.type; });
    });
});
</script>
<?= $this->endSection() ?>
