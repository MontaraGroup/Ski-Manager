<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Scenic Lifts & Sightseeing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ScenicLiftsPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading scenic lifts…</p>') ?>
<?= $this->endSection() ?>
