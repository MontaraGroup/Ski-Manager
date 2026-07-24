<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski Manager - Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>
<?= $this->section('content') ?>

<?php
    $db = db_connect();
    $playerCount = $db->table('users')->where('id !=', 1)->countAllResults(false);
    $totalSlopes = $db->table('player_items')->where('item_type', 'slope')->countAllResults(false);
    $topPlayer = $db->query("SELECT u.username, pf.cash FROM player_finances pf JOIN users u ON u.id = pf.user_id WHERE u.id != 1 ORDER BY pf.cash DESC LIMIT 1")->getRowArray();
    $recentPlayer = $db->table('users')->where('id !=', 1)->orderBy('created_at', 'DESC')->limit(1)->get()->getRowArray();
    $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
    $weather = $db->table('weather')->where('game_day', $gameDay)->get()->getRowArray();
    $playersLabel = $playerCount >= 10 ? number_format($playerCount) . '+ managers building right now' : 'Be one of the first to build';

    $currentSeasonNum = (int) getSeasonNumber();
    $seasonDay = (int) getSeasonDay();
    $seasonLen = (int) getSeasonLength();
    $seasonProgressPct = min(100, max(0, (int) round(($seasonDay / max(1, $seasonLen)) * 100)));
    $daysRemaining = max(0, $seasonLen - $seasonDay);
?>

<div class="bg-gradient-to-r from-primary to-info text-primary-content">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="relative flex h-2.5 w-2.5"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span><span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span></span>
            <span class="font-bold">Season <?= getSeasonNumber() ?> is live</span>
            <span class="opacity-80 text-sm">Day <?= getSeasonDay() ?>/<?= getSeasonLength() ?> at Park City</span>
        </div>
        <?php if (!auth()->loggedIn()) : ?>
        <a href="/register" class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-primary gap-1"><i class="fa-solid fa-play"></i> Join Season <?= getSeasonNumber() ?></a>
        <?php else : ?>
        <a href="/dashboard" class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-primary gap-1"><i class="fa-solid fa-gauge-high"></i> Your Dashboard</a>
        <?php endif ?>
    </div>
</div>

<div class="min-h-[70vh] flex items-center bg-gradient-to-br from-base-300 via-base-200 to-base-100 relative overflow-hidden">
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 relative">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-center">
            <div class="lg:col-span-3">
                <div class="badge badge-primary gap-1 mb-4"><i class="fa-solid fa-clock text-xs"></i> Season <?= getSeasonNumber() ?> · Day <?= getSeasonDay() ?></div>
                <h1 class="text-4xl md:text-6xl font-black leading-[1.05] mb-5">Build the resort<br>everyone talks about.</h1>
                <p class="text-lg text-base-content/60 mb-8 max-w-lg leading-relaxed">Start with an empty mountain and up to <?= currency(1000000) ?> in cash. Build lifts, hire staff, manage snowmaking, and survive <?= getSeasonLength() ?> days without going bankrupt - hire the wrong staff, skip the snow machines, ignore the government, and you're done by Day 10.</p>
                <div class="flex gap-3 flex-wrap mb-6">
                    <?php if (!auth()->loggedIn()) : ?>
                    <a href="/register" class="btn btn-primary btn-lg gap-2 shadow-lg"><i class="fa-solid fa-play"></i> Play Free - Takes 30 Seconds</a>
                    <?php else : ?>
                    <a href="/dashboard" class="btn btn-primary btn-lg gap-2 shadow-lg"><i class="fa-solid fa-gauge-high"></i> Go to Your Resort</a>
                    <?php endif ?>
                </div>
                <div class="flex items-center gap-4 text-xs text-base-content/50 flex-wrap">
                    <span><i class="fa-solid fa-users text-primary mr-1"></i><?= $playersLabel ?></span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No downloads</span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No pay-to-win</span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No credit card</span>
                </div>
            </div>

            <div class="lg:col-span-2">
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

<section class="py-12 px-4 bg-base-100">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-8"><h2 class="text-3xl font-bold mb-3">What will you build?</h2></div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors"><div class="card-body text-center">
                <div class="text-4xl mb-3">🏔️</div>
                <h3 class="font-bold mb-2">Opening new slopes</h3>
                <p class="text-sm text-base-content/60">Drawing runs on an interactive map, choosing difficulty ratings, and watching visitors pour in. <?= number_format($totalSlopes) ?> slopes built so far.</p>
            </div></div>
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors"><div class="card-body text-center">
                <div class="text-4xl mb-3">💰</div>
                <h3 class="font-bold mb-2">Making serious money</h3>
                <p class="text-sm text-base-content/60">Hotels, restaurants, parking fees, ticket sales. The top player has <?= $topPlayer ? currency((int) $topPlayer['cash']) : currency(500000) ?>. Can you beat that?</p>
            </div></div>
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors"><div class="card-body text-center">
                <div class="text-4xl mb-3">❄️</div>
                <h3 class="font-bold mb-2">Fighting the weather</h3>
                <p class="text-sm text-base-content/60">Today it's <?= $weather ? temp((int)$weather['temp']) . ' and ' . strtolower($weather['condition_name']) : 'cold' ?>. Some are turning on snow machines. Others are panicking.</p>
            </div></div>
        </div>
    </div>
</section>

<section class="py-12 px-4 bg-base-200">
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-3">Real resort maps</h2>
                <p class="text-base-content/60 mb-4">Build on real trail maps from top ski resorts. Draw slopes, place lifts, and watch your mountain come to life.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="badge badge-outline">Park City</span>
                    <span class="badge badge-outline">Deer Valley</span>
                    <span class="badge badge-outline">Vail</span>
                    <span class="badge badge-outline">Aspen</span>
                    <span class="badge badge-outline">Big Sky</span>
                    <span class="badge badge-outline">Palisades Tahoe</span>
                    <span class="badge badge-outline">Killington</span>
                </div>
                <p class="text-xs text-base-content/40">Maps by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="link">Mapsynergy</a></p>
            </div>
            <a href="<?= auth()->loggedIn() ? '/map' : '/register' ?>" class="block">
                <img src="/img/ParkCity_low.jpg" alt="Park City Trail Map" class="rounded-xl shadow-lg w-full hover:scale-[1.02] transition-transform" loading="lazy">
            </a>
        </div>
    </div>
</section>

<section class="py-16 px-4 bg-base-100">
    <div class="max-w-6xl mx-auto">
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-semibold mb-3">
                <i class="fa-solid fa-route text-xs"></i> Mountain Expansion Roadmap
            </div>
            <h2 class="text-3xl md:text-4xl font-extrabold mb-3">Season Progression Roadmap</h2>
            <p class="text-base-content/60 max-w-xl mx-auto text-sm md:text-base">
                Each season unlocks new mountain sectors, steeper terrain, and advanced infrastructure. Carry your resort legacy forward across every milestone.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 relative">

            <div class="card bg-base-100 border-2 border-primary shadow-xl hover:shadow-2xl transition-all duration-300 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 right-0 bg-primary text-primary-content text-[10px] font-extrabold uppercase px-3 py-1 rounded-bl-lg tracking-wider flex items-center gap-1.5 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                    Live Now
                </div>
                <div class="card-body p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-lg">
                            1
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Season 1</h3>
                            <span class="text-xs font-semibold text-primary">Sector 1 · Park City Base</span>
                        </div>
                    </div>

                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        The founding season. Establish beginner and intermediate runs, build your core lift network, recruit key staff, and navigate unpredictable alpine weather.
                    </p>

                    <div class="p-3.5 bg-base-200/80 rounded-xl border border-base-300 mb-4 space-y-2">
                        <div class="flex justify-between items-center text-xs font-bold">
                            <span class="text-base-content/70 flex items-center gap-1">
                                <i class="fa-solid fa-flag-checkered text-primary"></i> Season Progress
                            </span>
                            <span class="text-primary font-mono"><?= $seasonProgressPct ?>%</span>
                        </div>
                        <progress class="progress progress-primary w-full h-2" value="<?= getSeasonDay() ?>" max="<?= getSeasonLength() ?>"></progress>
                        <div class="flex justify-between items-center text-[11px] text-base-content/60 font-medium">
                            <span>Day <?= getSeasonDay() ?> of <?= getSeasonLength() ?></span>
                            <span class="badge badge-sm badge-outline font-mono"><?= $daysRemaining ?> days left</span>
                        </div>
                    </div>

                    <div class="space-y-2 text-xs text-base-content/80 pt-2 border-t border-base-200">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-success mt-0.5 shrink-0"></i>
                            <span><strong>Terrain:</strong> Beginner & Intermediate Slopes</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-success mt-0.5 shrink-0"></i>
                            <span><strong>Infrastructure:</strong> Double Lifts, Snowmaking, Grooming</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-circle-check text-success mt-0.5 shrink-0"></i>
                            <span><strong>Objective:</strong> Survive <?= getSeasonLength() ?> days & maximize profit</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-base-200/40 border-t border-base-200 text-center">
                    <?php if (!auth()->loggedIn()): ?>
                    <a href="/register" class="btn btn-primary btn-sm w-full gap-2">
                        <i class="fa-solid fa-play text-xs"></i> Join Season 1 Free
                    </a>
                    <?php else: ?>
                    <a href="/dashboard" class="btn btn-primary btn-sm w-full gap-2">
                        <i class="fa-solid fa-mountain text-xs"></i> Manage Your Resort
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 right-0 bg-base-300 text-base-content/70 text-[10px] font-extrabold uppercase px-3 py-1 rounded-bl-lg tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-lock text-[10px]"></i> Upcoming
                </div>
                <div class="card-body p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-base-200 text-base-content/70 flex items-center justify-center font-bold text-lg">
                            2
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Season 2</h3>
                            <span class="text-xs font-semibold text-base-content/50">Sector 2 · Advanced Peaks</span>
                        </div>
                    </div>

                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        The mountain expands into steep bowls and high-altitude peaks. Handle advanced skier crowds, high-speed express gondolas, and severe winter storm events.
                    </p>

                    <div class="p-3.5 bg-base-200/50 rounded-xl border border-base-300/80 mb-4 text-center">
                        <span class="text-xs font-bold text-base-content/60 flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-hourglass-half text-warning"></i> Unlocks after Season 1
                        </span>
                        <p class="text-[11px] text-base-content/50 mt-1">All Season 1 resorts & cash carry forward</p>
                    </div>

                    <div class="space-y-2 text-xs text-base-content/80 pt-2 border-t border-base-200">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-mountain text-info mt-0.5 shrink-0"></i>
                            <span><strong>Terrain:</strong> Black Diamond Runs & High Bowls</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-cable-car text-info mt-0.5 shrink-0"></i>
                            <span><strong>Infrastructure:</strong> Express Gondolas & Luxury Lodges</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-arrow-right-rotate text-info mt-0.5 shrink-0"></i>
                            <span><strong>Progression:</strong> Seamless progress carryover</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-base-200/40 border-t border-base-200 text-center">
                    <button class="btn btn-sm btn-ghost btn-disabled w-full gap-1 text-xs">
                        <i class="fa-solid fa-lock text-xs"></i> Locked (Season 1 In Progress)
                    </button>
                </div>
            </div>

            <div class="card bg-base-100 border border-base-300 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col justify-between">
                <div class="absolute top-0 right-0 bg-accent/20 text-accent text-[10px] font-extrabold uppercase px-3 py-1 rounded-bl-lg tracking-wider flex items-center gap-1.5">
                    <i class="fa-solid fa-check-to-slot text-[10px]"></i> Community Vote
                </div>
                <div class="card-body p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-accent/10 text-accent flex items-center justify-center font-bold text-lg">
                            3+
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Season 3 & Beyond</h3>
                            <span class="text-xs font-semibold text-accent">Full Mountain & Global Destinations</span>
                        </div>
                    </div>

                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        Conquer the entire Park City resort complex before voting on new global destinations like Aspen, Vail, or European alpine passes in Season 4!
                    </p>

                    <div class="p-3.5 bg-accent/5 rounded-xl border border-accent/20 mb-4 text-center">
                        <span class="text-xs font-bold text-accent flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-vote-yea text-accent"></i> Player Voting Active
                        </span>
                        <p class="text-[11px] text-base-content/60 mt-1">Shape the future game roadmap</p>
                    </div>

                    <div class="space-y-2 text-xs text-base-content/80 pt-2 border-t border-base-200">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-snowflake text-accent mt-0.5 shrink-0"></i>
                            <span><strong>Features:</strong> Extreme Backcountry & Rail Parks</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-globe text-accent mt-0.5 shrink-0"></i>
                            <span><strong>New Resorts:</strong> Community-voted destinations</span>
                        </div>
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-trophy text-accent mt-0.5 shrink-0"></i>
                            <span><strong>Competitions:</strong> Inter-resort tournaments</span>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-base-200/40 border-t border-base-200 text-center">
                    <?php if (auth()->loggedIn()): ?>
                    <a href="/vote" class="btn btn-accent btn-outline btn-sm w-full gap-2">
                        <i class="fa-solid fa-check-to-slot text-xs"></i> Vote for Season 4
                    </a>
                    <?php else: ?>
                    <a href="/register" class="btn btn-accent btn-outline btn-sm w-full gap-2">
                        <i class="fa-solid fa-user-plus text-xs"></i> Register To Vote
                    </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<?php if (auth()->loggedIn()) : ?>
<section class="py-8 px-4 bg-gradient-to-r from-primary/10 to-info/10">
    <div class="max-w-5xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <i class="fa-solid fa-check-to-slot text-primary text-3xl"></i>
            <div>
                <h3 class="font-bold text-lg">Vote for Season 4</h3>
                <p class="text-sm text-base-content/60">After Park City, where do we go? Help choose the next resort.</p>
            </div>
        </div>
        <a href="/vote" class="btn btn-primary gap-1"><i class="fa-solid fa-arrow-right"></i> Cast Your Vote</a>
    </div>
</section>
<?php endif ?>

<section class="py-16 px-4">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-3">This isn't a clicker game</h2>
            <p class="text-base-content/60 max-w-lg mx-auto">A deep ski resort tycoon simulator where every system is connected. Hire too many staff? Your expenses spike. Skip insurance? One accident costs everything. The most detailed browser-based ski management game available.</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-2"><i class="fa-solid fa-map text-primary text-xl"></i></div><div class="text-sm font-bold">Trail Map</div><div class="text-xs text-base-content/50">Draw and build your resort</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center mb-2"><i class="fa-solid fa-users text-warning text-xl"></i></div><div class="text-sm font-bold">10 Staff Roles</div><div class="text-xs text-base-content/50">Patrol, groomers, chefs...</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center mb-2"><i class="fa-solid fa-cloud-sun text-info text-xl"></i></div><div class="text-sm font-bold">Dynamic Weather</div><div class="text-xs text-base-content/50">Changes every hour</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-success/10 flex items-center justify-center mb-2"><i class="fa-solid fa-coins text-success text-xl"></i></div><div class="text-sm font-bold">Economy Simulation</div><div class="text-xs text-base-content/50">Loans, insurance, regulations</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-error/10 flex items-center justify-center mb-2"><i class="fa-solid fa-snowflake text-error text-xl"></i></div><div class="text-sm font-bold">Real Brands</div><div class="text-xs text-base-content/50">PistenBully, TechnoAlpin</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-secondary/10 flex items-center justify-center mb-2"><i class="fa-solid fa-person-snowboarding text-secondary text-xl"></i></div><div class="text-sm font-bold">Terrain Parks</div><div class="text-xs text-base-content/50">Halfpipes, rail gardens</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center mb-2"><i class="fa-solid fa-trophy text-warning text-xl"></i></div><div class="text-sm font-bold">Leaderboard</div><div class="text-xs text-base-content/50">Compete globally</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center mb-2"><i class="fa-solid fa-bolt text-primary text-xl"></i></div><div class="text-sm font-bold">Energy & Water</div><div class="text-xs text-base-content/50">Manage your resources</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-accent/10 flex items-center justify-center mb-2"><i class="fa-solid fa-building-columns text-accent text-xl"></i></div><div class="text-sm font-bold">Compliance</div><div class="text-xs text-base-content/50">Regulations and insurance</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-error/10 flex items-center justify-center mb-2"><i class="fa-solid fa-gauge text-error text-xl"></i></div><div class="text-sm font-bold">3 Difficulty Modes</div><div class="text-xs text-base-content/50">Easy, Standard, Hard</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-warning/10 flex items-center justify-center mb-2"><i class="fa-solid fa-star text-warning text-xl"></i></div><div class="text-sm font-bold">Achievements</div><div class="text-xs text-base-content/50">Unlock features as you grow</div></div></div>
            <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4 items-center text-center"><div class="w-12 h-12 rounded-xl bg-info/10 flex items-center justify-center mb-2"><i class="fa-solid fa-hotel text-info text-xl"></i></div><div class="text-sm font-bold">Hotels & Dining</div><div class="text-xs text-base-content/50">Lodges, restaurants, rentals</div></div></div>
        </div>
    </div>
</section>

<?php if (!auth()->loggedIn()) : ?>
<section class="py-10 px-4 bg-base-200">
    <div class="max-w-xl mx-auto text-center">
        <h2 class="text-xl font-bold mb-2">Ready to manage your own resort?</h2>
        <p class="text-sm text-base-content/60 mb-4">Free to play. No downloads. Start building in 30 seconds.</p>
        <a href="<?= url_to('register') ?>" class="btn btn-primary gap-2"><i class="fa-solid fa-rocket"></i> Start Your Resort Free</a>
    </div>
</section>
<?php endif ?>

<section class="py-12 px-4 bg-base-100">
    <div class="max-w-3xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
            <div class="card bg-base-200/50 border border-base-300"><div class="card-body p-4"><i class="fa-solid fa-heart text-error text-2xl mb-2"></i><h3 class="font-bold">100% Free</h3><p class="text-xs text-base-content/60">No paywalls, no pay-to-win. Earn everything by playing.</p></div></div>
            <div class="card bg-base-200/50 border border-base-300"><div class="card-body p-4"><i class="fa-solid fa-globe text-info text-2xl mb-2"></i><h3 class="font-bold">Instant Play</h3><p class="text-xs text-base-content/60">Browser-based. No downloads. Works on any device.</p></div></div>
            <div class="card bg-base-200/50 border border-base-300"><div class="card-body p-4"><i class="fa-solid fa-code-branch text-success text-2xl mb-2"></i><h3 class="font-bold">Open Source</h3><p class="text-xs text-base-content/60">Built in the open on <a href="https://github.com/MontaraGroup/Ski-Manager" target="_blank" rel="noopener noreferrer" class="link link-primary">GitHub</a>.</p></div></div>
        </div>
    </div>
</section>

<section class="py-16 px-4 bg-gradient-to-br from-primary to-secondary text-primary-content relative overflow-hidden">
    <div class="max-w-xl mx-auto text-center relative">
        <?php if (auth()->loggedIn()) : ?>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Get back to your resort.</h2>
        <p class="text-lg opacity-80">Your mountain needs you. Check in and keep building.</p>
        <a href="/dashboard" class="btn btn-lg gap-2 shadow-xl mt-6 bg-base-100 text-primary border-0 hover:bg-base-200"><i class="fa-solid fa-gauge-high"></i> Go to Dashboard</a>
        <?php else : ?>
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Your resort is waiting.</h2>
        <p class="text-lg opacity-80 mb-8">Up to <?= currency(1000000) ?> starting cash. An empty mountain. What you build is up to you.</p>
        <a href="/register" class="btn btn-lg gap-2 shadow-xl bg-base-100 text-primary border-0 hover:bg-base-200"><i class="fa-solid fa-play"></i> Start Building Now</a>
        <div class="mt-6 text-sm opacity-70">Already playing? <a href="/login" class="underline hover:opacity-100">Sign in</a></div>
        <?php endif ?>
    </div>
</section>

<?= $this->endSection() ?>
