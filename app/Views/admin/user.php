<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Admin - <?= esc($page['user']['username'] ?? 'User') ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminUserPage', $page, '<p class="max-w-6xl mx-auto p-4 lg:p-8 text-sm">Loading user profile…</p>') ?>
<?= $this->endSection() ?>
