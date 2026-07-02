<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-center min-h-[60vh] px-4">
    <div class="card w-full max-w-md bg-base-200 shadow-xl border border-base-300">
        <div class="card-body items-center text-center p-8 gap-4">
            
            <div class="bg-primary/10 text-primary p-4 rounded-full mb-2">
                <i class="fas fa-envelope-open-text text-3xl"></i>
            </div>
            
            <h2 class="card-title text-2xl font-bold tracking-tight">Verify Your Resort Account</h2>
            <p class="text-sm text-base-content/70 max-w-xs">
                We sent a 6-digit activation token to your registered email address. Enter it below to unlock your dashboard.
            </p>

            <?php if (session('error')) : ?>
                <div class="alert alert-error shadow-sm text-sm justify-start text-left w-full">
                    <i class="fas fa-circle-exclamation shrink-0"></i>
                    <span><?= session('error') ?></span>
                </div>
            <?php endif ?>

            <form action="<?= url_to('auth-action-verify') ?>" method="post" id="otp-form" class="w-full mt-2">
                <?= csrf_field() ?>

                <div class="form-control items-center mb-6">
                    <div class="otp otp-md otp-primary tracking-widest">
                        <input type="text" id="otp-input" name="token" maxlength="6" pattern="\d*" inputmode="numeric" autocomplete="one-time-code" required autofocus />
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>

                <div class="card-actions w-full">
                    <button type="submit" class="btn btn-primary w-full font-semibold">
                        <i class="fas fa-shield-check mr-2"></i> Activate Account
                    </button>
                </div>
            </form>

            <div class="text-xs text-base-content/50 mt-2">
                Didn't receive a token? Check your spam folder or wait a moment.
            </div>
        </div>
    </div>
</div>

<script data-cfasync="false">
    document.addEventListener('DOMContentLoaded', function () {
        const otpInput = document.getElementById('otp-input');
        const otpForm = document.getElementById('otp-form');

        if (otpInput && otpForm) {
            // Instant UX: Trigger auto-submit when the input hits full length
            otpInput.addEventListener('input', function () {
                if (this.value.length === 6) {
                    otpForm.submit();
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>
