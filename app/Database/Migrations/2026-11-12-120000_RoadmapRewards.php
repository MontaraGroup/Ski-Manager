<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Roadmap rewards: a permanent record of which items each player has voted on (kept when the vote
 * is removed, so vote/unvote can't farm rewards), a payout log whose unique key makes a double
 * payment impossible, the "new" dot in the nav, links from shipped items to /updates, and four
 * Community achievements.
 */
class RoadmapRewards extends Migration
{
    private const ACHIEVEMENTS = [
        ['roadmap_first_vote', 'Your Voice Counts', 'Vote on an idea on the roadmap', 'fa-solid fa-check-to-slot', 1, 2000, 100],
        ['roadmap_voter', 'Community Voice', 'Vote on 10 different roadmap ideas', 'fa-solid fa-comments', 10, 10000, 101],
        ['roadmap_idea_accepted', 'Idea Accepted', 'Have an idea you suggested accepted onto the roadmap', 'fa-solid fa-lightbulb', 1, 15000, 102],
        ['roadmap_idea_shipped', 'Idea Shipped', 'Have an idea you suggested shipped in the game', 'fa-solid fa-rocket', 1, 50000, 103],
    ];

    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'        => ['type' => 'INT', 'unsigned' => true],
            'item_id'        => ['type' => 'INT', 'unsigned' => true],
            'first_voted_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'item_id']);
        $this->forge->createTable('roadmap_vote_history', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'kind'       => ['type' => 'VARCHAR', 'constraint' => 32],
            'ref_key'    => ['type' => 'VARCHAR', 'constraint' => 64],
            'item_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'amount'     => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['user_id', 'kind', 'ref_key']);
        $this->forge->createTable('roadmap_rewards', true);

        if (!$this->db->fieldExists('roadmap_seen_at', 'users')) {
            $this->forge->addColumn('users', ['roadmap_seen_at' => ['type' => 'DATETIME', 'null' => true]]);
        }
        if (!$this->db->fieldExists('updates_url', 'roadmap_items')) {
            $this->forge->addColumn('roadmap_items', ['updates_url' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true]]);
        }

        // Players who already voted keep credit for it (achievements only; no Génépis are paid back)
        $this->db->query(
            'INSERT IGNORE INTO roadmap_vote_history (user_id, item_id, first_voted_at)
             SELECT v.user_id, v.item_id, COALESCE(MIN(v.created_at), NOW()) FROM roadmap_votes v
             JOIN users u ON u.id = v.user_id WHERE v.user_id IS NOT NULL AND COALESCE(u.status, \'\') <> \'demo\'
             GROUP BY v.user_id, v.item_id'
        );

        foreach (self::ACHIEVEMENTS as [$key, $name, $desc, $icon, $target, $reward, $sort]) {
            if ($this->db->table('achievement_defs')->where('achievement_key', $key)->countAllResults()) continue;
            $this->db->table('achievement_defs')->insert([
                'achievement_key' => $key, 'name' => $name, 'description' => $desc, 'icon' => $icon,
                'target' => $target, 'reward' => $reward, 'sort_order' => $sort,
            ]);
        }
    }

    public function down()
    {
        $keys = array_column(self::ACHIEVEMENTS, 0);
        $this->db->table('achievements')->whereIn('achievement_key', $keys)->delete();
        $this->db->table('achievement_defs')->whereIn('achievement_key', $keys)->delete();
        if ($this->db->fieldExists('updates_url', 'roadmap_items')) $this->forge->dropColumn('roadmap_items', 'updates_url');
        if ($this->db->fieldExists('roadmap_seen_at', 'users')) $this->forge->dropColumn('users', 'roadmap_seen_at');
        $this->forge->dropTable('roadmap_rewards', true);
        $this->forge->dropTable('roadmap_vote_history', true);
    }
}
