<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="carboncloud">
<head>
    <meta charset="UTF-8">
    <!-- Hotjar Suppression & Protection -->
    <script>
    (function () {
      window.hj = function () {};
      window._hjSettings = { hjid: 0, hjsv: 0 };
      try {
        Object.freeze(window._hjSettings);
        Object.freeze(window.hj);
      } catch (e) {}

      var isHotjar = function (s) {
        return typeof s === 'string' && /hotjar\.(com|io)/i.test(s);
      };

      if (typeof window.fetch === 'function') {
        var _fetch = window.fetch;
        window.fetch = function (resource, init) {
          var url = typeof resource === 'string' ? resource : (resource && resource.url);
          if (isHotjar(url)) return Promise.reject(new Error('Hotjar disabled'));
          return _fetch.apply(this, arguments);
        };
      }

      if (typeof window.XMLHttpRequest !== 'undefined') {
        var _open = XMLHttpRequest.prototype.open;
        XMLHttpRequest.prototype.open = function (method, url) {
          if (isHotjar(url)) {
            try { this.abort(); } catch (e) {}
            return;
          }
          return _open.apply(this, arguments);
        };
      }

      var origCreate = document.createElement;
      document.createElement = function (tagName, options) {
        var el = origCreate.call(document, tagName, options);
        if (typeof tagName === 'string' && tagName.toLowerCase() === 'script') {
          var origSetAttribute = el.setAttribute;
          el.setAttribute = function (name, val) {
            if (name === 'src' && isHotjar(val)) return;
            return origSetAttribute.call(el, name, val);
          };
          try {
            Object.defineProperty(el, 'src', {
              set: function (url) {
                if (isHotjar(url)) return;
                el.setAttribute('src', url);
              },
              get: function () {
                return el.getAttribute('src') || '';
              },
              configurable: true,
            });
          } catch (e) {}
        }
        return el;
      };

      var removeHj = function (node) {
        if (!node || node.nodeType !== 1) return;
        var id = node.id || '';
        var className = typeof node.className === 'string' ? node.className : '';
        var src = node.getAttribute ? (node.getAttribute('src') || '') : '';
        var testId = node.getAttribute ? (node.getAttribute('data-testid') || '') : '';
        if (
          /^_?hj/i.test(id) ||
          /hj-survey|hj-|_hj/i.test(className) ||
          testId.indexOf('hotjar') !== -1 ||
          /hotjar/i.test(src) ||
          /hotjar/i.test(id)
        ) {
          try { node.remove(); } catch (e) {}
        }
      };

      if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function (mutations) {
          for (var i = 0; i < mutations.length; i++) {
            var added = mutations[i].addedNodes;
            for (var j = 0; j < added.length; j++) {
              removeHj(added[j]);
            }
          }
        });
        if (document.documentElement) {
          observer.observe(document.documentElement, { childList: true, subtree: true });
        } else {
          document.addEventListener('DOMContentLoaded', function () {
            observer.observe(document.documentElement, { childList: true, subtree: true });
          });
        }
      }
    })();
    </script>
    <!-- Sentry Observability & Session Replay -->
    <?php $__sentryDsn = function_exists('env') ? env('SENTRY_DSN') : getenv('SENTRY_DSN'); ?>
    <?php if (!empty($__sentryDsn)) : ?>
    <!-- Deferred so it never holds up the first paint. The init runs from the bundle's own onload:
         by then window.Sentry is the full SDK, not a stub another Sentry script may have set first -->
    <script>
      window.__initSentry = function () {
      if (window.Sentry && typeof Sentry.browserTracingIntegration === 'function') {
        Sentry.init({
          dsn: <?= json_encode($__sentryDsn) ?>,
          environment: <?= json_encode(ENVIRONMENT ?? 'production') ?>,
          integrations: [
            Sentry.browserTracingIntegration(),
            Sentry.replayIntegration(),
          ],
          tracesSampleRate: 0.1,
          replaysSessionSampleRate: 0.1,
          replaysOnErrorSampleRate: 1.0,
          beforeSend: function (event, hint) {
            // Third-party scripts (Google Funding Choices, Hotjar) reject promises
            // with `undefined` when their in-flight requests are aborted (e.g. on
            // page navigation). These carry no stack trace and are not actionable.
            // Drop an event only when ALL of the following hold, so rejections from
            // our own code (including string reasons) are still reported:
            //  - it came from the global onunhandledrejection handler
            //  - the actual rejection reason is `undefined` (not a string)
            //  - there is no stack trace
            //  - a failed/aborted fetch/XHR breadcrumb to a known vendor URL exists
            var VENDOR_URL_PATTERNS = [
              /^https:\/\/fundingchoicesmessages\.google\.com\//,
              /^https:\/\/content\.hotjar\.io\//,
            ];
            var ex = event && event.exception && event.exception.values && event.exception.values[0];
            var isUndefinedRejection =
              !!hint &&
              Object.prototype.hasOwnProperty.call(hint, 'originalException') &&
              hint.originalException === undefined;
            var isStackless = !(ex && ex.stacktrace && ex.stacktrace.frames && ex.stacktrace.frames.length);
            var hasFailedVendorRequest = (event.breadcrumbs || []).some(function (crumb) {
              if (!crumb || (crumb.category !== 'xhr' && crumb.category !== 'fetch')) return false;
              var data = crumb.data || {};
              var failed = crumb.level === 'error' || data.status_code === 0;
              return failed && typeof data.url === 'string' && VENDOR_URL_PATTERNS.some(function (re) {
                return re.test(data.url);
              });
            });
            if (
              ex &&
              ex.mechanism && ex.mechanism.type === 'onunhandledrejection' &&
              isUndefinedRejection &&
              isStackless &&
              hasFailedVendorRequest
            ) {
              return null;
            }
            return event;
          },
        });
        <?php if (function_exists('auth') && auth()->loggedIn()) : ?>
        Sentry.setUser({
          id: <?= json_encode((string) auth()->id(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
          username: <?= json_encode(auth()->user()->username ?? 'player', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        });
        <?php endif; ?>
      }
      };
    </script>
    <script
      defer
      src="https://browser.sentry-cdn.com/8.38.0/bundle.tracing.replay.min.js"
      crossorigin="anonymous"
      onload="window.__initSentry()"
    ></script>
    <?php endif; ?>
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
    <meta name="color-scheme" content="dark light">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <title><?= $this->renderSection('title') ?> - Ski Manager</title>
    <link rel="preload" href="/css/style.css?v=<?= @filemtime(FCPATH . "css/style.css") ?>" as="style">
    <?php if (auth()->loggedIn()) : ?>
    <?php $__mapPrefetch = (db_connect()->table("player_finances")->where("user_id", auth()->id())->get()->getRowArray()["resort_map"] ?? "ParkCity"); ?>
    <link rel="prefetch" href="/img/<?= $__mapPrefetch ?>_low.jpg" as="image">
    <link rel="prefetch" href="/img/<?= $__mapPrefetch ?>_med.jpg" as="image">
    <?php endif ?>
    <link rel="stylesheet" href="/css/style.css?v=<?= @filemtime(FCPATH . "css/style.css") ?>" fetchpriority="high">
    <?= \App\Libraries\Islands::headTags(auth()->loggedIn()) ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://kit.fontawesome.com" crossorigin>
    <link rel="preconnect" href="https://ka-f.fontawesome.com" crossorigin>
    <script async src="https://kit.fontawesome.com/e8108d3d5f.js" crossorigin="anonymous"></script>
    <script>
        var saved = localStorage.getItem('theme') || 'carboncloud';
        document.documentElement.setAttribute('data-theme', saved);
        var metaScheme = document.querySelector('meta[name="color-scheme"]');
        if (metaScheme) {
            metaScheme.content = (saved === 'winter' || saved === 'light') ? 'light dark' : 'dark light';
        }
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
:root {
    accent-color: #3b82f6;
    scrollbar-width: thin;
    scrollbar-gutter: stable;
}

/* Modern Typography: Balance short headings and prevent orphans in paragraphs */
h1, h2, h3, h4, h5, h6 {
    text-wrap: balance;
}
p, blockquote, .pretty-text {
    text-wrap: pretty;
}

/* Form UX: Post-interaction validation styling */
input:user-invalid, select:user-invalid, textarea:user-invalid {
    border-color: var(--fallback-er, oklch(var(--er) / 1));
}

/* Native Dialog Backdrop Styling */
dialog::backdrop {
    background-color: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
}

/* Off-screen Content Deferral (defer-rendering-heavy-content) */
.content-visibility-auto {
    content-visibility: auto;
    contain-intrinsic-size: auto 200px;
}

/* Shared identity: condensed trail-sign type */
:root { --sign: 'Inter', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
body { font-family: var(--sign); }

/* Navbar */
.site-nav { min-height: 3.5rem; padding-top: 0.25rem; padding-bottom: 0.25rem; }
.brand { display: inline-flex; align-items: center; gap: 0.55rem; padding: 0 0.5rem; color: inherit; text-decoration: none; }
.brand-mark { width: 30px; height: 30px; flex-shrink: 0; }
.brand-name { font-family: var(--sign); font-size: 1.25rem; font-weight: 700; letter-spacing: -0.02em; line-height: 1; white-space: nowrap; }
/* Keep brand + Log in + Play free on one line without overflowing small phones */
@media (max-width: 420px) {
    .site-nav .brand { padding: 0 0.25rem; gap: 0.4rem; }
    .brand-name { font-size: 1.125rem; }
}
/* Touch screens: the logo link is a 44px tap target like the buttons beside it */
@media (pointer: coarse) {
    .site-nav .brand, .site-footer .brand { min-height: 2.75rem; }
}
@media (max-width: 380px) {
    .site-nav { padding-left: 0.25rem; padding-right: 0.25rem; }
    .site-nav .brand { gap: 0.4rem; }
    .brand-name { font-size: 1rem; }
}

/* Resort stats bar under the navbar */
.stats-bar { gap: 0 !important; }
.stats-bar .stat-pill {
    border: 0; border-radius: 0; background: none; padding: 0.1rem 0.85rem; gap: 0.4rem;
    color: color-mix(in oklab, var(--color-base-content) 80%, transparent);
    border-left: 1px solid var(--color-base-300); font-size: 0.75rem;
}
.stats-bar .stat-pill:first-child { border-left: 0; padding-left: 0; }
.stats-bar .stat-pill:not([href="/resort-analysis"]) i { color: color-mix(in oklab, var(--color-base-content) 70%, transparent) !important; }
.stats-bar .stat-pill .font-mono { font-family: inherit; font-variant-numeric: tabular-nums; }
.stats-bar .stat-pill:hover { color: var(--color-base-content); }

/* Footer */
.site-footer h6 { font-size: 0.8125rem; font-weight: 600; color: var(--color-base-content); margin-bottom: 0.75rem; }
.site-footer ul a { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.site-footer ul a:hover { color: var(--color-base-content); }
@media (pointer: coarse) { .site-footer ul a { display: inline-flex; align-items: center; min-height: 2.75rem; } .site-footer ul.space-y-2 > * + * { margin-top: 0; } }

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


/* Accessible Motion: Respect user preference for reduced motion without breaking functional UI */
@media (prefers-reduced-motion: reduce) {
    html:focus-within {
        scroll-behavior: auto;
    }
    .animate-fade-in-up,
    .animate-fade-in,
    .animate-slide-in-right,
    .animate-scale-in,
    .animate-pulse-soft,
    .dropdown-content {
        animation: none !important;
    }
}
</style>
<?php
    // AdSense policy: no ads on screens without publisher content (sign-in, sign-up, activation,
    // locked features). Pages can also opt out with a "no_ads" section.
    $__path = trim(service('request')->getUri()->getPath(), '/');
    $__noAds = (bool) preg_match('#^(login|register|logout|auth|verify-magic-link|demo/save|complete-profile)(/|$)#', $__path);
    if (!$__noAds) { ob_start(); echo $this->renderSection('no_ads'); $__noAds = trim((string) ob_get_clean()) !== ''; }
    ?>
    <?php if (!$__noAds) : ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-5636695863753930" crossorigin="anonymous"></script>
    <?php endif ?>
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
        $__isAdmin = \App\Libraries\Shell::isAdmin();
        $__bonusDue = \App\Libraries\Shell::bonusDue();
        $__nav = \App\Libraries\Shell::NAV;
        // The React AppShell island (HeroUI 3.2.7) renders for all players (guests and signed-in)
        $__shellIslands = true;
        $__flash = \App\Libraries\Shell::takeFlash();
        $__shell = \App\Libraries\Shell::props($__currentPath, $__flash);

        $__navItem = function (array $it) use ($__isAdmin, $__bonusDue) {
            $r = \App\Libraries\Shell::resolveItem($it, $__isAdmin, $__bonusDue);
            if ($r === null) return '';
            $cls = ($r['locked'] || $r['flagOff']) ? 'text-muted' : 'text-foreground';
            $badge = $r['bonusDue'] ? ' <span class="rounded-full bg-warning px-1.5 text-xs font-semibold text-warning-foreground">Due</span>' : '';
            $target = $r['external'] ? ' target="_blank" rel="noopener noreferrer"' : '';
            $title = $r['flagOff'] ? ' title="Switched off for players (feature flag)"' : '';
            return '<a href="' . esc($r['href'], 'attr') . '"' . $target . $title . ' class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium hover:bg-default transition-colors ' . $cls . '">'
                . '<i class="fa-solid ' . esc($r['icon'], 'attr') . ' fa-fw text-accent"></i><span>' . esc($r['label']) . '</span>' . $badge . '</a>';
        };
    ?>
    <?php if (session()->get('is_demo') && !featureEnabled('demo_v2')) : ?>
    <div class="bg-primary text-primary-foreground px-4 py-2 text-xs flex flex-wrap items-center justify-between gap-2 shadow-md sticky top-0 z-[10000]">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-md bg-amber-400 px-2 py-0.5 text-xs font-bold font-mono text-neutral-900">DEMO SANDBOX</span>
            <span class="font-medium">Park City Mountain Base &bull; 4 Lifts &bull; 8 Slopes &bull; 250,000 € Capital</span>
        </div>
        <div class="flex items-center gap-2">
            <form action="/demo/reset" method="post" onsubmit="return confirm('Your demo resort goes back to its starting setup. Your progress is lost.')">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex min-h-8 items-center rounded-lg px-2.5 py-1 text-xs font-medium text-primary-foreground hover:bg-white/10">
                    <i class="fa-solid fa-arrow-rotate-left mr-1"></i> Reset
                </button>
            </form>
            <a href="/demo/save" class="inline-flex min-h-8 items-center gap-1 rounded-lg bg-amber-400 px-3 py-1 text-xs font-bold shadow-sm text-neutral-900 hover:bg-amber-300">
                <i class="fa-solid fa-cloud-arrow-up"></i> Save My Resort & Claim Capital
            </a>
            <form action="/demo/exit" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex size-8 items-center justify-center rounded-lg text-primary-foreground/80 hover:text-white hover:bg-white/10" title="Exit Demo" aria-label="Exit Demo">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endif ?>
    <?php if ($__isAdmin && function_exists('isMaintenanceMode') && isMaintenanceMode()) : ?>
    <div class="bg-amber-600 text-white px-4 py-2 text-xs sm:text-sm font-semibold flex flex-wrap items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
            <span><strong>MAINTENANCE ACTIVE (Season 2 In-Dev):</strong> Public site is paused for 4 days with HTTP 503. You are browsing with admin clearance.</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-md bg-black/25 px-2 py-0.5 text-xs text-white font-bold">Admin Clearance</span>
            <a href="/admin" class="inline-flex min-h-8 items-center rounded-lg bg-neutral-900 px-3 py-1 text-xs font-semibold text-white hover:bg-neutral-800">Admin Controls</a>
        </div>
    </div>
    <?php endif ?>
    <?php ob_start() ?>
    <header class="site-nav border-b border-border bg-background" style="z-index:9999; position:sticky; top:0"><nav aria-label="Main navigation" class="mx-auto flex min-h-14 max-w-7xl items-center gap-1 px-2 sm:px-4">
        <div class="lg:hidden">
            <details class="relative">
                <summary class="flex min-h-11 min-w-11 items-center justify-center rounded-lg hover:bg-default cursor-pointer list-none" aria-label="Open menu"><i class="fa-solid fa-bars text-lg" aria-hidden="true"></i></summary>
                <div class="absolute left-0 mt-2 w-64 rounded-xl border border-border bg-popover p-2 shadow-2xl z-50 max-h-[80vh] overflow-y-auto">
                    <a href="/dashboard" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium hover:bg-default transition-colors"><i class="fa-solid fa-gauge-high fa-fw mr-1 text-accent"></i>Dashboard</a>
                    <?php foreach ($__nav as $__secName => $__sec) : ?>
                        <div class="text-xs font-semibold uppercase tracking-wider text-muted px-3 pt-2 pb-1"><?= esc($__secName) ?></div>
                        <?php foreach ($__sec['items'] as $__it) : echo $__navItem($__it); endforeach ?>
                    <?php endforeach ?>
                    <?php if ($__isAdmin) : ?><div class="text-xs font-semibold uppercase tracking-wider text-muted px-3 pt-2 pb-1">Admin</div><a href="/admin" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-danger hover:bg-default transition-colors"><i class="fa-solid fa-shield-halved fa-fw mr-1"></i>Admin Panel</a><?php endif ?>
                </div>
            </details>
        </div>
        <a href="/" class="brand flex min-h-11 items-center gap-2 rounded-lg px-2 font-semibold" aria-label="Ski Manager home">
            <svg class="brand-mark size-7 shrink-0" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="6" fill="#1d232b"/><path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0"/><path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35"/><path d="M20 9 L19 25" stroke="#2f6fd6" stroke-width="1.6" stroke-linecap="round"/></svg>
            <span class="brand-name hidden text-lg tracking-tight sm:inline">Ski Manager</span>
        </a>
        <div class="hidden items-center gap-1 lg:flex">
            <a href="/" class="flex min-h-9 items-center rounded-lg px-3 text-sm font-medium hover:bg-default transition-colors <?= $__currentPath === '/' ? 'bg-default font-semibold text-accent-soft-foreground' : '' ?>">Home</a>
            <a href="/dashboard" class="flex min-h-9 items-center rounded-lg px-3 text-sm font-medium hover:bg-default transition-colors <?= $__currentPath === '/dashboard' ? 'bg-default font-semibold text-accent-soft-foreground' : '' ?>">Dashboard</a>
            <?php foreach ($__nav as $__secName => $__sec) : ?>
            <details class="relative">
                <summary class="flex min-h-9 items-center gap-1.5 rounded-lg px-3 text-sm font-medium hover:bg-default cursor-pointer list-none select-none transition-colors">
                    <span><?= esc($__secName) ?></span>
                    <i class="fa-solid fa-chevron-down text-xs text-muted"></i>
                </summary>
                <div class="absolute left-0 mt-2 w-56 rounded-xl border border-border bg-popover p-1.5 shadow-2xl z-50">
                    <?php foreach ($__sec['items'] as $__it) : echo $__navItem($__it); endforeach ?>
                </div>
            </details>
            <?php endforeach ?>
            <?php if ($__isAdmin) : ?><a href="/admin" class="flex min-h-9 items-center rounded-lg px-3 text-sm font-medium text-danger hover:bg-default transition-colors">Admin</a><?php endif ?>
        </div>
        <div class="ml-auto flex items-center gap-1">
            <?php if (auth()->loggedIn()) : ?>
                <a href="/settings" class="flex min-h-9 items-center gap-1 rounded-lg px-2.5 text-sm font-medium hover:bg-default transition-colors">
                    <i class="fa-solid fa-user-circle"></i>
                    <span><?= esc(auth()->user()->username ?? 'Account') ?></span>
                </a>
            <?php else : ?>
                <div class="flex items-center gap-1.5 ml-1">
                    <a href="/login" class="flex min-h-11 sm:min-h-9 items-center justify-center rounded-lg px-3 text-xs sm:text-sm font-medium hover:bg-default transition-colors">Log in</a>
                    <a href="/register" class="flex min-h-11 sm:min-h-9 items-center justify-center rounded-lg bg-primary px-3 text-xs sm:text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors shadow-sm">Play free</a>
                </div>
            <?php endif ?>
        </div>
    </nav></header>
    <?php $__fallbackHeader = ob_get_clean(); ?>
    <div style="z-index:9999; position:sticky; top:0"><?= island('AppShell', $__shell, $__fallbackHeader) ?></div>

    <!-- Content -->
    <main class="flex-1 pb-20 md:pb-6" id="main-content" role="main">
    <?php if (session("admin_original_id")) : ?>
    <div class="bg-warning text-warning-content text-center py-2 text-sm font-semibold sticky top-0 z-50">
        <i class="fa-solid fa-user-secret mr-1"></i> Impersonating <?= esc(auth()->user()->username ?? "user") ?>. <a href="/admin/stop-impersonate" class="underline font-bold">Return to Admin</a>
    </div>
    <?php endif ?>
    <?php if (auth()->loggedIn()) : ?>
    <?php ob_start() ?>
    <div class="bg-base-100 border-b border-base-300 px-3 py-1.5 text-xs">
        <div class="max-w-7xl mx-auto flex items-center gap-2 overflow-x-auto scrollbar-none py-0.5 stats-bar">
            <?php
                $__db = db_connect();
                $__fin = $__db->table("player_finances")->where("user_id", auth()->id())->get()->getRowArray();
                $__cash = $__fin ? (int)$__fin["cash"] : (int) (match(session("difficulty") ?? "standard") { "easy" => 1000000, "hard" => 200000, default => 500000 });
                $__weather = getCurrentWeather();
                $__snow = $__weather['snow_base'];
                $__currentTemp = $__weather['temp'];
                $__genepis = $__db->table("genepis")->where("user_id", auth()->id())->get()->getRowArray();
                $__gbal = $__genepis ? (int)$__genepis["balance"] : 0;
                $__gameDay = $__weather['game_day'];
                $__rep = $__fin ? (int)($__fin["reputation"] ?? 0) : 0;
                $__rating = resortRating(auth()->id());
            ?>
            <a href="/finances" title="Cash balance: open Finances" class="stat-pill text-success border-success/20 ">
                <i class="fa-solid fa-money-bill-wave"></i>
                <span class="font-bold font-mono"><?= currency($__cash) ?></span>
            </a>
            <a href="/weather" title="<?= $__weather ? esc($__weather['condition_name']) : 'Weather' ?>: open the forecast" class="stat-pill text-info border-info/20 ">
                <i class="fa-solid fa-<?= $__currentTemp <= -5 ? 'snowflake' : ($__currentTemp <= 0 ? 'cloud text-base-content/50' : 'sun text-warning') ?>"></i>
                <span class="font-bold"><?= temp($__currentTemp) ?></span>
            </a>
            <a href="/snowmaking" title="Snow base: open Snowmaking" class="stat-pill text-info border-info/20 ">
                <i class="fa-solid fa-layer-group"></i>
                <span class="font-medium"><?= snow($__snow) ?></span>
            </a>
            <span title="Season Day <?= $__gameDay ?> of <?= getSeasonLength() ?>" class="stat-pill text-base-content/80 border-base-300">
                <i class="fa-solid fa-calendar-day text-primary"></i>
                <span>Day <b class="font-bold"><?= $__gameDay ?></b>/<?= getSeasonLength() ?></span>
            </span>
            <a href="/genepis" title="Génépis balance: open the Génépis shop" class="stat-pill text-success border-success/20 ">
                <i class="fa-solid fa-seedling"></i>
                <span class="font-bold font-mono"><?= number_format($__gbal) ?></span>
            </a>
            <a href="/resort-analysis" title="Resort Rating: <?= $__rating['stars'] ?>/5 Stars" class="stat-pill text-warning border-warning/20  gap-0.5">
                <?php for ($__i = 1; $__i <= 5; $__i++) : ?>
                    <i class="fa-solid fa-star text-[10px] <?= $__i <= $__rating["stars"] ? "text-warning" : "text-base-content/20" ?>"></i>
                <?php endfor ?>
            </a>
        </div>
    </div>
    <?= island('StatsBar', $__shell['stats'], ob_get_clean()) ?>
    <?php endif ?>

    <?php
        $__flashError = $__flash['error'] ?? null;
        $__flashErrors = $__flash['errors'] ?? null;
        $__flashSuccess = $__flash['success'] ?? $__flash['info'] ?? null;
        $__flashWarning = $__flash['warning'] ?? null;
    ?>
    <?php if ($__shellIslands) : ?><noscript><?php endif ?>
    <?php if ($__flashError || $__flashErrors || $__flashSuccess || $__flashWarning) : ?>
    <div class="max-w-7xl mx-auto px-4 lg:px-8 pt-4 pb-1">
        <?php if ($__flashError) : ?>
            <div class="flex items-center gap-3 rounded-xl border border-danger/30 bg-danger/10 p-3 text-danger shadow-sm mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation text-lg shrink-0"></i>
                <span class="text-sm font-medium"><?= esc($__flashError) ?></span>
            </div>
        <?php endif ?>
        <?php if ($__flashErrors && is_array($__flashErrors)) : ?>
            <div class="flex items-start gap-3 rounded-xl border border-danger/30 bg-danger/10 p-3 text-danger shadow-sm mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation text-lg shrink-0 mt-0.5"></i>
                <div class="text-sm space-y-1">
                    <?php foreach ($__flashErrors as $__err) : ?>
                        <div><?= esc($__err) ?></div>
                    <?php endforeach ?>
                </div>
            </div>
        <?php endif ?>
        <?php if ($__flashSuccess) : ?>
            <div class="flex items-center gap-3 rounded-xl border border-success/30 bg-success/10 p-3 text-success shadow-sm mb-3" role="status">
                <i class="fa-solid fa-circle-check text-lg shrink-0"></i>
                <span class="text-sm font-medium"><?= esc($__flashSuccess) ?></span>
            </div>
        <?php endif ?>
        <?php if ($__flashWarning) : ?>
            <div class="flex items-center gap-3 rounded-xl border border-warning/30 bg-warning/10 p-3 text-warning shadow-sm mb-3" role="alert">
                <i class="fa-solid fa-triangle-exclamation text-lg shrink-0"></i>
                <span class="text-sm font-medium"><?= esc($__flashWarning) ?></span>
            </div>
        <?php endif ?>
    </div>
    <?php endif ?>
    <?php if ($__shellIslands) : ?></noscript><?php endif ?>

        <?= $this->renderSection('content') ?>
    </main>

    <!-- Footer -->
    <footer class="site-footer bg-base-100 border-t border-base-300" role="contentinfo" aria-label="Site footer">
        <div class="max-w-6xl mx-auto px-4 pt-10 pb-6">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
                <!-- Brand -->
                <div class="col-span-2 md:col-span-1">
                    <a href="/" class="brand mb-3" style="padding:0"><svg class="brand-mark" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="6" fill="#1d232b"/><path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0"/><path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35"/><path d="M20 9 L19 25" stroke="#2f6fd6" stroke-width="1.6" stroke-linecap="round"/></svg><span class="brand-name">Ski Manager</span></a>
                    <p class="text-sm text-base-content/70 mb-4">A free ski resort management game. Build lifts and runs on real mountains and try to keep the place profitable.</p>
                    <div class="flex gap-3">
                        <a href="https://discord.gg/u3GzGt8K6a" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-foreground" title="Discord" aria-label="Ski Manager on Discord"><i class="fa-brands fa-discord text-lg" aria-hidden="true"></i></a>
                        <a href="https://github.com/MontaraGroup/Ski-Manager" target="_blank" rel="noopener noreferrer" class="flex size-9 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-foreground" title="GitHub" aria-label="Ski Manager source code on GitHub"><i class="fa-brands fa-github text-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
                <!-- Game -->
                <div>
                    <h6 class="">Game</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/register">Play Now</a></li>
                        <li><a href="/leaderboard">Leaderboard</a></li>
                        <li><a href="/updates">Updates</a></li>
                        <li><a href="/roadmap">Roadmap</a></li>
                        <li><a href="https://wiki.ski-manager.net" target="_blank" rel="noopener noreferrer">Wiki</a></li>
                        <li><a href="/genepis">Genepis</a></li>
                    </ul>
                </div>
                <!-- Resources -->
                <div>
                    <h6 class="">Resources</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/guides">Guides</a></li>
                        <li><a href="/faq">FAQ</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/bugs">Report a Bug</a></li>
                        <li><a href="/about">About</a></li>
                    </ul>
                </div>
                <!-- Legal -->
                <div>
                    <h6 class="">Legal</h6>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/terms">Terms</a></li>
                        <li><a href="/privacy">Privacy</a></li>
                        <li><a href="/cookies">Cookies</a></li>
                        <li><a href="/disclaimer">Disclaimer</a></li>
                        <li><a href="/sitemap">Sitemap</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="border-t border-base-300">
            <div class="flex flex-col md:flex-row items-center justify-between px-4 py-3 max-w-6xl mx-auto text-xs text-base-content/70">
                <p>&copy; <?= date('Y') ?> Ski Manager · Free to play in your browser</p>
                <p class="mt-1 md:mt-0">Maps by <a href="https://skimap.com" target="_blank" rel="noopener noreferrer" class="link link-hover">Mapsynergy</a> &middot; v1.1</p>
            </div>
        </div>
    </footer>

    <script>
        (function() {
            var toggle = document.getElementById('themeToggle');
            if (toggle) {
                var current = document.documentElement.getAttribute('data-theme');
                toggle.checked = (current === 'winter');
                toggle.addEventListener('change', function() {
                    var theme = this.checked ? 'winter' : 'carboncloud';
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                });
            }
        })();
    </script>

<script>
document.querySelectorAll(".site-nav details").forEach(function(det) {
    det.addEventListener("toggle", function() {
        if (this.open) {
            document.querySelectorAll(".site-nav details").forEach(function(other) {
                if (other !== det) other.removeAttribute("open");
            });
        }
    });
});
document.addEventListener("click", function(e) {
    if (!e.target.closest(".site-nav details")) {
        document.querySelectorAll(".site-nav details[open]").forEach(function(d) {
            d.removeAttribute("open");
        });
    }
});
</script>
<dialog id="confirmModal" class="fixed inset-0 m-auto p-0 max-w-md w-[calc(100vw-2rem)] rounded-2xl border border-border bg-popover text-popover-foreground shadow-2xl backdrop:bg-black/60 backdrop:backdrop-blur-sm" aria-labelledby="confirmTitle">
    <div class="p-6 space-y-4">
        <h3 class="text-lg font-bold" id="confirmTitle">Confirm</h3>
        <p class="text-sm text-muted" id="confirmMessage"></p>
        <div class="flex items-center justify-end gap-2 pt-2">
            <button type="button" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold hover:bg-default" onclick="closeConfirm()">Cancel</button>
            <button type="button" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center rounded-xl bg-primary text-primary-foreground px-4 py-2 text-sm font-semibold hover:bg-primary/90" id="confirmYes">Confirm</button>
        </div>
    </div>
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
const searchPages=<?= json_encode(array_map(fn($p) => ["n" => $p[0], "u" => $p[1], "i" => $p[2]], \App\Libraries\Shell::SEARCH), JSON_HEX_TAG | JSON_UNESCAPED_UNICODE) ?>;
const si=document.getElementById("globalSearch"),sr=document.getElementById("searchResults");
if(si){
    si.addEventListener("input",function(){
        const q=this.value.toLowerCase().trim();
        const emptyEl=document.getElementById("searchEmpty");
        if(!q){
            sr.innerHTML="";
            if(emptyEl) emptyEl.classList.remove("hidden");
            return;
        }
        if(emptyEl) emptyEl.classList.add("hidden");
        const m=searchPages.filter(p=>p.n.toLowerCase().includes(q));
        sr.innerHTML=m.length?m.map(p=>"<a href=\""+p.u+"\" class=\"flex items-center gap-2 p-2 rounded-lg hover:bg-base-200 text-sm\"><i class=\"fa-solid "+p.i+" w-5 text-center text-base-content/50\"></i>"+p.n+"</a>").join(""):`<p class="text-xs text-base-content/70 text-center py-2">No results</p>`;
    });
    si.addEventListener("keydown",function(e){
        if(e.key==="Enter"){
            const first=sr.querySelector("a");
            if(first)window.location=first.href;
        }
    });
}
</script>
<button id="backToTop" type="button" aria-label="Back to top" onclick="window.scrollTo({top:0,behavior:'smooth'})" style="display:none;position:fixed;bottom:1.5rem;left:1.5rem;z-index:9990;width:2.75rem;height:2.75rem;border-radius:50%;border:none;cursor:pointer;font-size:1rem;box-shadow:0 2px 8px rgba(0,0,0,0.2);" class="inline-flex items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg hover:bg-primary/90"><i class="fa-solid fa-arrow-up" aria-hidden="true"></i></button>
<script>window.addEventListener("scroll",function(){document.getElementById("backToTop").style.display=window.scrollY>300?"flex":"none";});</script>
<script>
document.querySelectorAll("[role='status']").forEach(function(el){
    if (el.closest(".card-body") || el.closest("form")) return;
    setTimeout(function(){
        el.style.transition = "opacity 0.5s ease, transform 0.5s ease";
        el.style.opacity = "0";
        el.style.transform = "translateY(-8px)";
        setTimeout(function(){ el.remove(); }, 500);
    }, 4500);
});
</script>
<script>
document.querySelectorAll("form").forEach(function(f){
    f.addEventListener("submit",function(){
        var btn=f.querySelector("button[type=submit],button:not([type])");
        if(btn&&!f.dataset.confirm){btn.disabled=true;}
    });
});
</script>
<script>
document.querySelectorAll("[data-count]").forEach(function(el){
    var target=parseInt(el.dataset.count),current=0,step=Math.max(1,Math.floor(target/30));
    var timer=setInterval(function(){current+=step;if(current>=target){current=target;clearInterval(timer);}el.textContent=current.toLocaleString();},20);
});
</script>

<!-- Cookie Consent Banner -->
<div id="cookieConsent" class="fixed bottom-0 left-0 right-0 bg-base-200 border-t border-base-300 p-4 shadow-lg" style="z-index:99999;display:none;">
    <div class="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
        <p class="text-sm text-base-content/80">We use cookies for analytics and advertising. You can choose which to accept.</p>
        <div class="flex gap-2 shrink-0">
            <button onclick="acceptCookies('all')" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center rounded-xl bg-primary text-primary-foreground px-4 py-2 text-sm font-semibold hover:bg-primary/90">Accept All</button>
            <button onclick="acceptCookies('analytics')" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center rounded-xl border border-border bg-transparent px-4 py-2 text-sm font-semibold hover:bg-default">Analytics Only</button>
            <button onclick="acceptCookies('none')" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold hover:bg-default">Reject All</button>
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
    if(saved){acceptCookies(saved);return;}
    // Where Google's consent message runs (EEA, UK, Switzerland: gdprApplies), it asks for consent
    // itself, so don't ask twice. Elsewhere, or if it hasn't answered within 2s, show this banner.
    var decided=false, banner=document.getElementById('cookieConsent');
    function show(){if(!decided){decided=true;banner.style.display='block';}}
    var fallback=setTimeout(show,2000);
    (function waitForTcf(tries){
        if(typeof window.__tcfapi!=='function'){if(tries<20)setTimeout(function(){waitForTcf(tries+1);},100);return;}
        window.__tcfapi('addEventListener',2,function(tcData,ok){
            if(decided||!ok||!tcData)return;
            if(tcData.gdprApplies){decided=true;clearTimeout(fallback);}
            else{clearTimeout(fallback);show();}
        });
    })(0);
})();
</script>
<?php if (function_exists("featureEnabled") && featureEnabled("tooltips")) : ?>
<style>[data-tip]{position:relative;cursor:help}[data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#1d232a;color:#a6adbb;padding:6px 10px;border-radius:6px;font-size:11px;white-space:nowrap;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.3);pointer-events:none}[data-tip]:hover::before{content:"";position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1d232a;z-index:9999}</style>
<?php endif ?>

<script>
if ("serviceWorker" in navigator && location.hostname === "ski-manager.net") {
  window.addEventListener("load", function () {
    navigator.serviceWorker.register("/sw.js").catch(function (err) {
      var name = (err && err.name) || "Error";
      var message = (err && err.message) || String(err);
      console.warn("Service worker registration failed", err);
      if (window.Sentry) {
        try {
          window.Sentry.addBreadcrumb({
            category: "service-worker",
            level: "warning",
            message: "Service worker registration failed",
            data: { name: name, message: message }
          });
          window.Sentry.captureMessage("Service worker registration failed: " + name + ": " + message, {
            level: "warning",
            tags: { "sw.registration": "failed" },
            extra: { errorName: name, errorMessage: message }
          });
        } catch (e) {}
      }
    });
  });
}
</script>



<?php if (auth()->loggedIn()) : ?>
<?php ob_start() ?>
<!-- Mobile Bottom Navigation Dock -->
<nav class="mobile-bottom-dock md:hidden" aria-label="Mobile Navigation">
    <div class="grid grid-cols-5 items-center text-center px-1 py-1">
        <a href="/dashboard" class="flex flex-col items-center justify-center py-1 transition-colors <?= $__currentPath === '/dashboard' ? 'text-primary font-bold' : 'text-base-content/70 ' ?>">
            <i class="fa-solid fa-gauge-high text-lg"></i>
            <span class="text-[10px] mt-0.5">Dashboard</span>
        </a>
        <a href="/resort" class="flex flex-col items-center justify-center py-1 transition-colors <?= $__currentPath === '/resort' ? 'text-primary font-bold' : 'text-base-content/70 ' ?>">
            <i class="fa-solid fa-mountain-sun text-lg"></i>
            <span class="text-[10px] mt-0.5">Resort</span>
        </a>
        <a href="/map" class="flex flex-col items-center justify-center py-1 transition-colors <?= $__currentPath === '/map' ? 'text-primary font-bold' : 'text-base-content/70 ' ?>">
            <i class="fa-solid fa-map text-lg"></i>
            <span class="text-[10px] mt-0.5">Map</span>
        </a>
        <a href="/finances" class="flex flex-col items-center justify-center py-1 transition-colors <?= $__currentPath === '/finances' ? 'text-primary font-bold' : 'text-base-content/70 ' ?>">
            <i class="fa-solid fa-coins text-lg"></i>
            <span class="text-[10px] mt-0.5">Finances</span>
        </a>
        <button type="button" onclick="document.getElementById('mobileMenuDrawer').showModal()" class="flex flex-col items-center justify-center py-1 text-base-content/70  transition-colors" aria-label="More operations">
            <div class="relative">
                <i class="fa-solid fa-bars text-lg"></i>
                <?php if ($__bonusDue) : ?>
                    <span class="absolute -top-1 -right-1.5 w-2 h-2 rounded-full bg-warning"></span>
                <?php endif ?>
            </div>
            <span class="text-[10px] mt-0.5">More</span>
        </button>
    </div>
</nav>

<!-- Mobile More Operations Sheet -->
<dialog id="mobileMenuDrawer" class="hidden" closedby="any" aria-label="Resort Menu">
    <div class="max-h-[85vh] p-4 bg-background rounded-t-2xl md:rounded-2xl border border-border">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-border">
            <div class="flex items-center gap-2 font-bold text-base">
                <i class="fa-solid fa-mountain-sun text-primary"></i> Resort Menu
            </div>
            <form method="dialog"><button class="flex size-8 items-center justify-center rounded-lg hover:bg-default" aria-label="Close menu"><i class="fa-solid fa-xmark"></i></button></form>
        </div>
        <div class="space-y-4 overflow-y-auto max-h-[65vh] pr-1">
            <?php foreach ($__nav as $__secName => $__sec) : ?>
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-muted mb-2 flex items-center gap-1.5">
                    <i class="fa-solid <?= esc($__sec['icon']) ?> text-[11px]"></i> <?= esc($__secName) ?>
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <?php foreach ($__sec['items'] as $__it) : ?>
                        <?php
                            $locked = false;
                            $href = $__it['url'];
                            if (!empty($__it['feature'])) {
                                $unlocked = $__isAdmin || isFeatureUnlocked($__it['feature']);
                                if (!$unlocked) { $locked = true; $href = '/achievements'; }
                            }
                            $isAct = $__currentPath === $__it['url'];
                            $bDue = !empty($__it['badge']) && $__it['badge'] === 'bonus' && $__bonusDue;
                        ?>
                        <a href="<?= esc($href) ?>" class="flex items-center gap-2 p-2 rounded-lg border border-border hover:border-primary/40 hover:bg-default text-xs transition-all <?= $isAct ? 'bg-primary/10 border-primary text-primary font-semibold' : ($locked ? 'opacity-40' : '') ?>">
                            <i class="fa-solid <?= esc($__it['icon']) ?> w-4 text-center text-primary/80"></i>
                            <span class="truncate"><?= esc($__it['label']) ?></span>
                            <?php if ($bDue) : ?><span class="inline-flex items-center rounded bg-amber-400 px-1 text-[10px] font-bold text-neutral-900 ml-auto">!</span><?php endif ?>
                        </a>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endforeach ?>
            <?php if ($__isAdmin) : ?>
            <div class="pt-2 border-t border-border">
                <a href="/admin" class="inline-flex min-h-11 sm:min-h-9 items-center justify-center gap-2 rounded-xl border border-danger/40 text-danger hover:bg-danger/10 w-full text-xs font-semibold"><i class="fa-solid fa-shield-halved"></i> Admin Control Panel</a>
            </div>
            <?php endif ?>
        </div>
    </div>
    <form method="dialog"><button>close</button></form>
</dialog>
<?= island('MobileDock', ['path' => $__currentPath, 'nav' => $__shell['nav'], 'isAdmin' => $__isAdmin, 'bonusDue' => $__bonusDue], ob_get_clean()) ?>
<?php endif ?>

<!-- Tutorial Widget (Collapsible) -->
<?php if (auth()->loggedIn()) : ?>
<div id="tutorialWidgetContainer" style="position:fixed;bottom:4.5rem;right:1rem;z-index:9980;" class="hidden md:bottom-6 md:right-6">
    <!-- Collapsed Pill Badge -->
    <div id="tutPill" onclick="toggleTutCollapse(false)" class="hidden cursor-pointer inline-flex items-center gap-2 rounded-full bg-primary text-primary-foreground px-3.5 py-2.5 text-xs font-semibold shadow-2xl min-h-11">
        <i class="fa-solid fa-graduation-cap text-sm"></i>
        <span class="font-bold text-xs" id="tutPillText">Tutorial</span>
        <i class="fa-solid fa-chevron-up text-xs opacity-70"></i>
    </div>

    <!-- Expanded Card -->
    <div id="tutorialWidget" class="w-[calc(100vw-2rem)] max-w-sm md:w-80 rounded-2xl border border-border bg-popover text-popover-foreground shadow-2xl p-4">
        <div>
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="inline-flex items-center rounded-md bg-primary/10 text-primary px-2 py-0.5 text-xs font-bold font-mono" id="tutStep">1/10</div>
                    <h3 class="font-bold text-sm" id="tutTitle">Welcome!</h3>
                </div>
                <div class="flex items-center gap-1">
                    <button onclick="toggleTutCollapse(true)" class="inline-flex min-h-11 min-w-11 sm:min-h-7 sm:min-w-7 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-foreground" aria-label="Minimize tutorial" title="Minimize tutorial"><i class="fa-solid fa-minus"></i></button>
                    <button onclick="skipTutorial()" class="inline-flex min-h-11 min-w-11 sm:min-h-7 sm:min-w-7 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-danger" aria-label="Skip tutorial" title="Skip tutorial"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="flex gap-3">
                <i class="fa-solid fa-mountain-sun text-primary text-xl mt-0.5" id="tutIcon"></i>
                <div class="flex-1">
                    <p class="text-xs text-muted leading-relaxed" id="tutText"></p>
                    <div id="tutAction" class="mt-2 hidden">
                        <div class="flex items-center gap-1 text-xs">
                            <i class="fa-solid fa-arrow-right text-primary"></i>
                            <span class="font-semibold text-primary" id="tutActionText"></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between mt-3">
                <progress class="w-28 sm:w-32 h-2 rounded-full overflow-hidden bg-default accent-primary [&::-webkit-progress-bar]:bg-default [&::-webkit-progress-value]:bg-primary [&::-moz-progress-bar]:bg-primary" id="tutProgress" value="0" max="10"></progress>
                <div class="flex gap-1">
                    <a id="tutGoBtn" href="/dashboard" class="inline-flex min-h-9 sm:min-h-7 items-center justify-center rounded-lg bg-primary text-primary-foreground px-2.5 py-1 text-xs font-semibold hover:bg-primary/90 gap-1 hidden"><i class="fa-solid fa-arrow-right"></i> Go</a>
                    <button id="tutNextBtn" onclick="advanceTutorial()" class="inline-flex min-h-9 sm:min-h-7 items-center justify-center rounded-lg bg-primary text-primary-foreground px-2.5 py-1 text-xs font-semibold hover:bg-primary/90 gap-1 hidden"><i class="fa-solid fa-check"></i> Next</button>
                    <button id="tutDoneBtn" onclick="advanceTutorial()" class="inline-flex min-h-9 sm:min-h-7 items-center justify-center rounded-lg bg-success text-success-foreground px-2.5 py-1 text-xs font-semibold hover:bg-success/90 gap-1 hidden"><i class="fa-solid fa-trophy"></i> Finish</button>
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
<?php if (session()->get('is_demo') && featureEnabled('demo_v2')) : ?>
// A new demo player has the cookie banner to answer first: start as the small pill so the two don't stack
if (localStorage.getItem('tut_collapsed') === null && !localStorage.getItem('cookie_consent')) { window.tutStartCollapsed = true; }
<?php endif ?>

let tutCollapsed = localStorage.getItem('tut_collapsed') === 'true' || window.tutStartCollapsed === true;

function toggleTutCollapse(collapse) {
    tutCollapsed = collapse;
    localStorage.setItem('tut_collapsed', collapse ? 'true' : 'false');
    updateTutDisplay();
}

function updateTutDisplay() {
    const container = document.getElementById('tutorialWidgetContainer');
    const pill = document.getElementById('tutPill');
    const widget = document.getElementById('tutorialWidget');
    if (!container || !pill || !widget) return;

    if (tutCollapsed) {
        widget.classList.add('hidden');
        pill.classList.remove('hidden');
    } else {
        widget.classList.remove('hidden');
        pill.classList.add('hidden');
    }
}

function loadTutorial() {
    const container = document.getElementById('tutorialWidgetContainer');
    fetch('/tutorial/check?page=' + encodeURIComponent(tutCurrentPage), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            if (d.done) { 
                if (container) container.classList.add('hidden'); 
                return; 
            }
            if (container) container.classList.remove('hidden');
            updateTutDisplay();
            renderTutStep(d);
        })
        .catch(() => {
            if (container) container.classList.add('hidden');
        });
}

function renderTutStep(d) {
    document.getElementById('tutStep').textContent = (d.step + 1) + '/' + d.total;
    document.getElementById('tutTitle').textContent = d.data.title;
    const pillText = document.getElementById('tutPillText');
    if (pillText) pillText.textContent = 'Tutorial: ' + (d.step + 1) + '/' + d.total;
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
            nextBtn.className = 'inline-flex min-h-9 sm:min-h-7 items-center justify-center rounded-lg bg-default/40 text-muted px-2.5 py-1 text-xs font-semibold gap-1 cursor-not-allowed';
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
                toast.className = 'fixed top-4 right-4 z-50';
                toast.innerHTML = '<div class="flex items-center gap-2 rounded-xl border border-success/30 bg-success/10 text-success p-3 shadow-lg text-sm font-semibold"><i class="fa-solid fa-trophy"></i><span>Tutorial complete! You\'re ready to run your resort.</span></div>';
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

<?php if (!($__currentPath === '/demo/save' && featureEnabled('demo_v2'))) : // nothing covers the save form ?>
loadTutorial();
<?php endif ?>
setInterval(loadTutorial, 10000);
</script>
<?php endif ?>
<script>
// Modern Web Guidance: Cross-browser light-dismiss fallback for <dialog closedby="any">
if (typeof HTMLDialogElement !== 'undefined' && !('closedBy' in HTMLDialogElement.prototype)) {
    document.addEventListener('click', function(event) {
        if (event.target && event.target.tagName === 'DIALOG' && event.target.getAttribute('closedby') === 'any') {
            var rect = event.target.getBoundingClientRect();
            var isInDialog = (
                rect.top <= event.clientY &&
                event.clientY <= rect.top + rect.height &&
                rect.left <= event.clientX &&
                event.clientX <= rect.left + rect.width
            );
            if (!isInDialog && typeof event.target.close === 'function') {
                event.target.close();
            }
        }
    });
}
</script>
</body>
</html>
