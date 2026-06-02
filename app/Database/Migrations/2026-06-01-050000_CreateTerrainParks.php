<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTerrainParks extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'park_type' => ['type' => 'ENUM', 'constraint' => ['halfpipe', 'jump_line', 'rail_garden', 'slopestyle'], 'default' => 'rail_garden'],
            'size' => ['type' => 'ENUM', 'constraint' => ['small', 'medium', 'large'], 'default' => 'small'],
            'condition_pct' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 100.00],
            'popularity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'daily_visitors' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'closed', 'under_construction', 'maintenance'], 'default' => 'under_construction'],
            'build_days_left' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'slope_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('terrain_parks');
    }

    public function down()
    {
        $this->forge->dropTable('terrain_parks');
    }
}
