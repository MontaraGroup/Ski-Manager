<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSeasonLeaderboardArchives extends Migration
{
    public function up()
    {
        // 1. Create season_leaderboard_archives table
        if (!$this->db->tableExists('season_leaderboard_archives')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'season_number' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'season_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'username' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                ],
                'resort_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'rank_position' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'cash' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '14,2',
                    'default'    => 0.00,
                ],
                'reputation' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'total_slopes' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'total_lifts' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                ],
                'reward_badge' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'achieved_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['season_number', 'rank_position']);
            $this->forge->addKey('user_id');
            $this->forge->createTable('season_leaderboard_archives', true);
        }

        // 2. Ensure Season 2 exists in seasons table
        if ($this->db->tableExists('seasons')) {
            $season2 = $this->db->table('seasons')->where('season_number', 2)->get()->getRowArray();
            if (!$season2) {
                $this->db->table('seasons')->insert([
                    'season_number' => 2,
                    'name'          => 'Season 2: Park City Expansion',
                    'resort_map'    => 'ParkCity',
                    'start_date'    => '2026-10-19',
                    'duration_days' => 135,
                    'winter_days'   => 100,
                    'active'        => 0,
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 3. Ensure Sector 2 exists in resort_sectors for Park City
        if ($this->db->tableExists('resort_sectors')) {
            $sector2 = $this->db->table('resort_sectors')
                ->where('resort_map', 'ParkCity')
                ->groupStart()
                    ->where('name', 'Sector 2')
                    ->orWhere('name', 'Sector 2 (Advanced Peaks)')
                ->groupEnd()
                ->get()->getRowArray();

            if (!$sector2) {
                $this->db->table('resort_sectors')->insert([
                    'resort_map'      => 'ParkCity',
                    'name'            => 'Sector 2 (Advanced Peaks)',
                    'description'     => 'High-altitude ridges, steep bowls, and express gondola terrain.',
                    'color'           => '#f59e0b',
                    'boundary_points' => json_encode([
                        [140, 160], [165, 175], [195, 220], [215, 270],
                        [190, 310], [150, 330], [120, 290], [130, 210]
                    ]),
                    'visible'         => 1,
                    'released'        => 0,
                    'sort_order'      => 2,
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // 4. Ensure Season 1 Pioneer achievement exists in achievement_defs
        if ($this->db->tableExists('achievement_defs')) {
            $pioneerDef = $this->db->table('achievement_defs')
                ->where('achievement_key', 'season_1_pioneer')
                ->get()->getRowArray();
            if (!$pioneerDef) {
                $this->db->table('achievement_defs')->insert([
                    'achievement_key' => 'season_1_pioneer',
                    'name'            => 'Season 1 Pioneer',
                    'description'     => 'Participated as an active ski resort manager during inaugural Season 1 at Park City.',
                    'icon'            => 'fa-solid fa-medal text-warning',
                    'target'          => 1,
                    'reward'          => 10000,
                    'sort_order'      => 99,
                    'unlocks'         => 'badge',
                    'unlock_label'    => 'Season 1 Pioneer Badge',
                ]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('season_leaderboard_archives', true);
    }
}
