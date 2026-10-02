<?php

function getCurrentSeason(): array
{
    static $season = null;
    if ($season !== null) return $season;

    $db = db_connect();
    $season = $db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
    if (!$season) {
        $season = [
            'id' => 1,
            'season_number' => 1,
            'name' => 'Season 1: Park City',
            'resort_map' => 'ParkCity',
            'start_date' => '2026-06-06',
            'duration_days' => 135,
            'winter_days' => 100,
        ];
    }
    return $season;
}

function getGameDay(): int
{
    $season = getCurrentSeason();
    return max(1, (int)((strtotime(date('Y-m-d')) - strtotime($season['start_date'])) / 86400) + 1);
}

function getSeasonDay(): int
{
    $season = getCurrentSeason();
    $duration = max(1, (int)($season['duration_days'] ?? 135));
    return (($gameDay = getGameDay()) - 1) % $duration + 1;
}

function getSeasonStartDate(): string
{
    return getCurrentSeason()['start_date'] ?? '2026-06-06';
}

function getSeasonLength(): int
{
    return max(1, (int)(getCurrentSeason()['duration_days'] ?? 135));
}

function getWinterDays(): int
{
    return (int)getCurrentSeason()['winter_days'];
}

function getSummerDays(): int
{
    $s = getCurrentSeason();
    return (int)$s['duration_days'] - (int)$s['winter_days'];
}

function isWinterDay(): bool
{
    return getSeasonDay() <= getWinterDays();
}

function getSeasonNumber(): int
{
    return (int)getCurrentSeason()['season_number'];
}

function getSeasonName(): string
{
    return getCurrentSeason()['name'];
}
