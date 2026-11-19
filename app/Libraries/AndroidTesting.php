<?php

namespace App\Libraries;

/**
 * Android closed test. Play Console generates access codes for the track; an admin pastes them in
 * at /admin/android-testing, and each eligible player (Beta::eligible: signed in, active, not demo)
 * can claim one from /beta/android. A player keeps the same code on every visit.
 */
class AndroidTesting
{
    public const TABLE = 'android_tester_codes';

    public static function joinUrl(): string
    {
        $url = trim((string) (setting('AndroidTesting.joinUrl') ?? ''));
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
    }

    /** What /beta/android shows this player */
    public static function state(int $userId): array
    {
        $db = db_connect();
        $mine = $userId > 0 ? $db->table(self::TABLE)->where('user_id', $userId)->get()->getRowArray() : null;
        return [
            'eligible'  => Beta::eligible($userId),
            'code'      => $mine['code'] ?? null,
            'claimedAt' => $mine['claimed_at'] ?? null,
            'available' => $mine !== null || $db->table(self::TABLE)->where('user_id', null)->countAllResults() > 0,
            'joinUrl'   => self::joinUrl(),
        ];
    }

    /** Give this player a code, or the one they already have. Null when none are left */
    public static function claim(int $userId): ?string
    {
        $db = db_connect();
        $existing = $db->table(self::TABLE)->select('code')->where('user_id', $userId)->get()->getRowArray();
        if ($existing) return $existing['code'];

        // One UPDATE takes the oldest free code, so two players claiming at once never get the same one.
        // The unique key on user_id stops one player getting two from a double click.
        try {
            $db->query(
                'UPDATE ' . self::TABLE . ' SET user_id = ?, claimed_at = ? WHERE user_id IS NULL ORDER BY id LIMIT 1',
                [$userId, date('Y-m-d H:i:s')]
            );
        } catch (\Throwable) {
            // The other request won the race for this player
        }
        $row = $db->table(self::TABLE)->select('code')->where('user_id', $userId)->get()->getRowArray();
        return $row['code'] ?? null;
    }

    /**
     * Codes from pasted text or a Play Console CSV export: any run of letters, digits and dashes,
     * 6 to 64 long, that has a digit in it (so header words like "Codes" are skipped).
     * @return string[]
     */
    public static function parse(string $raw): array
    {
        preg_match_all('/[A-Za-z0-9][A-Za-z0-9-]{5,63}/', $raw, $m);
        $codes = array_filter($m[0], fn($c) => preg_match('/\d/', $c));
        return array_values(array_unique($codes));
    }

    /** Add codes; ones already stored are skipped. Returns how many were new */
    public static function add(array $codes): int
    {
        if (!$codes) return 0;
        $db = db_connect();
        $before = $db->table(self::TABLE)->countAllResults();
        $now = date('Y-m-d H:i:s');
        foreach (array_chunk($codes, 500) as $chunk) {
            $db->table(self::TABLE)->ignore(true)->insertBatch(array_map(fn($c) => ['code' => $c, 'created_at' => $now], $chunk));
        }
        return $db->table(self::TABLE)->countAllResults() - $before;
    }

    /** Remove codes nobody has claimed yet (claimed ones stay so players keep theirs) */
    public static function removeUnclaimed(): int
    {
        $db = db_connect();
        $db->table(self::TABLE)->where('user_id', null)->delete();
        return $db->affectedRows();
    }

    /** Counts and the latest claims, for the admin page */
    public static function stats(int $limit = 100): array
    {
        $db = db_connect();
        $total = $db->table(self::TABLE)->countAllResults();
        $claimed = $db->table(self::TABLE)->where('user_id IS NOT NULL')->countAllResults();
        $rows = $db->table(self::TABLE . ' c')
            ->select('c.code, c.claimed_at, c.user_id, u.username')
            ->join('users u', 'u.id = c.user_id', 'left')
            ->where('c.user_id IS NOT NULL')
            ->orderBy('c.claimed_at', 'DESC')->limit($limit)
            ->get()->getResultArray();
        return [
            'total'   => $total,
            'claimed' => $claimed,
            'left'    => $total - $claimed,
            'claims'  => array_map(fn($r) => [
                'userId'    => (int) $r['user_id'],
                'username'  => (string) ($r['username'] ?? ('#' . $r['user_id'])),
                'code'      => (string) $r['code'],
                'claimedAt' => (string) $r['claimed_at'],
            ], $rows),
        ];
    }
}
