<?php

function getDifficulty(): string
{
    if (!function_exists('auth') || !auth()->loggedIn()) return 'standard';
    $db = db_connect();
    $finance = $db->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
    return $finance['difficulty'] ?? 'standard';
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

function getDifficultyLabel(): string
{
    return match(getDifficulty()) {
        'easy' => 'Easy',
        'hard' => 'Hard',
        default => 'Standard',
    };
}

function getDifficultyBadge(): string
{
    return match(getDifficulty()) {
        'easy' => '<span class="badge badge-success badge-xs">Easy</span>',
        'hard' => '<span class="badge badge-error badge-xs">Hard</span>',
        default => '<span class="badge badge-info badge-xs">Standard</span>',
    };
}

function isFeatureUnlocked(string $feature): bool
{
    if (!function_exists('auth') || !auth()->loggedIn()) return false;
    $difficulty = getDifficulty();
    if ($difficulty === 'easy') return true;

    static $unlocked = null;
    if ($unlocked === null) {
        $db = db_connect();
        $userId = auth()->id();
        $claimed = $db->table('achievements')->where('user_id', $userId)->where('claimed', 1)->get()->getResultArray();
        $claimedKeys = array_column($claimed, 'achievement_key');

        $defs = $db->table('achievement_defs')->where('unlocks IS NOT NULL')->get()->getResultArray();
        $unlocked = [];
        foreach ($defs as $d) {
            if (in_array($d['achievement_key'], $claimedKeys)) {
                $unlocked[] = $d['unlocks'];
            }
        }
    }
    return in_array($feature, $unlocked);
}

function getLockedFeatures(): array
{
    if (!function_exists('auth') || !auth()->loggedIn()) return [];
    if (getDifficulty() === 'easy') return [];

    $db = db_connect();
    $userId = auth()->id();
    $claimed = $db->table('achievements')->where('user_id', $userId)->where('claimed', 1)->get()->getResultArray();
    $claimedKeys = array_column($claimed, 'achievement_key');

    $defs = $db->table('achievement_defs')->where('unlocks IS NOT NULL')->get()->getResultArray();
    $locked = [];
    foreach ($defs as $d) {
        if (!in_array($d['achievement_key'], $claimedKeys)) {
            $locked[$d['unlocks']] = ['name' => $d['name'], 'desc' => $d['description'], 'label' => $d['unlock_label'], 'icon' => $d['icon'], 'progress' => 0, 'target' => (int) $d['target']];
            $userAch = $db->table('achievements')->where('user_id', $userId)->where('achievement_key', $d['achievement_key'])->get()->getRowArray();
            if ($userAch) $locked[$d['unlocks']]['progress'] = (int) $userAch['progress'];
        }
    }
    return $locked;
}

function getUnlockRequirement(string $feature): ?array
{
    $locked = getLockedFeatures();
    return $locked[$feature] ?? null;
}
