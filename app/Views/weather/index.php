<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Weather<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('WeatherPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading the forecast…</p>') ?>
<?= $this->endSection() ?>
