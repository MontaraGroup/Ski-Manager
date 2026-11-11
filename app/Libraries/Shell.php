<?php

namespace App\Libraries;

use App\Filters\FeatureGate;

/**
 * The in-game frame: navigation, stats bar, notifications and flash messages. The React shell
 * islands (resources/js/islands/AppShell.tsx, StatsBar.tsx, MobileDock.tsx) get their props
 * from here, and the PHP layout uses the same NAV and item rules for guests and as the
 * fallback shown until the islands mount, so both stay in step.
 */
class Shell
{
    /** section => icon + items; 'feature' = unlocked by progress, 'hide' = page switched off, 'badge' => 'bonus' */
    public const NAV = [
        'Resort' => ['icon' => 'fa-mountain-sun', 'items' => [
            ['url' => '/resort',        'label' => 'Overview',      'icon' => 'fa-mountain-sun'],
            ['url' => '/map',           'label' => 'Trail Map',     'icon' => 'fa-map'],
            ['url' => '/grooming',      'label' => 'Grooming',      'icon' => 'fa-tractor',          'feature' => 'grooming'],
            ['url' => '/snowmaking',    'label' => 'Snowmaking',    'icon' => 'fa-snowflake',        'feature' => 'snowmaking'],
            ['url' => '/night-skiing',  'label' => 'Night Skiing',  'icon' => 'fa-moon',             'feature' => 'night_skiing'],
            ['url' => '/scenic-lifts',  'label' => 'Scenic Lifts',  'icon' => 'fa-camera',           'feature' => 'scenic_lifts'],
            ['url' => '/terrain-parks', 'label' => 'Terrain Parks', 'icon' => 'fa-person-snowboarding', 'feature' => 'terrain_parks'],
            ['url' => '/parking',       'label' => 'Parking',       'icon' => 'fa-square-parking',    'feature' => 'parking'],
            ['url' => '/resort-analysis','label' => 'Analysis',     'icon' => 'fa-clipboard-check',   'feature' => 'resort_analysis'],
            ['url' => '/energy',        'label' => 'Energy',        'icon' => 'fa-bolt',             'hide' => 'energy'],
            ['url' => '/water',         'label' => 'Water',         'icon' => 'fa-droplet',          'hide' => 'water'],
        ]],
        'Buildings' => ['icon' => 'fa-building', 'items' => [
            ['url' => '/hotels',        'label' => 'Hotels',        'icon' => 'fa-hotel'],
            ['url' => '/restaurants',   'label' => 'Restaurants',   'icon' => 'fa-utensils'],
            ['url' => '/rentals',       'label' => 'Ski Rentals',   'icon' => 'fa-person-skiing'],
            ['url' => '/retail',        'label' => 'Retail',        'icon' => 'fa-store',            'feature' => 'retail'],
            ['url' => '/real-estate',   'label' => 'Real Estate',   'icon' => 'fa-city'],
            ['url' => '/transportation','label' => 'Transportation','icon' => 'fa-bus',              'feature' => 'transportation'],
            ['url' => '/off-season',    'label' => 'Off-Season',    'icon' => 'fa-sun',              'feature' => 'off_season'],
            ['url' => '/ski-patrol',    'label' => 'Ski Patrol',    'icon' => 'fa-shield-halved'],
        ]],
        'Operations' => ['icon' => 'fa-coins', 'items' => [
            ['url' => '/finances',      'label' => 'Finances',      'icon' => 'fa-coins'],
            ['url' => '/bank',          'label' => 'Bank',          'icon' => 'fa-building-columns'],
            ['url' => '/tickets',       'label' => 'Lift Tickets',  'icon' => 'fa-ticket'],
            ['url' => '/staff',         'label' => 'Staff',         'icon' => 'fa-users'],
            ['url' => '/marketing',     'label' => 'Marketing',     'icon' => 'fa-bullhorn',         'feature' => 'marketing'],
            ['url' => '/ski-lessons',   'label' => 'Ski School',    'icon' => 'fa-chalkboard-user'],
            ['url' => '/compliance',    'label' => 'Compliance',    'icon' => 'fa-scale-balanced'],
            ['url' => '/equipment',     'label' => 'Equipment Shop','icon' => 'fa-shop'],
            ['url' => '/alliances',     'label' => 'Alliances',     'icon' => 'fa-handshake'],
        ]],
        'More' => ['icon' => 'fa-star', 'items' => [
            ['url' => '/weather',       'label' => 'Weather',       'icon' => 'fa-cloud-sun'],
            ['url' => '/emergency',     'label' => 'Emergency',     'icon' => 'fa-truck-medical'],
            ['url' => '/tournaments',   'label' => 'Events',        'icon' => 'fa-trophy',           'feature' => 'tournaments'],
            ['url' => '/vip-guests',    'label' => 'VIP Guests',    'icon' => 'fa-star'],
            ['url' => '/achievements',  'label' => 'Achievements',  'icon' => 'fa-award'],
            ['url' => '/leaderboard',   'label' => 'Leaderboard',   'icon' => 'fa-ranking-star'],
            ['url' => '/daily-bonus',   'label' => 'Daily Bonus',   'icon' => 'fa-fire',             'badge' => 'bonus'],
            ['url' => '/genepis',       'label' => 'Génépis',       'icon' => 'fa-seedling'],
            ['url' => '/activity',      'label' => 'Activity Log',  'icon' => 'fa-clock-rotate-left'],
            ['url' => '/vote',          'label' => 'Vote Season 4', 'icon' => 'fa-check-to-slot'],
            ['url' => 'https://wiki.ski-manager.net', 'label' => 'Wiki', 'icon' => 'fa-book', 'ext' => true],
            ['url' => '/support',       'label' => 'Support',       'icon' => 'fa-headset'],
            ['url' => '/settings',      'label' => 'Settings',      'icon' => 'fa-gear'],
        ]],
    ];

    /** Pages the navbar search finds (name, url, icon) */
    public const SEARCH = [
        ['Dashboard', '/dashboard', 'fa-gauge-high'], ['Resort', '/resort', 'fa-mountain-sun'], ['Alliances', '/alliances', 'fa-handshake'],
        ['Trail Map', '/map', 'fa-map'], ['Weather', '/weather', 'fa-cloud-sun'], ['Staff', '/staff', 'fa-users'], ['Hire Staff', '/staff/hire', 'fa-user-plus'],
        ['Finances', '/finances', 'fa-coins'], ['Bank & Loans', '/bank', 'fa-landmark'], ['Tickets', '/tickets', 'fa-ticket'], ['Hotels', '/hotels', 'fa-hotel'],
        ['Restaurants', '/restaurants', 'fa-utensils'], ['Rentals', '/rentals', 'fa-person-skiing'], ['Retail', '/retail', 'fa-shop'],
        ['Real Estate', '/real-estate', 'fa-house'], ['Transportation', '/transportation', 'fa-bus'], ['Ski Patrol', '/ski-patrol', 'fa-shield-halved'],
        ['Equipment', '/equipment', 'fa-toolbox'], ['Snowmaking', '/snowmaking', 'fa-snowflake'], ['Night Skiing', '/night-skiing', 'fa-moon'],
        ['Grooming', '/grooming', 'fa-tractor'], ['Terrain Parks', '/terrain-parks', 'fa-person-snowboarding'], ['Parking', '/parking', 'fa-square-parking'],
        ['Energy', '/energy', 'fa-bolt'], ['Water', '/water', 'fa-droplet'], ['Scenic Lifts', '/scenic-lifts', 'fa-camera'], ['Marketing', '/marketing', 'fa-bullhorn'],
        ['Insurance', '/insurance', 'fa-shield-halved'], ['Government', '/government', 'fa-building-columns'], ['Environment', '/environment', 'fa-leaf'],
        ['Emergency', '/emergency', 'fa-truck-medical'], ['Ski Lessons', '/ski-lessons', 'fa-chalkboard-user'], ['Achievements', '/achievements', 'fa-trophy'],
        ['Leaderboard', '/leaderboard', 'fa-ranking-star'], ['Tournaments', '/tournaments', 'fa-medal'], ['Daily Bonus', '/daily-bonus', 'fa-gift'],
        ['Genepis', '/genepis', 'fa-seedling'], ['VIP Guests', '/vip-guests', 'fa-star'], ['Resort Analysis', '/resort-analysis', 'fa-clipboard-check'],
        ['Off-Season', '/off-season', 'fa-sun'], ['Morale', '/morale', 'fa-face-smile'], ['Activity Log', '/activity', 'fa-clock-rotate-left'],
        ['Notifications', '/notifications', 'fa-bell'], ['Settings', '/settings', 'fa-gear'], ['Account', '/account', 'fa-user-gear'], ['About', '/about', 'fa-circle-info'],
        ['FAQ', '/faq', 'fa-circle-question'], ['Updates', '/updates', 'fa-newspaper'], ['Roadmap', '/roadmap', 'fa-map-location-dot'], ['Contact', '/contact', 'fa-envelope'],
        ['Terms', '/terms', 'fa-file-contract'], ['Privacy', '/privacy', 'fa-shield-halved'], ['Cookies', '/cookies', 'fa-cookie-bite'],
        ['Disclaimer', '/disclaimer', 'fa-circle-info'], ['Sitemap', '/sitemap', 'fa-sitemap'],
    ];

    public static function isAdmin(): bool
    {
        // Same rule as AdminFilter: user #1 or anyone in the admin/superadmin groups
        return auth()->loggedIn() && (auth()->id() === 1 || auth()->user()?->inGroup('admin', 'superadmin'));
    }

    public static function bonusDue(): bool
    {
        if (!auth()->loggedIn()) return false;
        $row = db_connect()->table('daily_bonus')->where('user_id', auth()->id())->get()->getRowArray();
        return !$row || (($row['last_claim_date'] ?? null) < date('Y-m-d'));
    }

    /**
     * A nav item as the player may see it, or null when it's hidden. Locked features point at
     * /achievements; switched-off features are hidden from players and shown faded to admins.
     */
    public static function resolveItem(array $it, bool $isAdmin, bool $bonusDue): ?array
    {
        if (!empty($it['hide']) && function_exists('isPageHidden') && isPageHidden($it['hide'])) return null;
        $flag = FeatureGate::flagForPath($it['url']);
        $flagOff = $flag !== null && function_exists('featureEnabled') && !featureEnabled($flag);
        if ($flagOff && !$isAdmin) return null;
        $locked = !empty($it['feature']) && !($isAdmin || isFeatureUnlocked($it['feature']));
        return [
            'href' => $locked ? '/achievements' : $it['url'],
            'url' => $it['url'],
            'label' => $it['label'],
            'icon' => $it['icon'],
            'locked' => $locked,
            'flagOff' => $flagOff,
            'external' => !empty($it['ext']),
            'bonusDue' => ($it['badge'] ?? null) === 'bonus' && $bonusDue,
            // Shown because the page is in beta (flag level 3): the nav marks it "Beta"
            'beta' => $flag !== null && !$flagOff && function_exists('featureIsBetaFor') && featureIsBetaFor($flag),
        ];
    }

    /** Reads and clears the session flash messages; the layout shows them once, as toasts or in <noscript> */
    public static function takeFlash(): array
    {
        $session = session();
        $flash = [];
        foreach (['success', 'info', 'error', 'warning', 'errors'] as $key) {
            $value = $session->get($key);
            if ($value === null || $value === '' || $value === []) continue;
            $flash[$key] = $key === 'errors' ? array_values(array_map('strval', (array) $value)) : (string) $value;
            $session->remove($key);
        }
        return $flash;
    }

    /** Props for the shell islands (signed-in players and guests) */
    public static function props(string $currentPath, array $flash): array
    {
        $isLoggedIn = auth()->loggedIn();
        $userId = $isLoggedIn ? (int) auth()->id() : null;
        $isAdmin = self::isAdmin();
        $bonusDue = self::bonusDue();
        $db = db_connect();

        $nav = [];
        foreach (self::NAV as $name => $section) {
            $items = array_values(array_filter(array_map(fn($it) => self::resolveItem($it, $isAdmin, $bonusDue), $section['items'])));
            if ($items) $nav[] = ['name' => $name, 'icon' => $section['icon'], 'items' => $items];
        }

        $weather = getCurrentWeather();
        $fin = $userId ? $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray() : null;
        $genepis = $userId ? $db->table('genepis')->select('balance')->where('user_id', $userId)->get()->getRowArray() : null;
        $rating = $userId ? resortRating($userId) : null;
        $notifs = $userId ? $db->table('notifications')->select('id, title, message, icon, is_read')->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')->limit(5)->get()->getResultArray() : [];

        return [
            'path' => $currentPath,
            'user' => $isLoggedIn && auth()->user() ? ['username' => (string) auth()->user()->username, 'isAdmin' => $isAdmin] : null,
            'nav' => $nav,
            'bonusDue' => $bonusDue,
            'search' => array_map(fn($p) => ['name' => $p[0], 'url' => $p[1], 'icon' => $p[2]], self::SEARCH),
            'stats' => $isLoggedIn ? [
                'cash' => currency((int) ($fin['cash'] ?? 0)),
                'temp' => temp((int) $weather['temp']),
                'tempIcon' => $weather['temp'] <= -5 ? 'fa-snowflake' : ($weather['temp'] <= 0 ? 'fa-cloud' : 'fa-sun'),
                'weather' => (string) ($weather['condition_name'] ?? ''),
                'snow' => snow((int) $weather['snow_base']),
                'gameDay' => (int) $weather['game_day'],
                'seasonLength' => getSeasonLength(),
                'genepis' => (int) ($genepis['balance'] ?? 0),
                'stars' => (int) ($rating['stars'] ?? 0),
            ] : null,
            'notifications' => [
                'unread' => $userId ? unreadNotificationCount($userId) : 0,
                'latest' => array_map(fn($n) => [
                    'id' => (int) $n['id'], 'title' => (string) $n['title'], 'message' => (string) $n['message'],
                    'icon' => preg_match('/^[a-z0-9 -]+$/i', (string) $n['icon']) ? (string) $n['icon'] : 'fa-solid fa-bell',
                    'isRead' => (bool) $n['is_read'],
                ], $notifs),
            ],
            'flash' => $flash,
            'demo' => $userId && session()->get('is_demo') ? \App\Controllers\Demo::barState((int) $userId) : null,
            'seasonVote' => $userId ? SeasonVote::state($userId, $currentPath) : null,
        ];
    }
}
