<?php

function log_activity(int $userId, string $category, string $message, string $icon = 'fa-solid fa-circle-info'): void
{
    $db = db_connect();
    $startDate = getSeasonStartDate();
    $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

    $db->table('activity_log')->insert([
        'user_id' => $userId,
        'game_day' => $gameDay,
        'category' => $category,
        'message' => $message,
        'icon' => $icon,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
