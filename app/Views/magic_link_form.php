<?php
$actionUrl = function_exists('url_to') ? (function() { try { return url_to('magic-link'); } catch (\Throwable $e) { return site_url('login/magic-link'); } })() : site_url('login/magic-link');
$loginUrl = function_exists('url_to') ? (function() { try { return url_to('login'); } catch (\Throwable $e) { return site_url('login'); } })() : site_url('login');

$page = [
    'actionUrl' => $actionUrl,
    'loginUrl'  => $loginUrl,
    'csrfToken' => csrf_hash(),
    'csrfField' => csrf_token(),
    'oldEmail'  => old('email') ?? '',
    'error'     => session('error'),
    'errors'    => session('errors'),
    'message'   => session('message'),
];
?>
<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Login via Magic Link<?= $this->endSection() ?>
<?= $this->section('content') ?>
<?= island('MagicLinkFormPage', $page, '<p class="max-w-md mx-auto p-8 text-center text-sm">Loading magic link form…</p>') ?>
<?= $this->endSection() ?>
