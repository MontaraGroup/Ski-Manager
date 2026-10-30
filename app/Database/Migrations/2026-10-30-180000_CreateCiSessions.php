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

        // MySQLi runs with strict mode off here, so undersized columns would silently
        // truncate session data instead of failing; reject them up front.
        $fieldData = [];
        foreach ($this->db->getFieldData('ci_sessions') as $field) {
            $fieldData[$field->name] = $field;
        }

        // MySQLi reports SHOW COLUMNS base types (e.g. "mediumblob", not "medium_blob").
        $expectedTypes = [
            'id'         => ['char', 'varchar'],
            'ip_address' => ['char', 'varchar'],
            'timestamp'  => ['timestamp', 'datetime'],
            'data'       => ['blob', 'mediumblob', 'longblob'],
        ];
        $minimumLengths = ['id' => 128, 'ip_address' => 45];
        $incompatible   = [];

        foreach ($expectedTypes as $name => $types) {
            $field = $fieldData[$name];
            $type  = strtolower((string) $field->type);

            if (
                ! in_array($type, $types, true)
                || (isset($minimumLengths[$name]) && (int) $field->max_length < $minimumLengths[$name])
            ) {
                $incompatible[$name] = [
                    'type'       => $field->type,
                    'max_length' => $field->max_length,
                ];
            }
        }

        if ($incompatible !== []) {
            throw new \RuntimeException('Existing ci_sessions table has incompatible column definitions: ' . json_encode($incompatible));
        }

        $primaryFields = null;
        foreach ($this->db->getIndexData('ci_sessions') as $index) {
            if (strtoupper((string) $index->type) === 'PRIMARY') {
                $primaryFields = $index->fields;
                break;
            }
        }

        // Session::$matchIP is false, so DatabaseHandler queries by id alone and id must be unique.
        if ($primaryFields === null || array_values($primaryFields) !== ['id']) {
            throw new \RuntimeException('Existing ci_sessions table has an incompatible primary key: ' . json_encode($primaryFields) . '; expected (id).');
        }
    }

    public function down()
    {
        $this->forge->dropTable('ci_sessions', true);
    }
}
