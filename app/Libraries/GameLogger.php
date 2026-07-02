<?php

namespace App\Libraries;

use Config\Database;

class GameLogger
{
    public static function log(string $category, string $message, ?int $day = null, ?int $userId = null)
    {
        $db = Database::connect();
        $session = session();

        $finalUserId   = $userId ?? $session->get('user_id') ?? null;
        $finalUsername = 'System';

        if ($finalUserId) {
            $user = $db->table('users')->select('username')->where('id', $finalUserId)->get()->getRow();
            if ($user) {
                $finalUsername = $user->username;
            }
        }

        if ($day === null) {
            $gameConfig = $db->table('game_settings')->select('current_day')->get()->getRow();
            $day = $gameConfig ? $gameConfig->current_day : 0;
        }

        $db->table('activity_log')->insert([
            'user_id'    => $finalUserId,
            'username'   => $finalUsername,
            'game_day'   => $day,
            'category'   => ucfirst($category),
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
