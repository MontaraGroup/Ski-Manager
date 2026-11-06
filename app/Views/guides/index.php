<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Guides<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('GuidesPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading guides…</p>') ?>
<?= $this->endSection() ?>
