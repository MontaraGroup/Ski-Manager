<?php

namespace App\Libraries;

/**
 * The beta program, on PostHog early access features (Analytics::APP_URL, Early access).
 * Each early access feature at stage alpha or beta is listed on /beta; its linked flag is on
 * for admins and for players who opted in. Opting in or out is a PostHog enrollment
 * (Analytics::setEnrollment), so it works the same from the web and the Android app.
 * Membership stays local: joining opts the player into every beta feature, leaving opts them
 * out of all of them. Feedback stays in the beta_feedback table. Demo accounts never take part.
 */
class Beta
{
    public const COMMENT_MAX = 500;

    /** Early access stages shown on /beta */
    private const STAGES = ['alpha', 'beta'];

    /** Per request: user id => member */
    private static array $members = [];

    public static function isMember(int $userId): bool
    {
        if ($userId <= 0) return false;
        if (!array_key_exists($userId, self::$members)) {
            try {
                self::$members[$userId] = db_connect()->table('beta_members')->where('user_id', $userId)->countAllResults() > 0;
            } catch (\Throwable $e) {
                log_message('warning', 'Beta membership lookup failed: ' . $e->getMessage());
                self::$members[$userId] = false;
            }
        }
        return self::$members[$userId];
    }

    /** Signed-in, active, and not a demo account */
    public static function eligible(int $userId): bool
    {
        if ($userId <= 0) return false;
        if (session()->get('is_demo')) return false;
        $user = db_connect()->table('users')->select('status, active')->where('id', $userId)->get()->getRowArray();
        return $user && (string) ($user['status'] ?? '') !== 'demo' && (int) ($user['active'] ?? 0) === 1;
    }

    public static function join(int $userId): void
    {
        $db = db_connect();
        if (!$db->table('beta_members')->where('user_id', $userId)->countAllResults()) {
            $db->table('beta_members')->insert(['user_id' => $userId, 'joined_at' => date('Y-m-d H:i:s')]);
        }
        self::$members[$userId] = true;
        Analytics::setPersonProps($userId, ['is_beta_tester' => 'true']);
        foreach (self::features() as $f) Analytics::setEnrollment($userId, $f['flag_key'], true);
    }

    /** Leave the program: every beta feature goes off */
    public static function leave(int $userId): void
    {
        db_connect()->table('beta_members')->where('user_id', $userId)->delete();
        self::$members[$userId] = false;
        Analytics::setPersonProps($userId, ['is_beta_tester' => 'false']);
        foreach (self::features() as $f) Analytics::setEnrollment($userId, $f['flag_key'], false);
    }

    /** An early access feature listed on /beta (one players switch on themselves), or null */
    public static function betaFlag(string $key): ?array
    {
        foreach (self::features() as $f) {
            if ($f['flag_key'] === $key) return $f;
        }
        return null;
    }

    /** A feature on /beta that takes feedback: an early access feature or a beta tester flag */
    public static function listedFlag(string $key): ?array
    {
        foreach (array_merge(self::features(), self::testerFlags()) as $f) {
            if ($f['flag_key'] === $key) return $f;
        }
        return null;
    }

    /**
     * Active PostHog flags with a release condition "is_beta_tester exact true": on for every
     * beta tester (or the condition's rollout share of them), with no switch of their own.
     * Each also has `rollout`, the percentage, and `name`/`description` split from the flag's
     * description at its first sentence.
     */
    public static function testerFlags(): array
    {
        $early = array_column(self::features(), 'flag_key');
        $out = [];
        foreach (PostHogFlagCache::flags() as $key => $flag) {
            if (empty($flag['active']) || !empty($flag['deleted']) || in_array($key, $early, true)) continue;
            $rollout = null;
            foreach ($flag['filters']['groups'] ?? [] as $group) {
                foreach ($group['properties'] ?? [] as $p) {
                    $values = array_map('strval', (array) ($p['value'] ?? []));
                    if (($p['key'] ?? '') === 'is_beta_tester' && ($p['operator'] ?? 'exact') === 'exact' && in_array('true', $values, true)) {
                        $rollout = max($rollout ?? 0, (int) ($group['rollout_percentage'] ?? 100));
                    }
                }
            }
            if ($rollout === null || $rollout <= 0) continue;
            $text = trim((string) ($flag['name'] ?? ''));
            $parts = preg_split('/(?<=\.)\s+/', $text, 2);
            $name = rtrim((string) ($parts[0] ?? ''), '.');
            $out[] = [
                'flag_key' => (string) $key,
                'name' => $name !== '' ? $name : (string) $key,
                'description' => trim((string) ($parts[1] ?? '')),
                'docs' => '',
                'rollout' => min(100, $rollout),
            ];
        }
        usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $out;
    }

    public static function setFeature(int $userId, string $key, bool $on): void
    {
        Analytics::setEnrollment($userId, $key, $on);
    }

    /** Early access features at alpha or beta: flag_key, name, description, docs */
    public static function features(): array
    {
        $out = [];
        foreach (Analytics::earlyAccessFeatures() as $f) {
            if (!is_array($f) || empty($f['flagKey']) || !in_array($f['stage'] ?? '', self::STAGES, true)) continue;
            $out[] = [
                'flag_key' => (string) $f['flagKey'],
                'name' => (string) ($f['name'] ?? $f['flagKey']),
                'description' => (string) ($f['description'] ?? ''),
                'docs' => (string) ($f['documentationUrl'] ?? ''),
            ];
        }
        usort($out, fn($a, $b) => strcasecmp($a['name'], $b['name']));
        return $out;
    }

    /** One editable row per player per feature */
    public static function saveFeedback(int $userId, string $key, int $rating, ?string $comment): void
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');
        $row = $db->table('beta_feedback')->select('id')->where('user_id', $userId)->where('flag_key', $key)->get()->getRowArray();
        $data = ['rating' => $rating, 'comment' => $comment, 'updated_at' => $now];
        if ($row) {
            $db->table('beta_feedback')->where('id', $row['id'])->update($data);
        } else {
            $db->table('beta_feedback')->insert($data + ['user_id' => $userId, 'flag_key' => $key, 'created_at' => $now]);
        }
    }

    /** What /beta and Settings show this player */
    public static function state(int $userId): array
    {
        $flags = array_merge(self::features(), self::testerFlags());
        $mine = [];
        if ($userId > 0 && $flags) {
            $rows = db_connect()->table('beta_feedback')->where('user_id', $userId)->whereIn('flag_key', array_column($flags, 'flag_key'))->get()->getResultArray();
            foreach ($rows as $r) $mine[$r['flag_key']] = ['rating' => (int) $r['rating'], 'comment' => (string) ($r['comment'] ?? '')];
        }
        $member = self::isMember($userId);
        $features = array_map(fn($f) => [
            'key' => $f['flag_key'],
            'name' => self::cleanName($f['name']),
            'description' => $f['description'],
            'since' => null,
            'on' => $member && featureEnabledFor($f['flag_key'], $userId),
            'feedback' => $mine[$f['flag_key']] ?? null,
            // Set for beta tester flags: on for every tester (or this share of them), no switch
            'testers' => isset($f['rollout']),
            'rollout' => $f['rollout'] ?? null,
        ], $flags);

        return [
            'member' => $member,
            'eligible' => $userId > 0 && self::eligible($userId),
            'onCount' => count(array_filter($features, fn($f) => $f['on'])),
            'features' => $features,
        ];
    }

    /** Old flag names carry a "[BETA] " prefix; the beta page says "beta" already */
    public static function cleanName(string $name): string
    {
        return trim(str_replace('[BETA]', '', $name));
    }
}
