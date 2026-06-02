<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExtendedSystems extends Migration
{
    public function up()
    {
        // Loans / Bank
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'loan_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'principal' => ['type' => 'INT', 'unsigned' => true],
            'interest_rate' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
            'remaining' => ['type' => 'INT', 'unsigned' => true],
            'daily_payment' => ['type' => 'INT', 'unsigned' => true],
            'days_total' => ['type' => 'INT', 'unsigned' => true],
            'days_remaining' => ['type' => 'INT', 'unsigned' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paid', 'defaulted'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('loans');

        // Government regulations
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'regulation_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'compliance_cost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'penalty_risk' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'compliant' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('regulations');

        // Environmental factors
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'eco_score' => ['type' => 'INT', 'unsigned' => true, 'default' => 50],
            'carbon_output' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'renewable_pct' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'waste_management' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'wildlife_impact' => ['type' => 'INT', 'unsigned' => true, 'default' => 50],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('environmental');
    }

    public function down()
    {
        $this->forge->dropTable('loans');
        $this->forge->dropTable('regulations');
        $this->forge->dropTable('environmental');
    }
}

