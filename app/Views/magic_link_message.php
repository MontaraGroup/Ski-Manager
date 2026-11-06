<?php
$loginUrl = function_exists('url_to') ? (function() { try { return url_to('login'); } catch (\Throwable $e) { return site_url('login'); } })() : site_url('login');

$page = [
    'loginUrl'        => $loginUrl,
    'lifetimeMinutes' => (int) round((int) setting('Auth.magicLinkLifetime') / 60),
    'message'         => session('message'),
];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Check Your Email<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('MagicLinkMessagePage', $page, '<p class="max-w-md mx-auto p-8 text-center text-sm">Loading confirmation…</p>') ?>
<?= $this->endSection() ?>
