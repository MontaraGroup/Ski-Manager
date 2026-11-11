<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The beta program (App\Libraries\Beta): flag level 3 means admins plus beta members.
 * - feature_flags.beta_since: when the flag last moved to the beta level, shown on /beta
 * - beta_members: who joined
 * - beta_feature_optouts: beta features a member switched off (members start with every one on)
 * - beta_feedback: one rating and comment per player per feature, editable
 * No existing flag changes level.
 */
class BetaProgram extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('beta_since', 'feature_flags')) {
            $this->forge->addColumn('feature_flags', [
                'beta_since' => ['type' => 'DATETIME', 'null' => true, 'after' => 'enabled'],
            ]);
        }

        if (!$this->db->tableExists('beta_members')) {
            $this->forge->addField([
                'user_id'   => ['type' => 'INT', 'unsigned' => true],
                'joined_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('user_id', true);
            $this->forge->createTable('beta_members');
        }

        if (!$this->db->tableExists('beta_feature_optouts')) {
            $this->forge->addField([
                'user_id'    => ['type' => 'INT', 'unsigned' => true],
                'flag_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey(['user_id', 'flag_key'], true);
            $this->forge->createTable('beta_feature_optouts');
        }

        if (!$this->db->tableExists('beta_feedback')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'user_id'    => ['type' => 'INT', 'unsigned' => true],
                'flag_key'   => ['type' => 'VARCHAR', 'constraint' => 50],
                'rating'     => ['type' => 'TINYINT', 'unsigned' => true],
                'comment'    => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['user_id', 'flag_key']);
            $this->forge->addKey('flag_key');
            $this->forge->createTable('beta_feedback');
        }
    }

    public function down()
    {
        $this->forge->dropTable('beta_feedback', true);
        $this->forge->dropTable('beta_feature_optouts', true);
        $this->forge->dropTable('beta_members', true);
        if ($this->db->fieldExists('beta_since', 'feature_flags')) {
            $this->forge->dropColumn('feature_flags', 'beta_since');
        }
    }
}
