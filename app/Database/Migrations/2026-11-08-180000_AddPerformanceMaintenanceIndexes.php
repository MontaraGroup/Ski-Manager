<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPerformanceMaintenanceIndexes extends Migration
{
    public function up()
    {
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

        // 1. Composite index for user notification badge and list queries
        $addIndexIfMissing('notifications', 'idx_notif_user_read_created', '`user_id`, `is_read`, `created_at`');

        // 2. Retention pruning indexes for daily GameTick maintenance
        $addIndexIfMissing('ticket_sales', 'idx_ts_created_at', '`created_at`');
        $addIndexIfMissing('financial_transactions', 'idx_ft_created_at', '`created_at`');
        $addIndexIfMissing('notifications', 'idx_notif_read_created', '`is_read`, `created_at`');
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

        $dropIndexIfExists('notifications', 'idx_notif_user_read_created');
        $dropIndexIfExists('ticket_sales', 'idx_ts_created_at');
        $dropIndexIfExists('financial_transactions', 'idx_ft_created_at');
        $dropIndexIfExists('notifications', 'idx_notif_read_created');
    }
}
