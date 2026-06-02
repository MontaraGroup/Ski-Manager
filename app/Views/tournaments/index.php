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
        <span class="badge badge-outline">Day <?= $gameDay ?></span>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Your Tournaments -->
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-trophy mr-1"></i>Your Tournaments</h2>
            <?php if (empty($tournaments)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-trophy text-4xl text-base-content/20 mb-4"></i>
                    <h3 class="font-semibold text-lg">No tournaments hosted yet</h3>
                    <p class="text-sm text-base-content/50">Host a tournament to attract visitors and boost your reputation.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($tournaments as $t) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex flex-col md:flex-row md:items-center gap-3">
                            <div class="flex-1">
                                <div class="font-semibold"><?= esc($t['name']) ?></div>
                                <div class="text-xs text-base-content/50">
                                    Day <?= $t['start_day'] ?>–<?= $t['end_day'] ?> ·
                                    +<?= number_format((int)($t['visitors_boost'] ?? 0)) ?> visitors ·
                                    +<?= number_format((int)($t['reputation_boost'] ?? 0)) ?> reputation ·
                                    Cost: <?= currency((int) $t['prize_pool']) ?>
                                </div>
                                <?php if ($t['status'] === 'upcoming') : ?>
                                    <div class="text-xs text-info mt-1"><i class="fa-solid fa-clock mr-1"></i>Starts in <?= max(0, (int) $t['start_day'] - $gameDay) ?> day(s)</div>
                                <?php endif ?>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if ($t['status'] === 'active') : ?>
                                    <span class="badge badge-success badge-sm animate-pulse">Live Now</span>
                                <?php elseif ($t['status'] === 'upcoming') : ?>
                                    <span class="badge badge-info badge-sm">Upcoming</span>
                                    <form action="/tournaments/cancel/<?= $t['id'] ?>" method="post" onsubmit="return confirm('Cancel this tournament?')"><?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-xs text-error" aria-label="Delete"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                    </form>
                                <?php else : ?>
                                    <span class="badge badge-ghost badge-sm">Ended</span>
                                <?php endif ?>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>

            <!-- Special Events -->
            <h2 class="text-lg font-bold mb-3 mt-8"><i class="fa-solid fa-calendar-days mr-1"></i>Special Events</h2>
            <div class="space-y-3">
            <?php foreach ($events as $e) : ?>
                <?php $eventIcons = ['bonus' => 'fa-solid fa-gift text-success', 'weather' => 'fa-solid fa-cloud-bolt text-info', 'celebrity' => 'fa-solid fa-star text-warning', 'sale' => 'fa-solid fa-tag text-primary']; ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center shrink-0"><i class="<?= $eventIcons[$e['event_type']] ?? 'fa-solid fa-calendar' ?> text-lg"></i></div>
                        <div class="flex-1"><div class="font-semibold text-sm"><?= esc($e['name']) ?></div><div class="text-xs text-base-content/50"><?= esc($e['description']) ?></div><div class="text-xs text-base-content/50">Day <?= $e['game_day'] ?> · <?= $e['duration_days'] ?> day(s)</div></div>
                        <?php if ($e['active']) : ?><span class="badge badge-success badge-sm animate-pulse">Active</span><?php else : ?><span class="badge badge-ghost badge-sm">Inactive</span><?php endif ?>
                    </div>
                </div></div>
            <?php endforeach ?>
            </div>
        </div>

        <!-- Host Tournament -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-plus mr-1"></i>Host Tournament</h2>
            <div class="space-y-2">
            <?php foreach ($tournamentTypes as $key => $type) : ?>
                <form action="/tournaments/host" method="post"><?= csrf_field() ?>
                    <input type="hidden" name="type" value="<?= $key ?>">
                    <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left" onclick="return confirm('Host <?= $type['name'] ?> for <?= currency($type['cost']) ?>?')">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                    <i class="<?= $type['icon'] ?> text-warning"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-semibold text-sm"><?= $type['name'] ?></div>
                                    <div class="text-xs text-base-content/50"><?= $type['desc'] ?></div>
                                    <div class="text-xs text-base-content/50 mt-0.5">
                                        +<?= number_format($type['visitors']) ?> visitors · +<?= $type['reputation'] ?> rep · <?= $type['duration'] ?> day(s)
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="font-bold text-primary text-sm"><?= currency($type['cost']) ?></div>
                                </div>
                            </div>
                        </div>
                    </button>
                </form>
            <?php endforeach ?>
            </div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
