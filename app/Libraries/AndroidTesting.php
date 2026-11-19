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
            'message' => 'The Android app is in closed testing on Google Play. Open this to see how to join.',
            'icon' => 'fa-brands fa-android',
            'link' => '/beta/android',
            'is_read' => 0,
            'created_at' => $now,
        ], array_values($ids));
        foreach (array_chunk($rows, 500) as $chunk) $db->table('notifications')->insertBatch($chunk);
        return count($rows);
    }
}
