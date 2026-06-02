<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Environment<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <div>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-leaf mr-2 text-success"></i>Environment</h1>
            <p class="text-sm text-base-content/50">Manage your resort's environmental impact</p>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>

    <!-- Eco Score -->
    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body p-6 text-center">
        <div class="text-xs text-base-content/50 uppercase tracking-wider">Eco Score</div>
        <div class="text-5xl font-bold mt-1 <?= $env['eco_score'] > 70 ? 'text-success' : ($env['eco_score'] > 40 ? 'text-warning' : 'text-error') ?>"><?= $env['eco_score'] ?>/100</div>
        <progress class="progress <?= $env['eco_score'] > 70 ? 'progress-success' : ($env['eco_score'] > 40 ? 'progress-warning' : 'progress-error') ?> w-64 mx-auto mt-2" value="<?= $env['eco_score'] ?>" max="100"></progress>
        <p class="text-xs text-base-content/50 mt-2"><?= $env['eco_score'] > 70 ? 'Great! Eco-friendly resorts attract more visitors.' : ($env['eco_score'] > 40 ? 'Average. Consider investing in green upgrades.' : 'Poor. Risk of government penalties.') ?></p>
    </div></div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-error"><?= $totalCarbon ?></div>
            <div class="text-xs text-base-content/50">Carbon Output</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-success"><?= $env['renewable_pct'] ?>%</div>
            <div class="text-xs text-base-content/50">Renewable Energy</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-info"><?= $env['waste_management'] ?></div>
            <div class="text-xs text-base-content/50">Waste Management</div>
        </div></div>
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-3 text-center">
            <div class="text-2xl font-bold text-warning"><?= $env['wildlife_impact'] ?></div>
            <div class="text-xs text-base-content/50">Wildlife Score</div>
        </div></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Carbon Sources -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-semibold text-base mb-3"><i class="fa-solid fa-smog mr-1 text-error"></i>Carbon Sources</h2>
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between text-sm mb-1"><span>Snowmaking</span><span class="font-mono"><?= $carbonFromCannons ?></span></div>
                    <progress class="progress progress-error w-full" value="<?= $carbonFromCannons ?>" max="100"></progress>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1"><span>Night Skiing Lights</span><span class="font-mono"><?= $carbonFromLights ?></span></div>
                    <progress class="progress progress-warning w-full" value="<?= $carbonFromLights ?>" max="100"></progress>
                </div>
                <div>
                    <div class="flex justify-between text-sm mb-1"><span>Buildings</span><span class="font-mono"><?= $carbonFromBuildings ?></span></div>
                    <progress class="progress progress-info w-full" value="<?= $carbonFromBuildings ?>" max="100"></progress>
                </div>
            </div>
        </div></div>

        <!-- Green Upgrades -->
        <div class="card bg-base-100 shadow-sm"><div class="card-body p-4">
            <h2 class="font-semibold text-base mb-3"><i class="fa-solid fa-seedling mr-1 text-success"></i>Green Upgrades</h2>
            <div class="space-y-2">
            <?php foreach ($upgrades as $up) : ?>
                <form action="/environment/upgrade" method="post"><?= csrf_field() ?>
                    <input type="hidden" name="field" value="<?= $up['field'] ?>">
                    <input type="hidden" name="boost" value="<?= $up['boost'] ?>">
                    <button type="submit" class="flex items-center gap-3 w-full bg-base-200 rounded-lg p-3 hover:bg-base-300 transition-colors text-left">
                        <div class="w-8 h-8 rounded-lg bg-success/20 flex items-center justify-center shrink-0">
                            <i class="<?= $up['icon'] ?> text-success"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold text-sm"><?= $up['name'] ?></div>
                            <div class="text-xs text-base-content/50"><?= $up['desc'] ?></div>
                        </div>
                        <div class="font-bold text-primary text-sm shrink-0"><?= currency($up['cost']) ?></div>
                    </button>
                </form>
            <?php endforeach ?>
            </div>
        </div></div>
    </div>
</div>
<?= $this->endSection() ?>
