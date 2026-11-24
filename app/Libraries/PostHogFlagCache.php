<?php

namespace App\Libraries;

use PostHog\FlagDefinitionCacheProvider;

/**
 * Shares PostHog's feature flag definitions between PHP-FPM workers and keeps the last good copy.
 *
 * - The definitions live in the app cache, so only one request a minute downloads them.
 * - Every successful download is also written to writable/cache/posthog-flags.json with no
 *   expiry. If PostHog can't be reached, flags keep their last known values instead of every
 *   unknown flag falling back to "on".
 */
class PostHogFlagCache implements FlagDefinitionCacheProvider
{
    private const KEY = 'posthog_flag_defs';
    private const FRESH = 'posthog_flag_defs_fresh';
    private const LOCK = 'posthog_flag_defs_lock';
    private const TTL = 60;

    public static function file(): string
    {
        return WRITEPATH . 'cache/posthog-flags.json';
    }

    /** The definitions this server last saw, from the cache or the last-good file */
    public static function definitions(): ?array
    {
        $defs = cache(self::KEY);
        if (is_array($defs) && isset($defs['flags'])) return $defs;
        $raw = @file_get_contents(self::file());
        $defs = $raw ? json_decode($raw, true) : null;
        return is_array($defs) && isset($defs['flags']) ? $defs : null;
    }

    /** flag key => definition */
    public static function flags(): array
    {
        static $byKey = null;
        if ($byKey !== null) return $byKey;
        $byKey = [];
        foreach (self::definitions()['flags'] ?? [] as $flag) {
            if (is_array($flag) && isset($flag['key'])) $byKey[(string) $flag['key']] = $flag;
        }
        return $byKey;
    }

    public function getFlagDefinitions(): ?array
    {
        return self::definitions();
    }

    public function shouldFetchFlagDefinitions(): bool
    {
        if (cache(self::FRESH)) return false;
        // One worker fetches; the others keep using the cached copy. The lock also spaces out
        // retries to every 10 s while PostHog is down.
        if (cache(self::LOCK)) return false;
        cache()->save(self::LOCK, 1, 10);
        return true;
    }

    public function onFlagDefinitionsReceived(array $data): void
    {
        cache()->save(self::KEY, $data, 0);
        cache()->save(self::FRESH, 1, self::TTL);
        $tmp = self::file() . '.' . getmypid();
        if (@file_put_contents($tmp, json_encode($data)) !== false) {
            @rename($tmp, self::file());
        }
    }

    public function shutdown(): void
    {
    }
}
