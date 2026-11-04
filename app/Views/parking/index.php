<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Parking & Transit<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ParkingPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading parking & transit…</p>') ?>
<?= $this->endSection() ?>
