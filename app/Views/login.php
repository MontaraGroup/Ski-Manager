<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Log In<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="min-h-[80vh] flex items-center justify-center px-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Welcome Back</h2>

            <?php if (session('error')) : ?>
                <div class="alert alert-error mb-4" role="alert">
                    <span><?= session('error') ?></span>
                </div>
            <?php endif ?>

            <?php if (session('message')) : ?>
                <div class="alert alert-success mb-4" role="status">
                    <span><?= session('message') ?></span>
                </div>
            <?php endif ?>

            <form action="<?= url_to('login') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-control mb-4">
                    <label class="label" for="email">
                        <span class="label-text">Email</span>
                    </label>
                    <input type="email" name="email" id="email" class="input input-bordered w-full" placeholder="you@example.com" value="<?= old('email') ?>" required autofocus aria-required="true" autocomplete="email">
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="password">
                        <span class="label-text">Password</span>
                    </label>
                    <input type="password" name="password" id="password" class="input input-bordered w-full" placeholder="••••••••" required aria-required="true" autocomplete="current-password">
                </div>

                <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                <div class="form-control mb-4">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" <?php if (old('remember')) : ?> checked<?php endif ?>>
                        <span class="label-text">Remember me</span>
                    </label>
                </div>
                <?php endif ?>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">Log In</button>
                </div>
            </form>

            <div class="divider text-xs" >OR</div>
            <a href="/auth/google" class="btn btn-outline w-full gap-2" >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.9 33.1 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.2-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.3 15.7 18.8 13 24 13c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.2 26.7 36 24 36c-5.4 0-9.9-3.5-11.5-8.3l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.7 39.5 44 34 44 24c0-1.3-.2-2.7-.4-3.9z"/></svg>
                Continue with Google</a>
            <a href="/auth/discord" class="btn btn-outline w-full gap-2">
                <i class="fa-brands fa-discord text-lg"></i>
                Continue with Discord
            </a>

            <div class="divider" >OR</div>

            <div class="text-center space-y-2">
                <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                    <a href="<?= url_to('magic-link') ?>" class="link link-primary text-sm">Use Magic Link</a>
                <?php endif ?>
                <p class="text-sm text-base-content/60">
                    Don't have an account? <a href="<?= url_to('register') ?>" class="link link-primary">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
