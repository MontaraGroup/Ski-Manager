<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Suspicious Activity<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-triangle-exclamation mr-2 text-warning"></i>Suspicious Activity</h1>
    </div>
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-coins mr-1 text-warning"></i>High Cash Players</h2>
        <p class="text-xs text-base-content/50 mb-3">Players above expected thresholds for their difficulty.</p>
        <?php if (empty($suspects)) : ?>
            <p class="text-sm text-success"><i class="fa-solid fa-check mr-1"></i>No suspicious accounts.</p>
        <?php else : ?>
        <div class="overflow-x-auto"><table class="table table-sm">
            <thead><tr><th>Player</th><th>Cash</th><th>Difficulty</th><th>Last Income</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($suspects as $s) : ?>
                <tr><td class="font-semibold"><?= esc($s['username']) ?></td><td class="font-mono text-warning"><?= currency((int)$s['cash']) ?></td><td><span class="badge badge-sm"><?= $s['difficulty'] ?></span></td><td class="text-xs max-w-xs truncate"><?= esc($s['last_income'] ?? '-') ?></td><td><a href="/admin/user/<?= $s['id'] ?>" class="btn btn-ghost btn-xs"><i class="fa-solid fa-eye"></i></a></td></tr>
            <?php endforeach ?>
            </tbody>
        </table></div>
        <?php endif ?>
    </div></div>
    <div class="card bg-base-100 shadow-sm"><div class="card-body">
        <h2 class="font-bold text-sm mb-3"><i class="fa-solid fa-user-shield mr-1 text-info"></i>Recent Admin Cash Edits</h2>
        <?php if (empty($recent)) : ?>
            <p class="text-xs text-base-content/50">No recent admin cash edits.</p>
        <?php else : ?>
        <div class="overflow-x-auto"><table class="table table-sm">
            <thead><tr><th>Player</th><th>Cash</th><th>Action</th><th>When</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $r) : ?>
                <tr><td class="font-semibold"><?= esc($r['username']) ?></td><td class="font-mono"><?= currency((int)($r['cash'] ?? 0)) ?></td><td class="text-xs"><?= esc($r['latest'] ?? '') ?></td><td class="text-xs text-base-content/50"><?= isset($r['created_at']) ? date('M j H:i', strtotime($r['created_at'])) : '-' ?></td></tr>
            <?php endforeach ?>
            </tbody>
        </table></div>
        <?php endif ?>
    </div></div>
</div>
<?= $this->endSection() ?>
