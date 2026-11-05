<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Settings<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('SettingsPage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading settings…</p>') ?>
<?= $this->endSection() ?>
