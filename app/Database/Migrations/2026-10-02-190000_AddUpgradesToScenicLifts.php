<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddUpgradesToScenicLifts extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('scenic_lifts') && !$this->db->fieldExists('upgrades', 'scenic_lifts')) {
            $this->forge->addColumn('scenic_lifts', [
                'upgrades' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'revenue_per_day',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('scenic_lifts') && $this->db->fieldExists('upgrades', 'scenic_lifts')) {
            $this->forge->dropColumn('scenic_lifts', 'upgrades');
        }
    }
}
