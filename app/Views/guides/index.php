<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Guides<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-4xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Ski Manager guides</h1>
    <p class="text-base-content/70 mb-6">How the game really works, with the numbers it uses. Start at the top if you're new.</p>
    <ol class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php $n = 0; foreach ($guides as $slug => [$title, $summary, $icon]) : $n++; ?>
        <li><a href="/guides/<?= esc($slug, 'attr') ?>" class="card bg-base-100 shadow-sm hover:shadow-md transition-shadow h-full"><div class="card-body p-4 flex-row gap-3">
            <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center shrink-0"><i class="fa-solid <?= $icon ?> text-primary" aria-hidden="true"></i></div>
            <div>
                <div class="text-xs text-base-content/70">Guide <?= $n ?></div>
                <h2 class="font-bold"><?= esc($title) ?></h2>
                <p class="text-sm text-base-content/70 mt-1"><?= esc($summary) ?></p>
            </div>
        </div></a></li>
        <?php endforeach ?>
    </ol>
    <div class="mt-8 text-center">
        <a href="<?= auth()->loggedIn() ? '/dashboard' : '/register' ?>" class="btn btn-primary"><?= auth()->loggedIn() ? 'Back to your resort' : 'Play free' ?></a>
    </div>
</div>
<?= $this->endSection() ?>
