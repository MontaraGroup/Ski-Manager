<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Contact<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ContactPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading contact form…</p>') ?>
<?= $this->endSection() ?>
