<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Hire Staff<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$groups = [
    'slopes' => ['Slopes & lifts', ['groomer', 'mechanic', 'ski_patrol', 'medic', 'snowmaker', 'park_crew']],
    'guests' => ['Guests & buildings', ['instructor', 'chef', 'receptionist', 'manager']],
];
$verdicts = [
    'needed' => ['badge-error', 'Needed', 'fa-circle-exclamation text-error'],
    'useful' => ['badge-success', 'Useful', 'fa-circle-check text-success'],
    'enough' => ['badge-ghost', 'Enough for now', 'fa-circle-minus text-base-content/60'],
    'none' => ['badge-ghost', 'No post yet', 'fa-lock text-base-content/60'],
];
$groupOf = [];
foreach ($groups as $g => [, $keys]) foreach ($keys as $k) $groupOf[$k] = $g;
$needed = array_filter($roles, fn($r) => $r['verdict'] === 'needed');
$useful = array_filter($roles, fn($r) => $r['verdict'] === 'useful');
// Hire opens the role's candidate list (Candidates::today); when the day's list is used up, say so
$hireForm = function (string $key, array $r, string $btn) use ($candidates) {
    $n = count($candidates[$key] ?? []);
    if ($n === 0) return '<button class="btn btn-sm btn-ghost" disabled>None left today</button>';
    return '<button type="button" class="btn btn-sm gap-1 ' . $btn . '" onclick="document.getElementById(\'cand-' . esc($key, 'attr') . '\').showModal()">'
        . '<i class="fa-solid fa-user-plus" aria-hidden="true"></i> Hire</button>';
};
?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-3">
            <a href="/staff" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to staff"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold">Hire staff</h1>
                <p class="text-sm text-base-content/70">Pick from today's candidates. No hiring fee: you pay their daily salary from tonight.</p>
            </div>
        </div>
        <dl class="flex gap-5 text-sm">
            <div><dt class="text-xs text-base-content/70">Cash</dt><dd class="font-bold <?= $cash < 0 ? 'text-error' : '' ?>"><?= currency($cash) ?></dd></div>
            <div><dt class="text-xs text-base-content/70">Staff</dt><dd class="font-bold"><?= $staffCount ?></dd></div>
            <div><dt class="text-xs text-base-content/70">Salaries</dt><dd class="font-bold"><?= currency($payroll) ?>/day</dd></div>
        </dl>
    </div>


    <!-- What the resort needs -->
    <section class="card bg-base-100 shadow-sm mb-6" aria-labelledby="needs-h"><div class="card-body p-5">
        <h2 id="needs-h" class="font-bold mb-1"><?= $needed ? 'Your resort needs' : 'Every key post is covered' ?></h2>
        <?php if (!$needed) : ?>
            <p class="text-sm text-base-content/70"><?= $useful ? 'Nothing is urgent. ' . count($useful) . ' role' . (count($useful) === 1 ? ' would' : 's would') . ' still help: see below.' : 'Nothing is missing right now. Build more (slopes, buildings, machines) to open new posts.' ?></p>
        <?php else : ?>
            <ul class="divide-y divide-base-300">
            <?php foreach ($needed as $key => $r) : ?>
                <li class="flex flex-wrap items-center gap-3 py-2">
                    <div class="w-10 h-10 rounded-lg bg-error/10 flex items-center justify-center shrink-0"><i class="<?= esc($r['icon'], 'attr') ?> text-lg <?= esc($r['color'], 'attr') ?>" aria-hidden="true"></i></div>
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold"><?= esc($r['name']) ?></div>
                        <div class="text-sm text-base-content/70"><?= esc($r['why']) ?></div>
                    </div>
                    <span class="text-sm font-semibold"><?= currency($r['daily']) ?><span class="font-normal text-base-content/70">/day</span></span>
                    <?= $hireForm($key, $r, 'btn-primary') ?>
                </li>
            <?php endforeach ?>
            </ul>
        <?php endif ?>
    </div></section>

    <!-- Filters -->
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h2 class="text-lg font-bold">All roles</h2>
        <div class="min-w-0 overflow-x-auto"><div class="join" role="group" aria-label="Filter roles">
            <?php foreach (['all' => 'All', 'useful' => 'Worth hiring', 'slopes' => 'Slopes & lifts', 'guests' => 'Guests & buildings'] as $f => $label) : ?>
            <button type="button" class="btn btn-sm join-item role-filter <?= $f === 'all' ? 'btn-primary' : '' ?>" data-filter="<?= $f ?>" aria-pressed="<?= $f === 'all' ? 'true' : 'false' ?>"><?= $label ?></button>
            <?php endforeach ?>
        </div></div>
    </div>

    <!-- Role cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="roleGrid">
    <?php foreach ($groups as $g => [, $keys]) : foreach ($keys as $key) : if (!isset($roles[$key])) continue;
        $r = $roles[$key]; [$badge, $label, $icon] = $verdicts[$r['verdict']]; $dim = in_array($r['verdict'], ['enough', 'none'], true); ?>
        <article class="role-card card bg-base-100 shadow-sm <?= $r['verdict'] === 'needed' ? 'border border-error/40' : '' ?>"
                 data-group="<?= $g ?>" data-worth="<?= $dim ? '0' : '1' ?>"><div class="card-body p-4 gap-3">
            <div class="flex items-start gap-3">
                <div class="w-12 h-12 rounded-xl bg-base-200 flex items-center justify-center shrink-0 <?= $dim ? 'opacity-50' : '' ?>"><i class="<?= esc($r['icon'], 'attr') ?> text-xl <?= esc($r['color'], 'attr') ?>" aria-hidden="true"></i></div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-bold"><?= esc($r['name']) ?></h3>
                        <span class="badge badge-sm <?= $badge ?> shrink-0"><?= $label ?></span>
                    </div>
                    <p class="text-sm text-base-content/80 mt-1"><?= esc($r['effect']) ?></p>
                </div>
            </div>
            <p class="text-xs flex items-start gap-1.5 <?= $r['verdict'] === 'needed' ? 'text-error' : 'text-base-content/70' ?>"><i class="fa-solid <?= $icon ?> mt-0.5" aria-hidden="true"></i><span><?= esc($r['why']) ?></span></p>
            <div class="flex items-center justify-between gap-2 border-t border-base-300 pt-3">
                <dl class="flex gap-4 text-xs">
                    <div><dt class="text-base-content/70">Salary</dt><dd class="font-semibold"><?= currency($r['daily']) ?>/day</dd></div>
                    <div><dt class="text-base-content/70">You have</dt><dd class="font-semibold"><?= $r['have'] ?><?= $r['have'] ? ' (' . $r['assigned'] . ' working)' : '' ?></dd></div>
                    <?php if ($key !== 'manager') : ?><div><dt class="text-base-content/70">Posts</dt><dd class="font-semibold"><?= $r['posts'] ?></dd></div><?php endif ?>
                </dl>
                <?= $hireForm($key, $r, $dim ? 'btn-ghost' : 'btn-primary') ?>
            </div>
        </div></article>
    <?php endforeach; endforeach ?>
    </div>
    <p id="roleEmpty" class="text-sm text-base-content/70 text-center py-6" hidden>No roles would help right now.</p>

    <!-- Candidate lists, one dialog per role -->
    <?php foreach ($roles as $key => $r) : if (empty($candidates[$key])) continue; $dim = in_array($r['verdict'], ['enough', 'none'], true); ?>
    <dialog id="cand-<?= esc($key, 'attr') ?>" class="modal modal-bottom sm:modal-middle" aria-labelledby="cand-h-<?= esc($key, 'attr') ?>">
        <div class="modal-box">
            <h3 id="cand-h-<?= esc($key, 'attr') ?>" class="font-bold text-lg"><?= esc($r['name']) ?> candidates</h3>
            <p class="text-sm text-base-content/70 mt-1">Standard salary <?= currency($r['daily']) ?>/day. New candidates arrive tomorrow.<?= $dim ? ' Note: ' . esc($r['why']) : '' ?></p>
            <ul class="divide-y divide-base-300 mt-3">
            <?php foreach ($candidates[$key] as $c) : $diff = $r['daily'] > 0 ? (int) round(($c['daily'] / $r['daily'] - 1) * 100) : 0; ?>
                <li class="py-3 flex flex-wrap items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="font-semibold"><?= esc($c['name']) ?> <span class="badge badge-sm badge-ghost ml-1"><?= esc(\App\Libraries\Candidates::label($c['trait'])) ?></span></div>
                        <div class="text-xs text-base-content/70 mt-1 flex flex-wrap gap-x-3">
                            <span><?= currency($c['daily']) ?>/day<?= $diff ? ' (' . ($diff > 0 ? '+' : '−') . abs($diff) . '%)' : '' ?></span>
                            <span>Morale <?= (int) $c['morale'] ?>%</span>
                            <?php if ($key === 'groomer') : ?><span>Level <?= (int) $c['level'] ?></span><?php endif ?>
                        </div>
                    </div>
                    <form action="/staff/hire" method="post"><?= csrf_field() ?>
                        <input type="hidden" name="candidate" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-sm btn-primary gap-1"><i class="fa-solid fa-user-plus" aria-hidden="true"></i> Hire</button>
                    </form>
                </li>
            <?php endforeach ?>
            </ul>
            <div class="modal-action"><form method="dialog"><button class="btn btn-sm">Close</button></form></div>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
    <?php endforeach ?>

    <details class="card bg-base-100 shadow-sm mt-6">
        <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How staff work</summary>
        <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
            <li>Each role has a few candidates a day, asking for different salaries and starting with different morale; experienced groomer operators start above level 1. New candidates arrive every game day.</li>
            <li>New hires start on the first free post for their role. Reassign them on the <a href="/staff" class="link link-primary">staff page</a>.</li>
            <li>Salaries are paid every night, whether or not the staff member has work.</li>
            <li>Most roles only help while assigned to a post (a slope, lift, building, snow gun or park).</li>
            <li>Morale drops a little every day; working staff lose less than idle ones. Below 10% they may quit.</li>
            <li>Only groomer operators get better with training: each level restores a little more slope condition per night, up to level <?= \App\Controllers\Staff::maxUsefulLevel('groomer') ?>.</li>
        </ul>
    </details>
</div>

<script>
(function () {
    var buttons = document.querySelectorAll('.role-filter'), cards = document.querySelectorAll('.role-card'), empty = document.getElementById('roleEmpty');
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var f = btn.dataset.filter, shown = 0;
            buttons.forEach(function (b) { var on = b === btn; b.classList.toggle('btn-primary', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
            cards.forEach(function (c) {
                var show = f === 'all' || (f === 'useful' ? c.dataset.worth === '1' : c.dataset.group === f);
                c.hidden = !show; if (show) shown++;
            });
            empty.hidden = shown > 0;
        });
    });
})();
</script>
<?= $this->endSection() ?>
