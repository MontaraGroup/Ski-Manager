<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($page['alliance']['name']) ?> [<?= esc($page['alliance']['tag']) ?>] - Resort Alliance Showcase<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?= island('AllianceViewPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading alliance showcase…</p>') ?>
<?= $this->endSection() ?>
