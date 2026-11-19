<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Android closed-test reward (App\Libraries\AndroidTesting): one row per player per day the Android
 * app was used, and the players paid for reaching 14 days.
 */
class AndroidTesterRewards extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('android_app_days')) {
            $this->forge->addField([
                'user_id' => ['type' => 'INT', 'unsigned' => true],
                'day'     => ['type' => 'DATE'],
            ]);
            $this->forge->addKey(['user_id', 'day'], true);
            $this->forge->createTable('android_app_days');
        }
        if (!$this->db->tableExists('android_test_rewards')) {
            $this->forge->addField([
                'user_id' => ['type' => 'INT', 'unsigned' => true],
                'paid_at' => ['type' => 'DATETIME'],
            ]);
            $this->forge->addKey('user_id', true);
            $this->forge->createTable('android_test_rewards');
        }
    }

    public function down()
    {
        $this->forge->dropTable('android_test_rewards', true);
        $this->forge->dropTable('android_app_days', true);
    }
}
