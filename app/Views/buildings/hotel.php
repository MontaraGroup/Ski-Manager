<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Hotels<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php
    $db = db_connect(); $userId = auth()->id();
    $receptionists = $db->table('staff')->where('user_id', $userId)->where('role', 'receptionist')->where('status', 'active')->countAllResults();
    $visitors = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
    $dailyVisitors = (int) ($visitors['daily_visitors'] ?? 0);
    $openHotels = array_filter($buildings, fn($b) => $b['status'] === 'open');
    $occupiedBeds = min($totalCapacity, round($dailyVisitors * 0.3));
    $occupancyRate = $totalCapacity > 0 ? round($occupiedBeds / $totalCapacity * 100) : 0;
    $starRating = count($buildings) > 0 ? min(5, round(array_sum(array_column($buildings, 'level')) / count($buildings) + ($receptionists > 0 ? 1 : 0), 1)) : 0;
?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-hotel mr-2 text-primary"></i>Hotels & Lodging</h1>
                <p class="text-sm text-base-content/50">Manage accommodation for overnight guests</p>
            </div>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Hotel Overview Dashboard -->
    <div class="card bg-gradient-to-br from-primary/5 to-primary/10 shadow-sm border border-primary/20 mb-6">
        <div class="card-body p-5">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Star Rating</div>
                    <div class="flex items-center gap-1">
                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                            <i class="fa-<?= $i <= $starRating ? 'solid' : 'regular' ?> fa-star text-warning text-lg"></i>
                        <?php endfor ?>
                    </div>
                    <div class="text-xs text-base-content/50 mt-1"><?= $starRating ?>/5 average</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Tonight's Occupancy</div>
                    <div class="text-3xl font-bold"><?= $occupancyRate ?>%</div>
                    <div class="text-xs text-base-content/50"><?= $occupiedBeds ?>/<?= $totalCapacity ?> beds filled</div>
                    <progress class="progress progress-primary w-full mt-1" value="<?= $occupancyRate ?>" max="100"></progress>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Daily Revenue</div>
                    <div class="text-3xl font-bold text-success"><?= currency($totalRevenue) ?></div>
                    <div class="text-xs text-success">Net: <?= currency($totalRevenue - $totalUpkeep) ?>/day</div>
                </div>
                <div>
                    <div class="text-xs text-base-content/50 mb-1">Front Desk Staff</div>
                    <div class="text-3xl font-bold"><?= $receptionists ?></div>
                    <div class="text-xs <?= $receptionists >= count($openHotels) ? 'text-success' : 'text-warning' ?>"><?= $receptionists >= count($openHotels) ? 'Fully staffed' : 'Need ' . (count($openHotels) - $receptionists) . ' more' ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($receptionists < count($openHotels)) : ?>
    <div class="alert alert-warning mb-4"><i class="fa-solid fa-bell-concierge"></i><span>Some hotels have no receptionist. <a href="/staff/hire" class="link font-bold">Hire receptionists</a> to improve guest satisfaction and revenue.</span></div>
    <?php endif ?>

    <!-- Properties -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <h2 class="text-lg font-bold mb-3">Your Properties</h2>
            <?php if (empty($buildings)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-12">
                    <i class="fa-solid fa-hotel text-5xl text-base-content/15 mb-3"></i>
                    <p class="font-semibold">No hotels built yet</p>
                    <p class="text-sm text-base-content/50 mt-1">Build your first lodge to start earning overnight revenue.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-3">
                <?php foreach ($buildings as $b) : $isOpen = $b['status'] === 'open'; $cond = (int) $b['condition_pct']; $bOccupancy = $isOpen ? min(100, round(rand(40, 95))) : 0; ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
                        <div class="flex items-start gap-4">
                            <div class="w-16 h-16 rounded-xl <?= $isOpen ? 'bg-primary/10' : 'bg-base-200' ?> flex items-center justify-center shrink-0">
                                <?php if ($b['level'] == 1) : ?><i class="fa-solid fa-house text-2xl <?= $isOpen ? 'text-primary' : 'text-base-content/30' ?>"></i>
                                <?php elseif ($b['level'] == 2) : ?><i class="fa-solid fa-hotel text-2xl <?= $isOpen ? 'text-primary' : 'text-base-content/30' ?>"></i>
                                <?php else : ?><i class="fa-solid fa-building text-2xl <?= $isOpen ? 'text-primary' : 'text-base-content/30' ?>"></i>
                                <?php endif ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="font-bold"><?= esc($b['name']) ?></div>
                                    <span class="badge <?= $isOpen ? 'badge-success' : 'badge-ghost' ?> badge-sm"><?= $isOpen ? 'Open' : 'Closed' ?></span>
                                </div>
                                <div class="flex items-center gap-1 mb-2">
                                    <?php for ($i = 1; $i <= $b['level']; $i++) : ?><i class="fa-solid fa-star text-warning text-xs"></i><?php endfor ?>
                                    <?php for ($i = $b['level']+1; $i <= 3; $i++) : ?><i class="fa-regular fa-star text-base-content/20 text-xs"></i><?php endfor ?>
                                    <span class="text-xs text-base-content/50 ml-1"><?= $b['level'] == 1 ? 'Budget' : ($b['level'] == 2 ? 'Comfort' : 'Luxury') ?></span>
                                </div>
                                <div class="grid grid-cols-5 gap-1 text-center text-xs mb-3">
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $b['capacity'] ?></div><div class="text-[10px] text-base-content/50">Beds</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $bOccupancy ?>%</div><div class="text-[10px] text-base-content/50">Occ.</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-success"><?= currency($b['revenue_per_day']) ?></div><div class="text-[10px] text-base-content/50">Rev.</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold text-error"><?= currency($b['upkeep_per_day']) ?></div><div class="text-[10px] text-base-content/50">Cost</div></div>
                                    <div class="bg-base-200 rounded p-1.5"><div class="font-bold"><?= $cond ?>%</div><div class="text-[10px] text-base-content/50">Cond.</div></div>
                                </div>
                                <div class="flex gap-1">
                                    <form action="/buildings/toggle/<?= $b['id'] ?>" method="post" class="flex-1"><?= csrf_field() ?>
                                        <button class="btn btn-xs w-full <?= $isOpen ? 'btn-ghost' : 'btn-primary' ?>"><i class="fa-solid fa-power-off mr-1"></i><?= $isOpen ? 'Close' : 'Open' ?></button>
                                    </form>
                                    <?php if ($b['level'] < 3) : ?><form action="/buildings/upgrade/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Upgrade to <?= $b['level'] == 1 ? 'Comfort Hotel' : 'Luxury Resort' ?>?')"><?= csrf_field() ?><button class="btn btn-info btn-xs gap-1"><i class="fa-solid fa-arrow-up"></i> Upgrade</button></form><?php endif ?>
                                    <form action="/buildings/sell/<?= $b['id'] ?>" method="post" onsubmit="return confirm('Sell <?= esc($b['name']) ?>?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
                                </div>
                            </div>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <div>
            <h2 class="text-lg font-bold mb-3">Build New</h2>
            <div class="space-y-2">
            <?php $hotelInfo = [
                1 => ['tier' => 'Budget', 'star' => 1, 'desc' => 'Dormitory beds, shared bathrooms, basic amenities. Popular with backpackers.'],
                2 => ['tier' => 'Mid-Range', 'star' => 2, 'desc' => 'Private rooms, en-suite bathrooms, restaurant on-site. Families love these.'],
                3 => ['tier' => 'Luxury', 'star' => 3, 'desc' => 'Suites, spa access, concierge service, ski-in/ski-out. Premium pricing.'],
            ]; ?>
            <?php foreach ($def['levels'] as $lvl => $info) : $hi = $hotelInfo[$lvl]; ?>
                <form action="/buildings/build" method="post"><?= csrf_field() ?>
                    <input type="hidden" name="type" value="hotel"><input type="hidden" name="level" value="<?= $lvl ?>">
                    <button class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left"><div class="card-body p-3">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-semibold text-sm"><?= $info['name'] ?></span>
                            <div class="flex"><?php for ($i = 0; $i < $hi['star']; $i++) : ?><i class="fa-solid fa-star text-warning text-xs"></i><?php endfor ?></div>
                        </div>
                        <p class="text-xs text-base-content/50 mb-2"><?= $hi['desc'] ?></p>
                        <div class="flex justify-between text-xs">
                            <span><?= $info['capacity'] ?> beds - <?= currency($info['revenue']) ?>/day</span>
                            <span class="font-bold text-primary"><?= currency($info['cost']) ?></span>
                        </div>
                    </div></button>
                </form>
            <?php endforeach ?>
            </div>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-3">
                <h3 class="font-semibold text-xs mb-2"><i class="fa-solid fa-lightbulb mr-1 text-warning"></i>Revenue Boosters</h3>
                <ul class="text-xs text-base-content/60 space-y-1.5">
                    <li class="flex items-start gap-2"><i class="fa-solid fa-bell-concierge mt-0.5 text-primary"></i> <a href="/staff/hire" class="link link-primary">Receptionists</a> increase check-in speed and satisfaction</li>
                    <li class="flex items-start gap-2"><i class="fa-solid fa-utensils mt-0.5 text-warning"></i> Nearby <a href="/restaurants" class="link link-primary">restaurants</a> boost overnight bookings</li>
                    <li class="flex items-start gap-2"><i class="fa-solid fa-spa mt-0.5 text-secondary"></i> Luxury hotels benefit from <a href="/off-season" class="link link-primary">spa facilities</a></li>
                    <li class="flex items-start gap-2"><i class="fa-solid fa-bus mt-0.5 text-accent"></i> <a href="/transportation" class="link link-primary">Transport links</a> bring guests from further away</li>
                </ul>
            </div></div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
