<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use App\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    /**
     * Configures aliases for Filter classes to
     * make reading things nicer and simpler.
     *
     * @var array<string, class-string|list<class-string>>
     *
     * [filter_name => classname]
     * or [filter_name => [classname1, classname2, ...]]
     */
    public array $aliases = [

        'csrf'          => CSRF::class,
        'toolbar'       => \App\Filters\AdminDebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'throttle'      => \App\Filters\Throttle::class,
        'bottrap'       => \App\Filters\BotTrap::class,
        'cap'           => \App\Filters\CapVerify::class,
        'profilecomplete' => \App\Filters\ProfileComplete::class,
        'seasongate' => \App\Filters\SeasonGate::class,
        'featuregate' => \App\Filters\FeatureGate::class,
        'admin'         => \App\Filters\AdminFilter::class,
        'maintenance'   => \App\Filters\MaintenanceFilter::class,
        'markdown'      => \App\Filters\MarkdownNegotiationFilter::class,
        'cors'          => Cors::class,
        'apiauth'       => \App\Filters\ApiAuth::class,
        'apptoken'      => \App\Filters\AppToken::class,
        'tokens'        => \CodeIgniter\Shield\Filters\TokenAuth::class,

        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
    ];

    /**
     * List of special required filters.
     *
     * The filters listed here are special. They are applied before and after
     * other kinds of filters, and always applied even if a route does not exist.
     *
     * Filters set by default provide framework functionality. If removed,
     * those functions will no longer work.
     *
     * @see https://codeigniter.com/user_guide/incoming/filters.html#provided-filters
     *
     * @var array{before: list<string>, after: list<string>}
     */
    public array $required = [
        'before' => [
            'forcehttps', // Force Global Secure Requests
            'pagecache',  // Web Page Caching
        ],
        'after' => [
            'pagecache',   // Web Page Caching
            'performance', // Performance Metrics
            'toolbar',     // Debug Toolbar
        ],
    ];

    /**
     * List of filter aliases that are always
     * applied before and after every request.
     *
     * @var array{
     *     before: array<string, array{except: list<string>|string}>|list<string>,
     *     after: array<string, array{except: list<string>|string}>|list<string>
     * }
     */
    public array $globals = [
        'before' => [
            'maintenance',
            // 'honeypot',
            'csrf' => ['except' => ['api/v1/app/*']],
            'invalidchars',
        ],
        'after' => [
            // 'honeypot',
            'secureheaders',
            'markdown',
        ],
    ];

    /**
     * List of filter aliases that works on a
     * particular HTTP method (GET, POST, etc.).
     *
     * Example:
     * 'POST' => ['foo', 'bar']
     *
     * If you use this, you should disable auto-routing because auto-routing
     * permits any HTTP method to access a controller. Accessing the controller
     * with a method you don't expect could bypass the filter.
     *
     * @var array<string, list<string>>
     */
    public array $methods = [];

    /**
     * List of filter aliases that should run on any
     * before or after URI patterns.
     *
     * Example:
     * 'isLoggedIn' => ['before' => ['account/*', 'profiles/*']]
     *
     * @var array<string, array<string, list<string>>>
     */
    public array $filters = [
        'throttle' => [
            'before' => ['login*', 'register*', 'magic-link*', 'api/v1/app/auth*', 'demo/claim', 'admin/confirm'],
            'after'  => ['login'],
        ],
        // Hidden-field bot check on the forms that create accounts, sign in or send email
        'bottrap' => ['before' => ['register', 'login', 'login/magic-link']],
        // Cap CAPTCHA on the same forms (App\Filters\CapVerify), after the cheaper bot-field check
        'cap'     => ['before' => ['register', 'login', 'login/magic-link', 'demo/claim']],
        'admin' => ['before' => ['admin*']],
        'seasongate' => ['before' => ['dashboard*', 'resort*', 'map*', 'staff*', 'finances*', 'bank*', 'tickets*', 'weather*', 'grooming*', 'snowmaking*', 'night-skiing*', 'hotels*', 'restaurants*', 'rentals*', 'buildings*', 'equipment*', 'insurance*', 'marketing*', 'achievements*', 'leaderboard*', 'resort-analysis*', 'vip-guests*', 'genepis*', 'daily-bonus*', 'settings*', 'account*', 'activity*', 'notifications*', 'terrain-parks*', 'winter-sports*', 'parking*', 'energy*', 'water*', 'admin*', 'tour*', 'emergency*', 'environment*', 'government*', 'ski-lessons*', 'ski-patrol*', 'real-estate*', 'off-season*', 'scenic*', 'tournaments*', 'compliance*', 'vote*', 'support*', 'alliances*', 'tutorial*', 'beta*']],
        // Pages behind a testing flag (App\Filters\FeatureGate::ROUTE_FLAGS)
        'featuregate' => ['before' => ['winter-sports*']],
        'profilecomplete' => ['before' => ['dashboard*', 'resort*', 'map*', 'staff*', 'finances*', 'bank*', 'tickets*', 'weather*', 'grooming*', 'snowmaking*', 'night-skiing*', 'hotels*', 'restaurants*', 'rentals*', 'buildings*', 'equipment*', 'insurance*', 'marketing*', 'achievements*', 'leaderboard*', 'resort-analysis*', 'vip-guests*', 'genepis*', 'daily-bonus*', 'settings*', 'account*', 'activity*', 'notifications*', 'terrain-parks*', 'winter-sports*', 'parking*', 'energy*', 'water*', 'admin*', 'tour*', 'emergency*', 'environment*', 'government*', 'ski-lessons*', 'ski-patrol*', 'real-estate*', 'off-season*', 'scenic*', 'tournaments*', 'compliance*', 'vote*', 'support*', 'alliances*', 'tutorial*', 'beta*']],
        'session' => ['before' => ['dashboard*', 'resort*', 'map*', 'staff*', 'finances*', 'bank*', 'tickets*', 'weather*', 'grooming*', 'snowmaking*', 'night-skiing*', 'hotels*', 'restaurants*', 'rentals*', 'buildings*', 'equipment*', 'insurance*', 'marketing*', 'achievements*', 'leaderboard*', 'resort-analysis*', 'vip-guests*', 'genepis*', 'daily-bonus*', 'settings*', 'account*', 'activity*', 'notifications*', 'terrain-parks*', 'winter-sports*', 'parking*', 'energy*', 'water*', 'admin*', 'tour*', 'emergency*', 'environment*', 'government*', 'ski-lessons*', 'ski-patrol*', 'real-estate*', 'off-season*', 'scenic*', 'tournaments*', 'compliance*', 'vote*', 'support*', 'alliances*', 'tutorial*', 'beta*']],
    ];
}
