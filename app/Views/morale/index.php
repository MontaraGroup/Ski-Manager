<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Staff Morale<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('MoralePage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading staff morale…</p>') ?>
<?= $this->endSection() ?>
