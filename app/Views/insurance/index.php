<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Insurance<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('InsurancePage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading insurance…</p>') ?>
<?= $this->endSection() ?>
