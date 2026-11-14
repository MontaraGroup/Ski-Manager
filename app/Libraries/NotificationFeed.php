<?php

namespace App\Libraries;

/**
 * Reading side of the notifications table: categories, links and the item shape shared by the
 * /notifications page, the top-bar bell and the mobile API (Api\V1\App\Notifications).
 * Rows are written by notify() in app/Helpers/notify_helper.php.
 */
class NotificationFeed
{
    public const PAGE_SIZE = 30;

    /** category => label; the order is the order of the filter chips */
    public const CATEGORIES = [
        'operations' => 'Operations',
        'guests' => 'Guests',
        'progress' => 'Progress',
        'news' => 'News',
        'support' => 'Support',
    ];

    /** type => category. Types not listed (admin broadcasts: info, warning, success, error) are news. */
    private const TYPE_CATEGORY = [
        'equipment' => 'operations', 'equipment_breakdown' => 'operations', 'equipment_wear' => 'operations',
        'energy' => 'operations', 'energy_breakdown' => 'operations',
        'water' => 'operations', 'water_breakdown' => 'operations',
        'terrain_park' => 'operations', 'real_estate' => 'operations', 'building' => 'operations',
        'maintenance' => 'operations', 'resort' => 'operations', 'government' => 'operations',
        'vip' => 'guests',
        'achievement' => 'progress', 'welcome' => 'progress', 'welcome_back' => 'progress',
        'support' => 'support',
    ];

    /** Types that ask the player to act soon; shown with a warning tone */
    private const ALERT_TYPES = ['equipment_breakdown', 'equipment_wear', 'energy_breakdown', 'water_breakdown', 'warning', 'error'];

    /** Old rows pointed here before the right page existed; fixed when read, the table is not rewritten */
    private const LEGACY_LINKS = ['vip' => '/vip-guests'];

    public static function category(string $type): string
    {
        return self::TYPE_CATEGORY[$type] ?? 'news';
    }

    /** The types stored for a category, and for news the known types to leave out */
    public static function typeFilter(string $category): array
    {
        $types = array_keys(array_filter(self::TYPE_CATEGORY, fn($c) => $c === $category));
        return $category === 'news' ? ['not', array_keys(self::TYPE_CATEGORY)] : ['in', $types];
    }

    /** Applies a category filter to a query builder on the notifications table */
    public static function whereCategory($builder, ?string $category)
    {
        if ($category === null || !isset(self::CATEGORIES[$category])) return $builder;
        [$mode, $types] = self::typeFilter($category);
        return $mode === 'in' ? $builder->whereIn('type', $types) : $builder->whereNotIn('type', $types);
    }

    public static function isAlert(array $row): bool
    {
        // Rows from before the breakdown types existed carry it in the title only
        return in_array($row['type'] ?? '', self::ALERT_TYPES, true) || str_ends_with((string) ($row['title'] ?? ''), 'broke down!');
    }

    /** On-site path the notification opens, or null when it has none */
    public static function link(array $row): ?string
    {
        $link = (string) ($row['link'] ?? '');
        $type = (string) ($row['type'] ?? '');
        if (isset(self::LEGACY_LINKS[$type]) && $link === '/dashboard') $link = self::LEGACY_LINKS[$type];
        return $link !== '' && $link[0] === '/' && !str_starts_with($link, '//') ? $link : null;
    }

    /** Page name for a path from the in-game menus, used as "Open VIP Guests" */
    public static function linkLabel(?string $link): ?string
    {
        if ($link === null) return null;
        static $labels = null;
        if ($labels === null) {
            $labels = [];
            foreach (Shell::SEARCH as [$name, $url]) $labels[$url] = $name;
            foreach (Shell::NAV as $section) foreach ($section['items'] as $item) $labels[$item['url']] = $item['label'];
        }
        $path = strtok($link, '?#');
        return $labels[$path] ?? null;
    }

    /** One row in the shape every client reads. The first six keys predate this class: the Android app reads them. */
    public static function item(array $row): array
    {
        $link = self::link($row);
        $created = strtotime((string) $row['created_at']) ?: time();
        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'message' => (string) $row['message'],
            'icon' => preg_match('/^[a-z0-9 -]+$/i', (string) $row['icon']) ? (string) $row['icon'] : 'fa-solid fa-bell',
            'isRead' => (bool) $row['is_read'],
            'time' => date('M j, H:i', $created),
            'category' => self::category((string) $row['type']),
            'alert' => self::isAlert($row),
            'link' => $link,
            'linkLabel' => self::linkLabel($link),
            'createdAt' => date(DATE_ATOM, $created),
        ];
    }

    /** total and unread per category for one player, plus all */
    public static function counts(int $userId): array
    {
        $rows = db_connect()->table('notifications')->select('type, COUNT(*) AS total, SUM(is_read = 0) AS unread')
            ->where('user_id', $userId)->groupBy('type')->get()->getResultArray();
        $counts = ['all' => ['total' => 0, 'unread' => 0]];
        foreach (self::CATEGORIES as $key => $_) $counts[$key] = ['total' => 0, 'unread' => 0];
        foreach ($rows as $r) {
            $c = self::category((string) $r['type']);
            foreach (['all', $c] as $k) {
                $counts[$k]['total'] += (int) $r['total'];
                $counts[$k]['unread'] += (int) $r['unread'];
            }
        }
        return $counts;
    }

    /** Newest first, unread ones ahead, for the bell */
    public static function latest(int $userId, int $limit = 6): array
    {
        $rows = db_connect()->table('notifications')->select('id, type, title, message, icon, link, is_read, created_at')
            ->where('user_id', $userId)->orderBy('is_read', 'ASC')->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')
            ->limit($limit)->get()->getResultArray();
        return array_map([self::class, 'item'], $rows);
    }
}
