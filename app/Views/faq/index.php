<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>FAQ - Frequently Asked Questions<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('FaqPage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading FAQ…</p>') ?>
<?= $this->endSection() ?>
