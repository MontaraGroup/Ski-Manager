<?php

function checkAutoSeasonRollover(): void
{
    try {
        $db = db_connect();
        if (!$db->tableExists('seasons')) return;

        $curr = $db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
        if (!$curr) return;

        $startDate = $curr['start_date'] ?? '2026-06-06';
        $duration = (int) ($curr['duration_days'] ?? 135);
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $nextSeason = $db->table('seasons')
            ->where('active', 0)
            ->where('season_number >', (int) $curr['season_number'])
            ->orderBy('season_number', 'ASC')
            ->get()->getRowArray();

        if ($nextSeason && !empty($nextSeason['start_date'])) {
            if ($gameDay > $duration || strtotime(date('Y-m-d')) >= strtotime($nextSeason['start_date'])) {
                triggerSeasonRollover((int) $nextSeason['season_number']);
            }
        }
    } catch (\Throwable $e) {
        log_message('error', 'checkAutoSeasonRollover error: ' . $e->getMessage());
    }
}

function getCurrentSeason(): array
{
    static $season = null;
    if ($season !== null) return $season;

    checkAutoSeasonRollover();

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

function getNextSeason(): ?array
{
    static $nextSeason = null;
    if ($nextSeason !== null) return $nextSeason;

    $db = db_connect();
    if (!$db->tableExists('seasons')) return null;

    $nextSeason = $db->table('seasons')
        ->where('active', 0)
        ->where('season_number >', getSeasonNumber())
        ->orderBy('season_number', 'ASC')
        ->get()->getRowArray();

    return $nextSeason ?: null;
}

function getDaysUntilNextSeason(): int
{
    $next = getNextSeason();
    if ($next && !empty($next['start_date'])) {
        $diff = (int) ceil((strtotime($next['start_date']) - strtotime(date('Y-m-d'))) / 86400);
        return max(0, $diff);
    }
    $curr = getCurrentSeason();
    $len = (int) ($curr['duration_days'] ?? 135);
    $day = getSeasonDay();
    return max(0, $len - $day);
}

function triggerSeasonRollover(?int $targetSeasonNumber = null): bool
{
    $db = db_connect();
    if (!$db->tableExists('seasons')) return false;

    $curr = $db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
    $currSeasonNumber = (int) ($curr['season_number'] ?? 1);

    // Find next planned season
    $query = $db->table('seasons')->where('active', 0);
    if ($targetSeasonNumber !== null) {
        $query->where('season_number', $targetSeasonNumber);
    } else {
        $query->where('season_number >', $currSeasonNumber)->orderBy('season_number', 'ASC');
    }
    $nextSeason = $query->get()->getRowArray();
    if (!$nextSeason) {
        return false;
    }

    try {
        $db->transStart();

        // 1. Snapshot current leaderboard standings into archives
        if ($db->tableExists('season_leaderboard_archives')) {
            $alreadyArchived = $db->table('season_leaderboard_archives')
                ->where('season_number', $currSeasonNumber)
                ->countAllResults();

            if ($alreadyArchived === 0) {
                $now = date('Y-m-d H:i:s');
                $leaders = $db->table('player_finances')
                    ->select('player_finances.user_id, player_finances.cash, player_finances.reputation, users.username, users.resort_name')
                    ->join('users', 'users.id = player_finances.user_id')
                    ->where('users.id !=', 1)
                    ->orderBy('player_finances.cash', 'DESC')
                    ->limit(100)
                    ->get()->getResultArray();

                $archiveBatch = [];
                $rank = 1;
                foreach ($leaders as $l) {
                    $uId = (int) $l['user_id'];
                    $slopesCount = $db->table('player_items')->where('user_id', $uId)->where('item_type', 'slope')->countAllResults();
                    $liftsCount = $db->table('player_items')->where('user_id', $uId)->where('item_type', 'lift')->countAllResults();

                    $badge = ($rank <= 3) ? "Podium Champion (Rank #{$rank})" : (($rank <= 10) ? "Top 10 Season Pioneer" : "Season 1 Pioneer");

                    $archiveBatch[] = [
                        'season_number' => $currSeasonNumber,
                        'season_name'   => $curr['name'] ?? "Season {$currSeasonNumber}",
                        'user_id'       => $uId,
                        'username'      => $l['username'] ?? "Manager #{$uId}",
                        'resort_name'   => $l['resort_name'] ?? null,
                        'rank_position' => $rank,
                        'cash'          => $l['cash'],
                        'reputation'    => (int) ($l['reputation'] ?? 0),
                        'total_slopes'  => $slopesCount,
                        'total_lifts'   => $liftsCount,
                        'reward_badge'  => $badge,
                        'achieved_at'   => $now,
                    ];

                    // Award Genepis bonus to top 10 finishers
                    if ($rank <= 10 && $db->tableExists('genepis')) {
                        $db->query("UPDATE genepis SET balance = balance + 50 WHERE user_id = ?", [$uId]);
                    }

                    $rank++;
                }

                if (!empty($archiveBatch)) {
                    $db->table('season_leaderboard_archives')->insertBatch($archiveBatch);
                }
            }
        }

        // 2. Grant "Season 1 Pioneer" achievement to all active players
        if ($db->tableExists('achievements') && $currSeasonNumber === 1) {
            $allPlayers = $db->table('player_finances')->where('user_id !=', 1)->get()->getResultArray();
            $now = date('Y-m-d H:i:s');
            foreach ($allPlayers as $p) {
                $pId = (int) $p['user_id'];
                $hasBadge = $db->table('achievements')
                    ->where('user_id', $pId)
                    ->where('achievement_key', 'season_1_pioneer')
                    ->countAllResults();

                if ($hasBadge === 0) {
                    $db->table('achievements')->insert([
                        'user_id'         => $pId,
                        'achievement_key' => 'season_1_pioneer',
                        'unlocked_at'     => $now,
                    ]);
                }
            }
        }

        // 3. Unlock Sector 2 (Advanced Peaks) on Park City
        if ($db->tableExists('resort_sectors')) {
            $db->table('resort_sectors')
                ->where('resort_map', 'ParkCity')
                ->groupStart()
                    ->where('name', 'Sector 2')
                    ->orWhere('name', 'Sector 2 (Advanced Peaks)')
                ->groupEnd()
                ->update(['released' => 1, 'visible' => 1]);
        }

        // 4. Deactivate Season 1, Activate Season 2
        $db->table('seasons')->update(['active' => 0]);
        $db->table('seasons')->where('id', (int) $nextSeason['id'])->update(['active' => 1]);

        // 5. Broadcast in-game announcement
        if ($db->tableExists('activity_log')) {
            $now = date('Y-m-d H:i:s');
            $users = $db->table('users')->get()->getResultArray();
            $notifBatch = [];
            foreach ($users as $u) {
                $notifBatch[] = [
                    'user_id'    => $u['id'],
                    'game_day'   => 1,
                    'category'   => 'Season Rollover',
                    'message'    => "🎉 Welcome to Season 2! Sector 2 (Advanced Peaks) has unlocked on Park City Mountain. Black Diamond bowls and express gondolas are now available!",
                    'icon'       => 'fa-solid fa-mountain-sun',
                    'created_at' => $now,
                ];
            }
            if (!empty($notifBatch)) {
                $db->table('activity_log')->insertBatch($notifBatch);
            }
        }

        $db->transComplete();
        return true;
    } catch (\Throwable $e) {
        log_message('error', 'Season rollover error: ' . $e->getMessage());
        return false;
    }
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
