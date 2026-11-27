<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Detailed winter sport activities: add-on upgrades (floodlights, rental booth, concessions,
 * pro surface), operating session mode, surface/ice quality, and daily revenue breakdown.
 */
class WinterSportsDetail extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('winter_sport_facilities')) {
            return;
        }
        $fields = $this->db->getFieldNames('winter_sport_facilities');
        $add = [];
        if (!in_array('upgrades', $fields, true)) {
            $add['upgrades'] = ['type' => 'TEXT', 'null' => true, 'after' => 'condition_pct'];
        }
        if (!in_array('session_mode', $fields, true)) {
            $add['session_mode'] = ['type' => 'VARCHAR', 'constraint' => 24, 'default' => 'standard', 'after' => 'upgrades'];
        }
        if (!in_array('ice_quality', $fields, true)) {
            $add['ice_quality'] = ['type' => 'TINYINT', 'unsigned' => true, 'default' => 90, 'after' => 'session_mode'];
        }
        if (!in_array('last_guests', $fields, true)) {
            $add['last_guests'] = ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'daily_upkeep'];
        }
        if (!in_array('last_ticket_rev', $fields, true)) {
            $add['last_ticket_rev'] = ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'last_guests'];
        }
        if (!in_array('last_rental_rev', $fields, true)) {
            $add['last_rental_rev'] = ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'last_ticket_rev'];
        }
        if ($add) {
            $this->forge->addColumn('winter_sport_facilities', $add);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('winter_sport_facilities')) {
            return;
        }
        $fields = $this->db->getFieldNames('winter_sport_facilities');
        foreach (['last_rental_rev', 'last_ticket_rev', 'last_guests', 'ice_quality', 'session_mode', 'upgrades'] as $col) {
            if (in_array($col, $fields, true)) {
                $this->forge->dropColumn('winter_sport_facilities', $col);
            }
        }
    }
}
