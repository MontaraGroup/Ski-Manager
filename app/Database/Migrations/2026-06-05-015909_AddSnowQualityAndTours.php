<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnowQualityAndTours extends Migration
{
    public function up()
    {
        // Snow quality on slopes
        $this->forge->addColumn('player_items', [
            'snow_quality' => ['type' => 'ENUM', 'constraint' => ['powder', 'groomed', 'packed', 'icy', 'bare'], 'default' => 'packed', 'after' => 'difficulty'],
        ]);

        // Resort tours opt-in setting
        $this->forge->addColumn('player_finances', [
            'allow_tours' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1, 'after' => 'difficulty'],
        ]);

        // Resort likes
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'resort_user_id' => ['type' => 'INT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'resort_user_id']);
        $this->forge->addKey('resort_user_id');
        $this->forge->createTable('resort_likes');
    }

    public function down()
    {
        $this->forge->dropColumn('player_items', 'snow_quality');
        $this->forge->dropColumn('player_finances', 'allow_tours');
        $this->forge->dropTable('resort_likes');
    }
}
