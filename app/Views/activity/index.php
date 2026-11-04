<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Activity Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ActivityPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading your activity…</p>') ?>
<?= $this->endSection() ?>
