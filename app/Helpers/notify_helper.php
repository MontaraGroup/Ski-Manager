<?php

if (!function_exists('notify')) {
    function notify(int $userId, string $type, string $title, string $message, string $icon = 'fa-solid fa-bell', ?string $link = null): void
    {
        db_connect()->table('notifications')->insert([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'icon' => $icon,
            'link' => $link,
        ]);
    }
}

if (!function_exists('unreadNotificationCount')) {
    function unreadNotificationCount(int $userId): int
    {
        return db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 0)->countAllResults();
    }
}
