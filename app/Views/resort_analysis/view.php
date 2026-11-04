<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Analysis · Day <?= (int) $page['report']['game_day'] ?><?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('ResortAnalysisViewPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading resort report…</p>') ?>
<?= $this->endSection() ?>
