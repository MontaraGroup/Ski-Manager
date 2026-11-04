<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Night Skiing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('NightSkiingPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading night skiing…</p>') ?>
<?= $this->endSection() ?>
