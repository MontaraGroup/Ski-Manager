<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Session storage table for CodeIgniter's DatabaseHandler.
 *
 * File sessions raced with garbage collection / session destroy: a file could be
 * unlinked between FileHandler::read()'s fopen() and filesize(), raising
 * "filesize(): stat failed" (SKI-MANAGER-9). Rows + MySQL GET_LOCK avoid that.
 */
class CreateCiSessions extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('ci_sessions')) {
            return;
        }

        $this->forge->addField([
            'id'         => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => false],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => false],
            'timestamp'  => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP')],
            'data'       => ['type' => 'BLOB', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions', true);
    }

    public function down()
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}
