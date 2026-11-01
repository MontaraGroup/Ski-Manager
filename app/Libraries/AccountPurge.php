<?php

namespace App\Libraries;

/**
 * Permanently removes a user and everything that belongs to them. Used by "delete my account" and by
 * the demo cleanup (spark demo:cleanup).
 */
class AccountPurge
{
    /** Tables with a user_id that record who made something rather than belong to the player */
    private const KEEP = ['users', 'map_segments', 'admin_audit_log'];

    /** Every table with a user_id column, read from the schema so new tables are never missed */
    public static function tables(): array
    {
        static $tables = null;
        if ($tables === null) {
            $tables = array_values(array_diff(array_column(db_connect()->query(
                "SELECT TABLE_NAME AS t FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'user_id'"
            )->getResultArray(), 't'), self::KEEP));
        }
        return $tables;
    }

    public static function purge(int $userId): void
    {
        $db = db_connect();
        $db->transStart();
        foreach (self::tables() as $table) {
            $db->table($table)->where('user_id', $userId)->delete();
        }
        $db->table('tournaments')->where('host_id', $userId)->delete();
        $db->table('resort_likes')->where('resort_user_id', $userId)->delete();
        $db->table('users')->where('id', $userId)->delete();
        $db->transComplete();
        if (!$db->transStatus()) throw new \RuntimeException('Could not delete account #' . $userId);
    }
}
