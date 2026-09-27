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
    $weather = getCurrentWeather();
    $playersLabel = $playerCount >= 10 ? number_format($playerCount) . '+ active managers' : 'Be one of the first managers';

    $currentSeasonNum = (int) getSeasonNumber();
    $seasonDay = (int) getSeasonDay();
    $seasonLen = (int) getSeasonLength();
    $seasonProgressPct = min(100, max(0, (int) round(($seasonDay / max(1, $seasonLen)) * 100)));
    $daysRemaining = max(0, $seasonLen - $seasonDay);
?>

<!-- Minimalist Announcement Banner -->
<div class="bg-base-200/80 border-b border-base-300 py-2.5 px-4 text-xs font-medium">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-base-content/80">
            <span class="inline-block w-2 h-2 rounded-full bg-success"></span>
            <span class="font-semibold text-base-content">Season <?= getSeasonNumber() ?> Active</span>
            <span class="text-base-content/40">&bull;</span>
            <span>Day <?= getSeasonDay() ?> of <?= getSeasonLength() ?> (Park City Mountain)</span>
        </div>
        <div>
            <?php if (!auth()->loggedIn()) : ?>
            <a href="/register" class="link link-hover text-primary font-semibold flex items-center gap-1">
                Join Season <?= getSeasonNumber() ?> <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
            <?php else : ?>
            <a href="/dashboard" class="link link-hover text-primary font-semibold flex items-center gap-1">
                Go to Dashboard <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
            <?php endif ?>
        </div>
    </div>
</div>

<!-- Hero Section -->
<section class="py-16 md:py-24 bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <div class="lg:col-span-7 space-y-6">
                <!-- Status Tag -->
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-base-200 border border-base-300 text-xs font-mono text-base-content/80">
                    <i class="fa-solid fa-mountain text-primary"></i>
                    <span>Season <?= getSeasonNumber() ?></span>
                    <span class="text-base-content/30">|</span>
                    <span>Day <?= getSeasonDay() ?></span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-base-content leading-[1.08] uppercase">
                    Design Slopes.<br>
                    Spin Chairlifts.<br>
                    <span class="text-primary">Rule The Mountain.</span>
                </h1>

                <p class="text-base sm:text-lg text-base-content/70 max-w-xl leading-relaxed font-normal">
                    Start with an empty mountain and up to <?= currency(1000000) ?> in capital. Construct chairlifts, layout custom slopes, hire staff, engineer snowmaking networks, and keep your resort profitable.
                </p>

                <div class="flex flex-wrap items-center gap-3 pt-2">
                    <?php if (!auth()->loggedIn()) : ?>
                    <a href="/register" class="btn btn-primary rounded-xl font-semibold px-6 shadow-sm gap-2">
                        <i class="fa-solid fa-play text-xs"></i> Play Free
                    </a>
                    <a href="/login" class="btn btn-outline rounded-xl font-semibold border-base-300 gap-2">
                        <i class="fa-solid fa-right-to-bracket text-xs"></i> Sign In
                    </a>
                    <?php else : ?>
                    <a href="/dashboard" class="btn btn-primary rounded-xl font-semibold px-6 shadow-sm gap-2">
                        <i class="fa-solid fa-gauge-high text-xs"></i> Open Resort Dashboard
                    </a>
                    <?php endif ?>
                </div>

                <!-- Minimal Meta Badges -->
                <div class="flex flex-wrap items-center gap-y-2 gap-x-6 pt-4 text-xs text-base-content/60 font-medium">
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-users text-base-content/40"></i> <?= $playersLabel ?></span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-success"></i> Instant Browser Play</span>
                    <span class="flex items-center gap-1.5"><i class="fa-solid fa-check text-success"></i> No Downloads</span>
                </div>
            </div>

            <!-- Resort Director Accreditation Pass & Mountain Operations Board -->
            <div class="lg:col-span-5">
                <div class="relative bg-base-100 rounded-3xl border-2 border-base-300 shadow-md p-6 space-y-4">
                    <!-- Lanyard Slot Cutout -->
                    <div class="flex justify-center -mt-2 mb-1">
                        <div class="w-16 h-2.5 bg-base-300 rounded-full border border-base-content/20 shadow-inner"></div>
                    </div>

                    <!-- Accreditation Authority Header -->
                    <div class="bg-neutral text-neutral-content px-4 py-2.5 rounded-xl flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <i class="fa-solid fa-id-badge text-warning text-sm"></i>
                            <div>
                                <div class="text-[9px] uppercase tracking-widest font-mono text-neutral-content/70 leading-none">Resort Operations Pass</div>
                                <div class="text-xs font-bold font-mono tracking-tight leading-tight">ACCREDITED DIRECTOR &bull; CLASS A</div>
                            </div>
                        </div>
                        <span class="badge badge-success badge-sm font-mono text-[10px] font-bold">ACTIVE S<?= getSeasonNumber() ?></span>
                    </div>

                    <!-- Credential Holder Identity -->
                    <div class="flex items-center gap-3.5 py-1">
                        <div class="w-12 h-12 rounded-xl bg-base-200 border border-base-300 flex items-center justify-center shrink-0 relative overflow-hidden">
                            <?php if (auth()->loggedIn()) : ?>
                                <div class="w-full h-full bg-primary/10 text-primary flex items-center justify-center font-black text-lg">
                                    <?= strtoupper(substr(auth()->user()->username, 0, 2)) ?>
                                </div>
                            <?php else : ?>
                                <div class="w-full h-full bg-base-200 text-base-content/40 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-user-tie"></i>
                                </div>
                            <?php endif ?>
                            <span class="absolute bottom-0.5 right-0.5 w-2.5 h-2.5 bg-success rounded-full ring-2 ring-base-100"></span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-[10px] font-mono text-base-content/50 uppercase tracking-wider">Credential Holder</div>
                            <div class="font-black text-base text-base-content truncate tracking-tight">
                                <?= auth()->loggedIn() ? esc(auth()->user()->username) : 'GUEST CANDIDATE' ?>
                            </div>
                            <div class="text-xs text-base-content/70 font-medium truncate">
                                <?= auth()->loggedIn() ? 'Chief Operating Officer &bull; Park City Sector' : 'Awaiting Resort Commission &bull; Sector 1' ?>
                            </div>
                        </div>
                    </div>

                    <!-- Mountain Operations Telemetry Board -->
                    <div class="border-y border-base-200 divide-y divide-base-200 text-xs">
                        <!-- Summit Weather -->
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-base-content/60 font-medium flex items-center gap-2">
                                <i class="fa-solid fa-mountain-sun text-primary w-4 text-center"></i> Mountain Summit
                            </span>
                            <span class="font-mono font-semibold text-base-content">
                                <?= $weather ? $weather['temp_formatted'] . ' &bull; ' . esc($weather['condition']) : '24°F &bull; Clear' ?>
                            </span>
                        </div>

                        <!-- Snowpack Base -->
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-base-content/60 font-medium flex items-center gap-2">
                                <i class="fa-solid fa-snowflake text-info w-4 text-center"></i> Snowpack Depth
                            </span>
                            <span class="font-mono font-semibold text-base-content">
                                <?= isset($weather['snow_base']) ? (int)$weather['snow_base'] . '" Base' : 'Compacted Base' ?>
                                <?php if (isset($weather['snow_fall']) && $weather['snow_fall'] > 0) : ?>
                                    <span class="text-success text-[11px] font-normal">(+<?= (int)$weather['snow_fall'] ?>" fresh)</span>
                                <?php endif ?>
                            </span>
                        </div>

                        <!-- Operational Term -->
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-base-content/60 font-medium flex items-center gap-2">
                                <i class="fa-solid fa-calendar-days text-secondary w-4 text-center"></i> Operational Term
                            </span>
                            <span class="font-mono font-semibold text-base-content">
                                Day <?= getSeasonDay() ?> of <?= getSeasonLength() ?> (S<?= getSeasonNumber() ?>)
                            </span>
                        </div>

                        <!-- Slope Network -->
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-base-content/60 font-medium flex items-center gap-2">
                                <i class="fa-solid fa-person-skiing text-accent w-4 text-center"></i> Slope Network
                            </span>
                            <span class="font-mono font-semibold text-base-content">
                                <?= number_format($totalSlopes) ?> Active Runs
                            </span>
                        </div>

                        <!-- Leading Mountain Empire -->
                        <?php if ($topPlayer) : ?>
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="text-base-content/60 font-medium flex items-center gap-2">
                                <i class="fa-solid fa-trophy text-warning w-4 text-center"></i> Leading Operator
                            </span>
                            <span class="font-mono font-semibold text-base-content truncate max-w-[180px] text-right">
                                <?= esc($topPlayer['username']) ?> <span class="text-base-content/50 font-normal">(<?= currency((int) $topPlayer['cash']) ?>)</span>
                            </span>
                        </div>
                        <?php endif ?>
                    </div>

                    <!-- Barcode & Security Strip -->
                    <div class="pt-1 flex items-end justify-between gap-3">
                        <div class="space-y-1">
                            <div class="font-mono text-[9px] text-base-content/40 tracking-wider">SECURE RFID &bull; OP-ID</div>
                            <div class="h-6 flex items-center gap-[3px] text-base-content/70">
                                <span class="w-[2px] h-full bg-current"></span>
                                <span class="w-[3px] h-full bg-current"></span>
                                <span class="w-[1px] h-full bg-current"></span>
                                <span class="w-[4px] h-full bg-current"></span>
                                <span class="w-[1px] h-full bg-current"></span>
                                <span class="w-[2px] h-full bg-current"></span>
                                <span class="w-[3px] h-full bg-current"></span>
                                <span class="w-[1px] h-full bg-current"></span>
                                <span class="w-[2px] h-full bg-current"></span>
                                <span class="w-[4px] h-full bg-current"></span>
                                <span class="w-[1px] h-full bg-current"></span>
                                <span class="w-[3px] h-full bg-current"></span>
                                <span class="w-[2px] h-full bg-current"></span>
                                <span class="w-[1px] h-full bg-current"></span>
                                <span class="w-[3px] h-full bg-current"></span>
                                <span class="w-[2px] h-full bg-current"></span>
                            </div>
                            <div class="font-mono text-[9px] text-base-content/40 tracking-widest">
                                SM-2024-<?= strtoupper(substr(md5('director_pass_' . getSeasonNumber()), 0, 8)) ?>
                            </div>
                        </div>
                        <div>
                            <?php if (!auth()->loggedIn()) : ?>
                            <a href="/register" class="btn btn-sm btn-primary rounded-lg font-mono text-xs gap-1.5 shadow-sm">
                                Claim Pass <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                            <?php else : ?>
                            <a href="/dashboard" class="btn btn-sm btn-primary rounded-lg font-mono text-xs gap-1.5 shadow-sm">
                                Command Post <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </a>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Core Pillars -->
<section class="py-16 bg-base-200/40 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="mb-10">
            <h2 class="text-2xl font-bold tracking-tight text-base-content">Key Management Pillars</h2>
            <p class="text-sm text-base-content/60 mt-1">Balancing infrastructure, income, and alpine weather systems.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Pillar 1: Trail Architecture & Lift Networks -->
            <div class="p-6 bg-base-100 rounded-2xl border border-base-300 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-200">
                    <div class="flex items-center gap-1.5" title="Piste Difficulty Standards">
                        <span class="w-3.5 h-3.5 rounded-full bg-emerald-500 inline-block" title="Green Circle - Beginner"></span>
                        <span class="w-3.5 h-3.5 rounded-sm bg-blue-600 inline-block" title="Blue Square - Intermediate"></span>
                        <span class="w-3.5 h-3.5 rotate-45 bg-neutral inline-block" title="Black Diamond - Advanced"></span>
                    </div>
                    <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-base-content/50">Topography & Lifts</span>
                </div>
                <div>
                    <h3 class="font-bold text-base text-base-content">Slope & Lift Architecture</h3>
                    <p class="text-sm text-base-content/70 leading-relaxed font-normal mt-2">
                        Plot fall lines across authentic alpine terrain, grade slope difficulty, and configure high-capacity chairlifts and gondolas to prevent queue bottlenecks. Over <span class="font-semibold text-base-content"><?= number_format($totalSlopes) ?></span> runs mapped mountain-wide.
                    </p>
                </div>
                <div class="pt-2 flex items-center gap-2 text-xs font-mono text-base-content/60">
                    <i class="fa-solid fa-bezier-curve text-primary"></i>
                    <span>Real-world contour mapping</span>
                </div>
            </div>

            <!-- Pillar 2: Commercial Operations & Yield Management -->
            <div class="p-6 bg-base-100 rounded-2xl border border-base-300 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-200">
                    <div class="flex items-center gap-1.5 text-warning font-mono text-xs font-bold">
                        <i class="fa-solid fa-coins"></i>
                        <span>FISCAL ENGINE</span>
                    </div>
                    <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-base-content/50">Lodge & Retail</span>
                </div>
                <div>
                    <h3 class="font-bold text-base text-base-content">Commercial Operations</h3>
                    <p class="text-sm text-base-content/70 leading-relaxed font-normal mt-2">
                        Build luxury slope-side lodges, mountain restaurants, equipment rental centers, and parking lots. Calibrate ticket pricing to maximize revenue without exceeding lift capacity. Top resort: <span class="font-semibold text-base-content"><?= $topPlayer ? esc($topPlayer['username']) : 'Top Operator' ?></span>.
                    </p>
                </div>
                <div class="pt-2 flex items-center gap-2 text-xs font-mono text-base-content/60">
                    <i class="fa-solid fa-arrow-trend-up text-success"></i>
                    <span>Dynamic pass yield pricing</span>
                </div>
            </div>

            <!-- Pillar 3: Industrial Snowmaking & Fleet Grooming -->
            <div class="p-6 bg-base-100 rounded-2xl border border-base-300 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-base-200">
                    <div class="flex items-center gap-1.5 text-info font-mono text-xs font-bold">
                        <i class="fa-solid fa-snowflake"></i>
                        <span>SNOWPACK OPS</span>
                    </div>
                    <span class="text-[11px] font-mono font-bold uppercase tracking-wider text-base-content/50"><?= $weather ? $weather['temp_formatted'] : '24°F' ?></span>
                </div>
                <div>
                    <h3 class="font-bold text-base text-base-content">Snowmaking & Grooming</h3>
                    <p class="text-sm text-base-content/70 leading-relaxed font-normal mt-2">
                        Track wet-bulb temperature thresholds to trigger automated snow cannon banks during dry stretches. Dispatch snowcat grooming shifts at dusk to lay down corduroy and preserve surface ratings.
                    </p>
                </div>
                <div class="pt-2 flex items-center gap-2 text-xs font-mono text-base-content/60">
                    <i class="fa-solid fa-gauge-high text-info"></i>
                    <span>Wet-bulb telemetry & fleet routing</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Real Maps Section -->
<section class="py-16 bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-5 space-y-4">
                <div class="inline-flex items-center gap-1.5 text-xs font-mono text-base-content/60 uppercase tracking-wider">
                    <i class="fa-solid fa-map text-primary"></i> Map Database
                </div>
                <h2 class="text-3xl font-bold tracking-tight text-base-content">Built on real resort maps.</h2>
                <p class="text-sm text-base-content/70 leading-relaxed font-normal">
                    Manage real-world topography from famous ski destinations. Plot runs across natural ridge lines, bowl formations, and valley bases.
                </p>

                <div class="flex flex-wrap gap-2 pt-2">
                    <span class="px-2.5 py-1 rounded-md bg-base-200 border border-base-300 text-xs font-medium text-base-content">Park City</span>
                    <span class="px-2.5 py-1 rounded-md bg-base-200 border border-base-300 text-xs font-medium text-base-content">Deer Valley</span>
                    <span class="px-2.5 py-1 rounded-md bg-base-200 border border-base-300 text-xs font-medium text-base-content">Vail</span>
                    <span class="px-2.5 py-1 rounded-md bg-base-200 border border-base-300 text-xs font-medium text-base-content">Aspen</span>
                    <span class="px-2.5 py-1 rounded-md bg-base-200 border border-base-300 text-xs font-medium text-base-content">Big Sky</span>
                    <span class="px-2.5 py-1 rounded-md bg-base-200 border border-base-300 text-xs font-medium text-base-content">Palisades Tahoe</span>
                </div>

                <div class="pt-2 text-xs text-base-content/50">
                    Map overlays powered by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="link link-hover font-semibold">Mapsynergy</a>
                </div>
            </div>

            <div class="lg:col-span-7">
                <a href="<?= auth()->loggedIn() ? '/map' : '/register' ?>" class="block relative rounded-2xl overflow-hidden border border-base-300 shadow-sm">
                    <img src="/img/ParkCity_low.jpg" alt="Park City Trail Map" class="w-full h-auto object-cover" loading="lazy">
                    <div class="absolute inset-0 bg-base-900/5"></div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Season Progression Roadmap -->
<section class="py-16 bg-base-200/40 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="mb-10 text-center max-w-xl mx-auto">
            <span class="text-xs font-mono text-primary font-semibold uppercase tracking-wider">Season Progression</span>
            <h2 class="text-3xl font-bold tracking-tight text-base-content mt-1">Mountain Expansion Roadmap</h2>
            <p class="text-sm text-base-content/60 mt-2">Unlocking sectors, steeper elevation, and new resort challenges over time.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Season 1 -->
            <div class="p-6 bg-base-100 rounded-2xl border-2 border-primary/80 shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-primary font-mono text-xs font-bold">Season 1</span>
                        <span class="text-xs font-bold text-success flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full bg-success animate-pulse"></span> Active
                        </span>
                    </div>

                    <h3 class="font-bold text-lg text-base-content">Sector 1 &bull; Park City Base</h3>
                    <p class="text-xs text-base-content/70 leading-relaxed">
                        Founding sector with beginner and intermediate terrain. Build core lifts, establish ski patrol, and maintain financial stability.
                    </p>

                    <div class="p-3.5 bg-base-200/80 rounded-xl border border-base-300 space-y-2">
                        <div class="flex justify-between text-xs font-semibold">
                            <span class="text-base-content/70">Season Days</span>
                            <span class="font-mono text-primary font-bold"><?= $seasonProgressPct ?>%</span>
                        </div>
                        <progress class="progress progress-primary w-full h-2" value="<?= getSeasonDay() ?>" max="<?= getSeasonLength() ?>"></progress>
                        <div class="flex justify-between text-[11px] text-base-content/60 font-medium">
                            <span>Day <?= getSeasonDay() ?>/<?= getSeasonLength() ?></span>
                            <span><?= $daysRemaining ?> days left</span>
                        </div>
                    </div>

                    <ul class="space-y-2 text-xs text-base-content/80 pt-2 border-t border-base-200">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-success text-xs"></i> Beginner & Intermediate Slopes</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-success text-xs"></i> Snowmaking & Grooming Systems</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-check text-success text-xs"></i> Survival & Cash Management</li>
                    </ul>
                </div>

                <div class="pt-2">
                    <?php if (!auth()->loggedIn()): ?>
                    <a href="/register" class="btn btn-primary btn-sm rounded-xl w-full font-semibold">Join Season 1 Free</a>
                    <?php else: ?>
                    <a href="/dashboard" class="btn btn-primary btn-sm rounded-xl w-full font-semibold">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Season 2 -->
            <div class="p-6 bg-base-100 rounded-2xl border border-base-300 shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-neutral font-mono text-xs font-bold">Season 2</span>
                        <span class="text-xs font-medium text-base-content/50 flex items-center gap-1"><i class="fa-solid fa-lock text-[10px]"></i> Locked</span>
                    </div>

                    <h3 class="font-bold text-lg text-base-content">Sector 2 &bull; Advanced Peaks</h3>
                    <p class="text-xs text-base-content/70 leading-relaxed">
                        Expands terrain into steep bowls and high-altitude ridges. Introduce express gondolas and handle storm emergency operations.
                    </p>

                    <div class="p-3.5 bg-base-200/50 rounded-xl border border-base-300/80 text-center">
                        <span class="text-xs font-semibold text-base-content/70"><i class="fa-solid fa-hourglass-half text-warning mr-1"></i> Unlocks after Season 1</span>
                        <div class="text-[11px] text-base-content/50 mt-0.5">Full resort cash & assets carry forward</div>
                    </div>

                    <ul class="space-y-2 text-xs text-base-content/80 pt-2 border-t border-base-200">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-mountain text-info text-xs"></i> Black Diamond & Expert Bowls</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-cable-car text-info text-xs"></i> High-Speed Express Lifts</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-arrow-right-rotate text-info text-xs"></i> Full Progress Carryover</li>
                    </ul>
                </div>

                <div class="pt-2">
                    <button class="btn btn-sm btn-ghost btn-disabled rounded-xl w-full text-xs font-medium cursor-not-allowed">Season 1 In Progress</button>
                </div>
            </div>

            <!-- Season 3 & Beyond -->
            <div class="p-6 bg-base-100 rounded-2xl border border-base-300 shadow-sm space-y-4 flex flex-col justify-between">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="badge badge-accent font-mono text-xs font-bold">Season 3+</span>
                        <span class="text-xs font-semibold text-accent"><i class="fa-solid fa-check-to-slot text-[10px]"></i> Voting Active</span>
                    </div>

                    <h3 class="font-bold text-lg text-base-content">Global Mountain Expansion</h3>
                    <p class="text-xs text-base-content/70 leading-relaxed">
                        Conquer the complete mountain before participating in community votes for future destinations like Aspen or European alpine passes.
                    </p>

                    <div class="p-3.5 bg-accent/5 rounded-xl border border-accent/20 text-center">
                        <span class="text-xs font-bold text-accent"><i class="fa-solid fa-vote-yea mr-1"></i> Community Destination Voting</span>
                        <div class="text-[11px] text-base-content/60 mt-0.5">Help choose the next ski area</div>
                    </div>

                    <ul class="space-y-2 text-xs text-base-content/80 pt-2 border-t border-base-200">
                        <li class="flex items-center gap-2"><i class="fa-solid fa-snowflake text-accent text-xs"></i> Backcountry & Terrain Parks</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-globe text-accent text-xs"></i> Community-Selected Resorts</li>
                        <li class="flex items-center gap-2"><i class="fa-solid fa-trophy text-accent text-xs"></i> Inter-Resort Tournaments</li>
                    </ul>
                </div>

                <div class="pt-2">
                    <?php if (auth()->loggedIn()): ?>
                    <a href="/vote" class="btn btn-accent btn-outline btn-sm rounded-xl w-full font-semibold">Vote for Season 4</a>
                    <?php else: ?>
                    <a href="/register" class="btn btn-accent btn-outline btn-sm rounded-xl w-full font-semibold">Register To Vote</a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Modular Systems Grid -->
<section class="py-16 bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="mb-10 text-center max-w-xl mx-auto">
            <h2 class="text-2xl font-bold tracking-tight text-base-content">Connected Tycoon Mechanics</h2>
            <p class="text-sm text-base-content/60 mt-1">Every system impacts your resort's bottom line.</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-primary text-sm"><i class="fa-solid fa-map"></i></div>
                <div class="text-xs font-bold text-base-content">Trail Map</div>
                <div class="text-[11px] text-base-content/60">Draw & connect runs</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-warning text-sm"><i class="fa-solid fa-users"></i></div>
                <div class="text-xs font-bold text-base-content">10 Staff Roles</div>
                <div class="text-[11px] text-base-content/60">Patrol, groomers, chefs</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-info text-sm"><i class="fa-solid fa-cloud-sun"></i></div>
                <div class="text-xs font-bold text-base-content">Dynamic Weather</div>
                <div class="text-[11px] text-base-content/60">Hourly temperature shifts</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-success text-sm"><i class="fa-solid fa-coins"></i></div>
                <div class="text-xs font-bold text-base-content">Economy Simulation</div>
                <div class="text-[11px] text-base-content/60">Loans, tickets & insurance</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-error text-sm"><i class="fa-solid fa-snowflake"></i></div>
                <div class="text-xs font-bold text-base-content">Real Brands</div>
                <div class="text-[11px] text-base-content/60">PistenBully & TechnoAlpin</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-secondary text-sm"><i class="fa-solid fa-person-snowboarding"></i></div>
                <div class="text-xs font-bold text-base-content">Terrain Parks</div>
                <div class="text-[11px] text-base-content/60">Halfpipes & rail gardens</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-warning text-sm"><i class="fa-solid fa-trophy"></i></div>
                <div class="text-xs font-bold text-base-content">Leaderboard</div>
                <div class="text-[11px] text-base-content/60">Global seasonal rankings</div>
            </div>

            <div class="p-4 bg-base-200/50 rounded-xl border border-base-300 text-center space-y-1.5">
                <div class="w-8 h-8 rounded-lg bg-base-100 border border-base-300 flex items-center justify-center mx-auto text-primary text-sm"><i class="fa-solid fa-bolt"></i></div>
                <div class="text-xs font-bold text-base-content">Energy & Water</div>
                <div class="text-[11px] text-base-content/60">Resource infrastructure</div>
            </div>
        </div>
    </div>
</section>

<!-- Footer Banner -->
<section class="py-16 bg-base-200/60">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <div class="p-8 rounded-2xl bg-base-100 border border-base-300 shadow-sm space-y-4">
            <?php if (auth()->loggedIn()) : ?>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-base-content">Return to your mountain.</h2>
            <p class="text-sm text-base-content/70 max-w-md mx-auto">Your resort operations are running live in real-time.</p>
            <div class="pt-2">
                <a href="/dashboard" class="btn btn-primary rounded-xl font-semibold px-8 gap-2">
                    <i class="fa-solid fa-gauge-high text-xs"></i> Go to Dashboard
                </a>
            </div>
            <?php else : ?>
            <h2 class="text-2xl sm:text-3xl font-bold tracking-tight text-base-content">Your mountain awaits.</h2>
            <p class="text-sm text-base-content/70 max-w-md mx-auto">Start with up to <?= currency(1000000) ?> capital. No downloads required.</p>
            <div class="pt-2">
                <a href="/register" class="btn btn-primary rounded-xl font-semibold px-8 gap-2">
                    <i class="fa-solid fa-play text-xs"></i> Start Building Free
                </a>
            </div>
            <div class="text-xs text-base-content/50 pt-1">
                Already playing? <a href="/login" class="underline font-medium hover:text-base-content">Sign in here</a>
            </div>
            <?php endif ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
