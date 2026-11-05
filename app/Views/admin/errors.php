<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Error Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminErrorsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading error log…</p>') ?>
<?= $this->endSection() ?>
