<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class OptimizePerformanceAndIndexes extends Migration
{
    public function up()
    {
        // 1. Ensure required columns on player_finances
        if ($this->db->tableExists('player_finances')) {
            $colsToAdd = [];
            if (!$this->db->fieldExists('reputation', 'player_finances')) {
                $colsToAdd['reputation'] = ['type' => 'INT', 'default' => 0];
            }
            if (!$this->db->fieldExists('resort_map', 'player_finances')) {
                $colsToAdd['resort_map'] = ['type' => 'VARCHAR', 'constraint' => 64, 'default' => 'ParkCity'];
            }
            if (!$this->db->fieldExists('resort_name', 'player_finances')) {
                $colsToAdd['resort_name'] = ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true];
            }
            if (!$this->db->fieldExists('profile_completed', 'player_finances')) {
                $colsToAdd['profile_completed'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0];
            }
            if (!$this->db->fieldExists('last_active', 'player_finances')) {
                $colsToAdd['last_active'] = ['type' => 'DATETIME', 'null' => true];
            }
            if (!$this->db->fieldExists('daily_visitors', 'player_finances')) {
                $colsToAdd['daily_visitors'] = ['type' => 'INT', 'default' => 0];
            }
            if (!empty($colsToAdd)) {
                $this->forge->addColumn('player_finances', $colsToAdd);
            }
        }

        // Helper to safely add index if not already present
        $addIndexIfMissing = function (string $table, string $indexName, string $columnsSql) {
            if (!$this->db->tableExists($table)) {
                return;
            }
            try {
                $check = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])->getResultArray();
                if (empty($check)) {
                    $this->db->query("ALTER TABLE `{$table}` ADD INDEX `{$indexName}` ({$columnsSql})");
                }
            } catch (\Throwable $e) {
                // Ignore index creation failure if already exists or db driver differs
            }
        };

        // 2. High-performance composite indexes
        $addIndexIfMissing('player_items', 'idx_pi_user_type_status', '`user_id`, `item_type`, `status`');
        $addIndexIfMissing('equipment', 'idx_eq_user_type_status', '`user_id`, `equipment_type`, `status`');
        $addIndexIfMissing('staff', 'idx_staff_user_status', '`user_id`, `status`');
        $addIndexIfMissing('activity_log', 'idx_act_user_created', '`user_id`, `created_at`');
        $addIndexIfMissing('activity_log', 'idx_act_created', '`created_at`');
        $addIndexIfMissing('financial_transactions', 'idx_ft_user_created', '`user_id`, `created_at`');
        $addIndexIfMissing('dashboard_widgets', 'idx_dw_user_sort', '`user_id`, `sort_order`');
        $addIndexIfMissing('loans', 'idx_loans_user_status', '`user_id`, `status`');
        $addIndexIfMissing('buildings', 'idx_bld_user_type_status', '`user_id`, `building_type`, `status`');
        $addIndexIfMissing('player_boosts', 'idx_pb_user_boost_exp', '`user_id`, `boost_type`, `expires_at`');
    }

    public function down()
    {
        $dropIndexIfExists = function (string $table, string $indexName) {
            if (!$this->db->tableExists($table)) {
                return;
            }
            try {
                $check = $this->db->query("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName])->getResultArray();
                if (!empty($check)) {
                    $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
                }
            } catch (\Throwable $e) {
            }
        };

        $dropIndexIfExists('player_items', 'idx_pi_user_type_status');
        $dropIndexIfExists('equipment', 'idx_eq_user_type_status');
        $dropIndexIfExists('staff', 'idx_staff_user_status');
        $dropIndexIfExists('activity_log', 'idx_act_user_created');
        $dropIndexIfExists('activity_log', 'idx_act_created');
        $dropIndexIfExists('financial_transactions', 'idx_ft_user_created');
        $dropIndexIfExists('dashboard_widgets', 'idx_dw_user_sort');
        $dropIndexIfExists('loans', 'idx_loans_user_status');
        $dropIndexIfExists('buildings', 'idx_bld_user_type_status');
        $dropIndexIfExists('player_boosts', 'idx_pb_user_boost_exp');
    }
}
