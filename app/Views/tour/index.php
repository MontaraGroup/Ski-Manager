<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($page['owner']['username'] ?? 'Player') ?>'s Resort - Tour<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('TourPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading resort tour…</p>') ?>
<?= $this->endSection() ?>
