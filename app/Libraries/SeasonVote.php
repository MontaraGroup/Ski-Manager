<?php

namespace App\Libraries;

/**
 * Rules for the season format vote: who can vote, when the popup shows, and the results.
 * The vote is open while the `season_mode_vote` flag is on for the viewer and the close date
 * (setting('SeasonVote.closesAt')) is in the future.
 */
class SeasonVote
{
    public const CHOICES = ['shared', 'individual'];
    public const SNOOZE_DAYS = 3;
    public const COMMENT_MAX = 500;

    /** SQL rule for accounts that count: active, not demo, not deleted (users table aliased as u) */
    private const ELIGIBLE_SQL = "u.active = 1 AND (u.status IS NULL OR u.status <> 'demo') AND u.deleted_at IS NULL";

    public static function closesAt(): ?string
    {
        $value = setting('SeasonVote.closesAt');
        return is_string($value) && strtotime($value) ? $value : null;
    }

    public static function flagOn(): bool
    {
        return function_exists('featureEnabled') && featureEnabled('season_mode_vote');
    }

    public static function isOpen(): bool
    {
        $closes = self::closesAt();
        return self::flagOn() && $closes !== null && strtotime($closes) > time();
    }

    /** Closed after having been open: the flag is still on and the close date has passed */
    public static function isClosed(): bool
    {
        $closes = self::closesAt();
        return self::flagOn() && $closes !== null && strtotime($closes) <= time();
    }

    public static function closesLabel(?string $closes = null): ?string
    {
        $closes ??= self::closesAt();
        return $closes ? date('j F Y, H:i', strtotime($closes)) : null;
    }

    public static function eligible(int $userId): bool
    {
        if ($userId <= 0) return false;
        return (bool) db_connect()->table('users u')->where('u.id', $userId)->where(self::ELIGIBLE_SQL, null, false)->countAllResults();
    }

    public static function row(int $userId): ?array
    {
        return db_connect()->table('season_mode_votes')->where('user_id', $userId)->get()->getRowArray() ?: null;
    }

    /**
     * What the shell and Settings need: { open, closesAt, closesLabel, myChoice, showPrompt, order }.
     * No popup on admin pages, after voting, or while "Remind me later" is running.
     */
    public static function state(int $userId, string $path = ''): array
    {
        $closes = self::closesAt();
        $state = [
            'open' => false,
            'closesAt' => $closes,
            'closesLabel' => self::closesLabel($closes),
            'myChoice' => null,
            'showPrompt' => false,
            // Fixed per player so the cards don't swap on every page load
            'order' => $userId % 2 === 0 ? ['shared', 'individual'] : ['individual', 'shared'],
        ];
        if (!self::isOpen() || !self::eligible($userId)) return $state;

        $row = self::row($userId);
        $state['open'] = true;
        $state['myChoice'] = $row['choice'] ?? null;
        $snoozed = !empty($row['snoozed_until']) && strtotime($row['snoozed_until']) > time();
        $onAdmin = $path === '/admin' || str_starts_with($path, '/admin/');
        $state['showPrompt'] = $state['myChoice'] === null && !$snoozed && !$onAdmin;
        return $state;
    }

    /** One row per account: a second vote replaces the first */
    public static function vote(int $userId, string $choice, ?string $comment): void
    {
        $now = date('Y-m-d H:i:s');
        db_connect()->query(
            'INSERT INTO season_mode_votes (user_id, choice, comment, snoozed_until, created_at, updated_at) VALUES (?, ?, ?, NULL, ?, ?)
             ON DUPLICATE KEY UPDATE choice = VALUES(choice), comment = VALUES(comment), snoozed_until = NULL, updated_at = VALUES(updated_at)',
            [$userId, $choice, $comment, $now, $now]
        );
    }

    public static function snooze(int $userId): string
    {
        $now = date('Y-m-d H:i:s');
        $until = date('Y-m-d H:i:s', strtotime('+' . self::SNOOZE_DAYS . ' days'));
        db_connect()->query(
            'INSERT INTO season_mode_votes (user_id, snoozed_until, created_at, updated_at) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE snoozed_until = VALUES(snoozed_until), updated_at = VALUES(updated_at)',
            [$userId, $until, $now, $now]
        );
        return $until;
    }

    /** Totals over accounts that count. Comments only when asked (admin page) */
    public static function results(bool $withComments = false): array
    {
        $db = db_connect();
        $counts = ['shared' => 0, 'individual' => 0];
        $rows = $db->query('SELECT v.choice, COUNT(*) AS n FROM season_mode_votes v JOIN users u ON u.id = v.user_id
            WHERE v.choice IS NOT NULL AND ' . self::ELIGIBLE_SQL . ' GROUP BY v.choice')->getResultArray();
        foreach ($rows as $r) $counts[$r['choice']] = (int) $r['n'];

        $votes = $counts['shared'] + $counts['individual'];
        $eligible = (int) $db->query('SELECT COUNT(*) AS n FROM users u WHERE ' . self::ELIGIBLE_SQL)->getRow()->n;
        $pct = fn(int $n) => $votes ? round($n * 100 / $votes) : 0;

        $out = [
            'votes' => $votes,
            'shared' => ['count' => $counts['shared'], 'pct' => $pct($counts['shared'])],
            'individual' => ['count' => $counts['individual'], 'pct' => $pct($counts['individual'])],
            'eligible' => $eligible,
            'notVoted' => max(0, $eligible - $votes),
        ];
        if ($withComments) {
            $out['comments'] = array_map(fn($c) => [
                'username' => (string) $c['username'],
                'choice' => (string) $c['choice'],
                'comment' => (string) $c['comment'],
                'date' => date('j M Y, H:i', strtotime($c['updated_at'])),
            ], $db->query("SELECT u.username, v.choice, v.comment, v.updated_at FROM season_mode_votes v JOIN users u ON u.id = v.user_id
                WHERE v.choice IS NOT NULL AND v.comment IS NOT NULL AND v.comment <> '' AND " . self::ELIGIBLE_SQL . '
                ORDER BY v.updated_at DESC LIMIT 500')->getResultArray());
        }
        return $out;
    }
}
