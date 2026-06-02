<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Hire Staff<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="/staff" class="btn btn-ghost btn-sm btn-circle">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <h1 class="text-2xl font-bold">Hire Staff</h1>
    </div>

    <p class="text-sm text-base-content/60 mb-6">Choose a role to hire a new employee. Each staff member has a daily salary cost.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php foreach ($roles as $key => $role) : ?>
        <form action="/staff/hire" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="<?= $key ?>">
            <button type="submit" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow w-full text-left">
                <div class="card-body p-4 flex flex-row items-center gap-4">
                    <div class="w-12 h-12 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                        <i class="<?= $role['icon'] ?> text-xl <?= $role['color'] ?>"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold"><?= $role['name'] ?></div>
                        <div class="text-xs text-base-content/50"><?= $role['desc'] ?></div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="font-bold text-primary"><?= currency($role['salary']) ?></div>
                        <div class="text-xs text-base-content/50">per day</div>
                    </div>
                </div>
            </button>
        </form>
        <?php endforeach ?>
    </div>

</div>
<?= $this->endSection() ?>
