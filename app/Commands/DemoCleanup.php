<?php

namespace App\Commands;

use App\Libraries\AccountPurge;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Deletes demo guest accounts nobody kept: guests older than --days (default 7) that weren't played
 * in that time, and half-created guests (no resort) older than an hour. A demo account whose email
 * isn't a generated guest address is never touched: its player tried to save it.
 * Runs nightly from cron; --dry-run only counts.
 */
class DemoCleanup extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'demo:cleanup';
    protected $description = 'Delete unclaimed demo guest accounts older than N days (default 7)';
    protected $usage = 'demo:cleanup [--days 7] [--dry-run]';
    protected $options = ['--days' => 'Age in days before an unclaimed guest is deleted (default 7)', '--dry-run' => 'Only count, delete nothing'];

    public function run(array $params)
    {
        $days = max(1, (int) (CLI::getOption('days') ?: 7));
        $dryRun = CLI::getOption('dry-run') !== null;

        $ids = array_map('intval', array_column(db_connect()->query("
            SELECT u.id FROM users u
            LEFT JOIN player_finances pf ON pf.user_id = u.id
            WHERE u.status = 'demo'
              AND NOT EXISTS (SELECT 1 FROM auth_identities i WHERE i.user_id = u.id AND i.type = 'email_password'
                              AND i.secret NOT LIKE 'guest\\_%@demo.ski-manager.net')
              AND (
                (u.created_at < NOW() - INTERVAL ? DAY AND (u.last_active IS NULL OR u.last_active < NOW() - INTERVAL ? DAY))
                OR (pf.user_id IS NULL AND u.created_at < NOW() - INTERVAL 1 HOUR)
              )
            ORDER BY u.id", [$days, $days])->getResultArray(), 'id'));

        CLI::write(date('Y-m-d H:i') . ': ' . count($ids) . ' unclaimed demo account(s) older than ' . $days . ' days' . ($dryRun ? ' (dry run)' : ''));
        if ($dryRun) return;

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                AccountPurge::purge($id);
                $deleted++;
            } catch (\Throwable $e) {
                CLI::error('  #' . $id . ': ' . $e->getMessage());
            }
        }
        CLI::write('Deleted ' . $deleted . '.');
    }
}
