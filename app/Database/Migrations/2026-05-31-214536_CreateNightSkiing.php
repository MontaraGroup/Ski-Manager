<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNightSkiing extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'light_name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'light_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'level' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'coverage' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'energy_cost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'off', 'broken'], 'default' => 'off'],
            'condition_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'assigned_slope' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('night_skiing');
    }

    public function down()
    {
        $this->forge->dropTable('night_skiing');
    }
}

