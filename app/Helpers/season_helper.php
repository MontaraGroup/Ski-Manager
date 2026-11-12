<?php

/**
 * Check if the site is currently in maintenance mode.
 * Supports both filesystem flag (WRITEPATH . 'maintenance.flag') for instant zero-DB toggling
 * and the database 'seasons' table maintenance fields.
 */
function isMaintenanceMode(): bool
{
    static $isMaint = null;
    if ($isMaint !== null) {
        return $isMaint;
    }

    // 1. Filesystem flag override (instant switch)
    if (defined('WRITEPATH') && file_exists(WRITEPATH . 'maintenance.flag')) {
        $isMaint = true;
        return true;
    }

    // 2. Database seasons maintenance flag
    try {
        $db = db_connect();
        $row = $db->table('seasons')
            ->where('maintenance', 1)
            ->orderBy('season_number', 'DESC')
            ->get()
            ->getRowArray();

        if (!$row) {
            $isMaint = false;
            return false;
        }

        // Check if maintenance period has expired
        if (!empty($row['maintenance_until'])) {
            $untilTs = strtotime($row['maintenance_until']);
            if ($untilTs && time() >= $untilTs) {
                $isMaint = false;
                return false;
            }
        }

        $isMaint = true;
        return true;
    } catch (\Throwable $e) {
        log_message('error', 'isMaintenanceMode error: ' . $e->getMessage());
        $isMaint = false;
        return false;
    }
}

/**
 * Get detailed maintenance state, scheduled reopening time, and notice message.
 */
function getMaintenanceInfo(): array
{
    $default = [
        'enabled'           => false,
        'until'             => '2026-10-25 18:00:00 UTC',
        'message'           => 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for the launch of Season 2.',
        'remaining_seconds' => 0,
    ];

    if (defined('WRITEPATH') && file_exists(WRITEPATH . 'maintenance.flag')) {
        $raw = @file_get_contents(WRITEPATH . 'maintenance.flag');
        $parsed = json_decode($raw, true);
        if (is_array($parsed)) {
            $default = array_merge($default, $parsed);
        }
        $default['enabled'] = true;
    }

    try {
        $row = db_connect()->table('seasons')
            ->where('maintenance', 1)
            ->orderBy('season_number', 'DESC')
            ->get()
            ->getRowArray();
        if ($row) {
            $default['enabled'] = true;
            if (!empty($row['maintenance_until'])) {
                $default['until'] = $row['maintenance_until'] . ' UTC';
                $rem = strtotime($row['maintenance_until']) - time();
                $default['remaining_seconds'] = max(0, $rem);
                if ($rem <= 0) {
                    $default['enabled'] = false;
                }
            }
            if (!empty($row['maintenance_message'])) {
                $default['message'] = $row['maintenance_message'];
            }
        }
    } catch (\Throwable $e) {
        // Fall back gracefully
    }

    if ($default['remaining_seconds'] === 0 && !empty($default['until'])) {
        $default['remaining_seconds'] = max(0, strtotime($default['until']) - time());
    }

    return $default;
}

function checkAutoSeasonRollover(): void
{
    try {
        if (isMaintenanceMode()) {
            return;
        }

        $db = db_connect();
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

function triggerSeasonRollover(?int $targetSeasonNumber = null, bool $force = false): bool
{
    // Prevent automated season rollover while maintenance mode is active, unless explicitly forced
    if (!$force && isMaintenanceMode()) {
        log_message('notice', 'Season rollover blocked because maintenance mode is active.');
        return false;
    }

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

    // Weather rows are keyed only by game_day, which restarts at 1 each season. Without this,
    // the new season would read last season's rows and the tick would skip days that "exist".
    // DDL commits implicitly in MySQL, so the archive table is created before the transaction.
    $weatherArchive = 'weather_season_' . $currSeasonNumber;
    if ($db->tableExists('weather')) {
        $db->query("CREATE TABLE IF NOT EXISTS `{$weatherArchive}` LIKE `weather`");
    }

    $economyV2 = function_exists('featureLevel') && featureLevel('economy_v2') === 2;

    try {
        $db->transStart();

        // 0. Archive last season's weather; the new season's is generated from day 1 (App\Libraries\Weather)
        if ($db->tableExists('weather')) {
            if ($db->table($weatherArchive)->countAllResults() === 0) {
                $db->query("INSERT INTO `{$weatherArchive}` SELECT * FROM `weather`");
            }
            $db->table('weather')->emptyTable();
        }

        // 1. Snapshot current leaderboard standings into archives
        if ($db->tableExists('season_leaderboard_archives')) {
            $alreadyArchived = $db->table('season_leaderboard_archives')
                ->where('season_number', $currSeasonNumber)
                ->countAllResults();

            if ($alreadyArchived === 0) {
                $now = date('Y-m-d H:i:s');
                // resort_name lives on player_finances (users has no such column)
                $resortNameCol = $db->fieldExists('resort_name', 'player_finances') ? ', player_finances.resort_name' : '';
                $leaders = $db->table('player_finances')
                    ->select('player_finances.user_id, player_finances.cash, player_finances.reputation, users.username' . $resortNameCol)
                    ->join('users', 'users.id = player_finances.user_id')
                    ->where('users.id !=', 1)
                    // Demo accounts (status 'demo') shouldn't take podium/top-10 rewards; normal users have NULL status
                    ->groupStart()->where('users.status IS NULL')->orWhere('users.status !=', 'demo')->groupEnd()
                    // Economy v2 ranks the season's profit (resorts restart every season), not total cash
                    ->orderBy($economyV2 ? 'CAST(player_finances.total_income AS SIGNED) - CAST(player_finances.total_expenses AS SIGNED)' : 'player_finances.cash', 'DESC', false)
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
                        $db->query("UPDATE genepis SET balance = balance + 50, total_earned = total_earned + 50 WHERE user_id = ?", [$uId]);
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
                $existingAch = $db->table('achievements')
                    ->where('user_id', $pId)
                    ->where('achievement_key', 'season_1_pioneer')
                    ->get()
                    ->getRowArray();

                if ($existingAch) {
                    $db->table('achievements')
                        ->where('id', (int) $existingAch['id'])
                        ->update([
                            'progress'  => 1,
                            'completed' => 1,
                        ]);
                } else {
                    $db->table('achievements')->insert([
                        'user_id'         => $pId,
                        'achievement_key' => 'season_1_pioneer',
                        'name'            => 'Season 1 Pioneer',
                        'description'     => 'Participated as an active ski resort manager during inaugural Season 1 at Park City.',
                        'icon'            => 'fa-solid fa-medal text-warning',
                        'progress'        => 1,
                        'target'          => 1,
                        'reward_amount'   => 10000,
                        'completed'       => 1,
                        'claimed'         => 0,
                        'created_at'      => $now,
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

        // 4. Deactivate Season 1, Activate Season 2, Clear maintenance
        $db->table('seasons')->update(['active' => 0]);
        $db->table('seasons')->where('id', (int) $nextSeason['id'])->update([
            'active'            => 1,
            'maintenance'       => 0,
            'maintenance_until' => null,
        ]);

        // 4b. Economy v2: every resort starts the new season over, with a legacy bonus
        if ($economyV2) {
            \App\Libraries\SeasonReset::apply((int) $nextSeason['season_number'], $nextSeason['name'] ?? ('Season ' . $nextSeason['season_number']));
        }

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
        $db->transRollback();
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
