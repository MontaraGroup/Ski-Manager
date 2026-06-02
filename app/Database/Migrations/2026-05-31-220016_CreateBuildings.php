<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBuildings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'building_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'level' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'revenue_per_day' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'upkeep_per_day' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'condition_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'closed', 'upgrading', 'broken'], 'default' => 'open'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('buildings');
    }

    public function down()
    {
        $this->forge->dropTable('buildings');
    }
}

