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
    /**
     * The game menu: section => icon + items. 'feature' = unlocked by progress, 'hide' = page switched off,
     * 'badge' => 'bonus' | 'roadmap'. The Android app's menu (Api\V1\App\Menu) uses the same sections.
     */
    public const NAV = [
        'Mountain' => ['icon' => 'fa-mountain-sun', 'items' => [
            ['url' => '/resort',        'label' => 'Overview',      'icon' => 'fa-mountain-sun'],
            ['url' => '/map',           'label' => 'Trail Map',     'icon' => 'fa-map'],
            ['url' => '/weather',       'label' => 'Weather',       'icon' => 'fa-cloud-sun'],
            ['url' => '/grooming',      'label' => 'Grooming',      'icon' => 'fa-tractor',          'feature' => 'grooming'],
            ['url' => '/snowmaking',    'label' => 'Snowmaking',    'icon' => 'fa-snowflake',        'feature' => 'snowmaking'],
            ['url' => '/night-skiing',  'label' => 'Night Skiing',  'icon' => 'fa-moon',             'feature' => 'night_skiing'],
            ['url' => '/terrain-parks', 'label' => 'Terrain Parks', 'icon' => 'fa-person-snowboarding', 'feature' => 'terrain_parks'],
            ['url' => '/scenic-lifts',  'label' => 'Scenic Lifts',  'icon' => 'fa-camera',           'feature' => 'scenic_lifts'],
            ['url' => '/ski-patrol',    'label' => 'Ski Patrol',    'icon' => 'fa-shield-halved'],
            ['url' => '/emergency',     'label' => 'Emergency',     'icon' => 'fa-truck-medical'],
            ['url' => '/energy',        'label' => 'Energy',        'icon' => 'fa-bolt',             'hide' => 'energy'],
            ['url' => '/water',         'label' => 'Water',         'icon' => 'fa-droplet',          'hide' => 'water'],
        ]],
        'Guests' => ['icon' => 'fa-people-group', 'items' => [
            ['url' => '/hotels',        'label' => 'Hotels',        'icon' => 'fa-hotel'],
            ['url' => '/restaurants',   'label' => 'Restaurants',   'icon' => 'fa-utensils'],
            ['url' => '/rentals',       'label' => 'Ski Rentals',   'icon' => 'fa-person-skiing'],
            ['url' => '/retail',        'label' => 'Retail',        'icon' => 'fa-store',            'feature' => 'retail'],
            ['url' => '/ski-lessons',   'label' => 'Ski School',    'icon' => 'fa-chalkboard-user'],
            ['url' => '/transportation','label' => 'Transportation','icon' => 'fa-bus',              'feature' => 'transportation'],
            ['url' => '/parking',       'label' => 'Parking',       'icon' => 'fa-square-parking',    'feature' => 'parking'],
            ['url' => '/off-season',    'label' => 'Off-Season',    'icon' => 'fa-sun',              'feature' => 'off_season'],
            ['url' => '/real-estate',   'label' => 'Real Estate',   'icon' => 'fa-city'],
            ['url' => '/vip-guests',    'label' => 'VIP Guests',    'icon' => 'fa-star'],
        ]],
        'Business' => ['icon' => 'fa-coins', 'items' => [
            ['url' => '/finances',      'label' => 'Finances',      'icon' => 'fa-coins'],
            ['url' => '/bank',          'label' => 'Bank',          'icon' => 'fa-building-columns'],
            ['url' => '/tickets',       'label' => 'Lift Tickets',  'icon' => 'fa-ticket'],
            ['url' => '/staff',         'label' => 'Staff',         'icon' => 'fa-users'],
            ['url' => '/marketing',     'label' => 'Marketing',     'icon' => 'fa-bullhorn',         'feature' => 'marketing'],
            ['url' => '/equipment',     'label' => 'Equipment',     'icon' => 'fa-toolbox'],
            ['url' => '/compliance',    'label' => 'Compliance',    'icon' => 'fa-scale-balanced'],
            ['url' => '/resort-analysis','label' => 'Analysis',     'icon' => 'fa-clipboard-check',   'feature' => 'resort_analysis'],
        ]],
        'Community' => ['icon' => 'fa-ranking-star', 'items' => [
            ['url' => '/leaderboard',   'label' => 'Leaderboard',   'icon' => 'fa-ranking-star'],
            ['url' => '/alliances',     'label' => 'Alliances',     'icon' => 'fa-handshake'],
            ['url' => '/tournaments',   'label' => 'Events',        'icon' => 'fa-medal',            'feature' => 'tournaments'],
            ['url' => '/achievements',  'label' => 'Achievements',  'icon' => 'fa-trophy'],
            ['url' => '/daily-bonus',   'label' => 'Daily Bonus',   'icon' => 'fa-gift',             'badge' => 'bonus'],
            ['url' => '/genepis',       'label' => 'Génépis',       'icon' => 'fa-seedling'],
            ['url' => '/vote',          'label' => 'Vote',          'icon' => 'fa-check-to-slot'],
            ['url' => '/roadmap',       'label' => 'Roadmap',       'icon' => 'fa-map-location-dot', 'badge' => 'roadmap'],
        ]],
    ];

    /** The account menu (signed-in players), after the username; Admin and Log out are added by the islands */
    public const ACCOUNT = [
        ['url' => '/settings',      'label' => 'Settings',      'icon' => 'fa-gear'],
        ['url' => '/activity',      'label' => 'Activity Log',  'icon' => 'fa-clock-rotate-left'],
        ['url' => '/support',       'label' => 'Support',       'icon' => 'fa-headset'],
        ['url' => 'https://wiki.ski-manager.net', 'label' => 'Wiki', 'icon' => 'fa-book', 'ext' => true],
    ];

    /** The navbar for signed-out visitors: public pages only (the game pages all need an account) */
    public const PUBLIC_NAV = [
        ['url' => '/guides',  'label' => 'Guides',  'icon' => 'fa-book-open'],
        ['url' => '/updates', 'label' => 'Updates', 'icon' => 'fa-newspaper'],
        ['url' => '/roadmap', 'label' => 'Roadmap', 'icon' => 'fa-map-location-dot'],
        ['url' => '/about',   'label' => 'About',   'icon' => 'fa-circle-info'],
    ];

    /**
     * Pages search finds besides the NAV and ACCOUNT ones (name, url, icon, optional page-switch key like NAV's 'hide'). Search shows NAV and ACCOUNT pages
     * as the menu does (same label and icon, locked and switched-off rules), so only pages outside the menu go here.
     */
    public const SEARCH_EXTRA = [
        ['Dashboard', '/dashboard', 'fa-gauge-high'], ['Hire Staff', '/staff/hire', 'fa-user-plus'],
        ['Insurance', '/insurance', 'fa-shield-heart', 'insurance'], ['Government', '/government', 'fa-landmark', 'government'], ['Environment', '/environment', 'fa-leaf'],
        ['Morale', '/morale', 'fa-face-smile'], ['Android beta', '/beta/android', 'fa-mobile-screen'], ['Beta features', '/beta', 'fa-flask'],
        ['Notifications', '/notifications', 'fa-bell'], ['Account', '/account', 'fa-user-gear'], ['Guides', '/guides', 'fa-book-open'],
        ['About', '/about', 'fa-circle-info'], ['FAQ', '/faq', 'fa-circle-question'], ['Updates', '/updates', 'fa-newspaper'],
        ['Contact', '/contact', 'fa-envelope'], ['Terms', '/terms', 'fa-file-contract'], ['Privacy', '/privacy', 'fa-user-shield'],
        ['Cookies', '/cookies', 'fa-cookie-bite'], ['Disclaimer', '/disclaimer', 'fa-circle-exclamation'], ['Sitemap', '/sitemap', 'fa-sitemap'],
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
            // /vote is the monthly resort vote once its flag is on
            'label' => $it['url'] === '/vote' && \App\Libraries\ResortVote::flagOn() ? 'Resort Vote' : $it['label'],
            'icon' => $it['icon'],
            'locked' => $locked,
            'flagOff' => $flagOff,
            'external' => !empty($it['ext']),
            'bonusDue' => ($it['badge'] ?? null) === 'bonus' && $bonusDue,
            // "New" until the player has opened the page once (users.roadmap_seen_at)
            'isNew' => ($it['badge'] ?? null) === 'roadmap' && auth()->loggedIn() && empty(auth()->user()?->roadmap_seen_at),
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

        $resolve = fn(array $list) => array_values(array_filter(array_map(fn($it) => self::resolveItem($it, $isAdmin, $bonusDue), $list)));
        $nav = [];
        foreach (self::NAV as $name => $section) {
            $items = $resolve($section['items']);
            if ($items) $nav[] = ['name' => $name, 'icon' => $section['icon'], 'items' => $items];
        }
        $account = $isLoggedIn ? $resolve(self::ACCOUNT) : [];

        // Search: the menu's pages as the player sees them (locked ones open /achievements, like the menu),
        // then the pages outside the menu. One entry per URL.
        $search = [];
        foreach ([...array_merge(...array_column($nav, 'items')), ...$account] as $it) {
            $search[$it['url']] ??= ['name' => $it['label'], 'url' => $it['href'], 'icon' => $it['icon']];
        }
        foreach (self::SEARCH_EXTRA as $p) {
            [$name, $url, $icon] = $p;
            if (!empty($p[3]) && function_exists('isPageHidden') && isPageHidden($p[3])) continue;
            $search[$url] ??= ['name' => $name, 'url' => $url, 'icon' => $icon];
        }

        $weather = getCurrentWeather();
        $fin = $userId ? $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray() : null;
        $genepis = $userId ? $db->table('genepis')->select('balance')->where('user_id', $userId)->get()->getRowArray() : null;
        $rating = $userId ? resortRating($userId) : null;

        return [
            'path' => $currentPath,
            'user' => $isLoggedIn && auth()->user() ? ['username' => (string) auth()->user()->username, 'isAdmin' => $isAdmin] : null,
            'nav' => $isLoggedIn ? $nav : [],
            'account' => $account,
            'publicNav' => array_map(fn($it) => ['href' => $it['url'], 'url' => $it['url'], 'label' => $it['label'], 'icon' => $it['icon']], self::PUBLIC_NAV),
            'bonusDue' => $bonusDue,
            'search' => array_values($search),
            'stats' => $isLoggedIn ? [
                'cash' => currency((int) ($fin['cash'] ?? 0)),
                'temp' => temp((int) $weather['temp']),
                'tempIcon' => $weather['temp'] <= -5 ? 'fa-snowflake' : ($weather['temp'] <= 0 ? 'fa-cloud' : 'fa-sun'),
                'weather' => (string) ($weather['condition_name'] ?? ''),
                'snow' => snow((int) $weather['snow_base']),
                'gameDay' => (int) $weather['game_day'],
                'seasonLength' => getSeasonLength(),
                // Resort clock: server time, so the browser's own clock is never trusted
                'now' => time(),
                'timezone' => 'America/New_York',
                'genepis' => (int) ($genepis['balance'] ?? 0),
                'stars' => (int) ($rating['stars'] ?? 0),
            ] : null,
            'notifications' => [
                'unread' => $userId ? unreadNotificationCount($userId) : 0,
                'latest' => $userId ? NotificationFeed::latest((int) $userId) : [],
            ],
            'flash' => $flash,
            'demo' => $userId && session()->get('is_demo') ? \App\Controllers\Demo::barState((int) $userId) : null,
            'seasonVote' => $userId ? SeasonVote::state($userId, $currentPath) : null,
        ];
    }
}
