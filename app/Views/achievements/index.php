<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Achievements<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$fmt = fn(array $a, $v) => str_starts_with($a['achievement_key'], 'cash_') ? currency((int) $v) : number_format((int) $v);
$counts = ['all' => $total, 'ready' => $readyCount, 'progress' => $total - $completed, 'claimed' => $completed - $readyCount];
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-award mr-2 text-warning" aria-hidden="true"></i>Achievements</h1>
            <p class="text-sm text-base-content/70">Goals that pay cash rewards, and some unlock new parts of the game. Progress updates every night and whenever you open this page.</p>
        </div>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Completed</div>
            <div class="text-2xl font-bold"><?= $completed ?> <span class="text-sm font-normal text-base-content/70">of <?= $total ?></span></div>
            <progress class="progress progress-warning w-full h-1.5 mt-1" value="<?= $completed ?>" max="<?= max(1, $total) ?>" aria-label="<?= $completed ?> of <?= $total ?> completed"></progress>
        </div></div>
        <div class="card bg-base-100 shadow-sm <?= $readyCount ? 'border border-warning/40' : '' ?>"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Ready to claim</div>
            <div class="text-2xl font-bold text-warning"><?= currency($readyReward) ?></div>
            <?php if ($readyCount) : ?>
            <form action="/achievements/claim-all" method="post" class="mt-1"><?= csrf_field() ?>
                <button class="btn btn-warning btn-sm w-full gap-1"><i class="fa-solid fa-gift" aria-hidden="true"></i> Claim all <?= $readyCount ?></button>
            </form>
            <?php else : ?>
            <div class="text-xs text-base-content/70">Nothing waiting</div>
            <?php endif ?>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Rewards earned</div>
            <div class="text-2xl font-bold text-success"><?= currency($earned) ?></div>
            <div class="text-xs text-base-content/70">All seasons</div>
        </div></div>
    </div>

    <?php if ($lockedFeatures) : ?>
    <!-- What's still locked -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h2 class="font-bold mb-2"><i class="fa-solid fa-lock mr-1 text-info" aria-hidden="true"></i> Features still to unlock</h2>
        <p class="text-xs text-base-content/70 mb-3">Claim the achievement to open the feature.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <?php foreach ($lockedFeatures as $f) : ?>
            <div class="flex items-center gap-3 rounded-lg bg-base-200 p-2.5">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-semibold truncate"><?= esc($f['feature']) ?></div>
                    <div class="text-xs text-base-content/70 truncate"><?= esc($f['name']) ?>: <?= esc($f['description']) ?></div>
                </div>
                <?php if ($f['ready']) : ?><span class="badge badge-warning badge-sm">Claim to unlock</span>
                <?php else : ?><span class="text-xs font-mono text-base-content/70"><?= $f['pct'] ?>%</span><?php endif ?>
            </div>
            <?php endforeach ?>
        </div>
    </div></div>
    <?php endif ?>

    <!-- Filters -->
    <div role="tablist" class="tabs tabs-boxed mb-4 w-fit" id="achFilters">
        <?php foreach (['all' => 'All', 'ready' => 'Ready', 'progress' => 'In progress', 'claimed' => 'Done'] as $key => $label) : ?>
        <button type="button" role="tab" class="tab <?= $key === 'all' ? 'tab-active' : '' ?>" data-filter="<?= $key ?>" aria-selected="<?= $key === 'all' ? 'true' : 'false' ?>"><?= $label ?> <span class="ml-1 text-xs opacity-70"><?= $counts[$key] ?></span></button>
        <?php endforeach ?>
    </div>

    <?php foreach ($groups as $category => $list) : if (!$list) continue; $done = count(array_filter($list, fn($a) => $a['state'] !== 'progress')); ?>
    <section class="ach-group mb-6">
        <h2 class="text-lg font-bold mb-2"><?= esc($category) ?> <span class="text-sm font-normal text-base-content/70"><?= $done ?>/<?= count($list) ?></span></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <?php foreach ($list as $a) : ?>
            <div class="ach card bg-base-100 shadow-sm <?= $a['state'] === 'ready' ? 'border border-warning/50' : '' ?> <?= $a['state'] === 'claimed' ? 'opacity-70' : '' ?>" data-state="<?= $a['state'] ?>"><div class="card-body p-3">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg <?= $a['state'] === 'progress' ? 'bg-base-200' : 'bg-warning/20' ?> flex items-center justify-center shrink-0">
                        <i class="<?= esc($a['icon'], 'attr') ?> <?= $a['state'] === 'progress' ? 'text-base-content/40' : 'text-warning' ?>" aria-hidden="true"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="font-semibold text-sm"><?= esc($a['name']) ?></span>
                            <span class="text-xs font-bold text-warning shrink-0"><?= (int) $a['reward_amount'] ? currency((int) $a['reward_amount']) : '' ?></span>
                        </div>
                        <div class="text-xs text-base-content/70"><?= esc($a['description']) ?></div>
                        <?php if ($a['state'] === 'progress') : ?>
                        <div class="flex items-center gap-2 mt-1.5">
                            <progress class="progress progress-primary flex-1 h-1.5" value="<?= (int) $a['progress'] ?>" max="<?= max(1, (int) $a['target']) ?>" aria-label="<?= $a['pct'] ?>% done"></progress>
                            <span class="text-xs font-mono text-base-content/70 shrink-0"><?= $fmt($a, $a['progress']) ?> / <?= $fmt($a, $a['target']) ?></span>
                        </div>
                        <?php endif ?>
                        <div class="flex flex-wrap items-center gap-2 mt-1.5">
                            <?php if ($a['unlocks']) : ?><span class="badge badge-info badge-xs gap-1"><i class="fa-solid fa-<?= $a['claimed'] ? 'lock-open' : 'lock' ?>" aria-hidden="true"></i><?= $a['claimed'] ? 'Unlocked' : 'Unlocks' ?>: <?= esc($a['unlocks']) ?></span><?php endif ?>
                            <?php if ($a['state'] === 'ready') : ?>
                            <form action="/achievements/claim/<?= (int) $a['id'] ?>" method="post" class="ml-auto"><?= csrf_field() ?><button class="btn btn-warning btn-xs">Claim</button></form>
                            <?php elseif ($a['state'] === 'claimed') : ?>
                            <span class="text-xs text-success ml-auto"><i class="fa-solid fa-check" aria-hidden="true"></i> Claimed</span>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div></div>
            <?php endforeach ?>
        </div>
    </section>
    <?php endforeach ?>
    <p id="achEmpty" class="hidden text-sm text-base-content/70 text-center py-8">Nothing here yet.</p>
</div>
<script>
(function () {
    var tabs = document.querySelectorAll('#achFilters [data-filter]');
    function apply(filter) {
        var shown = 0;
        document.querySelectorAll('.ach').forEach(function (el) {
            var hit = filter === 'all' || el.dataset.state === filter;
            el.style.display = hit ? '' : 'none';
            if (hit) shown++;
        });
        document.querySelectorAll('.ach-group').forEach(function (g) {
            g.style.display = Array.prototype.some.call(g.querySelectorAll('.ach'), function (el) { return el.style.display !== 'none'; }) ? '' : 'none';
        });
        document.getElementById('achEmpty').classList.toggle('hidden', shown > 0);
        tabs.forEach(function (t) { var on = t.dataset.filter === filter; t.classList.toggle('tab-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { apply(t.dataset.filter); }); });
})();
</script>
<?= $this->endSection() ?>
