<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Login streak save (one missed day forgiven per season) and 100-day streak milestone badge.
 */
class LoginStreakSaveAndMilestones extends Migration
{
    private const LOGIN_100 = [
        'achievement_key' => 'login_100',
        'name' => '100-Day Streak',
        'description' => 'Claim your daily login bonus 100 days in a row',
        'icon' => 'fa-solid fa-medal',
        'target' => 100,
        'reward' => 100000,
        'sort_order' => 82,
    ];

    public function up()
    {
        if ($this->db->tableExists('daily_bonus')) {
            $fields = $this->db->getFieldNames('daily_bonus');
            if (!in_array('save_used_season', $fields, true)) {
                $this->forge->addColumn('daily_bonus', [
                    'save_used_season' => [
                        'type' => 'INT',
                        'unsigned' => true,
                        'null' => false,
                        'default' => 0,
                        'after' => 'best_streak',
                    ],
                ]);
            }
            if (!in_array('save_used_date', $fields, true)) {
                $this->forge->addColumn('daily_bonus', [
                    'save_used_date' => [
                        'type' => 'DATE',
                        'null' => true,
                        'default' => null,
                        'after' => 'save_used_season',
                    ],
                ]);
            }
        }

        if ($this->db->tableExists('achievement_defs')) {
            $exists = $this->db->table('achievement_defs')
                ->where('achievement_key', self::LOGIN_100['achievement_key'])
                ->countAllResults();
            if (!$exists) {
                $this->db->table('achievement_defs')->insert(self::LOGIN_100);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('achievements')) {
            $this->db->table('achievements')->where('achievement_key', self::LOGIN_100['achievement_key'])->delete();
        }
        if ($this->db->tableExists('achievement_defs')) {
            $this->db->table('achievement_defs')->where('achievement_key', self::LOGIN_100['achievement_key'])->delete();
        }
        if ($this->db->tableExists('daily_bonus')) {
            $fields = $this->db->getFieldNames('daily_bonus');
            if (in_array('save_used_date', $fields, true)) {
                $this->forge->dropColumn('daily_bonus', 'save_used_date');
            }
            if (in_array('save_used_season', $fields, true)) {
                $this->forge->dropColumn('daily_bonus', 'save_used_season');
            }
        }
    }
}
