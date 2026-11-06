<?php
$actionUrl = function_exists('url_to') ? (function() { try { return url_to('auth-action-verify'); } catch (\Throwable $e) { return site_url('auth/a/verify'); } })() : site_url('auth/a/verify');
$resendUrl = function_exists('url_to') ? (function() { try { return url_to('auth-action-show'); } catch (\Throwable $e) { return site_url('auth/a/show'); } })() : site_url('auth/a/show');

$page = [
    'actionUrl' => $actionUrl,
    'resendUrl' => $resendUrl,
    'csrfToken' => csrf_hash(),
    'csrfField' => csrf_token(),
    'error'     => session('error'),
    'errors'    => session('errors'),
];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Enter Verification Code<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('Email2FAVerifyPage', $page, '<p class="max-w-md mx-auto p-8 text-center text-sm">Loading code verification…</p>') ?>
<?= $this->endSection() ?>
