<?php

namespace App\Libraries;

use Config\Database;

class GameLogger
{
    public static function log(string $category, string $message, ?int $day = null, ?int $userId = null, string $icon = 'fa-solid fa-circle-info')
    {
        $db = Database::connect();
        $finalUserId = $userId ?? auth()->id() ?? null;

        if ($day === null) {
            $startDate = function_exists('getSeasonStartDate') ? getSeasonStartDate() : date('Y-m-d');
            $day = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);
        }

        if ($finalUserId) {
            $db->table('activity_log')->insert([
                'user_id'    => $finalUserId,
                'game_day'   => $day,
                'category'   => ucfirst($category),
                'message'    => $message,
                'icon'       => $icon,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
