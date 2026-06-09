<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Grooming Operations<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-tractor mr-2 text-success"></i>Grooming Operations</h1>
                <p class="text-sm text-base-content/50">Monitor slope conditions, assign crews, and manage grooming machines</p>
            </div>
        </div>
        <div class="flex gap-2">
            <?php if (!empty($criticalSlopes)) : ?>
            <form action="/grooming/groom-critical" method="post" data-confirm="Groom <?= count($criticalSlopes) ?> critical slope(s) first?"><?= csrf_field() ?>
                <button class="btn btn-error btn-sm gap-1"><i class="fa-solid fa-triangle-exclamation"></i> Fix Critical (<?= count($criticalSlopes) ?>)</button>
            </form>
            <?php endif ?>
            <?php if (!empty($equipment)) : ?>
            <form action="/grooming/groom-all" method="post" data-confirm="Run grooming on all slopes?"><?= csrf_field() ?>
                <button class="btn btn-success btn-sm gap-1" <?= count($slopes) === 0 ? 'disabled' : '' ?>><i class="fa-solid fa-tractor"></i> Groom All</button>
            </form>
            <?php endif ?>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Overall Status + Weather Impact -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm md:col-span-2"><div class="card-body p-4">
            <div class="flex items-center gap-6">
                <div class="radial-progress text-<?= $overallCondition >= 70 ? 'success' : ($overallCondition >= 40 ? 'warning' : 'error') ?>" style="--value:<?= $overallCondition ?>;--size:5rem;--thickness:4px;" role="progressbar">
                    <span class="text-lg font-bold"><?= $overallCondition ?>%</span>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-bold">Overall Slope Condition</h2>
                    <p class="text-sm text-base-content/60"><?= $overallCondition >= 80 ? 'Excellent conditions — visitors love it.' : ($overallCondition >= 50 ? 'Conditions declining. Schedule grooming soon.' : 'Poor conditions — visitors are leaving. Groom now!') ?></p>
                    <div class="flex gap-4 mt-2 text-xs text-base-content/50">
                        <span><i class="fa-solid fa-arrow-up text-success mr-1"></i>Grooming power: +<?= $groomBoost ?>%/run</span>
                        <span><i class="fa-solid fa-arrow-down text-error mr-1"></i>Daily decay: -<?= $dailyDecay ?>%</span>
                        <?php if ($groomBoost > $dailyDecay) : ?>
                        <span class="text-success font-semibold"><i class="fa-solid fa-check mr-1"></i>Sustainable</span>
                        <?php else : ?>
                        <span class="text-warning font-semibold"><i class="fa-solid fa-exclamation mr-1"></i>Losing ground</span>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h3 class="text-sm font-bold mb-2"><i class="fa-solid fa-cloud-sun mr-1"></i> Weather Impact</h3>
            <div class="text-2xl font-bold mb-1"><?= $weatherTemp ?>°<?= isImperial() ? 'F' : 'C' ?></div>
            <p class="text-xs text-base-content/60 mb-2"><?= $weatherDesc ?></p>
            <div class="space-y-1 text-xs">
                <?php if ($weatherTemp > 32) : ?>
                <div class="flex items-center gap-1 text-warning"><i class="fa-solid fa-sun"></i> Warm temps accelerate snow melt (-2% extra/day)</div>
                <?php elseif ($weatherTemp < 15) : ?>
                <div class="flex items-center gap-1 text-info"><i class="fa-solid fa-snowflake"></i> Cold temps preserve conditions (+1% slower decay)</div>
                <?php else : ?>
                <div class="flex items-center gap-1 text-success"><i class="fa-solid fa-check"></i> Ideal grooming temperatures</div>
                <?php endif ?>
                <?php if (stripos($weatherDesc, 'snow') !== false) : ?>
                <div class="flex items-center gap-1 text-info"><i class="fa-solid fa-cloud-meatball"></i> Fresh snowfall improving base (+2%)</div>
                <?php endif ?>
            </div>
        </div></div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-3 md:grid-cols-6 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold"><?= $slopeCount ?></div>
            <div class="text-xs text-base-content/50">Slopes</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold"><?= count($groomers) ?></div>
            <div class="text-xs text-base-content/50">Crew</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold <?= $totalAssigned >= $totalNeeded ? 'text-success' : 'text-warning' ?>"><?= $totalAssigned ?>/<?= $totalNeeded ?></div>
            <div class="text-xs text-base-content/50">Assigned</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold text-success"><?= $activeEquipment ?></div>
            <div class="text-xs text-base-content/50">Machines On</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold <?= count($criticalSlopes) > 0 ? 'text-error animate-pulse' : 'text-success' ?>"><?= count($criticalSlopes) ?></div>
            <div class="text-xs text-base-content/50">Critical</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-xl font-bold <?= $brokenEquipment > 0 ? 'text-error' : 'text-success' ?>"><?= $brokenEquipment ?></div>
            <div class="text-xs text-base-content/50">Broken</div>
        </div></div>
    </div>

    <!-- Alerts -->
    <?php if (empty($groomers) && empty($equipment)) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-circle-info"></i><span>You need <a href="/equipment" class="link font-semibold">grooming machines</a> and <a href="/staff/hire" class="link font-semibold">groomer operators</a> to maintain your slopes.</span></div>
    <?php elseif (empty($groomers)) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-user-slash"></i><span>No groomer operators hired. <a href="/staff/hire" class="link font-semibold">Hire groomers</a> to run your machines.</span></div>
    <?php elseif (empty($equipment)) : ?>
        <div class="alert alert-warning mb-4"><i class="fa-solid fa-toolbox"></i><span>No grooming machines. <a href="/equipment" class="link font-semibold">Buy a groomer</a> from the equipment shop.</span></div>
    <?php endif ?>
    <?php if (!empty($criticalSlopes)) : ?>
        <div class="alert alert-error mb-4"><i class="fa-solid fa-triangle-exclamation"></i><span><strong><?= count($criticalSlopes) ?> slope(s) below 40%!</strong> They'll close at 0%. Use "Fix Critical" to prioritize them.</span></div>
    <?php endif ?>

    <!-- Slope Conditions -->
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-lg font-bold"><i class="fa-solid fa-mountain mr-1"></i> Slope Conditions</h2>
        <div class="flex gap-1">
            <button class="btn btn-ghost btn-xs sort-btn" data-sort="name" onclick="sortSlopes('name')"><i class="fa-solid fa-arrow-down-a-z"></i></button>
            <button class="btn btn-ghost btn-xs sort-btn" data-sort="condition" onclick="sortSlopes('condition')"><i class="fa-solid fa-arrow-down-1-9"></i></button>
        </div>
    </div>
    <?php if (empty($slopes)) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-8">
            <i class="fa-solid fa-mountain text-3xl text-base-content/20 mb-2"></i>
            <p class="text-sm text-base-content/50">No slopes built. <a href="/map" class="link link-primary">Build slopes</a> on the trail map.</p>
        </div></div>
    <?php else : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6" id="slopeGrid">
        <?php foreach ($slopes as $slope) : ?>
            <?php
                $cond = (int) ($slope['condition_pct'] ?? 100);
                $diff = $slope['difficulty'] ?? '';
                $diffColor = match($diff) { 'green' => 'success', 'blue' => 'info', 'black' => 'neutral', 'double_black' => 'error', default => 'ghost' };
                $sectorCovered = isset($sectors[(int)($slope['sector'] ?? 0)]) && $sectors[(int)($slope['sector'] ?? 0)]['groomers_assigned'] >= $sectors[(int)($slope['sector'] ?? 0)]['groomers_needed'];
            ?>
            <div class="card bg-base-100 shadow-sm <?= $cond < 40 ? 'border border-error/30' : ($cond < 60 ? 'border border-warning/20' : '') ?>" data-condition="<?= $cond ?>" data-name="<?= esc($slope['name']) ?>">
                <div class="card-body p-3">
                    <div class="flex items-center justify-between mb-1">
                        <div class="flex items-center gap-2">
                            <?php if ($diff) : ?>
                            <span class="badge badge-xs badge-<?= $diffColor ?>"><?= ucfirst(str_replace('_', ' ', $diff)) ?></span>
                            <?php endif ?>
                            <span class="font-semibold text-sm truncate"><?= esc($slope['name']) ?></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <?php if ($cond < 40) : ?><span class="badge badge-xs badge-error gap-1"><i class="fa-solid fa-triangle-exclamation text-[8px]"></i>Critical</span>
                            <?php elseif ($cond < 60) : ?><span class="badge badge-xs badge-warning">Low</span>
                            <?php endif ?>
                            <span class="badge badge-xs <?= ($slope['status'] ?? 'open') === 'open' ? 'badge-success' : 'badge-error' ?>"><?= ucfirst($slope['status'] ?? 'open') ?></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <progress class="progress <?= $cond >= 70 ? 'progress-success' : ($cond >= 40 ? 'progress-warning' : 'progress-error') ?> flex-1 h-2" value="<?= $cond ?>" max="100"></progress>
                        <span class="text-xs font-mono font-bold w-8 text-right <?= $cond >= 70 ? 'text-success' : ($cond >= 40 ? 'text-warning' : 'text-error') ?>"><?= $cond ?>%</span>
                    </div>
                    <div class="flex items-center justify-between mt-2">
                        <div class="flex items-center gap-2 text-xs text-base-content/50">
                            <span><?= distance($slope['length_meters'] ?? 0) ?></span>
                            <span>·</span>
                            <?php if ($sectorCovered) : ?>
                            <span class="text-success"><i class="fa-solid fa-user-check text-[10px]"></i> Crewed</span>
                            <?php else : ?>
                            <span class="text-warning"><i class="fa-solid fa-user-xmark text-[10px]"></i> No crew</span>
                            <?php endif ?>
                            <span>·</span>
                            <span><?= $cond >= 80 ? '↑ Great' : ($cond >= 50 ? '→ OK' : '↓ Declining') ?></span>
                        </div>
                        <?php if ($cond < 100 && $activeEquipment > 0) : ?>
                        <form action="/grooming/groom-single" method="post" class="inline"><?= csrf_field() ?>
                            <input type="hidden" name="slope_id" value="<?= $slope['id'] ?>">
                            <button class="btn btn-xs btn-ghost text-success gap-1" title="Groom this slope"><i class="fa-solid fa-tractor"></i></button>
                        </form>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Sector Coverage -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-map-pin mr-1"></i> Sector Coverage</h2>
            <?php if (!empty($sectors)) : ?>
            <div class="space-y-2">
            <?php foreach ($sectors as $sectorNum => $sector) : ?>
                <?php $covered = $sector['groomers_assigned'] >= $sector['groomers_needed']; ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg <?= $covered ? 'bg-success/10' : 'bg-error/10' ?> flex items-center justify-center">
                                <i class="fa-solid fa-<?= $covered ? 'circle-check text-success' : 'circle-xmark text-error' ?>"></i>
                            </div>
                            <div>
                                <span class="font-semibold text-sm">Sector <?= $sectorNum ?></span>
                                <div class="text-xs text-base-content/50"><?= count($sector['slopes']) ?> slopes · Avg <?= $sector['avg_condition'] ?>%</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <?php if ($activeEquipment > 0 && $sector['avg_condition'] < 100) : ?>
                            <form action="/grooming/groom-sector" method="post" class="inline"><?= csrf_field() ?>
                                <input type="hidden" name="sector" value="<?= $sectorNum ?>">
                                <button class="btn btn-xs btn-ghost text-success" title="Groom this sector"><i class="fa-solid fa-tractor"></i></button>
                            </form>
                            <?php endif ?>
                            <div class="text-right">
                                <div class="font-mono text-sm <?= $covered ? 'text-success' : 'text-error' ?>"><?= $sector['groomers_assigned'] ?>/<?= $sector['groomers_needed'] ?></div>
                                <div class="text-xs text-base-content/50">crew</div>
                            </div>
                        </div>
                    </div>
                </div></div>
            <?php endforeach ?>
            </div>
            <?php else : ?>
            <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-6 text-sm text-base-content/40"><a href="/map" class="link link-primary">Build slopes</a> to create sectors</div></div>
            <?php endif ?>
        </div>

        <!-- Crew Assignment -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-user-gear mr-1"></i> Crew Assignment</h2>
            <?php if (empty($groomers)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-6">
                    <i class="fa-solid fa-tractor text-3xl text-base-content/20 mb-2"></i>
                    <p class="text-sm text-base-content/50">No groomer operators. <a href="/staff/hire" class="link link-primary">Hire crew</a></p>
                </div></div>
            <?php else : ?>
            <div class="space-y-2">
            <?php foreach ($groomers as $g) : ?>
                <?php $effectiveness = 3 + (int)$g['level']; ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="w-8 h-8 rounded-full bg-success/10 flex items-center justify-center">
                            <i class="fa-solid fa-user text-success text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold truncate"><?= esc($g['name']) ?></div>
                            <div class="flex items-center gap-2 text-xs text-base-content/50">
                                <span class="flex items-center gap-0.5"><?php for($i=0;$i<min($g['level'],5);$i++): ?><i class="fa-solid fa-star text-warning text-[8px]"></i><?php endfor ?></span>
                                <span>Lv.<?= $g['level'] ?></span>
                                <span>·</span>
                                <span>Morale <?= $g['morale'] ?>%</span>
                                <span>·</span>
                                <span class="text-success">+<?= $effectiveness ?>%</span>
                            </div>
                        </div>
                    </div>
                    <form action="/grooming/assign" method="post" class="flex gap-2"><?= csrf_field() ?>
                        <input type="hidden" name="groomer_id" value="<?= $g['id'] ?>">
                        <select name="sector" class="select select-bordered select-xs flex-1">
                            <option value="" <?= !$g['assigned_to'] ? 'selected' : '' ?>>- Unassigned -</option>
                            <?php foreach ($sectors as $sNum => $sec) : ?>
                            <option value="<?= $sNum ?>" <?= $g['assigned_to'] === 'sector_' . $sNum ? 'selected' : '' ?>>Sector <?= $sNum ?> (<?= count($sec['slopes']) ?> slopes, <?= $sec['avg_condition'] ?>%)</option>
                            <?php endforeach ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-check"></i></button>
                    </form>
                </div></div>
            <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>
    </div>

    <!-- Equipment Status -->
    <?php if (!empty($equipment)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-truck-monster mr-1"></i> Grooming Fleet</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($equipment as $eq) : ?>
        <div class="card bg-base-100 shadow-sm <?= $eq['status'] === 'broken' ? 'border border-error/30' : '' ?>"><div class="card-body p-3">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-truck-monster text-base-content/30"></i>
                    <span class="font-semibold text-sm"><?= esc($eq['name']) ?></span>
                </div>
                <span class="badge badge-xs <?= $eq['status'] === 'active' ? 'badge-success' : ($eq['status'] === 'broken' ? 'badge-error' : 'badge-ghost') ?>"><?= ucfirst($eq['status']) ?></span>
            </div>
            <div class="text-xs text-base-content/50 mb-2">
                <?= $eq['brand'] ?> · <?= $eq['capacity'] ?> slopes · <?= currency($eq['daily_cost'] ?? 0) ?>/day fuel
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-base-content/50 w-12">Health</span>
                <progress class="progress <?= $eq['condition_pct'] >= 60 ? 'progress-success' : ($eq['condition_pct'] >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $eq['condition_pct'] ?>" max="100"></progress>
                <span class="text-xs font-mono w-8 text-right"><?= $eq['condition_pct'] ?>%</span>
            </div>
            <?php if ($eq['condition_pct'] < 30) : ?>
            <div class="text-xs text-error mt-1"><i class="fa-solid fa-wrench mr-1"></i>Needs repair soon</div>
            <?php endif ?>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Grooming Economics -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-4">
        <h3 class="text-sm font-bold mb-3"><i class="fa-solid fa-chart-line mr-1"></i> Grooming Economics</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <div class="text-xs text-base-content/50">Daily Fuel Cost</div>
                <div class="text-lg font-bold"><?= currency($dailyFuelCost) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50">Crew Salaries</div>
                <div class="text-lg font-bold"><?= currency($crewSalaryCost) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50">Total Grooming/Day</div>
                <div class="text-lg font-bold text-error"><?= currency($dailyFuelCost + $crewSalaryCost) ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50">Condition Bonus</div>
                <div class="text-lg font-bold text-success"><?= $overallCondition >= 80 ? '+10%' : ($overallCondition >= 60 ? '+5%' : '+0%') ?></div>
                <div class="text-xs text-base-content/40">visitor boost</div>
            </div>
        </div>
    </div></div>

    <!-- How Grooming Works -->
    <div class="collapse collapse-arrow bg-base-100 shadow-sm mb-6">
        <input type="checkbox" />
        <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i> How Grooming Works</div>
        <div class="collapse-content text-sm text-base-content/70">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide mb-2 text-base-content/40">Condition Decay</h4>
                    <ul class="space-y-1">
                        <li><i class="fa-solid fa-person-skiing text-xs mr-2 text-info"></i>Green slopes: -3%/day</li>
                        <li><i class="fa-solid fa-person-skiing text-xs mr-2 text-info"></i>Blue slopes: -5%/day</li>
                        <li><i class="fa-solid fa-person-skiing text-xs mr-2"></i>Black slopes: -7%/day</li>
                        <li><i class="fa-solid fa-person-skiing text-xs mr-2 text-error"></i>Double black: -8%/day</li>
                        <li><i class="fa-solid fa-sun text-xs mr-2 text-warning"></i>Warm weather: extra -2%/day</li>
                        <li><i class="fa-solid fa-cloud-meatball text-xs mr-2 text-info"></i>Fresh snow: +2%/day</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-xs uppercase tracking-wide mb-2 text-base-content/40">Grooming Power</h4>
                    <ul class="space-y-1">
                        <li><i class="fa-solid fa-tractor text-xs mr-2 text-success"></i>Each machine: +5% per run (max +20%)</li>
                        <li><i class="fa-solid fa-user text-xs mr-2 text-info"></i>Crew bonus: +1% per crew level</li>
                        <li><i class="fa-solid fa-crosshairs text-xs mr-2 text-warning"></i>Single slope: +5% bonus over groom-all</li>
                        <li><i class="fa-solid fa-ban text-xs mr-2 text-error"></i>0% condition = slope auto-closes</li>
                        <li><i class="fa-solid fa-star text-xs mr-2 text-warning"></i>80%+ condition = +10% visitor boost</li>
                        <li><i class="fa-solid fa-star text-xs mr-2 text-warning"></i>60%+ condition = +5% visitor boost</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="flex flex-wrap gap-2">
        <a href="/equipment" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-toolbox"></i> Equipment Shop</a>
        <a href="/staff/hire" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-user-plus"></i> Hire Crew</a>
        <a href="/snowmaking" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-snowflake"></i> Snowmaking</a>
        <a href="/map" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-map"></i> Trail Map</a>
    </div>
</div>

<script>
function sortSlopes(by){
    var grid=document.getElementById('slopeGrid');if(!grid)return;
    var cards=[].slice.call(grid.children);
    cards.sort(function(a,b){
        if(by==='condition') return parseInt(a.dataset.condition)-parseInt(b.dataset.condition);
        return (a.dataset.name||'').localeCompare(b.dataset.name||'');
    });
    cards.forEach(function(c){grid.appendChild(c);});
}
</script>
<?= $this->endSection() ?>
