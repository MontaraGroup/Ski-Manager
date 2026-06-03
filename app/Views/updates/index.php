<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Game Updates<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-3xl mx-auto p-4 lg:p-8">
    <h1 class="text-3xl font-bold mb-2">Game Updates</h1>
    <p class="text-base-content/60 mb-6">What's new in Ski Manager. Follow our development progress and see what's coming next.</p>

    <div class="space-y-6">
        <?php foreach ($updates as $i => $update) : ?>
        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="card-body">
                <div class="flex items-center gap-2 mb-2">
                    <span class="badge <?= $i === 0 ? 'badge-primary' : 'badge-ghost' ?>">v<?= esc($update['version']) ?></span>
                    <span class="badge badge-outline badge-sm"><?= esc(ucfirst($update['type'])) ?></span>
                    <span class="text-sm text-base-content/50"><?= date('F j, Y', strtotime($update['released_at'])) ?></span>
                </div>
                <h2 class="card-title text-lg"><?= esc($update['title']) ?></h2>
                <?php if (!empty($update['description'])) : ?>
                    <p class="text-sm text-base-content/70 mt-1"><?= esc($update['description']) ?></p>
                <?php endif ?>
                <div class="prose prose-sm max-w-none mt-3">
                    <?php foreach ($update['categories'] as $category => $items) : ?>
                        <h3><?= esc($category) ?></h3>
                        <ul>
                            <?php foreach ($items as $item) : ?>
                                <li>
                                    <?php if ($item['type'] === 'new') : ?><span class="badge badge-success badge-xs mr-1">NEW</span>
                                    <?php elseif ($item['type'] === 'improved') : ?><span class="badge badge-info badge-xs mr-1">IMPROVED</span>
                                    <?php elseif ($item['type'] === 'fixed') : ?><span class="badge badge-warning badge-xs mr-1">FIXED</span>
                                    <?php elseif ($item['type'] === 'removed') : ?><span class="badge badge-error badge-xs mr-1">REMOVED</span>
                                    <?php endif ?>
                                    <?= esc($item['text']) ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <?php endforeach ?>

        <?php if (empty($updates)) : ?>
        <div class="text-center py-12 text-base-content/40">
            <i class="fa-solid fa-newspaper text-4xl mb-3"></i>
            <p>No updates yet.</p>
        </div>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
