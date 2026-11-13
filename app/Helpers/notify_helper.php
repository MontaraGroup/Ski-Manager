<?php

if (!function_exists('notify')) {
    /**
     * Adds a notification for one player. Skipped when the same type, title and message is already waiting
     * unread from the last 24 hours, so a repeated alert does not stack up.
     */
    function notify(int $userId, string $type, string $title, string $message, string $icon = 'fa-solid fa-bell', ?string $link = null): void
    {
        $db = db_connect();
        $waiting = $db->table('notifications')->where('user_id', $userId)->where('is_read', 0)
            ->where('created_at >=', date('Y-m-d H:i:s', time() - 86400))
            ->where('type', $type)->where('title', $title)->where('message', $message)->countAllResults();
        if ($waiting > 0) return;

        $db->table('notifications')->insert([
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
