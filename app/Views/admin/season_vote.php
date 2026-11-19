<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Season Format Vote<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="lg:flex lg:items-start">
<?= island('AdminNav') ?>
<div class="min-w-0 lg:flex-1">
<?= island('AdminSeasonVotePage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading the vote results…</p>') ?>
</div>
</div>
<?= $this->endSection() ?>
