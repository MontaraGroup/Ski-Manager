<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Android closed test (App\Libraries\AndroidTesting): access codes generated in Play Console, pasted
 * in by an admin, and handed out one per player from /beta/android.
 */
class AndroidTesterCodes extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('android_tester_codes')) return;
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 64],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'claimed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        // One code per player; NULLs (unclaimed codes) don't clash
        $this->forge->addUniqueKey('user_id');
        $this->forge->createTable('android_tester_codes');
    }

    public function down()
    {
        $this->forge->dropTable('android_tester_codes', true);
    }
}
