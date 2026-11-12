<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Economy Overview<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminEconomyPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading economy overview…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
