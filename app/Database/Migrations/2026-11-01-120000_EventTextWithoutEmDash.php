<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The default special events (Tournaments controller) were stored in special_events the first time the
 * tournaments page was opened, so their text still has the em dash the controller no longer uses.
 * This rewrites those stored descriptions to match the controller.
 */
class EventTextWithoutEmDash extends Migration
{
    private const TEXT = [
        'Heavy snowfall expected — prepare your slopes!' => 'Heavy snowfall expected. Prepare your slopes!',
        'A famous skier is visiting — reputation boost!' => 'A famous skier is visiting: reputation boost!',
    ];

    public function up()
    {
        foreach (self::TEXT as $old => $new) {
            $this->db->table('special_events')->where('description', $old)->update(['description' => $new]);
        }
    }

    public function down()
    {
        foreach (self::TEXT as $old => $new) {
            $this->db->table('special_events')->where('description', $new)->update(['description' => $old]);
        }
    }
}
