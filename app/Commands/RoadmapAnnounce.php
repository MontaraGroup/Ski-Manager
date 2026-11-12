<?php

namespace App\Commands;

use App\Libraries\RoadmapRewards;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * One-time notification telling active players about the roadmap and its rewards.
 * Safe to run more than once: players who already have a 'roadmap_launch' notification are skipped.
 */
class RoadmapAnnounce extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'roadmap:announce';
    protected $description = 'Tell active players about the roadmap (skips anyone already told)';
    protected $usage = 'roadmap:announce [--days 30] [--dry-run]';
    protected $options = ['--days' => 'Count players active in the last N days (default 30)', '--dry-run' => 'Only count, send nothing'];

    public function run(array $params)
    {
        helper('notify');
        $db = db_connect();
        $days = max(1, (int) (CLI::getOption('days') ?? 30));
        $dryRun = CLI::getOption('dry-run') !== null;

        $users = $db->query(
            "SELECT u.id FROM users u
             JOIN player_finances pf ON pf.user_id = u.id AND pf.profile_completed = 1
             WHERE u.last_active >= ? AND COALESCE(u.status, '') <> 'demo'
               AND NOT EXISTS (SELECT 1 FROM notifications n WHERE n.user_id = u.id AND n.type = 'roadmap_launch')",
            [date('Y-m-d H:i:s', strtotime("-{$days} days"))]
        )->getResultArray();

        CLI::write(count($users) . " players to notify (active in the last {$days} days, not told yet)" . ($dryRun ? ' — dry run, nothing sent' : ''));
        if ($dryRun) return;

        $message = 'Vote on what we build next. Your first vote each week earns ' . RoadmapRewards::VOTE_WEEKLY_GENEPIS
            . ' Génépis, and an idea of yours that ships earns ' . RoadmapRewards::IDEA_SHIPPED_GENEPIS . '.';
        foreach ($users as $u) {
            notify((int) $u['id'], 'roadmap_launch', 'Help decide what comes next', $message, 'fa-solid fa-map-location-dot', '/roadmap');
        }
        CLI::write('Sent ' . count($users) . ' notifications.', 'green');
    }
}
