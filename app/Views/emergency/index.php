<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Emergency & Rescue<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('EmergencyPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading emergency & rescue…</p>') ?>
<?= $this->endSection() ?>
