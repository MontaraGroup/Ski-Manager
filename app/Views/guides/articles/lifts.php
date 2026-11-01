<?php use App\Libraries\Economy as E; use App\Controllers\Resort as R; $names = ['button' => 'Button / drag lift', 'chair_fixed' => 'Fixed chair', 'chair_detach' => 'Detachable chair', 'gondola' => 'Gondola', 'cable_car' => 'Cable car']; ?>
<p>Lifts decide how many skiers your resort can actually serve. Skiers who can't get on a lift stay away, however good your slopes are.</p>

<h2 class="text-xl font-bold pt-2">Capacity</h2>
<p>Lifts run about <?= E::LIFT_HOURS ?> hours a day and a skier rides around <?= E::RIDES_PER_SKIER ?> times, so each <?= number_format(1000) ?> skiers an hour of capacity serves about <?= number_format((int) round(1000 * E::LIFT_HOURS / E::RIDES_PER_SKIER)) ?> skiers a day.</p>
<div class="overflow-x-auto"><table class="table table-sm">
    <thead><tr><th>Lift</th><th class="text-right">Skiers/hour (full seats)</th><th class="text-right">Running cost per km/day</th></tr></thead>
    <tbody><?php foreach (E::LIFT_CAPACITY as $t => $cap) : ?><tr><td><?= $names[$t] ?? $t ?></td><td class="text-right"><?= number_format($cap) ?></td><td class="text-right"><?= currency((int) round(E::LIFT_RUN_PER_M[$t] * 1000)) ?></td></tr><?php endforeach ?></tbody>
</table></div>
<p>A closed lift still costs a fifth of its running cost. Close lifts you don't need on quiet days.</p>

<h2 class="text-xl font-bold pt-2">Wear and repairs</h2>
<p>Open lifts wear up to 2% a day. At 0% a lift breaks and closes. Repair it from the <b>resort page</b>: a full repair costs up to <?= (int) (R::LIFT_REPAIR_SHARE * 100) ?>% of what the lift cost to build, less the better its condition, and a broken lift reopens straight away.</p>
<p>A <b>lift mechanic</b> assigned to a lift cuts that lift's wear by 1% a day. One mechanic looks after one lift, so a mechanic on each of your busiest lifts saves the most repair money.</p>
