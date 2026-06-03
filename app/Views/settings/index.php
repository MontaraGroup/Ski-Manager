<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Settings
    <!-- Game Difficulty -->
    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body">
            <h2 class="card-title text-lg">Game Difficulty</h2>
            <p class="text-sm text-base-content/60 mb-3">Changes how challenging the game is. Affects revenue, costs, decay rates, and available features.</p>
            <form action="/settings/difficulty" method="post">
                <?= csrf_field() ?>
                <?php $currentDifficulty = getDifficulty(); ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="difficulty" value="easy" class="peer hidden" <?= $currentDifficulty === 'easy' ? 'checked' : '' ?>>
                        <div class="card border-2 border-base-300 peer-checked:border-success peer-checked:bg-success/10 transition-colors">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-face-smile text-2xl text-success mb-2"></i>
                                <div class="font-bold">Easy</div>
                                <ul class="text-xs text-base-content/60 text-left mt-2 space-y-1">
                                    <li><i class="fa-solid fa-plus text-success mr-1"></i>+50% revenue</li>
                                    <li><i class="fa-solid fa-minus text-success mr-1"></i>-25% costs</li>
                                    <li><i class="fa-solid fa-minus text-success mr-1"></i>-50% decay</li>
                                    <li><i class="fa-solid fa-eye-slash text-success mr-1"></i>Simplified menus</li>
                                    <li><i class="fa-solid fa-coins text-success mr-1"></i>1M starting cash</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="difficulty" value="standard" class="peer hidden" <?= $currentDifficulty === 'standard' ? 'checked' : '' ?>>
                        <div class="card border-2 border-base-300 peer-checked:border-info peer-checked:bg-info/10 transition-colors">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-face-meh text-2xl text-info mb-2"></i>
                                <div class="font-bold">Standard</div>
                                <ul class="text-xs text-base-content/60 text-left mt-2 space-y-1">
                                    <li><i class="fa-solid fa-equals text-info mr-1"></i>Normal revenue</li>
                                    <li><i class="fa-solid fa-equals text-info mr-1"></i>Normal costs</li>
                                    <li><i class="fa-solid fa-equals text-info mr-1"></i>Normal decay</li>
                                    <li><i class="fa-solid fa-eye text-info mr-1"></i>All features</li>
                                    <li><i class="fa-solid fa-coins text-info mr-1"></i>500K starting cash</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="difficulty" value="hard" class="peer hidden" <?= $currentDifficulty === 'hard' ? 'checked' : '' ?>>
                        <div class="card border-2 border-base-300 peer-checked:border-error peer-checked:bg-error/10 transition-colors">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-skull text-2xl text-error mb-2"></i>
                                <div class="font-bold">Hard</div>
                                <ul class="text-xs text-base-content/60 text-left mt-2 space-y-1">
                                    <li><i class="fa-solid fa-minus text-error mr-1"></i>-25% revenue</li>
                                    <li><i class="fa-solid fa-plus text-error mr-1"></i>+30% costs</li>
                                    <li><i class="fa-solid fa-plus text-error mr-1"></i>+50% decay</li>
                                    <li><i class="fa-solid fa-gavel text-error mr-1"></i>2x inspections</li>
                                    <li><i class="fa-solid fa-coins text-error mr-1"></i>200K starting cash</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-save mr-1"></i>Save Difficulty</button>
            </form>
        </div>
    </div>

<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-gear mr-2"></i>Settings</h1>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Resort Name -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3"><i class="fa-solid fa-mountain-sun mr-2"></i>Resort Name</h2>
            <form action="/settings/resort-name" method="post" class="flex gap-2">
                <?= csrf_field() ?>
                <input type="text" name="resort_name" value="<?= esc($resort['name']) ?>" class="input input-bordered input-sm flex-1" maxlength="50" required>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check mr-1"></i> Save</button>
            </form>
        </div>
    </div>

    <!-- Units & Currency -->
    <form action="/settings" method="post">
        <?= csrf_field() ?>
        <div class="card bg-base-100 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="card-title text-base mb-3"><i class="fa-solid fa-ruler mr-2"></i>Units & Currency</h2>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="units" value="metric" class="peer hidden" <?= $units === 'metric' ? 'checked' : '' ?>>
                        <div class="border-2 border-base-300 rounded-lg p-4 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors">
                            <div class="font-bold text-center mb-2">Metric</div>
                            <div class="space-y-1 text-xs text-base-content/60">
                                <div class="flex justify-between"><span>Currency</span><span class="font-mono">€ Euro</span></div>
                                <div class="flex justify-between"><span>Temp</span><span class="font-mono">°C</span></div>
                                <div class="flex justify-between"><span>Distance</span><span class="font-mono">m / km</span></div>
                                <div class="flex justify-between"><span>Speed</span><span class="font-mono">km/h</span></div>
                                <div class="flex justify-between"><span>Snow</span><span class="font-mono">cm</span></div>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="units" value="imperial" class="peer hidden" <?= $units === 'imperial' ? 'checked' : '' ?>>
                        <div class="border-2 border-base-300 rounded-lg p-4 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors">
                            <div class="font-bold text-center mb-2">Imperial</div>
                            <div class="space-y-1 text-xs text-base-content/60">
                                <div class="flex justify-between"><span>Currency</span><span class="font-mono">$ USD</span></div>
                                <div class="flex justify-between"><span>Temp</span><span class="font-mono">°F</span></div>
                                <div class="flex justify-between"><span>Distance</span><span class="font-mono">ft / mi</span></div>
                                <div class="flex justify-between"><span>Speed</span><span class="font-mono">mph</span></div>
                                <div class="flex justify-between"><span>Snow</span><span class="font-mono">in</span></div>
                            </div>
                        </div>
                    </label>
                </div>
                <button type="submit" class="btn btn-primary btn-sm mt-4"><i class="fa-solid fa-floppy-disk mr-1"></i> Save Units</button>
            </div>
        </div>
    </form>

    <!-- Theme -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3"><i class="fa-solid fa-palette mr-2"></i>Theme</h2>
            <p class="text-sm text-base-content/60 mb-3">Use the toggle in the top-right corner of the navbar to switch between light and dark themes.</p>
            <div class="grid grid-cols-2 gap-3">
                <div class="border-2 border-base-300 rounded-lg p-3 text-center">
                    <i class="fa-solid fa-sun text-warning text-xl mb-1"></i>
                    <div class="text-sm font-semibold">Winter</div>
                    <div class="text-xs text-base-content/50">Light theme</div>
                </div>
                <div class="border-2 border-base-300 rounded-lg p-3 text-center">
                    <i class="fa-solid fa-moon text-info text-xl mb-1"></i>
                    <div class="text-sm font-semibold">Carbon Cloud</div>
                    <div class="text-xs text-base-content/50">Dark theme</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tutorial -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3"><i class="fa-solid fa-graduation-cap mr-2"></i>Tutorial</h2>
            <div class="flex items-center justify-between">
                <div>
                    <?php if ($tutorial && $tutorial['completed']) : ?>
                        <span class="badge badge-success gap-1"><i class="fa-solid fa-check"></i> Completed</span>
                    <?php elseif ($tutorial && $tutorial['skipped']) : ?>
                        <span class="badge badge-ghost gap-1"><i class="fa-solid fa-forward"></i> Skipped</span>
                    <?php elseif ($tutorial) : ?>
                        <span class="badge badge-info gap-1"><i class="fa-solid fa-spinner"></i> Step <?= $tutorial['current_step'] + 1 ?></span>
                    <?php else : ?>
                        <span class="badge badge-warning gap-1"><i class="fa-solid fa-circle"></i> Not started</span>
                    <?php endif ?>
                </div>
                <form action="/settings/reset-tutorial" method="post" onsubmit="return confirm('Restart the tutorial from the beginning?')">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-rotate-left"></i> Restart Tutorial</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Data Management -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3"><i class="fa-solid fa-database mr-2"></i>Data Management</h2>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">Notifications</div>
                        <div class="text-xs text-base-content/50"><?= $notifCount ?> notification<?= $notifCount !== 1 ? 's' : '' ?></div>
                    </div>
                    <form action="/settings/clear-notifications" method="post" onsubmit="return confirm('Delete all notifications?')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline btn-sm btn-error gap-1"><i class="fa-solid fa-trash"></i> Clear</button>
                    </form>
                </div>
                <div class="divider my-0"></div>
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold">Activity Log</div>
                        <div class="text-xs text-base-content/50"><?= $activityCount ?> entr<?= $activityCount !== 1 ? 'ies' : 'y' ?></div>
                    </div>
                    <form action="/settings/clear-activity" method="post" onsubmit="return confirm('Delete your entire activity log? This cannot be undone.')">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline btn-sm btn-error gap-1"><i class="fa-solid fa-trash"></i> Clear</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Link -->
    <div class="card bg-base-100 shadow-sm mb-4">
        <div class="card-body">
            <h2 class="card-title text-base mb-3"><i class="fa-solid fa-user-gear mr-2"></i>Account</h2>
            <p class="text-sm text-base-content/60 mb-3">Manage your password, email, and account settings.</p>
            <a href="/account" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-arrow-right"></i> Go to Account Settings</a>
        </div>
    </div>

</div>

    <!-- Game Difficulty -->
    <div class="card bg-base-100 shadow-sm border border-base-300">
        <div class="card-body">
            <h2 class="card-title text-lg">Game Difficulty</h2>
            <p class="text-sm text-base-content/60 mb-3">Changes how challenging the game is. Affects revenue, costs, decay rates, and available features.</p>
            <form action="/settings/difficulty" method="post">
                <?= csrf_field() ?>
                <?php $currentDifficulty = getDifficulty(); ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="difficulty" value="easy" class="peer hidden" <?= $currentDifficulty === 'easy' ? 'checked' : '' ?>>
                        <div class="card border-2 border-base-300 peer-checked:border-success peer-checked:bg-success/10 transition-colors">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-face-smile text-2xl text-success mb-2"></i>
                                <div class="font-bold">Easy</div>
                                <ul class="text-xs text-base-content/60 text-left mt-2 space-y-1">
                                    <li><i class="fa-solid fa-plus text-success mr-1"></i>+50% revenue</li>
                                    <li><i class="fa-solid fa-minus text-success mr-1"></i>-25% costs</li>
                                    <li><i class="fa-solid fa-minus text-success mr-1"></i>-50% decay</li>
                                    <li><i class="fa-solid fa-eye-slash text-success mr-1"></i>Simplified menus</li>
                                    <li><i class="fa-solid fa-coins text-success mr-1"></i>1M starting cash</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="difficulty" value="standard" class="peer hidden" <?= $currentDifficulty === 'standard' ? 'checked' : '' ?>>
                        <div class="card border-2 border-base-300 peer-checked:border-info peer-checked:bg-info/10 transition-colors">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-face-meh text-2xl text-info mb-2"></i>
                                <div class="font-bold">Standard</div>
                                <ul class="text-xs text-base-content/60 text-left mt-2 space-y-1">
                                    <li><i class="fa-solid fa-equals text-info mr-1"></i>Normal revenue</li>
                                    <li><i class="fa-solid fa-equals text-info mr-1"></i>Normal costs</li>
                                    <li><i class="fa-solid fa-equals text-info mr-1"></i>Normal decay</li>
                                    <li><i class="fa-solid fa-eye text-info mr-1"></i>All features</li>
                                    <li><i class="fa-solid fa-coins text-info mr-1"></i>500K starting cash</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="difficulty" value="hard" class="peer hidden" <?= $currentDifficulty === 'hard' ? 'checked' : '' ?>>
                        <div class="card border-2 border-base-300 peer-checked:border-error peer-checked:bg-error/10 transition-colors">
                            <div class="card-body p-4 text-center">
                                <i class="fa-solid fa-skull text-2xl text-error mb-2"></i>
                                <div class="font-bold">Hard</div>
                                <ul class="text-xs text-base-content/60 text-left mt-2 space-y-1">
                                    <li><i class="fa-solid fa-minus text-error mr-1"></i>-25% revenue</li>
                                    <li><i class="fa-solid fa-plus text-error mr-1"></i>+30% costs</li>
                                    <li><i class="fa-solid fa-plus text-error mr-1"></i>+50% decay</li>
                                    <li><i class="fa-solid fa-gavel text-error mr-1"></i>2x inspections</li>
                                    <li><i class="fa-solid fa-coins text-error mr-1"></i>200K starting cash</li>
                                </ul>
                            </div>
                        </div>
                    </label>
                </div>
                <button class="btn btn-primary btn-sm"><i class="fa-solid fa-save mr-1"></i>Save Difficulty</button>
            </form>
        </div>
    </div>

<?= $this->endSection() ?>
