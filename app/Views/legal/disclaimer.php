<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Disclaimer<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('LegalPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading Disclaimer…</p>') ?>
<?= $this->endSection() ?>
