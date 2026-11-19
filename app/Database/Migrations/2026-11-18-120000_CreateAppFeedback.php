<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Feedback sent from the Android app (App\Libraries\AppFeedback). The message also goes into the
 * player's support thread; this keeps the category, the diagnostics the player ticked and the
 * screenshot file name. Diagnostics and screenshots are deleted after 90 days.
 */
class CreateAppFeedback extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'INT', 'unsigned' => true],
            'category'    => ['type' => 'VARCHAR', 'constraint' => 10],
            'message'     => ['type' => 'TEXT'],
            'app_version' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'device'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'diagnostics' => ['type' => 'MEDIUMTEXT', 'null' => true],
            'screenshot'  => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'status'      => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'new'],
            'created_at'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->addKey('status');
        $this->forge->createTable('app_feedback', true);
    }

    public function down()
    {
        $this->forge->dropTable('app_feedback', true);
    }
}
