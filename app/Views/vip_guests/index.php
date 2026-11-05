<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>VIP Guests<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('VipGuestsPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading VIP guests…</p>') ?>
<?= $this->endSection() ?>
