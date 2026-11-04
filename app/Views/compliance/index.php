<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Compliance<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('CompliancePage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading compliance…</p>') ?>
<?= $this->endSection() ?>
