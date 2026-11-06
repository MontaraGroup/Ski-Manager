<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Daily Bonus<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('DailyBonusPage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading daily bonus…</p>') ?>
<?= $this->endSection() ?>
