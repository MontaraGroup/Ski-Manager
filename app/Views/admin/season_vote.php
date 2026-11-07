<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Season Format Vote<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('AdminSeasonVotePage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading the vote results…</p>') ?>
<?= $this->endSection() ?>
