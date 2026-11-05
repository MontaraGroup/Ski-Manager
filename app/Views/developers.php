<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Developers & API<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('DevelopersPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading API documentation…</p>') ?>
<?= $this->endSection() ?>
