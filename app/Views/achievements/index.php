<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Achievements<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AchievementsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading your achievements…</p>') ?>
<?= $this->endSection() ?>
