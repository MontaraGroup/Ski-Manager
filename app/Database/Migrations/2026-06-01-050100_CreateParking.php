<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateParking extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'parking_type' => ['type' => 'ENUM', 'constraint' => ['surface_lot', 'garage', 'shuttle_stop', 'village_gondola'], 'default' => 'surface_lot'],
            'capacity' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'occupied' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'fee_per_day' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'daily_revenue' => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0.00],
            'condition_pct' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 100.00],
            'status' => ['type' => 'ENUM', 'constraint' => ['open', 'closed', 'under_construction', 'full'], 'default' => 'under_construction'],
            'build_days_left' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('parking');
    }

    public function down()
    {
        $this->forge->dropTable('parking');
    }
}
