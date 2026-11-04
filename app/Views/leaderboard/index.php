<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Leaderboard<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('LeaderboardPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading the leaderboard…</p>') ?>
<?= $this->endSection() ?>
