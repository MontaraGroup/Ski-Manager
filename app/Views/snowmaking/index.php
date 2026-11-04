<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Snowmaking<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('SnowmakingPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading snowmaking operations…</p>') ?>
<?= $this->endSection() ?>
