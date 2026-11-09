<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Demos started and saved per day. No player data, and the counts outlive the cleanup of old demos. */
class DemoStats extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('demo_stats')) return;
        $this->forge->addField([
            'day'     => ['type' => 'DATE'],
            'started' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'claimed' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
        ]);
        $this->forge->addKey('day', true);
        $this->forge->createTable('demo_stats');
    }

    public function down()
    {
        $this->forge->dropTable('demo_stats', true);
    }
}
