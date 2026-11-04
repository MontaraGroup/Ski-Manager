<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Marketing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('MarketingPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading marketing campaigns…</p>') ?>
<?= $this->endSection() ?>
