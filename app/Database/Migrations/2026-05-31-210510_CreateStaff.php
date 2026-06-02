<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateStaff extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'role' => ['type' => 'VARCHAR', 'constraint' => 50],
            'level' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'salary' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'morale' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'experience' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'assigned_to' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'resting', 'training', 'fired'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('staff');
    }

    public function down()
    {
        $this->forge->dropTable('staff');
    }
}

