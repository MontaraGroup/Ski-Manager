<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Log In<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$weather = function_exists('getCurrentWeather') ? getCurrentWeather() : null;
$season = function_exists('getSeasonNumber') ? (int) getSeasonNumber() : 1;
$seasonDay = !empty($weather['game_day']) ? (int) $weather['game_day'] : null;
$players = db_connect()->table('users')->where('id !=', 1)->countAllResults();

$page = [
    'actionUrl' => url_to('login'),
    'csrfField' => csrf_token(),
    'csrfToken' => csrf_hash(),
    'oldEmail' => (string) old('email', ''),
    'allowRemember' => (bool) (setting('Auth.sessionConfig')['allowRemembering'] ?? true),
    'allowMagicLink' => (bool) (setting('Auth.allowMagicLinkLogins') ?? false),
    'allowDiscord' => (string) env('DISCORD_CLIENT_ID', '') !== '',
    'error' => session('error'),
    'errors' => session('errors') ? (array) session('errors') : null,
    'message' => session('message'),
    'season' => $season,
    'seasonDay' => $seasonDay,
    'conditionName' => $weather['condition_name'] ?? ($weather['condition'] ?? ''),
    'temperature' => isset($weather['temp']) ? temp((int) $weather['temp']) : '',
    'playerCount' => (int) $players,
];
?>
<?= island('LoginPage', $page, '<p class="max-w-md mx-auto p-4 lg:p-8 text-sm">Loading login…</p>') ?>
<?= $this->endSection() ?>
