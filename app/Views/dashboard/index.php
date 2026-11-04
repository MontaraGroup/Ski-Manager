<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
$seasonLength   = max(1, (int) getSeasonLength());
$seasonProgress = min($gameDay, $seasonLength);
// Game days restart at 1 each season, so the season number can't be derived from the day
$seasonNum      = (int) getSeasonNumber();
$seasonPct      = (int) round($seasonProgress / $seasonLength * 100);
$resortOpen     = (bool) ($finance['resort_open'] ?? 1);
$cashBalance    = (int) ($finance['cash'] ?? 0);
$repScore       = (int) ($finance['reputation'] ?? 0);
$genepisBal     = (int) ($genepis['balance'] ?? 0);

$wIcons = [
    'Sunny'         => 'fa-sun text-warning',
    'Partly Cloudy' => 'fa-cloud-sun text-info',
    'Cloudy'        => 'fa-cloud text-base-content/50',
    'Light Snow'    => 'fa-snowflake text-info',
    'Heavy Snow'    => 'fa-snowflake text-primary',
    'Blizzard'      => 'fa-wind text-error',
    'Freezing Rain' => 'fa-cloud-rain text-error',
    'Rain' => 'fa-cloud-showers-heavy text-info',
];

$totalParkingCap = 0;
$totalOccupied   = 0;
foreach ($parkingFacilities as $pf) {
    if ($pf['status'] === 'open' || $pf['status'] === 'full') {
        $totalParkingCap += (int) $pf['capacity'];
        $totalOccupied   += (int) $pf['occupied'];
    }
}
$parkingPct = $totalParkingCap > 0 ? (int) round($totalOccupied / $totalParkingCap * 100) : 0;

$openParks         = array_filter($terrainParks, fn($tp) => $tp['status'] === 'open');
$buildingParks     = array_filter($terrainParks, fn($tp) => $tp['status'] === 'under_construction');
$totalParkVisitors = array_sum(array_column($terrainParks, 'daily_visitors'));

$roles = [];
foreach ($staffAll as $s) {
    $roles[$s['role']] = ($roles[$s['role']] ?? 0) + 1;
}
$avgMorale = count($staffAll) > 0 ? (int) round(array_sum(array_column($staffAll, 'morale')) / count($staffAll)) : 0;
$activeIns = array_filter($insurance, fn($i) => ($i['active'] ?? 0) == 1);
$forecast  = $weather ? (is_array($weather['forecast'] ?? null) ? $weather['forecast'] : (json_decode($weather['forecast'] ?? '[]', true) ?? [])) : [];

$cf = $cashflow ?? [
    'ticket_price'    => 0,
    'ticket_income'   => 0,
    'building_income' => 0,
    'total_income'    => 0,
    'salaries'        => 0,
    'equipment_cost'  => 0,
    'marketing_cost'  => 0,
    'loan_payments'   => 0,
    'building_upkeep' => 0,
    'insurance_cost'  => 0,
    'running_costs'   => 0,
    'total_expenses'  => 0,
    'daily_profit'    => $dailyProfit ?? 0,
];
// Signed money without a stray "−0" / "+0"
$__plus  = fn(int $v) => $v > 0 ? '+' . currency($v) : currency(0);
$__minus = fn(int $v) => $v > 0 ? '−' . currency($v) : currency(0);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<style>
.dash { max-width: 1240px; margin: 0 auto; padding: 1.25rem 1rem 3rem; }
@media (min-width: 1024px) { .dash { padding: 1.75rem 2rem 3rem; } }

/* Header: the player's own trail map behind the resort name */
.dash-hero {
    position: relative; overflow: hidden; isolation: isolate;
    display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem;
    min-height: 168px; padding: 1.25rem 1.5rem; margin-bottom: 1.25rem;
    border-radius: 10px; background: #1a222c; color: #fff;
}
.dash-hero::before {
    content: ''; position: absolute; inset: 0; z-index: -2;
    background-image: var(--map); background-size: cover; background-position: center 40%;
    opacity: 0.7; transform: scale(1.04);
}
.dash-hero::after {
    content: ''; position: absolute; inset: 0; z-index: -1;
    background: linear-gradient(180deg, rgba(10,14,20,0.15) 0%, rgba(10,14,20,0.55) 45%, rgba(10,14,20,0.88) 100%);
}
.dash-hero h1 { font-family: var(--sign); font-size: 2rem; font-weight: 700; line-height: 1.1; letter-spacing: -0.025em; margin: 0.35rem 0 0.4rem; text-shadow: 0 1px 2px rgba(0,0,0,0.4); }
.hero-kicker { display: flex; align-items: center; gap: 0.6rem; font-size: 0.8125rem; color: rgba(255,255,255,0.8); }
.lift-status {
    font-family: var(--sign); font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    padding: 1px 8px 0; border-radius: 3px; line-height: 1.5;
}
.lift-status.open { background: #23a55a; color: #fff; }
.lift-status.closed { background: #d64545; color: #fff; }
.hero-meta { font-size: 0.875rem; color: rgba(255,255,255,0.78); }
.hero-meta b { color: #fff; font-weight: 600; }
.hero-meta .sep { margin: 0 0.5em; opacity: 0.5; }
.dash-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.hero-btn { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.28); color: #fff; backdrop-filter: blur(4px); }
.hero-btn:hover { background: rgba(255,255,255,0.22); border-color: rgba(255,255,255,0.4); color: #fff; }
.hero-btn[aria-pressed="true"] { background: var(--color-primary); border-color: var(--color-primary); color: var(--color-primary-content); }

.num { font-variant-numeric: tabular-nums; }
.muted { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }

/* Headline numbers */
.kpis {
    display: grid; grid-template-columns: repeat(2, 1fr);
    background: var(--color-base-100); border: 1px solid var(--color-base-300);
    border-radius: 10px; margin-bottom: 1.25rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
@media (min-width: 1024px) { .kpis { grid-template-columns: repeat(4, 1fr); } }
.kpi { padding: 0.95rem 1.25rem 1.05rem; min-width: 0; }
.kpi:nth-child(even) { border-left: 1px solid var(--color-base-300); }
.kpi:nth-child(n+3) { border-top: 1px solid var(--color-base-300); }
@media (min-width: 1024px) {
    .kpi:nth-child(n+3) { border-top: 0; }
    .kpi:nth-child(n+2) { border-left: 1px solid var(--color-base-300); }
}
.kpi-label { font-size: 0.75rem; font-weight: 500; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); display: flex; align-items: center; gap: 0.4rem; }
.kpi-value { font-family: var(--sign); font-size: 1.625rem; font-weight: 600; letter-spacing: -0.025em; line-height: 1.2; margin-top: 0.1rem; font-variant-numeric: tabular-nums; }
.kpi-value small { font-family: inherit; font-size: 1rem; font-weight: 500; opacity: 0.55; margin-left: 2px; }
.kpi-sub { font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); margin-top: 0.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.split { display: flex; height: 4px; border-radius: 2px; overflow: hidden; margin-top: 0.45rem; background: var(--color-base-300); }
.split > span:first-child { background: var(--color-success); }
.split > span:last-child { background: var(--color-error); opacity: 0.8; }

/* Customize banner */
.edit-mode-banner {
    display: none; align-items: center; justify-content: space-between; gap: 12px;
    padding: 0.6rem 0.9rem; margin-bottom: 1rem; border-radius: 8px; font-size: 0.8125rem;
    background: color-mix(in oklab, var(--color-primary) 10%, transparent);
    border: 1px solid color-mix(in oklab, var(--color-primary) 35%, transparent);
}
.edit-mode-active .edit-mode-banner { display: flex; }

/* Widget grid */
.widget-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; grid-auto-flow: row dense; }
@media (max-width: 1024px) { .widget-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .widget-grid { grid-template-columns: 1fr; } }
.widget-small { grid-column: span 1; }
.widget-medium { grid-column: span 2; }
.widget-large { grid-column: span 4; }
@media (max-width: 1024px) { .widget-large { grid-column: span 2; } }
@media (max-width: 640px) { .widget-small, .widget-medium, .widget-large { grid-column: span 1; } }

.widget-item {
    container-type: inline-size;
    background: var(--color-base-100); border: 1px solid var(--color-base-300);
    border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
.widget-grid > .widget-item:nth-child(n+5) { content-visibility: auto; contain-intrinsic-size: auto none auto 180px; }

/* Size-aware layouts */
.cq-compact { display: block; }
.cq-expanded { display: none; }
.cq-large-only { display: none; }
.cq-stats-grid, .cq-overview-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem 1.25rem; }
.cq-actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0 1rem; }
.cq-ach-mini-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; }

@media (min-width: 640px) {
    .widget-medium .cq-compact, .widget-large .cq-compact { display: none; }
    .widget-medium .cq-expanded, .widget-large .cq-expanded { display: block; }
    .widget-large .cq-large-only { display: block; }
    .widget-medium .cq-stats-grid { grid-template-columns: repeat(4, 1fr); }
    .widget-large .cq-stats-grid { grid-template-columns: repeat(6, 1fr); }
    .widget-large .cq-overview-grid { grid-template-columns: repeat(5, 1fr); }
    .widget-medium .cq-actions-grid { grid-template-columns: repeat(3, 1fr); }
    .widget-large .cq-actions-grid { grid-template-columns: repeat(6, 1fr); }
    .widget-medium .cq-ach-mini-grid, .widget-large .cq-ach-mini-grid { grid-template-columns: repeat(3, 1fr); }
}
@supports (container-type: inline-size) {
    @container (min-width: 360px) { .cq-compact { display: none; } .cq-expanded { display: block; } }
    @container (min-width: 440px) { .cq-actions-grid { grid-template-columns: repeat(3, 1fr); } .cq-ach-mini-grid { grid-template-columns: repeat(3, 1fr); } }
    @container (min-width: 480px) { .cq-stats-grid { grid-template-columns: repeat(4, 1fr); } }
    @container (min-width: 720px) {
        .cq-large-only { display: block; }
        .cq-stats-grid { grid-template-columns: repeat(6, 1fr); }
        .cq-overview-grid { grid-template-columns: repeat(5, 1fr); }
        .cq-actions-grid { grid-template-columns: repeat(6, 1fr); }
    }
}

.widget-content { padding: 0.9rem 1.25rem 1.1rem; flex: 1; display: flex; flex-direction: column; min-height: 0; }
.widget-header {
    display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem;
    margin: 0 -1.25rem 0.9rem; padding: 0 1.25rem 0.7rem; border-bottom: 1px solid var(--color-base-300);
}
.widget-title { font-family: var(--sign); font-size: 1rem; font-weight: 600; line-height: 1.2; margin: 0; }
.widget-title .aside { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 0.75rem; font-weight: 400; margin-left: 0.5em; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.widget-links { display: flex; gap: 0.875rem; }
.widget-link { font-size: 0.75rem; font-weight: 500; white-space: nowrap; text-decoration: none; color: var(--color-primary); }
.widget-link:hover { text-decoration: underline; text-underline-offset: 3px; }
.widget-center { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; padding: 0.5rem 0; }

/* Label-over-number stats */
.stat { display: block; min-width: 0; color: inherit; text-decoration: none; }
a.stat:hover .stat-value { color: var(--color-primary); }
.stat-label { font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.stat-value { font-family: var(--sign); font-size: 1.3125rem; font-weight: 600; letter-spacing: -0.02em; line-height: 1.2; margin-top: 0.05rem; font-variant-numeric: tabular-nums; white-space: nowrap; transition: color .15s; }
.stat-value small { font-size: 1rem; font-weight: 500; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.stat-note { font-size: 0.6875rem; margin-top: 0.25rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }

/* Trail difficulty signs */
.trails { display: flex; flex-wrap: wrap; gap: 0.2rem 0.7rem; margin-top: 0.35rem; font-size: 0.75rem; font-variant-numeric: tabular-nums; }
.trails > span { display: inline-flex; align-items: center; gap: 0.3rem; }
.tsign { display: inline-block; width: 10px; height: 10px; flex-shrink: 0; }
.tsign.green { border-radius: 50%; background: #23a55a; }
.tsign.blue { background: #2f6fd6; }
.tsign.black { width: 9px; height: 9px; background: var(--color-base-content); transform: rotate(45deg); margin: 0 1px; }

/* List rows */
.dash-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.5rem 0; font-size: 0.8125rem; border-top: 1px solid var(--color-base-300); }
.dash-row:first-child { border-top: 0; padding-top: 0; }
.dash-row .day { font-size: 0.6875rem; font-variant-numeric: tabular-nums; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.dash-row > i { width: 1rem; text-align: center; color: color-mix(in oklab, var(--color-primary) 80%, transparent); }

.dash-action-tile {
    display: flex; align-items: center; gap: 0.6rem; padding: 0.45rem 0.4rem; margin: 0 -0.4rem;
    font-size: 0.8125rem; color: inherit; text-decoration: none; border-radius: 6px;
}
.dash-action-tile i { width: 1rem; text-align: center; color: var(--color-primary); opacity: 0.8; }
.dash-action-tile:hover { background: color-mix(in oklab, var(--color-primary) 10%, transparent); }

/* Season timeline */
.season-track { position: relative; display: flex; height: 10px; border-radius: 5px; overflow: hidden; background: var(--color-base-300); }
.season-track .winter { background: color-mix(in oklab, #2f6fd6 35%, var(--color-base-300)); }
.season-track .summer { background: color-mix(in oklab, #e0a028 35%, var(--color-base-300)); }
.season-track .done { position: absolute; left: 0; top: 0; bottom: 0; background: linear-gradient(90deg, #2f6fd6 0, #2f6fd6 var(--w), #e0a028 var(--w)); }
.season-marker { position: relative; height: 1.3rem; margin-bottom: 0.15rem; }
.season-marker span { position: absolute; transform: translateX(-50%); font-size: 0.6875rem; font-weight: 600; white-space: nowrap; }
.season-marker span::after { content: ''; position: absolute; left: 50%; top: 100%; width: 1px; height: 6px; background: currentColor; }
.season-legend { display: flex; justify-content: space-between; margin-top: 0.45rem; font-size: 0.75rem; }

.forecast { display: flex; }
.forecast > div { padding: 0 0.75rem; text-align: center; border-left: 1px solid var(--color-base-300); }
.forecast > div:first-child { border-left: 0; }
.big-temp { font-family: var(--sign); font-size: 1.75rem; font-weight: 600; letter-spacing: -0.025em; line-height: 1.1; }

/* Utilities not in the compiled Tailwind build */
.dash .capitalize { text-transform: capitalize; }
.dash .mb-1\.5 { margin-bottom: 0.375rem; }

/* Customize mode */
.drag-handle {
    display: none; align-items: center; justify-content: space-between;
    padding: 6px 10px; font-size: 0.75rem; cursor: grab; user-select: none;
    background: var(--color-base-200); border-bottom: 1px solid var(--color-base-300);
}
.drag-handle:active { cursor: grabbing; }
.edit-mode .drag-handle { display: flex !important; }
.edit-mode .widget-item { outline: 1px dashed color-mix(in oklab, var(--color-primary) 50%, transparent); outline-offset: 2px; }
.widget-item.dragging { opacity: 0.35; }
.widget-item.drag-over { outline: 2px solid var(--color-primary) !important; }
</style>

<div class="dash" id="dashRoot">

<?php
$__mapKey   = preg_replace('/[^A-Za-z0-9]/', '', (string) ($finance['resort_map'] ?? 'ParkCity')) ?: 'ParkCity';
$__inPct    = ($cf['total_income'] + $cf['total_expenses']) > 0 ? round($cf['total_income'] / ($cf['total_income'] + $cf['total_expenses']) * 100) : 50;
?>
    <header class="dash-hero" style="--map:url('/img/<?= $__mapKey ?>_low.jpg')">
        <div>
            <div class="hero-kicker">
                <span class="lift-status <?= $resortOpen ? 'open' : 'closed' ?>"><?= $resortOpen ? 'Open' : 'Closed' ?></span>
                <span>Season <?= $seasonNum ?> · Day <?= $gameDay ?></span>
            </div>
            <h1><?= esc($resortMapName ?? 'Park City') ?></h1>
            <p class="hero-meta">
                <b><?= $liftCount ?></b> of <?= $totalLifts ?? $liftCount ?> lifts<span class="sep">·</span><b><?= $slopeCount ?></b> of <?= $totalSlopes ?? $slopeCount ?> slopes<span class="sep">·</span><b class="num"><?= number_format($repScore) ?></b> reputation
            </p>
        </div>
        <div class="dash-actions">
            <?php if ($bonusAvailable): ?>
            <a href="/daily-bonus" class="btn btn-warning btn-sm"><i class="fa-solid fa-gift" aria-hidden="true"></i> Daily bonus</a>
            <?php endif; ?>
            <a href="/map" class="btn btn-primary btn-sm"><i class="fa-solid fa-map" aria-hidden="true"></i> Trail map</a>
            <a href="/resort" class="btn btn-sm hero-btn">Resort settings</a>
            <button id="editModeBtn" type="button" onclick="toggleEditMode()" aria-pressed="false" class="btn btn-sm hero-btn">Customize</button>
        </div>
    </header>

    <div class="edit-mode-banner">
        <span>Drag a widget by its top bar to reorder it. Use S / M / L to change its width, or the eye icon to hide it.</span>
        <button type="button" onclick="toggleEditMode()" class="btn btn-xs btn-primary">Done</button>
    </div>

    <?php if (!empty($alerts) || $unclaimedAchievements > 0) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
        <?php foreach ($alerts as $alert) : ?>
        <a href="<?= $alert['link'] ?>" class="alert alert-<?= $alert['type'] ?> py-2 px-3.5 text-sm flex items-center justify-between">
            <span class="flex items-center gap-2.5"><i class="<?= $alert['icon'] ?>"></i><?= esc($alert['msg']) ?></span>
            <span class="underline whitespace-nowrap ml-2">Fix</span>
        </a>
        <?php endforeach ?>
        <?php if ($unclaimedAchievements > 0): ?>
        <a href="/achievements" class="alert alert-warning py-2 px-3.5 text-sm flex items-center justify-between">
            <span><?= $unclaimedAchievements ?> achievement reward<?= $unclaimedAchievements > 1 ? 's' : '' ?> to claim</span>
            <span class="underline whitespace-nowrap ml-2">Claim</span>
        </a>
        <?php endif; ?>
    </div>
    <?php endif ?>

    <section class="kpis" aria-label="Key numbers">
        <div class="kpi">
            <div class="kpi-label"><i class="fa-solid fa-sack-dollar opacity-60" aria-hidden="true"></i>Cash</div>
            <div class="kpi-value <?= $cashBalance < 0 ? 'text-error' : '' ?>"><?= currency($cashBalance) ?></div>
            <div class="kpi-sub">Lifetime <span class="<?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></span> · <?= number_format($genepisBal) ?> genepis</div>
        </div>
        <div class="kpi">
            <div class="kpi-label"><i class="fa-solid fa-chart-line opacity-60" aria-hidden="true"></i>Net per day</div>
            <div class="kpi-value <?= $dailyProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $dailyProfit >= 0 ? '+' : '' ?><?= currency($dailyProfit) ?></div>
            <div class="split" title="Income vs expenses"><span style="width:<?= $__inPct ?>%"></span><span style="flex:1"></span></div>
            <div class="kpi-sub"><?= currency($cf['total_income']) ?> in · <?= currency($cf['total_expenses']) ?> out</div>
        </div>
        <div class="kpi">
            <div class="kpi-label"><i class="fa-solid fa-person-skiing opacity-60" aria-hidden="true"></i>Visitors today</div>
            <div class="kpi-value" id="liveVisitors" data-count="<?= $dailyVisitors ?>" data-lifts="<?= $liftCount ?>" data-slopes="<?= $slopeCount ?>"><?= number_format($dailyVisitors) ?></div>
            <div class="kpi-sub">Ticket <?= $cf['ticket_price'] > 0 ? currency($cf['ticket_price']) : 'not set' ?> · lifts <?= number_format($totalLiftCapacity ?? 0) ?>/hr</div>
        </div>
        <div class="kpi">
            <div class="kpi-label"><i class="fa-solid <?= $weather ? ($wIcons[$weather['condition_name']] ?? 'fa-cloud') : 'fa-cloud' ?>" aria-hidden="true"></i>Weather</div>
            <div class="kpi-value"><?= $weather ? ($weather['temp_formatted'] ?? temp((int)($weather['temp'] ?? 0))) : '–' ?></div>
            <div class="kpi-sub"><?= esc($weather['condition_name'] ?? 'Clear') ?> · <?= $weather ? snow($weather['snow_base'] ?? 0) : '–' ?> base</div>
        </div>
    </section>

    <div id="widgetContainer" class="widget-grid">
    <?php foreach ($widgets as $w) : ?>
        <?php if (!$w['visible']) continue; ?>
        <?php $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
        <?php $sz = $w['size'] ?? 'large'; ?>

        <div class="widget-item widget-<?= $sz ?>" data-key="<?= $w['widget_key'] ?>" data-name="<?= esc($info['name']) ?>" data-size="<?= $sz ?>" id="widget-<?= $w['widget_key'] ?>">
            <div class="drag-handle">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-grip-vertical opacity-60" aria-hidden="true"></i>
                    <span><?= esc($info['name']) ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="join" role="group" aria-label="Resize <?= esc($info['name']) ?> widget">
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','small')" aria-label="Resize <?= esc($info['name']) ?> widget to small" aria-pressed="<?= $sz === 'small' ? 'true' : 'false' ?>" class="btn btn-xs join-item size-btn <?= $sz === 'small' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="small">S</button>
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','medium')" aria-label="Resize <?= esc($info['name']) ?> widget to medium" aria-pressed="<?= $sz === 'medium' ? 'true' : 'false' ?>" class="btn btn-xs join-item size-btn <?= $sz === 'medium' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="medium">M</button>
                        <button type="button" onclick="resizeWidget('<?= $w['widget_key'] ?>','large')" aria-label="Resize <?= esc($info['name']) ?> widget to large" aria-pressed="<?= $sz === 'large' ? 'true' : 'false' ?>" class="btn btn-xs join-item size-btn <?= $sz === 'large' ? 'btn-primary' : 'btn-ghost' ?>" data-sz="large">L</button>
                    </div>
                    <button type="button" onclick="hideWidget('<?= $w['widget_key'] ?>')" class="btn btn-ghost btn-xs ml-1" aria-label="Hide <?= esc($info['name']) ?> widget">
                        <i class="fa-solid fa-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div class="widget-content">

            <?php if ($w['widget_key'] === 'stats') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Cash flow <span class="aside">per day</span></h2>
                    <a href="/finances" class="widget-link">Ledger →</a>
                </div>
                <div class="cq-compact my-auto">
                    <div class="dash-row"><span class="flex-1 muted">Revenue</span><b class="num text-success"><?= $__plus((int) $cf['total_income']) ?></b></div>
                    <div class="dash-row"><span class="flex-1 muted">Expenses</span><b class="num text-error"><?= $__minus((int) $cf['total_expenses']) ?></b></div>
                </div>
                <div class="cq-expanded">
                    <div class="cq-stats-grid">
                        <div class="stat"><div class="stat-label">Tickets</div><div class="stat-value"><?= $__plus((int) $cf['ticket_income']) ?></div></div>
                        <div class="stat"><div class="stat-label">Buildings &amp; food</div><div class="stat-value"><?= $__plus((int) $cf['building_income']) ?></div></div>
                        <div class="stat"><div class="stat-label">Payroll</div><div class="stat-value"><?= $__minus((int) $cf['salaries']) ?></div></div>
                        <div class="stat" title="Building upkeep <?= currency($cf['building_upkeep']) ?>, equipment <?= currency($cf['equipment_cost']) ?>, insurance <?= currency($cf['insurance_cost']) ?>, marketing <?= currency($cf['marketing_cost']) ?>"><div class="stat-label">Upkeep &amp; running</div><div class="stat-value"><?= $__minus((int) $cf['running_costs']) ?></div></div>
                        <div class="stat"><div class="stat-label">Loan payments</div><div class="stat-value"><?= $__minus((int) $cf['loan_payments']) ?></div></div>
                        <div class="stat cq-large-only"><div class="stat-label">Net per day</div><div class="stat-value <?= $cf['daily_profit'] < 0 ? 'text-error' : 'text-success' ?>"><?= $cf['daily_profit'] < 0 ? $__minus((int) -$cf['daily_profit']) : $__plus((int) $cf['daily_profit']) ?></div></div>
                    </div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'overview') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Mountain</h2>
                    <div class="widget-links">
                        <a href="/grooming" class="widget-link">Grooming</a>
                        <a href="/map" class="widget-link">Trail map →</a>
                    </div>
                </div>
                <div class="cq-overview-grid">
                    <a href="/map" class="stat"><div class="stat-label">Slopes open</div><div class="stat-value"><?= $slopeCount ?> <small>/ <?= $totalSlopes ?? $slopeCount ?></small></div>
                        <?php if (!empty($slopesByDiff) && array_sum($slopesByDiff) > 0) : ?>
                        <div class="trails" aria-label="Open slopes by difficulty">
                            <span title="Green"><i class="tsign green"></i><?= $slopesByDiff['green'] ?></span>
                            <span title="Blue"><i class="tsign blue"></i><?= $slopesByDiff['blue'] ?></span>
                            <span title="Black"><i class="tsign black"></i><?= $slopesByDiff['black'] ?></span>
                            <span title="Double black"><i class="tsign black"></i><i class="tsign black"></i><?= $slopesByDiff['double_black'] ?></span>
                        </div>
                        <?php endif ?>
                    </a>
                    <a href="/map" class="stat"><div class="stat-label">Lifts open</div><div class="stat-value"><?= $liftCount ?> <small>/ <?= $totalLifts ?? $liftCount ?></small></div></a>
                    <a href="/staff" class="stat"><div class="stat-label">Staff</div><div class="stat-value"><?= $staffCount ?></div></a>
                    <a href="/buildings" class="stat"><div class="stat-label">Buildings</div><div class="stat-value"><?= $buildingCount ?></div></a>
                    <a href="/grooming" class="stat cq-large-only">
                        <div class="stat-label">Trail condition</div>
                        <div class="stat-value <?= ($avgSlopeCondition ?? 100) >= 70 ? '' : 'text-warning' ?>"><?= $avgSlopeCondition ?? 100 ?>%</div>
                        <div class="stat-note"><?= ($ungroomedCount ?? 0) > 0 ? ($ungroomedCount . ' need grooming') : 'All groomed' ?></div>
                    </a>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'season') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Season <?= $seasonNum ?> <span class="aside">day <?= $seasonProgress ?> of <?= $seasonLength ?></span></h2>
                </div>
                <div class="cq-compact">
                    <div class="widget-center">
                        <div class="radial-progress text-primary text-sm" style="--value:<?= $seasonPct ?>;--size:4rem;--thickness:4px;" role="progressbar" aria-valuenow="<?= $seasonPct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Season progress"><?= $seasonPct ?>%</div>
                    </div>
                </div>
                <?php $__winter = min(100, $seasonLength); $__summer = max(0, $seasonLength - $__winter); $__pos = round($seasonProgress / $seasonLength * 100, 2); ?>
                <div class="cq-expanded my-auto">
                    <div class="season-marker"><span style="left:<?= max(4, min(96, $__pos)) ?>%">Day <?= $seasonProgress ?></span></div>
                    <div class="season-track">
                        <span class="winter" style="flex:<?= $__winter ?>"></span>
                        <?php if ($__summer > 0) : ?><span class="summer" style="flex:<?= $__summer ?>"></span><?php endif ?>
                        <span class="done" style="width:<?= $__pos ?>%;--w:<?= $__pos > 0 ? round($__winter / $seasonLength * 100 / $__pos * 100, 2) : 100 ?>%"></span>
                    </div>
                    <div class="season-legend">
                        <span><i class="fa-solid fa-snowflake text-info" aria-hidden="true"></i> Winter · <?= $__winter ?> days</span>
                        <?php if ($__summer > 0) : ?><span class="muted">Summer · <?= $__summer ?> days <i class="fa-solid fa-sun text-warning" aria-hidden="true"></i></span><?php endif ?>
                    </div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'weather_mini' && $weather) : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Weather</h2>
                    <a href="/weather" class="widget-link">Forecast →</a>
                </div>
                <div class="cq-compact">
                    <div class="widget-center">
                        <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-2xl" aria-hidden="true"></i>
                        <div class="big-temp mt-1.5"><?= $weather['temp_formatted'] ?></div>
                        <div class="text-xs muted"><?= esc($weather['condition_name']) ?></div>
                    </div>
                </div>
                <div class="cq-expanded my-auto">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid <?= $wIcons[$weather['condition_name']] ?? 'fa-cloud' ?> text-3xl" aria-hidden="true"></i>
                            <div>
                                <div class="flex items-baseline gap-2"><span class="big-temp"><?= $weather['temp_formatted'] ?></span><span class="text-sm muted"><?= esc($weather['condition_name']) ?></span></div>
                                <div class="text-xs muted mt-0.5">Wind <?= speed((int)$weather['wind']) ?> · new snow <?= snow($weather['snowfall'] ?? 0) ?> · base <?= snow($weather['snow_base'] ?? 0) ?></div>
                            </div>
                        </div>
                        <?php if (!empty($forecast)) : ?>
                        <div class="forecast">
                            <?php foreach (array_slice($forecast, 0, 5) as $i => $fc) : ?>
                            <div class="<?= $i >= 3 ? 'cq-large-only' : '' ?>">
                                <div class="text-[11px] muted"><?= $i === 0 ? 'Tmrw' : '+' . ($i + 1) . 'd' ?></div>
                                <i class="fa-solid <?= $wIcons[$fc['condition']] ?? 'fa-cloud' ?> text-xs my-1" aria-hidden="true"></i>
                                <div class="text-xs num"><?= temp($fc['temp']) ?></div>
                            </div>
                            <?php endforeach ?>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'actions') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Manage</h2>
                </div>
                <nav class="cq-actions-grid" aria-label="Manage resort">
                    <a href="/map" class="dash-action-tile"><i class="fa-solid fa-map" aria-hidden="true"></i>Trail map</a>
                    <a href="/resort" class="dash-action-tile"><i class="fa-solid fa-sliders" aria-hidden="true"></i>Resort</a>
                    <a href="/finances" class="dash-action-tile"><i class="fa-solid fa-coins" aria-hidden="true"></i>Finances</a>
                    <a href="/staff" class="dash-action-tile"><i class="fa-solid fa-users" aria-hidden="true"></i>Staff <span class="muted num"><?= $staffCount ?></span></a>
                    <a href="/grooming" class="dash-action-tile"><i class="fa-solid fa-tractor" aria-hidden="true"></i>Grooming</a>
                    <a href="/snowmaking" class="dash-action-tile"><i class="fa-solid fa-snowflake" aria-hidden="true"></i>Snowmaking</a>
                    <a href="/buildings" class="dash-action-tile"><i class="fa-solid fa-hotel" aria-hidden="true"></i>Buildings</a>
                    <a href="/terrain-parks" class="dash-action-tile"><i class="fa-solid fa-person-snowboarding" aria-hidden="true"></i>Terrain parks</a>
                    <a href="/parking" class="dash-action-tile"><i class="fa-solid fa-square-parking" aria-hidden="true"></i>Parking</a>
                    <a href="/equipment" class="dash-action-tile"><i class="fa-solid fa-toolbox" aria-hidden="true"></i>Equipment</a>
                    <a href="/marketing" class="dash-action-tile"><i class="fa-solid fa-bullhorn" aria-hidden="true"></i>Marketing</a>
                    <a href="/vote" class="dash-action-tile"><i class="fa-solid fa-check-to-slot" aria-hidden="true"></i>Vote</a>
                </nav>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'achievements_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Rewards</h2>
                    <a href="/achievements" class="widget-link">All →</a>
                </div>
                <?php if ($unclaimedAchievements > 0) : ?>
                    <div class="flex items-center justify-between gap-3 my-auto">
                        <div>
                            <div class="text-sm font-semibold"><?= $unclaimedAchievements ?> reward<?= $unclaimedAchievements > 1 ? 's' : '' ?> ready</div>
                            <div class="text-xs muted"><?= $completedAchievements ?? 0 ?> achievements unlocked so far</div>
                        </div>
                        <a href="/achievements" class="btn btn-warning btn-sm">Claim</a>
                    </div>
                <?php else : ?>
                    <div class="cq-ach-mini-grid my-auto">
                        <div class="stat"><div class="stat-label">Completed</div><div class="stat-value"><?= $completedAchievements ?? 0 ?></div></div>
                        <div class="stat"><div class="stat-label">In progress</div><div class="stat-value"><?= count($inProgressAchievements) ?></div></div>
                        <div class="stat"><div class="stat-label">Unclaimed</div><div class="stat-value">0</div></div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'achievements' || $w['widget_key'] === 'getting_started') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Achievements in progress</h2>
                    <a href="/achievements" class="widget-link">All →</a>
                </div>
                <?php if (empty($inProgressAchievements)) : ?>
                    <div class="widget-center text-sm muted">Nothing left to unlock.</div>
                <?php else : ?>
                    <div class="flex-1 overflow-y-auto">
                        <?php foreach (array_slice($inProgressAchievements, 0, 4) as $ach) : ?>
                        <?php $pct = $ach['target'] > 0 ? min(100, (int) round($ach['progress'] / $ach['target'] * 100)) : 0; ?>
                        <div class="dash-row" style="display:block">
                            <div class="flex justify-between items-baseline gap-3 mb-1.5">
                                <span class="truncate"><?= esc($ach['name']) ?></span>
                                <span class="text-xs muted num whitespace-nowrap"><?= number_format($ach['progress']) ?> / <?= number_format($ach['target']) ?></span>
                            </div>
                            <progress class="progress progress-primary w-full h-1.5" value="<?= $ach['progress'] ?>" max="<?= max(1, $ach['target']) ?>" aria-label="<?= $pct ?>%"></progress>
                        </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'activity') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Recent activity</h2>
                    <a href="/activity" class="widget-link">Full log →</a>
                </div>
                <?php if (empty($recentActivity)) : ?>
                    <div class="widget-center text-sm muted">Nothing yet.</div>
                <?php else : ?>
                    <div id="activityFeedList" class="flex-1 overflow-y-auto">
                        <?php foreach (array_slice($recentActivity, 0, 6) as $log) : ?>
                        <div class="dash-row">
                            <i class="<?= esc($log['icon'] ?? 'fa-solid fa-circle-dot') ?>" aria-hidden="true"></i>
                            <span class="flex-1 truncate"><?= esc($log['message']) ?></span>
                            <span class="day">Day <?= esc($log['game_day'] ?? $gameDay) ?></span>
                        </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'parking_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Parking</h2>
                    <a href="/parking" class="widget-link">Manage</a>
                </div>
                <div class="grid grid-cols-3 gap-3 my-auto">
                    <div class="stat"><div class="stat-label">Lots</div><div class="stat-value"><?= count($parkingFacilities) ?></div></div>
                    <div class="stat"><div class="stat-label">Spaces</div><div class="stat-value"><?= number_format($totalParkingCap) ?></div></div>
                    <div class="stat"><div class="stat-label">Full</div><div class="stat-value <?= $parkingPct > 90 ? 'text-error' : ($parkingPct > 70 ? 'text-warning' : '') ?>"><?= $parkingPct ?>%</div></div>
                </div>
                <progress class="progress w-full h-1.5 mt-3" value="<?= $totalOccupied ?>" max="<?= max(1, $totalParkingCap) ?>"></progress>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'terrain_parks_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Terrain parks</h2>
                    <a href="/terrain-parks" class="widget-link">Manage</a>
                </div>
                <div class="grid grid-cols-3 gap-3 my-auto">
                    <div class="stat"><div class="stat-label">Open</div><div class="stat-value"><?= count($openParks) ?></div></div>
                    <div class="stat"><div class="stat-label">Building</div><div class="stat-value"><?= count($buildingParks) ?></div></div>
                    <div class="stat"><div class="stat-label">Riders</div><div class="stat-value"><?= number_format($totalParkVisitors) ?></div></div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'finances_mini' && $finance) : ?>
                <div class="widget-header">
                    <h2 class="widget-title">All-time finances</h2>
                    <a href="/finances" class="widget-link">Details</a>
                </div>
                <div class="my-auto">
                    <div class="dash-row"><span class="flex-1 muted">Income</span><span class="num"><?= currency($finance['total_income'] ?? 0) ?></span></div>
                    <div class="dash-row"><span class="flex-1 muted">Expenses</span><span class="num"><?= currency($finance['total_expenses'] ?? 0) ?></span></div>
                    <div class="dash-row"><span class="flex-1 muted">Net</span><b class="num <?= $netProfit >= 0 ? 'text-success' : 'text-error' ?>"><?= $netProfit >= 0 ? '+' : '' ?><?= currency($netProfit) ?></b></div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'staff_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Staff</h2>
                    <a href="/staff" class="widget-link">Manage</a>
                </div>
                <?php if (empty($staffAll)) : ?>
                    <div class="widget-center text-sm muted">
                        <p>No one hired yet.</p>
                        <a href="/staff/hire" class="btn btn-xs btn-primary mt-2">Hire staff</a>
                    </div>
                <?php else : ?>
                    <div class="mb-3">
                        <?php foreach ($roles as $role => $count) : ?>
                        <div class="dash-row"><span class="flex-1 capitalize truncate"><?= esc(str_replace('_', ' ', $role)) ?></span><span class="num"><?= $count ?></span></div>
                        <?php endforeach ?>
                    </div>
                    <div class="flex items-center gap-2 mt-auto text-xs">
                        <span class="muted">Morale</span>
                        <progress class="progress <?= $avgMorale >= 60 ? 'progress-success' : ($avgMorale >= 30 ? 'progress-warning' : 'progress-error') ?> flex-1 h-1.5" value="<?= $avgMorale ?>" max="100"></progress>
                        <span class="num"><?= $avgMorale ?>%</span>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'equipment_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Equipment</h2>
                    <a href="/equipment" class="widget-link">Fleet</a>
                </div>
                <div class="stat my-auto"><div class="stat-label">Units</div><div class="stat-value"><?= count($equipment) ?></div></div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'insurance_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Insurance</h2>
                    <a href="/insurance" class="widget-link">Policies</a>
                </div>
                <div class="grid grid-cols-2 gap-3 my-auto">
                    <div class="stat"><div class="stat-label">Active</div><div class="stat-value"><?= count($activeIns) ?></div></div>
                    <div class="stat"><div class="stat-label">Available</div><div class="stat-value"><?= max(0, count($insurance) - count($activeIns)) ?></div></div>
                </div>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'loans_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Loans</h2>
                    <a href="/bank" class="widget-link">Bank</a>
                </div>
                <?php if (empty($loans)) : ?>
                    <div class="stat my-auto"><div class="stat-label">Owed</div><div class="stat-value">None</div></div>
                <?php else : ?>
                    <div class="stat my-auto">
                        <div class="stat-label"><?= count($loans) ?> loan<?= count($loans) > 1 ? 's' : '' ?> outstanding</div>
                        <div class="stat-value"><?= currency(array_sum(array_column($loans, 'remaining'))) ?></div>
                    </div>
                <?php endif ?>
            <?php endif ?>

            <?php if ($w['widget_key'] === 'marketing_mini') : ?>
                <div class="widget-header">
                    <h2 class="widget-title">Marketing</h2>
                    <a href="/marketing" class="widget-link">Campaigns</a>
                </div>
                <div class="stat my-auto"><div class="stat-label">Running campaigns</div><div class="stat-value"><?= count($marketing) ?></div></div>
            <?php endif ?>

            </div><!-- /widget-content -->
        </div><!-- /widget-item -->
    <?php endforeach ?>
    </div>

    <div id="hiddenWidgets" class="hidden mt-6 p-4 rounded-lg border border-base-300" inert>
        <h2 class="text-sm font-semibold mb-3">Hidden widgets</h2>
        <div class="flex flex-wrap gap-2" id="hiddenWidgetList">
        <?php foreach ($widgets as $w) : ?>
            <?php if ($w['visible']) continue; $info = $availableWidgets[$w['widget_key']] ?? null; if (!$info) continue; ?>
            <button type="button" onclick="showWidget('<?= $w['widget_key'] ?>')" class="btn btn-outline btn-sm gap-1.5 hidden-widget-btn" data-key="<?= $w['widget_key'] ?>">
                <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> <?= esc($info['name']) ?>
            </button>
        <?php endforeach ?>
        </div>
    </div>
</div>

<script>
const csrfName = '<?= csrf_token() ?>';
let csrfHash = '<?= csrf_hash() ?>';
const dashRoot = document.getElementById('dashRoot');
const container = document.getElementById('widgetContainer');
const editBtn = document.getElementById('editModeBtn');
const hiddenSection = document.getElementById('hiddenWidgets');
let editMode = false, dragEl = null;

function toggleEditMode() {
    editMode = !editMode;
    container.classList.toggle('edit-mode', editMode);
    dashRoot.classList.toggle('edit-mode-active', editMode);
    hiddenSection.classList.toggle('hidden', !editMode);
    hiddenSection.toggleAttribute('inert', !editMode);
    editBtn.setAttribute('aria-pressed', editMode ? 'true' : 'false');
    editBtn.innerHTML = editMode
        ? 'Done'
        : 'Customize';
    container.querySelectorAll('.widget-item').forEach(el => el.setAttribute('draggable', editMode ? 'true' : 'false'));
}

function ajaxPost(url, data) {
    return fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfHash
        },
        body: JSON.stringify({ ...data, [csrfName]: csrfHash })
    })
    .then(r => r.json())
    .then(json => {
        if (json && json.csrf_hash) csrfHash = json.csrf_hash;
        return json;
    });
}

function hideWidget(key) {
    const el = document.getElementById('widget-' + key);
    const widgetName = (el && el.dataset.name) ? el.dataset.name : key.replace(/_/g, ' ');
    if (el) {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 180);
    }
    ajaxPost('/dashboard/toggle-widget', { widget_key: key });
    const list = document.getElementById('hiddenWidgetList');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline btn-sm gap-1.5 hidden-widget-btn';
    btn.dataset.key = key;
    btn.innerHTML = '<i class="fa-solid fa-plus text-xs" aria-hidden="true"></i> ' + esc2(widgetName);
    btn.onclick = () => showWidget(key);
    list.appendChild(btn);
}

function esc2(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

function showWidget(key) {
    ajaxPost('/dashboard/toggle-widget', { widget_key: key }).then(() => {
        sessionStorage.setItem('editMode', '1');
        location.reload();
    });
}

function resizeWidget(key, size) {
    const el = document.getElementById('widget-' + key);
    if (el) {
        el.classList.remove('widget-small', 'widget-medium', 'widget-large');
        el.classList.add('widget-' + size);
        el.dataset.size = size;
        el.querySelectorAll('.size-btn').forEach(b => {
            const active = b.dataset.sz === size;
            b.classList.toggle('btn-primary', active);
            b.classList.toggle('btn-ghost', !active);
            b.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }
    ajaxPost('/dashboard/resize-widget', { widget_key: key, size: size });
}

function saveOrder() {
    const order = [...container.querySelectorAll('.widget-item')].map(el => el.dataset.key);
    ajaxPost('/dashboard/reorder-widgets', { order });
}

container.addEventListener('dragstart', e => {
    if (!editMode) return;
    dragEl = e.target.closest('.widget-item');
    if (dragEl) {
        dragEl.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', '');
    }
});
container.addEventListener('dragend', () => {
    if (dragEl) dragEl.classList.remove('dragging');
    container.querySelectorAll('.widget-item').forEach(el => el.classList.remove('drag-over'));
    if (dragEl) saveOrder();
    dragEl = null;
});
container.addEventListener('dragover', e => {
    if (!editMode || !dragEl) return;
    e.preventDefault();
    const t = e.target.closest('.widget-item');
    container.querySelectorAll('.widget-item').forEach(el => el.classList.remove('drag-over'));
    if (t && t !== dragEl) t.classList.add('drag-over');
});
container.addEventListener('drop', e => {
    if (!editMode || !dragEl) return;
    e.preventDefault();
    const t = e.target.closest('.widget-item');
    if (t && t !== dragEl) {
        const items = [...container.querySelectorAll('.widget-item')];
        const refNode = items.indexOf(dragEl) < items.indexOf(t) ? t.nextSibling : t;
        if ('moveBefore' in Element.prototype) {
            container.moveBefore(dragEl, refNode);
        } else {
            container.insertBefore(dragEl, refNode);
        }
    }
});

if (sessionStorage.getItem('editMode')) {
    sessionStorage.removeItem('editMode');
    toggleEditMode();
}
</script>
<?php if (featureEnabled("live_visitors")) : ?>
<script data-cfasync="false">
(function(){var el=document.getElementById("liveVisitors");if(!el)return;var base=parseInt(el.dataset.count)||0,lifts=parseInt(el.dataset.lifts)||0,slopes=parseInt(el.dataset.slopes)||0;var capacity=Math.max(1,lifts*80+slopes*40);var cur=Math.min(base,capacity);el.textContent=cur.toLocaleString();var nv=document.getElementById("navVisitors");if(nv)nv.textContent=cur.toLocaleString()+" visitors";function tick(){if(lifts===0&&slopes===0){el.textContent="0";setTimeout(tick,10000);return}var range=Math.max(1,Math.ceil(capacity*0.02));var d=Math.floor(Math.random()*(range*2+1))-range;if(Math.random()<0.4)d=0;cur=Math.max(0,Math.min(cur+d,capacity));el.textContent=cur.toLocaleString();var nv2=document.getElementById("navVisitors");if(nv2)nv2.textContent=cur.toLocaleString()+" visitors";if(d!==0){/* no colour flash */}setTimeout(tick,8000+Math.floor(Math.random()*7000))}setTimeout(tick,5000)})();
</script>
<?php endif ?>
<?php if (featureEnabled("activity_feed")) : ?>
<script data-cfasync="false">
(function(){
    var feedList = document.getElementById("activityFeedList");
    if (!feedList) return;
    function esc(s) {
        return String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function refresh() {
        fetch("/dashboard/activity", { priority: "low", headers: { "X-Requested-With": "XMLHttpRequest" } })
        .then(function(r) { return r.json(); })
        .then(function(logs) {
            if (!Array.isArray(logs) || !logs.length) return;
            feedList.innerHTML = "";
            logs.slice(0, 6).forEach(function(log) {
                var div = document.createElement("div");
                div.className = "dash-row";
                div.innerHTML = '<i class="' + esc(log.icon || 'fa-solid fa-circle-dot') + '" aria-hidden="true"></i>' +
                    '<span class="flex-1 truncate">' + esc(log.message) + '</span>' +
                    '<span class="day">Day ' + esc(log.game_day || '') + '</span>';
                feedList.appendChild(div);
            });
        }).catch(function(){});
    }
    setInterval(refresh, 30000);
})();
</script>
<?php endif ?>
<?= $this->endSection() ?>
