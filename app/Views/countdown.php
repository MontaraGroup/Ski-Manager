<?php
$seasonStart = getSeasonStartDate() . ' 00:00:00';
$targetTimestamp = max(time(), strtotime($seasonStart));
$page = [
    'targetTimestamp' => $targetTimestamp,
    'seasonStart'     => getSeasonStartDate(),
];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Season 1 Launch<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('CountdownPage', $page, '<p class="max-w-md mx-auto p-8 text-center text-sm">Loading countdown…</p>') ?>
<?= $this->endSection() ?>
