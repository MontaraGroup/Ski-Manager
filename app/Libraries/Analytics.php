<?php

namespace App\Libraries;

use PostHog\PostHog;

/**
 * Server side of PostHog: feature flags, beta (early access) enrollment, and PHP error tracking.
 *
 * Env: POSTHOG_KEY (public project key), POSTHOG_HOST (the j.ski-manager.net proxy) and
 * POSTHOG_FLAGS_KEY (the project's Feature Flags secure key, phs_…, for local evaluation).
 * Players use their account id as the PostHog distinct id, the same id the browser and the
 * Android app identify with; visitors who aren't signed in share the id "guest".
 *
 * Nothing here may break a page: every call catches its own errors and returns null
 * ("PostHog didn't answer"), and callers fall back to their own default.
 */
class Analytics
{
    /** Where flags and early access features are managed */
    public const APP_URL = 'https://us.posthog.com/project/765435';

    private static ?bool $ready = null;

    /** Remote answers for early access flags, per request */
    private static array $remote = [];

    public static function ready(): bool
    {
        if (self::$ready !== null) return self::$ready;
        $key = (string) env('POSTHOG_KEY', '');
        if ($key === '' || !class_exists(PostHog::class)) return self::$ready = false;
        try {
            PostHog::init($key, [
                'host' => (string) env('POSTHOG_HOST', 'https://us.i.posthog.com'),
                // Events go out in one request at shutdown, after the response (Config\Events)
                'consumer' => 'lib_curl',
                'timeout' => 3000,
                'feature_flag_request_timeout_ms' => 1500,
                'flag_definition_cache_provider' => new PostHogFlagCache(),
                'error_tracking' => [
                    'enabled' => !is_cli() || ENVIRONMENT === 'production',
                    // CodeIgniter already turns PHP warnings into exceptions; only uncaught ones are sent
                    'capture_errors' => false,
                    // 404s, bad URLs, CSRF failures and redirects are the visitor's request, not a bug
                    'excluded_exceptions' => [\CodeIgniter\Exceptions\HTTPExceptionInterface::class],
                    'context_provider' => static fn() => ['distinctId' => self::distinctId(self::viewerId())],
                ],
            ], null, null, ((string) env('POSTHOG_FLAGS_KEY', '')) ?: null);
            return self::$ready = true;
        } catch (\Throwable $e) {
            log_message('error', 'PostHog init failed: ' . $e->getMessage());
            return self::$ready = false;
        }
    }

    public static function distinctId(?int $userId): string
    {
        return $userId && $userId > 0 ? (string) $userId : 'guest';
    }

    private static function viewerId(): ?int
    {
        try {
            // Only a session the request already opened: starting one at shutdown, after the
            // response was sent, raises "headers already sent"
            if (session_status() !== PHP_SESSION_ACTIVE || !function_exists('auth')) return null;
            return auth()->loggedIn() ? (int) auth()->id() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Whether `$key` is on for this player. Null when PostHog has no such flag or can't answer,
     * so the caller can apply its own default.
     */
    public static function flag(string $key, ?int $userId, bool $isAdmin): ?bool
    {
        if (!self::ready()) return null;
        $def = PostHogFlagCache::flags()[$key] ?? null;
        if ($def === null) return null;
        if (empty($def['active'])) return false;

        $id = self::distinctId($userId);
        try {
            if (self::level($key) !== 3) {
                // isFeatureEnabled() is deprecated in posthog-php 4.27 (it still works in 4.x). Its
                // replacement, evaluateFlags()->isEnabled(), always sends a $feature_flag_called event,
                // a billed event for every flag check on every page. The @ keeps the deprecation
                // notice out of Sentry and the log.
                return @PostHog::isFeatureEnabled($key, $id, [], self::personProps($isAdmin), [], true, false);
            }
            // Early access flags depend on the player's opt-in, which only PostHog's servers know
            // (local evaluation always says no), so ask PostHog's /flags for all of this
            // player's flags at once. Cached a minute per player; changing an opt-in clears it.
            if ($userId === null || $userId <= 0) return false;
            // A choice made in the last few minutes wins: PostHog takes a few seconds to apply it
            $recent = cache(self::choiceKey($key, $id));
            if (is_array($recent)) return (bool) $recent[0];
            $memo = self::remoteKey($id);
            if (!array_key_exists($memo, self::$remote)) {
                $cached = cache($memo);
                if (!is_array($cached)) {
                    $cached = PostHog::fetchFeatureVariants($id);
                    cache()->save($memo, $cached, 60);
                }
                self::$remote[$memo] = $cached;
            }
            return array_key_exists($key, self::$remote[$memo]) ? (bool) self::$remote[$memo][$key] : null;
        } catch (\Throwable $e) {
            log_message('warning', "PostHog flag {$key} failed: " . $e->getMessage());
            return null;
        }
    }

    private static function choiceKey(string $key, string $id): string
    {
        return 'posthog_choice_' . md5($key . '|' . $id);
    }

    private static function remoteKey(string $id): string
    {
        return 'posthog_remote_' . md5($id);
    }

    /** Experiment variant (string), true/false for a plain flag, or null. Records the exposure. */
    public static function variant(string $key, ?int $userId, bool $isAdmin = false): string|bool|null
    {
        if (!self::ready()) return null;
        try {
            // Deprecated in posthog-php 4.27 like isFeatureEnabled() above; this one records the exposure
            return @PostHog::getFeatureFlag($key, self::distinctId($userId), [], self::personProps($isAdmin));
        } catch (\Throwable $e) {
            log_message('warning', "PostHog variant {$key} failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 0 off, 1 admins only, 2 everyone, 3 beta (an early access feature players opt into),
     * read from the flag's definition. Null when PostHog has no such flag.
     */
    public static function level(string $key): ?int
    {
        self::ready();
        $def = PostHogFlagCache::flags()[$key] ?? null;
        if ($def === null) return null;
        if (empty($def['active'])) return 0;
        $filters = $def['filters'] ?? [];
        if (!empty($filters['feature_enrollment']) || str_contains(json_encode($filters), '$feature_enrollment/')) {
            return 3;
        }
        foreach ($filters['groups'] ?? [] as $group) {
            if (empty($group['properties']) && (int) ($group['rollout_percentage'] ?? 100) >= 100) return 2;
        }
        return 1;
    }

    /** Opt a player in or out of an early access feature (also works for the Android app) */
    public static function setEnrollment(int $userId, string $flagKey, bool $on): bool
    {
        if (!self::ready()) return false;
        try {
            PostHog::capture([
                'distinctId' => self::distinctId($userId),
                'event' => '$feature_enrollment_update',
                'properties' => [
                    '$feature_flag' => $flagKey,
                    '$feature_enrollment' => $on,
                    '$set' => ['$feature_enrollment/' . $flagKey => $on],
                ],
            ]);
            PostHog::flush();
            $memo = self::remoteKey(self::distinctId($userId));
            unset(self::$remote[$memo]);
            cache()->delete($memo);
            cache()->save(self::choiceKey($flagKey, self::distinctId($userId)), [$on], 300);
            return true;
        } catch (\Throwable $e) {
            log_message('error', "PostHog enrollment {$flagKey} failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Early access features from PostHog's public endpoint (the one posthog-js uses), cached
     * five minutes. Each: name, description, stage, documentationUrl, flagKey.
     */
    public static function earlyAccessFeatures(): array
    {
        $cached = cache('posthog_early_access');
        if (is_array($cached)) return $cached;
        $key = (string) env('POSTHOG_KEY', '');
        if ($key === '') return [];
        $host = rtrim((string) env('POSTHOG_HOST', 'https://us.i.posthog.com'), '/');
        $features = [];
        try {
            $res = service('curlrequest', ['timeout' => 3, 'connect_timeout' => 2, 'http_errors' => false], null, null, false)
                ->get($host . '/api/early_access_features/?token=' . rawurlencode($key));
            if ($res->getStatusCode() !== 200) throw new \RuntimeException('HTTP ' . $res->getStatusCode());
            $features = json_decode((string) $res->getBody(), true)['earlyAccessFeatures'] ?? [];
            cache()->save('posthog_early_access', $features, 300);
        } catch (\Throwable $e) {
            log_message('warning', 'PostHog early access list failed: ' . $e->getMessage());
            cache()->save('posthog_early_access', [], 30);
        }
        return is_array($features) ? $features : [];
    }

    /** Sends queued events; the shutdown function in Config\Events calls it after the response */
    public static function flush(): void
    {
        if (!self::$ready) return;
        try {
            PostHog::flush();
        } catch (\Throwable $e) {
            log_message('warning', 'PostHog flush failed: ' . $e->getMessage());
        }
    }

    private static function personProps(bool $isAdmin): array
    {
        // Matches the flag conditions "is_admin exact true" and what the browser sets
        return ['is_admin' => $isAdmin ? 'true' : 'false'];
    }
}
