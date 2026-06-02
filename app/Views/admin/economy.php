<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Economy Overview<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/admin" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-chart-line mr-2 text-success"></i>Economy Overview</h1>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-success"><?= currency($totalCash) ?></div><div class="text-xs text-base-content/50">Total Cash in Game</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold"><?= currency($avgCash) ?></div><div class="text-xs text-base-content/50">Average per Player</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-info"><?= currency($totalIncome) ?></div><div class="text-xs text-base-content/50">Total Income Generated</div></div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center"><div class="text-2xl font-bold text-error"><?= currency($totalDebt) ?></div><div class="text-xs text-base-content/50">Total Active Debt</div></div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-trophy mr-1 text-warning"></i>Richest Players</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0"><div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>#</th><th>Player</th><th>Cash</th><th>Income</th><th>Expenses</th></tr></thead>
                    <tbody>
                    <?php foreach ($richest as $i => $r) : ?>
                        <tr>
                            <td class="font-bold <?= $i === 0 ? 'text-warning' : '' ?>"><?= $i + 1 ?></td>
                            <td><a href="/admin/user/<?= $r['user_id'] ?>" class="link"><?= esc($r['username']) ?></a></td>
                            <td class="font-mono font-bold"><?= currency($r['cash']) ?></td>
                            <td class="text-success font-mono text-xs"><?= currency($r['total_income']) ?></td>
                            <td class="text-error font-mono text-xs"><?= currency($r['total_expenses']) ?></td>
                        </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div></div></div>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-receipt mr-1"></i>Recent Transactions</h2>
            <div class="card bg-base-100 shadow-sm"><div class="card-body p-0">
                <div class="divide-y divide-base-300 max-h-96 overflow-y-auto">
                <?php foreach ($recentTransactions as $t) : ?>
                    <div class="flex items-center gap-2 p-2 text-xs">
                        <span class="font-mono font-bold <?= $t['type'] === 'income' ? 'text-success' : 'text-error' ?> w-20 text-right"><?= $t['type'] === 'income' ? '+' : '-' ?><?= currency(abs($t['amount'])) ?></span>
                        <span class="flex-1 truncate"><?= esc($t['description']) ?></span>
                        <span class="text-base-content/40 shrink-0">D<?= $t['game_day'] ?></span>
                    </div>
                <?php endforeach ?>
                </div>
            </div></div>
        </div>
    </div>

    <div class="card bg-base-100 shadow-sm">
        <div class="card-body">
            <h2 class="card-title"><i class="fa-solid fa-gift mr-1 text-warning"></i>Give Cash to All Players</h2>
            <form action="/admin/add-cash-all" method="post" class="flex items-end gap-3 mt-2" onsubmit="return confirm('Give cash to ALL players?')">
                <?= csrf_field() ?>
                <div class="form-control flex-1">
                    <label class="label"><span class="label-text">Amount</span></label>
                    <input type="number" name="amount" class="input input-bordered input-sm" min="1" max="10000000" placeholder="e.g. 50000" required>
                </div>
                <button type="submit" class="btn btn-warning btn-sm"><i class="fa-solid fa-coins mr-1"></i> Give to All</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
