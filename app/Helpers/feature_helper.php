<?php

/** Same rule as AdminFilter: user #1 or anyone in the admin/superadmin groups. False in CLI (no signed-in user). */
function featureViewerIsAdmin(): bool
{
    if (!function_exists('auth') || !auth()->loggedIn()) return false;
    $user = auth()->user();
    return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
}

/** Flags that stay off until their row exists (they change game rules, so a missing row mustn't switch them on) */
const FEATURE_DEFAULT_OFF = ['economy_v2', 'season_mode_vote'];

/**
 * Flag levels: 0 = off, 1 = admins only, 2 = everyone.
 * Unknown keys count as on, so a feature isn't hidden just because its flag row is missing.
 */
function featureEnabled(string $key): bool
{
    static $cache = null;
    if ($cache === null) {
        $rows = db_connect()->table('feature_flags')->get()->getResultArray();
        $cache = [];
        foreach ($rows as $r) $cache[$r['flag_key']] = (int) $r['enabled'];
    }
    $level = $cache[$key] ?? (in_array($key, FEATURE_DEFAULT_OFF, true) ? 0 : 2);
    if ($level === 2) return true;
    if ($level === 1) return featureViewerIsAdmin();
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
    return isset($admins[$userId]);
}

/** 0 = off, 1 = admins only, 2 = everyone; unknown keys count as on (except FEATURE_DEFAULT_OFF) */
function featureLevel(string $key): int
{
    $row = db_connect()->table('feature_flags')->select('enabled')->where('flag_key', $key)->get()->getRowArray();
    return $row ? (int) $row['enabled'] : (in_array($key, FEATURE_DEFAULT_OFF, true) ? 0 : 2);
}
