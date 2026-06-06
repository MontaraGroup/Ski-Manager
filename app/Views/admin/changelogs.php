<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Changelog Manager<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto p-4 lg:p-8">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="/admin" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <h1 class="text-2xl font-bold"><i class="fa-solid fa-newspaper mr-2"></i>Changelogs</h1>
        </div>
    </div>

    <?php if (session('success')) : ?><div class="alert alert-success mb-4"><span><?= session('success') ?></span></div><?php endif ?>

    <div class="card bg-base-100 shadow-sm mb-6"><div class="card-body">
        <h2 class="font-bold text-sm mb-3">New Entry</h2>
        <form action="/admin/changelogs/save" method="post">
            <?= csrf_field() ?>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <input type="text" name="version" class="input input-sm input-bordered" placeholder="Version (e.g. 2.1.0)" required>
                <input type="text" name="title" class="input input-sm input-bordered" placeholder="Title" required>
            </div>
            <textarea name="content" class="textarea textarea-bordered w-full mb-3" rows="4" placeholder="Changes (one per line)"></textarea>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="published" class="checkbox checkbox-sm" checked> Publish</label>
                <button class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div></div>

    <?php foreach ($entries as $e) : ?>
    <div class="card bg-base-100 shadow-sm mb-3"><div class="card-body p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <span class="badge badge-primary badge-sm"><?= esc($e['version']) ?></span>
                <span class="font-bold text-sm"><?= esc($e['title']) ?></span>
                <?php if (!$e['published']) : ?><span class="badge badge-warning badge-xs">Draft</span><?php endif ?>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-base-content/40"><?= date('M j', strtotime($e['created_at'])) ?></span>
                <form action="/admin/changelogs/delete/<?= $e['id'] ?>" method="post" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-trash"></i></button></form>
            </div>
        </div>
        <p class="text-xs text-base-content/70 whitespace-pre-line"><?= esc($e['content']) ?></p>
    </div></div>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
