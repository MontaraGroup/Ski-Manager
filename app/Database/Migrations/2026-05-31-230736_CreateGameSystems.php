<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateGameSystems extends Migration
{
    public function up()
    {
        // Insurance policies
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'policy_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'premium_per_day' => ['type' => 'INT', 'unsigned' => true],
            'coverage_amount' => ['type' => 'INT', 'unsigned' => true],
            'active' => ['type' => 'TINYINT', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('insurance');

        // Activity log
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'game_day' => ['type' => 'INT', 'unsigned' => true],
            'category' => ['type' => 'VARCHAR', 'constraint' => 50],
            'message' => ['type' => 'VARCHAR', 'constraint' => 255],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 50, 'default' => 'fa-solid fa-circle-info'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'game_day']);
        $this->forge->createTable('activity_log');

        // Achievements
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'achievement_key' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 200],
            'icon' => ['type' => 'VARCHAR', 'constraint' => 50],
            'progress' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'target' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'reward_amount' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'completed' => ['type' => 'TINYINT', 'default' => 0],
            'claimed' => ['type' => 'TINYINT', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('achievements');

        // Daily login bonus
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'last_claim_day' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'streak' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'total_claimed' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id', false, true);
        $this->forge->createTable('daily_bonus');

        // Tournaments
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'start_day' => ['type' => 'INT', 'unsigned' => true],
            'end_day' => ['type' => 'INT', 'unsigned' => true],
            'prize_pool' => ['type' => 'INT', 'unsigned' => true],
            'metric' => ['type' => 'VARCHAR', 'constraint' => 50],
            'status' => ['type' => 'ENUM', 'constraint' => ['upcoming', 'active', 'ended'], 'default' => 'upcoming'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tournaments');

        // Special events
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'description' => ['type' => 'VARCHAR', 'constraint' => 255],
            'event_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'effect_type' => ['type' => 'VARCHAR', 'constraint' => 50],
            'effect_value' => ['type' => 'INT', 'default' => 0],
            'game_day' => ['type' => 'INT', 'unsigned' => true],
            'duration_days' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'active' => ['type' => 'TINYINT', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('special_events');
    }

    public function down()
    {
        $this->forge->dropTable('insurance');
        $this->forge->dropTable('activity_log');
        $this->forge->dropTable('achievements');
        $this->forge->dropTable('daily_bonus');
        $this->forge->dropTable('tournaments');
        $this->forge->dropTable('special_events');
    }
}

