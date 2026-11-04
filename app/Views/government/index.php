<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Government & Regulations<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('GovernmentPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading regulations…</p>') ?>
<?= $this->endSection() ?>
