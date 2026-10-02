<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateResortAlliances extends Migration
{
    public function up()
    {
        // 1. Alliances table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'tag' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'motto' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'crest_icon' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'fa-mountain-sun',
            ],
            'crest_color' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => '#3b82f6',
            ],
            'founder_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'level' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'xp' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'treasury_cash' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'max_members' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 5,
            ],
            'min_reputation' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'is_recruiting' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'pass_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'default'    => 'Syndicate Mountain Pass',
            ],
            'pass_tier' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
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
        $this->forge->addKey('name');
        $this->forge->addKey('tag');
        $this->forge->createTable('alliances', true);

        // 2. Alliance Members table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'alliance_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'role' => [
                'type'       => 'ENUM',
                'constraint' => ['founder', 'officer', 'member'],
                'default'    => 'member',
            ],
            'donated_cash' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'cross_skiers_generated' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 0,
            ],
            'joined_at' => [
                'type' => 'DATETIME',
                'null' => true,
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
        $this->forge->addKey(['alliance_id', 'user_id']);
        $this->forge->addKey('user_id', false, true); // unique user constraint
        $this->forge->createTable('alliance_members', true);

        // 3. Unlocked Perks table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'alliance_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'perk_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'perk_level' => [
                'type'     => 'INT',
                'unsigned' => true,
                'default'  => 1,
            ],
            'unlocked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['alliance_id', 'perk_key']);
        $this->forge->createTable('alliance_unlocked_perks', true);

        // 4. Alliance Applications table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'alliance_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'accepted', 'rejected'],
                'default'    => 'pending',
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->addKey(['alliance_id', 'user_id']);
        $this->forge->createTable('alliance_applications', true);

        // 5. Alliance Activity Log table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'alliance_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'message' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('alliance_id');
        $this->forge->createTable('alliance_activity_log', true);
    }

    public function down()
    {
        $this->forge->dropTable('alliance_activity_log', true);
        $this->forge->dropTable('alliance_applications', true);
        $this->forge->dropTable('alliance_unlocked_perks', true);
        $this->forge->dropTable('alliance_members', true);
        $this->forge->dropTable('alliances', true);
    }
}
