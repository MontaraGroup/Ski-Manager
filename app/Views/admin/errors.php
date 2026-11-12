<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Error Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminErrorsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading error log…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
