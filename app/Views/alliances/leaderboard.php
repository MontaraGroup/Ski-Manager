<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Alliance Leaderboard & Standings<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('AllianceLeaderboardPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading alliance standings…</p>') ?>
<?= $this->endSection() ?>
