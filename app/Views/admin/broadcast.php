<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Broadcast Announcement<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminBroadcastPage', $page, '<p class="max-w-2xl mx-auto p-4 lg:p-8 text-sm">Loading broadcast dispatcher…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
