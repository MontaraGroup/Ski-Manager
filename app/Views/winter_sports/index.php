<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Winter Sports<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('WinterSportsPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading winter sport facilities…</p>') ?>
<?= $this->endSection() ?>
