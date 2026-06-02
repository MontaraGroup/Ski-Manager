<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Bank<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/finances" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-building-columns mr-2 text-primary"></i>Bank</h1>
            <p class="text-sm text-base-content/50">Take out loans to fund expansion</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Cash Balance</div>
            <div class="text-2xl font-bold text-success"><?= currency((int) $finance['cash']) ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Total Debt</div>
            <div class="text-2xl font-bold text-error"><?= currency($totalDebt) ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Daily Payments</div>
            <div class="text-2xl font-bold text-warning"><?= currency($dailyPayments) ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xs text-base-content/50">Active Loans</div>
            <div class="text-2xl font-bold"><?= count($loans) ?>/3</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Active Loans</h2>
            <?php if (empty($loans)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-piggy-bank text-4xl text-base-content/20 mb-4"></i>
                    <h3 class="font-semibold text-lg">No active loans</h3>
                    <p class="text-sm text-base-content/50">You're debt-free! Take a loan when you need funds for expansion.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($loans as $loan) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex flex-col md:flex-row md:items-center gap-3">
                            <div class="flex-1">
                                <div class="font-semibold"><?= ucwords(str_replace('_', ' ', $loan['loan_type'])) ?> Loan</div>
                                <div class="text-xs text-base-content/50">
                                    Principal: <?= currency((int) $loan['principal']) ?> —
                                    Rate: <?= $loan['interest_rate'] ?>% —
                                    <?= currency((int) $loan['daily_payment']) ?>/day —
                                    <?= $loan['days_remaining'] ?> days left
                                </div>
                                <div class="flex items-center gap-2 mt-2">
                                    <span class="text-xs text-base-content/50">Remaining:</span>
                                    <progress class="progress progress-error w-32" value="<?= (int) $loan['remaining'] ?>" max="<?= (int) $loan['principal'] * 1.1 ?>"></progress>
                                    <span class="text-xs font-mono text-error"><?= currency((int) $loan['remaining']) ?></span>
                                </div>
                            </div>
                            <form action="/bank/repay/<?= $loan['id'] ?>" method="post" onsubmit="return confirm('Repay <?= currency((int) $loan['remaining']) ?> now?')"><?= csrf_field() ?>
                                <button class="btn btn-success btn-sm"><i class="fa-solid fa-check mr-1"></i>Repay Now</button>
                            </form>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3">Borrow</h2>
            <?php if (count($loans) >= 3) : ?>
                <div class="alert alert-warning"><span>Maximum 3 active loans. Repay one first.</span></div>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($loanOptions as $key => $opt) : ?>
                    <form action="/bank/borrow" method="post"><?= csrf_field() ?>
                        <input type="hidden" name="type" value="<?= $key ?>">
                        <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left" onclick="return confirm('Borrow <?= currency($opt['amount']) ?>? You will repay <?= currency($opt['total_repay']) ?> over <?= $opt['days'] ?> days.')">
                            <div class="card-body p-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                        <i class="<?= $opt['icon'] ?> text-primary"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-sm"><?= $opt['name'] ?></div>
                                        <div class="text-xs text-base-content/50"><?= $opt['rate'] ?>% interest — <?= $opt['days'] ?> days — <?= currency($opt['daily_payment']) ?>/day</div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="font-bold text-success text-sm"><?= currency($opt['amount']) ?></div>
                                        <div class="text-xs text-base-content/50">repay <?= currency($opt['total_repay']) ?></div>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </form>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
