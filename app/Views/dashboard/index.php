<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('DashboardPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading mountain dashboard…</p>') ?>
<?= $this->endSection() ?>
