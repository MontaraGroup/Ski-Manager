<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Confirm it's you<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminConfirmPage', $page, '<p class="mx-auto max-w-md p-4 text-sm lg:p-8">Loading…</p>') ?>
<?= $this->endSection() ?>
