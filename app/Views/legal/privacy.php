<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Privacy Policy<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('LegalPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading Privacy Policy…</p>') ?>
<?= $this->endSection() ?>
