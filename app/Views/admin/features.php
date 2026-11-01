<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Feature Flags<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$usage = $usage ?? [];
$levels = [0 => 'Off', 1 => 'Admins', 2 => 'Everyone'];
$counts = [0 => 0, 1 => 0, 2 => 0];
foreach ($flags as $f) $counts[(int) $f['enabled']]++;
$unused = array_filter($flags, fn($f) => empty($usage[$f['flag_key']]));
$groups = [
    'Game features' => array_filter($flags, fn($f) => !str_starts_with($f['flag_key'], 'beta_')),
    'Beta'          => array_filter($flags, fn($f) => str_starts_with($f['flag_key'], 'beta_')),
];
?>
<style>
.ff { max-width: 56rem; margin: 0 auto; padding: 1.5rem 1rem 3rem; }
.ff h1 { font-family: var(--sign); font-size: 1.75rem; font-weight: 700; letter-spacing: -0.025em; margin: 0; }
.ff h2 { font-family: var(--sign); font-size: 1.0625rem; font-weight: 600; margin: 1.75rem 0 0.6rem; }
.ff .muted { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.ff .panel { background: var(--color-base-100); border: 1px solid var(--color-base-300); border-radius: 10px; }
.ff .row { display: flex; align-items: center; gap: 1rem; padding: 0.75rem 1rem; border-top: 1px solid var(--color-base-300); scroll-margin-top: 5rem; }
.ff .row:first-child { border-top: 0; }
.ff .row:target { background: color-mix(in oklab, var(--color-primary) 8%, transparent); }
.ff .name { font-weight: 600; font-size: 0.9375rem; }
.ff .key { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem; }
.ff .uses { font-size: 0.75rem; margin-top: 0.2rem; }
.ff .uses code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.6875rem; padding: 0 0.3rem; border-radius: 3px; background: var(--color-base-200); }
.ff .seg { display: inline-flex; border: 1px solid var(--color-base-300); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
.ff .seg button { font-size: 0.75rem; font-weight: 500; padding: 0.3rem 0.7rem; background: transparent; color: inherit; border-left: 1px solid var(--color-base-300); cursor: pointer; }
.ff .seg form:first-child button { border-left: 0; }
.ff .seg button:hover { background: var(--color-base-200); }
.ff .seg button[aria-pressed="true"].l0 { background: var(--color-error); color: var(--color-error-content); }
.ff .seg button[aria-pressed="true"].l1 { background: var(--color-warning); color: var(--color-warning-content); }
.ff .seg button[aria-pressed="true"].l2 { background: var(--color-success); color: var(--color-success-content); }
@media (max-width: 640px) { .ff .row { flex-wrap: wrap; } .ff .seg { width: 100%; } .ff .seg form { flex: 1; } .ff .seg button { width: 100%; } }
</style>

<div class="ff">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-4">
        <div>
            <a href="/admin" class="text-sm muted">← Admin</a>
            <h1 class="mt-1">Feature flags</h1>
            <p class="text-sm muted mt-1"><?= $counts[2] ?> on for everyone · <?= $counts[1] ?> admins only · <?= $counts[0] ?> off<?= $unused ? ' · ' . count($unused) . ' not connected to anything' : '' ?></p>
        </div>
        <div class="flex gap-2">
            <form action="/admin/features/disable-all-beta" method="post" onsubmit="return confirm('Switch every beta flag off for everyone?')"><?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm">Reset beta</button>
            </form>
            <form action="/admin/features/enable-all" method="post" onsubmit="return confirm('Switch EVERY flag on for everyone, including beta features?')"><?= csrf_field() ?>
                <button class="btn btn-outline btn-sm">All on</button>
            </form>
        </div>
    </div>

    <?php if (session('success')) : ?><div role="status" class="alert alert-success mb-3"><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div role="alert" class="alert alert-error mb-3"><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <p class="text-sm muted mb-3"><b class="text-base-content">Off</b> hides the feature from players (pages redirect to the dashboard and nav links disappear). <b class="text-base-content">Admins</b> lets admins test it before players see it. Admins can always open switched-off pages.</p>

    <search>
        <input type="search" id="flagSearch" placeholder="Search flags…" aria-label="Search flags" class="input input-bordered input-sm w-full">
    </search>

    <?php foreach ($groups as $groupName => $groupFlags) : if (!$groupFlags) continue; ?>
    <section class="flag-group">
    <h2><?= esc($groupName) ?> <span class="text-xs font-normal muted"><?= count($groupFlags) ?></span></h2>
    <div class="panel">
        <?php foreach ($groupFlags as $f) :
            $lvl = (int) $f['enabled'];
            $u = $usage[$f['flag_key']] ?? [];
        ?>
        <div class="row flag-item" id="flag-<?= (int) $f['id'] ?>" data-search="<?= esc(strtolower($f['name'] . ' ' . $f['flag_key'] . ' ' . ($f['description'] ?? '')), 'attr') ?>">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-baseline gap-x-2">
                    <span class="name"><?= esc(str_replace('[BETA] ', '', $f['name'])) ?></span>
                    <span class="key muted"><?= esc($f['flag_key']) ?></span>
                </div>
                <?php if (!empty($f['description'])) : ?><div class="text-sm muted"><?= esc($f['description']) ?></div><?php endif ?>
                <div class="uses muted">
                    <?php if (!$u) : ?>
                        <span class="text-warning"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Not connected to anything. Changing it has no effect.</span>
                    <?php else : ?>
                        <?php if (!empty($u['pages'])) : ?>Gates <?php foreach ($u['pages'] as $pg) : ?><code><?= esc($pg) ?></code> <?php endforeach ?><?php endif ?>
                        <?php if (!empty($u['code'])) : ?><?= !empty($u['pages']) ? '· ' : '' ?>Checked in <?php foreach (array_slice($u['code'], 0, 3) as $c) : ?><code><?= esc($c) ?></code> <?php endforeach ?><?= count($u['code']) > 3 ? '+' . (count($u['code']) - 3) . ' more' : '' ?><?php endif ?>
                    <?php endif ?>
                </div>
            </div>
            <div class="seg" role="group" aria-label="<?= esc($f['name'], 'attr') ?> level">
                <?php foreach ($levels as $value => $label) : ?>
                <form action="/admin/features/set/<?= (int) $f['id'] ?>" method="post"><?= csrf_field() ?>
                    <input type="hidden" name="level" value="<?= $value ?>">
                    <button class="l<?= $value ?>" aria-pressed="<?= $lvl === $value ? 'true' : 'false' ?>" <?= $lvl === $value ? 'disabled' : '' ?>><?= $label ?></button>
                </form>
                <?php endforeach ?>
            </div>
        </div>
        <?php endforeach ?>
    </div>
    </section>
    <?php endforeach ?>
    <p id="noFlags" class="hidden text-sm muted text-center py-6">No flags match.</p>
</div>
<script>
document.getElementById('flagSearch').addEventListener('input', function () {
    var q = this.value.toLowerCase().trim(), shown = 0;
    document.querySelectorAll('.flag-item').forEach(function (el) {
        var hit = !q || el.dataset.search.includes(q);
        el.style.display = hit ? '' : 'none';
        if (hit) shown++;
    });
    document.querySelectorAll('.flag-group').forEach(function (g) {
        g.style.display = Array.prototype.some.call(g.querySelectorAll('.flag-item'), function (el) { return el.style.display !== 'none'; }) ? '' : 'none';
    });
    document.getElementById('noFlags').classList.toggle('hidden', shown > 0);
});
</script>
<?= $this->endSection() ?>
