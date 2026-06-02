<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Scenic Lifts<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-camera mr-2 text-primary"></i>Scenic Lifts</h1>
            <p class="text-sm text-base-content/50">Designate lifts for summer sightseeing — earn revenue year-round</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($scenicLifts) ?></div>
            <div class="text-xs text-base-content/50">Scenic Lifts</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success"><?= currency($totalRevenue) ?></div>
            <div class="text-xs text-base-content/50">Summer Revenue/Day</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold"><?= count($lifts) ?></div>
            <div class="text-xs text-base-content/50">Total Lifts</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Scenic Lifts -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-mountain-sun mr-1 text-success"></i>Designated Scenic</h2>
            <?php if (empty($scenicLifts)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-8">
                    <i class="fa-solid fa-camera text-3xl text-base-content/20 mb-3"></i>
                    <p class="text-sm text-base-content/50">No scenic lifts yet. Designate lifts from the list on the right.</p>
                </div></div>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($scenicLifts as $lift) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-success/20 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-camera text-success"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold truncate"><?= esc($lift['name']) ?></div>
                                <div class="text-xs text-base-content/50"><?= distance((int)$lift['length_meters']) ?> · <?= currency(1500) ?>/day summer revenue</div>
                            </div>
                            <form action="/scenic-lifts/remove/<?= $lift['id'] ?>" method="post" onsubmit="return confirm('Remove scenic designation?')"><?= csrf_field() ?>
                                <button class="btn btn-ghost btn-xs text-error" aria-label="Delete"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                            </form>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>

        <!-- Available Lifts -->
        <div>
            <h2 class="text-lg font-bold mb-3"><i class="fa-solid fa-cable-car mr-1"></i>Available Lifts</h2>
            <?php if (empty($availableLifts)) : ?>
                <div class="card bg-base-100 shadow-sm"><div class="card-body text-center py-8">
                    <?php if (empty($lifts)) : ?>
                        <i class="fa-solid fa-cable-car text-3xl text-base-content/20 mb-3"></i>
                        <p class="text-sm text-base-content/50">No lifts built yet. <a href="/map" class="link link-primary">Build lifts</a> from the Trail Map.</p>
                    <?php else : ?>
                        <i class="fa-solid fa-check text-3xl text-success mb-3"></i>
                        <p class="text-sm text-base-content/50">All lifts are designated as scenic!</p>
                    <?php endif ?>
                </div></div>
            <?php else : ?>
                <div class="space-y-2">
                <?php foreach ($availableLifts as $lift) : ?>
                    <div class="card bg-base-100 shadow-sm"><div class="card-body p-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-cable-car text-base-content/40"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold truncate"><?= esc($lift['name']) ?></div>
                                <div class="text-xs text-base-content/50"><?= ucwords(str_replace('_', ' ', $lift['subtype'])) ?> · <?= distance((int)$lift['length_meters']) ?></div>
                            </div>
                            <form action="/scenic-lifts/designate" method="post"><?= csrf_field() ?>
                                <input type="hidden" name="item_id" value="<?= $lift['id'] ?>">
                                <button class="btn btn-primary btn-xs"><i class="fa-solid fa-camera mr-1"></i>Designate</button>
                            </form>
                        </div>
                    </div></div>
                <?php endforeach ?>
                </div>
            <?php endif ?>

            <div class="card bg-base-100 shadow-sm mt-4"><div class="card-body p-4">
                <h3 class="font-semibold text-sm mb-2"><i class="fa-solid fa-circle-info mr-1 text-info"></i>Scenic Lift Info</h3>
                <ul class="text-xs text-base-content/60 space-y-1.5">
                    <li><i class="fa-solid fa-sun mr-1"></i>Scenic lifts operate during the off-season (summer)</li>
                    <li><i class="fa-solid fa-coins mr-1"></i>Each scenic lift earns <?= currency(1500) ?>/day from sightseeing tourists</li>
                    <li><i class="fa-solid fa-mountain-sun mr-1"></i>Longer lifts with better views earn more</li>
                    <li><i class="fa-solid fa-star mr-1"></i>Scenic lifts boost resort prestige</li>
                </ul>
            </div></div>
        </div>

    </div>
</div>
<?= $this->endSection() ?>
