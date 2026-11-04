<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
    $weatherCondition = $weather ? ($weather['condition_name'] ?? $weather['condition'] ?? '') : '';
    $snowfall = $weather ? (int) ($weather['snowfall'] ?? 0) : 0;
    $seasons = [
        1 => ['Park City base', 'Green and blue terrain. The first lifts, ski patrol, and not going broke.'],
        2 => ['Advanced Peaks', 'The upper mountain: steep bowls, high ridges, black and double-black runs.'],
        3 => ['Beyond Park City', 'Backcountry, terrain parks, and a new mountain chosen by player vote.'],
    ];
    $diffClass = ['green' => 'green', 'blue' => 'blue', 'black' => 'black', 'double_black' => 'black'];
?>
<style>
.home-hero { position: relative; isolation: isolate; overflow: hidden; color: #fff; background: #141b24; }
.home-hero::before {
    content: ''; position: absolute; inset: 0; z-index: -2;
    background-image: url('/img/ParkCity_med.jpg'), url('/img/ParkCity_low.jpg');
    background-size: cover; background-position: center 35%; opacity: 0.75;
}
.home-hero::after {
    content: ''; position: absolute; inset: 0; z-index: -1;
    background: linear-gradient(90deg, rgba(10,14,20,0.92) 0%, rgba(10,14,20,0.78) 45%, rgba(10,14,20,0.35) 100%);
}
@media (max-width: 1023px) { .home-hero::after { background: rgba(10,14,20,0.82); } }
.home-hero h1 { font-family: var(--sign); font-weight: 700; font-size: clamp(2.125rem, 4.2vw, 3.25rem); line-height: 1.08; letter-spacing: -0.03em; margin: 0; max-width: 17ch; }
.home-hero .lede { font-size: 1.0625rem; line-height: 1.65; color: rgba(255,255,255,0.82); max-width: 33rem; }
.home-hero .note { display: inline-block; font-size: 0.875rem; color: rgba(255,255,255,0.85); text-decoration: underline; text-decoration-color: rgba(255,255,255,0.35); text-underline-offset: 4px; }
.home-hero .note:hover { color: #fff; text-decoration-color: #fff; }
.home-hero .small { font-size: 0.8125rem; color: rgba(255,255,255,0.62); }
.home-hero .ghost { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.3); color: #fff; }
.home-hero .ghost:hover { background: rgba(255,255,255,0.22); color: #fff; }

/* Snow report board */
.report { background: #f7f5f0; color: #1d232b; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.35); overflow: hidden; }
.report-head { display: flex; align-items: baseline; justify-content: space-between; padding: 0.85rem 1.1rem 0.7rem; background: #1d232b; color: #f7f5f0; }
.report-head h2 { font-family: var(--sign); font-size: 0.9375rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; line-height: 1; margin: 0; }
.report-head span { font-size: 0.75rem; opacity: 0.7; }
.report-big { display: grid; grid-template-columns: 1fr 1fr; border-bottom: 1px solid rgba(0,0,0,0.1); }
.report-big > div { padding: 0.9rem 1.1rem; }
.report-big > div + div { border-left: 1px solid rgba(0,0,0,0.1); }
.report-label { font-size: 0.75rem; color: #5b6470; }
.report-num { font-family: var(--sign); font-size: 2rem; font-weight: 600; letter-spacing: -0.03em; line-height: 1; margin-top: 0.15rem; font-variant-numeric: tabular-nums; }
.report-rows > div { display: flex; justify-content: space-between; gap: 1rem; padding: 0.6rem 1.1rem; font-size: 0.875rem; border-top: 1px solid rgba(0,0,0,0.08); }
.report-rows > div:first-child { border-top: 0; }
.report-rows dt { color: #5b6470; }
.report-rows dd { font-weight: 600; text-align: right; font-variant-numeric: tabular-nums; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Plain sections */
.home-section { padding: 4rem 0; }
.home-section h2 { font-family: var(--sign); font-size: 1.75rem; font-weight: 700; letter-spacing: -0.025em; line-height: 1.15; margin: 0; }
.prose-home p { font-size: 1rem; line-height: 1.7; color: color-mix(in oklab, var(--color-base-content) 80%, transparent); max-width: 38rem; }
.prose-home p + p { margin-top: 0.9rem; }
.muted { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.textlink { color: var(--color-primary); font-weight: 600; text-decoration: none; }
.textlink:hover { text-decoration: underline; text-underline-offset: 3px; }
/* Links that stand on their own (not inside a sentence) get a 44px tap target on touch screens */
@media (pointer: coarse) { .home-hero .note, .home-tap { display: inline-flex; align-items: center; min-height: 2.75rem; } }
.tsign { display: inline-block; width: 11px; height: 11px; flex-shrink: 0; }
.tsign.green { border-radius: 50%; background: #23a55a; }
.tsign.blue { background: #2f6fd6; }
.tsign.black { width: 9px; height: 9px; margin: 0 1px; background: var(--color-base-content); transform: rotate(45deg); }

/* Season 1 standings */
.standings { width: 100%; border-collapse: collapse; font-size: 0.9375rem; }
.standings th { text-align: left; font-size: 0.75rem; font-weight: 500; padding: 0 0 0.5rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.standings td { padding: 0.6rem 0; border-top: 1px solid var(--color-base-300); vertical-align: top; }
.standings .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.standings .pos { width: 2rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); font-variant-numeric: tabular-nums; }
.standings small { display: block; font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }

/* How a season goes */
.steps { counter-reset: step; list-style: none; margin: 0; padding: 0; }
.steps li { counter-increment: step; position: relative; padding: 0 0 1.1rem 2.25rem; font-size: 0.9875rem; line-height: 1.6; color: color-mix(in oklab, var(--color-base-content) 80%, transparent); }
.steps li::before { content: counter(step); position: absolute; left: 0; top: 0.05rem; font-family: var(--sign); font-weight: 700; font-size: 1rem; line-height: 1.6; color: var(--color-base-content); }
.steps b { color: var(--color-base-content); font-weight: 600; }
.map-frame { display: block; border-radius: 6px; overflow: hidden; border: 1px solid var(--color-base-300); }
.map-frame img { display: block; width: 100%; height: auto; }

/* Recently built */
.recent { list-style: none; margin: 0; padding: 0; }
.recent li { display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0; border-top: 1px solid var(--color-base-300); font-size: 0.9375rem; }
.recent li:first-child { border-top: 0; }
.recent .when { margin-left: auto; font-size: 0.8125rem; white-space: nowrap; }

/* Seasons */
.season-list { list-style: none; margin: 0; padding: 0; }
.season-list li { display: grid; grid-template-columns: 6.5rem 1fr; gap: 0.25rem 1.25rem; padding: 0.85rem 0; border-top: 1px solid var(--color-base-300); }
.season-list li:first-child { border-top: 0; }
.season-list .when { font-size: 0.8125rem; padding-top: 0.15rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.season-list .now .when { color: var(--color-primary); font-weight: 600; }
.season-list b { font-weight: 600; }
.season-list p { font-size: 0.9375rem; line-height: 1.55; margin-top: 0.15rem; color: color-mix(in oklab, var(--color-base-content) 72%, transparent); }

.home-cta { background: #1d232b; color: #f7f5f0; }
.home-cta p { font-size: 1.0625rem; color: rgba(247,245,240,0.82); }
</style>

<!-- Hero -->
<section class="home-hero">
    <div class="max-w-6xl mx-auto px-4 py-14 md:py-20 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
        <div class="lg:col-span-7 space-y-6">
            <?php if ($s2JustOpened) : ?>
            <a href="#season-2" class="note">Season 2 opened on <?= date('F j', $seasonStart) ?>. What's new →</a>
            <?php else : ?>
            <div class="small">Season <?= $currentSeasonNum ?>, day <?= $seasonDay ?> of <?= $seasonLen ?></div>
            <?php endif ?>
            <h1>Run a ski resort on a real mountain.</h1>
            <p class="lede">Ski Manager is a free management game that runs in your browser. You start with the Park City trail map, up to <?= currency(1000000) ?> depending on difficulty, and a <?= $seasonLen ?>-day season to make it pay.</p>
            <div class="flex flex-wrap items-center gap-3">
                <?php if (!auth()->loggedIn()) : ?>
                <a href="/register" class="btn btn-primary px-6">Play free</a>
                <a href="/demo" class="btn ghost">Try a demo mountain</a>
                <a href="/login" class="home-tap text-sm underline underline-offset-4 ml-1" style="color:rgba(255,255,255,0.8)">Sign in</a>
                <?php else : ?>
                <a href="/dashboard" class="btn btn-primary px-6">Go to your resort</a>
                <a href="/map" class="btn ghost">Open the trail map</a>
                <?php endif ?>
            </div>
            <p class="small"><?= number_format($playerCount) ?> players so far, <?= number_format($totalSlopes) ?> runs built.</p>
        </div>

        <div class="lg:col-span-5">
            <div class="report">
                <div class="report-head">
                    <h2>Snow report</h2>
                    <span>Park City · today</span>
                </div>
                <div class="report-big">
                    <div>
                        <div class="report-label">Summit</div>
                        <div class="report-num"><?= $weather ? esc($weather['temp_formatted']) : '–' ?></div>
                        <div class="text-xs mt-1" style="color:#5b6470"><?= esc($weatherCondition ?: '–') ?></div>
                    </div>
                    <div>
                        <div class="report-label">Base depth</div>
                        <div class="report-num"><?= isset($weather['snow_base']) ? snow($weather['snow_base']) : '–' ?></div>
                        <div class="text-xs mt-1" style="color:#5b6470"><?= $snowfall > 0 ? snow($snowfall) . ' new snow' : 'No new snow' ?></div>
                    </div>
                </div>
                <dl class="report-rows">
                    <div><dt>Season</dt><dd><?= $currentSeasonNum ?>, day <?= $seasonDay ?> of <?= $seasonLen ?></dd></div>
                    <div><dt>Runs built by players</dt><dd><?= number_format($totalSlopes) ?></dd></div>
                    <?php if ($topPlayer) : ?>
                    <div><dt>Richest resort</dt><dd><?= esc($topPlayer['username']) ?> · <?= currency((int) $topPlayer['cash']) ?></dd></div>
                    <?php endif ?>
                </dl>
            </div>
        </div>
    </div>
</section>

<?php if ($s2Live) : ?>
<!-- Season 2 note -->
<section id="season-2" class="home-section bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-12">
        <div class="lg:col-span-7 prose-home">
            <div class="text-sm muted mb-2"><?= date('F j, Y', $seasonStart) ?></div>
            <h2 class="mb-5">Season 2 is open</h2>
            <p>Sector 2, the upper part of Park City, is now on the trail map.<?php if ($s2Runs) : ?> It has <?= number_format($s2Runs) ?> runs and <?= number_format($s2Lifts) ?> lift<?= $s2Lifts === 1 ? '' : 's' ?>, and most of it is steep: <?= number_format($s2Steep) ?> of those runs are black or double black.<?php endif ?></p>
            <p>Everything you built in Season 1 is still there, and so is your cash. The final Season 1 standings have been saved, everyone who played got the Season 1 Pioneer badge, and the top ten got 50 Genepis each.</p>
            <p class="pt-1"><a href="<?= auth()->loggedIn() ? '/map' : '/register' ?>" class="textlink home-tap"><?= auth()->loggedIn() ? 'Open the trail map' : 'Start a resort' ?> →</a></p>
        </div>
        <?php if ($standings) : ?>
        <div class="lg:col-span-5">
            <h3 class="font-semibold mb-3">How Season 1 ended</h3>
            <table class="standings">
                <thead><tr><th></th><th>Player</th><th class="num">Cash</th></tr></thead>
                <tbody>
                <?php foreach ($standings as $__i => $__p) : ?>
                    <tr>
                        <td class="pos"><?= $__i + 1 ?></td>
                        <td><?= esc($__p['username']) ?><?php if ((int) $__p['total_lifts'] + (int) $__p['total_slopes'] > 0) : ?><small><?= (int) $__p['total_lifts'] ?> lift<?= (int) $__p['total_lifts'] === 1 ? '' : 's' ?>, <?= (int) $__p['total_slopes'] ?> run<?= (int) $__p['total_slopes'] === 1 ? '' : 's' ?></small><?php endif ?></td>
                        <td class="num"><?= currency((int) $__p['cash']) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <p class="text-sm mt-3"><a href="/leaderboard" class="textlink home-tap">Full leaderboard</a></p>
        </div>
        <?php endif ?>
    </div>
</section>
<?php endif ?>

<!-- How a season goes -->
<section class="home-section bg-base-200 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
        <div class="lg:col-span-5">
            <h2 class="mb-6">How a season goes</h2>
            <ol class="steps">
                <li><b>Build a lift.</b> Pick one of the lift lines on the trail map. A 1.6 km fixed-grip chair runs about <?= currency(240000) ?>; a gondola on the same line costs a lot more.</li>
                <li><b>Lay out runs under it.</b> They follow the real fall lines of the mountain, graded green through double black.</li>
                <li><b>Hire people.</b> Ski patrol, groomers, lift mechanics, instructors. Nothing runs without staff.</li>
                <li><b>Set ticket prices.</b> Charge too much and fewer people come.</li>
                <li><b>Watch the weather.</b> Make snow when it's cold enough, groom overnight, and try to still have money on day <?= $seasonLen ?>.</li>
            </ol>
            <p class="text-sm muted leading-relaxed">There's more once you're going: hotels and restaurants, loans and insurance, terrain parks, night skiing, energy and water bills, and a leaderboard.</p>
        </div>
        <div class="lg:col-span-7">
            <a href="<?= auth()->loggedIn() ? '/map' : '/register' ?>" class="map-frame">
                <img src="/img/ParkCity_low.jpg" alt="The Park City trail map the game is played on" width="600" height="340" loading="lazy" decoding="async">
            </a>
            <p class="text-xs muted mt-2">The Park City trail map you build on. Map by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="underline">Mapsynergy</a>.</p>
        </div>
    </div>
</section>

<?php if ($recent) : ?>
<!-- Recently built -->
<section class="home-section bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-12">
        <div class="lg:col-span-5">
            <h2 class="mb-3">Recently built</h2>
            <p class="muted">What other players have been putting on their mountains.</p>
        </div>
        <div class="lg:col-span-7">
            <ul class="recent">
                <?php foreach ($recent as $__b) : ?>
                <li>
                    <?php if ($__b['item_type'] === 'lift') : ?>
                        <i class="fa-solid fa-cable-car muted text-sm w-3 text-center" aria-hidden="true"></i>
                    <?php else : ?>
                        <span class="tsign <?= $diffClass[$__b['difficulty'] ?? ''] ?? 'blue' ?>" aria-hidden="true"></span>
                    <?php endif ?>
                    <span class="min-w-0 truncate"><b class="font-semibold"><?= esc($__b['username']) ?></b> built <?= esc($__b['name']) ?></span>
                    <span class="when muted"><?= timeAgo($__b['created_at']) ?></span>
                </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</section>
<?php endif ?>

<!-- Seasons -->
<section class="home-section bg-base-200 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-12 gap-12">
        <div class="lg:col-span-5">
            <h2 class="mb-3">Seasons</h2>
            <p class="muted">Each season opens more of the mountain. What you built carries over.</p>
        </div>
        <div class="lg:col-span-7">
            <ul class="season-list">
                <?php foreach ($seasons as $__n => [$__title, $__text]) :
                    $__isNow = $__n === 3 ? $currentSeasonNum >= 3 : $__n === $currentSeasonNum;
                    $__when  = $__isNow ? 'Now, day ' . $seasonDay : ($__n < $currentSeasonNum ? 'Finished' : 'Later');
                ?>
                <li class="<?= $__isNow ? 'now' : '' ?>">
                    <div class="when">Season <?= $__n ?><?= $__n === 3 ? '+' : '' ?><br><?= $__when ?></div>
                    <div>
                        <b><?= esc($__title) ?></b>
                        <p><?= esc($__text) ?><?php if ($__n === 3) : ?> <a href="<?= auth()->loggedIn() ? '/vote' : '/register' ?>" class="textlink">Vote on it</a><?php endif ?></p>
                    </div>
                </li>
                <?php endforeach ?>
            </ul>
        </div>
    </div>
</section>

<!-- Call to action -->
<section class="home-cta">
    <div class="max-w-6xl mx-auto px-4 py-12 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <?php if (auth()->loggedIn()) : ?>
        <p>Season <?= $currentSeasonNum ?> has <?= $daysRemaining ?> days left.</p>
        <a href="/dashboard" class="btn btn-primary px-8">Go to your resort</a>
        <?php else : ?>
        <p>It's free, and you can try a demo mountain without making an account.</p>
        <div class="flex items-center gap-4">
            <a href="/register" class="btn btn-primary px-8">Play free</a>
            <a href="/demo" class="home-tap text-sm underline underline-offset-4" style="color:rgba(247,245,240,0.8)">Try the demo</a>
        </div>
        <?php endif ?>
    </div>
</section>

<?= $this->endSection() ?>
