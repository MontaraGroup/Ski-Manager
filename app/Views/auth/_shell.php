<?php
/**
 * Shared styles + trail-map side panel for the login and register pages.
 *
 * @var string $variant 'login' or 'register'
 */
$__isRegister = ($variant ?? 'login') === 'register';
$__weather = function_exists('getCurrentWeather') ? getCurrentWeather() : null;
$__season = function_exists('getSeasonNumber') ? (int) getSeasonNumber() : 1;
// The tick's day (what the navbar shows), not the calendar-based getSeasonDay()
$__seasonDay = !empty($__weather['game_day']) ? (int) $__weather['game_day'] : null;
$__players = db_connect()->table('users')->where('id !=', 1)->countAllResults();
?>
<style>
.auth-wrap { min-height: calc(100vh - 3.5rem); display: flex; align-items: center; justify-content: center; padding: 2rem 1rem 3rem; }
.auth-card { width: 100%; max-width: 64rem; display: grid; grid-template-columns: 1fr; border-radius: 12px; overflow: hidden; background: var(--color-base-100); border: 1px solid var(--color-base-300); box-shadow: 0 20px 50px rgba(0,0,0,0.25); }
@media (min-width: 1024px) { .auth-card { grid-template-columns: 1fr 1fr; } }

/* Trail map panel, same treatment as the homepage hero */
.auth-side { position: relative; isolation: isolate; overflow: hidden; color: #fff; background: #141b24; display: flex; flex-direction: column; justify-content: space-between; gap: 1.5rem; padding: 1.5rem; min-height: 9rem; }
.auth-side::before { content: ''; position: absolute; inset: 0; z-index: -2; background-image: url('/img/ParkCity_med.jpg'), url('/img/ParkCity_low.jpg'); background-size: cover; background-position: center 35%; opacity: 0.8; transform: scale(1.03); }
.auth-side::after { content: ''; position: absolute; inset: 0; z-index: -1; background: linear-gradient(180deg, rgba(10,14,20,0.35) 0%, rgba(10,14,20,0.6) 45%, rgba(10,14,20,0.92) 100%); }
@media (min-width: 1024px) { .auth-side { padding: 2.25rem; min-height: 38rem; } }
.auth-brand { display: inline-flex; align-items: center; gap: 0.55rem; color: #fff; text-decoration: none; }
.auth-brand svg { width: 30px; height: 30px; }
.auth-brand span { font-family: var(--sign); font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; line-height: 1; }
.auth-side h2 { font-family: var(--sign); font-weight: 700; font-size: 2.125rem; letter-spacing: -0.03em; line-height: 1.08; margin: 0 0 0.75rem; text-shadow: 0 1px 2px rgba(0,0,0,0.4); }
.auth-side h2 em { font-style: normal; color: #7fb4ff; }
.auth-points { list-style: none; margin: 0; padding: 0; display: grid; gap: 0.6rem; font-size: 0.9375rem; color: rgba(255,255,255,0.88); }
.auth-points li { display: flex; align-items: center; gap: 0.7rem; }
.auth-facts { display: flex; flex-wrap: wrap; gap: 0.35rem 1.25rem; font-size: 0.8125rem; color: rgba(255,255,255,0.7); padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.18); }
.auth-facts b { color: #fff; font-weight: 600; }
/* Trail-difficulty markers as bullets */
.tsign { display: inline-block; flex-shrink: 0; width: 13px; height: 13px; }
.tsign.green { border-radius: 50%; background: #23a55a; }
.tsign.blue { background: #2f6fd6; }
.tsign.black { width: 11px; height: 11px; margin: 0 1px; background: #fff; transform: rotate(45deg); }
@media (max-width: 1023px) { .auth-side .auth-body, .auth-side .auth-facts { display: none; } .auth-side { justify-content: flex-end; } }

.auth-form { padding: 1.75rem 1.5rem 2rem; }
@media (min-width: 640px) { .auth-form { padding: 2.25rem 2.5rem 2.5rem; } }
.auth-form-inner { max-width: 24rem; margin: 0 auto; }
.auth-form h1 { font-family: var(--sign); font-weight: 700; font-size: 1.75rem; letter-spacing: -0.025em; line-height: 1.15; margin: 0; }
.auth-sub { font-size: 0.875rem; margin: 0.4rem 0 1.5rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.auth-field { display: block; }
.auth-field > span { display: flex; justify-content: space-between; align-items: baseline; font-size: 0.8125rem; font-weight: 500; margin-bottom: 0.35rem; }
.auth-field > span a { font-weight: 500; }
.auth-hint { font-size: 0.75rem; margin-top: 0.35rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.auth-pw { position: relative; }
.auth-pw .input { padding-right: 3rem; }
.auth-pw button { position: absolute; right: 0.35rem; top: 50%; transform: translateY(-50%); width: 2.25rem; height: 2.25rem; border-radius: 0.5rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.auth-pw button:hover, .auth-pw button:focus-visible { color: var(--color-base-content); background: color-mix(in oklab, var(--color-base-content) 8%, transparent); }
.auth-or { display: flex; align-items: center; gap: 0.75rem; margin: 1.25rem 0; font-size: 0.75rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.auth-or::before, .auth-or::after { content: ''; flex: 1; height: 1px; background: var(--color-base-300); }
.auth-social { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.auth-switch { text-align: center; font-size: 0.875rem; margin-top: 1.5rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }

/* Difficulty picker */
.diff { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; }
.diff label { cursor: pointer; }
.diff .opt { border: 1.5px solid var(--color-base-300); border-radius: 0.6rem; padding: 0.6rem 0.4rem; text-align: center; transition: border-color .15s, background-color .15s; height: 100%; }
.diff .opt:hover { border-color: color-mix(in oklab, var(--color-base-content) 35%, transparent); }
.diff .opt b { display: flex; align-items: center; justify-content: center; gap: 0.4rem; font-size: 0.8125rem; }
.diff .opt .cash { font-family: var(--sign); font-size: 0.9375rem; font-weight: 600; margin-top: 0.2rem; font-variant-numeric: tabular-nums; }
.diff .opt small { display: block; font-size: 0.6875rem; color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.diff input:checked + .opt { border-color: var(--color-primary); background: color-mix(in oklab, var(--color-primary) 12%, transparent); }
.diff input:focus-visible + .opt { outline: 2px solid var(--color-primary); outline-offset: 2px; }
.diff .tsign.black { background: var(--color-base-content); }
</style>

<aside class="auth-side">
    <a href="/" class="auth-brand" aria-label="Ski Manager home">
        <svg viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="6" fill="#1d232b"/><path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0"/><path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35"/><path d="M20 9 L19 25" stroke="#2f6fd6" stroke-width="1.6" stroke-linecap="round"/></svg>
        <span>Ski Manager</span>
    </a>
    <div class="auth-body">
        <?php if ($__isRegister) : ?>
            <h2>Run your own<br><em>ski resort.</em></h2>
            <ul class="auth-points">
                <li><span class="tsign green"></span>Start with cash for your first lifts and runs</li>
                <li><span class="tsign blue"></span>Hire staff, set ticket prices, keep the books balanced</li>
                <li><span class="tsign black"></span>Real weather decides how good each season is</li>
            </ul>
        <?php else : ?>
            <h2>Back to the<br><em>mountain.</em></h2>
            <ul class="auth-points">
                <li><span class="tsign green"></span>Check today's snow and open your runs</li>
                <li><span class="tsign blue"></span>See how your resort did overnight</li>
                <li><span class="tsign black"></span>Climb the leaderboard before the season ends</li>
            </ul>
        <?php endif ?>
    </div>
    <div class="auth-facts">
        <span>Season <b><?= $__season ?></b><?= $__seasonDay ? ' · day <b>' . $__seasonDay . '</b>' : '' ?></span>
        <?php if (!empty($__weather['condition_name'])) : ?><span>Park City: <b><?= esc($__weather['condition_name']) ?></b>, <?= temp((int) $__weather['temp']) ?></span><?php endif ?>
        <?php if ($__players >= 10) : ?><span><b><?= number_format($__players) ?></b> managers</span><?php endif ?>
        <span>Free, in your browser</span>
    </div>
</aside>
