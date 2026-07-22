<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="carboncloud">
<head>
    <meta charset="UTF-8">
    <!-- Google Consent Mode v2 -->
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent', 'default', {
        'analytics_storage': 'denied',
        'ad_storage': 'denied',
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'wait_for_update': 500
    });
    </script>
    <!-- End Consent Mode -->
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!="dataLayer"?"\x26l="+l:"";j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);})(window,document,"script","dataLayer","GTM-5GGPL25W");</script>
    <!-- End Google Tag Manager -->
    <meta property="og:title" content="Ski Manager - Free Online Ski Resort Game">
    <meta property="og:description" content="Build, manage, and grow your dream ski resort. Free to play in your browser.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ski-manager.net/">
    <meta property="og:site_name" content="Ski Manager">
    <meta property="og:image" content="https://ski-manager.net/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Ski Manager - build and run your own ski resort">
    <meta name="twitter:image" content="https://ski-manager.net/og-image.png">
    <meta name="theme-color" content="#1a2940">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Ski Manager - Free Online Ski Resort Game">
    <meta name="twitter:description" content="Build, manage, and grow your dream ski resort. Free to play.">
    <meta name="msvalidate.01" content="17A0AC384937E88A4F602D2B7DAE237F" />
    <link rel="canonical" href="https://ski-manager.net<?= uri_string() ? "/" . uri_string() : "" ?>" />
    <meta name="description" content="Ski Manager - Free online ski resort management game. Build slopes, hire staff, manage finances, and compete with players worldwide.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <title><?= $this->renderSection('title') ?> - Ski Manager</title>
    <link rel="preload" href="/css/style.css?v=<?= @filemtime(FCPATH . "css/style.css") ?>" as="style">
    <?php if (auth()->loggedIn()) : ?><link rel="prefetch" href="/img/<?= (db_connect()->table("player_finances")->where("user_id", auth()->id())->get()->getRowArray()["resort_map"] ?? "ParkCity") ?>_low.jpg" as="image"><?php endif ?>
    <?php if (auth()->loggedIn()) : ?><link rel="prefetch" href="/img/<?= (db_connect()->table("player_finances")->where("user_id", auth()->id())->get()->getRowArray()["resort_map"] ?? "ParkCity") ?>_med.jpg" as="image"><?php endif ?>
    <link rel="stylesheet" href="/css/style.css?v=<?= @filemtime(FCPATH . "css/style.css") ?>" fetchpriority="high">
    <script defer src="https://js.sentry-cdn.com/67d62e71889bb1702e60a6c3130aff40.min.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://kit.fontawesome.com" crossorigin>
    <link rel="preconnect" href="https://ka-f.fontawesome.com" crossorigin>
    <script async src="https://kit.fontawesome.com/e8108d3d5f.js" crossorigin="anonymous"></script>
    <script>
        var saved = localStorage.getItem('theme') || 'carboncloud';
        document.documentElement.setAttribute('data-theme', saved);
    </script>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebApplication",
      "name": "Ski Manager",
      "url": "https://ski-manager.net",
      "description": "Free online ski resort management game",
      "applicationCategory": "Game",
      "operatingSystem": "Web Browser",
      "image": "https://ski-manager.net/og-image.png",
      "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" }
    }
    </script>
    <style>.scrollbar-none::-webkit-scrollbar{display:none}.scrollbar-none{-ms-overflow-style:none;scrollbar-width:none}</style>
<style>
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideInRight{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.95)}to{opacity:1;transform:scale(1)}}
@keyframes pulse-soft{0%,100%{opacity:1}50%{opacity:0.7}}
.animate-fade-in-up{animation:fadeInUp 0.4s ease-out both}
.animate-fade-in{animation:fadeIn 0.3s ease-out both}
.animate-slide-in-right{animation:slideInRight 0.4s ease-out both}
.animate-scale-in{animation:scaleIn 0.3s ease-out both}
.animate-pulse-soft{animation:pulse-soft 2s ease-in-out infinite}

.card{transition:transform 0.15s ease,box-shadow 0.15s ease}
.card:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,0.1)}
.btn{transition:transform 0.1s ease,box-shadow 0.1s ease}
.btn:active{transform:scale(0.97)}
.badge{transition:all 0.2s ease}
.progress{transition:value 0.5s ease}
.alert{animation:fadeInUp 0.3s ease-out both}
.stat-value,.text-2xl.font-bold,.text-3xl.font-bold{transition:color 0.3s ease}
a.link{transition:opacity 0.15s ease}
a.link:hover{opacity:0.8}
.dropdown-content{animation:scaleIn 0.15s ease-out both}
</style>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5636695863753930" crossorigin="anonymous"></script>
    <script async src="https://fundingchoicesmessages.google.com/i/pub-5636695863753930?ers=1"></script><script>(function() {function signalGooglefcPresent() {if (!window.frames['googlefcPresent']) {if (document.body) {const iframe = document.createElement('iframe'); iframe.style = 'width: 0; height: 0; border: none; z-index: -1000; left: -1000px; top: -1000px;'; iframe.style.display = 'none'; iframe.name = 'googlefcPresent'; document.body.appendChild(iframe);} else {setTimeout(signalGooglefcPresent, 0);}}}signalGooglefcPresent();})();</script>
</head>
<body class="min-h-screen flex flex-col bg-base-200"><a href="#main-content" class="skip-link">Skip to main content</a>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5GGPL25W" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <!-- Navbar -->
    <?php $__currentPath = '/' . trim(uri_string(), '/'); ?>
    <!-- Navbar -->
    <?php
        $__currentPath = '/' . trim(uri_string(), '/');
        $__isAdmin = auth()->loggedIn() && auth()->id() === 1;

        $__bonusDue = false;
        if (auth()->loggedIn()) {
            $__br = db_connect()->table('daily_bonus')->where('user_id', auth()->id())->get()->getRowArray();
            $__bonusDue = !$__br || (($__br['last_claim_date'] ?? null) < date('Y-m-d'));
        }

        $__nav = [
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

        $__navItem = function (array $it) use ($__isAdmin, $__bonusDue) {
            if (!empty($it['hide']) && function_exists('isPageHidden') && isPageHidden($it['hide'])) return '';
            $locked = false;
            $href = $it['url'];
            if (!empty($it['feature'])) {
                $unlocked = $__isAdmin || isFeatureUnlocked($it['feature']);
                if (!$unlocked) { $locked = true; $href = '/achievements'; }
            }
            $ext = !empty($it['ext']);
            $cls = $locked ? 'opacity-40' : '';
            $badge = (!empty($it['badge']) && $it['badge'] === 'bonus' && $__bonusDue)
                ? ' <span class="badge badge-warning badge-xs">!</span>' : '';
            $target = $ext ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<li><a href="' . esc($href, 'attr') . '"' . $target . ' class="' . $cls . '">'
                . '<i class="fa-solid ' . esc($it['icon'], 'attr') . ' fa-fw mr-2"></i>' . esc($it['label']) . $badge . '</a></li>';
        };
    ?>
    <nav aria-label="Main navigation"><div class="navbar bg-base-100 shadow-md" style="z-index:9999; position:sticky; top:0">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden" aria-label="Open menu"><i class="fa-solid fa-bars text-lg" aria-hidden="true"></i></div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-10 mt-3 w-64 p-2 shadow max-h-[80vh] overflow-y-auto">
                    <li><a href="/dashboard"><i class="fa-solid fa-gauge-high fa-fw mr-2"></i>Dashboard</a></li>
                    <?php foreach ($__nav as $__secName => $__sec) : ?>
                        <li class="menu-title text-xs mt-1"><?= esc($__secName) ?></li>
                        <?php foreach ($__sec['items'] as $__it) : echo $__navItem($__it); endforeach ?>
                    <?php endforeach ?>
                    <?php if ($__isAdmin) : ?><li class="menu-title text-xs mt-1">Admin</li><li><a href="/admin"><i class="fa-solid fa-shield-halved fa-fw mr-2 text-error"></i>Admin Panel</a></li><?php endif ?>
                </ul>
            </div>
            <a href="/" class="btn btn-ghost text-xl font-bold"><i class="fa-solid fa-person-skiing mr-2"></i>Ski Manager</a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1 gap-1">
                <li><a href="/" class="<?= $__currentPath === '/' ? 'active' : '' ?>"><i class="fa-solid fa-house mr-1"></i>Home</a></li>
                <li><a href="/dashboard" class="<?= $__currentPath === '/dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge-high mr-1"></i>Dashboard</a></li>
                <?php foreach ($__nav as $__secName => $__sec) : ?>
                <li>
                    <details>
                        <summary><i class="fa-solid <?= esc($__sec['icon'], 'attr') ?> mr-1"></i><?= esc($__secName) ?></summary>
                        <ul class="bg-base-100 rounded-box shadow w-56 z-50">
                            <?php foreach ($__sec['items'] as $__it) : echo $__navItem($__it); endforeach ?>
                        </ul>
                    </details>
                </li>
                <?php endforeach ?>
                <?php if ($__isAdmin) : ?><li><a href="/admin" class="text-error"><i class="fa-solid fa-shield-halved mr-1"></i>Admin</a></li><?php endif ?>
            </ul>
        </div>
        <div class="navbar-end gap-2">
            <?php if (auth()->loggedIn()) : ?>
            <button onclick="document.getElementById('searchModal').showModal()" class="btn btn-ghost btn-sm btn-circle" aria-label="Search"><i class="fa-solid fa-search" aria-hidden="true"></i></button>
            <dialog id="searchModal" class="modal modal-top">
                <div class="modal-box max-w-lg mx-auto mt-20 p-0">
                    <div class="flex items-center gap-2 p-3 border-b border-base-300">
                        <i class="fa-solid fa-search text-base-content/30"></i>
                        <input type="text" id="globalSearch" placeholder="Search pages, features, settings..." class="input input-ghost input-sm flex-1 focus:outline-none" autocomplete="off" autofocus />
                        <kbd class="kbd kbd-xs">Esc</kbd>
                    </div>
                    <div id="searchResults" class="max-h-80 overflow-y-auto p-2"></div>
                    <div id="searchEmpty" class="p-6 text-center text-sm text-base-content/40">
                        <i class="fa-solid fa-compass text-2xl mb-2"></i>
                        <p>Type to search pages and features</p>
                    </div>
                </div>
                <form method="dialog" class="modal-backdrop"><button>close</button></form>
            </dialog>
            <?php endif ?>
            <label class="swap swap-rotate btn btn-ghost btn-sm btn-circle" aria-label="Toggle light or dark theme">
                <input type="checkbox" id="themeToggle" value="winter" class="theme-controller" />
                <svg aria-hidden="true" class="swap-on fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.64,17l-.71.71a1,1,0,0,0,0,1.41,1,1,0,0,0,1.41,0l.71-.71A1,1,0,0,0,5.64,17ZM5,12a1,1,0,0,0-1-1H3a1,1,0,0,0,0,2H4A1,1,0,0,0,5,12Zm7-7a1,1,0,0,0,1-1V3a1,1,0,0,0-2,0V4A1,1,0,0,0,12,5ZM5.64,7.05a1,1,0,0,0,.7.29,1,1,0,0,0,.71-.29,1,1,0,0,0,0-1.41l-.71-.71A1,1,0,0,0,4.93,6.34Zm12,.29a1,1,0,0,0,.7-.29l.71-.71a1,1,0,1,0-1.41-1.41L17,5.64a1,1,0,0,0,0,1.41A1,1,0,0,0,17.66,7.34ZM21,11H20a1,1,0,0,0,0,2h1a1,1,0,0,0,0-2Zm-9,8a1,1,0,0,0-1,1v1a1,1,0,0,0,2,0V20A1,1,0,0,0,12,19ZM18.36,17A1,1,0,0,0,17,18.36l.71.71a1,1,0,0,0,1.41,0,1,1,0,0,0,0-1.41ZM12,6.5A5.5,5.5,0,1,0,17.5,12,5.51,5.51,0,0,0,12,6.5Zm0,9A3.5,3.5,0,1,1,15.5,12,3.5,3.5,0,0,1,12,15.5Z"/></svg>
                <svg aria-hidden="true" class="swap-off fill-current w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"/></svg>
            </label>
            <?php if (auth()->loggedIn()) : ?>
                <?php $__notifCount = unreadNotificationCount(auth()->id()); ?>
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-sm btn-circle indicator" aria-label="Notifications">
                        <i class="fa-solid fa-bell text-base-content/80"></i>
                        <span id="navbarNotifBadge" class="indicator-item badge badge-error badge-xs <?php echo $__notifCount == 0 ? "hidden" : ""; ?>"><?= $__notifCount > 9 ? "9+" : $__notifCount ?></span>
                    </div>
                    <div tabindex="0" class="dropdown-content card bg-base-100 shadow-2xl z-50 w-80 mt-2 border border-base-200/50">
                        <div class="card-body p-3">
                            <div class="flex items-center justify-between mb-2 pb-1.5 border-b border-base-100">
                                <span class="font-bold text-xs uppercase tracking-wider text-base-content/50">Notifications</span>
                                <?php if ($__notifCount > 0) : ?>
                                    <a href="/notifications/read-all" class="link link-primary text-xs no-underline hover:underline">Mark all read</a>
                                <?php endif ?>
                            </div>
                            <div id="navbarNotifTargetList" class="space-y-1 max-h-64 overflow-y-auto">
                                <?php $__notifs = db_connect()->table("notifications")->where("user_id", auth()->id())->orderBy("created_at", "DESC")->limit(5)->get()->getResultArray(); ?>
                                <?php if (empty($__notifs)) : ?>
                                    <p class="text-xs text-base-content/40 text-center py-4">No notifications</p>
                                <?php else : ?>
                                    <?php foreach ($__notifs as $__n) : ?>
                                        <?php 
                                            $isRead = (bool)($__n["is_read"] ?? false);
                                            $iconColor = "text-base-content/40";
                                            $bgAccent = "bg-base-200/50";
                                            
                                            if (!$isRead) {
                                                $iconStr = strtolower($__n["icon"] ?? "");
                                                $titleStr = strtolower($__n["title"] ?? "");
                                                if (strpos($iconStr, "snowflake") !== false || strpos($titleStr, "weather") !== false) { $iconColor = "text-info"; $bgAccent = "bg-info/10"; }
                                                elseif (strpos($iconStr, "trophy") !== false || strpos($iconStr, "medal") !== false) { $iconColor = "text-accent"; $bgAccent = "bg-accent/10"; }
                                                elseif (strpos($iconStr, "exclamation") !== false || strpos($iconStr, "triangle") !== false) { $iconColor = "text-error"; $bgAccent = "bg-error/10"; }
                                                else { $iconColor = "text-primary"; $bgAccent = "bg-primary/10"; }
                                            }
                                        ?>
                                        <a href="<?= esc($__n["link"] ?? "/notifications") ?>" class="flex items-start gap-2.5 p-2 rounded-xl hover:bg-base-200/70 transition-all <?= $isRead ? "opacity-60" : "font-medium" ?>">
                                            <div class="w-7 h-7 rounded-lg <?= $bgAccent ?> shrink-0 flex items-center justify-center">
                                                <i class="<?= esc($__n["icon"] ?? "fa-solid fa-bell") ?> text-[11px] <?= $iconColor ?>"></i>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-[11px] text-base-content font-bold truncate"><?= esc($__n["title"]) ?></div>
                                                <div class="text-[10px] text-base-content/60 truncate mt-0.5"><?= esc($__n["message"]) ?></div>
                                            </div>
                                            <?php if (!$isRead) : ?>
                                                <span class="w-1.5 h-1.5 rounded-full bg-primary mt-2 shrink-0"></span>
                                            <?php endif; ?>
                                        </a>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                            <div class="border-t border-base-100 pt-2 mt-1">
                                <a href="/notifications" class="btn btn-ghost btn-xs w-full text-[11px]">View All</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="dropdown dropdown-end"><div tabindex="0" role="button" class="btn btn-ghost btn-sm"><i class="fa-solid fa-user-circle mr-1"></i><?= auth()->user()->username ?></div><ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow w-48 z-50 mt-2"><li><a href="/dashboard"><i class="fa-solid fa-gauge-high mr-1"></i>Dashboard</a></li><li><a href="/settings"><i class="fa-solid fa-gear mr-1"></i>Settings</a></li><li><a href="/logout" class="text-error"><i class="fa-solid fa-right-from-bracket mr-1"></i>Logout</a></li></ul></div>
            <?php else : ?>
                <a href="/login" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-to-bracket mr-1"></i>Log in</a>
                <a href="/register" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus mr-1"></i>Play Free</a>
            <?php endif ?>
        </div>
    </div>
    </nav>

    <!-- Content -->
    <main class="flex-1" id="main-content" role="main">
    <?php if (session("admin_original_id")) : ?>
    <div class="bg-warning text-warning-content text-center py-2 text-sm font-semibold sticky top-0 z-50">
        <i class="fa-solid fa-user-secret mr-1"></i> Impersonating <?= auth()->user()->username ?? "user" ?> — <a href="/admin/stop-impersonate" class="underline font-bold">Return to Admin</a>
    </div>
    <?php endif ?>
    <?php if (auth()->loggedIn()) : ?>
    <div class="bg-base-100 border-b border-base-300 px-4 py-1.5 text-xs">
        <div class="max-w-7xl mx-auto flex items-center gap-3 md:gap-4 overflow-x-auto overflow-y-visible stats-bar scrollbar-none">
            <?php
                $__db = db_connect();
                $__fin = $__db->table("player_finances")->where("user_id", auth()->id())->get()->getRowArray();
                $__cash = $__fin ? (int)$__fin["cash"] : (int) (match(session("difficulty") ?? "standard") { "easy" => 1000000, "hard" => 200000, default => 500000 });
                $__weather = $__db->table("weather")->orderBy("game_day", "DESC")->limit(1)->get()->getRowArray();
                $__snow = $__weather ? (int)$__weather["snow_base"] : 0;
                $__genepis = $__db->table("genepis")->where("user_id", auth()->id())->get()->getRowArray();
                $__gbal = $__genepis ? (int)$__genepis["balance"] : 0;
                $__gameDay = max(1, (int)((strtotime(date("Y-m-d")) - strtotime(getSeasonStartDate())) / 86400) + 1);
                $__rep = $__fin ? (int)($__fin["reputation"] ?? 0) : 0;
                $__rating = resortRating(auth()->id());
            ?>
            <?php
                $__openLifts = $__fin ? $__db->table("player_items")->where("user_id", auth()->id())->where("item_type", "lift")->where("status", "open")->countAllResults(false) : 0;
                $__openSlopes = $__fin ? $__db->table("player_items")->where("user_id", auth()->id())->whereIn("item_type", ["slope","downhill","crosscountry","snowpark","luge"])->where("status", "open")->countAllResults(false) : 0;
                $__visitors = $__openLifts * 80 + $__openSlopes * 40;
                $__currentTemp = function_exists('hourlyTemp') && $__weather ? hourlyTemp((int)$__weather['temp']) : ($__weather ? (int)$__weather['temp'] : 0);
            ?>
            <a href="/finances" title="Cash balance" class="flex items-center gap-1 shrink-0 hover:text-success transition-colors"><i class="fa-solid fa-money-bill-wave text-success"></i> <?= currency($__cash) ?></a>
            <span class="text-base-content/20 hidden md:inline">·</span>
            <a href="/weather" title="<?= $__weather ? $__weather['condition_name'] : 'Weather' ?>" class="flex items-center gap-1 shrink-0 hover:text-info transition-colors"><i class="fa-solid fa-<?= $__currentTemp <= -5 ? 'snowflake text-info' : ($__currentTemp <= 0 ? 'cloud text-base-content/50' : 'sun text-warning') ?>"></i> <?= temp($__currentTemp) ?></a>
            <span class="text-base-content/20 hidden md:inline">·</span>
            <a href="/snowmaking" title="Snow base" class="flex items-center gap-1 shrink-0 hover:text-info transition-colors"><i class="fa-solid fa-layer-group text-info"></i> <?= snow($__snow) ?></a>
            <span class="text-base-content/20 hidden md:inline">·</span>
            <span title="Season progress" class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-calendar text-primary"></i> Day <?= $__gameDay ?>/<?= getSeasonLength() ?></span>
            <span class="text-base-content/20 hidden md:inline">·</span>
            <span title="Daily visitors" class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-people-group"></i> <span id="navVisitors"><?= number_format($__visitors) ?></span></span>
            <span class="text-base-content/20 hidden md:inline">·</span>
            <a href="/genepis" title="Génépis balance" class="flex items-center gap-1 shrink-0 hover:text-success transition-colors"><i class="fa-solid fa-seedling text-success"></i> <?= number_format($__gbal) ?></a>
            <span class="text-base-content/20 hidden md:inline">·</span>
            <a href="/resort-analysis" title="Resort rating" class="flex items-center gap-0.5 shrink-0"><?php for ($__i = 1; $__i <= 5; $__i++) : ?><i class="fa-solid fa-star text-xs <?= $__i <= $__rating["stars"] ? "text-warning" : "text-base-content/20" ?>"></i><?php endfor ?></a>
        </div>
    </div>
    <?php endif ?>
        <?= $this->renderSection('content') ?>
    </main>
    <div id="liveAppAlertToastStack" class="toast toast-bottom toast-end z-[9999] space-y-2 pointer-events-none max-w-sm w-full"></div>

    <!-- Footer -->
    <footer class="bg-base-200 border-t border-base-300" role="contentinfo" aria-label="Site footer">
        <div class="max-w-6xl mx-auto px-4 pt-10 pb-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
                <!-- Brand -->
                <div class="col-span-2 md:col-span-1">
                    <a href="/" class="flex items-center gap-2 text-lg font-bold mb-3"><i class="fa-solid fa-person-skiing text-primary"></i>Ski Manager</a>
                    <p class="text-xs text-base-content/50 mb-4">The most detailed free ski resort management game. Build, manage, and dominate the slopes.</p>
                    <div class="flex gap-3">
                        <a href="https://discord.gg/u3GzGt8K6a" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm btn-circle" title="Discord"><i class="fa-brands fa-discord text-lg"></i></a>
                        <a href="https://gitlab.com/contact1231/skimanager-v2" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm btn-circle" title="GitLab"><i class="fa-brands fa-gitlab text-lg"></i></a>
                    </div>
                </div>
                <!-- Game -->
                <div>
                    <h6 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-3">Game</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/register" class="link link-hover text-base-content/60">Play Now</a></li>
                        <li><a href="/leaderboard" class="link link-hover text-base-content/60">Leaderboard</a></li>
                        <li><a href="/updates" class="link link-hover text-base-content/60">Updates</a></li>
                        <li><a href="https://wiki.ski-manager.net" target="_blank" class="link link-hover text-base-content/60">Wiki</a></li>
                        <li><a href="/genepis" class="link link-hover text-base-content/60">Genepis</a></li>
                    </ul>
                </div>
                <!-- Resources -->
                <div>
                    <h6 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-3">Resources</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/faq" class="link link-hover text-base-content/60">FAQ</a></li>
                        <li><a href="/contact" class="link link-hover text-base-content/60">Contact</a></li>
                        <li><a href="/bugs" class="link link-hover text-base-content/60">Report a Bug</a></li>
                        <li><a href="/about" class="link link-hover text-base-content/60">About</a></li>
                    </ul>
                </div>
                <!-- Legal -->
                <div>
                    <h6 class="text-xs font-semibold uppercase tracking-wider text-base-content/40 mb-3">Legal</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/terms" class="link link-hover text-base-content/60">Terms</a></li>
                        <li><a href="/privacy" class="link link-hover text-base-content/60">Privacy</a></li>
                        <li><a href="/cookies" class="link link-hover text-base-content/60">Cookies</a></li>
                        <li><a href="/disclaimer" class="link link-hover text-base-content/60">Disclaimer</a></li>
                        <li><a href="/sitemap" class="link link-hover text-base-content/60">Sitemap</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-base-300">
            <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 max-w-6xl mx-auto text-xs text-base-content/40">
                <p>&copy; <?= date('Y') ?> Ski Manager. Built with <i class="fa-solid fa-heart text-error text-[10px]"></i> for ski lovers.</p>
                <p class="mt-1 md:mt-0">Maps by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="link link-hover">Mapsynergy</a> &middot; v1.1</p>
            </div>
        </div>
    </footer>

    <script>
        (function() {
            var toggle = document.getElementById('themeToggle');
            var current = document.documentElement.getAttribute('data-theme');
            toggle.checked = (current === 'winter');
            toggle.addEventListener('change', function() {
                var theme = this.checked ? 'winter' : 'carboncloud';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
            });
        })();
    </script>

<script>
document.querySelectorAll(".navbar details").forEach(function(det) {
    det.addEventListener("toggle", function() {
        if (this.open) {
            document.querySelectorAll(".navbar details").forEach(function(other) {
                if (other !== det) other.removeAttribute("open");
            });
        }
    });
});
document.addEventListener("click", function(e) {
    if (!e.target.closest(".navbar details")) {
        document.querySelectorAll(".navbar details[open]").forEach(function(d) {
            d.removeAttribute("open");
        });
    }
});
</script>
<dialog id="confirmModal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box">
        <h3 class="text-lg font-bold" id="confirmTitle">Confirm</h3>
        <p class="py-4 text-sm text-base-content/70" id="confirmMessage"></p>
        <div class="modal-action">
            <button class="btn btn-ghost btn-sm" onclick="closeConfirm()">Cancel</button>
            <button class="btn btn-primary btn-sm" id="confirmYes">Confirm</button>
        </div>
    </div>
    <div class="modal-backdrop" role="button" tabindex="-1" onclick="closeConfirm()" onkeydown="if(event.key==='Enter'||event.key===' ')closeConfirm()"></div>
</dialog>
<script>
let pendingForm=null;
function closeConfirm(){document.getElementById("confirmModal").close();pendingForm=null;}
document.addEventListener("submit",function(e){
    const form=e.target,msg=form.dataset.confirm;
    if(!msg||form.dataset.confirmed)return;
    e.preventDefault();
    pendingForm=form;
    document.getElementById("confirmTitle").textContent=form.dataset.confirmTitle||"Confirm";
    document.getElementById("confirmMessage").textContent=msg;
    document.getElementById("confirmModal").showModal();
});
document.getElementById("confirmYes").addEventListener("click",function(){
    if(pendingForm){pendingForm.dataset.confirmed="1";pendingForm.submit();}
    closeConfirm();
});
</script>
<script>
const searchPages=[{n:"Dashboard",u:"/dashboard",i:"fa-gauge-high"},{n:"Resort",u:"/resort",i:"fa-mountain-sun"},{n:"Trail Map",u:"/map",i:"fa-map"},{n:"Weather",u:"/weather",i:"fa-cloud-sun"},{n:"Staff",u:"/staff",i:"fa-users"},{n:"Hire Staff",u:"/staff/hire",i:"fa-user-plus"},{n:"Finances",u:"/finances",i:"fa-coins"},{n:"Bank \u0026 Loans",u:"/bank",i:"fa-landmark"},{n:"Tickets",u:"/tickets",i:"fa-ticket"},{n:"Hotels",u:"/hotels",i:"fa-hotel"},{n:"Restaurants",u:"/restaurants",i:"fa-utensils"},{n:"Rentals",u:"/rentals",i:"fa-person-skiing"},{n:"Retail",u:"/retail",i:"fa-shop"},{n:"Real Estate",u:"/real-estate",i:"fa-house"},{n:"Transportation",u:"/transportation",i:"fa-bus"},{n:"Ski Patrol",u:"/ski-patrol",i:"fa-shield-halved"},{n:"Equipment",u:"/equipment",i:"fa-toolbox"},{n:"Snowmaking",u:"/snowmaking",i:"fa-snowflake"},{n:"Night Skiing",u:"/night-skiing",i:"fa-moon"},{n:"Grooming",u:"/grooming",i:"fa-tractor"},{n:"Terrain Parks",u:"/terrain-parks",i:"fa-person-snowboarding"},{n:"Parking",u:"/parking",i:"fa-square-parking"},{n:"Energy",u:"/energy",i:"fa-bolt"},{n:"Water",u:"/water",i:"fa-droplet"},{n:"Scenic Lifts",u:"/scenic-lifts",i:"fa-camera"},{n:"Marketing",u:"/marketing",i:"fa-bullhorn"},{n:"Insurance",u:"/insurance",i:"fa-shield-halved"},{n:"Government",u:"/government",i:"fa-building-columns"},{n:"Environment",u:"/environment",i:"fa-leaf"},{n:"Emergency",u:"/emergency",i:"fa-truck-medical"},{n:"Ski Lessons",u:"/ski-lessons",i:"fa-chalkboard-user"},{n:"Achievements",u:"/achievements",i:"fa-trophy"},{n:"Leaderboard",u:"/leaderboard",i:"fa-ranking-star"},{n:"Tournaments",u:"/tournaments",i:"fa-medal"},{n:"Daily Bonus",u:"/daily-bonus",i:"fa-gift"},{n:"Genepis",u:"/genepis",i:"fa-seedling"},{n:"VIP Guests",u:"/vip-guests",i:"fa-star"},{n:"Resort Analysis",u:"/resort-analysis",i:"fa-clipboard-check"},{n:"Off-Season",u:"/off-season",i:"fa-sun"},{n:"Morale",u:"/morale",i:"fa-face-smile"},{n:"Activity Log",u:"/activity",i:"fa-clock-rotate-left"},{n:"Notifications",u:"/notifications",i:"fa-bell"},{n:"Settings",u:"/settings",i:"fa-gear"},{n:"Account",u:"/account",i:"fa-user-gear"},{n:"About",u:"/about",i:"fa-circle-info"},{n:"FAQ",u:"/faq",i:"fa-circle-question"},{n:"Updates",u:"/updates",i:"fa-newspaper"},{n:"Contact",u:"/contact",i:"fa-envelope"},{n:"Terms",u:"/terms",i:"fa-file-contract"},{n:"Privacy",u:"/privacy",i:"fa-shield-halved"},{n:"Cookies",u:"/cookies",i:"fa-cookie-bite"},{n:"Disclaimer",u:"/disclaimer",i:"fa-circle-info"},{n:"Sitemap",u:"/sitemap",i:"fa-sitemap"}];
const si=document.getElementById("globalSearch"),sr=document.getElementById("searchResults");
if(si){si.addEventListener("input",function(){const q=this.value.toLowerCase().trim();if(!q){sr.innerHTML="";return;}const m=searchPages.filter(p=>p.n.toLowerCase().includes(q));sr.innerHTML=m.length?m.map(p=>"<a href=\""+p.u+"\" class=\"flex items-center gap-2 p-2 rounded-lg hover:bg-base-200 text-sm\"><i class=\"fa-solid "+p.i+" w-5 text-center text-base-content/50\"></i>"+p.n+"</a>").join(""):"<p class=\"text-xs text-base-content/40 text-center py-2\">No results</p>";});si.addEventListener("keydown",function(e){if(e.key==="Enter"){const first=sr.querySelector("a");if(first)window.location=first.href;}});
document.addEventListener("keydown",function(e){if((e.metaKey||e.ctrlKey)&&e.key==="k"){e.preventDefault();si.focus();si.closest(".dropdown").querySelector("[tabindex]").focus();si.focus();}});
}
</script>
<button id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" style="display:none;position:fixed;bottom:1.5rem;left:1.5rem;z-index:9990;width:2.5rem;height:2.5rem;border-radius:50%;border:none;cursor:pointer;font-size:1rem;box-shadow:0 2px 8px rgba(0,0,0,0.2);" class="btn btn-circle btn-sm btn-primary"><i class="fa-solid fa-arrow-up"></i></button>
<script>window.addEventListener("scroll",function(){document.getElementById("backToTop").style.display=window.scrollY>300?"flex":"none";});</script>
<script>
document.querySelectorAll(".alert-success,.alert-error,.alert-warning").forEach(function(el){
    if(el.closest(".card-body"))return;
    setTimeout(function(){el.style.transition="opacity 0.5s";el.style.opacity="0";setTimeout(function(){el.remove();},500);},4000);
});
</script>
<script>
document.querySelectorAll("form").forEach(function(f){
    f.addEventListener("submit",function(){
        var btn=f.querySelector("button[type=submit],button:not([type])");
        if(btn&&!f.dataset.confirm){btn.classList.add("loading");btn.disabled=true;}
    });
});
</script>
<script>
document.querySelectorAll("[data-count]").forEach(function(el){
    var target=parseInt(el.dataset.count),current=0,step=Math.max(1,Math.floor(target/30));
    var timer=setInterval(function(){current+=step;if(current>=target){current=target;clearInterval(timer);}el.textContent=current.toLocaleString();},20);
});
</script>
<script>
document.addEventListener("keydown",function(e){
});
</script>
<dialog id="shortcutModal" class="modal modal-bottom sm:modal-middle">
    <div class="modal-box">
        <h3 class="font-bold text-lg mb-3"><i class="fa-solid fa-keyboard mr-2"></i>Keyboard Shortcuts</h3>
        <div class="grid grid-cols-2 gap-2 text-sm">
            <div><kbd class="kbd kbd-sm">D</kbd> Dashboard</div>
            <div><kbd class="kbd kbd-sm">R</kbd> Resort</div>
            <div><kbd class="kbd kbd-sm">M</kbd> Trail Map</div>
            <div><kbd class="kbd kbd-sm">W</kbd> Weather</div>
            <div><kbd class="kbd kbd-sm">S</kbd> Staff</div>
            <div><kbd class="kbd kbd-sm">F</kbd> Finances</div>
            <div><kbd class="kbd kbd-sm">Ctrl+K</kbd> Search</div>
            <div><kbd class="kbd kbd-sm">?</kbd> This help</div>
        </div>
        <div class="modal-action"><form method="dialog"><button class="btn btn-sm">Close</button></form></div>
    </div>
    <form method="dialog" class="modal-backdrop"><button>close</button></form>
</dialog>
<script>


</script>

<!-- Cookie Consent Banner -->
<div id="cookieConsent" class="fixed bottom-0 left-0 right-0 bg-base-200 border-t border-base-300 p-4 shadow-lg" style="z-index:99999;display:none;">
    <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-sm text-base-content/80">We use cookies for analytics and advertising. You can choose which to accept.</p>
        <div class="flex gap-2 shrink-0">
            <button onclick="acceptCookies('all')" class="btn btn-primary btn-sm">Accept All</button>
            <button onclick="acceptCookies('analytics')" class="btn btn-outline btn-sm">Analytics Only</button>
            <button onclick="acceptCookies('none')" class="btn btn-ghost btn-sm">Reject All</button>
        </div>
    </div>
</div>
<script>
function acceptCookies(level){
    var consent={
        analytics_storage:(level==='all'||level==='analytics')?'granted':'denied',
        ad_storage:level==='all'?'granted':'denied',
        ad_user_data:level==='all'?'granted':'denied',
        ad_personalization:level==='all'?'granted':'denied'
    };
    gtag('consent','update',consent);
    localStorage.setItem('cookie_consent',level);
    document.getElementById('cookieConsent').style.display='none';
}
(function(){
    var saved=localStorage.getItem('cookie_consent');
    if(saved){acceptCookies(saved);}
    else{document.getElementById('cookieConsent').style.display='block';}
})();
</script>
<?php if (function_exists("featureEnabled") && featureEnabled("tooltips")) : ?>
<style>[data-tip]{position:relative;cursor:help}[data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#1d232a;color:#a6adbb;padding:6px 10px;border-radius:6px;font-size:11px;white-space:nowrap;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.3);pointer-events:none}[data-tip]:hover::before{content:"";position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1d232a;z-index:9999}</style>
<?php endif ?>
<?php if (function_exists("featureEnabled") && featureEnabled("beta_mobile_nav")) : ?>
<nav class="fixed bottom-0 left-0 right-0 bg-base-100 border-t border-base-300 z-50 md:hidden" style="padding-bottom:env(safe-area-inset-bottom)">
    <div class="flex justify-around items-center py-2">
        <a href="/dashboard" class="flex flex-col items-center gap-0.5 text-xs <?= uri_string() === "dashboard" ? "text-primary" : "text-base-content/50" ?>"><i class="fa-solid fa-gauge-high text-lg"></i>Home</a>
        <a href="/map" class="flex flex-col items-center gap-0.5 text-xs <?= uri_string() === "map" ? "text-primary" : "text-base-content/50" ?>"><i class="fa-solid fa-map text-lg"></i>Map</a>
        <a href="/weather" class="flex flex-col items-center gap-0.5 text-xs <?= uri_string() === "weather" ? "text-primary" : "text-base-content/50" ?>"><i class="fa-solid fa-cloud-sun text-lg"></i>Weather</a>
        <a href="/staff" class="flex flex-col items-center gap-0.5 text-xs <?= uri_string() === "staff" ? "text-primary" : "text-base-content/50" ?>"><i class="fa-solid fa-users text-lg"></i>Staff</a>
        <a href="/finances" class="flex flex-col items-center gap-0.5 text-xs <?= uri_string() === "finances" ? "text-primary" : "text-base-content/50" ?>"><i class="fa-solid fa-coins text-lg"></i>Money</a>
    </div>
</nav>
<style>.md\:hidden{padding-bottom:60px}</style>
<?php endif ?>
</script>
<script>if("serviceWorker" in navigator && location.hostname === "ski-manager.net"){navigator.serviceWorker.register("/sw.js");}</script>

<script>
let processedAlertIds = new Set();
let skipInitialToasts = true;

function fetchLiveResortAlerts() {
    fetch("/api/notifications/live")
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(res => {
            const badgeEl = document.getElementById("navbarNotifBadge");
            if (badgeEl) {
                if (res.unread > 0) {
                    badgeEl.textContent = res.unread > 9 ? "9+" : res.unread;
                    badgeEl.classList.remove("hidden");
                } else {
                    badgeEl.classList.add("hidden");
                }
            }

            if (!res.list || res.list.length === 0) return;

            res.list.forEach(alert => {
                if (alert.is_read == 1 || processedAlertIds.has(alert.id)) return;
                processedAlertIds.add(alert.id);

                if (skipInitialToasts) return;

                // Build a modern, tactile DaisyUI Toast component
                const toast = document.createElement("div");
                
                // Contextual neon glow mappings based on category profiles
                let glowLayer = "shadow-[0_0_20px_rgba(59,130,246,0.15)] border-primary/20";
                if (iconStr.includes("snowflake")) glowLayer = "shadow-[0_0_20px_rgba(0,218,255,0.2)] border-info/30";
                else if (iconStr.includes("trophy") || iconStr.includes("medal")) glowLayer = "shadow-[0_0_20px_rgba(217,70,239,0.2)] border-accent/30";
                else if (iconStr.includes("exclamation") || iconStr.includes("triangle")) glowLayer = "shadow-[0_0_20px_rgba(239,68,68,0.25)] border-error/30";

                toast.className = `alert bg-base-100 p-3.5 flex gap-3 pointer-events-auto transition-all duration-300 transform translate-y-4 opacity-0 rounded-xl border ${glowLayer}`;

                
                // Color-code accent boundaries matching system classifications
                let accentColor = "text-primary bg-primary/10";
                const iconStr = (alert.icon || "").toLowerCase();
                if (iconStr.includes("snowflake")) accentColor = "text-info bg-info/10";
                else if (iconStr.includes("trophy") || iconStr.includes("medal")) accentColor = "text-accent bg-accent/10";
                else if (iconStr.includes("exclamation") || iconStr.includes("triangle")) accentColor = "text-error bg-error/10";

                toast.innerHTML = `
                    <div class="w-8 h-8 rounded-lg ${accentColor} flex items-center justify-center shrink-0">
                        <i class="${alert.icon || "fa-solid fa-bell"} text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-bold text-base-content truncate">${alert.title}</div>
                        <p class="text-[11px] text-base-content/60 truncate mt-0.5">${alert.message}</p>
                    </div>
                `;

                const stack = document.getElementById("liveAppAlertToastStack");
                if (stack) {
                    stack.appendChild(toast);
                    setTimeout(() => toast.classList.remove("translate-y-4", "opacity-0"), 50);
                    setTimeout(() => {
                        toast.classList.add("opacity-0", "translate-x-4");
                        setTimeout(() => toast.remove(), 350);
                    }, 5000);
                }
            });

            skipInitialToasts = false;
        }).catch(() => {});
}

document.addEventListener("DOMContentLoaded", () => {
    fetchLiveResortAlerts();
    setInterval(fetchLiveResortAlerts, 10000);
});
</script>

</body>
<!-- Tutorial Widget -->
<?php if (auth()->loggedIn()) : ?>
<div id="tutorialWidget" class="hidden" style="position:fixed;bottom:1rem;right:1rem;z-index:9998;">
    <div class="card bg-base-100 shadow-xl w-80 border border-base-300">
        <div class="card-body p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="badge badge-primary badge-sm" id="tutStep">1/10</div>
                    <h3 class="font-bold text-sm" id="tutTitle">Welcome!</h3>
                </div>
                <button onclick="skipTutorial()" class="btn btn-ghost btn-xs" title="Skip tutorial"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="flex gap-3">
                <i class="fa-solid fa-mountain-sun text-primary text-xl mt-0.5" id="tutIcon"></i>
                <div class="flex-1">
                    <p class="text-xs text-base-content/70 leading-relaxed" id="tutText"></p>
                    <div id="tutAction" class="mt-2 hidden">
                        <div class="flex items-center gap-1 text-xs">
                            <i class="fa-solid fa-arrow-right text-primary"></i>
                            <span class="font-semibold text-primary" id="tutActionText"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between mt-3">
                <progress class="progress progress-primary w-32" id="tutProgress" value="0" max="10"></progress>
                <div class="flex gap-1">
                    <a id="tutGoBtn" href="/dashboard" class="btn btn-primary btn-xs gap-1 hidden"><i class="fa-solid fa-arrow-right"></i> Go</a>
                    <button id="tutNextBtn" onclick="advanceTutorial()" class="btn btn-primary btn-xs gap-1 hidden"><i class="fa-solid fa-check"></i> Next</button>
                    <button id="tutDoneBtn" onclick="advanceTutorial()" class="btn btn-success btn-xs gap-1 hidden"><i class="fa-solid fa-trophy"></i> Finish</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const tutWidget = document.getElementById('tutorialWidget');
const tutCsrf = '<?= csrf_hash() ?>';
const tutCsrfName = '<?= csrf_token() ?>';
let tutCurrentPage = window.location.pathname;

function loadTutorial() {
    fetch('/tutorial/check?page=' + encodeURIComponent(tutCurrentPage), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            if (d.done) { tutWidget.classList.add('hidden'); return; }
            tutWidget.classList.remove('hidden');
            renderTutStep(d);
        })
        .catch(() => tutWidget.classList.add('hidden'));
}

function renderTutStep(d) {
    document.getElementById('tutStep').textContent = (d.step + 1) + '/' + d.total;
    document.getElementById('tutTitle').textContent = d.data.title;
    document.getElementById('tutText').textContent = d.data.text;
    document.getElementById('tutIcon').className = d.data.icon + ' text-primary text-xl mt-0.5';
    document.getElementById('tutProgress').value = d.step;
    document.getElementById('tutProgress').max = d.total;

    const actionDiv = document.getElementById('tutAction');
    const actionText = document.getElementById('tutActionText');
    const goBtn = document.getElementById('tutGoBtn');
    const nextBtn = document.getElementById('tutNextBtn');
    const doneBtn = document.getElementById('tutDoneBtn');

    goBtn.classList.add('hidden');
    nextBtn.classList.add('hidden');
    doneBtn.classList.add('hidden');

    if (d.data.action) {
        actionDiv.classList.remove('hidden');
        actionText.textContent = d.data.action;

        if (d.canAdvance) {
            nextBtn.classList.remove('hidden');
        } else if (d.data.page && d.data.page !== tutCurrentPage) {
            goBtn.classList.remove('hidden');
            goBtn.href = d.data.page;
        } else {
            nextBtn.classList.remove('hidden');
            nextBtn.disabled = true;
            nextBtn.textContent = 'Complete the action above';
            nextBtn.classList.replace('btn-primary', 'btn-ghost');
        }
    } else {
        actionDiv.classList.add('hidden');
        if (d.step >= d.total - 1) {
            doneBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
        }
    }
}

function advanceTutorial() {
    fetch('/tutorial/advance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ [tutCsrfName]: tutCsrf })
    }).then(r => r.json()).then(d => {
        if (d.done || d.completed) {
            tutWidget.classList.add('hidden');
            if (d.completed) {
                const toast = document.createElement('div');
                toast.className = 'toast toast-end z-50';
                toast.innerHTML = '<div class="alert alert-success"><i class="fa-solid fa-trophy"></i><span>Tutorial complete! You\'re ready to run your resort.</span></div>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            }
        } else {
            renderTutStep({ step: d.step, total: <?= count(\App\Controllers\Tutorial::STEPS) ?>, data: d.data, canAdvance: false });
        }
    });
}

function skipTutorial() {
    fetch('/tutorial/skip', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ [tutCsrfName]: tutCsrf })
    }).then(() => tutWidget.classList.add('hidden'));
}

loadTutorial();
setInterval(loadTutorial, 10000);
</script>
<?php endif ?>

<script>
document.querySelectorAll('.navbar details').forEach(function(det) {
    det.addEventListener('toggle', function() {
        if (this.open) {
            document.querySelectorAll('.navbar details').forEach(function(other) {
                if (other !== det) other.removeAttribute('open');
            });
        }
    });
});
</script>
