<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Lift Tickets & Pricing<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('TicketsPage', $page, '<p class="max-w-5xl mx-auto p-4 lg:p-8 text-sm">Loading tickets & pricing…</p>') ?>
<?= $this->endSection() ?>
