<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Resort Vote<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('ResortVotePage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading the resort vote…</p>') ?>
<?= $this->endSection() ?>
