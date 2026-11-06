<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Terrain Parks<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('TerrainParksPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading terrain parks…</p>') ?>
<?= $this->endSection() ?>
