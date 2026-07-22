<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUpdatesTable extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Safely check if 'updates' table exists
        if (! $db->tableExists('updates')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'version'      => ['type' => 'VARCHAR', 'constraint' => 50],
                'title'        => ['type' => 'VARCHAR', 'constraint' => 255],
                'description'  => ['type' => 'TEXT', 'null' => true],
                'released_at'  => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'type'         => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'Major'],
                'is_latest'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('updates', true);
        }

        // Safely check if 'update_items' table exists
        if (! $db->tableExists('update_items')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'update_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'type'        => ['type' => 'VARCHAR', 'constraint' => 50],
                'content'     => ['type' => 'TEXT'],
                'sort_order'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->createTable('update_items', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('update_items', true);
        $this->forge->dropTable('updates', true);
    }
}
