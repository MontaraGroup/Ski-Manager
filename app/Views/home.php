<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski Manager - Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>
<?= $this->section('content') ?>
<!-- Season 1 Countdown -->
<div id="s1countdown" class="bg-gradient-to-r from-primary to-info text-primary-content">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-3">
            <span class="text-2xl"><i class="fa-solid fa-mountain-sun"></i></span>
            <div>
                <span class="font-bold">Season 1: Park City</span>
                <span class="text-sm opacity-80 ml-2" id="s1timer"></span>
            </div>
        </div>
        <a href="/register" class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-primary">Join Season 1</a>
    </div>
</div>
<script>
(function(){
    var launch = new Date("2026-06-06T04:00:00Z").getTime();
    var el = document.getElementById("s1timer");
    var wrap = document.getElementById("s1countdown");
    function tick(){
        var now = Date.now();
        var diff = launch - now;
        if(diff <= 0){ el.innerHTML = "<i class="fa-solid fa-mountain-sun"></i> Season 1 is LIVE - Sector 1 open at Park City"; el.style.fontWeight = "bold"; return; }
        var h = Math.floor(diff/3600000);
        var m = Math.floor((diff%3600000)/60000);
        var s = Math.floor((diff%60000)/1000);
        el.textContent = "Launches in " + h + "h " + m + "m " + s + "s";
        setTimeout(tick, 1000);
    }
    if(Date.now() >= launch){ el.innerHTML = "<i class="fa-solid fa-mountain-sun"></i> Season 1 is LIVE - Sector 1 open at Park City"; el.style.fontWeight = "bold"; } else { tick(); }
})();
</script>

<?php
    $db = db_connect();
    $playerCount = $db->table('users')->countAllResults(false);
    $totalSlopes = $db->table('player_items')->where('item_type', 'slope')->countAllResults(false);
    $totalStaff = $db->table('staff')->where('status', 'active')->countAllResults(false);
    $topPlayer = $db->query("SELECT u.username, pf.cash FROM player_finances pf JOIN users u ON u.id = pf.user_id WHERE u.id != 1 ORDER BY pf.cash DESC LIMIT 1")->getRowArray();
    $recentPlayer = $db->table('users')->where('id !=', 1)->orderBy('created_at', 'DESC')->limit(1)->get()->getRowArray();
    $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
    $weather = $db->table('weather')->where('game_day', $gameDay)->get()->getRowArray();
?>

<!-- Hero -->
<div class="min-h-[70vh] flex items-center bg-gradient-to-br from-base-300 via-base-200 to-base-100 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 relative">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-center">
            <div class="lg:col-span-3">
                <div class="badge badge-primary gap-1 mb-4"><i class="fa-solid fa-circle animate-pulse-soft text-xs"></i> Season 1 is Live</div>
                <h1 class="text-4xl md:text-6xl font-black leading-[1.05] mb-5">Build the resort<br>everyone talks about.</h1>
                <p class="text-lg text-base-content/60 mb-8 max-w-lg leading-relaxed">Start with an empty mountain and €500,000. Build lifts, hire staff, manage snowmaking, and survive <?= getSeasonLength() ?> days without going bankrupt - hire the wrong staff, skip the snow machines, ignore the government, and you're bankrupt by Day 10.</p>
                <div class="flex gap-3 flex-wrap mb-6">
                    <a href="/register" class="btn btn-primary btn-lg gap-2 shadow-lg"><i class="fa-solid fa-play"></i> Play Free - Takes 30 Seconds</a>
                </div>
                <div class="flex items-center gap-4 text-xs text-base-content/50">
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No downloads</span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No pay-to-win</span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No credit card</span>
                </div>
            </div>

            <!-- Live feed card -->
            <div class="lg:col-span-2 hidden lg:block">
                <div class="card bg-base-100 shadow-xl">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-success"></span></span>
                            <span class="text-xs font-semibold text-success">Live - Day <?= $gameDay ?></span>
                        </div>
                        <?php if ($weather) : ?>
                        <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg mb-3">
                            <i class="fa-solid fa-<?= $weather['temp'] <= -5 ? 'snowflake text-info' : 'cloud-sun text-warning' ?> text-2xl"></i>
                            <div>
                                <div class="font-bold"><?= temp((int)$weather['temp']) ?> · <?= $weather['condition_name'] ?></div>
                                <div class="text-xs text-base-content/50">Today on the mountain</div>
                            </div>
                        </div>
                        <?php endif ?>
                        <?php if ($topPlayer) : ?>
                        <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg mb-3">
                            <i class="fa-solid fa-crown text-warning text-xl"></i>
                            <div>
                                <div class="font-bold text-sm"><?= esc($topPlayer['username']) ?></div>
                                <div class="text-xs text-base-content/50">Richest resort - <?= currency((int) $topPlayer['cash']) ?></div>
                            </div>
                        </div>
                        <?php endif ?>
                        <?php if ($recentPlayer) : ?>
                        <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
                            <i class="fa-solid fa-user-plus text-primary text-xl"></i>
                            <div>
                                <div class="font-bold text-sm"><?= esc($recentPlayer['username']) ?></div>
                                <div class="text-xs text-base-content/50">Just joined <?= timeAgo($recentPlayer['created_at']) ?></div>
                            </div>
                        </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Urgency bar -->
<section class="py-4 bg-warning text-warning-content">
    <div class="max-w-5xl mx-auto px-4 text-center text-sm font-semibold">
        <i class="fa-solid fa-clock mr-1"></i> Season <?= (int) ceil($gameDay / getSeasonLength()) ?> is live - Day <?= getSeasonDay() ?>/<?= getSeasonLength() ?>. The mountain won't wait for you.
    </div>
</section>

<!-- The hook - show what they're missing -->
<section class="py-12 px-4 bg-base-100">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-3">What will you build?</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors">
                <div class="card-body text-center">
                    <div class="text-4xl mb-3">🏔️</div>
                    <h3 class="font-bold mb-2">Opening new slopes</h3>
                    <p class="text-sm text-base-content/60">Drawing runs on an interactive map, choosing difficulty ratings, and watching visitors pour in. <?= $totalSlopes ?> slopes built so far.</p>
                </div>
            </div>
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors">
                <div class="card-body text-center">
                    <div class="text-4xl mb-3">💰</div>
                    <h3 class="font-bold mb-2">Making serious money</h3>
                    <p class="text-sm text-base-content/60">Hotels, restaurants, parking fees, ticket sales. The top player has <?= $topPlayer ? currency((int) $topPlayer['cash']) : '€500,000+' ?>. Can you beat that?</p>
                </div>
            </div>
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors">
                <div class="card-body text-center">
                    <div class="text-4xl mb-3">❄️</div>
                    <h3 class="font-bold mb-2">Fighting the weather</h3>
                    <p class="text-sm text-base-content/60">Today it's <?= $weather ? temp((int)$weather['temp']) . ' and ' . strtolower($weather['condition_name']) : 'cold' ?>. Some are turning on snow machines. Others are panicking.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What makes it different -->
<section class="py-16 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-3">This isn't a clicker game</h2>
            <p class="text-base-content/60 max-w-lg mx-auto">A deep ski resort tycoon simulator where every system is connected. Hire too many staff? Your expenses spike. Skip insurance? One accident costs everything. The most detailed browser-based ski management game available.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-2"><i class="fa-solid fa-map text-primary text-xl"></i></div>
                <div class="text-sm font-bold">Trail Map</div>
                <div class="text-xs text-base-content/50">Draw and build your resort</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center mb-2"><i class="fa-solid fa-users text-warning text-xl"></i></div>
                <div class="text-sm font-bold">10 Staff Roles</div>
                <div class="text-xs text-base-content/50">Patrol, groomers, chefs...</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center mb-2"><i class="fa-solid fa-cloud-sun text-info text-xl"></i></div>
                <div class="text-sm font-bold">Dynamic Weather</div>
                <div class="text-xs text-base-content/50">Changes every day</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center mb-2"><i class="fa-solid fa-coins text-success text-xl"></i></div>
                <div class="text-sm font-bold">Economy Simulation</div>
                <div class="text-xs text-base-content/50">Loans, insurance, regulations</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-error/10 flex items-center justify-center mb-2"><i class="fa-solid fa-snowflake text-error text-xl"></i></div>
                <div class="text-sm font-bold">Real Brands</div>
                <div class="text-xs text-base-content/50">PistenBully, TechnoAlpin</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center mb-2"><i class="fa-solid fa-person-snowboarding text-secondary text-xl"></i></div>
                <div class="text-sm font-bold">Terrain Parks</div>
                <div class="text-xs text-base-content/50">Halfpipes, rail gardens</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center mb-2"><i class="fa-solid fa-trophy text-warning text-xl"></i></div>
                <div class="text-sm font-bold">Leaderboard</div>
                <div class="text-xs text-base-content/50">Compete globally</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-2"><i class="fa-solid fa-bolt text-primary text-xl"></i></div>
                <div class="text-sm font-bold">Energy & Water</div>
                <div class="text-xs text-base-content/50">Manage your resources</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center mb-2"><i class="fa-solid fa-building-columns text-accent text-xl"></i></div>
                <div class="text-sm font-bold">Government</div>
                <div class="text-xs text-base-content/50">Regulations and compliance</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-error/10 flex items-center justify-center mb-2"><i class="fa-solid fa-gauge text-error text-xl"></i></div>
                <div class="text-sm font-bold">3 Difficulty Modes</div>
                <div class="text-xs text-base-content/50">Easy, Standard, Hard</div>
            </div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center">
                <div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center mb-2"><i class="fa-solid fa-star text-warning text-xl"></i></div>
                <div class="text-sm font-bold">55 Achievements</div>
                <div class="text-xs text-base-content/50">Unlock features as you grow</div>
            </div></div>
        </div>
    </div>
</section>


<!-- Mid-page CTA -->
<section class="py-10 px-4 bg-base-200">
    <div class="max-w-xl mx-auto text-center">
        <h2 class="text-xl font-bold mb-2">Ready to manage your own resort?</h2>
        <p class="text-sm text-base-content/60 mb-4">Free to play. No downloads. Start building in 30 seconds.</p>
        <?php if (!auth()->loggedIn()) : ?>
            <a href="<?= url_to('register') ?>" class="btn btn-primary gap-2"><i class="fa-solid fa-rocket"></i> Start Your Resort Free</a>
        <?php endif ?>
    </div>
</section>

<!-- Social proof -->
<section class="py-12 px-4 bg-base-100">
    <div class="max-w-3xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div class="card bg-base-200/50 border border-base-300">
                <div class="card-body">
                    <i class="fa-solid fa-heart text-error text-2xl mb-2"></i>
                    <h3 class="font-bold">100% Free</h3>
                    <p class="text-xs text-base-content/60">No paywalls, no pay-to-win. Earn everything by playing.</p>
                </div>
            </div>
            <div class="card bg-base-200/50 border border-base-300">
                <div class="card-body">
                    <i class="fa-solid fa-globe text-info text-2xl mb-2"></i>
                    <h3 class="font-bold">Instant Play</h3>
                    <p class="text-xs text-base-content/60">Browser-based. No downloads. Works on any device.</p>
                </div>
            </div>
            <div class="card bg-base-200/50 border border-base-300">
                <div class="card-body">
                    <i class="fa-solid fa-code-branch text-success text-2xl mb-2"></i>
                    <h3 class="font-bold">Open Source</h3>
                    <p class="text-xs text-base-content/60">Built in the open on <a href="https://gitlab.com/contact1231/skimanager-v2" target="_blank" rel="noopener noreferrer" class="link link-primary">GitLab</a>.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA -->
<section class="py-16 px-4 bg-gradient-to-br from-primary to-secondary text-primary-content relative overflow-hidden mb-0" style="margin-bottom:0;">
    <div class="max-w-xl mx-auto text-center relative">
        <?php if (auth()->loggedIn()) : ?>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Get back to your resort.</h2>
        <p class="text-lg" style="opacity:0.8;">Your mountain needs you. Check in and keep building.</p>
        <a href="/dashboard" class="btn btn-lg gap-2 shadow-xl mt-6" style="background:white;color:#2563eb;"><i class="fa-solid fa-gauge-high"></i> Go to Dashboard</a>
        <?php else : ?>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Your resort is waiting.</h2>
        <p class="text-lg opacity-80 mb-8">€500,000 starting cash. An empty mountain. What you build is up to you.</p>
        <a href="/register" class="btn btn-lg gap-2 shadow-xl" style="background:white;color:#2563eb;"><i class="fa-solid fa-play"></i> Start Building Now</a>
        <?php endif ?>
        <div class="mt-6 text-sm" style="opacity:0.6;">
            Already playing? <a href="/login" class="underline hover:opacity-100">Sign in</a>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
