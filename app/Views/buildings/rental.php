<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($page['def']['label'] ?? 'Ski Rentals') ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('BuildingPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading ski rentals…</p>') ?>
<?= $this->endSection() ?>
