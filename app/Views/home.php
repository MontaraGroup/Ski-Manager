<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Free Online Ski Resort Tycoon Game<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('HomePage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading Ski Manager…</p>') ?>
<?= $this->endSection() ?>
