<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Real Estate<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('RealEstatePage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading real estate…</p>') ?>
<?= $this->endSection() ?>
