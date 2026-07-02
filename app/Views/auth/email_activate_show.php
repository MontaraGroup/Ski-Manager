<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.emailActivateTitle') ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container d-flex justify-content-center p-8 mx-auto animate-fade-in-up max-w-md w-full">
    <div class="card bg-base-100 shadow-xl border border-base-300 p-6 rounded-2xl">
        <div class="text-center mb-6">
            <h2 class="card-title text-xl font-bold justify-center gap-2 mb-2">
                <i class="fa-solid fa-envelope-open-text text-primary"></i>
                <?= lang('Auth.emailActivateTitle') ?>
            </h2>
            <p class="text-xs text-base-content/70 mx-auto max-w-xs">
                <?= lang('Auth.emailActivateBody') ?>
            </p>
        </div>

        <?php if (session('error')) : ?>
            <div class="alert alert-error mb-4 text-xs p-3 rounded-xl flex gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= session('error') ?></span>
            </div>
        <?php endif ?>

        <?php if (session('message')) : ?>
            <div class="alert alert-success mb-4 text-xs p-3 rounded-xl flex gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span><?= session('message') ?></span>
            </div>
        <?php endif ?>

        <form action="<?= site_url('auth/activate-submit') ?>" method="post" class="space-y-6 flex flex-col items-center">
            <?= csrf_field() ?>
            
            <div class="form-control items-center w-full">
                <label class="label text-xs font-semibold tracking-wider text-base-content/50 mb-2">
                    <span class="label-text">6-Digit Verification Code</span>
                </label>
                
                <label class="otp otp-md otp-primary">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                    <input 
                        type="text" 
                        id="code" 
                        name="code" 
                        autocomplete="one-time-code" 
                        inputmode="numeric" 
                        maxlength="6" 
                        pattern="[0-9]{6}" 
                        required 
                    />
                </label>
            </div>

            <button type="submit" class="btn btn-primary w-full rounded-xl shadow-md mt-2">
                <i class="fa-solid fa-circle-check mr-1"></i>Verify & Activate Account
            </button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
