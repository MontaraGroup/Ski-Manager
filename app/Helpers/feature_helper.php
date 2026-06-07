<?php

function featureEnabled(string $key): bool
{
    static $cache = null;
    if ($cache === null) {
        $rows = db_connect()->table('feature_flags')->get()->getResultArray();
        $cache = [];
        foreach ($rows as $r) $cache[$r['flag_key']] = (int) $r['enabled'];
    }
    $level = $cache[$key] ?? 2;
    if ($level === 2) return true;
    if ($level === 1) return auth()->id() === 1;
    return false;
}
