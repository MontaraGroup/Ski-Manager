<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Feature Flags<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminFeaturesPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading feature flags…</p>') ?>
<?= $this->endSection() ?>
