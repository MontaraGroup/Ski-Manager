<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Economy Overview<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminEconomyPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading economy overview…</p>') ?>
<?= $this->endSection() ?>
