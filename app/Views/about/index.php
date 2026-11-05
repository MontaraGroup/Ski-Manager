<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>About Ski Manager<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('AboutPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading About Ski Manager…</p>') ?>
<?= $this->endSection() ?>
