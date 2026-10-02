<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<style>
/* Strict zero-hover styling */
.btn { transition: none !important; }
.card { transition: none !important; }
.btn:hover, .card:hover { transform: none !important; }
</style>

<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <!-- Top Navigation & Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle" aria-label="Back to dashboard">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-2xl lg:text-3xl font-bold tracking-tight">
                        <i class="fa-solid fa-check-to-slot mr-2 text-primary"></i>Vote for Season 4 Resort
                    </h1>
                </div>
                <p class="text-sm text-base-content/60 mt-0.5">
                    Help choose the next mountain destination. The winning resort becomes the official community map in Season 4.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 self-start md:self-auto">
            <div class="badge badge-outline gap-1 text-xs py-3 px-3 font-mono">
                <i class="fa-solid fa-mountain text-primary"></i>
                Season <?= esc($seasonNumber) ?> &bull; Day <?= esc($seasonDay) ?> / <?= esc($seasonLength) ?>
            </div>
            <div class="badge badge-primary badge-outline text-xs py-3 px-3">
                <i class="fa-solid fa-flag-checkered mr-1"></i> Season 4 Decision
            </div>
        </div>
    </div>

    <!-- User Vote Status Alert -->
    <?php if ($userVote && isset($options[$userVote['resort_key']])): ?>
        <?php $currentChoice = $options[$userVote['resort_key']]; ?>
        <div class="alert alert-success shadow-sm mb-6 border border-success/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-success/20 text-success flex items-center justify-center shrink-0 text-lg">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <div class="font-bold text-sm">
                        You have voted for <strong><?= esc($currentChoice['name']) ?></strong>!
                    </div>
                    <div class="text-xs opacity-80">
                        Recorded <?= !empty($userVote['updated_at']) ? timeAgo($userVote['updated_at']) : (!empty($userVote['created_at']) ? timeAgo($userVote['created_at']) : 'recently') ?>. 
                        You can switch your vote anytime before Season 4 commences or retract your vote.
                    </div>
                </div>
            </div>
            <form action="/vote/retract" method="post" class="shrink-0">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-xs sm:btn-sm btn-ghost text-error gap-1">
                    <i class="fa-solid fa-rotate-left"></i> Retract Vote
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="alert alert-info shadow-sm mb-6 border border-info/30 flex items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-info/20 text-info flex items-center justify-center shrink-0 text-lg">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <div class="font-bold text-sm">Cast your vote for Season 4!</div>
                    <div class="text-xs opacity-80">
                        Review the mountain specifications and gameplay perks below. Every manager gets one vote.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Aggregate Vote Share Progress & Telemetry -->
    <div class="card bg-base-100 shadow-sm border border-base-200 mb-8">
        <div class="card-body p-4 sm:p-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-sm"><i class="fa-solid fa-chart-pie mr-1 text-primary"></i> Community Vote Distribution</span>
                    <span class="badge badge-ghost badge-sm font-mono"><?= number_format($totalVotes) ?> <?= $totalVotes === 1 ? 'vote' : 'votes' ?> total</span>
                </div>
                <?php if ($leadingResort && isset($options[$leadingResort]) && $totalVotes > 0): ?>
                    <div class="text-xs text-base-content/70 flex items-center gap-1.5">
                        <span class="badge badge-warning badge-xs font-semibold"><i class="fa-solid fa-crown text-[10px] mr-1"></i> Frontrunner:</span>
                        <strong class="text-base-content"><?= esc($options[$leadingResort]['name']) ?></strong>
                        <span class="font-mono text-primary font-bold">(<?= round(($voteCounts[$leadingResort] / $totalVotes) * 100, 1) ?>%)</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Segmented Bar -->
            <div class="h-4 bg-base-300 rounded-full overflow-hidden flex w-full">
                <?php if ($totalVotes > 0): ?>
                    <?php foreach ($options as $k => $opt): ?>
                        <?php 
                            $cnt = $voteCounts[$k] ?? 0;
                            $pct = round(($cnt / $totalVotes) * 100, 1);
                        ?>
                        <?php if ($pct > 0): ?>
                            <div class="<?= esc($opt['bar_color']) ?> h-full" style="width: <?= $pct ?>%;" title="<?= esc($opt['name']) ?>: <?= $pct ?>% (<?= $cnt ?> votes)"></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="bg-base-200 w-full h-full flex items-center justify-center text-[10px] text-base-content/50">
                        No votes recorded yet &bull; Be the first to vote!
                    </div>
                <?php endif; ?>
            </div>

            <!-- Legend Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 pt-3 text-xs">
                <?php foreach ($options as $k => $opt): ?>
                    <?php 
                        $cnt = $voteCounts[$k] ?? 0;
                        $pct = $totalVotes > 0 ? round(($cnt / $totalVotes) * 100, 1) : 0;
                    ?>
                    <div class="flex items-center gap-1.5 truncate">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0 <?= esc($opt['bar_color']) ?>"></span>
                        <span class="truncate font-medium"><?= esc($opt['name']) ?></span>
                        <span class="text-base-content/40 font-mono ml-auto"><?= $pct ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Candidate Resorts Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-8">
        <?php foreach ($options as $k => $opt): ?>
            <?php 
                $cnt = $voteCounts[$k] ?? 0;
                $pct = $totalVotes > 0 ? round(($cnt / $totalVotes) * 100, 1) : 0;
                $isUserChoice = ($userVote && $userVote['resort_key'] === $k);
                $isLeader = ($leadingResort === $k && $cnt > 0);
            ?>
            <div class="card bg-base-100 shadow-sm border <?= $isUserChoice ? 'border-2 border-primary bg-primary/[0.02]' : 'border-base-200' ?> flex flex-col justify-between">
                <div class="card-body p-5">
                    <!-- Resort Card Header -->
                    <div class="flex items-start justify-between gap-3 mb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl <?= esc($opt['bg_accent']) ?> flex items-center justify-center shrink-0 text-xl <?= esc($opt['color']) ?>">
                                <i class="<?= esc($opt['icon']) ?>"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-bold text-base text-base-content"><?= esc($opt['name']) ?></h3>
                                </div>
                                <div class="text-xs text-base-content/50 flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-[10px]"></i> <?= esc($opt['location']) ?>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <?php if ($isUserChoice): ?>
                                <span class="badge badge-primary badge-sm font-semibold gap-1">
                                    <i class="fa-solid fa-check text-[10px]"></i> Your Choice
                                </span>
                            <?php endif; ?>
                            <?php if ($isLeader): ?>
                                <span class="badge badge-warning badge-sm font-semibold gap-1 text-warning-content">
                                    <i class="fa-solid fa-crown text-[10px]"></i> Leading
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tagline & Description -->
                    <div class="text-xs font-semibold italic text-primary/80 mb-1">
                        &ldquo;<?= esc($opt['tagline']) ?>&rdquo;
                    </div>
                    <p class="text-xs text-base-content/70 leading-relaxed mb-4">
                        <?= esc($opt['desc']) ?>
                    </p>

                    <!-- Mountain Specs Grid -->
                    <div class="grid grid-cols-4 gap-2 p-2.5 rounded-lg bg-base-200/50 text-center mb-4 border border-base-200">
                        <div>
                            <div class="text-[10px] uppercase font-bold text-base-content/40 tracking-wider">Vertical</div>
                            <div class="text-xs font-mono font-bold text-base-content mt-0.5"><?= esc($opt['specs']['vertical']) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-base-content/40 tracking-wider">Acres</div>
                            <div class="text-xs font-mono font-bold text-base-content mt-0.5"><?= esc($opt['specs']['acres']) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-base-content/40 tracking-wider">Summit</div>
                            <div class="text-xs font-mono font-bold text-base-content mt-0.5"><?= esc($opt['specs']['summit']) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] uppercase font-bold text-base-content/40 tracking-wider">Snowfall</div>
                            <div class="text-xs font-mono font-bold text-base-content mt-0.5"><?= esc($opt['specs']['snowfall']) ?></div>
                        </div>
                    </div>

                    <!-- Terrain Difficulty Distribution -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-[11px] font-semibold text-base-content/60 mb-1.5">
                            <span>Terrain Difficulty Split</span>
                            <span class="text-[10px] font-mono text-base-content/40">G / B / Blk / Double-Blk</span>
                        </div>
                        <div class="h-2 w-full bg-base-300 rounded-full overflow-hidden flex">
                            <div class="bg-emerald-500 h-full" style="width: <?= $opt['specs']['difficulty']['green'] ?>%;" title="Green: <?= $opt['specs']['difficulty']['green'] ?>%"></div>
                            <div class="bg-blue-500 h-full" style="width: <?= $opt['specs']['difficulty']['blue'] ?>%;" title="Blue: <?= $opt['specs']['difficulty']['blue'] ?>%"></div>
                            <div class="bg-zinc-800 h-full" style="width: <?= $opt['specs']['difficulty']['black'] ?>%;" title="Black Diamond: <?= $opt['specs']['difficulty']['black'] ?>%"></div>
                            <div class="bg-rose-600 h-full" style="width: <?= $opt['specs']['difficulty']['double_black'] ?>%;" title="Double Black: <?= $opt['specs']['difficulty']['double_black'] ?>%"></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-base-content/50 mt-1 font-mono">
                            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> <?= $opt['specs']['difficulty']['green'] ?>%</span>
                            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> <?= $opt['specs']['difficulty']['blue'] ?>%</span>
                            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-zinc-800"></span> <?= $opt['specs']['difficulty']['black'] ?>%</span>
                            <span class="flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span> <?= $opt['specs']['difficulty']['double_black'] ?>%</span>
                        </div>
                    </div>

                    <!-- Season 4 Gameplay Perk -->
                    <div class="p-3 rounded-lg border border-primary/20 bg-primary/[0.04] mb-4">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <div class="flex items-center gap-1.5 text-xs font-bold text-primary">
                                <i class="<?= esc($opt['perk']['icon']) ?>"></i>
                                <span><?= esc($opt['perk']['title']) ?></span>
                            </div>
                            <span class="badge badge-primary badge-outline badge-xs text-[10px]">
                                <?= esc($opt['perk']['badge']) ?>
                            </span>
                        </div>
                        <p class="text-xs text-base-content/80 leading-normal">
                            <?= esc($opt['perk']['desc']) ?>
                        </p>
                    </div>

                    <!-- Vote Progress & Action Button -->
                    <div class="pt-2 border-t border-base-200">
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="text-base-content/50 font-mono"><?= $cnt ?> <?= $cnt === 1 ? 'vote' : 'votes' ?></span>
                            <span class="font-mono font-bold <?= $isLeader ? 'text-warning' : 'text-base-content' ?>"><?= $pct ?>%</span>
                        </div>
                        <progress class="progress <?= $isUserChoice ? 'progress-primary' : ($isLeader ? 'progress-warning' : 'progress-secondary') ?> w-full h-2 mb-3" value="<?= $pct ?>" max="100"></progress>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-base-content/40">
                                <?php if ($isUserChoice): ?>
                                    <i class="fa-solid fa-circle-check text-primary mr-1"></i>Your current selection
                                <?php else: ?>
                                    Click to support this mountain
                                <?php endif; ?>
                            </span>

                            <?php if ($isUserChoice): ?>
                                <button class="btn btn-sm btn-primary cursor-default opacity-90 gap-1" disabled>
                                    <i class="fa-solid fa-check"></i> Voted
                                </button>
                            <?php else: ?>
                                <form action="/vote/cast" method="post" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="resort" value="<?= esc($k) ?>">
                                    <button type="submit" class="btn btn-sm <?= $userVote ? 'btn-outline btn-primary' : 'btn-primary' ?> gap-1">
                                        <i class="fa-solid fa-check-to-slot"></i> <?= $userVote ? 'Switch Vote' : 'Vote' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Bottom Row: Recent Community Pulse & Voting Rules -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Recent Votes Activity Feed -->
        <div class="card bg-base-100 shadow-sm border border-base-200 lg:col-span-1">
            <div class="card-body p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-bold text-sm flex items-center gap-1.5">
                        <i class="fa-solid fa-wave-square text-primary"></i> Live Voting Pulse
                    </h2>
                    <span class="badge badge-ghost badge-xs">Recent</span>
                </div>
                
                <?php if (!empty($recentVotes)): ?>
                    <div class="divide-y divide-base-200">
                        <?php foreach ($recentVotes as $rv): ?>
                            <?php 
                                $rOpt = $options[$rv['resort_key']] ?? null;
                                $timeStr = !empty($rv['updated_at']) ? $rv['updated_at'] : (!empty($rv['created_at']) ? $rv['created_at'] : 'now');
                            ?>
                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between gap-2 text-xs">
                                <div class="flex items-center gap-2 truncate">
                                    <div class="w-6 h-6 rounded-full bg-base-200 flex items-center justify-center shrink-0 text-xs">
                                        <i class="fa-solid fa-user text-base-content/40"></i>
                                    </div>
                                    <div class="truncate">
                                        <span class="font-semibold text-base-content"><?= esc($rv['username'] ?? 'Anonymous Skier') ?></span>
                                        <span class="text-base-content/40">&rarr;</span>
                                        <span class="font-medium <?= $rOpt['color'] ?? 'text-primary' ?>"><?= esc($rOpt['name'] ?? $rv['resort_key']) ?></span>
                                    </div>
                                </div>
                                <span class="text-[10px] text-base-content/40 font-mono shrink-0">
                                    <?= timeAgo($timeStr) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="py-6 text-center text-xs text-base-content/40">
                        <i class="fa-solid fa-check-to-slot text-2xl mb-1 block opacity-30"></i>
                        No votes recorded yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- How Voting Works & Season 4 Roadmap -->
        <div class="card bg-base-100 shadow-sm border border-base-200 lg:col-span-2">
            <div class="card-body p-4 sm:p-5">
                <h2 class="font-bold text-sm mb-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-circle-question text-info"></i> How Voting & Season 4 Migration Works
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-base-content/70">
                    <div class="p-3 rounded-lg bg-base-200/40 border border-base-200">
                        <div class="font-bold text-base-content mb-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-arrows-spin text-primary"></i> Flexible Voting
                        </div>
                        <p>
                            Each manager has one active vote. You may change your vote or retract it at any point during Seasons 1–3 as community standings shift.
                        </p>
                    </div>

                    <div class="p-3 rounded-lg bg-base-200/40 border border-base-200">
                        <div class="font-bold text-base-content mb-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-trophy text-warning"></i> Winning Destination
                        </div>
                        <p>
                            At the conclusion of Season 3, the resort with the highest total votes will be officially adopted as the Season 4 map with custom topology and slope sectors.
                        </p>
                    </div>

                    <div class="p-3 rounded-lg bg-base-200/40 border border-base-200">
                        <div class="font-bold text-base-content mb-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-map-location-dot text-success"></i> Seasons 1–3 Progression
                        </div>
                        <p>
                            Seasons 1, 2, and 3 are situated in Park City, progressively unlocking Sector 1 (Base/Payday), Sector 2 (King Con), and Sector 3 (McConkey's Bowl).
                        </p>
                    </div>

                    <div class="p-3 rounded-lg bg-base-200/40 border border-base-200">
                        <div class="font-bold text-base-content mb-1 flex items-center gap-1.5">
                            <i class="fa-solid fa-sparkles text-amber-500"></i> Unique In-Game Perks
                        </div>
                        <p>
                            Each mountain bestows persistent gameplay modifiers to all resorts during Season 4 (such as snowmaking efficiency, luxury pricing, or freeride ticket revenue).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
