<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLiftTickets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'ticket_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'price' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'active' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('lift_tickets');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'game_day' => ['type' => 'INT', 'unsigned' => true],
            'ticket_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'quantity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'revenue' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'game_day']);
        $this->forge->createTable('ticket_sales');
    }

    public function down()
    {
        $this->forge->dropTable('lift_tickets');
        $this->forge->dropTable('ticket_sales');
    }
}

