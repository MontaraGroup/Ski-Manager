<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddHostToTournaments extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tournaments', [
            'host_id' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'status'],
            'visitors_boost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'host_id'],
            'reputation_boost' => ['type' => 'INT', 'unsigned' => true, 'default' => 0, 'after' => 'visitors_boost'],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tournaments', ['host_id', 'visitors_boost', 'reputation_boost']);
    }
}

