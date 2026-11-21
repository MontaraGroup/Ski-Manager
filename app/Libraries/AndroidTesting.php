<?php

namespace App\Libraries;

/**
 * Android closed test on Google Play. Testers are the members of a Google Group that is added to the
 * closed track in Play Console: a player joins the group, then opts in with the test link. Both links
 * are set at /admin/android-testing. The android_tester_codes table from the earlier access-code
 * version is no longer read.
 */
class AndroidTesting
{
    /** The closed test's opt-in link from Play Console, or '' */
    public static function joinUrl(): string
    {
        return self::httpsSetting('AndroidTesting.joinUrl');
    }

    /** The testers Google Group, or '' */
    public static function groupUrl(): string
    {
        return self::httpsSetting('AndroidTesting.groupUrl');
    }

    private static function httpsSetting(string $key): string
    {
        $url = trim((string) (setting($key) ?? ''));
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
    }

    /** What /beta/android shows this player */
    public static function state(int $userId): array
    {
        return [
            'eligible' => Beta::eligible($userId),
            'groupUrl' => self::groupUrl(),
            'joinUrl'  => self::joinUrl(),
            'reward'   => self::rewardState($userId),
        ];
    }

    /** The notification type the invite is stored under; also how a repeat send finds who already has it */
    public const NOTICE_TYPE = 'android_beta';

    /** Accounts that can claim a code: active and not demo, the same rule as Beta::eligible */
    private static function eligibleUsers(): \CodeIgniter\Database\BaseBuilder
    {
        return db_connect()->table('users')->where('active', 1)
            ->groupStart()->where('status IS NULL')->orWhere('status !=', 'demo')->groupEnd();
    }

    /** For the admin page: who the invite reaches, and who it reached already */
    public static function inviteStats(): array
    {
        $db = db_connect();
        $eligible = self::eligibleUsers()->countAllResults();
        $notified = $db->table('notifications')->select('user_id')->distinct()->where('type', self::NOTICE_TYPE)->countAllResults();
        $last = $db->table('notifications')->selectMax('created_at')->where('type', self::NOTICE_TYPE)->get()->getRow('created_at');
        return ['eligible' => $eligible, 'notified' => $notified, 'notifiedAt' => $last ? (string) $last : null];
    }

    /** Sends the invite to every eligible account that doesn't have it yet. Returns how many got it */
    public static function notifyAll(): int
    {
        $db = db_connect();
        $already = array_map('intval', array_column(
            $db->table('notifications')->select('user_id')->distinct()->where('type', self::NOTICE_TYPE)->get()->getResultArray(), 'user_id'));
        $ids = array_diff(array_map('intval', array_column(self::eligibleUsers()->select('id')->get()->getResultArray(), 'id')), $already);
        $now = date('Y-m-d H:i:s');
        $rows = array_map(fn(int $id) => [
            'user_id' => $id,
            'type' => self::NOTICE_TYPE,
            'title' => 'Ski Manager for Android: join the closed test',
            'message' => 'The Android app is in closed testing on Google Play. The first ' . self::REWARD_CAP . ' testers to use it on ' . self::REWARD_DAYS . ' different days get ' . self::REWARD_GENEPIS . ' Génépis. Open this to see how to join.',
            'icon' => 'fa-brands fa-android',
            'link' => '/beta/android',
            'is_read' => 0,
            'created_at' => $now,
        ], array_values($ids));
        foreach (array_chunk($rows, 500) as $chunk) $db->table('notifications')->insertBatch($chunk);
        return count($rows);
    }

    /** The tester reward: the first REWARD_CAP players to use the Android app on REWARD_DAYS different days */
    public const REWARD_DAYS = 14;
    public const REWARD_CAP = 14;
    public const REWARD_GENEPIS = 200;

    /**
     * Notes that this player used the Android app today (called by the AppToken filter on each app
     * request). On the first request of a new day it also checks whether they've now earned the reward.
     */
    public static function recordAppDay(int $userId): void
    {
        $db = db_connect();
        $db->query('INSERT IGNORE INTO android_app_days (user_id, day) VALUES (?, ?)', [$userId, date('Y-m-d')]);
        if ($db->affectedRows() === 1) self::payIfEarned($userId);
    }

    private static function payIfEarned(int $userId): void
    {
        $db = db_connect();
        if ($db->table('android_app_days')->where('user_id', $userId)->countAllResults() < self::REWARD_DAYS) return;
        // One payout at a time, so two players finishing together can't both take the last reward
        if (!(int) $db->query("SELECT GET_LOCK('android_test_reward', 5) AS l")->getRow('l')) return;
        try {
            $paid = $db->table('android_test_rewards')->countAllResults();
            $mine = $db->table('android_test_rewards')->where('user_id', $userId)->countAllResults();
            if ($mine > 0 || $paid >= self::REWARD_CAP) return;
            $db->table('android_test_rewards')->insert(['user_id' => $userId, 'paid_at' => date('Y-m-d H:i:s')]);
            Ledger::creditGenepis($userId, self::REWARD_GENEPIS, 'Android closed test: ' . self::REWARD_DAYS . ' days');
            helper('notify');
            notify($userId, 'android_beta_reward', 'Android tester reward: ' . self::REWARD_GENEPIS . ' Génépis',
                "You used the Android app on " . self::REWARD_DAYS . " different days, so you're one of the first " . self::REWARD_CAP . ' testers to finish. Thanks for testing.',
                'fa-brands fa-android', '/genepis');
        } finally {
            $db->query("SELECT RELEASE_LOCK('android_test_reward')");
        }
    }

    /** This player's progress toward the reward, for /beta/android */
    public static function rewardState(int $userId): array
    {
        $db = db_connect();
        $paidCount = $db->table('android_test_rewards')->countAllResults();
        return [
            'days'     => $userId > 0 ? $db->table('android_app_days')->where('user_id', $userId)->countAllResults() : 0,
            'needed'   => self::REWARD_DAYS,
            'genepis'  => self::REWARD_GENEPIS,
            'cap'      => self::REWARD_CAP,
            'left'     => max(0, self::REWARD_CAP - $paidCount),
            'paid'     => $userId > 0 && $db->table('android_test_rewards')->where('user_id', $userId)->countAllResults() > 0,
        ];
    }

    /** For the admin page: who has been paid, and who is closest */
    public static function rewardStats(): array
    {
        $db = db_connect();
        $paid = $db->query('SELECT r.user_id, u.username, r.paid_at FROM android_test_rewards r LEFT JOIN users u ON u.id = r.user_id ORDER BY r.paid_at')->getResultArray();
        $close = $db->query('SELECT d.user_id, u.username, COUNT(*) AS days, MIN(d.day) AS first_day, MAX(d.day) AS last_day
            FROM android_app_days d LEFT JOIN users u ON u.id = d.user_id
            WHERE d.user_id NOT IN (SELECT user_id FROM android_test_rewards)
            GROUP BY d.user_id, u.username ORDER BY days DESC, last_day DESC LIMIT 20')->getResultArray();
        return [
            'cap' => self::REWARD_CAP, 'needed' => self::REWARD_DAYS, 'genepis' => self::REWARD_GENEPIS,
            'paid' => array_map(fn($r) => ['userId' => (int) $r['user_id'], 'username' => (string) ($r['username'] ?? '#' . $r['user_id']), 'paidAt' => (string) $r['paid_at']], $paid),
            'testers' => array_map(fn($r) => ['userId' => (int) $r['user_id'], 'username' => (string) ($r['username'] ?? '#' . $r['user_id']), 'days' => (int) $r['days'], 'firstDay' => (string) $r['first_day'], 'lastDay' => (string) $r['last_day']], $close),
        ];
    }
}
