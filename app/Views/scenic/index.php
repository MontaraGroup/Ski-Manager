<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Scenic Lifts & Sightseeing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-6xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-camera mr-2 text-primary"></i>Scenic Lifts & Sightseeing</h1>
            <p class="text-sm text-base-content/50">Turn your lifts into tourist attractions - set prices, add upgrades, earn year-round</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Revenue Dashboard -->
    <div class="card bg-gradient-to-r from-primary/10 to-secondary/10 shadow-sm mb-6"><div class="card-body p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-cable-car mr-1"></i>Scenic Lifts</div>
                <div class="text-2xl font-bold"><?= count($scenicLifts) ?></div>
                <div class="text-xs text-base-content/50">of <?= count($lifts) ?> total</div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-coins mr-1"></i>Peak Revenue</div>
                <div class="text-2xl font-bold text-success"><?= currency($totalDailyRevenue) ?></div>
                <div class="text-xs text-base-content/50">per summer day</div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-calendar mr-1"></i>Current Earning</div>
                <div class="text-2xl font-bold <?= $isSummer ? 'text-success' : 'text-warning' ?>"><?= currency($seasonRevenue) ?></div>
                <div class="text-xs text-base-content/50"><?= $isSummer ? 'Summer - full rate' : 'Winter - 30% rate' ?></div>
            </div>
            <div>
                <div class="text-xs text-base-content/50 mb-1"><i class="fa-solid fa-<?= $isSummer ? 'sun' : 'snowflake' ?> mr-1"></i>Season</div>
                <div class="text-2xl font-bold"><?= $isSummer ? 'Summer' : 'Winter' ?></div>
                <div class="text-xs text-base-content/50">Day <?= $seasonDay ?>/135</div>
            </div>
        </div>
    </div></div>

    <!-- Active Scenic Lifts -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-mountain-sun mr-1 text-success"></i> Your Scenic Lifts</h2>
    <?php if (empty($scenicLifts)) : ?>
        <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body text-center py-10">
            <i class="fa-solid fa-camera text-5xl text-base-content/15 mb-3"></i>
            <p class="font-semibold">No scenic lifts yet</p>
            <p class="text-sm text-base-content/50 mt-1">Designate a lift below to start earning from sightseeing tourists</p>
        </div></div>
    <?php else : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <?php foreach ($scenicLifts as $lift) : ?>
            <?php $rev = (int) ($lift['scenic']['revenue_per_day'] ?? 1500); ?>
            <div class="card bg-base-100 shadow-sm ">
                <div class="card-body p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                                <i class="fa-solid fa-cable-car text-primary text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold"><?= esc($lift['name']) ?></h3>
                                <div class="text-xs text-base-content/50"><?= ucwords(str_replace('_', ' ', $lift['subtype'] ?? '')) ?> · <?= distance((int)$lift['length_meters']) ?> · Sector <?= $lift['sector'] ?></div>
                            </div>
                        </div>
                        <form action="/scenic-lifts/remove/<?= $lift['id'] ?>" method="post" onsubmit="return confirm('Remove scenic status?')"><?= csrf_field() ?>
                            <button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-xmark"></i></button>
                        </form>
                    </div>

                    <!-- Ticket Price -->
                    <div class="mt-3 p-3 bg-base-200 rounded-lg">
                        <form action="/scenic-lifts/update-price/<?= $lift['id'] ?>" method="post" class="flex items-center gap-3">
                            <?= csrf_field() ?>
                            <div class="flex-1">
                                <label class="text-xs text-base-content/50">Ticket price per ride</label>
                                <input type="range" name="price" min="500" max="5000" step="100" value="<?= $rev ?>" class="range range-primary range-xs" oninput="this.nextElementSibling.textContent=new Intl.NumberFormat().format(this.value)+' €'">
                                <span class="text-sm font-bold"><?= number_format($rev) ?> €</span>
                            </div>
                            <button type="submit" class="btn btn-primary btn-xs"><i class="fa-solid fa-check"></i></button>
                        </form>
                        <div class="text-xs text-base-content/50 mt-1">Higher prices = more revenue but fewer riders. Sweet spot: 1,000-2,500€</div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-2 mt-3 text-center text-sm">
                        <div class="bg-base-200 rounded-lg p-2"><div class="font-bold text-success"><?= currency($rev) ?></div><div class="text-xs text-base-content/50">Revenue/day</div></div>
                        <div class="bg-base-200 rounded-lg p-2"><div class="font-bold"><?= $lift['condition_pct'] ?>%</div><div class="text-xs text-base-content/50">Condition</div></div>
                        <div class="bg-base-200 rounded-lg p-2"><div class="font-bold"><?= $lift['status'] === 'open' ? '✓' : '✗' ?></div><div class="text-xs text-base-content/50">Status</div></div>
                    </div>
                </div>
            </div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Available Lifts to Designate -->
    <?php if (!empty($availableLifts)) : ?>
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-plus-circle mr-1"></i> Add Scenic Lift</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
        <?php foreach ($availableLifts as $lift) : ?>
        <div class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow"><div class="card-body p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-lg bg-base-200 flex items-center justify-center">
                    <i class="fa-solid fa-cable-car text-base-content/40"></i>
                </div>
                <div>
                    <div class="font-semibold text-sm"><?= esc($lift['name']) ?></div>
                    <div class="text-xs text-base-content/50"><?= ucwords(str_replace('_', ' ', $lift['subtype'] ?? '')) ?> · <?= distance((int)$lift['length_meters']) ?></div>
                </div>
            </div>
            <form action="/scenic-lifts/designate" method="post"><?= csrf_field() ?>
                <input type="hidden" name="item_id" value="<?= $lift['id'] ?>">
                <div class="flex items-center gap-2 mb-2">
                    <label class="text-xs text-base-content/50">Starting price:</label>
                    <select name="ticket_price" class="select select-bordered select-xs">
                        <option value="1000">1,000€</option>
                        <option value="1500" selected>1,500€</option>
                        <option value="2000">2,000€</option>
                        <option value="2500">2,500€</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm w-full gap-1"><i class="fa-solid fa-camera"></i> Make Scenic</button>
            </form>
        </div></div>
        <?php endforeach ?>
    </div>
    <?php endif ?>

    <!-- Upgrade Ideas -->
    <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-sparkles mr-1 text-warning"></i> Available Upgrades</h2>
    <p class="text-sm text-base-content/60 mb-3">Coming soon - enhance your scenic lifts with premium experiences.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <?php foreach ($upgrades as $key => $up) : ?>
        <div class="card bg-base-200/50 border border-base-300 opacity-60"><div class="card-body p-3">
            <div class="flex items-center gap-2 mb-2">
                <i class="<?= $up['icon'] ?> text-warning"></i>
                <span class="font-semibold text-sm"><?= $up['name'] ?></span>
            </div>
            <p class="text-xs text-base-content/60"><?= $up['desc'] ?></p>
            <div class="text-xs mt-2"><span class="text-base-content/50">Cost:</span> <?= currency($up['cost']) ?> · <span class="text-success">+<?= currency($up['revenue_boost']) ?>/day</span></div>
            <button class="btn btn-ghost btn-xs w-full mt-2" disabled>Coming Soon</button>
        </div></div>
        <?php endforeach ?>
    </div>

    <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
        <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>How Scenic Lifts Work</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-base-content/60">
            <div class="space-y-1.5">
                <div><i class="fa-solid fa-sun mr-1 text-warning"></i> <strong>Summer:</strong> Full revenue from sightseeing tourists</div>
                <div><i class="fa-solid fa-snowflake mr-1 text-info"></i> <strong>Winter:</strong> 30% revenue (tourists still ride for views)</div>
                <div><i class="fa-solid fa-sliders mr-1"></i> Set ticket prices between 500-5,000€ per ride</div>
            </div>
            <div class="space-y-1.5">
                <div><i class="fa-solid fa-chart-line mr-1 text-success"></i> Higher prices = more revenue but fewer riders</div>
                <div><i class="fa-solid fa-wrench mr-1"></i> Scenic lifts still need maintenance and staff</div>
                <div><i class="fa-solid fa-star mr-1 text-warning"></i> Scenic lifts boost resort prestige and rating</div>
            </div>
        </div>
    </div></div>
</div>
<?= $this->endSection() ?>
