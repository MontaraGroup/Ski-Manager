<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-7xl mx-auto p-4 lg:p-8">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold">Welcome back, <?= auth()->user()->username ?></h1>
            <p class="text-base-content/60 mt-1 text-sm">Day <?= $gameDay ?> - here's your resort at a glance.</p>
        </div>
        <button id="editModeBtn" onclick="toggleEditMode()" class="btn btn-ghost btn-sm gap-1 mt-2 md:mt-0"><i class="fa-solid fa-pen"></i> Edit Layout</button>
    </div>

    <?php
    $seasonLength = 135; $seasonProgress = min($gameDay, $seasonLength); $seasonNum = (int) ceil($gameDay / $seasonLength);
    $wIcons = ['Sunny'=>'fa-sun text-warning','Partly Cloudy'=>'fa-cloud-sun text-info','Cloudy'=>'fa-cloud text-base-content/50','Light Snow'=>'fa-snowflake text-info','Heavy Snow'=>'fa-snowflake text-primary','Blizzard'=>'fa-wind text-error','Freezing Rain'=>'fa-cloud-rain text-error'];
    $totalParkingCap = 0; $totalOccupied = 0;
    foreach ($parkingFacilities as $pf) { if ($pf['status'] === 'open' || $pf['status'] === 'full') { $totalParkingCap += $pf['capacity']; $totalOccupied += $pf['occupied']; } }
    $parkingPct = $totalParkingCap > 0 ? round($totalOccupied / $totalParkingCap * 100) : 0;
    $openParks = array_filter($terrainParks, fn($tp) => $tp['status'] === 'open');
    $buildingParks = array_filter($terrainParks, fn($tp) => $tp['status'] === 'under_construction');
    $totalParkVisitors = array_sum(array_column($terrainParks, 'daily_visitors'));
    $roles = []; foreach ($staffAll as $s) { $roles[$s['role']] = ($roles[$s['role']] ?? 0) + 1; }
    $avgMorale = count($staffAll) > 0 ? round(array_sum(array_column($staffAll, 'morale')) / count($staffAll)) : 0;
    $activeIns = array_filter($insurance, fn($i) => ($i['active'] ?? 0) == 1);
    $forecast = $weather ? json_decode($weather['forecast'] ?? '[]', true) : [];
    ?>

    <div id="widgetContainer" class="widget-grid">
    <?php foreach ($widgets as $w) : ?>
        <?php if (!$w['visible']) continue; ?>
        <?php $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
        <?php $sz = $w['size'] ?? 'large'; ?>

        <div class="widget-item card bg-base-100 shadow-sm widget-<?= $sz ?>" data-key="<?= $w['widget_key'] ?>" data-size="<?= $sz ?>" id="widget-<?= $w['widget_key'] ?>">
            <div class="drag-handle">
                <div class="flex items-center gap-2 text-base-content/50"><i class="fa-solid fa-grip-vertical"></i><span><?= $info['name'] ?></span></div>
                <div class="flex items-center gap-1">
                    <div class="join"><button onclick="resizeWidget('<?= $w['widget_key'] ?>','small')" class="btn btn-xs join-item <?= $sz === 'small' ? 'btn-primary' : 'btn-ghost' ?>">S</button><button onclick="resizeWidget('<?= $w['widget_key'] ?>','medium')" class="btn btn-xs join-item <?= $sz === 'medium' ? 'btn-primary' : 'btn-ghost' ?>">M</button><button onclick="resizeWidget('<?= $w['widget_key'] ?>','large')" class="btn btn-xs join-item <?= $sz === 'large' ? 'btn-primary' : 'btn-ghost' ?>">L</button></div>
                    <button onclick="hideWidget('<?= $w['widget_key'] ?>')" class="btn btn-ghost btn-xs text-error ml-1"><i class="fa-solid fa-eye-slash"></i></button>
                </div>
            </div>
            <div class="widget-content">

        <?php // ===== STATS ===== ?>
        <?php if ($w['widget_key'] === 'stats') : ?>
            <?php if ($sz === 'small') : ?>
                <div class="widget-center">
                    <i class="fa-solid fa-money-bill-wave text-success text-xl mb-1"></i>
                    <div class="text-xl font-bold"><?= currency($finance['cash'] ?? 0) ?></div>
                    <div class="text-xs text-base-content/50 mt-1">Day <?= $gameDay ?> · <?= number_format($dailyVisitors) ?> visitors</div>
                </div>
            <?php elseif ($sz === 'medium') : ?>
                <div class="grid grid-cols-4 gap-2 h-full items-center">
                    <div class="text-center"><div class="text-xs text-base-content/50"><i class="fa-solid fa-money-bill-wave"></i></div><div class="font-bold"><?= currency($finance['cash'] ?? 0) ?></div></div>
                    <div class="text-center"><div class="text-xs text-base-content/50"><i class="fa-solid fa-people-group"></i></div><div class="font-bold"><?= number_format($dailyVisitors) ?></div></div>
                    <div class="text-center"><div class="text-xs text-base-content/50"><i class="fa-solid fa-chart-line"></i></div><div class="font-bold <?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></div></div>
                    <div class="text-center"><div class="text-xs text-base-content/50"><i class="fa-solid fa-star"></i></div><div class="font-bold"><?= $finance['reputation'] ?? 0 ?> rep</div></div>
                </div>
            <?php else : ?>
                <div class="grid grid-cols-4 md:grid-cols-8 gap-3 h-full items-center">
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-money-bill-wave"></i> Cash</div><div class="text-lg font-bold"><?= currency($finance['cash'] ?? 0) ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-people-group"></i> Visitors</div><div class="text-lg font-bold"><?= number_format($dailyVisitors) ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-coins"></i> Income</div><div class="text-lg font-bold text-success"><?= currency($finance['total_income'] ?? 0) ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-receipt"></i> Expenses</div><div class="text-lg font-bold text-error"><?= currency($finance['total_expenses'] ?? 0) ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-chart-line"></i> Net</div><div class="text-lg font-bold <?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-star"></i> Rep</div><div class="text-lg font-bold"><?= $finance['reputation'] ?? 0 ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-calendar-day"></i> Day</div><div class="text-lg font-bold"><?= $gameDay ?></div></div>
                    <div class="text-center p-2 bg-base-200 rounded-lg"><div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-seedling"></i> Genepis</div><div class="text-lg font-bold"><?= $genepis['balance'] ?? 0 ?></div></div>
                </div>
            <?php endif ?>
        <?php endif ?>

        <?php // ===== SEASON ===== ?>
        <?php if ($w['widget_key'] === 'season') : ?>
            <?php if ($sz === 'small') : ?>
                <div class="widget-center">
                    <div class="radial-progress text-primary" style="--value:<?= round($seasonProgress / $seasonLength * 100) ?>;--size:4rem;--thickness:4px;" role="progressbar"><?= round($seasonProgress / $seasonLength * 100) ?>%</div>
                    <div class="text-xs text-base-content/50 mt-2">Season <?= $seasonNum ?></div>
                </div>
            <?php else : ?>
                <div class="flex flex-col justify-center h-full">
                    <div class="flex justify-between text-sm mb-2"><span><i class="fa-solid fa-calendar mr-1"></i>Season <?= $seasonNum ?></span><span class="text-base-content/50">Day <?= $seasonProgress ?> / <?= $seasonLength ?></span></div>
                    <div class="flex gap-2 items-center">
                        <div style="flex:100">
                            <div class="flex justify-between text-xs text-base-content/50 mb-0.5"><span><i class="fa-solid fa-snowflake mr-1"></i>Winter</span><span><?= min($seasonProgress, 100) ?>/100</span></div>
                            <progress class="progress progress-info w-full" value="<?= min($seasonProgress, 100) ?>" max="100"></progress>
                        </div>
                        <div style="flex:35">
                            <div class="flex justify-between text-xs text-base-content/50 mb-0.5"><span><i class="fa-solid fa-sun mr-1"></i>Summer</span><span><?= max(0, $seasonProgress - 100) ?>/35</span></div>
                            <progress class="progress progress-warning w-full" value="<?= max(0, $seasonProgress - 100) ?>" max="35"></progress>
                        </div>
                    </div>
                </div>
            <?php endif ?>
        <?php endif ?>

        <?php // ===== WEATHER ===== ?>
        <?php if ($w['widget_key'] === 'weather_mini' && $weather) : ?>
            <?php if ($sz === 'small') : ?>
                <div class="widget-center">
                    <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-3xl"></i>
                    <div class="text-xl font-bold mt-1"><?= temp((int)$weather['temp']) ?></div>
                    <div class="text-xs text-base-content/50"><?= $weather['condition_name'] ?></div>
                </div>
            <?php elseif ($sz === 'medium') : ?>
                <div class="flex items-center justify-between h-full">
                    <div class="flex items-center gap-3"><i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-3xl"></i><div><div class="text-xl font-bold"><?= temp((int)$weather['temp']) ?></div><div class="text-xs text-base-content/50"><?= $weather['condition_name'] ?> · <?= speed((int)$weather['wind']) ?> wind</div></div></div>
                    <?php if (!empty($forecast)) : ?>
                    <div class="flex gap-2">
                        <?php foreach (array_slice($forecast, 0, 3) as $fc) : ?>
                        <div class="text-center"><i class="fa-solid <?= $wIcons[$fc['condition']] ?? 'fa-cloud' ?> text-xs"></i><div class="text-xs font-bold"><?= temp($fc['temp']) ?></div></div>
                        <?php endforeach ?>
                    </div>
                    <?php endif ?>
                </div>
            <?php else : ?>
                <div class="flex items-center justify-between h-full">
                    <div class="flex items-center gap-4"><i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-4xl"></i><div><div class="text-2xl font-bold"><?= temp((int)$weather['temp']) ?></div><div class="text-sm text-base-content/50"><?= $weather['condition_name'] ?> · <?= speed((int)$weather['wind']) ?> wind · <?= snow($weather['snowfall'] ?? 0) ?> snow · Base: <?= snow($weather['snow_base'] ?? 0) ?></div></div></div>
                    <?php if (!empty($forecast)) : ?>
                    <div class="flex gap-3 items-end">
                        <?php foreach ($forecast as $i => $fc) : ?>
                        <div class="text-center">
                            <div class="text-[10px] text-base-content/40 mb-1">+<?= $i + 1 ?>d</div>
                            <i class="fa-solid <?= $wIcons[$fc['condition']] ?? 'fa-cloud' ?> text-sm"></i>
                            <div class="text-xs font-bold mt-0.5"><?= temp($fc['temp']) ?></div>
                            <?php if (($fc['snowfall'] ?? 0) > 0) : ?><div class="text-[10px] text-info"><i class="fa-solid fa-snowflake"></i> <?= $fc['snowfall'] ?></div><?php endif ?>
                        </div>
                        <?php endforeach ?>
                    </div>
                    <?php endif ?>
                    <a href="/weather" class="btn btn-ghost btn-sm">Details <i class="fa-solid fa-chevron-right ml-1"></i></a>
                </div>
            <?php endif ?>
        <?php endif ?>

        <?php // ===== ACHIEVEMENT ALERTS ===== ?>
        <?php if ($w['widget_key'] === 'achievements_mini') : ?>
            <?php if ($unclaimedAchievements > 0) : ?>
                <div class="widget-center"><i class="fa-solid fa-award text-warning text-2xl"></i><div class="font-bold mt-1"><?= $unclaimedAchievements ?></div><div class="text-xs text-base-content/50">unclaimed</div><a href="/achievements" class="btn btn-warning btn-xs mt-2">Claim</a></div>
            <?php elseif (!empty($inProgressAchievements)) : ?>
                <div class="flex flex-col h-full">
                    <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-bell mr-1 text-base-content/40"></i>Achievements</span><span class="badge badge-ghost badge-sm">All claimed</span></div>
                    <div class="text-xs text-base-content/50 mb-2">Closest to completion:</div>
                    <div class="space-y-2 flex-1 overflow-y-auto"><?php foreach (array_slice($inProgressAchievements, 0, 3) as $ach) : ?><?php $pct = $ach['target'] > 0 ? round($ach['progress'] / $ach['target'] * 100) : 0; ?><div class="flex items-center gap-2"><i class="<?= $ach['icon'] ?> text-base-content/30 w-4 text-center text-xs"></i><div class="flex-1 min-w-0"><div class="text-xs font-semibold truncate"><?= esc($ach['name']) ?></div><progress class="progress progress-primary w-full" value="<?= $ach['progress'] ?>" max="<?= $ach['target'] ?>"></progress></div><span class="text-xs font-mono text-base-content/50"><?= $pct ?>%</span></div><?php endforeach ?></div>
                </div>
            <?php else : ?>
                <div class="widget-center"><i class="fa-solid fa-trophy text-success text-2xl"></i><div class="text-sm font-semibold mt-2">All caught up!</div><div class="text-xs text-base-content/50">No achievements to claim</div></div>
            <?php endif ?>
        <?php endif ?>

        <?php // ===== QUICK ACTIONS ===== ?>
        <?php if ($w['widget_key'] === 'actions') : ?>
            <div class="flex flex-col h-full">
                <h2 class="text-sm font-semibold mb-2"><i class="fa-solid fa-bolt mr-1 text-warning"></i>Quick Actions</h2>
                <div class="grid grid-cols-<?= $sz === 'small' ? '2' : '4' ?> gap-<?= $sz === 'small' ? '1' : '2' ?> flex-1 content-start">
                    <a href="/resort" class="btn btn-primary <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-mountain-sun"></i><?= $sz !== 'small' ? 'Resort' : '' ?></a>
                    <a href="/map" class="btn btn-outline <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-map"></i><?= $sz !== 'small' ? 'Map' : '' ?></a>
                    <a href="/finances" class="btn btn-outline <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-coins"></i><?= $sz !== 'small' ? 'Finances' : '' ?></a>
                    <a href="/staff" class="btn btn-outline <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-users"></i><?= $sz !== 'small' ? 'Staff' : '' ?></a>
                    <a href="/weather" class="btn btn-outline <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-cloud-sun"></i><?= $sz !== 'small' ? 'Weather' : '' ?></a>
                    <a href="/terrain-parks" class="btn btn-outline <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-person-snowboarding"></i><?= $sz !== 'small' ? 'Parks' : '' ?></a>
                    <a href="/parking" class="btn btn-outline <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-square-parking"></i><?= $sz !== 'small' ? 'Parking' : '' ?></a>
                    <a href="/daily-bonus" class="btn <?= $bonusAvailable ? 'btn-warning' : 'btn-outline' ?> <?= $sz === 'small' ? 'btn-xs' : 'btn-sm' ?> gap-1"><i class="fa-solid fa-gift"></i><?= $sz !== 'small' ? ($bonusAvailable ? 'Bonus!' : 'Bonus') : '' ?></a>
                </div>
            </div>
        <?php endif ?>

        <?php // ===== OVERVIEW ===== ?>
        <?php if ($w['widget_key'] === 'overview') : ?>
            <div class="flex flex-col h-full">
                <h2 class="text-sm font-semibold mb-2"><i class="fa-solid fa-mountain-sun mr-1 text-primary"></i>Resort Overview</h2>
                <div class="grid grid-cols-2 <?= $sz === 'large' ? 'md:grid-cols-4' : '' ?> gap-2 flex-1 content-start">
                    <div class="text-center p-<?= $sz === 'small' ? '2' : '3' ?> bg-base-200 rounded-lg"><div class="<?= $sz === 'small' ? 'text-lg' : 'text-2xl' ?> font-bold text-info"><?= $slopeCount ?></div><div class="text-xs text-base-content/50">Slopes</div></div>
                    <div class="text-center p-<?= $sz === 'small' ? '2' : '3' ?> bg-base-200 rounded-lg"><div class="<?= $sz === 'small' ? 'text-lg' : 'text-2xl' ?> font-bold text-success"><?= $liftCount ?></div><div class="text-xs text-base-content/50">Lifts</div></div>
                    <div class="text-center p-<?= $sz === 'small' ? '2' : '3' ?> bg-base-200 rounded-lg"><div class="<?= $sz === 'small' ? 'text-lg' : 'text-2xl' ?> font-bold text-warning"><?= $staffCount ?></div><div class="text-xs text-base-content/50">Staff</div></div>
                    <div class="text-center p-<?= $sz === 'small' ? '2' : '3' ?> bg-base-200 rounded-lg"><div class="<?= $sz === 'small' ? 'text-lg' : 'text-2xl' ?> font-bold text-primary"><?= $buildingCount ?></div><div class="text-xs text-base-content/50">Buildings</div></div>
                </div>
            </div>
        <?php endif ?>

        <?php // ===== PARKING ===== ?>
        <?php if ($w['widget_key'] === 'parking_mini') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-square-parking mr-1 text-info"></i>Parking</span><a href="/parking" class="link link-primary text-xs">Manage</a></div>
                <div class="grid grid-cols-3 gap-2 text-center flex-1 content-center">
                    <div><div class="<?= $sz === 'small' ? 'text-lg' : 'text-xl' ?> font-bold"><?= count($parkingFacilities) ?></div><div class="text-xs text-base-content/50">Lots</div></div>
                    <div><div class="<?= $sz === 'small' ? 'text-lg' : 'text-xl' ?> font-bold"><?= number_format($totalParkingCap) ?></div><div class="text-xs text-base-content/50">Spots</div></div>
                    <div><div class="<?= $sz === 'small' ? 'text-lg' : 'text-xl' ?> font-bold <?= $parkingPct > 90 ? 'text-error' : ($parkingPct > 70 ? 'text-warning' : 'text-success') ?>"><?= $parkingPct ?>%</div><div class="text-xs text-base-content/50">Full</div></div>
                </div>
                <progress class="progress progress-info w-full mt-auto" value="<?= $totalOccupied ?>" max="<?= max(1, $totalParkingCap) ?>"></progress>
            </div>
        <?php endif ?>

        <?php // ===== TERRAIN PARKS ===== ?>
        <?php if ($w['widget_key'] === 'terrain_parks_mini') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-person-snowboarding mr-1 text-warning"></i>Terrain Parks</span><a href="/terrain-parks" class="link link-primary text-xs">Manage</a></div>
                <div class="grid grid-cols-3 gap-2 text-center flex-1 content-center">
                    <div><div class="<?= $sz === 'small' ? 'text-lg' : 'text-xl' ?> font-bold text-success"><?= count($openParks) ?></div><div class="text-xs text-base-content/50">Open</div></div>
                    <div><div class="<?= $sz === 'small' ? 'text-lg' : 'text-xl' ?> font-bold text-warning"><?= count($buildingParks) ?></div><div class="text-xs text-base-content/50">Building</div></div>
                    <div><div class="<?= $sz === 'small' ? 'text-lg' : 'text-xl' ?> font-bold text-info"><?= $totalParkVisitors ?></div><div class="text-xs text-base-content/50">Riders</div></div>
                </div>
            </div>
        <?php endif ?>

        <?php // ===== FINANCES ===== ?>
        <?php if ($w['widget_key'] === 'finances_mini' && $finance) : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-coins mr-1 text-warning"></i>Finances</span><a href="/finances" class="link link-primary text-xs">Details</a></div>
                <div class="grid grid-cols-3 gap-2 text-center flex-1 content-center">
                    <div><div class="font-bold text-success"><?= currency($finance['total_income'] ?? 0) ?></div><div class="text-xs text-base-content/50">Income</div></div>
                    <div><div class="font-bold text-error"><?= currency($finance['total_expenses'] ?? 0) ?></div><div class="text-xs text-base-content/50">Expenses</div></div>
                    <div><div class="font-bold <?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></div><div class="text-xs text-base-content/50">Net</div></div>
                </div>
            </div>
        <?php endif ?>

        <?php // ===== STAFF ===== ?>
        <?php if ($w['widget_key'] === 'staff_mini') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-users mr-1 text-info"></i>Staff</span><a href="/staff" class="link link-primary text-xs">Manage</a></div>
                <?php if (empty($staffAll)) : ?><div class="flex-1 flex items-center justify-center text-sm text-base-content/40">No staff</div>
                <?php else : ?>
                <div class="grid grid-cols-2 gap-1 text-sm"><?php foreach ($roles as $role => $count) : ?><div class="flex justify-between"><span class="text-base-content/60 truncate"><?= ucfirst(str_replace('_', ' ', $role)) ?></span><span class="font-semibold"><?= $count ?></span></div><?php endforeach ?></div>
                <div class="flex items-center gap-2 mt-auto pt-2 text-sm"><span class="text-base-content/60">Morale</span><progress class="progress <?= $avgMorale >= 60 ? 'progress-success' : ($avgMorale >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1" value="<?= $avgMorale ?>" max="100"></progress><span class="text-xs font-mono"><?= $avgMorale ?>%</span></div>
                <?php endif ?>
            </div>
        <?php endif ?>

        <?php // ===== EQUIPMENT ===== ?>
        <?php if ($w['widget_key'] === 'equipment_mini') : ?>
            <div class="widget-center">
                <i class="fa-solid fa-toolbox text-warning text-xl"></i>
                <div class="text-2xl font-bold mt-1"><?= count($equipment) ?></div>
                <div class="text-xs text-base-content/50">items owned</div>
                <a href="/equipment" class="link link-primary text-xs mt-2">Shop</a>
            </div>
        <?php endif ?>

        <?php // ===== INSURANCE ===== ?>
        <?php if ($w['widget_key'] === 'insurance_mini') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-shield-halved mr-1 text-success"></i>Insurance</span><a href="/insurance" class="link link-primary text-xs">Manage</a></div>
                <div class="grid grid-cols-2 gap-2 text-center flex-1 content-center">
                    <div><div class="text-xl font-bold text-success"><?= count($activeIns) ?></div><div class="text-xs text-base-content/50">Active</div></div>
                    <div><div class="text-xl font-bold"><?= count($insurance) - count($activeIns) ?></div><div class="text-xs text-base-content/50">Available</div></div>
                </div>
            </div>
        <?php endif ?>

        <?php // ===== LOANS ===== ?>
        <?php if ($w['widget_key'] === 'loans_mini') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-landmark mr-1 text-primary"></i>Loans</span><a href="/bank" class="link link-primary text-xs">Bank</a></div>
                <?php if (empty($loans)) : ?><div class="flex-1 flex items-center justify-center text-sm text-base-content/50">No active loans</div>
                <?php else : ?><div class="flex-1 flex flex-col items-center justify-center"><div class="text-2xl font-bold text-error"><?= currency(array_sum(array_column($loans, 'remaining'))) ?></div><div class="text-xs text-base-content/50"><?= count($loans) ?> active</div></div><?php endif ?>
            </div>
        <?php endif ?>

        <?php // ===== MARKETING ===== ?>
        <?php if ($w['widget_key'] === 'marketing_mini') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-bullhorn mr-1 text-secondary"></i>Marketing</span><a href="/marketing" class="link link-primary text-xs">Manage</a></div>
                <?php if (empty($marketing)) : ?><div class="flex-1 flex items-center justify-center text-sm text-base-content/50">No campaigns</div>
                <?php else : ?><div class="flex-1 flex flex-col items-center justify-center"><div class="text-2xl font-bold text-success"><?= count($marketing) ?></div><div class="text-xs text-base-content/50">active campaign<?= count($marketing) > 1 ? 's' : '' ?></div></div><?php endif ?>
            </div>
        <?php endif ?>

        <?php // ===== ACHIEVEMENTS ===== ?>
        <?php if ($w['widget_key'] === 'achievements' || $w['widget_key'] === 'getting_started') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-award mr-1 text-warning"></i>Achievements</span><a href="/achievements" class="link link-primary text-xs">View All</a></div>
                <?php if (empty($inProgressAchievements)) : ?><div class="flex-1 flex items-center justify-center text-sm text-base-content/40">No achievements in progress</div>
                <?php else : ?><div class="space-y-2 flex-1 overflow-y-auto"><?php foreach ($inProgressAchievements as $ach) : ?><div class="flex items-center gap-2"><i class="<?= $ach['icon'] ?> text-base-content/30 w-4 text-center text-xs"></i><div class="flex-1 min-w-0"><div class="text-xs font-semibold truncate"><?= esc($ach['name']) ?></div><progress class="progress progress-primary w-full" value="<?= $ach['progress'] ?>" max="<?= $ach['target'] ?>"></progress></div><span class="text-xs font-mono text-base-content/50"><?= $ach['progress'] ?>/<?= $ach['target'] ?></span></div><?php endforeach ?></div><?php endif ?>
            </div>
        <?php endif ?>

        <?php // ===== ACTIVITY ===== ?>
        <?php if ($w['widget_key'] === 'activity') : ?>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between mb-2"><span class="text-sm font-semibold"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Activity</span><a href="/activity" class="link link-primary text-xs">View All</a></div>
                <?php if (empty($recentActivity)) : ?><div class="flex-1 flex items-center justify-center text-sm text-base-content/40">No activity</div>
                <?php else : ?><div class="space-y-1 flex-1 overflow-y-auto"><?php foreach ($recentActivity as $log) : ?><div class="flex items-center gap-2 py-0.5"><i class="<?= $log['icon'] ?? 'fa-solid fa-circle' ?> text-xs text-base-content/50 w-3 text-center"></i><span class="flex-1 text-xs truncate"><?= esc($log['message']) ?></span><span class="text-xs text-base-content/40">D<?= $log['game_day'] ?? '' ?></span></div><?php endforeach ?></div><?php endif ?>
            </div>
        <?php endif ?>

            </div><!-- /widget-content -->
        </div><!-- /widget-item -->
    <?php endforeach ?>
    </div>

    <div id="hiddenWidgets" class="hidden mt-6">
        <h3 class="text-sm font-semibold text-base-content/50 mb-3"><i class="fa-solid fa-eye-slash mr-1"></i> Hidden Widgets</h3>
        <div class="flex flex-wrap gap-2" id="hiddenWidgetList">
        <?php foreach ($widgets as $w) : ?>
            <?php if ($w['visible']) continue; $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
            <button onclick="showWidget('<?= $w['widget_key'] ?>')" class="btn btn-outline btn-sm gap-1 hidden-widget-btn" data-key="<?= $w['widget_key'] ?>"><i class="<?= $info['icon'] ?> text-xs"></i> <?= $info['name'] ?> <i class="fa-solid fa-plus text-xs text-success"></i></button>
        <?php endforeach ?>
        </div>
    </div>
</div>

<style>
.widget-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; }
@media (max-width: 768px) { .widget-grid { grid-template-columns: repeat(2, 1fr); } }
.widget-small { grid-column: span 1; grid-row: span 1; }
.widget-medium { grid-column: span 2; grid-row: span 1; }
.widget-large { grid-column: span 4; grid-row: span 1; }
@media (max-width: 768px) {
    .widget-small { grid-column: span 1; }
    .widget-medium { grid-column: span 2; }
    .widget-large { grid-column: span 2; }
}
.widget-item { overflow: hidden; }
.widget-content { padding: 0.75rem; flex: 1; display: flex; flex-direction: column; min-height: 0; }
.widget-small .widget-content { padding: 0.625rem; }
.widget-center { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; }
.drag-handle { display: none; align-items: center; justify-content: space-between; padding: 0.25rem 0.75rem; font-size: 0.75rem; cursor: grab; user-select: none; background: rgba(128,128,128,0.1); }
.drag-handle:active { cursor: grabbing; }
.edit-mode .drag-handle { display: flex !important; }
.edit-mode .widget-item { outline: 2px dashed rgba(128,128,128,0.15); }
.edit-mode .widget-item:hover { outline-color: rgba(59,130,246,0.3); }
.widget-item.dragging { opacity: 0.3; }
.widget-item.drag-over { outline-color: rgba(59,130,246,1) !important; outline-width: 3px; }
</style>

<script>
const csrfName='<?= csrf_token() ?>',csrfHash='<?= csrf_hash() ?>',container=document.getElementById('widgetContainer'),editBtn=document.getElementById('editModeBtn'),hiddenSection=document.getElementById('hiddenWidgets');
let editMode=false,dragEl=null;
function toggleEditMode(){editMode=!editMode;container.classList.toggle('edit-mode',editMode);hiddenSection.classList.toggle('hidden',!editMode);editBtn.innerHTML=editMode?'<i class="fa-solid fa-check"></i> Done':'<i class="fa-solid fa-pen"></i> Edit Layout';editBtn.classList.toggle('btn-primary',editMode);editBtn.classList.toggle('btn-ghost',!editMode);container.querySelectorAll('.widget-item').forEach(el=>el.setAttribute('draggable',editMode?'true':'false'));}
function ajaxPost(url,data){return fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({...data,[csrfName]:csrfHash})}).then(r=>r.json());}
function hideWidget(key){const el=document.getElementById('widget-'+key);if(el){el.style.transition='opacity .2s';el.style.opacity='0';setTimeout(()=>el.remove(),200);}ajaxPost('/dashboard/toggle-widget',{widget_key:key});const list=document.getElementById('hiddenWidgetList'),btn=document.createElement('button');btn.className='btn btn-outline btn-sm gap-1 hidden-widget-btn';btn.dataset.key=key;btn.innerHTML='<i class="fa-solid fa-plus text-xs text-success"></i> '+key.replace(/_/g,' ');btn.onclick=()=>showWidget(key);list.appendChild(btn);}
function showWidget(key){ajaxPost('/dashboard/toggle-widget',{widget_key:key}).then(()=>{sessionStorage.setItem('editMode','1');location.reload();});}
function resizeWidget(key,size){sessionStorage.setItem('editMode','1');ajaxPost('/dashboard/resize-widget',{widget_key:key,size:size}).then(()=>location.reload());}
function saveOrder(){const order=[...container.querySelectorAll('.widget-item')].map(el=>el.dataset.key);ajaxPost('/dashboard/reorder-widgets',{order});}
container.addEventListener('dragstart',e=>{if(!editMode)return;dragEl=e.target.closest('.widget-item');if(dragEl){dragEl.classList.add('dragging');e.dataTransfer.effectAllowed='move';e.dataTransfer.setData('text/plain','');}});
container.addEventListener('dragend',()=>{if(dragEl)dragEl.classList.remove('dragging');container.querySelectorAll('.widget-item').forEach(el=>el.classList.remove('drag-over'));if(dragEl)saveOrder();dragEl=null;});
container.addEventListener('dragover',e=>{if(!editMode||!dragEl)return;e.preventDefault();const t=e.target.closest('.widget-item');container.querySelectorAll('.widget-item').forEach(el=>el.classList.remove('drag-over'));if(t&&t!==dragEl)t.classList.add('drag-over');});
container.addEventListener('drop',e=>{if(!editMode||!dragEl)return;e.preventDefault();const t=e.target.closest('.widget-item');if(t&&t!==dragEl){const items=[...container.querySelectorAll('.widget-item')];if(items.indexOf(dragEl)<items.indexOf(t))container.insertBefore(dragEl,t.nextSibling);else container.insertBefore(dragEl,t);}});
if(sessionStorage.getItem('editMode')){sessionStorage.removeItem('editMode');toggleEditMode();}
</script>
<?= $this->endSection() ?>
