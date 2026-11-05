<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Changelogs<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminChangelogsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading changelog manager…</p>') ?>
<?= $this->endSection() ?>
