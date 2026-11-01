<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.emailActivateTitle') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
// $user: pending sign-up (null when the code is entered from another browser)
// $mailNotice: 'sent' | 'resent' | 'failed' | null, $retryIn: seconds until another code can be emailed
$user = $user ?? null;
$mailNotice = $mailNotice ?? null;
$retryIn = (int) ($retryIn ?? 0);
$maskedEmail = null;
if ($user !== null && $user->email) {
    [$local, $domain] = explode('@', $user->email, 2) + [1 => ''];
    $maskedEmail = mb_substr($local, 0, 1) . str_repeat('•', max(2, min(6, mb_strlen($local) - 1))) . '@' . $domain;
}
?>
<div class="mx-auto max-w-md w-full p-4 sm:p-8">
    <div class="card bg-base-100 shadow-xl border border-base-300 p-6 rounded-2xl">
        <div class="text-center mb-5">
            <h1 class="text-xl font-bold flex items-center justify-center gap-2 mb-2">
                <i class="fa-solid fa-envelope-open-text text-primary" aria-hidden="true"></i>
                Confirm your email
            </h1>
            <?php if ($user === null) : ?>
                <p class="text-sm text-base-content/70">Enter the email you signed up with and the 6-digit code we sent you.</p>
            <?php elseif ($mailNotice === 'failed') : ?>
                <p class="text-sm text-base-content/70">We need to confirm <b><?= esc($maskedEmail) ?></b> before you can play.</p>
            <?php else : ?>
                <p class="text-sm text-base-content/70">We <?= $mailNotice === 'resent' ? 'just sent a new' : 'sent a' ?> 6-digit code to <b><?= esc($maskedEmail) ?></b>. It's valid for 30 minutes. Check your spam folder if it's not in your inbox.</p>
            <?php endif ?>
        </div>

        <?php if ($mailNotice === 'failed') : ?>
            <div role="alert" class="alert alert-warning mb-4 text-sm p-3 rounded-xl flex gap-2 items-start">
                <i class="fa-solid fa-triangle-exclamation mt-0.5" aria-hidden="true"></i>
                <span>We couldn't send the email just now. Try again in a minute. If it keeps failing, <a href="/contact" class="link">let us know</a>, or sign up with Google or Discord instead.</span>
            </div>
        <?php endif ?>

        <?php /* Flash errors and messages are shown by the layout */ ?>
        <form action="<?= site_url('auth/activate-submit') ?>" method="post" class="space-y-4">
            <?= csrf_field() ?>
            <?php if ($user === null) : ?>
            <label class="block">
                <span class="block text-xs font-semibold text-base-content/70 mb-1.5">Email</span>
                <input type="email" name="email" value="<?= esc(old('email') ?? '', 'attr') ?>" autocomplete="email" required class="input input-bordered w-full" style="background: var(--color-base-200); border-color: color-mix(in oklab, var(--color-base-content) 25%, transparent)">
            </label>
            <?php endif ?>
            <label class="block">
                <span class="block text-xs font-semibold text-base-content/70 mb-1.5">6-digit code</span>
                <input type="text" name="code" autocomplete="one-time-code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required
                    class="input input-bordered w-full h-14 text-center font-mono text-2xl tracking-[0.5em]" style="background: var(--color-base-200); border-color: color-mix(in oklab, var(--color-base-content) 25%, transparent)" <?= $user !== null ? 'autofocus' : '' ?>>
            </label>
            <button type="submit" class="btn btn-primary w-full rounded-xl">
                <i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i>Confirm and start playing
            </button>
        </form>

        <div class="text-center text-sm mt-5 text-base-content/70">
            <?php if ($user !== null) : ?>
                <?php if ($retryIn > 0) : ?>
                    <span id="resendWait">You can ask for a new code in <span id="resendSecs"><?= $retryIn ?></span>s</span>
                    <a id="resendLink" href="<?= site_url('auth/a/show?resend=1') ?>" class="link link-primary" hidden>Send a new code</a>
                    <script>
                    (function () {
                        var s = <?= $retryIn ?>, out = document.getElementById('resendSecs');
                        var t = setInterval(function () {
                            if (--s <= 0) { clearInterval(t); document.getElementById('resendWait').hidden = true; document.getElementById('resendLink').hidden = false; }
                            else out.textContent = s;
                        }, 1000);
                    })();
                    </script>
                <?php else : ?>
                    Didn't get it? <a href="<?= site_url('auth/a/show?resend=1') ?>" class="link link-primary">Send a new code</a>
                <?php endif ?>
            <?php else : ?>
                Code expired? <a href="<?= site_url('login') ?>" class="link link-primary">Sign in</a> and we'll email a new one.
            <?php endif ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
