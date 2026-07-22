<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUpdatesTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'version'      => ['type' => 'VARCHAR', 'constraint' => 50],
            'title'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'description'  => ['type' => 'TEXT', 'null' => true],
            'release_date' => ['type' => 'VARCHAR', 'constraint' => 50],
            'type'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Major'],
            'is_latest'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'content_json' => ['type' => 'TEXT', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('updates');
    }

    public function down()
    {
        $this->forge->dropTable('updates');
    }
}
