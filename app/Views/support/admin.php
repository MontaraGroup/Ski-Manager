<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Support Inbox<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminSupportInboxPage', $page, '<p class="max-w-4xl mx-auto p-4 lg:p-8 text-sm">Loading support inbox…</p>') ?>
<?= $this->endSection() ?>
