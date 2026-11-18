<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Test the Android app<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AndroidTestingPage', $page, '<p class="mx-auto max-w-3xl p-4 text-sm lg:p-8">Loading…</p>') ?>
<?= $this->endSection() ?>
