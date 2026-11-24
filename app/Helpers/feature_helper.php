<?php

use App\Libraries\Analytics;

/*
 * Feature flags live in PostHog (Feature flags, tag "game"): App\Libraries\Analytics::APP_URL.
 * Levels as the rest of the code knows them:
 *   0 off               flag switched off
 *   1 admins only       release condition "is_admin = true"
 *   2 everyone          100% rollout
 *   3 beta              an early access feature: admins plus players who opted in on /beta
 *
 * PostHog flags are evaluated locally from cached definitions (App\Libraries\PostHogFlagCache).
 * If this server has never downloaded them, the old feature_flags table answers instead, so an
 * outage can't switch admin-only or unfinished features on for everyone.
 * Unknown keys count as on, except FEATURE_DEFAULT_OFF.
 */

/** Same rule as AdminFilter: user #1 or anyone in the admin/superadmin groups. False in CLI (no signed-in user). */
function featureViewerIsAdmin(): bool
{
    if (!function_exists('auth') || !auth()->loggedIn()) return false;
    $user = auth()->user();
    return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
}

/** Flags that stay off until they exist (they change game rules, so a missing flag mustn't switch them on) */
const FEATURE_DEFAULT_OFF = ['economy_v2', 'season_mode_vote', 'demo_v2', 'map_3d'];

const FEATURE_LEVEL_BETA = 3;

/** True once PostHog flag definitions have been downloaded at least once on this server */
function featureFlagsFromPostHog(): bool
{
    static $known = null;
    if ($known === null) {
        Analytics::ready(); // starting the client downloads the definitions when they are due
        $known = \App\Libraries\PostHogFlagCache::definitions() !== null;
    }
    return $known;
}

/** Levels from the old feature_flags table: only used before PostHog has answered once */
function featureLegacyLevels(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db_connect()->table('feature_flags')->get()->getResultArray() as $r) $cache[$r['flag_key']] = (int) $r['enabled'];
        } catch (\Throwable) {
        }
    }
    return $cache;
}

function featureDefaultLevel(string $key): int
{
    return in_array($key, FEATURE_DEFAULT_OFF, true) ? 0 : 2;
}

/** Whether the viewer sees `$key` only because it's a beta (early access) feature: the "Beta" badge */
function featureIsBetaFor(string $key): bool
{
    return featureLevel($key) === FEATURE_LEVEL_BETA && featureEnabled($key);
}

function featureEnabled(string $key): bool
{
    $userId = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : null;
    return featureCheck($key, $userId, featureViewerIsAdmin());
}

/** Flag value for any player, for code with no signed-in viewer (the game tick, season rollover) */
function featureEnabledFor(string $key, int $userId): bool
{
    static $admins = null;
    if ($admins === null) {
        $admins = [1 => true];
        foreach (db_connect()->table('auth_groups_users')->whereIn('group', ['admin', 'superadmin'])->get()->getResultArray() as $r) {
            $admins[(int) $r['user_id']] = true;
        }
    }
    return featureCheck($key, $userId, isset($admins[$userId]));
}

function featureCheck(string $key, ?int $userId, bool $isAdmin): bool
{
    if (!featureFlagsFromPostHog()) {
        $level = featureLegacyLevels()[$key] ?? featureDefaultLevel($key);
        return $level === 2 || ($level >= 1 && $isAdmin);
    }
    // Beta features are always on for admins, whatever their own opt-in says
    if ($isAdmin && Analytics::level($key) === FEATURE_LEVEL_BETA) return true;
    $on = Analytics::flag($key, $userId, $isAdmin);
    if ($on !== null) return $on;
    // PostHog has no such flag, or couldn't answer for a beta flag: admins only for beta,
    // the default for unknown keys
    $level = Analytics::level($key) ?? featureDefaultLevel($key);
    return $level === 2 || ($level >= 1 && $isAdmin);
}

/** 0 = off, 1 = admins only, 2 = everyone, 3 = beta; unknown keys count as on (except FEATURE_DEFAULT_OFF) */
function featureLevel(string $key): int
{
    if (!featureFlagsFromPostHog()) return featureLegacyLevels()[$key] ?? featureDefaultLevel($key);
    return Analytics::level($key) ?? featureDefaultLevel($key);
}
