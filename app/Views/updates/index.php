<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Game Updates<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('UpdatesPage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading game updates…</p>') ?>
<?= $this->endSection() ?>
