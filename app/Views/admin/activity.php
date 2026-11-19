<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Activity Log<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminActivityPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading activity logs…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
