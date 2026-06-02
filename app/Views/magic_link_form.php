<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Magic Link<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="min-h-[80vh] flex items-center justify-center px-4">
    <div class="card bg-base-100 shadow-xl w-full max-w-md">
        <div class="card-body">
            <h2 class="card-title text-2xl font-bold justify-center mb-4">Login via Magic Link</h2>
            <p class="text-center text-base-content/60 mb-4">Enter your email and we'll send you a link to log in instantly.</p>

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

            <form action="<?= url_to('magic-link') ?>" method="post">
                <?= csrf_field() ?>

                <div class="form-control mb-4">
                    <label class="label" for="email">
                        <span class="label-text">Email</span>
                    </label>
                    <input type="email" name="email" id="email" class="input input-bordered w-full" placeholder="you@example.com" value="<?= old('email') ?>" required autofocus aria-required="true" autocomplete="email">
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full">Send Magic Link</button>
                </div>
            </form>

            <div class="divider"></div>

            <p class="text-center text-sm text-base-content/60">
                <a href="<?= url_to('login') ?>" class="link link-primary">Back to Login</a>
            </p>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
