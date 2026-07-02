<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="flex items-center justify-center min-h-[60vh] px-4">
    <div class="card w-full max-w-md bg-base-200 shadow-xl border border-base-300">
        <div class="card-body items-center text-center p-8 gap-4">
            
            <div class="bg-secondary/10 text-secondary p-4 rounded-full mb-2">
                <i class="fas fa-key text-3xl"></i>
            </div>
            
            <h2 class="card-title text-2xl font-bold tracking-tight">Two-Factor Authentication</h2>
            <p class="text-sm text-base-content/70 max-w-xs">
                Confirm your identity. Enter the 6-digit authentication token sent to your email profile.
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
                    <div class="otp otp-md otp-secondary tracking-widest">
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
                    <button type="submit" class="btn btn-secondary w-full font-semibold">
                        <i class="fas fa-unlock-keyhole mr-2"></i> Confirm Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script data-cfasync="false">
    document.addEventListener('DOMContentLoaded', function () {
        const otpInput = document.getElementById('otp-input');
        const otpForm = document.getElementById('otp-form');
        if (otpInput && otpForm) {
            otpInput.addEventListener('input', function () {
                if (this.value.length === 6) {
                    otpForm.submit();
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>
