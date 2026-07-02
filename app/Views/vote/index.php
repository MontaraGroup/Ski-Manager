<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-check-to-slot mr-2 text-primary"></i>Vote for Season 4</h1>
            <p class="text-sm text-base-content/50">Choose the next resort after Park City. Your vote counts.</p>
        </div>
    </div>

    <div class="alert alert-info mb-6">
        <i class="fa-solid fa-check-circle"></i>
        <span>You voted for <strong>Aspen Snowmass</strong>. You can change your vote anytime.</span>
    </div>
    
    <div class="text-sm text-base-content/50 mb-4"><i class="fa-solid fa-chart-bar mr-1"></i> 5 votes cast so far</div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="card bg-base-100 shadow-sm w-full">
            <div class="card-body p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                            <i class="fa-solid fa-gem text-primary text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold">Deer Valley</div>
                            <div class="text-xs text-base-content/50">Park City, Utah</div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3">Known for luxury skiing, groomed runs, and no snowboarders allowed. Upscale dining and pristine conditions.</p>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress progress-primary flex-1 h-2" value="20" max="100"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right">20%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-base-content/40">1 vote</span>
                    <form action="/vote/cast" method="post">
                        <input type="hidden" name="csrf_test_name" value="4b6f7633e2e85d86331beb2e2b859739">
                        <input type="hidden" name="resort" value="DeerValley">
                        <button class="btn btn-sm btn-outline btn-primary gap-1"><i class="fa-solid fa-check-to-slot"></i> Vote</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="aura aura-sm text-primary/70 aura-glow rounded-2xl w-full">
            <div class="card bg-base-100 shadow-sm w-full">
                <div class="card-body p-4">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                                <i class="fa-solid fa-mountain-sun text-info text-lg"></i>
                            </div>
                            <div>
                                <div class="font-bold">Aspen Snowmass</div>
                                <div class="text-xs text-base-content/50">Aspen, Colorado</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="badge badge-primary badge-xs">Your vote</span>
                            <span class="badge badge-warning badge-xs">Leading</span>
                        </div>
                    </div>
                    <p class="text-xs text-base-content/60 mb-3">Four mountains in one. Mix of celebrity culture, expert terrain, and world-class snowfall.</p>
                    <div class="flex items-center gap-2 mb-2">
                        <progress class="progress progress-primary flex-1 h-2" value="60" max="100"></progress>
                        <span class="text-xs font-mono font-bold w-12 text-right">60%</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-base-content/40">3 votes</span>
                        <form action="/vote/cast" method="post">
                            <input type="hidden" name="csrf_test_name" value="4b6f7633e2e85d86331beb2e2b859739">
                            <input type="hidden" name="resort" value="AspenSnowmass">
                            <button class="btn btn-sm btn-primary btn-disabled gap-1" disabled><i class="fa-solid fa-check"></i> Voted</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm w-full">
            <div class="card-body p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                            <i class="fa-solid fa-mountain text-success text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold">Big Sky</div>
                            <div class="text-xs text-base-content/50">Big Sky, Montana</div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3">The biggest skiing in America. Massive vertical drop, wide open bowls, and uncrowded slopes.</p>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress progress-primary flex-1 h-2" value="0" max="100"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right">0%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-base-content/40">0 votes</span>
                    <form action="/vote/cast" method="post">
                        <input type="hidden" name="csrf_test_name" value="4b6f7633e2e85d86331beb2e2b859739">
                        <input type="hidden" name="resort" value="BigSkyCombo">
                        <button class="btn btn-sm btn-outline btn-primary gap-1"><i class="fa-solid fa-check-to-slot"></i> Vote</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm w-full">
            <div class="card-body p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                            <i class="fa-solid fa-crown text-warning text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold">Vail</div>
                            <div class="text-xs text-base-content/50">Vail, Colorado</div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3">Legendary back bowls, massive front-side grooming, and a vibrant village. The gold standard of American skiing.</p>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress progress-primary flex-1 h-2" value="0" max="100"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right">0%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-base-content/40">0 votes</span>
                    <form action="/vote/cast" method="post">
                        <input type="hidden" name="csrf_test_name" value="4b6f7633e2e85d86331beb2e2b859739">
                        <input type="hidden" name="resort" value="Vail">
                        <button class="btn btn-sm btn-outline btn-primary gap-1"><i class="fa-solid fa-check-to-slot"></i> Vote</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm w-full">
            <div class="card-body p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                            <i class="fa-solid fa-sun text-warning text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold">Palisades Tahoe</div>
                            <div class="text-xs text-base-content/50">Olympic Valley, California</div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3">Host of the 1960 Winter Olympics. Steep chutes, lake views, and California sunshine.</p>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress progress-primary flex-1 h-2" value="0" max="100"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right">0%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-base-content/40">0 votes</span>
                    <form action="/vote/cast" method="post">
                        <input type="hidden" name="csrf_test_name" value="4b6f7633e2e85d86331beb2e2b859739">
                        <input type="hidden" name="resort" value="PalisadesTahoe">
                        <button class="btn btn-sm btn-outline btn-primary gap-1"><i class="fa-solid fa-check-to-slot"></i> Vote</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm w-full">
            <div class="card-body p-4">
                <div class="flex items-start justify-between mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                            <i class="fa-solid fa-snowflake text-error text-lg"></i>
                        </div>
                        <div>
                            <div class="font-bold">Killington</div>
                            <div class="text-xs text-base-content/50">Killington, Vermont</div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-base-content/60 mb-3">The Beast of the East. Longest season on the East Coast, aggressive snowmaking, and rowdy apres-ski.</p>
                <div class="flex items-center gap-2 mb-2">
                    <progress class="progress progress-primary flex-1 h-2" value="20" max="100"></progress>
                    <span class="text-xs font-mono font-bold w-12 text-right">20%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-base-content/40">1 vote</span>
                    <form action="/vote/cast" method="post">
                        <input type="hidden" name="csrf_test_name" value="4b6f7633e2e85d86331beb2e2b859739">
                        <input type="hidden" name="resort" value="Killington">
                        <button class="btn btn-sm btn-outline btn-primary gap-1"><i class="fa-solid fa-check-to-slot"></i> Vote</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="collapse collapse-arrow bg-base-100 shadow-sm">
        <input type="checkbox" />
        <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i> How Voting Works</div>
        <div class="collapse-content text-sm text-base-content/70">
            <ul class="space-y-1 mt-2">
                <li><i class="fa-solid fa-check text-success text-xs mr-2"></i>One vote per player</li>
                <li><i class="fa-solid fa-rotate text-info text-xs mr-2"></i>Change your vote anytime before Season 4 starts</li>
                <li><i class="fa-solid fa-trophy text-warning text-xs mr-2"></i>The winning resort becomes the Season 4 map</li>
                <li><i class="fa-solid fa-mountain text-primary text-xs mr-2"></i>Seasons 1-3 are Park City (Sectors 1, 2, 3). Season 4 moves to a new mountain.</li>
            </ul>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
