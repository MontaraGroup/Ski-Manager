<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Snowmaking revamp (App\Libraries\Snowmaking): made snow builds up per slope towards a target base,
 * guns run in auto or max mode and can carry upgrades. Also fixes the HKD catalog water figures
 * (ten times every other brand) and drops the unused snow_cannons table.
 */
class SnowmakingRevamp extends Migration
{
    public function up()
    {
        $items = $this->db->getFieldNames('player_items');
        $add = [];
        if (!in_array('made_snow_cm', $items, true)) {
            $add['made_snow_cm'] = ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false, 'default' => 0, 'after' => 'snow_depth'];
        }
        if (!in_array('snow_target_cm', $items, true)) {
            $add['snow_target_cm'] = ['type' => 'SMALLINT', 'unsigned' => true, 'null' => false, 'default' => 30, 'after' => 'made_snow_cm'];
        }
        if ($add) $this->forge->addColumn('player_items', $add);

        $equipment = $this->db->getFieldNames('equipment');
        $add = [];
        if (!in_array('snow_mode', $equipment, true)) {
            $add['snow_mode'] = ['type' => 'ENUM', 'constraint' => ['auto', 'max'], 'null' => false, 'default' => 'auto', 'after' => 'status'];
        }
        if (!in_array('upgrades', $equipment, true)) {
            $add['upgrades'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'snow_mode'];
        }
        if ($add) $this->forge->addColumn('equipment', $add);

        // HKD guns were entered at ten times the water of every other brand
        $this->db->query("UPDATE equipment_catalog SET water_liters = water_liters DIV 10 WHERE equipment_type = 'snowmaker' AND brand = 'HKD' AND water_liters >= 20000");
        $this->db->query("UPDATE equipment SET water_liters = water_liters DIV 10 WHERE equipment_type = 'snowmaker' AND brand = 'HKD' AND water_liters >= 20000");

        // Replaced by equipment rows long ago; never written to
        if ($this->db->tableExists('snow_cannons') && $this->db->table('snow_cannons')->countAllResults() === 0) {
            $this->forge->dropTable('snow_cannons', true);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('player_items', ['made_snow_cm', 'snow_target_cm']);
        $this->forge->dropColumn('equipment', ['snow_mode', 'upgrades']);
        $this->db->query("UPDATE equipment_catalog SET water_liters = water_liters * 10 WHERE equipment_type = 'snowmaker' AND brand = 'HKD' AND water_liters < 20000");
        $this->db->query("UPDATE equipment SET water_liters = water_liters * 10 WHERE equipment_type = 'snowmaker' AND brand = 'HKD' AND water_liters < 20000");
        // snow_cannons is not recreated: nothing used it (see 2026-05-31-213244_CreateSnowmaking)
    }
}
