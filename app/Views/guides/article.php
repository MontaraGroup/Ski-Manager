<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?><?= esc($page['title'] ?? 'Guide') ?><?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('GuideArticlePage', $page, '<p class="max-w-3xl mx-auto p-4 lg:p-8 text-sm">Loading guide article…</p>') ?>
<?= $this->endSection() ?>
