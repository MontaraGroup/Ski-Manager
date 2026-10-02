<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResortVotesTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('resort_votes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'resort_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('user_id');
            $this->forge->addKey('resort_key');
            $this->forge->createTable('resort_votes', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('resort_votes', true);
    }
}
