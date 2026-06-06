<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Player Comparison<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-code-compare mr-2"></i>Compare Players</h1>
    </div>
    <form class="flex gap-3 mb-6">
        <select name="a" class="select select-bordered select-sm flex-1">
            <option value="">Player A</option>
            <?php foreach ($users as $u) : ?><option value="<?= $u['id'] ?>" <?= $a == $u['id'] ? 'selected' : '' ?>><?= esc($u['username']) ?></option><?php endforeach ?>
        </select>
        <select name="b" class="select select-bordered select-sm flex-1">
            <option value="">Player B</option>
            <?php foreach ($users as $u) : ?><option value="<?= $u['id'] ?>" <?= $b == $u['id'] ? 'selected' : '' ?>><?= esc($u['username']) ?></option><?php endforeach ?>
        </select>
        <button class="btn btn-primary btn-sm">Compare</button>
    </form>
    <?php if ($dataA && $dataB) : ?>
    <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
        <table class="table table-sm">
            <thead><tr><th>Stat</th><th><?= esc($dataA['username']) ?></th><th><?= esc($dataB['username']) ?></th></tr></thead>
            <tbody>
                <tr><td>Cash</td><td class="font-mono"><?= currency((int)($dataA['cash'] ?? 0)) ?></td><td class="font-mono"><?= currency((int)($dataB['cash'] ?? 0)) ?></td></tr>
                <tr><td>Difficulty</td><td><?= $dataA['difficulty'] ?? '-' ?></td><td><?= $dataB['difficulty'] ?? '-' ?></td></tr>
                <tr><td>Staff</td><td><?= $dataA['staff'] ?></td><td><?= $dataB['staff'] ?></td></tr>
                <tr><td>Buildings</td><td><?= $dataA['buildings'] ?></td><td><?= $dataB['buildings'] ?></td></tr>
                <tr><td>Lifts/Slopes</td><td><?= $dataA['items'] ?></td><td><?= $dataB['items'] ?></td></tr>
                <tr><td>Resort</td><td><?= $dataA['resort_map'] ?? '-' ?></td><td><?= $dataB['resort_map'] ?? '-' ?></td></tr>
            </tbody>
        </table>
    </div></div></div>
    <?php elseif ($a || $b) : ?>
    <p class="text-sm text-base-content/50">Select two players to compare.</p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
