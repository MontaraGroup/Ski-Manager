<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Bank & Loans<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('BankPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading bank…</p>') ?>
<?= $this->endSection() ?>
