<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Account Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?php $user = auth()->user(); $db = db_connect(); $identity = $db->table('auth_identities')->where('user_id', auth()->id())->where('type', 'email_password')->get()->getRowArray(); ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">
    <div class="flex items-center gap-3 mb-6">
        <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
        <h1 class="text-2xl font-bold"><i class="fa-solid fa-gear mr-2"></i>Account Settings</h1>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4" role="status"><span><?= session('success') ?></span></div><?php endif ?>
    <?php if (session('error')) : ?><div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div><?php endif ?>

    <!-- Username -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-user mr-1"></i>Username</h2>
        <form action="/account/username" method="post">
            <?= csrf_field() ?>
            <div class="flex gap-2">
                <input type="text" name="username" value="<?= esc($user->username) ?>" class="input input-bordered flex-1" required minlength="3" maxlength="30" autocomplete="username">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div></div>

    <!-- Email -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-envelope mr-1"></i>Email</h2>
        <form action="/account/email" method="post">
            <?= csrf_field() ?>
            <div class="flex gap-2">
                <input type="email" name="email" value="<?= esc($identity['secret'] ?? '') ?>" class="input input-bordered flex-1" required autocomplete="email">
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div></div>

    <!-- Password -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-key mr-1"></i>Change Password</h2>
        <form action="/account/password" method="post">
            <?= csrf_field() ?>
            <div class="form-control mb-3">
                <label class="label"><span class="label-text">Current Password</span></label>
                <input type="password" name="current_password" class="input input-bordered" required autocomplete="current-password">
            </div>
            <div class="form-control mb-3">
                <label class="label"><span class="label-text">New Password</span></label>
                <input type="password" name="new_password" class="input input-bordered" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-control mb-3">
                <label class="label"><span class="label-text">Confirm New Password</span></label>
                <input type="password" name="confirm_password" class="input input-bordered" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Change Password</button>
        </form>
    </div></div>

    <!-- Game Settings Link -->
    <div class="card bg-base-100 shadow-sm mb-4"><div class="card-body">
        <h2 class="font-bold text-base mb-3"><i class="fa-solid fa-sliders mr-1"></i>Game Settings</h2>
        <p class="text-sm text-base-content/60 mb-3">Units, currency, and theme preferences</p>
        <a href="/settings" class="btn btn-outline btn-sm">Game Settings</a>
    </div></div>

    <!-- Delete Account -->
    <div class="card bg-error/10 border border-error/30 shadow-sm"><div class="card-body">
        <h2 class="font-bold text-base text-error mb-3"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Delete Account</h2>
        <p class="text-sm text-base-content/60 mb-3">This permanently deletes your account, resort, and all game data. This cannot be undone.</p>
        <form action="/account/delete" method="post" onsubmit="return document.querySelector('[name=confirm_delete]').value === 'DELETE'">
            <?= csrf_field() ?>
            <div class="form-control mb-3">
                <label class="label"><span class="label-text">Type DELETE to confirm</span></label>
                <input type="text" name="confirm_delete" class="input input-bordered input-error" placeholder="DELETE" autocomplete="off">
            </div>
            <button type="submit" class="btn btn-error btn-sm">Delete My Account</button>
        </form>
    </div></div>
</div>
<?= $this->endSection() ?>
