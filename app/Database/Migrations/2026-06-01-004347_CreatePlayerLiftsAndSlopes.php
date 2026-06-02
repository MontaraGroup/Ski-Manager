<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePlayerLiftsAndSlopes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'segment_id' => ['type' => 'INT', 'unsigned' => true],
            'item_type' => ['type' => 'ENUM', 'constraint' => ['lift', 'slope']],
            'subtype' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'level' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'length_meters' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'condition_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'difficulty' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'closed', 'building', 'broken'], 'default' => 'open'],
            'sector' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('player_items');
    }

    public function down()
    {
        $this->forge->dropTable('player_items');
    }
}

