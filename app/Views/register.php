<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Sign Up<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
    $__isDemo = (bool) session()->get('is_demo');
    $__difficulty = old('difficulty') ?: 'standard';
    // Starting cash per difficulty, as set in Config/Events.php on registration
    $__levels = [
        'easy'     => ['Easy', 'green', 1000000, 'Room for mistakes'],
        'standard' => ['Standard', 'blue', 500000, 'The intended game'],
        'hard'     => ['Hard', 'black', 200000, 'No room for error'],
    ];
?>
<div class="auth-wrap">
    <div class="auth-card">
        <?= view('auth/_shell', ['variant' => 'register']) ?>

        <main class="auth-form">
            <div class="auth-form-inner">
                <h1><?= $__isDemo ? 'Claim your resort' : 'Create your account' ?></h1>
                <p class="auth-sub"><?= $__isDemo ? 'Save your demo mountain to a permanent account.' : 'Free, and you can be building in under a minute.' ?></p>

                <?php if ($__isDemo) : ?>
                    <div class="alert alert-info mb-4" role="status">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span class="text-sm">Your demo lifts, slopes, equipment and cash carry over to the new account.</span>
                    </div>
                <?php endif ?>

                <?php if (session('error')) : ?>
                    <div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-circle-exclamation"></i><span><?= esc(session('error')) ?></span></div>
                <?php endif ?>
                <?php if (session('errors')) : ?>
                    <div class="alert alert-error mb-4" role="alert"><i class="fa-solid fa-circle-exclamation"></i><div><?php foreach ((array) session('errors') as $error) : ?><p><?= esc($error) ?></p><?php endforeach ?></div></div>
                <?php endif ?>

                <?php if (!$__isDemo) : ?>
                    <?php $__discord = (string) env('DISCORD_CLIENT_ID', '') !== ''; // hidden until Discord sign-in is configured ?>
                    <div class="auth-social"<?= $__discord ? '' : ' style="grid-template-columns:1fr"' ?>>
                        <a href="/auth/google" class="btn btn-outline gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" class="w-5 h-5" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.1H42V20H24v8h11.3C33.9 33.1 29.4 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.2-2.7-.4-3.9z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.3 15.7 18.8 13 24 13c3.1 0 5.8 1.2 8 3l5.7-5.7C34 6 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.2 26.7 36 24 36c-5.4 0-9.9-3.5-11.5-8.3l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.1H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C36.7 39.5 44 34 44 24c0-1.3-.2-2.7-.4-3.9z"/></svg>
                            Google
                        </a>
                        <?php if ($__discord) : ?>
                        <a href="/auth/discord" class="btn btn-outline gap-2">
                            <i class="fa-brands fa-discord text-lg text-[#5865F2]" aria-hidden="true"></i> Discord
                        </a>
                        <?php endif ?>
                    </div>
                    <div class="auth-or">or sign up with email</div>
                <?php endif ?>

                <form action="<?= $__isDemo ? '/demo/claim' : url_to('register') ?>" method="post" class="space-y-4">
                    <?= csrf_field() ?>
                    <label class="auth-field" for="username">
                        <span>Username</span>
                        <input type="text" name="username" id="username" class="input input-bordered w-full" placeholder="Shown on the leaderboard" autocomplete="username" value="<?= esc(old('username') ?? '') ?>" required autofocus minlength="3" maxlength="30">
                    </label>
                    <label class="auth-field" for="email">
                        <span>Email</span>
                        <input type="email" name="email" id="email" class="input input-bordered w-full" placeholder="you@example.com" autocomplete="email" value="<?= esc(old('email') ?? '') ?>" required>
                    </label>
                    <div class="auth-field">
                        <span><label for="password">Password</label></span>
                        <div class="auth-pw">
                            <input type="password" name="password" id="password" class="input input-bordered w-full" placeholder="At least 8 characters" autocomplete="new-password" required minlength="8" aria-describedby="pwHint">
                            <button type="button" data-toggle-pw="password" aria-label="Show password" aria-pressed="false"><span data-pw-icon="show"><i class="fa-solid fa-eye" aria-hidden="true"></i></span><span data-pw-icon="hide" hidden><i class="fa-solid fa-eye-slash" aria-hidden="true"></i></span></button>
                        </div>
                    </div>
                    <label class="auth-field" for="password_confirm">
                        <span>Confirm password</span>
                        <input type="password" name="password_confirm" id="password_confirm" class="input input-bordered w-full" placeholder="Type it again" autocomplete="new-password" required minlength="8">
                        <div class="auth-hint" id="pwHint">8+ characters. Avoid common passwords or your username.</div>
                    </label>

                    <fieldset>
                        <legend class="text-[0.8125rem] font-medium mb-1.5">Difficulty <span class="font-normal opacity-60">· starting cash</span></legend>
                        <div class="diff">
                            <?php foreach ($__levels as $__key => [$__label, $__sign, $__cash, $__note]) : ?>
                                <label>
                                    <input type="radio" name="difficulty" value="<?= $__key ?>" class="sr-only" <?= $__difficulty === $__key ? 'checked' : '' ?>>
                                    <div class="opt">
                                        <b><span class="tsign <?= $__sign ?>"></span><?= $__label ?></b>
                                        <div class="cash"><?= currency($__cash) ?></div>
                                        <small><?= $__note ?></small>
                                    </div>
                                </label>
                            <?php endforeach ?>
                        </div>
                    </fieldset>

                    <label class="flex items-start gap-2 text-sm cursor-pointer">
                        <input type="checkbox" name="terms" class="checkbox checkbox-primary checkbox-sm mt-0.5" required oninvalid="this.setCustomValidity('You must accept the Terms of Service to create an account')" onchange="this.setCustomValidity('')">
                        <span>I agree to the <a href="/terms" target="_blank" rel="noopener noreferrer" class="link link-primary">Terms of Service</a> and <a href="/privacy" target="_blank" rel="noopener noreferrer" class="link link-primary">Privacy Policy</a></span>
                    </label>
                    <button type="submit" class="btn btn-primary w-full"><?= $__isDemo ? 'Save my resort' : 'Create account' ?></button>
                </form>

                <p class="auth-switch">Already have an account? <a href="<?= url_to('login') ?>" class="link link-primary font-medium">Log in</a></p>
            </div>
        </main>
    </div>
</div>
<script>
document.querySelectorAll('[data-toggle-pw]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = document.getElementById(btn.dataset.togglePw);
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        // Both icons are in the button; the Font Awesome kit swaps each <i> for an <svg>, so toggle the wrappers
        btn.querySelector('[data-pw-icon="show"]').hidden = show;
        btn.querySelector('[data-pw-icon="hide"]').hidden = !show;
    });
});
// Tell people before submitting rather than after a round trip
(function () {
    var pw = document.getElementById('password'), confirm = document.getElementById('password_confirm');
    function check() { confirm.setCustomValidity(confirm.value && confirm.value !== pw.value ? 'Passwords do not match' : ''); }
    pw.addEventListener('input', check);
    confirm.addEventListener('input', check);
})();
</script>
<?= $this->endSection() ?>
