<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Profile<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ResortPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading resort profile…</p>') ?>
<?= $this->endSection() ?>
