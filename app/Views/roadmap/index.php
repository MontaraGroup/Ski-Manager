<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Development Roadmap<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('RoadmapPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading roadmap…</p>') ?>
<?= $this->endSection() ?>
