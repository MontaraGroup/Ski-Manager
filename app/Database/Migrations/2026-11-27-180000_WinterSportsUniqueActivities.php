<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds bespoke per-activity JSON state (config_json), optional map placement coordinates,
 * and three Winter Activities achievements to achievement_defs.
 */
class WinterSportsUniqueActivities extends Migration
{
    private const ACHIEVEMENTS = [
        ['ws_first_venue', 'Beyond the Slopes', 'Build your first Winter Activity venue', 'fa-solid fa-hockey-puck', 1, 10000, 104],
        ['ws_all_five', 'Winter Sports Capital', 'Build all 5 unique Winter Activity venues', 'fa-solid fa-medal', 5, 100000, 105],
        ['ws_revenue_100k', 'Arena Mogul', 'Earn 100,000 from Winter Activities', 'fa-solid fa-coins', 100000, 50000, 106],
    ];

    public function up()
    {
        if ($this->db->tableExists('winter_sport_facilities')) {
            $fields = $this->db->getFieldNames('winter_sport_facilities');
            $add = [];
            if (!in_array('config_json', $fields, true)) {
                $add['config_json'] = ['type' => 'TEXT', 'null' => true, 'after' => 'ice_quality'];
            }
            if (!in_array('map_x', $fields, true)) {
                $add['map_x'] = ['type' => 'FLOAT', 'null' => true, 'default' => null];
            }
            if (!in_array('map_y', $fields, true)) {
                $add['map_y'] = ['type' => 'FLOAT', 'null' => true, 'default' => null];
            }
            if (!in_array('resort_map', $fields, true)) {
                $add['resort_map'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'default' => null];
            }
            if ($add) {
                $this->forge->addColumn('winter_sport_facilities', $add);
            }
        }

        if ($this->db->tableExists('achievement_defs')) {
            foreach (self::ACHIEVEMENTS as [$key, $name, $desc, $icon, $target, $reward, $sort]) {
                if ($this->db->table('achievement_defs')->where('achievement_key', $key)->countAllResults()) {
                    continue;
                }
                $this->db->table('achievement_defs')->insert([
                    'achievement_key' => $key,
                    'name' => $name,
                    'description' => $desc,
                    'icon' => $icon,
                    'target' => $target,
                    'reward' => $reward,
                    'sort_order' => $sort,
                ]);
            }
        }
    }

    public function down()
    {
        $keys = array_column(self::ACHIEVEMENTS, 0);
        if ($this->db->tableExists('achievements')) {
            $this->db->table('achievements')->whereIn('achievement_key', $keys)->delete();
        }
        if ($this->db->tableExists('achievement_defs')) {
            $this->db->table('achievement_defs')->whereIn('achievement_key', $keys)->delete();
        }
        if ($this->db->tableExists('winter_sport_facilities')) {
            $fields = $this->db->getFieldNames('winter_sport_facilities');
            foreach (['resort_map', 'map_y', 'map_x', 'config_json'] as $col) {
                if (in_array($col, $fields, true)) {
                    $this->forge->dropColumn('winter_sport_facilities', $col);
                }
            }
        }
    }
}
