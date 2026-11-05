<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Suspicious Activity<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminSuspiciousPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading suspicious activity…</p>') ?>
<?= $this->endSection() ?>
