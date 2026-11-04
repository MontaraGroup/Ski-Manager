<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($page['alliance']['name']) ?> [<?= esc($page['alliance']['tag']) ?>] - Alliance Headquarters<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('AllianceDashboardPage', $page, '<p class="max-w-7xl mx-auto p-4 lg:p-8 text-sm">Loading alliance headquarters…</p>') ?>
<?= $this->endSection() ?>
