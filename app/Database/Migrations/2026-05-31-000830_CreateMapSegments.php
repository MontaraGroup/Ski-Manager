<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMapSegments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'type' => ['type' => 'ENUM', 'constraint' => ['lift', 'slope'], 'null' => false],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'points' => ['type' => 'JSON', 'null' => false],
            'length_meters' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sector' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'active' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('map_segments');
    }

    public function down()
    {
        $this->forge->dropTable('map_segments');
    }
}

