<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSnowQualityAndTours extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Safely check and add snow quality / tour columns if they don't exist yet
        if ($db->tableExists('player_finances') && ! $db->fieldExists('difficulty', 'player_finances')) {
            $this->forge->addColumn('player_finances', [
                'difficulty' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => 'normal']
            ]);
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        if ($db->tableExists('player_finances') && $db->fieldExists('difficulty', 'player_finances')) {
            $this->forge->dropColumn('player_finances', 'difficulty');
        }
    }
}
