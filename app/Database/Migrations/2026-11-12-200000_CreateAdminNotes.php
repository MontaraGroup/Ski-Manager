<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Private notes admins keep on a player (shown on /admin/user/{id}) */
class CreateAdminNotes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'admin_id'   => ['type' => 'INT', 'unsigned' => true],
            'note'       => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('admin_notes', true);
    }

    public function down()
    {
        $this->forge->dropTable('admin_notes', true);
    }
}
