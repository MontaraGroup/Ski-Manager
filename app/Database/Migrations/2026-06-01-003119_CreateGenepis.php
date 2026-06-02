<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGenepis extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'balance' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'total_earned' => ['type' => 'INT', 'unsigned' => true, 'default' => 100],
            'total_spent' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id', false, true);
        $this->forge->createTable('genepis');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'amount' => ['type' => 'INT', 'default' => 0],
            'type' => ['type' => 'ENUM', 'constraint' => ['earned', 'spent'], 'default' => 'earned'],
            'reason' => ['type' => 'VARCHAR', 'constraint' => 200],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('genepis_log');
    }

    public function down()
    {
        $this->forge->dropTable('genepis');
        $this->forge->dropTable('genepis_log');
    }
}

