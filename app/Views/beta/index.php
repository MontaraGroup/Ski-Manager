<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Beta features<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('BetaPage', $page, '<p class="mx-auto max-w-3xl p-4 text-sm lg:p-8">Loading beta features…</p>') ?>
<?= $this->endSection() ?>
