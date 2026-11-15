<?php

namespace App\Database\Migrations;

use App\Libraries\ResortVote as Poll;
use CodeIgniter\Database\Migration;

/**
 * The monthly resort vote (App\Libraries\ResortVote): the North American ski area list players
 * pick from, one round per calendar month, and one ballot per player per round. The
 * `resort_vote` flag starts off. The old Season 4 table `resort_votes` is left as it is.
 */
class MonthlyResortVote extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('vote_ski_areas')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'source_id'   => ['type' => 'VARCHAR', 'constraint' => 64],
                'name'        => ['type' => 'VARCHAR', 'constraint' => 128],
                'country'     => ['type' => 'CHAR', 'constraint' => 2],
                'region_code' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => true],
                'region'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
                'locality'    => ['type' => 'VARCHAR', 'constraint' => 96, 'null' => true],
                'runs'        => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
                'lifts'       => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],
                'vertical_m'  => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
                'top_m'       => ['type' => 'SMALLINT', 'unsigned' => true, 'null' => true],
                'lat'         => ['type' => 'DECIMAL', 'constraint' => '8,5', 'null' => true],
                'lng'         => ['type' => 'DECIMAL', 'constraint' => '8,5', 'null' => true],
                'website'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                // Already playable, or a past winner being built: not offered on the ballot
                'in_game'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
                'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('source_id');
            $this->forge->addKey('name');
            $this->forge->createTable('vote_ski_areas');
        }

        if (!$this->db->tableExists('resort_vote_rounds')) {
            $this->forge->addField([
                'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'month'              => ['type' => 'CHAR', 'constraint' => 7],
                'status'             => ['type' => 'ENUM', 'constraint' => ['open', 'closed'], 'default' => 'open'],
                'closed_at'          => ['type' => 'DATETIME', 'null' => true],
                'winner_area_id'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'winner_note'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'winner_at'          => ['type' => 'DATETIME', 'null' => true],
                'roadmap_item_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'voters_notified_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at'         => ['type' => 'DATETIME', 'null' => true],
                'updated_at'         => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('month');
            $this->forge->createTable('resort_vote_rounds');
        }

        if (!$this->db->tableExists('resort_vote_ballots')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'round_id'   => ['type' => 'INT', 'unsigned' => true],
                'user_id'    => ['type' => 'INT', 'unsigned' => true],
                'area_id'    => ['type' => 'INT', 'unsigned' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['round_id', 'user_id']);
            $this->forge->addKey(['round_id', 'area_id']);
            $this->forge->createTable('resort_vote_ballots');
        }

        if (!$this->db->table('vote_ski_areas')->countAllResults()) {
            Poll::importAreas();
        }

        if (!$this->db->table('feature_flags')->where('flag_key', Poll::FLAG)->countAllResults()) {
            $this->db->table('feature_flags')->insert([
                'flag_key'    => Poll::FLAG,
                'name'        => 'Monthly resort vote',
                'description' => 'Players vote each calendar month on the North American resort to add next (/vote). Results and the winner on /admin/resort-vote',
                'enabled'     => 0,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('feature_flags')->where('flag_key', Poll::FLAG)->delete();
        $this->forge->dropTable('resort_vote_ballots', true);
        $this->forge->dropTable('resort_vote_rounds', true);
        $this->forge->dropTable('vote_ski_areas', true);
    }
}
