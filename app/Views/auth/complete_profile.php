<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Complete Your Profile<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="min-h-[80vh] flex items-center justify-center px-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-2">Welcome to Ski Manager!</h2>
            <p class="text-center text-base-content/60 mb-4">Set up your resort before you start playing.</p>

            <?php if (session('error')) : ?>
                <div class="alert alert-error mb-4"><span><?= session('error') ?></span></div>
            <?php endif ?>

            <form action="/complete-profile" method="post">
                <?= csrf_field() ?>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Game Difficulty</span></label>
                    <div class="grid grid-cols-3 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="difficulty" value="easy" class="peer hidden">
                            <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-success peer-checked:bg-success/10 transition-colors">
                                <i class="fa-solid fa-face-smile text-success text-lg"></i>
                                <div class="text-xs font-bold mt-1">Easy</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="difficulty" value="standard" class="peer hidden" checked>
                            <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-info peer-checked:bg-info/10 transition-colors">
                                <i class="fa-solid fa-face-meh text-info text-lg"></i>
                                <div class="text-xs font-bold mt-1">Standard</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="difficulty" value="hard" class="peer hidden">
                            <div class="border-2 border-base-300 rounded-lg p-3 text-center peer-checked:border-error peer-checked:bg-error/10 transition-colors">
                                <i class="fa-solid fa-skull text-error text-lg"></i>
                                <div class="text-xs font-bold mt-1">Hard</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="form-control mb-4">
                    <label class="label"><span class="label-text">Choose Your Resort</span></label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <?php $maps = ['ParkCity' => 'Park City, UT', 'Vail' => 'Vail, CO', 'AspenSnowmass' => 'Aspen, CO', 'DeerValley' => 'Deer Valley, UT', 'Killington' => 'Killington, VT', 'BigSkyCombo' => 'Big Sky, MT', 'PalisadesTahoe' => 'Palisades, CA']; ?>
                        <?php $icons = ['ParkCity'=>'fa-mountain-sun','Vail'=>'fa-mountain','AspenSnowmass'=>'fa-tree','DeerValley'=>'fa-person-skiing','Killington'=>'fa-snowflake','BigSkyCombo'=>'fa-cloud-sun','PalisadesTahoe'=>'fa-water']; ?>
                        <?php foreach ($maps as $key => $loc) : ?>
                        <?php $enabled = ($key === 'ParkCity'); ?>
                        <label class="<?= $enabled ? 'cursor-pointer' : '' ?>">
                            <input type="radio" name="resort_map" value="<?= $key ?>" class="peer hidden" <?= $enabled ? 'checked' : 'disabled' ?>>
                            <div class="border-2 rounded-lg p-2 text-center transition-colors <?= $enabled ? 'border-primary bg-primary/10' : 'border-base-300' ?>">
                                <div class="w-full h-12 rounded mb-1 flex items-center justify-center <?= $enabled ? 'bg-primary/10' : 'bg-base-300' ?>">
                                    <i class="fa-solid <?= $icons[$key] ?? 'fa-mountain' ?> text-xl <?= $enabled ? 'text-primary' : 'text-base-content/20' ?>"></i>
                                </div>
                                <div class="text-xs font-bold"><?= $key === 'BigSkyCombo' ? 'Big Sky' : ($key === 'AspenSnowmass' ? 'Aspen' : ($key === 'PalisadesTahoe' ? 'Palisades' : ($key === 'DeerValley' ? 'Deer Valley' : ($key === 'ParkCity' ? 'Park City' : $key)))) ?></div>
                                <div class="text-[10px] text-base-content/50"><?= $loc ?></div>
                                <?php if (!$enabled) : ?><div class="text-[9px] font-bold text-center mt-1"><span class="bg-neutral text-neutral-content px-1.5 py-0.5 rounded">Soon</span></div><?php endif ?>
                            </div>
                        </label>
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="form-control mb-4">
                    <label class="cursor-pointer flex items-start gap-2">
                        <input type="checkbox" name="terms" class="checkbox checkbox-primary checkbox-sm mt-0.5" required>
                        <span class="label-text text-sm">I agree to the <a href="/terms" target="_blank" class="link link-primary">Terms of Service</a> and <a href="/privacy" target="_blank" class="link link-primary">Privacy Policy</a></span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full">Start Playing</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
