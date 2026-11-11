<?php

/** Same rule as AdminFilter: user #1 or anyone in the admin/superadmin groups. False in CLI (no signed-in user). */
function featureViewerIsAdmin(): bool
{
    if (!function_exists('auth') || !auth()->loggedIn()) return false;
    $user = auth()->user();
    return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
}

/** Flags that stay off until their row exists (they change game rules, so a missing row mustn't switch them on) */
const FEATURE_DEFAULT_OFF = ['economy_v2', 'season_mode_vote', 'demo_v2'];

/**
 * Flag levels: 0 = off, 1 = admins only, 2 = everyone, 3 = beta (admins plus beta members who
 * haven't switched it off; App\Libraries\Beta). Beta is stored as 3 rather than renumbering, so
 * every existing 0/1/2 row and `=== 2` check keeps its meaning.
 * Unknown keys count as on, so a feature isn't hidden just because its flag row is missing.
 */
const FEATURE_LEVEL_BETA = 3;

/** Every flag's level, read once per request */
function featureLevels(): array
{
    static $cache = null;
    if ($cache === null) {
        $rows = db_connect()->table('feature_flags')->get()->getResultArray();
        $cache = [];
        foreach ($rows as $r) $cache[$r['flag_key']] = (int) $r['enabled'];
    }
    return $cache;
}

/** Whether the viewer sees `$key` only because it's at the beta level (the "Beta" badge) */
function featureIsBetaFor(string $key): bool
{
    return (featureLevels()[$key] ?? null) === FEATURE_LEVEL_BETA && featureEnabled($key);
}

function featureEnabled(string $key): bool
{
    $level = featureLevels()[$key] ?? (in_array($key, FEATURE_DEFAULT_OFF, true) ? 0 : 2);
    if ($level === 2) return true;
    if ($level === 1) return featureViewerIsAdmin();
    if ($level === FEATURE_LEVEL_BETA) {
        if (featureViewerIsAdmin()) return true;
        if (!function_exists('auth') || !auth()->loggedIn()) return false;
        return \App\Libraries\Beta::hasFeatureOn((int) auth()->id(), $key);
    }
    return false;
}

/** Flag level for any player, for code with no signed-in viewer (the game tick, season rollover) */
function featureEnabledFor(string $key, int $userId): bool
{
    $level = featureLevel($key);
    if ($level === 2) return true;
    if ($level === 0) return false;
    static $admins = null;
    if ($admins === null) {
        $admins = [1 => true];
        foreach (db_connect()->table('auth_groups_users')->whereIn('group', ['admin', 'superadmin'])->get()->getResultArray() as $r) {
            $admins[(int) $r['user_id']] = true;
        }
    }
    if (isset($admins[$userId])) return true;
    return $level === FEATURE_LEVEL_BETA && \App\Libraries\Beta::hasFeatureOn($userId, $key);
}

/** 0 = off, 1 = admins only, 2 = everyone, 3 = beta; unknown keys count as on (except FEATURE_DEFAULT_OFF) */
function featureLevel(string $key): int
{
    $row = db_connect()->table('feature_flags')->select('enabled')->where('flag_key', $key)->get()->getRowArray();
    return $row ? (int) $row['enabled'] : (in_array($key, FEATURE_DEFAULT_OFF, true) ? 0 : 2);
}
