<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Try a demo resort<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('DemoStartPage', [], '<p class="max-w-xl mx-auto p-4 lg:p-8 text-sm">Loading…</p>') ?>
<?= $this->endSection() ?>
