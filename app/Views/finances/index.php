<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Finances<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-coins mr-2 text-warning"></i>Finances</h1>
            <p class="text-sm text-base-content/50">Day <?= $gameDay ?> - Financial overview</p>
        </div>
    </div>

    <!-- Balance -->
    <div class="card bg-base-100 shadow-sm mb-6">
        <div class="card-body p-6">
            <div class="text-center">
                <div class="text-xs text-base-content/50 uppercase tracking-wider">Current Balance</div>
                <div class="text-5xl font-bold mt-1 <?= $finance['cash'] >= 0 ? 'text-success' : 'text-error' ?>"><?= currency((int) $finance['cash']) ?></div>
            </div>
        </div>
    </div>

    <!-- Income / Expenses Summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h2 class="font-semibold text-sm text-success mb-3"><i class="fa-solid fa-arrow-trend-up mr-1"></i>Daily Income (estimated)</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-base-content/60">Lift Tickets</span><span class="font-mono text-success">+<?= currency(0) ?></span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Restaurants</span><span class="font-mono text-success">+<?= currency(0) ?></span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Hotels</span><span class="font-mono text-success">+<?= currency(0) ?></span></div>
                    <div class="flex justify-between"><span class="text-base-content/60">Rentals</span><span class="font-mono text-success">+<?= currency(0) ?></span></div>
                    <div class="divider my-1"></div>
                    <div class="flex justify-between font-semibold"><span>Total Income</span><span class="text-success"><?= currency(0) ?></span></div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-4">
                <h2 class="font-semibold text-sm text-error mb-3"><i class="fa-solid fa-arrow-trend-down mr-1"></i>Daily Expenses</h2>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-base-content/60"><i class="fa-solid fa-users mr-1"></i>Staff Salaries (<?= $staffCount ?>)</span>
                        <span class="font-mono text-error">-<?= currency($dailySalaries) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60"><i class="fa-solid fa-bullhorn mr-1"></i>Marketing (<?= $campaignCount ?>)</span>
                        <span class="font-mono text-error">-<?= currency($dailyMarketing) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60"><i class="fa-solid fa-snowflake mr-1"></i>Snowmaking Energy</span>
                        <span class="font-mono text-error">-<?= currency($dailyCannons) ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-base-content/60"><i class="fa-solid fa-moon mr-1"></i>Night Skiing Energy</span>
                        <span class="font-mono text-error">-<?= currency($dailyLights) ?></span>
                    </div>
                    <div class="divider my-1"></div>
                    <div class="flex justify-between font-semibold"><span>Total Expenses</span><span class="text-error">-<?= currency($totalDailyExpenses) ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lifetime Stats -->
    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-xs text-base-content/50">Lifetime Income</div>
                <div class="text-xl font-bold text-success"><?= currency((int) $finance['total_income']) ?></div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-xs text-base-content/50">Lifetime Expenses</div>
                <div class="text-xl font-bold text-error"><?= currency((int) $finance['total_expenses']) ?></div>
            </div>
        </div>
        <div class="card bg-base-100 shadow-sm">
            <div class="card-body p-3 text-center">
                <div class="text-xs text-base-content/50">Net Profit</div>
                <?php $profit = (int) $finance['total_income'] - (int) $finance['total_expenses']; ?>
                <div class="text-xl font-bold <?= $profit >= 0 ? 'text-success' : 'text-error' ?>"><?= currency($profit) ?></div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="card bg-base-100 shadow-sm">
        <div class="card-body p-4">
            <h2 class="font-semibold text-base mb-3"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Recent Transactions</h2>
            <?php if (empty($transactions)) : ?>
                <div class="text-center py-8 text-base-content/40">
                    <i class="fa-solid fa-receipt text-3xl mb-2"></i>
                    <p class="text-sm">No transactions yet. Income and expenses will appear here as the game progresses.</p>
                </div>
            <?php else : ?>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Day</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($transactions as $t) : ?>
                            <tr>
                                <td class="text-base-content/50"><?= $t['game_day'] ?></td>
                                <td><span class="badge badge-ghost badge-sm"><?= esc($t['category']) ?></span></td>
                                <td class="text-sm"><?= esc($t['description']) ?></td>
                                <td class="font-mono <?= $t['type'] === 'income' ? 'text-success' : 'text-error' ?>">
                                    <?= $t['type'] === 'income' ? '+' : '-' ?><?= currency((int) $t['amount']) ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
