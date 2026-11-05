<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Edit Resort<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ResortEditPage', $page, '<p class="max-w-2xl mx-auto p-4 lg:p-8 text-sm">Loading resort settings…</p>') ?>
<?= $this->endSection() ?>
