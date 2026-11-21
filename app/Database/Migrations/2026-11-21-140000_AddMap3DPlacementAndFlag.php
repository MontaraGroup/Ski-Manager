<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Interactive 3D Trail Map: optional player-pinned coordinates for buildings and parking lots,
 * plus the `map_3d` feature flag starting at level 1 (Admins only) for staged rollout.
 */
class AddMap3DPlacementAndFlag extends Migration
{
    private const PLACEMENT_COLUMNS = [
        'map_x'      => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
        'map_y'      => ['type' => 'DECIMAL', 'constraint' => '8,2', 'null' => true],
        'resort_map' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
    ];

    public function up()
    {
        foreach (['buildings', 'parking'] as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            $toAdd = [];
            foreach (self::PLACEMENT_COLUMNS as $col => $def) {
                if (!$this->db->fieldExists($col, $table)) {
                    $toAdd[$col] = $def;
                }
            }
            if (!empty($toAdd)) {
                $this->forge->addColumn($table, $toAdd);
            }
        }

        if ($this->db->tableExists('feature_flags') && !$this->db->table('feature_flags')->where('flag_key', 'map_3d')->countAllResults()) {
            $this->db->table('feature_flags')->insert([
                'flag_key'    => 'map_3d',
                'name'        => 'Interactive 3D Trail Map',
                'description' => '3D mountain map with built buildings, detailed run telemetry, data lenses, live animated skiers and a live skier counter on /map.',
                'enabled'     => 1,
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('feature_flags')) {
            $this->db->table('feature_flags')->where('flag_key', 'map_3d')->delete();
        }
        foreach (['buildings', 'parking'] as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach (array_keys(self::PLACEMENT_COLUMNS) as $col) {
                if ($this->db->fieldExists($col, $table)) {
                    $this->forge->dropColumn($table, $col);
                }
            }
        }
    }
}
