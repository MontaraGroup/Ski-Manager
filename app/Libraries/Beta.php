<?php

namespace App\Libraries;

/**
 * The beta program. A flag at level 3 (FEATURE_LEVEL_BETA) is on for admins and for beta members
 * who haven't switched that feature off. Members start with every beta feature on; leaving the
 * program removes the membership and every switch, so all beta features go off at once.
 * Demo accounts never take part.
 */
class Beta
{
    public const LEVEL = 3;
    public const COMMENT_MAX = 500;

    /** Per request: user id => ['member' => bool, 'off' => [flag_key => true]] */
    private static array $cache = [];

    /** Whether the beta feature `$key` is on for this player (membership and their own switch) */
    public static function hasFeatureOn(int $userId, string $key): bool
    {
        $s = self::load($userId);
        return $s['member'] && !isset($s['off'][$key]);
    }

    public static function isMember(int $userId): bool
    {
        return self::load($userId)['member'];
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
        unset(self::$cache[$userId]);
    }

    /** Leave the program: every beta feature goes off, and switches start fresh if they join again */
    public static function leave(int $userId): void
    {
        $db = db_connect();
        $db->table('beta_feature_optouts')->where('user_id', $userId)->delete();
        $db->table('beta_members')->where('user_id', $userId)->delete();
        unset(self::$cache[$userId]);
    }

    /** A flag row at the beta level, or null */
    public static function betaFlag(string $key): ?array
    {
        $row = db_connect()->table('feature_flags')->where('flag_key', $key)->where('enabled', self::LEVEL)->get()->getRowArray();
        return $row ?: null;
    }

    public static function setFeature(int $userId, string $key, bool $on): void
    {
        $db = db_connect();
        $db->table('beta_feature_optouts')->where('user_id', $userId)->where('flag_key', $key)->delete();
        if (!$on) {
            $db->table('beta_feature_optouts')->insert(['user_id' => $userId, 'flag_key' => $key, 'created_at' => date('Y-m-d H:i:s')]);
        }
        unset(self::$cache[$userId]);
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
        $db = db_connect();
        $flags = $db->table('feature_flags')->where('enabled', self::LEVEL)->orderBy('name')->get()->getResultArray();
        $mine = [];
        if ($userId > 0 && $flags) {
            $rows = $db->table('beta_feedback')->where('user_id', $userId)->whereIn('flag_key', array_column($flags, 'flag_key'))->get()->getResultArray();
            foreach ($rows as $r) $mine[$r['flag_key']] = ['rating' => (int) $r['rating'], 'comment' => (string) ($r['comment'] ?? '')];
        }
        $member = $userId > 0 && self::isMember($userId);
        $features = array_map(fn($f) => [
            'key' => (string) $f['flag_key'],
            'name' => self::cleanName((string) $f['name']),
            'description' => (string) ($f['description'] ?? ''),
            'since' => !empty($f['beta_since']) ? date('j M Y', strtotime($f['beta_since'])) : null,
            'on' => $member && self::hasFeatureOn($userId, (string) $f['flag_key']),
            'feedback' => $mine[$f['flag_key']] ?? null,
        ], $flags);

        return [
            'member' => $member,
            'eligible' => $userId > 0 && self::eligible($userId),
            'onCount' => count(array_filter($features, fn($f) => $f['on'])),
            'features' => $features,
        ];
    }

    /** For /admin/features: members with each beta feature on, and feedback per flag */
    public static function adminSummary(): array
    {
        $db = db_connect();
        $members = $db->table('beta_members')->countAllResults();
        $off = [];
        foreach ($db->query('SELECT o.flag_key, COUNT(*) n FROM beta_feature_optouts o JOIN beta_members m ON m.user_id = o.user_id GROUP BY o.flag_key')->getResultArray() as $r) {
            $off[$r['flag_key']] = (int) $r['n'];
        }
        $feedback = [];
        foreach ($db->query('SELECT flag_key, COUNT(*) n, AVG(rating) avg FROM beta_feedback GROUP BY flag_key')->getResultArray() as $r) {
            $feedback[$r['flag_key']] = ['count' => (int) $r['n'], 'average' => round((float) $r['avg'], 1), 'comments' => []];
        }
        foreach (array_keys($feedback) as $key) {
            $rows = $db->table('beta_feedback f')
                ->select('f.rating, f.comment, f.updated_at, u.username')
                ->join('users u', 'u.id = f.user_id', 'left')
                ->where('f.flag_key', $key)->where('f.comment IS NOT NULL')->where('f.comment !=', '')
                ->orderBy('f.updated_at', 'DESC')->limit(5)->get()->getResultArray();
            $feedback[$key]['comments'] = array_map(fn($r) => [
                'username' => (string) ($r['username'] ?? 'Deleted player'),
                'rating' => (int) $r['rating'],
                'comment' => (string) $r['comment'],
                'date' => date('j M Y', strtotime((string) $r['updated_at'])),
            ], $rows);
        }

        return ['members' => $members, 'off' => $off, 'feedback' => $feedback];
    }

    /** Old flag names carry a "[BETA] " prefix; the beta page says "beta" already */
    public static function cleanName(string $name): string
    {
        return trim(str_replace('[BETA]', '', $name));
    }

    private static function load(int $userId): array
    {
        if (isset(self::$cache[$userId])) return self::$cache[$userId];
        $state = ['member' => false, 'off' => []];
        if ($userId > 0) {
            try {
                $db = db_connect();
                $state['member'] = $db->table('beta_members')->where('user_id', $userId)->countAllResults() > 0;
                if ($state['member']) {
                    foreach ($db->table('beta_feature_optouts')->select('flag_key')->where('user_id', $userId)->get()->getResultArray() as $r) {
                        $state['off'][$r['flag_key']] = true;
                    }
                }
            } catch (\Throwable $e) {
                // Tables not migrated yet: nobody is a member
                log_message('warning', 'Beta membership lookup failed: ' . $e->getMessage());
            }
        }
        return self::$cache[$userId] = $state;
    }
}
