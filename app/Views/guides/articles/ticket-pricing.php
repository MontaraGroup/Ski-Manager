<?php use App\Libraries\Economy as E; $labels = ['full_day' => 'Full day', 'half_day' => 'Half day', 'child' => 'Child', 'two_day' => 'Two days', 'weekly' => 'Weekly', 'group' => 'Group', 'senior' => 'Senior', 'season' => 'Season pass']; ?>
<p>Ticket prices are the main lever on your income. Every skier buys one ticket type, and your prices change how many skiers come at all.</p>

<h2 class="text-xl font-bold pt-2">Reference prices and the ticket mix</h2>
<p>Each ticket has a reference price where it neither attracts nor scares off skiers. Multi-day tickets count as several visits.</p>
<div class="overflow-x-auto"><table class="table table-sm">
    <thead><tr><th>Ticket</th><th class="text-right">Reference price</th><th class="text-right">Share of skiers</th></tr></thead>
    <tbody><?php foreach (E::TICKET_MIX as $t => $share) : ?><tr><td><?= $labels[$t] ?? $t ?></td><td class="text-right"><?= currency(E::TICKET_REFERENCE[$t]) ?></td><td class="text-right"><?= (int) round($share * 100) ?>%</td></tr><?php endforeach ?></tbody>
</table></div>

<h2 class="text-xl font-bold pt-2">How prices change demand</h2>
<p>Price a ticket above its reference and fewer skiers come; below it and more come (up to <?= (int) round((E::MAX_DEMAND - 1) * 100) ?>% more at most). For a full-day ticket:</p>
<ul class="list-disc pl-6 space-y-1">
    <?php foreach ([0.8, 1.0, 1.2, 1.5] as $f) : $p = (int) round(E::TICKET_REFERENCE['full_day'] * $f); ?>
    <li>At <?= currency($p) ?>: <?= (int) round(E::priceDemand('full_day', $p) * 100) ?>% of the skiers you'd get at the reference price.</li>
    <?php endforeach ?>
</ul>
<p>If you stop selling a ticket type, about <?= (int) (E::SWITCH_TICKET * 100) ?>% of the skiers who wanted it buy another ticket instead; the rest stay home.</p>

<h2 class="text-xl font-bold pt-2">Finding your price</h2>
<p>A bit above the reference usually earns more in total, because each skier pays more while most still come. But if your lifts are already full, extra skiers can't get on anyway, so raise prices; and if prices put off a lot of skiers, the <a href="/resort-analysis" class="link link-primary">resort analysis</a> report will tell you exactly how many.</p>
