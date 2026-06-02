<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMarketingAndFinances extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'campaign_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'daily_cost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'visitor_boost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'reputation_boost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'days_remaining' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status' => ['type' => 'ENUM', 'constraint' => ['active', 'paused', 'expired'], 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('marketing_campaigns');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'game_day' => ['type' => 'INT', 'unsigned' => true],
            'category' => ['type' => 'VARCHAR', 'constraint' => 50],
            'description' => ['type' => 'VARCHAR', 'constraint' => 200],
            'amount' => ['type' => 'INT', 'default' => 0],
            'type' => ['type' => 'ENUM', 'constraint' => ['income', 'expense'], 'default' => 'income'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'game_day']);
        $this->forge->createTable('financial_transactions');

        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'cash' => ['type' => 'BIGINT', 'default' => 500000],
            'total_income' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'total_expenses' => ['type' => 'BIGINT', 'unsigned' => true, 'default' => 0],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id', false, true);
        $this->forge->createTable('player_finances');
    }

    public function down()
    {
        $this->forge->dropTable('marketing_campaigns');
        $this->forge->dropTable('financial_transactions');
        $this->forge->dropTable('player_finances');
    }
}

