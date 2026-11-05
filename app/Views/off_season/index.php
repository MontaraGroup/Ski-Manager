<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Off-Season<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('OffSeasonPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading off-season…</p>') ?>
<?= $this->endSection() ?>
