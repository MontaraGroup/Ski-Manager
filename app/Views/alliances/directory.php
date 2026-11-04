<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Alliances - Discover & Form Syndicates<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('AllianceDirectoryPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading alliances directory…</p>') ?>
<?= $this->endSection() ?>
