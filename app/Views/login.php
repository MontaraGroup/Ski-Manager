<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Log In<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="min-h-[85vh] flex items-center justify-center px-4 py-8">
    <div class="w-full max-w-4xl grid lg:grid-cols-2 rounded-2xl overflow-hidden shadow-2xl">
        <div class="hidden lg:flex flex-col justify-between p-8 bg-gradient-to-br from-primary to-secondary text-primary-content relative overflow-hidden">
            <div class="absolute inset-0 opacity-10">
                <i class="fa-solid fa-mountain-sun text-[18rem] absolute -bottom-12 -right-10"></i>
            </div>
            <div class="relative">
                <div class="flex items-center gap-2 text-xl font-extrabold">
                    <i class="fa-solid fa-mountain-sun"></i> Ski Manager
                </div>
                <p class="mt-2 text-sm text-primary-content/80">Build, groom, and grow your dream resort.</p>
            </div>
            <div class="relative space-y-3 mt-8">
                <div class="flex items-center gap-3 text-sm"><i class="fa-solid fa-snowflake w-5"></i> Real hourly weather & snowmaking</div>
                <div class="flex items-center gap-3 text-sm"><i class="fa-solid fa-cable-car w-5"></i> Design lifts, slopes & terrain parks</div>
                <div class="flex items-center gap-3 text-sm"><i class="fa-solid fa-trophy w-5"></i> Compete on the global leaderboard</div>
            </div>
            <p class="relative text-xs text-primary-content/60 mt-8">Free to play. No download required.</p>
        </div>
        <div class="bg-base-100 p-8 lg:p-10">
            <div class="max-w-sm mx-auto">
                <div class="lg:hidden flex items-center justify-center gap-2 text-lg font-extrabold text-primary mb-4">
                    <i class="fa-solid fa-mountain-sun"></i> Ski Manager
                </div>
                <h2 class="text-2xl font-bold mb-1">Welcome back</h2>
                <p class="text-sm text-base-content/50 mb-6">Log in to manage your resort.</p>
                <?php if (session('error')) : ?>
                    <div class="alert alert-error mb-4" role="alert"><span><?= session('error') ?></span></div>
                <?php endif ?>
                <?php if (session('message')) : ?>
                    <div class="alert alert-success mb-4" role="status"><span><?= session('message') ?></span></div>
                <?php endif ?>
                <form action="<?= url_to('login') ?>" method="post" class="space-y-4">
                    <?= csrf_field() ?>
                    <div class="form-control">
                        <label class="label py-1" for="email"><span class="label-text font-medium">Email</span></label>
                        <input type="email" name="email" id="email" class="input input-bordered w-full" placeholder="you@example.com" value="<?= old('email') ?>" required autofocus aria-required="true" autocomplete="email">
                    </div>
                    <div class="form-control">
                        <label class="label py-1" for="password"><span class="label-text font-medium">Password</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="password" class="input input-bordered w-full pr-12" placeholder="••••••••" required aria-required="true" autocomplete="current-password">
                            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-base-content/40 hover:text-base-content" onclick="const p=document.getElementById('password');const i=this.querySelector('i');if(p.type==='password'){p.type='text';i.className='fa-solid fa-eye-slash';}else{p.type='password';i.className='fa-solid fa-eye';}" aria-label="Toggle password visibility" tabindex="-1"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <?php if (setting('Auth.sessionConfig')['allowRemembering']) : ?>
                        <label class="label cursor-pointer justify-start gap-2 py-0">
                            <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" <?php if (old('remember')) : ?> checked<?php endif ?>>
                            <span class="label-text text-sm">Remember me</span>
                        </label>
                        <?php else : ?><span></span><?php endif ?>
                        <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                            <a href="<?= url_to('magic-link') ?>" class="link link-primary text-sm">Forgot password?</a>
                        <?php endif ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Log In</button>
                </form>
                <div class="divider text-xs text-base-content/40">OR</div>
                <div class="space-y-2">
                    <a href="/auth/google" class="btn btn-outline w-full gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.9 33.1 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.2-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.3 15.7 18.8 13 24 13c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.2 26.7 36 24 36c-5.4 0-9.9-3.5-11.5-8.3l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.7 39.5 44 34 44 24c0-1.3-.2-2.7-.4-3.9z"/></svg>
                        Continue with Google
                    </a>
                    <a href="/auth/discord" class="btn btn-outline w-full gap-2">
                        <i class="fa-brands fa-discord text-lg text-[#5865F2]"></i> Continue with Discord
                    </a>
                </div>
                <p class="text-center text-sm text-base-content/60 mt-6">
                    Don't have an account? <a href="<?= url_to('register') ?>" class="link link-primary font-medium">Sign up</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
