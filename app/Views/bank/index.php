<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Bank & Loans<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-landmark mr-2 text-primary"></i>Alpine National Bank</h1>
            <p class="text-sm text-base-content/50">Loans, credit lines, and financial services for your resort</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Financial Summary -->
    <div class="card bg-gradient-to-r from-emerald-50 to-teal-50 dark:from-emerald-950 dark:to-teal-950 shadow-xl mb-6">
        <div class="card-body p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-wallet mr-1"></i>Cash Balance</div>
                    <div class="text-2xl font-bold text-success"><?= currency((int) $finance['cash']) ?></div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-file-invoice-dollar mr-1"></i>Active Loans</div>
                    <div class="text-2xl font-bold"><?= count($loans) ?><span class="text-sm text-base-content/50">/3</span></div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-scale-unbalanced mr-1"></i>Total Debt</div>
                    <div class="text-2xl font-bold <?= $totalDebt > 0 ? 'text-error' : 'text-success' ?>"><?= currency($totalDebt) ?></div>
                </div>
                <div class="text-center">
                    <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-calendar-check mr-1"></i>Daily Payments</div>
                    <div class="text-2xl font-bold text-warning"><?= currency($dailyPayments) ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($totalDebt > (int) $finance['cash'] * 2) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span>Your debt exceeds twice your cash reserves. Consider paying off loans before borrowing more.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Active Loans -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-file-contract mr-1"></i> Active Loans</h2>
            <?php if (empty($loans)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-check-circle text-5xl text-success/30 mb-3"></i>
                    <p class="font-semibold text-success">Debt Free!</p>
                    <p class="text-sm text-base-content/50 mt-1">No active loans — your finances are clean</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($loans as $loan) : ?>
                    <?php $progress = $loan['days_total'] > 0 ? round(($loan['days_total'] - $loan['days_remaining']) / $loan['days_total'] * 100) : 0; ?>
                    <div class="card bg-base-100 shadow-sm">
                        <div class="card-body p-4">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="font-bold"><?= currency((int) $loan['principal']) ?></div>
                                    <div class="text-xs text-base-content/50"><?= ucfirst($loan['loan_type']) ?> · <?= $loan['interest_rate'] ?>% APR</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono text-sm font-bold text-error"><?= currency((int) $loan['remaining']) ?></div>
                                    <div class="text-xs text-base-content/50">remaining</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs mb-1">
                                <span><?= $loan['days_remaining'] ?> days left</span>
                                <span><?= $progress ?>% paid</span>
                            </div>
                            <progress class="progress progress-primary w-full" value="<?= $progress ?>" max="100"></progress>

                            <div class="flex items-center justify-between mt-3">
                                <div class="text-xs text-base-content/50">
                                    <i class="fa-solid fa-calendar mr-1"></i><?= currency((int) $loan['daily_payment']) ?>/day
                                </div>
                                <form action="/bank/repay/<?= $loan['id'] ?>" method="post" data-confirm="Pay off the remaining balance?" data-confirm-title="Repay Loan">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-success btn-xs gap-1" <?= (int) $finance['cash'] < (int) $loan['remaining'] ? 'disabled' : '' ?>>
                                        <i class="fa-solid fa-check"></i> Pay Off
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Loan Products -->
        <div class="lg:col-span-3">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-hand-holding-dollar mr-1 text-success"></i> Available Loans</h2>
            <?php if (count($loans) >= 3) : ?>
                <div class="alert alert-warning mb-3"><i class="fa-solid fa-lock"></i><span>Maximum 3 active loans. Repay an existing loan to borrow again.</span></div>
            <?php endif ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($loanOptions as $key => $opt) : ?>
                <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow <?= $key === 'emergency' ? 'border border-error/30' : '' ?>">
                    <div class="card-body p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-10 h-10 rounded-xl <?= $key === 'emergency' ? 'bg-error/10' : 'bg-primary/10' ?> flex items-center justify-center">
                                <i class="<?= $opt['icon'] ?> text-lg <?= $key === 'emergency' ? 'text-error' : 'text-primary' ?>"></i>
                            </div>
                            <div>
                                <div class="font-bold text-sm"><?= $opt['name'] ?></div>
                                <div class="text-xl font-bold text-success"><?= currency($opt['amount']) ?></div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs mb-3">
                            <div class="bg-base-200 rounded-lg p-2 text-center">
                                <div class="font-bold"><?= $opt['rate'] ?>%</div>
                                <div class="text-base-content/50">Interest</div>
                            </div>
                            <div class="bg-base-200 rounded-lg p-2 text-center">
                                <div class="font-bold"><?= $opt['days'] ?> days</div>
                                <div class="text-base-content/50">Term</div>
                            </div>
                            <div class="bg-base-200 rounded-lg p-2 text-center">
                                <div class="font-bold"><?= currency($opt['daily_payment']) ?></div>
                                <div class="text-base-content/50">Daily Payment</div>
                            </div>
                            <div class="bg-base-200 rounded-lg p-2 text-center">
                                <div class="font-bold"><?= currency($opt['total_repay']) ?></div>
                                <div class="text-base-content/50">Total Repay</div>
                            </div>
                        </div>

                        <form action="/bank/borrow" method="post" onsubmit="return confirmAction(this, 'Confirm Loan', 'Borrow <?= currency($opt['amount']) ?> at <?= $opt['rate'] ?>% for <?= $opt['days'] ?> days?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="<?= $key ?>">
                            <button class="btn <?= $key === 'emergency' ? 'btn-error' : 'btn-primary' ?> btn-sm w-full gap-1" <?= count($loans) >= 3 ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-hand-holding-dollar"></i> Borrow <?= currency($opt['amount']) ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach ?>
            </div>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-4">
                <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>How Loans Work</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-base-content/60">
                    <div class="space-y-1.5">
                        <div><i class="fa-solid fa-coins mr-1 text-success"></i> Borrowed cash is added to your balance instantly</div>
                        <div><i class="fa-solid fa-calendar mr-1"></i> Daily payments are deducted automatically by the game tick</div>
                        <div><i class="fa-solid fa-percent mr-1 text-warning"></i> Interest is calculated upfront and included in total repayment</div>
                    </div>
                    <div class="space-y-1.5">
                        <div><i class="fa-solid fa-lock mr-1"></i> Maximum 3 active loans at a time</div>
                        <div><i class="fa-solid fa-check mr-1 text-success"></i> Pay off early anytime — no penalties</div>
                        <div><i class="fa-solid fa-triangle-exclamation mr-1 text-error"></i> Emergency credit has the highest interest rate</div>
                    </div>
                </div>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
