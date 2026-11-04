<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Staff<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('StaffPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading staff roster…</p>') ?>
<?= $this->endSection() ?>
