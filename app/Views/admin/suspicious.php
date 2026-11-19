<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Suspicious Activity<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminSuspiciousPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading suspicious activity…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
