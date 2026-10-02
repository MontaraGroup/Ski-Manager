<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold mb-1">Game Updates</h1>
            <p class="text-base-content/60">What's new in Ski Manager. Follow our progress and see what's shipped.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="https://roadmap.ski-manager.net" target="_blank" class="btn btn-sm btn-outline gap-1.5">
                <i class="fa-solid fa-map text-primary"></i> Roadmap <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-60"></i>
            </a>
            <a href="https://skimanager1.featurebase.app/en/changelog" target="_blank" class="btn btn-sm btn-primary btn-outline gap-1.5">
                <i class="fa-solid fa-bullhorn"></i> Changelog <i class="fa-solid fa-arrow-up-right-from-square text-[10px] opacity-60"></i>
            </a>
        </div>
    </div>

    <div class="relative border-l-2 border-base-300 ml-3 space-y-8">
        
        <!-- v1.4.0 Release -->
        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-primary"></span>
            <div class="card bg-base-100 shadow-sm border border-primary">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-primary font-mono">v1.4.0</span>
                        <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-star text-[10px]"></i>Latest</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>October 2, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Season 4 Mountain Vote, Engine Optimization &amp; AI Integration</h2>
                    <p class="text-sm text-base-content/70 mt-1">Launched the official Season 4 Mountain Selection Vote with live telemetry, overhauled database performance and automated storage maintenance, and deployed AI Agent content negotiation.</p>
                    
                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-plus text-[10px]"></i>New Features</span>
                                <span class="text-xs text-base-content/40">3</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span>
                                    <span><strong>Season 4 Mountain Vote:</strong> Community voting for Season 4 with 6 authentic candidate resorts, live mountain telemetry, and custom in-game perks.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span>
                                    <span><strong>AI Content Negotiation:</strong> Native HTTP <code>Accept: text/markdown</code> negotiation delivering clean, high-density Markdown for AI agents.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span>
                                    <span><strong>RFC 9309 Bot Governance:</strong> Explicit crawl and Content-Signal policy directives for 20 AI search and training crawlers.</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-info badge-sm gap-1"><i class="fa-solid fa-arrow-up-long text-[10px]"></i>Improvements</span>
                                <span class="text-xs text-base-content/40">2</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-info mt-1.5 shrink-0"></span>
                                    <span><strong>98% DB Query Reduction:</strong> Eliminated 50-user N+1 loops in the Admin dashboard with batch lookups and composite indexing.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-info mt-1.5 shrink-0"></span>
                                    <span><strong>Automated Storage Pruning:</strong> Automated background pruning for historical activity and transaction logs keeps database size optimal.</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-error badge-sm gap-1"><i class="fa-solid fa-bug text-[10px]"></i>Bug Fixes</span>
                                <span class="text-xs text-base-content/40">3</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span>
                                    <span>Fixed vote modification error by adding self-healing schema migration for <code>updated_at</code> on <code>resort_votes</code>.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span>
                                    <span>Guarded division by zero in seasonal helpers and protected nullable offsets in admin and gameplay views.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span>
                                    <span>Enforced atomic financial transactions and funds verification across buildings, loans, and night skiing.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.3.6</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>July 22, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Deployment Automation &amp; System Resilience</h2>
                    <p class="text-sm text-base-content/70 mt-1">Integrated seamless GitHub webhook CI/CD deployment pipelines, automated writable cache directory recovery, and fixed session persistence boundaries.</p>
                    
                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-plus text-[10px]"></i>New Features</span>
                                <span class="text-xs text-base-content/40">1</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span>
                                    <span><strong>GitHub Webhook Auto-Deploy:</strong> Pushing to `main` now triggers instant server-side updates, database migrations, and cache clearing automatically.</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-info badge-sm gap-1"><i class="fa-solid fa-arrow-up-long text-[10px]"></i>Improvements</span>
                                <span class="text-xs text-base-content/40">1</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-info mt-1.5 shrink-0"></span>
                                    <span><strong>Automated Writable Safeguards:</strong> Deployment and initialization scripts now self-heal missing cache, session, and log directories with strict permission enforcement.</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-error badge-sm gap-1"><i class="fa-solid fa-bug text-[10px]"></i>Bug Fixes</span>
                                <span class="text-xs text-base-content/40">1</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span>
                                    <span>Resolved critical `CacheException` 500 errors caused by missing `writable/cache` directory states during cleanups.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.3.5</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>July 2, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Authentication Infrastructure Overhaul</h2>
                    <p class="text-sm text-base-content/70 mt-1">A massive under-the-hood refactor to isolate and stabilize user registration pipelines, resolving container session context anomalies and enhancing visual verification entry profiles.</p>
                    
                    <div class="mt-4 space-y-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-plus text-[10px]"></i>New Features</span>
                                <span class="text-xs text-base-content/40">2</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span>
                                    <span><strong>Tactile DaisyUI OTP Field:</strong> Replaced the old standard text box with a modern multi-segmented 6-digit split interface constraint array matching token layouts.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-success mt-1.5 shrink-0"></span>
                                    <span><strong>Session-Less Activation Bypass:</strong> Introduced a standalone transactional background recovery terminal block for heavily blocked system users.</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-info badge-sm gap-1"><i class="fa-solid fa-arrow-up-long text-[10px]"></i>Improvements</span>
                                <span class="text-xs text-base-content/40">2</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-info mt-1.5 shrink-0"></span>
                                    <span><strong>Fault-Tolerant Action Pipelines:</strong> Submissions are now dynamically insulated from failing SMTP thread exceptions, preventing layout thread terminations.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-info mt-1.5 shrink-0"></span>
                                    <span><strong>Secure Cookie Synchronizations:</strong> Shifted global cookie persistence properties to explicit HTTPS secure flags to ensure seamless validation transport across isolated host containers.</span>
                                </li>
                            </ul>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="badge badge-error badge-sm gap-1"><i class="fa-solid fa-bug text-[10px]"></i>Bug Fixes</span>
                                <span class="text-xs text-base-content/40">2</span>
                            </div>
                            <ul class="space-y-1.5 ml-1">
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span>
                                    <span>Resolved a critical `PageNotFoundException` (404 Error) thrown during email validation handshakes due to tracking state cookie loss.</span>
                                </li>
                                <li class="flex items-start gap-2 text-sm text-base-content/80">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error mt-1.5 shrink-0"></span>
                                    <span>Fixed a native Shield `LogicException` rule boundary where lingering active workflow states blocked initial logins upon verification execution.</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.3.1</span>
                        <span class="badge badge-outline badge-sm">Minor</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 28, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Operations &amp; Support Overhaul</h2>
                    <p class="text-sm text-base-content/70 mt-1">Ski Patrol deployment arrives on the mountain, completely shifting the resort vibe alongside upgraded Admin chat infrastructure and unmatched system aura.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.3</span>
                        <span class="badge badge-outline badge-sm">Minor</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 14, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Polish &amp; Quality-of-Life</h2>
                    <p class="text-sm text-base-content/70 mt-1">A round of refinements across the site plus important fixes to daily bonuses and the tutorial.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.2</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 10, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">UI Overhaul &amp; New Features</h2>
                    <p class="text-sm text-base-content/70 mt-1">Major UI improvements across all pages, support chat, voting system, compliance hub, equipment shop redesign, and dozens of fixes.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.1</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 9, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Economy Rebalance &amp; Systems Overhaul</h2>
                    <p class="text-sm text-base-content/70 mt-1">Major economy rebalance, improved grooming/snowmaking/weather/finances, PWA support, and dozens of bug fixes.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v1.0</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 7, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Season 1 Launch Update</h2>
                    <p class="text-sm text-base-content/70 mt-1">Major update rebuilding the trail map, admin panel, unit system, and adding feature flags.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v0.5</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 7, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Season 1: Park City</h2>
                    <p class="text-sm text-base-content/70 mt-1">Season 1 officially launches June 7, 2026 at 12:00 AM Eastern Time. All players start fresh on Park City Mountain Resort.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v0.4</span>
                        <span class="badge badge-outline badge-sm">Minor</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 4, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Bug Fixes &amp; Dashboard Improvements</h2>
                    <p class="text-sm text-base-content/70 mt-1">Bug fixes, dashboard improvements, and developer tooling.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v0.3</span>
                        <span class="badge badge-outline badge-sm">Minor</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 3, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Polish &amp; Compliance Update</h2>
                    <p class="text-sm text-base-content/70 mt-1">Cookie consent, legal pages, analytics setup, cross-system integration, and quality-of-life improvements.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v0.2</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>June 1, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Resource Management Update</h2>
                    <p class="text-sm text-base-content/70 mt-1">Major update bringing resource management, terrain parks, and quality-of-life improvements to Ski Manager.</p>
                </div>
            </div>
        </div>

        <div class="relative pl-6">
            <span class="absolute -left-[9px] top-1.5 w-4 h-4 rounded-full border-2 border-base-100 bg-base-300"></span>
            <div class="card bg-base-100 shadow-sm border border-base-300">
                <div class="card-body p-5">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <span class="badge badge-ghost font-mono">v0.1</span>
                        <span class="badge badge-outline badge-sm">Major</span>
                        <span class="text-xs text-base-content/50 ml-auto"><i class="fa-solid fa-calendar mr-1"></i>May 27, 2026</span>
                    </div>
                    <h2 class="text-lg font-bold">Complete Rebuild</h2>
                    <p class="text-sm text-base-content/70 mt-1">Ski Manager has been completely rebuilt from the ground up with a modern technology stack and dramatically expanded gameplay.</p>
                </div>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
