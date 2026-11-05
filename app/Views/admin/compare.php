<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Player Comparison<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminComparePage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading player comparison…</p>') ?>
<?= $this->endSection() ?>
