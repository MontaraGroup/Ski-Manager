<?php

/**
 * The signed-in player's difficulty. Read once per request and player: the nav alone asks for it
 * dozens of times per page (unlock and hidden-page checks). A change of difficulty redirects, so the
 * next request reads the new value.
 */
function getDifficulty(): string
{
    if (!function_exists('auth') || !auth()->loggedIn()) return 'standard';
    static $byUser = [];
    $userId = (int) auth()->id();
    if (!isset($byUser[$userId])) {
        $row = db_connect()->table('player_finances')->select('difficulty')->where('user_id', $userId)->get()->getRowArray();
        $byUser[$userId] = (string) ($row['difficulty'] ?? 'standard');
    }
    return $byUser[$userId];
}

function getDifficultyConfig(string $key, string $default = '100'): string
{
    static $cache = [];
    $difficulty = getDifficulty();
    $cacheKey = $difficulty . '_' . $key;
    if (isset($cache[$cacheKey])) return $cache[$cacheKey];

    $db = db_connect();
    $row = $db->table('difficulty_config')->where('difficulty', $difficulty)->where('config_key', $key)->get()->getRowArray();
    $cache[$cacheKey] = $row['config_value'] ?? $default;
    return $cache[$cacheKey];
}

function getDifficultyMultiplier(string $key): float
{
    return (float) getDifficultyConfig($key, '100') / 100;
}

function isPageHidden(string $page): bool
{
    $hidden = getDifficultyConfig('hidden_pages', '');
    if (empty($hidden)) return false;
    return in_array($page, explode(',', $hidden));
}

/**
 * The signed-in player's achievement rows and the defs that unlock features, read once per
 * request: the nav checks every locked feature, and the locked page lists them all.
 */
function featureUnlockData(): array
{
    static $byUser = [];
    $userId = (int) auth()->id();
    if (!isset($byUser[$userId])) {
        $db = db_connect();
        $claimed = [];
        $progress = [];
        foreach ($db->table('achievements')->select('achievement_key, claimed, progress')->where('user_id', $userId)->orderBy('id')->get()->getResultArray() as $row) {
            if ((int) $row['claimed'] === 1) $claimed[$row['achievement_key']] = true;
            $progress[$row['achievement_key']] ??= (int) $row['progress'];
        }
        $byUser[$userId] = [
            'claimed' => $claimed,
            'progress' => $progress,
            'defs' => $db->table('achievement_defs')->where('unlocks IS NOT NULL')->get()->getResultArray(),
        ];
    }
    return $byUser[$userId];
}

function isFeatureUnlocked(string $feature): bool
{
    if (!function_exists('auth') || !auth()->loggedIn()) return false;
    if (getDifficulty() === 'easy') return true;

    $data = featureUnlockData();
    foreach ($data['defs'] as $d) {
        if ($d['unlocks'] === $feature && isset($data['claimed'][$d['achievement_key']])) return true;
    }
    return false;
}

function getLockedFeatures(): array
{
    if (!function_exists('auth') || !auth()->loggedIn()) return [];
    if (getDifficulty() === 'easy') return [];

    $data = featureUnlockData();
    $locked = [];
    foreach ($data['defs'] as $d) {
        if (!isset($data['claimed'][$d['achievement_key']])) {
            $locked[$d['unlocks']] = ['name' => $d['name'], 'desc' => $d['description'], 'label' => $d['unlock_label'], 'icon' => $d['icon'], 'progress' => $data['progress'][$d['achievement_key']] ?? 0, 'target' => (int) $d['target']];
        }
    }
    return $locked;
}

function getUnlockRequirement(string $feature): ?array
{
    $locked = getLockedFeatures();
    return $locked[$feature] ?? null;
}
