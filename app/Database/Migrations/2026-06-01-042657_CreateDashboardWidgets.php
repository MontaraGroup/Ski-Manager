<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDashboardWidgets extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'widget_key' => ['type' => 'VARCHAR', 'constraint' => 50],
            'visible' => ['type' => 'TINYINT', 'default' => 1],
            'sort_order' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('dashboard_widgets');
    }

    public function down()
    {
        $this->forge->dropTable('dashboard_widgets');
    }
}

