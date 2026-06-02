<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateWeather extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'game_day' => ['type' => 'INT', 'unsigned' => true, 'default' => 1],
            'temp' => ['type' => 'INT', 'default' => 0],
            'condition_name' => ['type' => 'VARCHAR', 'constraint' => 50],
            'wind' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'snowfall' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'visibility' => ['type' => 'VARCHAR', 'constraint' => 20],
            'humidity' => ['type' => 'INT', 'unsigned' => true, 'default' => 50],
            'snow_base' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'forecast' => ['type' => 'JSON', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('game_day');
        $this->forge->createTable('weather');
    }

    public function down()
    {
        $this->forge->dropTable('weather');
    }
}

