<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Génépis<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('GenepisPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading Génépis shop…</p>') ?>
<?= $this->endSection() ?>
