<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Finances<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('FinancesPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading your finances…</p>') ?>
<?= $this->endSection() ?>
