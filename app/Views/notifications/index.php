<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Notifications<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('NotificationsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading your notifications…</p>') ?>
<?= $this->endSection() ?>
