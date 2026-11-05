<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Audit Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminAuditPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading admin audit log…</p>') ?>
<?= $this->endSection() ?>
