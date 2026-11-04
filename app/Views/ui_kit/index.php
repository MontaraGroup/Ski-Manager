<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>UI kit<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="max-w-5xl mx-auto px-4 py-8">
    <?= island('UiKit', [], '<p class="text-sm">Loading the UI kit. It needs JavaScript.</p>') ?>
</div>
<?= $this->endSection() ?>
