<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Edit Resort<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-2xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/resort" class="btn btn-ghost btn-sm btn-circle">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <h1 class="text-2xl font-bold">Edit Resort</h1>
    </div>

    <?php if (session('success')) : ?>
        <div class="alert alert-success mb-4" role="status">
            <span><?= session('success') ?></span>
        </div>
    <?php endif ?>

    <?php if (session('error')) : ?>
        <div class="alert alert-error mb-4" role="alert">
            <span><?= session('error') ?></span>
        </div>
    <?php endif ?>

    <form action="/resort/edit" method="post">
        <?= csrf_field() ?>

        <div class="card bg-base-100 shadow-sm mb-6">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Resort Details</h2>

                <div class="form-control mb-4">
                    <label class="label" for="name">
                        <span class="label-text">Resort Name</span>
                    </label>
                    <input type="text" name="name" id="name" class="input input-bordered w-full" value="<?= esc($resort['name'] ?? 'My Resort') ?>" required aria-required="true" autocomplete="off" maxlength="50">
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="location">
                        <span class="label-text">Location</span>
                    </label>
                    <input type="text" name="location" id="location" class="input input-bordered w-full" value="<?= esc($resort['location'] ?? '') ?>" autocomplete="off" maxlength="100">
                </div>

                <div class="form-control mb-4">
                    <label class="label" for="description">
                        <span class="label-text">Description</span>
                    </label>
                    <textarea name="description" id="description" class="textarea textarea-bordered w-full" rows="3" maxlength="500"><?= esc($resort['description'] ?? '') ?></textarea>
                    <label class="label">
                        <span class="label-text-alt text-base-content/50">Max 500 characters</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm mb-6">
            <div class="card-body">
                <h2 class="card-title text-base mb-4">Resort Status</h2>

                <div class="form-control">
                    <label class="label cursor-pointer justify-start gap-3">
                        <input type="checkbox" name="is_open" class="toggle toggle-success" <?= ($resort['is_open'] ?? true) ? 'checked' : '' ?>>
                        <div>
                            <div class="text-sm font-semibold">Resort Open</div>
                            <div class="text-xs text-base-content/50">When closed, guests stop visiting and no revenue is generated</div>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="/resort" class="btn btn-ghost">Cancel</a>
        </div>
    </form>

</div>
<?= $this->endSection() ?>
