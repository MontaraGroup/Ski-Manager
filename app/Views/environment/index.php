<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Environment<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('EnvironmentPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading environment…</p>') ?>
<?= $this->endSection() ?>
