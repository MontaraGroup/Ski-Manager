<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $user = auth()->user(); $db = db_connect(); $identity = $db->table('auth_identities')->where('user_id', auth()->id())->where('type', 'email_password')->get()->getRowArray(); $googleId = $db->table('auth_identities')->where('user_id', auth()->id())->where('type', 'google')->get()->getRowArray(); ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-gear mr-2"></i>Settings</h1>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Account -->
    <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">Account</h3>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-user mr-1"></i>Profile</h2>
        <form action="/account/username" method="post" class="flex gap-2 mb-3"><?= csrf_field() ?>
            <div class="flex-1">
                <label class="label py-0"><span class="label-text text-xs">Username</span></label>
                <input type="text" name="username" value="<?= esc($user->username) ?>" class="input input-bordered input-sm w-full" required minlength="3" maxlength="30" autocomplete="username">
            </div>
            <button type="submit" class="btn btn-primary btn-sm self-end">Save</button>
        </form>
        <form action="/account/email" method="post" class="flex gap-2"><?= csrf_field() ?>
            <div class="flex-1">
                <label class="label py-0"><span class="label-text text-xs">Email</span></label>
                <input type="email" name="email" value="<?= esc($identity['secret'] ?? '') ?>" class="input input-bordered input-sm w-full" required autocomplete="email">
            </div>
            <button type="submit" class="btn btn-primary btn-sm self-end">Save</button>
        </form>
    </div></div>

    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-key mr-1"></i>Password</h2>
        <form action="/account/password" method="post">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div><label class="label py-0"><span class="label-text text-xs">Current</span></label><input type="password" name="current_password" class="input input-bordered input-sm w-full" required autocomplete="current-password"></div>
                <div><label class="label py-0"><span class="label-text text-xs">New</span></label><input type="password" name="new_password" class="input input-bordered input-sm w-full" required minlength="8" autocomplete="new-password"></div>
                <div><label class="label py-0"><span class="label-text text-xs">Confirm</span></label><input type="password" name="confirm_password" class="input input-bordered input-sm w-full" required minlength="8" autocomplete="new-password"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Change Password</button>
        </form>
    </div></div>

    <!-- Linked Accounts -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-link mr-1"></i>Linked Accounts</h2>
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-brands fa-google text-lg"></i><span class="text-sm">Google</span></div>
                <?php if ($googleId) : ?>
                    <span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-check"></i> Connected</span>
                <?php else : ?>
                    <a href="/auth/google" class="btn btn-ghost btn-sm gap-1"><i class="fa-brands fa-google"></i> Connect</a>
                <?php endif ?>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2"><i class="fa-brands fa-discord text-lg text-indigo-400"></i><span class="text-sm">Discord</span></div>
                <a href="https://discord.gg/TyEnFdfd8w" target="_blank" class="btn btn-ghost btn-sm gap-1"><i class="fa-solid fa-arrow-up-right-from-square"></i> Join</a>
            </div>
        </div>
    </div></div>

    <div class="divider"></div>
    <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">Game</h3>

    <!-- Resort Name -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-mountain-sun mr-1"></i>Resort</h2>
        <form action="/settings/resort-name" method="post" class="flex gap-2 mb-3"><?= csrf_field() ?>
            <input type="text" name="resort_name" value="<?= esc($resort['name']) ?>" class="input input-bordered input-sm flex-1" maxlength="50" required>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-check"></i></button>
        </form>
        <div class="flex items-center justify-between">
            <div><span class="text-xs text-base-content/50">Resort Tours</span><p class="text-xs text-base-content/40">Let other players visit your resort</p></div>
            <form action="/settings/toggle-tours" method="post"><?= csrf_field() ?>
                <button type="submit" class="btn btn-sm <?= ($finance['allow_tours'] ?? 1) ? 'btn-success' : 'btn-ghost' ?> gap-1">
                    <i class="fa-solid <?= ($finance['allow_tours'] ?? 1) ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                    <?= ($finance['allow_tours'] ?? 1) ? 'On' : 'Off' ?>
                </button>
            </form>
        </div>
    </div></div>

    <!-- Units -->
    <form action="/settings" method="post">
        <?= csrf_field() ?>
        <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
            <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-ruler mr-1"></i>Units & Currency</h2>
            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="units" value="metric" class="peer hidden" <?= $units === 'metric' ? 'checked' : '' ?>>
                    <div class="border-2 border-base-300 rounded-lg p-3 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors text-center">
                        <div class="font-bold mb-1">Metric</div>
                        <div class="text-xs text-base-content/50">€ · °C · m · km/h · cm</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="units" value="imperial" class="peer hidden" <?= $units === 'imperial' ? 'checked' : '' ?>>
                    <div class="border-2 border-base-300 rounded-lg p-3 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors text-center">
                        <div class="font-bold mb-1">Imperial</div>
                        <div class="text-xs text-base-content/50">$ · °F · ft · mph · in</div>
                    </div>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-sm mt-3"><i class="fa-solid fa-floppy-disk mr-1"></i>Save</button>
        </div></div>
    </form>

    <!-- Theme -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-palette mr-1"></i>Theme</h2>
        <div class="grid grid-cols-2 gap-3">
            <button onclick="setTheme('winter')" class="border-2 rounded-lg p-3 text-center transition-colors hover:bg-base-200" id="theme-winter">
                <i class="fa-solid fa-sun text-warning text-xl mb-1"></i>
                <div class="text-sm font-semibold">Winter</div>
                <div class="text-xs text-base-content/50">Light</div>
            </button>
            <button onclick="setTheme('carboncloud')" class="border-2 rounded-lg p-3 text-center transition-colors hover:bg-base-200" id="theme-carboncloud">
                <i class="fa-solid fa-moon text-info text-xl mb-1"></i>
                <div class="text-sm font-semibold">Carbon Cloud</div>
                <div class="text-xs text-base-content/50">Dark</div>
            </button>
        </div>
    </div></div>

    <!-- Difficulty -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-sliders mr-1"></i>Difficulty</h2>
        <form action="/settings/difficulty" method="post">
            <?= csrf_field() ?>
            <?php $currentDifficulty = getDifficulty(); ?>
            <div class="grid grid-cols-3 gap-2 mb-3">
                <label class="cursor-pointer">
                    <input type="radio" name="difficulty" value="easy" class="peer hidden" <?= $currentDifficulty === 'easy' ? 'checked' : '' ?>>
                    <div class="border-2 border-base-300 rounded-lg p-3 peer-checked:border-success peer-checked:bg-success/10 text-center transition-colors">
                        <i class="fa-solid fa-face-smile text-success text-xl mb-1"></i>
                        <div class="text-sm font-bold">Easy</div>
                        <div class="text-[10px] text-base-content/50">+50% rev · -25% cost · $1M</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="difficulty" value="standard" class="peer hidden" <?= $currentDifficulty === 'standard' ? 'checked' : '' ?>>
                    <div class="border-2 border-base-300 rounded-lg p-3 peer-checked:border-info peer-checked:bg-info/10 text-center transition-colors">
                        <i class="fa-solid fa-face-meh text-info text-xl mb-1"></i>
                        <div class="text-sm font-bold">Standard</div>
                        <div class="text-[10px] text-base-content/50">Normal rates · $500K</div>
                    </div>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="difficulty" value="hard" class="peer hidden" <?= $currentDifficulty === 'hard' ? 'checked' : '' ?>>
                    <div class="border-2 border-base-300 rounded-lg p-3 peer-checked:border-error peer-checked:bg-error/10 text-center transition-colors">
                        <i class="fa-solid fa-skull text-error text-xl mb-1"></i>
                        <div class="text-sm font-bold">Hard</div>
                        <div class="text-[10px] text-base-content/50">-25% rev · +30% cost · $200K</div>
                    </div>
                </label>
            </div>
            <button class="btn btn-primary btn-sm"><i class="fa-solid fa-save mr-1"></i>Save</button>
        </form>
    </div></div>

    <div class="divider"></div>
    <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">Game Stats</h3>

    <!-- Stats -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-center text-sm">
            <div><div class="text-xs text-base-content/50">Joined</div><div class="font-bold"><?= date('M j, Y', strtotime($user->created_at)) ?></div></div>
            <div><div class="text-xs text-base-content/50">Difficulty</div><div class="font-bold"><?= ucfirst($currentDifficulty) ?></div></div>
            <div><div class="text-xs text-base-content/50">Resort Map</div><div class="font-bold"><?= esc($finance['resort_map'] ?? 'ParkCity') ?></div></div>
            <div><div class="text-xs text-base-content/50">Notifications</div><div class="font-bold"><?= $notifCount ?></div></div>
        </div>
    </div></div>

    <div class="divider"></div>
    <h3 class="text-sm font-semibold text-base-content/50 uppercase tracking-wider mb-3">Other</h3>

    <!-- Tutorial -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-sm"><i class="fa-solid fa-graduation-cap mr-1"></i>Tutorial</h2>
                <div class="mt-1">
                    <?php if ($tutorial && $tutorial['completed']) : ?><span class="badge badge-success badge-sm gap-1"><i class="fa-solid fa-check"></i>Completed</span>
                    <?php elseif ($tutorial && $tutorial['skipped']) : ?><span class="badge badge-ghost badge-sm gap-1"><i class="fa-solid fa-forward"></i>Skipped</span>
                    <?php elseif ($tutorial) : ?><span class="badge badge-info badge-sm gap-1">Step <?= $tutorial['current_step'] + 1 ?></span>
                    <?php else : ?><span class="badge badge-warning badge-sm gap-1">Not started</span><?php endif ?>
                </div>
            </div>
            <form action="/settings/reset-tutorial" method="post" data-confirm="Restart the tutorial from the beginning?"><?= csrf_field() ?>
                <button type="submit" class="btn btn-outline btn-sm gap-1"><i class="fa-solid fa-rotate-left"></i>Restart</button>
            </form>
        </div>
    </div></div>

    <!-- Data Management -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-semibold text-sm mb-3"><i class="fa-solid fa-database mr-1"></i>Data</h2>
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <div><div class="text-sm">Notifications</div><div class="text-xs text-base-content/40"><?= $notifCount ?> notification<?= $notifCount !== 1 ? 's' : '' ?></div></div>
                <form action="/settings/clear-notifications" method="post" data-confirm="Delete all notifications?"><?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline btn-xs btn-error gap-1"><i class="fa-solid fa-trash"></i>Clear</button>
                </form>
            </div>
            <div class="divider my-0"></div>
            <div class="flex items-center justify-between">
                <div><div class="text-sm">Activity Log</div><div class="text-xs text-base-content/40"><?= $activityCount ?> entr<?= $activityCount !== 1 ? 'ies' : 'y' ?></div></div>
                <form action="/settings/clear-activity" method="post" data-confirm="Delete your entire activity log?"><?= csrf_field() ?>
                    <button type="submit" class="btn btn-outline btn-xs btn-error gap-1"><i class="fa-solid fa-trash"></i>Clear</button>
                </form>
            </div>
            <div class="divider my-0"></div>
            <div class="flex items-center justify-between">
                <div><div class="text-sm">Cookies</div><div class="text-xs text-base-content/40">Manage cookie preferences</div></div>
                <button onclick="localStorage.removeItem('cookie_consent');location.reload();" class="btn btn-outline btn-xs gap-1"><i class="fa-solid fa-cookie-bite"></i>Manage</button>
            </div>
        </div>
    </div></div>

    <!-- Delete Account -->
    <div class="card bg-error/10 border border-error/30 shadow-sm mb-4"><div class="card-body p-4">
        <h2 class="font-bold text-sm text-error mb-2"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Delete Account</h2>
        <p class="text-xs text-base-content/60 mb-3">Permanently deletes your account, resort, and all game data.</p>
        <form action="/account/delete" method="post" data-confirm="This will permanently delete your account. Type DELETE to confirm." data-confirm-title="Delete Account">
            <?= csrf_field() ?>
            <div class="flex gap-2">
                <input type="text" name="confirm_delete" class="input input-bordered input-sm input-error flex-1" placeholder="Type DELETE" autocomplete="off">
                <button type="submit" class="btn btn-error btn-sm">Delete</button>
            </div>
        </form>
    </div></div>
</div>

<script>
function setTheme(t) {
    document.documentElement.setAttribute('data-theme', t);
    localStorage.setItem('theme', t);
    document.getElementById('themeToggle').checked = (t === 'winter');
    document.querySelectorAll('[id^="theme-"]').forEach(function(el) {
        el.classList.remove('border-primary', 'bg-primary/10');
        el.classList.add('border-base-300');
    });
    var active = document.getElementById('theme-' + t);
    if (active) { active.classList.add('border-primary', 'bg-primary/10'); active.classList.remove('border-base-300'); }
}
(function() {
    var current = localStorage.getItem('theme') || 'carboncloud';
    var active = document.getElementById('theme-' + current);
    if (active) { active.classList.add('border-primary', 'bg-primary/10'); active.classList.remove('border-base-300'); }
})();
</script>
<?= $this->endSection() ?>
