<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="relative overflow-hidden bg-gradient-to-b from-base-100 via-base-200 to-base-100 py-16 lg:py-24 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4 text-center relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-semibold mb-6 animate-pulse-soft">
            <span class="w-2 h-2 rounded-full bg-primary"></span>
            <span>Season 4 Is Live &bull; Park City Mountain Resort</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-base-content max-w-4xl mx-auto leading-tight">
            Build, Manage, & Conquer <br class="hidden sm:inline">
            <span class="bg-gradient-to-r from-primary via-accent to-secondary bg-clip-text text-transparent">
                Your Dream Ski Resort
            </span>
        </h1>

        <p class="mt-6 text-base sm:text-lg text-base-content/70 max-w-2xl mx-auto leading-relaxed">
            The ultimate deep-simulation ski resort economic management game. Carve custom trails, engineer snowmaking networks, manage ski patrol safety, and compete with players worldwide—right in your browser.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center items-center">
            <?php if (auth()->loggedIn()): ?>
                <a href="<?= site_url('dashboard') ?>" class="btn btn-primary btn-lg shadow-lg hover:scale-105 transition-all w-full sm:w-auto gap-2">
                    <i class="fa-solid fa-gauge-high"></i> Go to Resort Dashboard
                </a>
            <?php else: ?>
                <a href="<?= site_url('register') ?>" class="btn btn-primary btn-lg shadow-lg hover:scale-105 transition-all w-full sm:w-auto gap-2">
                    <i class="fa-solid fa-person-skiing"></i> Start Playing Free
                </a>
                <a href="<?= site_url('login') ?>" class="btn btn-outline btn-lg w-full sm:w-auto gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Player Login
                </a>
            <?php endif; ?>
        </div>

        <div class="mt-10 flex flex-wrap justify-center items-center gap-6 text-xs text-base-content/60 font-medium">
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-bolt text-warning"></i> Instant Browser Play</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-ban text-error"></i> No Downloads Required</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-shield-halved text-success"></i> 100% Free To Play</span>
            <span class="flex items-center gap-1.5"><i class="fa-solid fa-mobile-screen text-info"></i> Mobile & PWA Ready</span>
        </div>
    </div>
</section>

<section class="py-8 bg-base-100 border-b border-base-300">
    <div class="max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div class="p-4 rounded-xl bg-base-200/50">
                <div class="text-2xl lg:text-3xl font-black text-primary">Interactive</div>
                <div class="text-xs text-base-content/60 font-medium mt-1">Trail Map Engine</div>
            </div>
            <div class="p-4 rounded-xl bg-base-200/50">
                <div class="text-2xl lg:text-3xl font-black text-accent">Real-Time</div>
                <div class="text-xs text-base-content/60 font-medium mt-1">Weather & Snow Depth</div>
            </div>
            <div class="p-4 rounded-xl bg-base-200/50">
                <div class="text-2xl lg:text-3xl font-black text-success">30+</div>
                <div class="text-xs text-base-content/60 font-medium mt-1">Operational Modules</div>
            </div>
            <div class="p-4 rounded-xl bg-base-200/50">
                <div class="text-2xl lg:text-3xl font-black text-info">Global</div>
                <div class="text-xs text-base-content/60 font-medium mt-1">Season Leaderboard</div>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-base-200">
    <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold">Deep Management Mechanics</h2>
            <p class="text-base-content/60 mt-2">Every slope, lift, employee, and dollar matters in your quest for mountain dominance.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="card bg-base-100 shadow-sm border border-base-300 hover:border-primary transition-all">
                <div class="card-body p-6">
                    <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-map"></i>
                    </div>
                    <h3 class="card-title text-lg">Trail Map & Lift Layouts</h3>
                    <p class="text-sm text-base-content/70">Build chairlifts, high-speed gondolas, and custom-rated ski slopes (Green, Blue, Black Diamond). Balance mountain capacity and eliminate queue lines.</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300 hover:border-accent transition-all">
                <div class="card-body p-6">
                    <div class="w-12 h-12 rounded-xl bg-accent/10 text-accent flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-snowflake"></i>
                    </div>
                    <h3 class="card-title text-lg">Snowmaking & Grooming</h3>
                    <p class="text-sm text-base-content/70">Connect water pumps, reservoirs, and power grids to deploy snow cannons during cold snaps. Dispatch snowcats to keep conditions smooth and fresh.</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300 hover:border-info transition-all">
                <div class="card-body p-6">
                    <div class="w-12 h-12 rounded-xl bg-info/10 text-info flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h3 class="card-title text-lg">Ski Patrol & Safety</h3>
                    <p class="text-sm text-base-content/70">Station patrol units and medical centers to respond to mountain emergencies, maintain safety compliance ratings, and prevent slope closures.</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300 hover:border-success transition-all">
                <div class="card-body p-6">
                    <div class="w-12 h-12 rounded-xl bg-success/10 text-success flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-hotel"></i>
                    </div>
                    <h3 class="card-title text-lg">Hospitality & Facilities</h3>
                    <p class="text-sm text-base-content/70">Construct hotels, alpine restaurants, equipment rental shops, parking structures, and real estate developments to boost visitor stay duration.</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300 hover:border-warning transition-all">
                <div class="card-body p-6">
                    <div class="w-12 h-12 rounded-xl bg-warning/10 text-warning flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <h3 class="card-title text-lg">Economy & Banking</h3>
                    <p class="text-sm text-base-content/70">Set dynamic lift ticket prices, secure commercial bank loans, manage staff salaries, purchase insurance policies, and maximize seasonal profits.</p>
                </div>
            </div>

            <div class="card bg-base-100 shadow-sm border border-base-300 hover:border-secondary transition-all">
                <div class="card-body p-6">
                    <div class="w-12 h-12 rounded-xl bg-secondary/10 text-secondary flex items-center justify-center mb-4 text-xl">
                        <i class="fa-solid fa-trophy"></i>
                    </div>
                    <h3 class="card-title text-lg">VIP Guests & Competitions</h3>
                    <p class="text-sm text-base-content/70">Satisfy high-profile VIP guests, host world-class tournaments, claim daily login bonuses, and rise through the global seasonal leaderboards.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-base-100">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-2">How To Play</h2>
        <p class="text-base-content/60 mb-12">Start your ski resort career in three simple steps.</p>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-primary text-primary-content font-bold flex items-center justify-center text-lg mb-3">1</div>
                <h4 class="font-bold text-base mb-1">Create Account</h4>
                <p class="text-xs text-base-content/70">Register in under 10 seconds with no installation needed.</p>
            </div>
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-primary text-primary-content font-bold flex items-center justify-center text-lg mb-3">2</div>
                <h4 class="font-bold text-base mb-1">Carve Your Slopes</h4>
                <p class="text-xs text-base-content/70">Install lifts, open trails, hire staff, and welcome your first skiers.</p>
            </div>
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 rounded-full bg-primary text-primary-content font-bold flex items-center justify-center text-lg mb-3">3</div>
                <h4 class="font-bold text-base mb-1">Expand & Dominate</h4>
                <p class="text-xs text-base-content/70">Reinvest profits into luxury hotels and top the global leaderboards.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-base-200 border-t border-base-300">
    <div class="max-w-3xl mx-auto px-4">
        <div class="text-center mb-10">
            <h2 class="text-2xl font-bold">Frequently Asked Questions</h2>
        </div>

        <div class="space-y-3">
            <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-xl">
                <input type="radio" name="faq-accordion" checked="checked" />
                <div class="collapse-title text-base font-bold">Is Ski Manager completely free to play?</div>
                <div class="collapse-content text-sm text-base-content/70">
                    Yes! Ski Manager is 100% free to play directly in any modern desktop or mobile web browser.
                </div>
            </div>

            <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-xl">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-base font-bold">Do I need to download or install anything?</div>
                <div class="collapse-content text-sm text-base-content/70">
                    No installation is needed. All game state progress saves automatically to your browser and cloud account.
                </div>
            </div>

            <div class="collapse collapse-plus bg-base-100 border border-base-300 rounded-xl">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-base font-bold">Can I play on mobile devices?</div>
                <div class="collapse-content text-sm text-base-content/70">
                    Yes! Ski Manager is built with Progressive Web App (PWA) support and adaptive mobile navigation.
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-16 bg-primary text-primary-content">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-3xl font-black mb-3">Ready To Build Your Ski Empire?</h2>
        <p class="text-primary-content/80 text-sm max-w-xl mx-auto mb-6">Join hundreds of managers building and competing on the slopes today.</p>
        <a href="<?= site_url('register') ?>" class="btn btn-secondary btn-lg shadow-xl hover:scale-105 transition-all gap-2">
            <i class="fa-solid fa-user-plus"></i> Claim Your Mountain Now
        </a>
    </div>
</section>

<?= $this->endSection() ?>
