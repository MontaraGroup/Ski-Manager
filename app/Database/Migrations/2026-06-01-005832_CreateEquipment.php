<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEquipment extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'equipment_type' => ['type' => 'ENUM', 'constraint' => ['groomer', 'snowmaker']],
            'model_key' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'brand' => ['type' => 'VARCHAR', 'constraint' => 50],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'fuel_cost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'condition_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'assigned_to' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'off', 'broken', 'maintenance'], 'default' => 'off'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('equipment');
    }

    public function down()
    {
        $this->forge->dropTable('equipment');
    }
}

