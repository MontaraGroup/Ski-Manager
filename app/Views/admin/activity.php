<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Activity Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminActivityPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading activity logs…</p>') ?>
<?= $this->endSection() ?>
