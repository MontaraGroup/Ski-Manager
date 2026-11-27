<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * One weather row per game day. Two requests running Weather::ensure() at once each inserted the
 * same day (production had day 21 three times). Keep the first row of each day, then make
 * game_day unique so ensure()'s INSERT IGNORE can't add another. A copy whose table already has a
 * unique key covering game_day (a test database with season_number) only loses its duplicates.
 */
class WeatherUniqueDay extends Migration
{
    public function up()
    {
        $bySeason = $this->db->fieldExists('season_number', 'weather');
        $this->db->query('DELETE w FROM weather w JOIN weather k ON k.game_day = w.game_day AND k.id < w.id'
            . ($bySeason ? ' AND k.season_number <=> w.season_number' : ''));
        if ($bySeason) return;

        foreach ($this->db->getIndexData('weather') as $name => $index) {
            if ($index->fields === ['game_day'] && $index->type !== 'PRIMARY') {
                $this->db->query('ALTER TABLE weather DROP INDEX `' . $name . '`');
            }
        }
        $this->db->query('ALTER TABLE weather ADD UNIQUE KEY game_day (game_day)');
    }

    public function down()
    {
        if ($this->db->fieldExists('season_number', 'weather')) return;
        $this->db->query('ALTER TABLE weather DROP INDEX game_day, ADD KEY game_day (game_day)');
    }
}
