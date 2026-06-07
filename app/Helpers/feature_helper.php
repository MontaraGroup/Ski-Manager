<?php

function featureEnabled(string $key): bool
{
    static $cache = null;
    if ($cache === null) {
        $rows = db_connect()->table('feature_flags')->get()->getResultArray();
        $cache = [];
        foreach ($rows as $r) $cache[$r['flag_key']] = (bool) $r['enabled'];
    }
    return $cache[$key] ?? true;
}
