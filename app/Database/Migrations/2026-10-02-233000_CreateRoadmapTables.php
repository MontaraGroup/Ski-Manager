<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoadmapTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('roadmap_items')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                ],
                'description' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 32,
                    'default'    => 'planned', // planned, in_progress, completed
                ],
                'category' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'default'    => 'gameplay', // gameplay, quality_of_life, economy, mobile
                ],
                'upvotes' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'author_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'default'    => 'Ski Manager Team',
                ],
                'author_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
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
            $this->forge->addKey('status');
            $this->forge->addKey('category');
            $this->forge->addKey('upvotes');
            $this->forge->createTable('roadmap_items', true);
        }

        if (!$this->db->tableExists('roadmap_votes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'item_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'voter_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['item_id', 'voter_hash']);
            $this->forge->addKey('item_id');
            $this->forge->createTable('roadmap_votes', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('roadmap_votes', true);
        $this->forge->dropTable('roadmap_items', true);
    }
}
