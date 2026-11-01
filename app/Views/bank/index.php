<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Bank & Loans<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$freeSlots = max(0, $maxLoans - count($loans));
$loadPct = $lastIncome ? (int) round($dailyPayments / max(1, $lastIncome) * 100) : null;
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-landmark mr-2 text-primary" aria-hidden="true"></i>Alpine National Bank</h1>
            <p class="text-sm text-base-content/70">Borrow now, repay in daily instalments</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Cash</div>
            <div class="text-2xl font-bold <?= $cash < 0 ? 'text-error' : '' ?>"><?= currency($cash) ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Owed</div>
            <div class="text-2xl font-bold <?= $totalDebt > 0 ? 'text-error' : 'text-success' ?>"><?= currency($totalDebt) ?></div>
            <div class="text-xs text-base-content/70"><?= $totalDebt > 0 ? currency($totalPayoff) . ' to pay off today' : 'Debt free' ?></div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Daily repayments</div>
            <div class="text-2xl font-bold <?= $dailyPayments ? 'text-warning' : '' ?>"><?= currency($dailyPayments) ?></div>
            <div class="text-xs <?= $loadPct !== null && $loadPct > 50 ? 'text-error' : 'text-base-content/70' ?>">
                <?= $loadPct === null ? 'taken each night' : $loadPct . '% of yesterday\'s income (' . currency($lastIncome) . ')' ?>
            </div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <div class="text-xs text-base-content/70">Loan slots</div>
            <div class="text-2xl font-bold"><?= count($loans) ?> / <?= $maxLoans ?></div>
            <div class="text-xs text-base-content/70"><?= $hasExtraSlot ? 'includes your extra slot' : '<a href="/genepis" class="link">Extra slot</a> with Génépis' ?></div>
        </div></div>
    </div>

    <?php if ($loadPct !== null && $loadPct > 50) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>Repayments take more than half of what your resort earned yesterday. Repayments are taken even when cash runs out, so your balance can go negative.</span></div>
    <?php elseif ($cash < 0 && $loans) : ?>
    <div class="alert alert-error mb-4"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i><span>Your cash is negative and repayments keep coming. Cut costs or raise income before borrowing more.</span></div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <!-- Active loans -->
        <section class="lg:col-span-2" aria-labelledby="loans-h">
            <h2 id="loans-h" class="text-lg font-bold mb-3">Your loans</h2>
            <?php if (!$loans) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-10">
                    <i class="fa-solid fa-circle-check text-5xl text-success/40 mb-3" aria-hidden="true"></i>
                    <p class="font-semibold">Debt free</p>
                    <p class="text-sm text-base-content/70 mt-1">Borrowing can pay for lifts and buildings before they earn.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($loans as $loan) : $canPay = $cash >= $loan['payoff']; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4 gap-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold"><?= esc($loan['name']) ?></div>
                                <div class="text-xs text-base-content/70"><?= currency((int) $loan['principal']) ?> borrowed · <?= rtrim(rtrim((string) $loan['interest_rate'], '0'), '.') ?>% interest</div>
                            </div>
                            <div class="text-right">
                                <div class="font-mono font-bold text-error"><?= currency((int) $loan['remaining']) ?></div>
                                <div class="text-xs text-base-content/70">left to repay</div>
                            </div>
                        </div>
                        <div class="flex justify-between text-xs"><span><?= (int) $loan['days_remaining'] ?> days left · <?= currency((int) $loan['daily_payment']) ?>/day</span><span><?= $loan['paid_pct'] ?>% of term</span></div>
                        <progress class="progress progress-primary w-full" value="<?= $loan['paid_pct'] ?>" max="100" aria-label="<?= $loan['paid_pct'] ?>% of the term done"></progress>
                        <form action="/bank/repay/<?= (int) $loan['id'] ?>" method="post" class="flex items-center justify-between gap-2 mt-1"
                              data-confirm="Pay off the <?= esc($loan['name']) ?> now for <?= currency($loan['payoff']) ?>?" data-confirm-title="Pay off loan"><?= csrf_field() ?>
                            <span class="text-xs <?= $loan['saving'] > 0 ? 'text-success' : 'text-base-content/70' ?>"><?= $loan['saving'] > 0 ? 'Pay off now and save ' . currency($loan['saving']) . ' interest' : 'Pay off now' ?></span>
                            <button class="btn btn-success btn-xs gap-1 shrink-0" <?= $canPay ? '' : 'disabled' ?>><i class="fa-solid fa-check" aria-hidden="true"></i> Pay <?= currency($loan['payoff']) ?></button>
                        </form>
                        <?php if (!$canPay) : ?><div class="text-xs text-base-content/70 text-right">You need <?= currency($loan['payoff'] - $cash) ?> more cash</div><?php endif ?>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </section>

        <!-- Products -->
        <section class="lg:col-span-3" aria-labelledby="offers-h">
            <h2 id="offers-h" class="text-lg font-bold mb-3">Borrow</h2>
            <?php if (!$freeSlots) : ?>
                <div class="alert alert-warning mb-3"><i class="fa-solid fa-lock" aria-hidden="true"></i><span>All <?= $maxLoans ?> loan slots are in use. Pay one off to borrow again.<?php if (!$hasExtraSlot) : ?> <a href="/genepis" class="link font-semibold">Get an extra slot</a> with Génépis.<?php endif ?></span></div>
            <?php endif ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach ($products as $key => $p) : $emergency = $key === 'emergency'; ?>
                <div class="card bg-base-100 shadow-sm <?= $emergency ? 'border border-error/30' : '' ?>"><div class="card-body p-4 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl <?= $emergency ? 'bg-error/10' : 'bg-primary/10' ?> flex items-center justify-center"><i class="<?= $p['icon'] ?> text-lg <?= $emergency ? 'text-error' : 'text-primary' ?>" aria-hidden="true"></i></div>
                        <div>
                            <div class="font-bold text-sm"><?= esc($p['name']) ?></div>
                            <div class="text-xl font-bold text-success"><?= currency($p['amount']) ?></div>
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 gap-2 text-xs">
                        <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Interest</dt><dd class="font-bold"><?= currency($p['interest']) ?> (<?= $p['rate'] + 0 ?>%)</dd></div>
                        <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Term</dt><dd class="font-bold"><?= $p['days'] ?> days</dd></div>
                        <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Each day</dt><dd class="font-bold"><?= currency($p['daily_payment']) ?></dd></div>
                        <div class="bg-base-200 rounded-lg p-2 text-center"><dt class="text-base-content/70">Total to repay</dt><dd class="font-bold"><?= currency($p['total_repay']) ?></dd></div>
                    </dl>
                    <form action="/bank/borrow" method="post" data-confirm="Borrow <?= currency($p['amount']) ?> and repay <?= currency($p['daily_payment']) ?> a day for <?= $p['days'] ?> days (<?= currency($p['total_repay']) ?> in total)?" data-confirm-title="Confirm loan"><?= csrf_field() ?>
                        <input type="hidden" name="type" value="<?= esc($key) ?>">
                        <button class="btn <?= $emergency ? 'btn-error' : 'btn-primary' ?> btn-sm w-full gap-1" <?= $freeSlots ? '' : 'disabled' ?>><i class="fa-solid fa-hand-holding-dollar" aria-hidden="true"></i> Borrow <?= currency($p['amount']) ?></button>
                    </form>
                </div></div>
            <?php endforeach ?>
            </div>

            <details class="card bg-base-100 shadow-sm mt-4">
                <summary class="card-body p-4 cursor-pointer font-semibold text-sm">How loans work</summary>
                <ul class="px-4 pb-4 text-sm text-base-content/80 space-y-1 list-disc pl-9">
                    <li>The money is added to your cash straight away.</li>
                    <li>The interest is the total for the whole term, not a yearly rate. It's added up front and repaid in equal instalments each night.</li>
                    <li>Instalments are taken even when your cash runs out, so your balance can go negative.</li>
                    <li>Pay off early at any time: you pay what's left of the money you borrowed plus the interest so far, and skip the rest.</li>
                    <li>Up to <?= $maxLoans ?> loans at once<?= $hasExtraSlot ? ' (with your extra slot)' : ', or one more with an extra slot from Génépis' ?>. Loans are cleared when a new season starts, and count against your net worth for the legacy bonus.</li>
                </ul>
            </details>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
