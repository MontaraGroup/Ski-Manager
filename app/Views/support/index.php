<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Support<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('SupportPage', $page, '<p class="max-w-2xl mx-auto p-4 lg:p-8 text-sm">Loading support chat…</p>') ?>
<?= $this->endSection() ?>
