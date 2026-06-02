<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateScenicLifts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'item_id' => ['type' => 'INT', 'unsigned' => true],
            'revenue_per_day' => ['type' => 'INT', 'unsigned' => true, 'default' => 1500],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'item_id']);
        $this->forge->createTable('scenic_lifts');
    }

    public function down()
    {
        $this->forge->dropTable('scenic_lifts');
    }
}

