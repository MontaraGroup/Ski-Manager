<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Roadmap idea grouping: an admin merges duplicate ideas into a main one. merged_into points at the
 * main idea (one level only), premerge_status is the status to restore when the idea is split out
 * again. Vote rows stay on the idea they were cast for, so splitting restores the exact counts.
 */
class RoadmapMergeIdeas extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('merged_into', 'roadmap_items')) {
            $this->forge->addColumn('roadmap_items', [
                'merged_into'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
                'premerge_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'merged_at'       => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->db->query('ALTER TABLE roadmap_items ADD INDEX idx_roadmap_merged_into (merged_into)');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('merged_into', 'roadmap_items')) {
            // Merged ideas come back as separate ideas with the status they had before the merge
            $this->db->query('UPDATE roadmap_items SET status = COALESCE(premerge_status, status) WHERE merged_into IS NOT NULL');
            $this->db->query('ALTER TABLE roadmap_items DROP INDEX idx_roadmap_merged_into');
            $this->forge->dropColumn('roadmap_items', ['merged_into', 'premerge_status', 'merged_at']);
        }
    }
}
