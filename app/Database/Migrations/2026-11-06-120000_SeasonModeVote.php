<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The season format vote (App\Libraries\SeasonVote). One row per account: the choice, an optional
 * comment, and when "Remind me later" hides the popup until. The `season_mode_vote` flag starts
 * off, so nobody sees the vote until an admin switches it on and sets a close date.
 */
class SeasonModeVote extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('season_mode_votes')) {
            $this->forge->addField([
                'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'user_id'       => ['type' => 'INT', 'unsigned' => true],
                'choice'        => ['type' => 'ENUM', 'constraint' => ['shared', 'individual'], 'null' => true],
                'comment'       => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'snoozed_until' => ['type' => 'DATETIME', 'null' => true],
                'created_at'    => ['type' => 'DATETIME', 'null' => true],
                'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('user_id');
            $this->forge->createTable('season_mode_votes');
        }

        if (!$this->db->table('feature_flags')->where('flag_key', 'season_mode_vote')->countAllResults()) {
            $this->db->table('feature_flags')->insert([
                'flag_key'    => 'season_mode_vote',
                'name'        => 'Season format vote',
                'description' => 'Popup asking players to choose between the shared season and their own pace. Needs a close date on /admin/season-vote',
                'enabled'     => 0,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('feature_flags')->where('flag_key', 'season_mode_vote')->delete();
        $this->forge->dropTable('season_mode_votes', true);
    }
}
