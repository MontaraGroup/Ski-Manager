<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Complete Your Profile<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('CompleteProfilePage', $page, '<p class="max-w-md mx-auto p-8 text-center text-sm">Loading profile setup…</p>') ?>
<?= $this->endSection() ?>
