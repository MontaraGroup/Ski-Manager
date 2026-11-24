<?php

namespace App\Commands;

use App\Libraries\SeasonReset;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * One-time switch to economy v2: restarts the active season with every resort reset (plus legacy
 * bonus), rebalances salaries and difficulty, and turns the `economy_v2` flag on for everyone.
 * Without --apply it only prints what it would do.
 */
class EconomyRelease extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'economy:release';
    protected $description = 'Restart the active season on economy v2 (dry run unless --apply)';
    protected $usage = 'economy:release [--apply]';
    protected $options = ['--apply' => 'Make the changes (otherwise a dry run)'];

    /** New difficulty multipliers, in percent */
    private const DIFFICULTY = [
        'easy' => ['revenue_multiplier' => 125, 'visitor_multiplier' => 115, 'cost_multiplier' => 85],
        'hard' => ['revenue_multiplier' => 80, 'visitor_multiplier' => 80, 'cost_multiplier' => 120],
    ];
    private const SALARY_FACTOR = 0.6;

    public function run(array $params)
    {
        $apply = CLI::getOption('apply') !== null || in_array('--apply', $params, true);
        $db = db_connect();
        $season = $db->table('seasons')->where('active', 1)->orderBy('season_number', 'DESC')->get()->getRowArray();
        if (!$season) {
            CLI::error('No active season.');
            return EXIT_ERROR;
        }
        $num = (int) $season['season_number'];
        if (SeasonReset::alreadyApplied($num)) {
            CLI::error("Season {$num} was already restarted on economy v2.");
            return EXIT_ERROR;
        }

        $plan = SeasonReset::plan();
        $bonuses = array_column($plan, 'legacy_bonus');
        CLI::write(($apply ? 'APPLYING' : 'DRY RUN') . " — restart {$season['name']} on economy v2 from " . date('Y-m-d'), $apply ? 'red' : 'yellow');
        CLI::write(sprintf('%d players reset · %d get a legacy bonus · bonuses total %s · max %s',
            count($plan), count(array_filter($bonuses)), number_format(array_sum($bonuses)), number_format($bonuses ? max($bonuses) : 0)));
        CLI::write('Richest 10 (net worth → new starting cash):');
        foreach (array_slice($plan, 0, 10) as $p) {
            CLI::write(sprintf('  #%-5d %-8s %15s → %s', $p['user_id'], $p['difficulty'], number_format($p['net_worth']), number_format($p['starting_cash'] + $p['legacy_bonus'])));
        }
        CLI::write('Also: staff role salaries x' . self::SALARY_FACTOR . '; easy 125/115/85 and hard 80/80/120 (revenue/visitors/costs %); flag economy_v2 → everyone.');
        if (!$apply) {
            CLI::write('Nothing changed. Run with --apply to do it.', 'yellow');
            return EXIT_SUCCESS;
        }

        $db->transException(true)->transStart();
        try {
            foreach (self::DIFFICULTY as $diff => $values) {
                foreach ($values as $key => $value) {
                    $db->table('difficulty_config')->where('difficulty', $diff)->where('config_key', $key)->update(['config_value' => $value]);
                }
            }
            foreach ($db->table('staff_roles')->get()->getResultArray() as $role) {
                $db->table('staff_roles')->where('id', $role['id'])->update(['salary' => (int) (round($role['salary'] * self::SALARY_FACTOR / 10) * 10)]);
            }
            SeasonReset::restartActiveSeason();
            SeasonReset::apply($num, $season['name']);
            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('Rolled back: ' . $e->getMessage());
            return EXIT_ERROR;
        }
        cache()->clean();
        log_message('notice', "Economy v2 released: {$season['name']} restarted, " . count($plan) . ' resorts reset.');
        CLI::write("Done. {$season['name']} is on day 1. Set the economy_v2 flag to 100% in PostHog: " . \App\Libraries\Analytics::APP_URL . '/feature_flags', 'green');
        return EXIT_SUCCESS;
    }
}
