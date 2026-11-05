<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Broadcast Announcement<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminBroadcastPage', $page, '<p class="max-w-2xl mx-auto p-4 lg:p-8 text-sm">Loading broadcast dispatcher…</p>') ?>
<?= $this->endSection() ?>
