<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Events & Tournaments<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('TournamentsPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading events & tournaments…</p>') ?>
<?= $this->endSection() ?>
