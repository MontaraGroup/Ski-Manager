<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Panel<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminIndexPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading admin dashboard…</p>') ?>
<?= $this->endSection() ?>
