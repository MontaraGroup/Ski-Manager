<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Grooming<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$capacity = $fleet['capacity'];
$activeMachines = count(array_filter($machines, fn($m) => $m['status'] === 'active'));
$brokenMachines = count(array_filter($machines, fn($m) => $m['status'] === 'broken'));
$tonightIds = array_flip(array_map(fn($s) => (int) $s['id'], $tonight));
$net = $nightlyRestorePerSlope - $avgDecay;
$tone = fn(int $c) => $c >= 70 ? 'success' : ($c >= 40 ? 'warning' : 'error');
$diffBadge = fn($d) => match ($d) { 'green' => 'badge-success', 'blue' => 'badge-info', 'black' => 'badge-neutral', 'red', 'double_black' => 'badge-error', default => 'badge-ghost' };
$groomable = array_filter($slopes, fn($s) => \App\Libraries\Grooming::groomable($s) && !$s['groomed_today']);
$batch = array_slice($groomable, 0, $runsLeft);
$batchCost = array_sum(array_column($batch, 'extra_cost'));
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-tractor mr-2 text-success" aria-hidden="true"></i>Grooming</h1>
                <p class="text-sm text-base-content/70">Slopes wear down every day. Your machines groom the worst ones overnight; worn slopes draw fewer skiers.</p>
            </div>
        </div>
        <?php if ($capacity > 0 && $batch) : ?>
        <form action="/grooming/groom-all" method="post" data-confirm="Groom <?= count($batch) ?> slope<?= count($batch) === 1 ? '' : 's' ?> now for <?= esc(currency($batchCost), 'attr') ?>?"><?= csrf_field() ?>
            <button class="btn btn-success btn-sm gap-1"><i class="fa-solid fa-tractor" aria-hidden="true"></i> Groom worst <?= count($batch) ?> now · <?= currency($batchCost) ?></button>
        </form>
        <?php endif ?>
    </div>

    <?php if (!$machines) : ?>
        <div role="alert" class="alert alert-warning mb-4"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>No grooming machines yet, so nothing restores your slopes. <a href="/equipment" class="link font-semibold">Buy a groomer</a> and <a href="/staff/hire" class="link font-semibold">hire an operator</a> to run it.</span></div>
    <?php elseif ($capacity === 0) : ?>
        <div role="alert" class="alert alert-warning mb-4"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span>
            <?php if ($activeMachines === 0) : ?>All your machines are off or broken. <a href="/equipment" class="link font-semibold">Switch one on or repair it</a>.
            <?php else : ?>Your machines have no operators. <a href="/staff/hire" class="link font-semibold">Hire a groomer operator</a>: each one runs one machine.<?php endif ?>
        </span></div>
    <?php elseif ($fleet['idle_machines'] > 0) : ?>
        <div role="status" class="alert alert-info mb-4"><i class="fa-solid fa-user-plus" aria-hidden="true"></i><span><?= $fleet['idle_machines'] ?> machine<?= $fleet['idle_machines'] === 1 ? ' is' : 's are' ?> on but idle for lack of an operator. <a href="/staff/hire" class="link font-semibold">Hire groomer operators</a> to use them.</span></div>
    <?php endif ?>
    <?php if ($brokenSlopes > 0) : ?>
        <div role="alert" class="alert alert-error mb-4"><i class="fa-solid fa-ban" aria-hidden="true"></i><span><?= $brokenSlopes ?> slope<?= $brokenSlopes === 1 ? ' has' : 's have' ?> worn down to 0% and closed. Grooming reopens <?= $brokenSlopes === 1 ? 'it' : 'them' ?>: closed slopes go first tonight, or groom <?= $brokenSlopes === 1 ? 'it' : 'them' ?> now.</span></div>
    <?php endif ?>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Average condition</div>
            <div class="text-2xl font-bold text-<?= $tone($avgCondition) ?>"><?= $avgCondition ?>%</div>
            <div class="text-xs text-base-content/70">Slopes draw <?= (int) round(\App\Libraries\Economy::conditionFactor($avgCondition) * 100) ?>% of their skiers</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Tonight's night shift</div>
            <div class="text-2xl font-bold"><?= count($tonight) ?> <span class="text-sm font-normal text-base-content/70">of <?= count($slopes) ?> slopes</span></div>
            <div class="text-xs text-base-content/70"><?= $capacity > 0 ? '+' . $restore . '% each, worst first' : 'No machine can run' ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Wear vs grooming</div>
            <div class="text-2xl font-bold text-<?= $net >= 0 ? 'success' : 'error' ?>"><?= $net >= 0 ? 'Keeping up' : 'Falling behind' ?></div>
            <div class="text-xs text-base-content/70">−<?= $avgDecay ?>%/day wear, +<?= $nightlyRestorePerSlope ?>%/day grooming per slope</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Daytime runs left today</div>
            <div class="text-2xl font-bold"><?= $runsLeft ?> <span class="text-sm font-normal text-base-content/70">of <?= $capacity ?></span></div>
            <div class="text-xs text-base-content/70">Extra passes cost fuel: <?= currency((int) round(\App\Libraries\Grooming::EXTRA_RUN_PER_M * 1000)) ?> per km</div>
        </div></div>
    </div>

    <!-- Slopes -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-mountain mr-1" aria-hidden="true"></i> Slopes <span class="text-sm font-normal text-base-content/70">worst first</span></h2>
    <?php if (!$slopes) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-8">
            <p class="text-sm text-base-content/70">No slopes yet. <a href="/map" class="link link-primary">Build slopes</a> on the trail map.</p>
        </div></div>
    <?php else : ?>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr><th>Slope</th><th class="w-48">Condition</th><th>Draws</th><th>Wear / day</th><th>Status</th><th class="text-right">Extra run</th></tr></thead>
            <tbody>
            <?php foreach ($slopes as $s) : $c = (int) $s['condition_pct']; ?>
                <tr>
                    <td>
                        <div class="flex items-center gap-2">
                            <?php if ($s['difficulty']) : ?><span class="badge badge-xs <?= $diffBadge($s['difficulty']) ?>"><?= esc(ucfirst(str_replace('_', ' ', $s['difficulty']))) ?></span><?php endif ?>
                            <span class="font-semibold"><?= esc($s['name']) ?></span>
                        </div>
                        <div class="text-xs text-base-content/70"><?= esc($s['sector_name']) ?> · <?= distance($s['length_meters']) ?></div>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <progress class="progress progress-<?= $tone($c) ?> flex-1 h-2" value="<?= $c ?>" max="100" aria-label="Condition <?= $c ?>%"></progress>
                            <span class="text-xs font-mono font-bold w-9 text-right text-<?= $tone($c) ?>"><?= $c ?>%</span>
                        </div>
                    </td>
                    <?php if ($s['status'] === 'open') : ?>
                    <td class="text-xs"><?= $s['draw_pct'] ?>% of skiers</td>
                    <td class="text-xs">−<?= $s['decay'] ?>%<?php if ($s['thin_base']) : ?> <span class="badge badge-warning badge-xs" title="Natural snow base under <?= \App\Libraries\Weather::THIN_BASE ?> cm and no snow gun on this slope">thin base</span><?php elseif ($thinBase && $s['snow_covered']) : ?> <span class="badge badge-info badge-xs" title="A snow gun covers this slope today">snowmaking</span><?php endif ?></td>
                    <?php else : ?>
                    <td class="text-xs text-error">Closed</td>
                    <td class="text-xs text-base-content/70">–</td>
                    <?php endif ?>
                    <td class="text-xs">
                        <?php if ($s['status'] !== 'open') : ?><span class="badge badge-xs badge-error"><?= esc(ucfirst($s['status'])) ?></span>
                        <?php elseif ($s['groomed_today']) : ?><span class="text-success"><i class="fa-solid fa-check" aria-hidden="true"></i> Groomed today</span>
                        <?php elseif (isset($tonightIds[(int) $s['id']])) : ?><span class="text-info"><i class="fa-solid fa-moon" aria-hidden="true"></i> Tonight</span>
                        <?php elseif ($c >= 100) : ?><span class="text-base-content/70">Perfect</span>
                        <?php else : ?><span class="text-base-content/70">Waiting</span><?php endif ?>
                    </td>
                    <td class="text-right">
                        <?php if (\App\Libraries\Grooming::groomable($s) && !$s['groomed_today'] && $runsLeft > 0) : ?>
                        <form action="/grooming/groom-single" method="post" class="inline"><?= csrf_field() ?>
                            <input type="hidden" name="slope_id" value="<?= (int) $s['id'] ?>">
                            <button class="btn btn-xs btn-ghost text-success gap-1" title="Groom now for <?= esc(currency($s['extra_cost']), 'attr') ?>"><i class="fa-solid fa-tractor" aria-hidden="true"></i> <?= currency($s['extra_cost']) ?></button>
                        </form>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Fleet -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold"><i class="fa-solid fa-truck-monster mr-1" aria-hidden="true"></i> Fleet</h2>
                <a href="/equipment" class="btn btn-ghost btn-xs">Equipment shop</a>
            </div>
            <?php if (!$machines) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-6 text-sm text-base-content/70">No machines. <a href="/equipment" class="link link-primary">Buy a groomer</a>.</div></div>
            <?php else : ?>
            <div class="space-y-2">
                <?php $crewedIds = array_flip(array_map(fn($m) => (int) $m['id'], $fleet['machines'])); ?>
                <?php foreach ($machines as $m) : $hp = (int) $m['condition_pct']; ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-semibold text-sm truncate"><?= esc($m['name']) ?></div>
                            <div class="text-xs text-base-content/70"><?= (int) $m['capacity'] ?> slopes/night · <?= currency((int) $m['fuel_cost']) ?>/day fuel</div>
                        </div>
                        <?php if ($m['status'] === 'broken') : ?><span class="badge badge-sm badge-error">Broken</span>
                        <?php elseif ($m['status'] !== 'active') : ?><span class="badge badge-sm badge-ghost">Off</span>
                        <?php elseif (isset($crewedIds[(int) $m['id']])) : ?><span class="badge badge-sm badge-success">Running</span>
                        <?php else : ?><span class="badge badge-sm badge-warning">No operator</span><?php endif ?>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="text-xs text-base-content/70 w-12">Health</span>
                        <progress class="progress progress-<?= $hp >= 60 ? 'success' : ($hp >= 30 ? 'warning' : 'error') ?> flex-1 h-1.5" value="<?= $hp ?>" max="100" aria-label="Health <?= $hp ?>%"></progress>
                        <span class="text-xs font-mono w-9 text-right"><?= $hp ?>%</span>
                    </div>
                </div></div>
                <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>

        <!-- Crew + costs + sectors -->
        <div class="space-y-6">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold"><i class="fa-solid fa-user-gear mr-1" aria-hidden="true"></i> Operators</h2>
                    <a href="/staff/hire" class="btn btn-ghost btn-xs">Hire</a>
                </div>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 text-sm">
                    <?php if (!$operators) : ?>
                        <p class="text-base-content/70">No groomer operators. Each operator runs one machine.</p>
                    <?php else : ?>
                        <p><?= count($operators) ?> operator<?= count($operators) === 1 ? '' : 's' ?> running <?= count($fleet['machines']) ?> of <?= $activeMachines ?> switched-on machine<?= $activeMachines === 1 ? '' : 's' ?>. Each pass restores <b>+<?= $restore ?>%</b> (more with experienced operators).</p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <?php foreach ($operators as $o) : ?><span class="badge badge-ghost badge-sm"><?= esc($o['name']) ?> · Lv.<?= (int) $o['level'] ?></span><?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div></div>
            </div>

            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                <h3 class="text-sm font-bold mb-2">Daily cost</h3>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div><div class="text-xs text-base-content/70">Fuel</div><div class="font-bold"><?= currency($fuelPerDay) ?></div></div>
                    <div><div class="text-xs text-base-content/70">Operators</div><div class="font-bold"><?= currency($crewPerDay) ?></div></div>
                    <div><div class="text-xs text-base-content/70">Total</div><div class="font-bold text-error"><?= currency($fuelPerDay + $crewPerDay) ?></div></div>
                </div>
                <p class="text-xs text-base-content/70 mt-2">Fuel is charged for every switched-on machine, crewed or not. Daytime runs cost extra.</p>
            </div></div>

            <?php if (count($sectors) > 1) : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                <h3 class="text-sm font-bold mb-2">By sector</h3>
                <?php foreach ($sectors as $name => $sec) : ?>
                <div class="flex items-center justify-between text-sm py-1">
                    <span><?= esc($name) ?> <span class="text-xs text-base-content/70"><?= $sec['count'] ?> slope<?= $sec['count'] === 1 ? '' : 's' ?></span></span>
                    <span class="font-mono text-<?= $tone($sec['avg']) ?>">avg <?= $sec['avg'] ?>% · worst <?= $sec['worst'] ?>%</span>
                </div>
                <?php endforeach ?>
            </div></div>
            <?php endif ?>
        </div>
    </div>

    <!-- How it works (the real rules, from App\Libraries\Grooming and Economy) -->
    <details class="card bg-base-100 shadow-sm mb-6">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1" aria-hidden="true"></i> How grooming works</summary>
        <div class="px-4 pb-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-base-content/80">
            <ul class="space-y-1 list-disc pl-5">
                <li>Open slopes wear every day: green −2%, blue −3%, red/black −4%, double black −5%.</li>
                <li>Above 0°C or in rain they wear 2% faster; 5 cm or more of fresh snow takes 2% off the wear.</li>
                <li>When the natural snow base is under <?= \App\Libraries\Weather::THIN_BASE ?> cm, slopes wear another 2% unless a snow gun covers them (each running gun covers as many slopes as its capacity, on days cold enough to make snow).</li>
                <li>A slope at 100% draws all its skiers; at 50% it draws 80%; at 0% it closes until it's groomed again.</li>
                <li>Slopes groomed overnight count as groomed snow, which attracts a few more visitors.</li>
            </ul>
            <ul class="space-y-1 list-disc pl-5">
                <li>Every night each running machine grooms as many slopes as its capacity, worst first.</li>
                <li>A machine runs only when switched on and with an operator; one operator per machine.</li>
                <li>Each pass restores +<?= \App\Libraries\Grooming::RESTORE_BASE ?>% plus 2% per average operator level (up to +<?= \App\Libraries\Grooming::RESTORE_MAX ?>%).</li>
                <li>During the day the same fleet can do one extra pass per slope, up to its nightly capacity, for <?= currency((int) round(\App\Libraries\Grooming::EXTRA_RUN_PER_M * 1000)) ?> of fuel per km.</li>
            </ul>
        </div>
    </details>
</div>
<?= $this->endSection() ?>
