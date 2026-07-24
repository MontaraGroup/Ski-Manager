<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php if (isset($season)): ?>
<div class="bg-gradient-to-r from-primary to-info text-primary-content">
    <div class="max-w-6xl mx-auto px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-white"></span>
            </span>
            <span class="font-bold">Season <?= esc($season['number'] ?? 1) ?> is live</span>
            <span class="opacity-80 text-sm">Day <?= esc($season['current_day'] ?? 1) ?>/<?= esc($season['total_days'] ?? 135) ?> at <?= esc($season['map_name'] ?? 'Park City') ?></span>
        </div>
        <a href="<?= site_url('register') ?>" class="btn btn-sm btn-outline border-white text-white hover:bg-white hover:text-primary gap-1">
            <i class="fa-solid fa-play"></i> Join Season <?= esc($season['number'] ?? 1) ?>
        </a>
    </div>
</div>
<?php endif; ?>

<div class="min-h-[70vh] flex items-center bg-gradient-to-br from-base-300 via-base-200 to-base-100 relative overflow-hidden border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 relative">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 items-center">
            <div class="lg:col-span-3">
                <div class="badge badge-primary gap-1 mb-4">
                    <i class="fa-solid fa-clock text-xs"></i> Season <?= esc($season['number'] ?? 1) ?> &bull; Day <?= esc($season['current_day'] ?? 1) ?>
                </div>
                <h1 class="text-4xl md:text-6xl font-black leading-[1.05] mb-5 tracking-tight">
                    Build the resort<br>
                    <span class="bg-gradient-to-r from-primary via-accent to-secondary bg-clip-text text-transparent">
                        everyone talks about.
                    </span>
                </h1>
                <p class="text-lg text-base-content/70 mb-8 max-w-lg leading-relaxed">
                    Start with an empty mountain and up to 1,000,000 € in cash. Build lifts, hire staff, manage snowmaking, and survive without going bankrupt.
                </p>

                <div class="flex gap-3 flex-wrap mb-6">
                    <?php if (function_exists('auth') && auth()->loggedIn()): ?>
                        <a href="<?= site_url('dashboard') ?>" class="btn btn-primary btn-lg gap-2 shadow-lg hover:scale-105 transition-all">
                            <i class="fa-solid fa-gauge-high"></i> Go to Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= site_url('register') ?>" class="btn btn-primary btn-lg gap-2 shadow-lg hover:scale-105 transition-all">
                            <i class="fa-solid fa-play"></i> Play Free - Takes 30 Seconds
                        </a>
                        <a href="<?= site_url('login') ?>" class="btn btn-outline btn-lg gap-2">
                            <i class="fa-solid fa-right-to-bracket"></i> Login
                        </a>
                    <?php endif; ?>
                </div>

                <div class="flex items-center gap-4 text-xs text-base-content/60 flex-wrap font-medium">
                    <span><i class="fa-solid fa-users text-primary mr-1"></i>Active Managers Online</span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No downloads</span>
                    <span><i class="fa-solid fa-check text-success mr-1"></i>No pay-to-win</span>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body p-5">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-success opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-success"></span>
                            </span>
                            <span class="text-xs font-semibold text-success">Live Resort Leaderboard</span>
                        </div>

                        <?php if (isset($richest_user)): ?>
                        <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg mb-3">
                            <i class="fa-solid fa-crown text-warning text-xl"></i>
                            <div>
                                <div class="font-bold text-sm"><?= esc($richest_user['username'] ?? 'Top Manager') ?></div>
                                <div class="text-xs text-base-content/60">Richest resort &bull; <?= number_format($richest_user['cash'] ?? 0) ?> €</div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($newest_user)): ?>
                        <div class="flex items-center gap-3 p-3 bg-base-200 rounded-lg">
                            <i class="fa-solid fa-user-plus text-primary text-xl"></i>
                            <div>
                                <div class="font-bold text-sm"><?= esc($newest_user['username'] ?? 'New Manager') ?></div>
                                <div class="text-xs text-base-content/60">Recently joined the slopes</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="py-12 px-4 bg-base-100">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-3">What will you build?</h2>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors border border-base-300">
                <div class="card-body text-center">
                    <div class="text-4xl mb-3">🏔️</div>
                    <h3 class="font-bold mb-2">Opening new slopes</h3>
                    <p class="text-sm text-base-content/70">Draw runs on an interactive map, choose difficulty ratings, and watch visitors pour in. <?= esc($slopes_count ?? '180+') ?> slopes built so far.</p>
                </div>
            </div>
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors border border-base-300">
                <div class="card-body text-center">
                    <div class="text-4xl mb-3">💰</div>
                    <h3 class="font-bold mb-2">Making serious money</h3>
                    <p class="text-sm text-base-content/70">Hotels, restaurants, parking fees, and ticket sales. Build a profitable alpine business empire.</p>
                </div>
            </div>
            <div class="card bg-base-200/50 hover:bg-base-200 transition-colors border border-base-300">
                <div class="card-body text-center">
                    <div class="text-4xl mb-3">❄️</div>
                    <h3 class="font-bold mb-2">Fighting the weather</h3>
                    <p class="text-sm text-base-content/70">Monitor snow depth, deploy TechnoAlpin snow cannons during dry spells, and dispatch groomers.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-12 px-4 bg-base-200 border-t border-b border-base-300">
    <div class="max-w-5xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
                <h2 class="text-3xl font-bold mb-3">Real resort maps</h2>
                <p class="text-base-content/70 mb-4">Build on real trail maps from top ski resorts. Draw slopes, place lifts, and watch your mountain come to life.</p>
                <div class="flex flex-wrap gap-2 mb-4">
                    <span class="badge badge-outline">Park City</span>
                    <span class="badge badge-outline">Deer Valley</span>
                    <span class="badge badge-outline">Vail</span>
                    <span class="badge badge-outline">Aspen</span>
                    <span class="badge badge-outline">Palisades Tahoe</span>
                </div>
                <p class="text-xs text-base-content/50">Maps by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="link">Mapsynergy</a></p>
            </div>
            <a href="<?= site_url('register') ?>" class="block">
                <img src="/img/ParkCity_low.jpg" alt="Park City Trail Map" class="rounded-xl shadow-lg w-full hover:scale-[1.02] transition-transform" loading="lazy">
            </a>
        </div>
    </div>
</section>

<section class="py-16 px-4 bg-gradient-to-br from-primary to-secondary text-primary-content relative overflow-hidden">
    <div class="max-w-xl mx-auto text-center relative">
        <h2 class="text-3xl md:text-4xl font-bold mb-4">Your resort is waiting.</h2>
        <p class="text-lg opacity-80 mb-8">Up to 1,000,000 € starting cash. An empty mountain. What you build is up to you.</p>
        <a href="<?= site_url('register') ?>" class="btn btn-lg gap-2 shadow-xl bg-base-100 text-primary border-0 hover:bg-base-200">
            <i class="fa-solid fa-play"></i> Start Building Now
        </a>
    </div>
</section>

<?= $this->endSection() ?>
