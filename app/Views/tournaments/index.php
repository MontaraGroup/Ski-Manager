<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Events & Tournaments<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-trophy mr-2 text-warning"></i>Events & Tournaments</h1>
            <p class="text-sm text-base-content/50">Host competitions to attract visitors and earn reputation</p>
        </div>
        <span class="badge badge-outline ml-auto">Day <?= $gameDay ?></span>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Stats -->
    <?php
        $activeTournaments = array_filter($tournaments, fn($t) => $t['status'] === 'active');
        $upcomingTournaments = array_filter($tournaments, fn($t) => $t['status'] === 'upcoming');
        $activeEvents = array_filter($events, fn($e) => $e['active'] ?? false);
    ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="aura aura-holo aura-xs rounded-2xl w-full">
            <div class="card bg-base-100 border border-base-200 shadow-sm w-full"><div class="card-body p-3 text-center">
                <div class="text-2xl font-bold text-success"><?= count($activeTournaments) ?></div>
                <div class="text-xs text-base-content/50">Live Now</div>
            </div></div>
        </div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-info"><?= count($upcomingTournaments) ?></div>
            <div class="text-xs text-base-content/50">Upcoming</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-warning"><?= count($activeEvents) ?></div>
            <div class="text-xs text-base-content/50">Active Events</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($tournaments) ?></div>
            <div class="text-xs text-base-content/50">Total Hosted</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Tournaments + Events -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-trophy mr-1"></i>Your Tournaments</h2>
            <?php if (empty($tournaments)) : ?>
                <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-trophy text-4xl text-base-content/20 mb-3"></i>
                    <p class="font-semibold">No tournaments hosted yet</p>
                    <p class="text-sm text-base-content/50 mt-1">Host one from the panel on the right.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-2 mb-6">
                <?php foreach ($tournaments as $t) : ?>
                    <div class="card bg-base-100 shadow-sm <?= $t['status'] === 'active' ? 'border border-success/30' : '' ?>"><div class="card-body p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg <?= $t['status'] === 'active' ? 'bg-success/10' : ($t['status'] === 'upcoming' ? 'bg-info/10' : 'bg-base-200') ?> flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-trophy <?= $t['status'] === 'active' ? 'text-success' : ($t['status'] === 'upcoming' ? 'text-info' : 'text-base-content/30') ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-sm"><?= esc($t['name']) ?></span>
                                    <?php if ($t['status'] === 'active') : ?><span class="badge badge-success badge-xs animate-pulse">Live</span>
                                    <?php elseif ($t['status'] === 'upcoming') : ?><span class="badge badge-info badge-xs">In <?= max(0, (int)$t['start_day'] - $gameDay) ?>d</span>
                                    <?php else : ?><span class="badge badge-ghost badge-xs">Ended</span><?php endif ?>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-base-content/50 mt-0.5">
                                    <span>Day <?= $t['start_day'] ?>-<?= $t['end_day'] ?></span>
                                    <span>+<?= number_format((int)($t['visitors_boost'] ?? 0)) ?> visitors</span>
                                    <span>+<?= (int)($t['reputation_boost'] ?? 0) ?> rep</span>
                                    <span>Prize: <?= currency((int)$t['prize_pool']) ?></span>
                                </div>
                            </div>
                            <?php if ($t['status'] === 'upcoming') : ?>
                            <form action="/tournaments/cancel/<?= $t['id'] ?>" method="post" data-confirm="Cancel this tournament?"><?= csrf_field() ?>
                                <button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                            <?php endif ?>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>

            <!-- Special Events -->
            <?php if (!empty($events)) : ?>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-calendar-days mr-1"></i>Special Events</h2>
            <div class="space-y-2">
            <?php foreach ($events as $e) : ?>
                <?php $eventIcons = ['bonus' => 'fa-solid fa-gift text-success', 'weather' => 'fa-solid fa-cloud-bolt text-info', 'celebrity' => 'fa-solid fa-star text-warning', 'sale' => 'fa-solid fa-tag text-primary']; ?>
                <div class="card bg-base-100 shadow-sm <?= ($e['active'] ?? false) ? 'border border-warning/30' : '' ?>"><div class="card-body p-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg <?= ($e['active'] ?? false) ? 'bg-warning/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                            <i class="<?= $eventIcons[$e['event_type']] ?? 'fa-solid fa-calendar' ?> text-lg"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-sm"><?= esc($e['name']) ?></span>
                                <?php if ($e['active'] ?? false) : ?><span class="badge badge-warning badge-xs animate-pulse">Active</span><?php endif ?>
                            </div>
                            <div class="text-xs text-base-content/50"><?= esc($e['description'] ?? '') ?></div>
                            <div class="text-xs text-base-content/40 mt-0.5">Day <?= $e['game_day'] ?> · <?= $e['duration_days'] ?> day<?= $e['duration_days'] > 1 ? 's' : '' ?></div>
                        </div>
                    </div>
                </div></div>
            <?php endforeach ?>
            </div>
            <?php endif ?>
        </div>

        <!-- Host Tournament -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-plus-circle mr-1"></i>Host Tournament</h2>
            <div class="space-y-2">
            <?php foreach ($tournamentTypes as $key => $type) : ?>
                <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow">
                    <div class="card-body p-3">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-9 h-9 rounded-lg bg-warning/10 flex items-center justify-center shrink-0">
                                <i class="<?= $type['icon'] ?? 'fa-solid fa-trophy' ?> text-warning"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm"><?= $type['name'] ?></div>
                                <div class="text-xs text-base-content/50"><?= $type['desc'] ?? '' ?></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-xs mb-2">
                            <span class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-people-group"></i>+<?= number_format($type['visitors'] ?? 0) ?></span>
                            <span class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-star"></i>+<?= $type['reputation'] ?? 0 ?></span>
                            <span class="badge badge-ghost badge-xs gap-1"><i class="fa-solid fa-clock"></i><?= $type['duration'] ?? 1 ?>d</span>
                        </div>
                        <div class="flex items-center justify-between border-t border-base-300 pt-2">
                            <div class="font-bold text-warning"><?= currency($type['cost'] ?? 0) ?></div>
                            <form action="/tournaments/host" method="post" data-confirm="Host <?= $type['name'] ?> for <?= currency($type['cost'] ?? 0) ?>?"><?= csrf_field() ?>
                                <input type="hidden" name="type" value="<?= $key ?>">
                                <button class="btn btn-warning btn-xs gap-1"><i class="fa-solid fa-flag-checkered"></i> Host</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
            </div>

            <!-- How It Works -->
            <div class="collapse collapse-arrow bg-base-100 shadow-sm mt-4">
                <input type="checkbox" />
                <div class="collapse-title font-semibold text-sm"><i class="fa-solid fa-circle-info mr-1"></i>How It Works</div>
                <div class="collapse-content text-sm text-base-content/70">
                    <ul class="space-y-1 mt-2">
                        <li><i class="fa-solid fa-coins text-warning text-xs mr-2"></i>Pay the prize pool upfront</li>
                        <li><i class="fa-solid fa-people-group text-info text-xs mr-2"></i>Tournaments attract extra visitors</li>
                        <li><i class="fa-solid fa-star text-warning text-xs mr-2"></i>Earn reputation for hosting</li>
                        <li><i class="fa-solid fa-calendar text-primary text-xs mr-2"></i>Events run for a set number of days</li>
                        <li><i class="fa-solid fa-seedling text-success text-xs mr-2"></i>Win Genepis from completed tournaments</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
