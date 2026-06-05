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
    <meta property="og:url" content="https://skimanager.net/">
    <meta property="og:site_name" content="Ski Manager">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Ski Manager - Free Online Ski Resort Game">
    <meta name="twitter:description" content="Build, manage, and grow your dream ski resort. Free to play.">
    <meta name="msvalidate.01" content="17A0AC384937E88A4F602D2B7DAE237F" />
    <link rel="canonical" href="https://skimanager.net<?= uri_string() ? "/" . uri_string() : "" ?>" />
    <meta name="description" content="Ski Manager - Free online ski resort management game. Build slopes, hire staff, manage finances, and compete with players worldwide.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <title><?= $this->renderSection('title') ?> - Ski Manager</title>
    <link rel="preload" href="/css/style.css?v=12" as="style">
    <link rel="stylesheet" href="/css/style.css?v=12" fetchpriority="high">
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
      "url": "https://skimanager.net",
      "description": "Free online ski resort management game",
      "applicationCategory": "Game",
      "operatingSystem": "Web Browser",
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
</head>
<body class="min-h-screen flex flex-col bg-base-200"><a href="#main-content" class="skip-link">Skip to main content</a>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5GGPL25W" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <!-- Navbar -->
    <nav aria-label="Main navigation"><div class="navbar bg-base-100 sticky top-0 shadow-md" style="z-index:9999; position:relative px-4 lg:px-8">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost lg:hidden">
                    <i class="fa-solid fa-bars text-lg"></i>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content bg-base-100 rounded-box z-10 mt-3 w-64 p-2 shadow max-h-[80vh] overflow-y-auto">
                    <li class="menu-title text-xs">Game</li>
                    <li><a href="/dashboard"><i class="fa-solid fa-gauge-high fa-fw mr-2"></i>Dashboard</a></li>
                    <li><a href="/resort"><i class="fa-solid fa-mountain-sun fa-fw mr-2"></i>Resort</a></li>
                    <li><a href="/map"><i class="fa-solid fa-map fa-fw mr-2"></i>Trail Map</a></li>
                    <li><a href="/weather"><i class="fa-solid fa-cloud-sun fa-fw mr-2"></i>Weather</a></li>
                    <li class="menu-title text-xs mt-2">Operations</li>
                    <li><a href="/staff"><i class="fa-solid fa-users fa-fw mr-2"></i>Staff</a></li>
                    <li><a href="<?= isFeatureUnlocked('grooming') ? '/grooming' : '/achievements' ?>" class="<?= isFeatureUnlocked('grooming') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-tractor fa-fw mr-2"></i>Grooming</a></li>
                    <li><a href="<?= isFeatureUnlocked('snowmaking') ? '/snowmaking' : '/achievements' ?>" class="<?= isFeatureUnlocked('snowmaking') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-snowflake fa-fw mr-2"></i>Snowmaking</a></li>
                    <li><a href="<?= isFeatureUnlocked('night_skiing') ? '/night-skiing' : '/achievements' ?>" class="<?= isFeatureUnlocked('night_skiing') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-moon fa-fw mr-2"></i>Night Skiing</a></li>
                    <li><a href="<?= isFeatureUnlocked('terrain_parks') ? '/terrain-parks' : '/achievements' ?>" class="<?= isFeatureUnlocked('terrain_parks') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-person-snowboarding fa-fw mr-2"></i>Terrain Parks</a></li>
                    <li><a href="<?= isFeatureUnlocked('parking') ? '/parking' : '/achievements' ?>" class="<?= isFeatureUnlocked('parking') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-square-parking fa-fw mr-2"></i>Parking</a></li>
                    <?php if (!isPageHidden('energy')) : ?><li><a href="/energy"><i class="fa-solid fa-bolt fa-fw mr-2"></i>Energy</a></li><?php endif ?>
                    <?php if (!isPageHidden('water')) : ?><li><a href="/water"><i class="fa-solid fa-droplet fa-fw mr-2"></i>Water</a></li><?php endif ?>
                    <li class="menu-title text-xs mt-2">Buildings</li>
                    <li><a href="/hotels"><i class="fa-solid fa-hotel fa-fw mr-2"></i>Hotels</a></li>
                    <li><a href="/restaurants"><i class="fa-solid fa-utensils fa-fw mr-2"></i>Restaurants</a></li>
                    <li><a href="/rentals"><i class="fa-solid fa-ski-boot fa-fw mr-2"></i>Rentals</a></li>
                    <li><a href="<?= isFeatureUnlocked('retail') ? '/retail' : '/achievements' ?>" class="<?= isFeatureUnlocked('retail') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-shop fa-fw mr-2"></i>Retail</a></li>
                    <li class="menu-title text-xs mt-2">Business</li>
                    <li><a href="/finances"><i class="fa-solid fa-coins fa-fw mr-2"></i>Finances</a></li>
                    <li><a href="/bank"><i class="fa-solid fa-landmark fa-fw mr-2"></i>Bank</a></li>
                    <li><a href="/tickets"><i class="fa-solid fa-ticket fa-fw mr-2"></i>Tickets</a></li>
                    <li><a href="<?= isFeatureUnlocked('marketing') ? '/marketing' : '/achievements' ?>" class="<?= isFeatureUnlocked('marketing') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-bullhorn fa-fw mr-2"></i>Marketing</a></li>
                    <?php if (!isPageHidden('insurance')) : ?><li><a href="/insurance"><i class="fa-solid fa-shield-halved fa-fw mr-2"></i>Insurance</a></li><?php endif ?>
                    <li><a href="/equipment"><i class="fa-solid fa-toolbox fa-fw mr-2"></i>Equipment</a></li>
                    <li class="menu-title text-xs mt-2">More</li>
                    <li><a href="/achievements"><i class="fa-solid fa-trophy fa-fw mr-2"></i>Achievements</a></li>
                    <li><a href="/leaderboard"><i class="fa-solid fa-ranking-star fa-fw mr-2"></i>Leaderboard</a></li>
                    <li><a href="<?= isFeatureUnlocked('resort_analysis') ? '/resort-analysis' : '/achievements' ?>" class="<?= isFeatureUnlocked('resort_analysis') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-clipboard-check fa-fw mr-2"></i>Analysis</a></li>
                    <li><a href="/vip-guests"><i class="fa-solid fa-star fa-fw mr-2"></i>VIP Guests</a></li>
                    <li><a href="/genepis"><i class="fa-solid fa-seedling fa-fw mr-2"></i>Genepis</a></li>
                    <li><a href="/daily-bonus"><i class="fa-solid fa-gift fa-fw mr-2"></i>Daily Bonus</a></li>
                    <li><a href="/settings"><i class="fa-solid fa-gear fa-fw mr-2"></i>Settings</a></li>
                </ul>
            </div>
            <a href="/" class="btn btn-ghost text-xl font-bold"><i class="fa-solid fa-person-skiing mr-2"></i>Ski Manager</a>
        </div>
        <div class="navbar-center hidden lg:flex">
            <ul class="menu menu-horizontal px-1 gap-1">
                <li><a href="/"><i class="fa-solid fa-house mr-1"></i>Home</a></li>
                <li><a href="/dashboard"><i class="fa-solid fa-gauge-high mr-1"></i>Dashboard</a></li>
                <li>
                    <details>
                        <summary><i class="fa-solid fa-mountain-sun mr-1"></i>Resort</summary>
                        <ul class="bg-base-100 rounded-box shadow w-52 z-50">
                            <li><a href="/resort"><i class="fa-solid fa-mountain-sun mr-1"></i>Overview</a></li>
                            <li><a href="/map"><i class="fa-solid fa-map mr-1"></i>Trail Map</a></li>
                            <li><a href="<?= isFeatureUnlocked('grooming') ? '/grooming' : '/achievements' ?>" class="<?= isFeatureUnlocked('grooming') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-tractor fa-fw mr-2"></i>Grooming</a></li>
                                    <li><a href="<?= isFeatureUnlocked('terrain_parks') ? '/terrain-parks' : '/achievements' ?>" class="<?= isFeatureUnlocked('terrain_parks') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-mountain-sun fa-fw mr-2"></i> Terrain Parks</a></li>
                                    <li><a href="<?= isFeatureUnlocked('parking') ? '/parking' : '/achievements' ?>" class="<?= isFeatureUnlocked('parking') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-square-parking fa-fw mr-2"></i> Parking</a></li>
                                    <li><a href="<?= isFeatureUnlocked('resort_analysis') ? '/resort-analysis' : '/achievements' ?>" class="<?= isFeatureUnlocked('resort_analysis') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-clipboard-check fa-fw mr-2"></i> Analysis</a></li>
                                    <?php if (!isPageHidden('energy')) : ?><li><a href="/energy"><i class="fa-solid fa-bolt fa-fw mr-2"></i> Energy</a></li><?php endif ?>
                                    <?php if (!isPageHidden('water')) : ?><li><a href="/water"><i class="fa-solid fa-droplet fa-fw mr-2"></i> Water</a></li><?php endif ?>
                            <li><a href="<?= isFeatureUnlocked('snowmaking') ? '/snowmaking' : '/achievements' ?>" class="<?= isFeatureUnlocked('snowmaking') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-snowflake mr-1"></i>Snowmaking</a></li>
                            <li><a href="<?= isFeatureUnlocked('night_skiing') ? '/night-skiing' : '/achievements' ?>" class="<?= isFeatureUnlocked('night_skiing') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-moon mr-1"></i>Night Skiing</a></li>
                            <li><a href="<?= isFeatureUnlocked('scenic_lifts') ? '/scenic-lifts' : '/achievements' ?>" class="<?= isFeatureUnlocked('scenic_lifts') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-camera mr-1"></i>Scenic Lifts</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><i class="fa-solid fa-building mr-1"></i>Buildings</summary>
                        <ul class="bg-base-100 rounded-box shadow w-52 z-50">
                            <li><a href="/hotels"><i class="fa-solid fa-hotel mr-1"></i>Hotels</a></li>
                            <li><a href="/restaurants"><i class="fa-solid fa-utensils mr-1"></i>Restaurants</a></li>
                            <li><a href="/rentals"><i class="fa-solid fa-bag-shopping mr-1"></i>Ski Rentals</a></li>
                            <li><a href="<?= isFeatureUnlocked('retail') ? '/retail' : '/achievements' ?>" class="<?= isFeatureUnlocked('retail') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-store mr-1"></i>Retail</a></li>
                            <li><a href="/real-estate"><i class="fa-solid fa-city mr-1"></i>Real Estate</a></li>
                            <li><a href="<?= isFeatureUnlocked('transportation') ? '/transportation' : '/achievements' ?>" class="<?= isFeatureUnlocked('transportation') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-bus mr-1"></i>Transportation</a></li>
                            <li><a href="<?= isFeatureUnlocked('off_season') ? '/off-season' : '/achievements' ?>" class="<?= isFeatureUnlocked('off_season') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-sun mr-1"></i>Off-Season</a></li>
                            <li><a href="/ski-patrol"><i class="fa-solid fa-shield-halved mr-1"></i>Ski Patrol</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><i class="fa-solid fa-coins mr-1"></i>Operations</summary>
                        <ul class="bg-base-100 rounded-box shadow w-52 z-50">
                            <li><a href="/finances"><i class="fa-solid fa-coins mr-1"></i>Finances</a></li>
                            <li><a href="/bank"><i class="fa-solid fa-building-columns mr-1"></i>Bank</a></li>
                            <li><a href="/tickets"><i class="fa-solid fa-ticket mr-1"></i>Lift Tickets</a></li>
                            <li><a href="/staff"><i class="fa-solid fa-users mr-1"></i>Staff</a></li>
                            <li><a href="<?= isFeatureUnlocked('marketing') ? '/marketing' : '/achievements' ?>" class="<?= isFeatureUnlocked('marketing') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-bullhorn mr-1"></i>Marketing</a></li>
                            <li><a href="/ski-lessons"><i class="fa-solid fa-chalkboard-user mr-1"></i>Ski School</a></li>
                            <?php if (!isPageHidden('insurance')) : ?><li><a href="/insurance"><i class="fa-solid fa-shield-halved mr-1"></i>Insurance</a></li><?php endif ?>
                            <?php if (!isPageHidden('government')) : ?><li><a href="/government"><i class="fa-solid fa-building-columns mr-1"></i>Government</a></li><?php endif ?>
                            <li><a href="/environment"><i class="fa-solid fa-leaf mr-1"></i>Environment</a></li>
                            <li><a href="/equipment"><i class="fa-solid fa-shop mr-1"></i>Equipment Shop</a></li>
                        </ul>
                    </details>
                </li>
                <li>
                    <details>
                        <summary><i class="fa-solid fa-star mr-1"></i>More</summary>
                        <ul class="bg-base-100 rounded-box shadow w-52 z-50">
                            <li><a href="/weather"><i class="fa-solid fa-cloud-sun mr-1"></i>Weather</a></li>
                            <li><a href="/emergency"><i class="fa-solid fa-truck-medical mr-1"></i>Emergency</a></li>
                            <li><a href="<?= isFeatureUnlocked('tournaments') ? '/tournaments' : '/achievements' ?>" class="<?= isFeatureUnlocked('tournaments') ? '' : 'opacity-40' ?>"><i class="fa-solid fa-trophy mr-1"></i>Events</a></li>
                            <li><a href="/achievements"><i class="fa-solid fa-award mr-1"></i>Achievements</a></li>
                            <li><a href="/daily-bonus"><i class="fa-solid fa-fire mr-1"></i>Daily Bonus</a></li>
                            <li><a href="/leaderboard"><i class="fa-solid fa-trophy mr-1"></i>Leaderboard</a></li>
                            <li><a href="/activity"><i class="fa-solid fa-clock-rotate-left mr-1"></i>Activity Log</a></li>
                            <li><a href="https://wiki.ski-manager.net" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-book mr-1"></i>Wiki</a></li>
                            <li><a href="/genepis"><i class="fa-solid fa-seedling mr-1"></i>Génépis</a></li>
                            <?php if (auth()->loggedIn() && auth()->id() === 1) : ?><li><a href="/admin"><i class="fa-solid fa-shield-halved mr-1 text-error"></i>Admin</a></li><?php endif ?>
                            <li><a href="/settings"><i class="fa-solid fa-gear mr-1"></i>Settings</a></li>
                        </ul>
                    </details>
                </li>
            </ul>
        </div>
        <div class="navbar-end gap-2">
            <!-- Search -->
            <?php if (auth()->loggedIn()) : ?>
            <div class="dropdown dropdown-end">
                <div tabindex="0" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-search"></i></div>
                <div tabindex="0" class="dropdown-content mt-2 z-50">
                    <div class="card bg-base-100 shadow-xl w-72 p-3">
                        <input type="text" id="globalSearch" placeholder="Search pages... (Ctrl+K)" class="input input-bordered input-sm w-full" autocomplete="off" />
                        <div id="searchResults" class="mt-2 max-h-64 overflow-y-auto"></div>
                    </div>
                </div>
            </div>
            <?php endif ?>
            <!-- Theme Switcher -->
            <label class="toggle text-base-content">
                <input type="checkbox" id="themeToggle" value="winter" class="theme-controller" />
                <svg aria-label="sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2"></path><path d="M12 20v2"></path><path d="m4.93 4.93 1.41 1.41"></path><path d="m17.66 17.66 1.41 1.41"></path><path d="M2 12h2"></path><path d="M20 12h2"></path><path d="m6.34 17.66-1.41 1.41"></path><path d="m19.07 4.93-1.41 1.41"></path></g></svg>
                <svg aria-label="moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2" fill="none" stroke="currentColor"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"></path></g></svg>
            </label>
            <?php if (auth()->loggedIn()) : ?>
                <?php $__notifCount = unreadNotificationCount(auth()->id()); ?>
                <div class="dropdown dropdown-end">
                    <div tabindex="0" class="btn btn-ghost btn-sm btn-circle indicator">
                        <i class="fa-solid fa-bell"></i>
                        <?php if ($__notifCount > 0) : ?><span class="indicator-item badge badge-error badge-xs"><?= $__notifCount > 9 ? "9+" : $__notifCount ?></span><?php endif ?>
                    </div>
                    <div tabindex="0" class="dropdown-content card bg-base-100 shadow-xl z-50 w-72 mt-2">
                        <div class="card-body p-3">
                            <div class="flex items-center justify-between mb-2"><span class="font-bold text-sm">Notifications</span><?php if ($__notifCount > 0) : ?><a href="/notifications/read-all" class="link link-primary text-xs">Mark all read</a><?php endif ?></div>
                            <?php $__notifs = db_connect()->table("notifications")->where("user_id", auth()->id())->orderBy("created_at", "DESC")->limit(5)->get()->getResultArray(); ?>
                            <?php if (empty($__notifs)) : ?><p class="text-xs text-base-content/40 text-center py-3">No notifications</p>
                            <?php else : ?><div class="space-y-1"><?php foreach ($__notifs as $__n) : ?><a href="<?= $__n["link"] ?? "/notifications" ?>" class="flex items-start gap-2 p-1.5 rounded-lg hover:bg-base-200 <?= $__n["is_read"] ? "opacity-50" : "" ?>"><i class="<?= $__n["icon"] ?> text-xs mt-0.5 w-4 text-center shrink-0"></i><div class="flex-1 min-w-0"><div class="text-xs font-semibold truncate"><?= esc($__n["title"]) ?></div><div class="text-xs text-base-content/50 truncate"><?= esc($__n["message"]) ?></div></div></a><?php endforeach ?></div><?php endif ?>
                            <a href="/notifications" class="btn btn-ghost btn-xs w-full mt-2">View All</a>
                        </div>
                    </div>
                </div>
                <div class="dropdown dropdown-end"><div tabindex="0" role="button" class="btn btn-ghost btn-sm"><i class="fa-solid fa-user-circle mr-1"></i><?= auth()->user()->username ?></div><ul tabindex="0" class="dropdown-content menu bg-base-100 rounded-box shadow w-48 z-50 mt-2"><li><a href="/dashboard"><i class="fa-solid fa-gauge-high mr-1"></i>Dashboard</a></li><li><a href="/account"><i class="fa-solid fa-gear mr-1"></i>Account</a></li><li><a href="/logout" class="text-error"><i class="fa-solid fa-right-from-bracket mr-1"></i>Logout</a></li></ul></div>
            <?php else : ?>
                <a href="/login" class="btn btn-ghost btn-sm"><i class="fa-solid fa-right-to-bracket mr-1"></i>Log in</a>
                <a href="/register" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus mr-1"></i>Play Free</a>
            <?php endif ?>
        </div>
    </div>
    </nav>

    <!-- Content -->
    <main class="flex-1" id="main-content" role="main">
    <?php if (auth()->loggedIn()) : ?>
    <div class="bg-base-100 border-b border-base-300 px-4 py-1.5 text-xs">
        <div class="max-w-7xl mx-auto flex items-center gap-3 md:gap-4 overflow-x-auto stats-bar scrollbar-none">
            <?php
                $__db = db_connect();
                $__fin = $__db->table("player_finances")->where("user_id", auth()->id())->get()->getRowArray();
                $__cash = $__fin ? (int)$__fin["cash"] : (int) (match(session("difficulty") ?? "standard") { "easy" => 1000000, "hard" => 200000, default => 500000 });
                $__weather = $__db->table("weather")->orderBy("game_day", "DESC")->limit(1)->get()->getRowArray();
                $__snow = $__weather ? (int)$__weather["snow_base"] : 0;
                $__genepis = $__db->table("genepis")->where("user_id", auth()->id())->get()->getRowArray();
                $__gbal = $__genepis ? (int)$__genepis["balance"] : 0;
                $__gameDay = max(1, (int)((strtotime(date("Y-m-d")) - strtotime("2026-06-01")) / 86400) + 1);
                $__rep = $__fin ? (int)($__fin["reputation"] ?? 0) : 0;
                $__rating = resortRating(auth()->id());
            ?>
            <span class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-money-bill-wave text-success"></i> <?= currency($__cash) ?></span>
            <span class="text-base-content/40 hidden md:inline">|</span>
            <span class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-snowflake text-info"></i> <?= snow($__snow) ?></span>
            <span class="text-base-content/40 hidden md:inline">|</span>
            <span class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-star text-warning"></i> <?= $__rep ?> rep</span>
            <span class="text-base-content/40 hidden md:inline">|</span>
            <span class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-calendar text-primary"></i> Day <?= $__gameDay ?>/135</span>
            <span class="text-base-content/40 hidden md:inline">|</span>
            <span class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-people-group"></i> <?= $__fin ? number_format((int)($__fin["total_income"] ?? 0) / max(1, $__gameDay) / 15) : 0 ?> visitors</span>
            <span class="text-base-content/40 hidden md:inline">|</span>
            <span class="flex items-center gap-1 shrink-0"><i class="fa-solid fa-seedling text-success"></i> <?= number_format($__gbal) ?></span>
            <span class="text-base-content/40 hidden md:inline">|</span>
            <span class="flex items-center gap-0.5 shrink-0"><?php for ($__i = 1; $__i <= 5; $__i++) : ?><i class="fa-solid fa-star text-xs <?= $__i <= $__rating["stars"] ? "text-warning" : "text-base-content/40" ?>"></i><?php endfor ?></span>
        </div>
    </div>
    <?php endif ?>
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Footer -->
    <footer class="bg-base-100" role="contentinfo" aria-label="Site footer">
        <div class="max-w-6xl mx-auto p-10">
            <div class="flex flex-col md:flex-row justify-between gap-8">
                <div>
                    <h6 class="text-sm font-semibold uppercase tracking-wider text-base-content/50 mb-3">Game</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/register" class="link link-hover"><i class="fa-solid fa-play mr-1"></i>Play Now</a></li>
                        <li><a href="/leaderboard" class="link link-hover"><i class="fa-solid fa-trophy mr-1"></i>Leaderboard</a></li>
                        <li><a href="/updates" class="link link-hover"><i class="fa-solid fa-newspaper mr-1"></i>Updates</a></li>
                        <li><a href="https://wiki.ski-manager.net" target="_blank" rel="noopener noreferrer" class="link link-hover"><i class="fa-solid fa-book mr-1"></i>Wiki</a></li>
                    </ul>
                </div>
                <div>
                    <h6 class="text-sm font-semibold uppercase tracking-wider text-base-content/50 mb-3">Support</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/contact" class="link link-hover"><i class="fa-solid fa-envelope mr-1"></i>Contact</a></li>
                        <li><a href="/faq" class="link link-hover"><i class="fa-solid fa-circle-question mr-1"></i>FAQ</a></li>
                        <li><a href="/bugs" class="link link-hover"><i class="fa-solid fa-bug mr-1"></i>Report a Bug</a></li>
                    </ul>
                </div>
                <div>
                    <h6 class="text-sm font-semibold uppercase tracking-wider text-base-content/50 mb-3">Legal</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/terms" class="link link-hover"><i class="fa-solid fa-file-contract mr-1"></i>Terms of Service</a></li>
                        <li><a href="/privacy" class="link link-hover"><i class="fa-solid fa-shield-halved mr-1"></i>Privacy Policy</a></li>
                        <li><a href="/cookies" class="link link-hover"><i class="fa-solid fa-cookie-bite mr-1"></i>Cookie Policy</a></li>
                        <li><a href="/disclaimer" class="link link-hover"><i class="fa-solid fa-circle-info mr-1"></i>Disclaimer</a></li>
                        <li><a href="/sitemap" class="link link-hover"><i class="fa-solid fa-sitemap mr-1"></i>Sitemap</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-base-300">
            <div class="flex flex-col md:flex-row items-center justify-between p-4 max-w-6xl mx-auto text-sm text-base-content/60">
                <p><i class="fa-solid fa-person-skiing mr-1"></i><span class="font-bold">Ski Manager</span> &copy; <?= date('Y') ?> - Build. Manage. Dominate the slopes.</p>
                <div class="flex gap-4 mt-2 md:mt-0">
                    <a href="https://discord.gg/TyEnFdfd8w" target="_blank" rel="noopener noreferrer" class="link link-hover"><i class="fa-brands fa-discord mr-1"></i>Discord</a>
                </div>
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
