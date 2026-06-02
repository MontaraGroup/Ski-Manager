<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSnowmaking extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'cannon_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'level' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'output_per_day' => ['type' => 'INT', 'unsigned' => true, 'default' => 3],
            'energy_cost' => ['type' => 'INT', 'unsigned' => true, 'default' => 500],
            'water_usage' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'off', 'broken'], 'default' => 'off'],
            'condition_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'assigned_slope' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('snow_cannons');
    }

    public function down()
    {
        $this->forge->dropTable('snow_cannons');
    }
}

