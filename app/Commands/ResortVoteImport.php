<?php

namespace App\Commands;

use App\Libraries\ResortVote;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Reloads the resort vote's ski area list from app/Database/Seeds/data/ski_areas_na.csv after
 * scripts/build_ski_areas.py refreshed it. Adds new areas and updates known ones; never deletes,
 * so ballots keep pointing at their area.
 */
class ResortVoteImport extends BaseCommand
{
    protected $group = 'Game';
    protected $name = 'resortvote:import';
    protected $description = 'Load the resort vote ski area list from its CSV (adds and updates, never deletes)';

    public function run(array $params)
    {
        $n = ResortVote::importAreas();
        CLI::write("Read {$n} ski areas. Table now has " . db_connect()->table('vote_ski_areas')->countAllResults() . ' rows.', 'green');
    }
}
