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
            // Only skip creation when the existing table is usable by DatabaseHandler.
            $this->assertCompatibleSchema();

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
        // With DBDebug off in production, Forge returns false instead of throwing;
        // fail loudly so the migration is not recorded as complete without a table.
        if ($this->forge->createTable('ci_sessions', true) === false) {
            throw new \RuntimeException('Failed to create the ci_sessions table: ' . json_encode($this->db->error()));
        }
    }

    /**
     * Ensures an existing ci_sessions table has the columns and primary key
     * that CodeIgniter's DatabaseHandler relies on.
     */
    private function assertCompatibleSchema(): void
    {
        $required = ['id', 'ip_address', 'timestamp', 'data'];
        $missing  = array_diff($required, $this->db->getFieldNames('ci_sessions'));

        if ($missing !== []) {
            throw new \RuntimeException('Existing ci_sessions table is missing required columns: ' . implode(', ', $missing));
        }

        $primaryFields = null;
        foreach ($this->db->getIndexData('ci_sessions') as $index) {
            if (strtoupper((string) $index->type) === 'PRIMARY') {
                $primaryFields = $index->fields;
                break;
            }
        }

        // Session::$matchIP is false, so the primary key must be (id); (id, ip_address) is
        // also accepted so the table keeps working if matchIP is ever enabled.
        $valid = [['id'], ['id', 'ip_address']];
        if ($primaryFields === null || ! in_array(array_values($primaryFields), $valid, true)) {
            throw new \RuntimeException('Existing ci_sessions table has an incompatible primary key: ' . json_encode($primaryFields) . '; expected (id) or (id, ip_address).');
        }
    }

    public function down()
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}
