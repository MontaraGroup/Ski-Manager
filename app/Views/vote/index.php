<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Vote for Next Season's Mountain<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('VotePage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading resort ballot…</p>') ?>
<?= $this->endSection() ?>
