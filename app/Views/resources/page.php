<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= ($page['category'] ?? '') === 'energy' ? 'Energy' : 'Water' ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ResourcesPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading utilities…</p>') ?>
<?= $this->endSection() ?>
