<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Sitemap<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('LegalPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading Sitemap…</p>') ?>
<?= $this->endSection() ?>
