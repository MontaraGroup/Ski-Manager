<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Player comments on roadmap ideas. A comment belongs to the idea it was written on; a main idea's
 * thread shows its own comments and those of the ideas merged into it. updated_at is set only when
 * the author edits the comment, so a non-null value means "edited".
 */
class CreateRoadmapComments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'item_id'    => ['type' => 'INT', 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'body'       => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME'],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['item_id', 'created_at']);
        $this->forge->addKey(['user_id', 'created_at']);
        $this->forge->createTable('roadmap_comments', true);
    }

    public function down()
    {
        $this->forge->dropTable('roadmap_comments', true);
    }
}
