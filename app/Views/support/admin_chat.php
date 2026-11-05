<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Chat with <?= esc($page['player']['username'] ?? 'Player') ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminSupportChatPage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading support conversation…</p>') ?>
<?= $this->endSection() ?>
