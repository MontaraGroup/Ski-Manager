<?php use App\Controllers\Staff as St; use App\Libraries\Candidates as C; $names = array_column(db_connect()->table('staff_roles')->select('role_key, name')->get()->getResultArray(), 'name', 'role_key'); ?>
<p>Staff are paid every night whether or not they have work, and most roles only help while assigned to a post. The <a href="/staff/hire" class="link link-primary">hire page</a> tells you which roles your resort needs right now.</p>

<h2 class="text-xl font-bold pt-2">What each role does</h2>
<ul class="space-y-2">
    <?php foreach (St::EFFECTS as $role => $effect) : ?><li><b><?= esc($names[$role] ?? ucwords(str_replace('_', ' ', $role))) ?>.</b> <?= esc($effect) ?></li><?php endforeach ?>
</ul>

<h2 class="text-xl font-bold pt-2">Choosing candidates</h2>
<p>Each role has <?= C::SHORTLIST ?> candidates a day. They differ in what they ask for and how happy they start:</p>
<ul class="list-disc pl-6 space-y-1">
    <?php foreach (C::TRAITS as $t => [$label, [$sMin, $sMax], [$mMin, $mMax], [$lMin, $lMax]]) : ?>
    <li><b><?= esc($label) ?></b>: <?= $sMin ?>–<?= $sMax ?>% of the standard salary, starting morale <?= $mMin ?>–<?= $mMax ?>%<?= $lMax > 1 ? ', level ' . $lMin . '–' . $lMax . ' (groomer operators only)' : '' ?>.</li>
    <?php endforeach ?>
</ul>

<h2 class="text-xl font-bold pt-2">Morale</h2>
<p>Morale drops a little every day; working staff lose less than idle ones. Below 10% they may quit. Hiring someone with no post to fill is the quickest way to lose them, and their salary.</p>
<p>Only groomer operators get better with training, up to level <?= St::maxUsefulLevel('groomer') ?>.</p>
