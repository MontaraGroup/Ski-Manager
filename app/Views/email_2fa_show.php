<?php
$actionUrl = function_exists('url_to') ? (function() { try { return url_to('auth-action-handle'); } catch (\Throwable $e) { return site_url('auth/a/handle'); } })() : site_url('auth/a/handle');

$page = [
    'actionUrl' => $actionUrl,
    'csrfToken' => csrf_hash(),
    'csrfField' => csrf_token(),
    'email'     => old('email', $user->email ?? ''),
    'error'     => session('error'),
    'errors'    => session('errors'),
];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Two-Factor Authentication<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('Email2FAShowPage', $page, '<p class="max-w-md mx-auto p-8 text-center text-sm">Loading two-factor authentication…</p>') ?>
<?= $this->endSection() ?>
