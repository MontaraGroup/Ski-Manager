<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Panel<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $__seasonLen = max(1, (int) ($season['duration_days'] ?? 135));
    $__seasonPct = min(100, (int) round($gameDay / $__seasonLen * 100));
    $__maintenance = (bool) ($season['maintenance'] ?? 0);
    $__envWritable = is_writable(ROOTPATH . '.env');
    $__tickAge = $lastTick ? time() - strtotime($lastTick['created_at']) : null;
    $__tickStale = $__tickAge === null || $__tickAge > 26 * 3600;
    $__activePct = $totalUsers > 0 ? (int) round($onlineRecent / $totalUsers * 100) : 0;
    $__avgCash = $totalUsers > 0 ? (int) ($totalCash / $totalUsers) : 0;
    $__gb = fn($b) => number_format($b / 1073741824, 1) . ' GB';
    $__navGroups = [
        'Players' => [
            ['/admin/broadcast', 'fa-bullhorn', 'Broadcast'],
            ['/admin/support', 'fa-headset', 'Support', $unreadSupport],
            ['/admin/compare', 'fa-code-compare', 'Compare'],
            ['/admin/suspicious', 'fa-triangle-exclamation', 'Suspicious'],
            ['/admin/export-players', 'fa-download', 'Export'],
        ],
        'Game' => [
            ['/admin/settings', 'fa-gear', 'Settings'],
            ['/admin/economy', 'fa-chart-line', 'Economy'],
            ['/admin/seasons', 'fa-calendar-plus', 'Seasons'],
            ['/admin/features', 'fa-toggle-on', 'Flags'],
            ['/admin/changelogs', 'fa-newspaper', 'Changelogs'],
        ],
        'Logs' => [
            ['/admin/activity', 'fa-clock-rotate-left', 'Activity'],
            ['/admin/audit', 'fa-shield-halved', 'Audit'],
            ['/admin/errors', 'fa-bug', 'Errors'],
        ],
    ];
    $__kpis = [
        ['fa-users', 'Players', number_format($totalUsers), '+' . $newUsers7d . ' this week' . ($bannedUsers ? ' · ' . $bannedUsers . ' banned' : '')],
        ['fa-signal', 'Active 24h', number_format($onlineRecent), $__activePct . '% of players'],
        ['fa-sack-dollar', 'Total cash', currency($totalCash), currency($__avgCash) . ' avg per player'],
        ['fa-file-invoice-dollar', 'Active loans', number_format($totalLoans), 'across all players'],
        ['fa-user-group', 'Staff', number_format($totalStaff), 'currently employed'],
        ['fa-hotel', 'Buildings', number_format($totalBuildings), 'lodges, hotels, restaurants'],
        ['fa-person-skiing', 'Slopes & lifts', number_format($totalItems), 'built by players'],
        ['fa-square-parking', 'Parking & parks', number_format($totalParking + $totalParks), $totalParking . ' lots · ' . $totalParks . ' terrain parks'],
    ];
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<style>
/* Same visual language as /dashboard */
.adm { max-width: 1240px; margin: 0 auto; padding: 1.25rem 1rem 3rem; }
@media (min-width: 1024px) { .adm { padding: 1.75rem 2rem 3rem; } }
.adm .muted { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.adm .num { font-variant-numeric: tabular-nums; }

.adm-head { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
.adm-head h1 { font-family: var(--sign); font-size: 1.75rem; font-weight: 700; letter-spacing: -0.025em; line-height: 1.1; margin: 0; }
.adm-tag { font-family: var(--sign); font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; padding: 1px 8px 0; border-radius: 3px; line-height: 1.5; }
.adm-tag.staging { background: var(--color-warning); color: var(--color-warning-content); }
.adm-tag.prod { background: var(--color-error); color: var(--color-error-content); }
.adm-tag.maint { color: var(--color-error); box-shadow: inset 0 0 0 1px var(--color-error); }
.adm-season { width: 100%; max-width: 280px; }
.adm-season .bar { height: 4px; border-radius: 2px; background: var(--color-base-300); overflow: hidden; margin-top: 0.4rem; }
.adm-season .bar > span { display: block; height: 100%; background: var(--color-primary); }

.panel { background: var(--color-base-100); border: 1px solid var(--color-base-300); border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,0.06); min-width: 0; }
.panel-body { padding: 0.9rem 1.25rem 1.1rem; }
.panel-header { display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem; padding: 0.8rem 1.25rem 0.7rem; border-bottom: 1px solid var(--color-base-300); }
.panel-title { font-family: var(--sign); font-size: 1rem; font-weight: 600; line-height: 1.2; margin: 0; }
.panel-title .aside { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 0.75rem; font-weight: 400; margin-left: 0.5em; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.panel-link { font-size: 0.75rem; font-weight: 500; white-space: nowrap; text-decoration: none; color: var(--color-primary); }
.panel-link:hover { text-decoration: underline; text-underline-offset: 3px; }

/* Toolbar: section links on the left, game controls on the right */
.adm-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem 1.5rem; padding: 0.6rem 0.9rem; margin-bottom: 1.25rem; }
.adm-nav { display: flex; flex-wrap: wrap; align-items: center; gap: 0.25rem 1.25rem; }
.adm-nav-group { display: flex; flex-wrap: wrap; align-items: center; gap: 0.15rem; }
.adm-nav-group > span { font-size: 0.6875rem; font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; margin-right: 0.35rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.adm-nav a { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.3rem 0.5rem; border-radius: 6px; font-size: 0.8125rem; font-weight: 500; text-decoration: none; color: inherit; }
.adm-nav a:hover { background: var(--color-base-200); color: var(--color-primary); }
.adm-nav a i { opacity: 0.55; font-size: 0.75rem; }
.adm-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; }
.tick-status { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.8125rem; margin-right: 0.25rem; }
.adm .dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }

/* Headline numbers: one joined strip, like the dashboard */
.kpis { display: grid; grid-template-columns: repeat(2, 1fr); margin-bottom: 1.25rem; }
@media (min-width: 1024px) { .kpis { grid-template-columns: repeat(4, 1fr); } }
.kpi { padding: 0.95rem 1.25rem 1.05rem; min-width: 0; }
.kpi:nth-child(even) { border-left: 1px solid var(--color-base-300); }
.kpi:nth-child(n+3) { border-top: 1px solid var(--color-base-300); }
@media (min-width: 1024px) {
    .kpi:nth-child(-n+4) { border-top: 0; }
    .kpi:not(:nth-child(4n+1)) { border-left: 1px solid var(--color-base-300); }
}
.kpi-label { font-size: 0.75rem; font-weight: 500; display: flex; align-items: center; gap: 0.4rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.kpi-value { font-family: var(--sign); font-size: 1.625rem; font-weight: 600; letter-spacing: -0.025em; line-height: 1.2; margin-top: 0.1rem; font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (max-width: 640px) { .kpi { padding: 0.8rem 0.9rem 0.9rem; } .kpi-value { font-size: 1.375rem; } }
.kpi-sub { font-size: 0.75rem; margin-top: 0.3rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }

.adm-grid { display: grid; grid-template-columns: 1fr; gap: 1rem; margin-bottom: 1rem; }
@media (min-width: 1024px) { .adm-grid.main { grid-template-columns: 2fr 1fr; } .adm-grid.tools { grid-template-columns: repeat(3, 1fr); } }

.adm [data-filter].on { background: var(--color-primary); border-color: var(--color-primary); color: var(--color-primary-content); }
.players th { white-space: nowrap; }
.players th[data-sort] { cursor: pointer; user-select: none; }
.players th[data-sort]:hover { color: var(--color-primary); }
.players th[aria-sort="ascending"]::after { content: ' ▲'; font-size: 0.6rem; }
.players th[aria-sort="descending"]::after { content: ' ▼'; font-size: 0.6rem; }
.players tbody tr.player-row:hover { background: var(--color-base-200); }

.feed-item { display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.55rem 1.25rem; font-size: 0.8125rem; }
.feed-item + .feed-item { border-top: 1px solid var(--color-base-300); }
.feed-item > i { width: 1rem; text-align: center; margin-top: 0.15rem; opacity: 0.5; }

.field-label { display: block; font-size: 0.75rem; margin-bottom: 0.2rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.adm .row { display: flex; justify-content: space-between; gap: 1rem; font-size: 0.8125rem; padding: 0.2rem 0; }
.meter { height: 4px; border-radius: 2px; background: var(--color-base-300); overflow: hidden; margin-top: 0.35rem; }
.meter > span { display: block; height: 100%; }
</style>

<div class="adm">

    <!-- Header -->
    <div class="adm-head">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1>Admin</h1>
                <span class="adm-tag <?= $isStaging ? 'staging' : 'prod' ?>"><?= $isStaging ? 'Staging' : 'Production' ?></span>
                <?php if ($__maintenance) : ?><span class="adm-tag maint">Maintenance</span><?php endif ?>
            </div>
            <div class="text-xs muted mt-1.5">Season started <?= esc($season['start_date'] ?? 'N/A') ?> · Server time <?= date('g:ia') ?> (<?= esc(date_default_timezone_get()) ?>)</div>
        </div>
        <div class="adm-season">
            <div class="flex justify-between text-xs">
                <span class="font-semibold num">Day <?= $gameDay ?> of <?= $__seasonLen ?></span>
                <span class="muted num"><?= $gameDay >= $__seasonLen ? 'final day' : ($__seasonLen - $gameDay) . ' days left' ?></span>
            </div>
            <div class="bar"><span style="width: <?= $__seasonPct ?>%"></span></div>
        </div>
    </div>

    <?php if (session('success')) : ?><div role="alert" class="alert alert-success mb-4"><i class="fa-solid fa-circle-check"></i><span><?= esc(session('success')) ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div role="alert" class="alert alert-error mb-4"><i class="fa-solid fa-circle-exclamation"></i><span><?= esc(session('error')) ?></span></div><?php endif ?>

    <!-- Sections + controls -->
    <div class="panel adm-toolbar">
        <nav class="adm-nav" aria-label="Admin sections">
            <?php foreach ($__navGroups as $__group => $__links) : ?>
                <div class="adm-nav-group">
                    <span><?= $__group ?></span>
                    <?php foreach ($__links as $__l) : ?>
                        <a href="<?= $__l[0] ?>"><i class="fa-solid <?= $__l[1] ?>" aria-hidden="true"></i><?= $__l[2] ?><?php if (!empty($__l[3])) : ?><span class="badge badge-error badge-xs"><?= (int) $__l[3] ?></span><?php endif ?></a>
                    <?php endforeach ?>
                </div>
            <?php endforeach ?>
        </nav>
        <div class="adm-controls">
            <span class="tick-status" title="<?= $lastTick ? esc($lastTick['created_at']) : '' ?>">
                <span class="dot <?= $__tickStale ? 'bg-error' : 'bg-success' ?>"></span>
                <span class="muted">Last tick</span>
                <b class="<?= $__tickStale ? 'text-error' : '' ?>"><?= $lastTick ? timeAgo($lastTick['created_at']) : 'never' ?></b>
            </span>
            <form action="/admin/maintenance" method="post" onsubmit="return confirm('<?= $__maintenance ? 'Turn maintenance OFF and reopen the site to players?' : 'Turn maintenance ON? Players will see a 503 page.' ?>')"><?= csrf_field() ?>
                <button class="btn btn-xs <?= $__maintenance ? 'btn-error' : 'btn-ghost' ?> gap-1"><i class="fa-solid fa-wrench"></i>Maintenance <?= $__maintenance ? 'on' : 'off' ?></button>
            </form>
            <form action="/admin/toggle-env" method="post"><?= csrf_field() ?>
                <button class="btn btn-xs <?= ENVIRONMENT === 'development' ? 'btn-warning' : 'btn-ghost' ?> gap-1" <?= $__envWritable ? '' : 'disabled' ?> title="<?= $__envWritable ? 'Toggle CI_ENVIRONMENT between development and production' : '.env is read-only on the server (edit it directly or chmod 664 .env)' ?>">
                    <i class="fa-solid fa-code"></i><?= ENVIRONMENT === 'development' ? 'Dev mode' : 'Prod mode' ?><?php if (!$__envWritable) : ?> <i class="fa-solid fa-lock text-[10px] opacity-70"></i><?php endif ?>
                </button>
            </form>
            <form action="/admin/trigger-tick" method="post" onsubmit="return confirm('Run the game tick on <?= $isStaging ? 'STAGING' : 'PRODUCTION' ?> now? This advances every player by a day.')"><?= csrf_field() ?>
                <button class="btn btn-xs btn-primary gap-1"><i class="fa-solid fa-play"></i>Run tick</button>
            </form>
        </div>
    </div>

    <!-- Headline numbers -->
    <section class="panel kpis" aria-label="Key numbers">
        <?php foreach ($__kpis as [$__icon, $__label, $__value, $__sub]) : ?>
            <div class="kpi">
                <div class="kpi-label"><i class="fa-solid <?= $__icon ?> opacity-60" aria-hidden="true"></i><?= $__label ?></div>
                <div class="kpi-value"><?= $__value ?></div>
                <div class="kpi-sub"><?= esc($__sub) ?></div>
            </div>
        <?php endforeach ?>
    </section>

    <!-- Players + activity -->
    <div class="adm-grid main">
        <section class="panel flex flex-col">
            <div class="panel-header flex-wrap">
                <h2 class="panel-title">Players <span class="aside">newest <?= count($users) ?> of <?= number_format($totalUsers) ?></span></h2>
                <div class="flex items-center gap-2">
                    <div class="join" role="group" aria-label="Filter players">
                        <button type="button" class="btn btn-xs join-item on" data-filter="all">All</button>
                        <button type="button" class="btn btn-xs join-item" data-filter="online">Online</button>
                        <button type="button" class="btn btn-xs join-item" data-filter="banned">Banned</button>
                    </div>
                    <search><input type="search" id="playerSearch" placeholder="Name or ID…" aria-label="Search players" class="input input-xs w-32"></search>
                </div>
            </div>
            <div class="overflow-auto max-h-[560px]">
                <table class="table table-sm table-pin-rows players" id="playerTable">
                    <thead><tr>
                        <th data-sort="num">ID</th>
                        <th data-sort="text">Player</th>
                        <th data-sort="num" class="text-right">Cash</th>
                        <th data-sort="num" class="text-right">Staff</th>
                        <th data-sort="num" class="text-right" title="Buildings">Bldg</th>
                        <th data-sort="num" class="text-right" title="Slopes and lifts">Items</th>
                        <th data-sort="num">Joined</th>
                        <th data-sort="num">Last active</th>
                        <th><span class="sr-only">Actions</span></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($users as $u) : ?>
                        <?php
                            $__banned = isset($u['active']) && !$u['active'];
                            $__lastTs = !empty($u['last_active']) ? strtotime($u['last_active']) : 0;
                            $__online = $__lastTs > strtotime('-15 minutes');
                        ?>
                        <tr class="player-row <?= $__banned ? 'opacity-50' : '' ?>" data-online="<?= $__online ? 1 : 0 ?>" data-banned="<?= $__banned ? 1 : 0 ?>">
                            <td data-v="<?= (int) $u['id'] ?>" class="muted num"><?= (int) $u['id'] ?></td>
                            <td data-v="<?= esc(strtolower($u['username'] ?? '')) ?>" class="font-medium whitespace-nowrap">
                                <a href="/admin/user/<?= (int) $u['id'] ?>" class="link link-hover"><?= esc($u['username']) ?></a>
                                <?php if ($__banned) : ?><span class="badge badge-error badge-xs ml-1">Banned</span><?php endif ?>
                            </td>
                            <td data-v="<?= $u['cash'] ?>" class="num text-right whitespace-nowrap <?= $u['cash'] < 0 ? 'text-error' : '' ?>"><?= currency($u['cash']) ?></td>
                            <td data-v="<?= $u['staff_count'] ?>" class="num text-right"><?= $u['staff_count'] ?></td>
                            <td data-v="<?= $u['building_count'] ?>" class="num text-right"><?= $u['building_count'] ?></td>
                            <td data-v="<?= $u['item_count'] ?>" class="num text-right"><?= $u['item_count'] ?></td>
                            <td data-v="<?= strtotime($u['created_at']) ?>" class="text-xs muted whitespace-nowrap" title="<?= esc($u['created_at']) ?>"><?= date('M j', strtotime($u['created_at'])) ?></td>
                            <td data-v="<?= $__lastTs ?>" class="text-xs whitespace-nowrap <?= $__lastTs > strtotime('-1 hour') ? 'text-success font-semibold' : 'muted' ?>" title="<?= $u['last_activity'] ? esc($u['last_activity']['message']) : '' ?>">
                                <?php if ($__online) : ?><span class="dot bg-success mr-1" title="Online now"></span><?php endif ?><?= $__lastTs ? timeAgo($u['last_active']) : 'Never' ?>
                            </td>
                            <td>
                                <div class="flex gap-0.5 justify-end">
                                    <a href="/admin/user/<?= (int) $u['id'] ?>" class="btn btn-ghost btn-xs" aria-label="Edit <?= esc($u['username']) ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <?php if ((int) $u['id'] !== 1) : ?>
                                        <?php if ($__banned) : ?>
                                            <form action="/admin/unban/<?= (int) $u['id'] ?>" method="post"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-success" aria-label="Unban <?= esc($u['username']) ?>" title="Unban"><i class="fa-solid fa-unlock"></i></button></form>
                                        <?php else : ?>
                                            <form action="/admin/ban/<?= (int) $u['id'] ?>" method="post" onsubmit="return confirm('Ban <?= esc($u['username'], 'js') ?>?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-warning" aria-label="Ban <?= esc($u['username']) ?>" title="Ban"><i class="fa-solid fa-ban"></i></button></form>
                                        <?php endif ?>
                                        <form action="/admin/delete/<?= (int) $u['id'] ?>" method="post" onsubmit="return confirm('Permanently delete <?= esc($u['username'], 'js') ?> and all their resort data? This cannot be undone.')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error" aria-label="Delete <?= esc($u['username']) ?>" title="Delete"><i class="fa-solid fa-trash"></i></button></form>
                                    <?php endif ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                    <tr id="noResults" class="hidden"><td colspan="9" class="text-center muted py-6">No players match.</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel flex flex-col">
            <div class="panel-header">
                <h2 class="panel-title">Activity</h2>
                <a href="/admin/activity" class="panel-link">View all</a>
            </div>
            <div class="overflow-y-auto max-h-[560px] py-1">
                <?php if (empty($recentLogs)) : ?><div class="p-6 text-center text-sm muted">No activity yet.</div><?php endif ?>
                <?php foreach ($recentLogs as $log) : ?>
                    <div class="feed-item">
                        <i class="<?= esc($log['icon'], 'attr') ?>" aria-hidden="true"></i>
                        <div class="flex-1 min-w-0">
                            <div class="truncate" title="<?= esc($log['message']) ?>"><span class="font-semibold text-primary"><?= esc($log['username']) ?></span> <?= esc($log['message']) ?></div>
                            <div class="text-[11px] muted">Day <?= (int) $log['game_day'] ?> · <?= timeAgo($log['created_at']) ?></div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </section>
    </div>

    <!-- Tools -->
    <div class="adm-grid tools">
        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Weather</h2>
                <?php if ($weather) : ?><span class="text-xs muted whitespace-nowrap"><i class="fa-solid <?= weatherIcon($weather['condition_name'] ?? '') ?> mr-1"></i><?= esc($weather['condition_name'] ?? '') ?> · <?= temp($weather['temp']) ?></span><?php endif ?>
            </div>
            <form action="/admin/set-weather" method="post" class="panel-body"><?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-x-3 gap-y-2.5 mb-3">
                    <div class="col-span-2"><label class="field-label" for="wCond">Condition</label>
                    <select name="condition" id="wCond" class="select select-sm w-full">
                        <?php foreach (array_keys(\App\Libraries\Weather::VISIBILITY) as $__c) : ?>
                            <option value="<?= $__c ?>" <?= ($weather['condition_name'] ?? 'Light Snow') === $__c ? 'selected' : '' ?>><?= $__c ?></option>
                        <?php endforeach ?>
                    </select></div>
                    <div><label class="field-label" for="wTemp">Temp (°C)</label><input type="number" name="temp" id="wTemp" value="<?= (int) ($weather['temp'] ?? -5) ?>" class="input input-sm w-full" min="-30" max="30"></div>
                    <div><label class="field-label" for="wWind">Wind (km/h)</label><input type="number" name="wind" id="wWind" value="<?= (int) ($weather['wind'] ?? 15) ?>" class="input input-sm w-full" min="0" max="120"></div>
                    <div><label class="field-label" for="wSnow">Snowfall (cm)</label><input type="number" name="snowfall" id="wSnow" value="<?= (int) ($weather['snowfall'] ?? 0) ?>" class="input input-sm w-full" min="0" max="50"></div>
                    <div><label class="field-label" for="wBase">Snow base (cm)</label><input type="number" name="snow_base" id="wBase" value="<?= (int) ($weather['snow_base'] ?? 50) ?>" class="input input-sm w-full" min="0" max="300"></div>
                </div>
                <button class="btn btn-sm btn-primary w-full">Apply weather</button>
            </form>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Bulk actions</h2>
                <span class="text-xs text-warning whitespace-nowrap">all <?= (int) $totalUsers ?> players</span>
            </div>
            <div class="panel-body space-y-3">
                <form action="/admin/add-cash-all" method="post" onsubmit="return confirm('Give ' + Number(this.amount.value).toLocaleString() + ' cash to ALL <?= (int) $totalUsers ?> players? This affects the economy and leaderboard.')"><?= csrf_field() ?>
                    <label class="field-label" for="cashAll">Give cash to everyone</label>
                    <div class="join w-full">
                        <input type="number" name="amount" id="cashAll" value="10000" min="1" max="10000000" class="input input-sm join-item flex-1 min-w-0">
                        <button class="btn btn-sm join-item">Give</button>
                    </div>
                </form>
                <form action="/admin/add-genepis-all" method="post" onsubmit="return confirm('Give ' + Number(this.amount.value).toLocaleString() + ' Genepis to ALL <?= (int) $totalUsers ?> players?')"><?= csrf_field() ?>
                    <label class="field-label" for="genAll">Give Genepis to everyone</label>
                    <div class="join w-full">
                        <input type="number" name="amount" id="genAll" value="100" min="1" max="100000" class="input input-sm join-item flex-1 min-w-0">
                        <button class="btn btn-sm join-item">Give</button>
                    </div>
                </form>
                <form action="/admin/grant-achievement" method="post" class="pt-3 border-t border-base-300" onsubmit="return confirm('Grant achievement #' + this.achievement_id.value + ' to user #' + this.user_id.value + '?')"><?= csrf_field() ?>
                    <label class="field-label">Grant achievement to one player</label>
                    <div class="join w-full">
                        <input type="number" name="user_id" min="1" class="input input-sm join-item flex-1 min-w-0" placeholder="User ID" aria-label="User ID" required>
                        <input type="number" name="achievement_id" min="1" class="input input-sm join-item flex-1 min-w-0" placeholder="Achievement ID" aria-label="Achievement ID" required>
                        <button class="btn btn-sm join-item">Grant</button>
                    </div>
                </form>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">Server</h2>
                <a href="/admin/errors" class="panel-link">Error log</a>
            </div>
            <div class="panel-body">
                <div class="mb-3">
                    <div class="row"><span class="muted">Disk</span><span class="num"><?= $__gb($server['diskUsed']) ?> / <?= $__gb($server['diskTotal']) ?></span></div>
                    <div class="meter"><span class="<?= $server['diskPct'] > 85 ? 'bg-error' : ($server['diskPct'] > 70 ? 'bg-warning' : 'bg-success') ?>" style="width: <?= $server['diskPct'] ?>%"></span></div>
                </div>
                <div class="row"><span class="muted">Load (1/5/15m)</span><span class="num <?= $server['load'][0] > $server['cores'] ? 'text-error' : '' ?>"><?= implode(' / ', array_map(fn($l) => number_format($l, 2), $server['load'])) ?></span></div>
                <div class="row"><span class="muted">CPU cores</span><span class="num"><?= $server['cores'] ?></span></div>
                <div class="row"><span class="muted">PHP / CI4</span><span class="num"><?= PHP_VERSION ?> / <?= \CodeIgniter\CodeIgniter::CI_VERSION ?></span></div>
                <div class="row"><span class="muted">Memory (peak / limit)</span><span class="num"><?= $server['memPeak'] ?>M / <?= esc($server['memLimit']) ?></span></div>
                <div class="row"><span class="muted">MySQL</span><span class="num"><?= esc($server['dbVersion']) ?></span></div>
                <div class="row"><span class="muted">Database size</span><span class="num"><?= $server['dbSize'] ?> MB</span></div>
            </div>
        </section>
    </div>
</div>
<script>
(function () {
    var table = document.getElementById('playerTable');
    var tbody = table.tBodies[0];
    var search = document.getElementById('playerSearch');
    var empty = document.getElementById('noResults');
    var filter = 'all';

    function apply() {
        var q = search.value.trim().toLowerCase();
        var shown = 0;
        tbody.querySelectorAll('tr.player-row').forEach(function (r) {
            var id = r.cells[0].textContent.trim();
            var name = r.cells[1].dataset.v;
            var matchQ = !q || name.includes(q) || id === q.replace('#', '');
            var matchF = filter === 'all' || r.dataset[filter] === '1';
            var show = matchQ && matchF;
            r.style.display = show ? '' : 'none';
            if (show) shown++;
        });
        empty.classList.toggle('hidden', shown > 0);
    }

    search.addEventListener('input', apply);
    document.querySelectorAll('[data-filter]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            filter = btn.dataset.filter;
            document.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.toggle('on', b === btn); });
            apply();
        });
    });

    table.querySelectorAll('th[data-sort]').forEach(function (th, col) {
        th.addEventListener('click', function () {
            var dir = th.getAttribute('aria-sort') === 'ascending' ? 'descending' : 'ascending';
            table.querySelectorAll('th[data-sort]').forEach(function (h) { h.removeAttribute('aria-sort'); });
            th.setAttribute('aria-sort', dir);
            var numeric = th.dataset.sort === 'num';
            var rows = Array.from(tbody.querySelectorAll('tr.player-row'));
            rows.sort(function (a, b) {
                var x = a.cells[col].dataset.v, y = b.cells[col].dataset.v;
                var cmp = numeric ? (parseFloat(x) || 0) - (parseFloat(y) || 0) : x.localeCompare(y);
                return dir === 'ascending' ? cmp : -cmp;
            });
            rows.forEach(function (r) { tbody.insertBefore(r, empty); });
        });
    });
})();
</script>
<?= $this->endSection() ?>
