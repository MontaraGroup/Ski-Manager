<?php

if (!function_exists('timeAgo')) {
    function timeAgo(string $datetime): string
    {
        $now = time();
        $then = strtotime($datetime);
        $diff = $now - $then;

        if ($diff < 60) return 'just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M j', $then);
    }
}
