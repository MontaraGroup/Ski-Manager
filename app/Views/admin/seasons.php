<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Season Planner<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminSeasonsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading season planner…</p>') ?>
<?= $this->endSection() ?>
