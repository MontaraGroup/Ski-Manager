<?php use App\Controllers\Bank as B; use App\Libraries\Fleet as F; use App\Controllers\Resort as R; ?>
<p>Your dashboard and the <a href="/finances" class="link link-primary">finances page</a> show each day's income and costs. The goal is simple: income above costs, most days.</p>

<h2 class="text-xl font-bold pt-2">Where money goes</h2>
<ul class="list-disc pl-6 space-y-1">
    <li><b>Lifts and slopes</b> cost money to run every day, more for long lifts. Usually the biggest cost.</li>
    <li><b>Salaries</b>, every night for every staff member.</li>
    <li><b>Buildings</b> pay upkeep; they earn by how full they are.</li>
    <li><b>Machines</b> cost fuel only on the days they actually run.</li>
</ul>

<h2 class="text-xl font-bold pt-2">Loans</h2>
<p>The <a href="/bank" class="link link-primary">bank</a> lends money repaid in equal daily instalments. The interest is the total for the term, not a yearly rate:</p>
<div class="overflow-x-auto"><table class="table table-sm">
    <thead><tr><th>Loan</th><th class="text-right">Amount</th><th class="text-right">Interest</th><th class="text-right">Term</th><th class="text-right">Per day</th></tr></thead>
    <tbody><?php foreach (B::PRODUCTS as $p) : $t = B::terms($p); ?><tr><td><?= esc($t['name']) ?></td><td class="text-right"><?= currency($t['amount']) ?></td><td class="text-right"><?= currency($t['interest']) ?> (<?= $t['rate'] + 0 ?>%)</td><td class="text-right"><?= $t['days'] ?> days</td><td class="text-right"><?= currency($t['daily_payment']) ?></td></tr><?php endforeach ?></tbody>
</table></div>
<p>Paying off early costs what's left of the money you borrowed plus the interest so far. Instalments are taken even if cash runs out, so borrow for things that earn: a lift or building that pays back within the term.</p>

<h2 class="text-xl font-bold pt-2">Repairs and resale</h2>
<p>Groomers and snow guns cost up to <?= (int) (F::REPAIR_SHARE * 100) ?>% of their price to repair from 0%, and sell for up to <?= (int) (F::RESALE_SHARE * 100) ?>% of their price depending on condition. Lifts cost up to <?= (int) (R::LIFT_REPAIR_SHARE * 100) ?>% of their build cost to repair. Repairing early is cheaper than waiting for a breakdown, and a broken machine earns nothing.</p>
