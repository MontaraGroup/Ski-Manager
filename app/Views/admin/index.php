<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin Panel<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminIndexPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading admin dashboard…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
