<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Equipment<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('EquipmentPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading equipment shop…</p>') ?>
<?= $this->endSection() ?>
