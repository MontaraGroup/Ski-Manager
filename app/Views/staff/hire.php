<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Hire Staff<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('StaffHirePage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading candidates…</p>') ?>
<?= $this->endSection() ?>
