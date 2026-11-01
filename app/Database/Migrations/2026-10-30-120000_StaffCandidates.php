<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Hiring candidates: each player sees a few named candidates per role, refreshed every game day */
class StaffCandidates extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('staff_candidates')) return;
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'role' => ['type' => 'VARCHAR', 'constraint' => 50],
            'name' => ['type' => 'VARCHAR', 'constraint' => 100],
            'level' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'salary' => ['type' => 'INT', 'unsigned' => true],
            'morale' => ['type' => 'TINYINT', 'unsigned' => true],
            'trait' => ['type' => 'VARCHAR', 'constraint' => 30],
            'game_day' => ['type' => 'INT', 'unsigned' => true],
            'hired' => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'game_day']);
        $this->forge->createTable('staff_candidates');
    }

    public function down()
    {
        $this->forge->dropTable('staff_candidates', true);
    }
}
