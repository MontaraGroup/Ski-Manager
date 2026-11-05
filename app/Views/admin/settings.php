<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Game Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminSettingsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading game settings…</p>') ?>
<?= $this->endSection() ?>
