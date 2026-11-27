<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Other winter sport facilities (ice rink, ice hockey venue, ski jump ramp, bobsleigh track):
 * additional guest/spectator income, upgrades, and tournament event requirements.
 */
class WinterSportFacilities extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('winter_sport_facilities')) {
            return;
        }

        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],
            'facility_type'  => ['type' => 'VARCHAR', 'constraint' => 32],
            'name'           => ['type' => 'VARCHAR', 'constraint' => 100],
            'level'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 1],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'open'],
            'condition_pct'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 100],
            'ticket_price'   => ['type' => 'INT', 'unsigned' => true, 'default' => 20],
            'daily_revenue'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'daily_upkeep'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'total_revenue'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey(['user_id', 'facility_type']);
        $this->forge->createTable('winter_sport_facilities', true);
    }

    public function down()
    {
        $this->forge->dropTable('winter_sport_facilities', true);
    }
}
