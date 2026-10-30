<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Scenic lifts get a real per-ride ticket price (App\Libraries\Scenic). Before, the "price" slider
 * wrote straight into revenue_per_day, so any lift could earn 5,000/day for nothing.
 */
class ScenicTicketPrice extends Migration
{
    public function up()
    {
        if (!in_array('ticket_price', $this->db->getFieldNames('scenic_lifts'), true)) {
            $this->forge->addColumn('scenic_lifts', ['ticket_price' => ['type' => 'INT', 'unsigned' => true, 'null' => false, 'default' => 25, 'after' => 'item_id']]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('scenic_lifts', 'ticket_price');
    }
}
