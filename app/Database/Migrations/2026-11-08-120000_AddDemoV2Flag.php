<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** The `demo_v2` flag: demo rules, first-play and save changes. Starts off. */
class AddDemoV2Flag extends Migration
{
    public function up()
    {
        if (!$this->db->table('feature_flags')->where('flag_key', 'demo_v2')->countAllResults()) {
            $this->db->table('feature_flags')->insert([
                'flag_key'    => 'demo_v2',
                'name'        => 'Demo v2',
                'description' => 'Demo account rules, first-play panel and the save flow. Use 0 or 2: admins are never demo accounts',
                'enabled'     => 0,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('feature_flags')->where('flag_key', 'demo_v2')->delete();
    }
}
