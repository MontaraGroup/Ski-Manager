<?php

function log_activity(int $userId, string $category, string $message, string $icon = 'fa-solid fa-circle-info'): void
{
    $db = db_connect();
    $startDate = getSeasonStartDate();
    $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

    // Resolve username dynamically for the master admin log layout
    $username = 'System';
    if ($userId > 0) {
        $user = function_exists('auth') ? auth()->getProvider()->findById($userId) : null;
        if ($user) {
            $username = $user->username;
        }
    }

    $insertData = [
        'user_id'    => $userId,
        'game_day'   => $gameDay,
        'category'   => $category,
        'message'    => $message,
        'icon'       => $icon,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    // Safely add username only if the structural schema column exists
    if ($db->fieldExists('username', 'activity_log')) {
        $insertData['username'] = $username;
    }

    $db->table('activity_log')->insert($insertData);
}
