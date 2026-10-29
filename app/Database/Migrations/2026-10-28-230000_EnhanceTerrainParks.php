<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnhanceTerrainParks extends Migration
{
    public function up()
    {
        // 1. Add daily_revenue and last_event_day to terrain_parks if not already present
        $fields = [];

        if (! $this->db->fieldExists('daily_revenue', 'terrain_parks')) {
            $fields['daily_revenue'] = [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'daily_visitors',
            ];
        }

        if (! $this->db->fieldExists('last_event_day', 'terrain_parks')) {
            $fields['last_event_day'] = [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
                'after'      => 'daily_revenue',
            ];
        }

        if (! empty($fields)) {
            $this->forge->addColumn('terrain_parks', $fields);
        }

        // 2. Update icons in park_feature_types to clean, modern FontAwesome icons
        if ($this->db->tableExists('park_feature_types')) {
            $this->db->table('park_feature_types')
                ->where('type_key', 'halfpipe')
                ->update(['icon' => 'fa-bezier-curve']);

            $this->db->table('park_feature_types')
                ->where('type_key', 'jump_line')
                ->update(['icon' => 'fa-angles-up']);

            $this->db->table('park_feature_types')
                ->where('type_key', 'rail_garden')
                ->update(['icon' => 'fa-bars-staggered']);

            $this->db->table('park_feature_types')
                ->where('type_key', 'slopestyle')
                ->update(['icon' => 'fa-flag-checkered']);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('daily_revenue', 'terrain_parks')) {
            $this->forge->dropColumn('terrain_parks', 'daily_revenue');
        }

        if ($this->db->fieldExists('last_event_day', 'terrain_parks')) {
            $this->forge->dropColumn('terrain_parks', 'last_event_day');
        }
    }
}
