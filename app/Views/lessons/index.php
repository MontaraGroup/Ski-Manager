<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Ski School & Lessons<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('SkiLessonsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading ski school…</p>') ?>
<?= $this->endSection() ?>
