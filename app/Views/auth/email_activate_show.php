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

$pageData = [
    'actionUrl'     => site_url('auth/activate-submit'),
    'resendUrl'     => site_url('auth/a/show?resend=1'),
    'loginUrl'      => site_url('login'),
    'contactUrl'    => site_url('contact'),
    'csrfTokenName' => csrf_token(),
    'csrfTokenHash' => csrf_hash(),
    'userExists'    => $user !== null,
    'maskedEmail'   => $maskedEmail,
    'mailNotice'    => $mailNotice,
    'retryIn'       => $retryIn,
    'oldEmail'      => old('email') ?? '',
];

ob_start();
?>
<div class="mx-auto max-w-md w-full p-4 sm:p-8">
    <div class="rounded-2xl border border-border bg-card p-6 shadow-xl space-y-6">
        <div class="text-center space-y-2">
            <h1 class="text-xl font-bold flex items-center justify-center gap-2">
                <i class="fa-solid fa-envelope-open-text text-primary" aria-hidden="true"></i>
                Confirm your email
            </h1>
            <?php if ($user === null) : ?>
                <p class="text-sm text-muted">Enter the email you signed up with and the 6-digit code we sent you.</p>
            <?php elseif ($mailNotice === 'failed') : ?>
                <p class="text-sm text-muted">We need to confirm <b class="text-foreground"><?= esc($maskedEmail) ?></b> before you can play.</p>
            <?php else : ?>
                <p class="text-sm text-muted">We <?= $mailNotice === 'resent' ? 'just sent a new' : 'sent a' ?> 6-digit code to <b class="text-foreground"><?= esc($maskedEmail) ?></b>. It is valid for 30 minutes. Check your spam folder if it is not in your inbox.</p>
            <?php endif ?>
        </div>

        <?php if ($mailNotice === 'failed') : ?>
            <div role="alert" class="flex items-start gap-2.5 rounded-xl border border-warning/30 bg-warning/10 p-3 text-xs text-warning">
                <i class="fa-solid fa-triangle-exclamation mt-0.5 shrink-0" aria-hidden="true"></i>
                <span>We could not send the email just now. Try again in a minute. If it keeps failing, <a href="<?= site_url('contact') ?>" class="underline hover:text-foreground">let us know</a>, or sign up with Google or Discord instead.</span>
            </div>
        <?php endif ?>

        <form action="<?= site_url('auth/activate-submit') ?>" method="post" class="space-y-4">
            <?= csrf_field() ?>
            <?php if ($user === null) : ?>
            <div>
                <label class="block text-xs font-semibold text-muted mb-1.5" for="activate-email-fallback">Email</label>
                <input id="activate-email-fallback" type="email" name="email" value="<?= esc(old('email') ?? '', 'attr') ?>" autocomplete="email" required class="w-full rounded-xl border border-border bg-default/40 px-3 py-2 text-base sm:text-sm text-foreground min-h-11 sm:min-h-9">
            </div>
            <?php endif ?>
            <div>
                <label class="block text-xs font-semibold text-muted mb-1.5" for="activate-code-fallback">6-digit code</label>
                <input id="activate-code-fallback" type="text" name="code" autocomplete="one-time-code" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required
                    class="w-full rounded-xl border border-border bg-default/40 h-14 text-center font-mono text-2xl tracking-[0.5em] text-foreground" <?= $user !== null ? 'autofocus' : '' ?>>
            </div>
            <button type="submit" class="inline-flex w-full min-h-11 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Confirm and start playing</span>
            </button>
        </form>

        <div class="text-center text-xs text-muted">
            <?php if ($user !== null) : ?>
                <?php if ($retryIn > 0) : ?>
                    <span id="resendWait">You can ask for a new code in <span id="resendSecs" class="font-semibold text-foreground"><?= $retryIn ?></span>s</span>
                    <a id="resendLink" href="<?= site_url('auth/a/show?resend=1') ?>" class="font-medium text-accent hover:underline" hidden>Send a new code</a>
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
                    Did not get it? <a href="<?= site_url('auth/a/show?resend=1') ?>" class="font-medium text-accent hover:underline">Send a new code</a>
                <?php endif ?>
            <?php else : ?>
                Code expired? <a href="<?= site_url('login') ?>" class="font-medium text-accent hover:underline">Sign in</a> and we will email a new one.
            <?php endif ?>
        </div>
    </div>
</div>
<?php $fallbackHtml = ob_get_clean(); ?>
<?= island('EmailActivateShowPage', $pageData, $fallbackHtml) ?>
<?= $this->endSection() ?>
