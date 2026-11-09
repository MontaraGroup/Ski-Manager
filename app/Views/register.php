<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Sign Up<?= $this->endSection() ?>

<?= $this->section('content') ?>
<?php
$isDemo = (bool) session()->get('is_demo');
$difficulty = old('difficulty') ?: 'standard';
$levels = [
    'easy'     => ['Easy', 'green', 1000000, 'Room for mistakes'],
    'standard' => ['Standard', 'blue', 500000, 'The intended game'],
    'hard'     => ['Hard', 'black', 200000, 'No room for error'],
];

$difficulties = [];
foreach ($levels as $k => [$label, $sign, $cash, $note]) {
    $difficulties[] = [
        'key' => $k,
        'label' => $label,
        'sign' => $sign,
        'cashText' => currency($cash),
        'note' => $note,
    ];
}

$weather = function_exists('getCurrentWeather') ? getCurrentWeather() : null;
$season = function_exists('getSeasonNumber') ? (int) getSeasonNumber() : 1;
$seasonDay = !empty($weather['game_day']) ? (int) $weather['game_day'] : null;
$players = db_connect()->table('users')->where('id !=', 1)->countAllResults();

$page = [
    'actionUrl' => $isDemo ? '/demo/claim' : url_to('register'),
    'isDemo' => $isDemo,
    'demoSummary' => $isDemo ? \App\Controllers\Demo::summary((int) auth()->id()) : null,
    'csrfField' => csrf_token(),
    'csrfToken' => csrf_hash(),
    'oldUsername' => (string) old('username', ''),
    'oldEmail' => (string) old('email', ''),
    'oldDifficulty' => $difficulty,
    'difficulties' => $difficulties,
    'allowDiscord' => (string) env('DISCORD_CLIENT_ID', '') !== '',
    'error' => session('error'),
    'errors' => session('errors') ? (array) session('errors') : null,
    'season' => $season,
    'seasonDay' => $seasonDay,
    'conditionName' => $weather['condition_name'] ?? ($weather['condition'] ?? ''),
    'temperature' => isset($weather['temp']) ? temp((int) $weather['temp']) : '',
    'playerCount' => (int) $players,
];
?>
<?= island('RegisterPage', $page, '<p class="max-w-md mx-auto p-4 lg:p-8 text-sm">Loading registration…</p>') ?>
<?= $this->endSection() ?>
