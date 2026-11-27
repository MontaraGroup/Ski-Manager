<!DOCTYPE html>
<html lang="en" dir="ltr" data-theme="carboncloud" data-parkcity-webp="<?= esc(implode(' ', array_keys(array_filter(\App\Controllers\ResortMap::webpSizes('/img/ParkCity.jpg'))))) ?>">
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
    <?php
    // PostHog product analytics. POSTHOG_KEY is the project's public key (phc_…);
    // POSTHOG_HOST is the managed reverse proxy (https://j.ski-manager.net), so ad
    // blockers don't drop events, or https://us.i.posthog.com without the proxy.
    // Until the visitor accepts analytics it runs cookieless: an in-memory id and no
    // session replay. Signed-in players are always identified by their account id,
    // because feature flags, beta opt-ins and support tickets are keyed on it (the
    // server evaluates flags for the same id). window.__posthogConsent() (called from
    // the cookie banner and the Google consent message) adds cookies and replay.
    $__phKey  = (string) env('POSTHOG_KEY', '');
    $__phHost = rtrim((string) env('POSTHOG_HOST', 'https://us.i.posthog.com'), '/');
    ?>
    <?php if ($__phKey !== '' && preg_match('#^https://([a-z]+\.i\.posthog\.com|[a-z0-9-]+\.ski-manager\.net)$#', $__phHost)) : ?>
    <script>
    !function(t,e){var o,n,p,r;e.__SV||(window.posthog && window.posthog.__loaded)||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}p||((p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",p.onerror=function(){p=null},(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r));var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],Object.defineProperty(u,"toString",{configurable:!0,enumerable:!0,writable:!0,value:function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e}}),Object.defineProperty(u.people,"toString",{configurable:!0,enumerable:!0,writable:!0,value:function(){return u.toString(1)+".people (stub)"}}),o="Eh Rh Ph Oh Dh qh init yd bd dd fd Sd wc md xd ad Ed Ad Rd Pd Od Fd capture getExtension gr pd gd calculateEventProperties Dd register register_once register_for_session unregister unregister_for_session zd hd jd getFeatureFlag getFeatureFlagPayload getFeatureFlagResult getAllFeatureFlags isFeatureEnabled reloadFeatureFlags updateFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSurveysLoaded onSessionId getSurveys getActiveMatchingSurveys onActiveMatchingSurveysChanged renderSurvey displaySurvey cancelPendingSurvey canRenderSurvey canRenderSurveyAsync Hd identify setPersonProperties unsetPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset Ud shutdown setIdentity clearIdentity get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException addExceptionStep captureLog startExceptionAutocapture stopExceptionAutocapture loadToolbar get_property getSessionProperty qd Td createPersonProfile setInternalOrTestUser Bd Uh zh opt_in_capturing opt_out_capturing Hh has_opted_in_capturing has_opted_out_capturing get_explicit_consent_status is_capturing clear_opt_in_out_capturing $d debug kc Ds getPageViewId captureTraceFeedback captureTraceMetric rd".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);
    (function () {
      var consented = false;
      try { var c = localStorage.getItem('cookie_consent'); consented = c === 'all' || c === 'analytics'; } catch (e) {}
      var player = <?= (function_exists('auth') && auth()->loggedIn())
          ? json_encode(['id' => (string) auth()->id(), 'username' => auth()->user()->username ?? '', 'is_admin' => function_exists('featureViewerIsAdmin') && featureViewerIsAdmin()], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
          : 'null' ?>;
      posthog.init(<?= json_encode($__phKey, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>, {
        api_host: <?= json_encode($__phHost, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        ui_host: 'https://us.posthog.com',
        defaults: '2026-05-30',
        person_profiles: 'identified_only',
        persistence: consented ? 'localStorage+cookie' : 'memory',
        disable_session_recording: !consented,
        session_recording: { maskAllInputs: true },
        capture_exceptions: true,
        // Adds X-PostHog-Session-Id to this site's own requests, so server errors and traces link to the replay
        tracing_headers: [location.hostname],
        enable_heatmaps: true,
        opt_in_site_apps: true,
        // Support chat for signed-in players only; it follows the site's light/dark preference
        disable_conversations: !player,
        conversations: { theme: { colorScheme: 'auto', borderRadius: '12px' } },
        loaded: function (ph) { identify(ph); }
      });
      function identify(ph) {
        if (player) ph.identify(player.id, { username: player.username, is_admin: player.is_admin ? 'true' : 'false' });
      }
      window.__posthogConsent = function (granted) {
        if (!window.posthog || granted === consented) return;
        consented = granted;
        if (granted) {
          posthog.set_config({ persistence: 'localStorage+cookie' });
          posthog.startSessionRecording();
          identify(posthog);
        } else {
          posthog.stopSessionRecording();
          posthog.reset();
          posthog.set_config({ persistence: 'memory' });
          identify(posthog);
        }
      };
    })();
    </script>
    <?php endif; ?>
    <?php
    $__rawTitle = trim((string) $this->renderSection('title'));
    $__pageTitle = $__rawTitle !== '' ? $__rawTitle . ' - Ski Manager' : 'Ski Manager - Free Online Ski Resort Game';
    $__uriPath = trim(uri_string(), '/');
    $__canonicalUrl = 'https://ski-manager.net' . ($__uriPath !== '' ? '/' . $__uriPath : '/');

    // Build breadcrumbs for SEO and navigation
    $__breadcrumbs = [
        ['name' => 'Home', 'url' => 'https://ski-manager.net/'],
    ];
    if ($__uriPath !== '') {
        $__segments = explode('/', $__uriPath);
        $__accumulatedPath = '';
        foreach ($__segments as $__i => $__seg) {
            $__accumulatedPath .= '/' . $__seg;
            $__isLast = ($__i === count($__segments) - 1);
            if ($__isLast && $__rawTitle !== '') {
                $__crumbName = $__rawTitle;
            } else {
                $__friendlyNames = [
                    'about' => 'About',
                    'faq' => 'FAQ',
                    'guides' => 'Guides',
                    'contact' => 'Contact',
                    'terms' => 'Terms of Service',
                    'privacy' => 'Privacy Policy',
                    'cookies' => 'Cookie Policy',
                    'disclaimer' => 'Disclaimer',
                    'sitemap' => 'Sitemap',
                    'updates' => 'Updates',
                    'roadmap' => 'Roadmap',
                    'developers' => 'Developers',
                    'leaderboard' => 'Leaderboard',
                    'register' => 'Register',
                    'login' => 'Log In',
                    'legal' => 'Legal',
                ];
                $__crumbName = $__friendlyNames[$__seg] ?? ucfirst(str_replace('-', ' ', $__seg));
            }
            $__breadcrumbs[] = [
                'name' => $__crumbName,
                'url' => 'https://ski-manager.net' . $__accumulatedPath,
            ];
        }
    }
    ?>
    <title><?= esc($__pageTitle) ?></title>
    <meta property="og:title" content="<?= esc($__pageTitle) ?>">
    <meta property="og:description" content="Build, manage, and grow your dream ski resort. Free to play in your browser.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= esc($__canonicalUrl) ?>">
    <meta property="og:site_name" content="Ski Manager">
    <meta property="og:image" content="https://ski-manager.net/og-image.png">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Ski Manager - build and run your own ski resort">
    <meta name="twitter:image" content="https://ski-manager.net/og-image.png">
    <meta name="theme-color" content="#1a2940">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= esc($__pageTitle) ?>">
    <meta name="twitter:description" content="Build, manage, and grow your dream ski resort. Free to play.">
    <meta name="msvalidate.01" content="17A0AC384937E88A4F602D2B7DAE237F" />
    <link rel="canonical" href="<?= esc($__canonicalUrl) ?>" />
    <meta name="description" content="Ski Manager - Free online ski resort management game. Build slopes, hire staff, manage finances, and compete with players worldwide.">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="preload" href="/css/style.css?v=<?= @filemtime(FCPATH . "css/style.css") ?>" as="style">
    <?php if (!auth()->loggedIn() && (uri_string() === '' || uri_string() === '/')) : ?>
    <link rel="preload" as="image" href="/img/ParkCity_low.webp" type="image/webp" fetchpriority="high">
    <?php endif ?>
    <?php if (auth()->loggedIn()) : ?>
    <?php $__mapPrefetch = preg_replace('/[^A-Za-z]/', '', (string) (db_connect()->table("player_finances")->select("resort_map")->where("user_id", auth()->id())->get()->getRow("resort_map") ?? "ParkCity")); ?>
    <?php // Warm the files the trail map will ask for: the WebP copies when the server has them
          $__mapWebp = \App\Controllers\ResortMap::webpSizes('/img/' . $__mapPrefetch . '.jpg'); ?>
    <link rel="prefetch" href="/img/<?= $__mapPrefetch ?>_low.<?= $__mapWebp['low'] ? 'webp' : 'jpg' ?>" as="image">
    <link rel="prefetch" href="/img/<?= $__mapPrefetch ?>_med.<?= $__mapWebp['med'] ? 'webp' : 'jpg' ?>" as="image">
    <?php endif ?>
    <link rel="stylesheet" href="/css/style.css?v=<?= @filemtime(FCPATH . "css/style.css") ?>" fetchpriority="high">
    <?= \App\Libraries\Islands::headTags(auth()->loggedIn()) ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://kit.fontawesome.com" crossorigin>
    <link rel="preconnect" href="https://ka-f.fontawesome.com" crossorigin>
    <script async src="https://kit.fontawesome.com/bbfd599354.js" crossorigin="anonymous"></script>
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
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "Ski Manager",
      "url": "https://ski-manager.net",
      "logo": "https://ski-manager.net/apple-touch-icon.png",
      "sameAs": [
        "https://wiki.ski-manager.net",
        "https://github.com/MontaraGroup/Ski-Manager",
        "https://discord.gg/u3GzGt8K6a"
      ]
    }
    </script>
    <?php if (count($__breadcrumbs) > 1) : ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        <?php foreach ($__breadcrumbs as $__idx => $__crumb) : ?>
        {
          "@type": "ListItem",
          "position": <?= $__idx + 1 ?>,
          "name": <?= json_encode($__crumb['name'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>,
          "item": <?= json_encode($__crumb['url'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>
        }<?= $__idx < count($__breadcrumbs) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
      ]
    }
    </script>
    <?php endif; ?>
    <?= $this->renderSection('extra_head') ?>
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
.site-footer h2 { font-size: 0.8125rem; font-weight: 600; color: var(--color-base-content); margin-bottom: 0.75rem; }
.site-footer ul a { color: color-mix(in oklab, var(--color-base-content) 70%, transparent); }
.site-footer ul a:hover { color: var(--color-base-content); }
@media (pointer: coarse) { .site-footer a:not(.brand) { display: inline-flex; align-items: center; min-height: 2.75rem; min-width: 2.75rem; } .site-footer ul.space-y-2 > * + * { margin-top: 0; } }

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
<?php
    // The page is shown because it's in beta (flag level 3): Page headings add a "Beta" chip
    $__betaFlag = \App\Filters\FeatureGate::flagForPath('/' . trim(uri_string(), '/'));
    $__betaPage = $__betaFlag !== null && function_exists('featureIsBetaFor') && featureIsBetaFor($__betaFlag);
?>
<body class="min-h-screen flex flex-col bg-base-200"<?= $__betaPage ? ' data-beta-page="1"' : '' ?>><a href="#main-content" class="skip-link">Skip to main content</a>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5GGPL25W" height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

    <!-- Navbar -->
    <?php
        $__currentPath = '/' . trim(uri_string(), '/');
        $__isAdmin = \App\Libraries\Shell::isAdmin();
        $__bonusDue = \App\Libraries\Shell::bonusDue();
        // The React AppShell island renders for everyone (guests and signed-in); the markup below is its no-JS fallback
        $__shellIslands = true;
        $__flash = \App\Libraries\Shell::takeFlash();
        $__shell = \App\Libraries\Shell::props($__currentPath, $__flash);
        $__isActive = fn(string $url) => $__currentPath === $url || ($url !== '/' && str_starts_with($__currentPath, $url . '/'));

        // One menu link in the no-JS fallback, from an item Shell::props() already resolved (locks, switches, badges)
        $__navItem = function (array $r) use ($__isActive) {
            $cls = ($r['locked'] ?? false) || ($r['flagOff'] ?? false) ? 'text-muted' : 'text-foreground';
            $active = $__isActive($r['url']);
            $badge = !empty($r['bonusDue']) ? ' <span class="rounded-full bg-warning px-1.5 text-xs font-semibold text-warning-foreground">Due</span>' : '';
            if (!empty($r['beta'])) $badge .= ' <span class="rounded-full bg-warning/15 px-2 text-xs font-semibold text-warning">Beta</span>';
            if (!empty($r['isNew'])) $badge .= ' <span class="rounded-full bg-accent px-1.5 text-xs font-semibold text-accent-foreground">New</span>';
            if (!empty($r['locked'])) $badge .= ' <i class="fa-solid fa-lock text-xs text-muted" aria-hidden="true"></i><span class="sr-only"> (locked)</span>';
            $ext = !empty($r['external']);
            $target = $ext ? ' target="_blank" rel="noopener noreferrer"' : '';
            if ($ext) $badge .= '<span class="sr-only"> (opens in a new tab)</span>';
            return '<a href="' . esc($r['href'], 'attr') . '"' . $target . ($active ? ' aria-current="page"' : '')
                . ' class="flex min-h-11 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium hover:bg-default ' . $cls . ($active ? ' font-semibold' : '') . '">'
                . '<i class="fa-solid ' . esc($r['icon'], 'attr') . ' fa-fw text-accent" aria-hidden="true"></i><span>' . esc($r['label']) . '</span>' . $badge . '</a>';
        };
        // The whole menu for the fallback's small-screen panels (navbar button and dock "More")
        $__menuPanel = function () use ($__shell, $__navItem, $__isAdmin) {
            $html = '';
            if ($__shell['nav']) {
                $html .= $__navItem(['href' => '/dashboard', 'url' => '/dashboard', 'label' => 'Dashboard', 'icon' => 'fa-gauge-high']);
                foreach ($__shell['nav'] as $sec) {
                    $html .= '<p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-muted">' . esc($sec['name']) . '</p>';
                    foreach ($sec['items'] as $it) $html .= $__navItem($it);
                }
                $html .= '<p class="px-3 pb-1 pt-3 text-xs font-semibold uppercase tracking-wider text-muted">Account</p>';
                foreach ($__shell['account'] as $it) $html .= $__navItem($it);
                if ($__isAdmin) $html .= $__navItem(['href' => '/admin', 'url' => '/admin', 'label' => 'Admin panel', 'icon' => 'fa-user-shield']);
            } else {
                foreach ($__shell['publicNav'] as $it) $html .= $__navItem($it);
            }
            return $html;
        };
    ?>
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
        <div class="<?= $__shell['nav'] ? 'hidden md:block lg:hidden' : 'lg:hidden' ?>">
            <details class="relative">
                <summary class="flex min-h-11 min-w-11 cursor-pointer list-none items-center justify-center rounded-lg hover:bg-default" aria-label="Open the menu"><i class="fa-solid fa-bars text-lg" aria-hidden="true"></i></summary>
                <div class="absolute left-0 z-50 mt-2 max-h-[80vh] w-64 overflow-y-auto rounded-xl border border-border bg-popover p-2 shadow-2xl"><?= $__menuPanel() ?></div>
            </details>
        </div>
        <a href="/" class="brand flex min-h-11 items-center gap-2 rounded-lg px-2 font-semibold" aria-label="Ski Manager home">
            <svg class="brand-mark size-7 shrink-0" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="6" fill="#1d232b"/><path d="M3 25 L12 11 L16 17 L20 9 L29 25 Z" fill="#f7f5f0"/><path d="M12 11 L14.6 15 L12.6 14 L10.4 15.6 Z M20 9 L23 14.5 L20.8 13.4 L18.4 15 Z" fill="#1d232b" opacity=".35"/><path d="M20 9 L19 25" stroke="#2f6fd6" stroke-width="1.6" stroke-linecap="round"/></svg>
            <span class="brand-name hidden text-lg tracking-tight sm:inline">Ski Manager</span>
        </a>
        <div class="hidden items-center gap-1 lg:flex">
            <?php if ($__shell['nav']) : ?>
                <a href="/dashboard"<?= $__isActive('/dashboard') ? ' aria-current="page"' : '' ?> class="flex min-h-9 items-center gap-2 rounded-lg px-3 text-sm font-medium hover:bg-default <?= $__isActive('/dashboard') ? 'bg-default font-semibold text-accent-soft-foreground' : '' ?>"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i>Dashboard</a>
                <?php foreach ($__shell['nav'] as $__sec) : $__secActive = (bool) array_filter($__sec['items'], fn($it) => $__isActive($it['url'])); ?>
                <details class="relative">
                    <summary class="flex min-h-9 cursor-pointer list-none select-none items-center gap-1.5 rounded-lg px-3 text-sm font-medium hover:bg-default <?= $__secActive ? 'bg-default font-semibold text-accent-soft-foreground' : '' ?>">
                        <i class="fa-solid <?= esc($__sec['icon'], 'attr') ?>" aria-hidden="true"></i>
                        <span><?= esc($__sec['name']) ?></span><?= $__secActive ? '<span class="sr-only"> (current section)</span>' : '' ?>
                        <i class="fa-solid fa-chevron-down text-xs text-muted" aria-hidden="true"></i>
                    </summary>
                    <div class="absolute left-0 z-50 mt-2 w-60 rounded-xl border border-border bg-popover p-1.5 shadow-2xl">
                        <?php foreach ($__sec['items'] as $__it) : echo $__navItem($__it); endforeach ?>
                    </div>
                </details>
                <?php endforeach ?>
            <?php else : ?>
                <?php foreach ($__shell['publicNav'] as $__it) : ?>
                <a href="<?= esc($__it['href'], 'attr') ?>"<?= $__isActive($__it['url']) ? ' aria-current="page"' : '' ?> class="flex min-h-9 items-center rounded-lg px-3 text-sm font-medium hover:bg-default <?= $__isActive($__it['url']) ? 'bg-default font-semibold text-accent-soft-foreground' : '' ?>"><?= esc($__it['label']) ?></a>
                <?php endforeach ?>
            <?php endif ?>
        </div>
        <div class="ml-auto flex items-center gap-1">
            <?php if (auth()->loggedIn()) : ?>
                <details class="relative">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-1 rounded-lg px-2.5 text-sm font-medium hover:bg-default sm:min-h-9">
                        <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
                        <span class="hidden max-w-32 truncate sm:inline"><?= esc(auth()->user()->username ?? 'Account') ?></span>
                        <span class="sr-only sm:hidden">Account menu</span>
                    </summary>
                    <div class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-border bg-popover p-1.5 shadow-2xl">
                        <?php foreach ($__shell['account'] as $__it) : echo $__navItem($__it); endforeach ?>
                        <?php if ($__isAdmin) : echo $__navItem(['href' => '/admin', 'url' => '/admin', 'label' => 'Admin panel', 'icon' => 'fa-user-shield']); endif ?>
                        <?= $__navItem(['href' => '/logout', 'url' => '/logout', 'label' => 'Log out', 'icon' => 'fa-right-from-bracket']) ?>
                    </div>
                </details>
            <?php else : ?>
                <div class="ml-1 flex items-center gap-1.5">
                    <a href="/login" class="flex min-h-11 items-center justify-center rounded-lg px-3 text-xs font-medium hover:bg-default sm:min-h-9 sm:text-sm">Log in</a>
                    <a href="/register" class="flex min-h-11 items-center justify-center rounded-lg bg-primary px-3 text-xs font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 sm:min-h-9 sm:text-sm">Play free</a>
                </div>
            <?php endif ?>
        </div>
    </nav></header>
    <?php $__fallbackHeader = ob_get_clean(); ?>
    <div style="z-index:9999; position:sticky; top:0"><?= island('AppShell', $__shell, $__fallbackHeader) ?></div>

    <!-- Content -->
    <main class="flex-1 <?= auth()->loggedIn() ? 'pb-20 md:pb-6' : 'pb-6' ?>" id="main-content" role="main">
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

    <?php
    $__firstSeg = explode('/', $__uriPath)[0] ?? '';
    $__showVisualBreadcrumbs = count($__breadcrumbs) > 1 && (
        !auth()->loggedIn() ||
        in_array($__firstSeg, ['about', 'faq', 'guides', 'contact', 'terms', 'privacy', 'cookies', 'disclaimer', 'sitemap', 'updates', 'roadmap', 'developers', 'legal'])
    );
    ?>
    <?php if ($__showVisualBreadcrumbs) : ?>
    <nav aria-label="Breadcrumb" class="w-full max-w-6xl mx-auto px-4 pt-3 pb-1 text-xs text-base-content/70">
        <ol itemscope itemtype="https://schema.org/BreadcrumbList" class="flex flex-wrap items-center gap-1.5">
            <?php foreach ($__breadcrumbs as $__idx => $__crumb) : ?>
                <?php if ($__idx > 0) : ?>
                    <li aria-hidden="true" class="text-base-content/40">/</li>
                <?php endif; ?>
                <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" class="inline-flex items-center">
                    <?php if ($__idx === count($__breadcrumbs) - 1) : ?>
                        <span itemprop="name" class="font-medium text-base-content" aria-current="page"><?= esc($__crumb['name']) ?></span>
                    <?php else : ?>
                        <a itemprop="item" href="<?= esc($__crumb['url']) ?>" class="hover:text-primary transition-colors">
                            <span itemprop="name"><?= esc($__crumb['name']) ?></span>
                        </a>
                    <?php endif; ?>
                    <meta itemprop="position" content="<?= $__idx + 1 ?>" />
                </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

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
                        <a href="https://discord.gg/u3GzGt8K6a" target="_blank" rel="noopener noreferrer" class="flex size-11 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-foreground" title="Discord" aria-label="Ski Manager on Discord"><i class="fa-brands fa-discord text-lg" aria-hidden="true"></i></a>
                        <a href="https://github.com/MontaraGroup/Ski-Manager" target="_blank" rel="noopener noreferrer" class="flex size-11 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-foreground" title="GitHub" aria-label="Ski Manager source code on GitHub"><i class="fa-brands fa-github text-lg" aria-hidden="true"></i></a>
                    </div>
                </div>
                <!-- Game -->
                <div>
                    <h2 class="font-semibold text-xs tracking-wider uppercase text-muted mb-3">Game</h2>
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
                    <h2 class="font-semibold text-xs tracking-wider uppercase text-muted mb-3">Resources</h2>
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
                    <h2 class="font-semibold text-xs tracking-wider uppercase text-muted mb-3">Legal</h2>
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
<div id="cookieConsent" role="region" aria-label="Cookie consent" class="fixed bottom-0 left-0 right-0 bg-base-200 border-t border-base-300 p-4 shadow-lg" style="z-index:99999;display:none;">
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
    if(window.__posthogConsent)window.__posthogConsent(consent.analytics_storage==='granted');
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
            if(tcData.gdprApplies){
                decided=true;clearTimeout(fallback);
                // TCF purposes 1 (store info on device) and 9 (market research) cover product analytics.
                if(window.__posthogConsent&&(tcData.eventStatus==='tcloaded'||tcData.eventStatus==='useractioncomplete')){
                    var pc=(tcData.purpose&&tcData.purpose.consents)||{};
                    window.__posthogConsent(!!(pc[1]&&pc[9]));
                }
            }
            else{clearTimeout(fallback);show();}
        });
    })(0);
})();
</script>
<style>[data-tip]{position:relative;cursor:help}[data-tip]:hover::after{content:attr(data-tip);position:absolute;bottom:calc(100% + 6px);left:50%;transform:translateX(-50%);background:#1d232a;color:#a6adbb;padding:6px 10px;border-radius:6px;font-size:11px;white-space:nowrap;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,.3);pointer-events:none}[data-tip]:hover::before{content:"";position:absolute;bottom:calc(100% + 2px);left:50%;transform:translateX(-50%);border:4px solid transparent;border-top-color:#1d232a;z-index:9999}</style>

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
<!-- Phone navigation (no-JS fallback of the MobileDock island) -->
<nav class="mobile-bottom-dock md:hidden" aria-label="Phone navigation">
    <ul class="grid grid-cols-5">
        <?php foreach ([['/dashboard', 'Dashboard', 'fa-gauge-high'], ['/resort', 'Resort', 'fa-mountain-sun'], ['/map', 'Map', 'fa-map'], ['/finances', 'Finances', 'fa-coins']] as [$__u, $__l, $__i]) : ?>
        <li>
            <a href="<?= $__u ?>"<?= $__isActive($__u) ? ' aria-current="page"' : '' ?> class="flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs <?= $__isActive($__u) ? 'font-semibold text-accent-soft-foreground' : 'text-muted' ?>">
                <i class="fa-solid <?= $__i ?> text-lg" aria-hidden="true"></i><?= $__l ?>
            </a>
        </li>
        <?php endforeach ?>
        <li>
            <details class="relative">
                <summary class="flex min-h-14 cursor-pointer list-none flex-col items-center justify-center gap-0.5 text-xs text-muted">
                    <span class="relative"><i class="fa-solid fa-bars text-lg" aria-hidden="true"></i><?php if ($__bonusDue) : ?><span class="absolute -right-1.5 -top-1 size-2 rounded-full bg-warning" aria-hidden="true"></span><?php endif ?></span>
                    More<?= $__bonusDue ? '<span class="sr-only">, daily bonus ready</span>' : '' ?>
                </summary>
                <div class="fixed inset-x-2 bottom-16 z-50 max-h-[70vh] overflow-y-auto rounded-xl border border-border bg-popover p-2 shadow-2xl"><?= $__menuPanel() ?></div>
            </details>
        </li>
    </ul>
</nav>
<?= island('MobileDock', ['path' => $__currentPath, 'nav' => $__shell['nav'], 'account' => $__shell['account'], 'search' => $__shell['search'], 'isAdmin' => $__isAdmin, 'bonusDue' => $__bonusDue], ob_get_clean()) ?>
<?php endif ?>

<!-- Tutorial card (collapsible) -->
<?php if (auth()->loggedIn()) : ?>
<?php $__tutOnMap = $__currentPath === '/map'; ?>
<?php // Pages whose tutorial action sits on the right (the map's Build button and drawer, the restaurant catalog)
      // get the card bottom-left. On the Trail Map it also stops short of the hammer (Build) button on a phone, and
      // hides while the Build drawer is open there, because the drawer fills the screen with Construct at the bottom. ?>
<?php $__tutLeft = in_array($__currentPath, ['/map', '/restaurants'], true); ?>
<?php // The PostHog Support launcher is a 50px button fixed 20px from the bottom-right corner (top edge 70px up)
      // and drawn above everything, so a card under it can't be clicked. Keep the card 88px up wherever it reaches
      // that corner: on a phone always (the card is nearly full width), on desktop when it sits on the right. ?>
<div id="tutorialWidgetContainer" hidden class="fixed z-[9980] bottom-[5.5rem] <?= $__tutLeft ? 'md:bottom-6 left-4 md:left-6' : 'right-4 md:right-6' ?> <?= $__tutOnMap ? 'max-md:in-[.map-drawer-open]:invisible' : '' ?>">
    <button type="button" id="tutPill" hidden onclick="toggleTutCollapse(false)" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-3.5 py-2.5 text-xs font-semibold text-white shadow-2xl" aria-label="Open the tutorial">
        <i class="fa-solid fa-graduation-cap text-sm" aria-hidden="true"></i>
        <span id="tutPillText">Tutorial</span>
        <i class="fa-solid fa-chevron-up text-xs opacity-70" aria-hidden="true"></i>
    </button>

    <section id="tutorialWidget" aria-labelledby="tutTitle" class="<?= $__tutOnMap ? 'w-[calc(100vw-7rem)]' : 'w-[calc(100vw-2rem)]' ?> max-w-sm md:w-80 rounded-2xl border border-border bg-surface p-4 text-foreground shadow-2xl">
        <div class="mb-2 flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="inline-flex shrink-0 items-center rounded-md bg-primary/10 px-2 py-0.5 font-mono text-xs font-bold text-primary" id="tutStep">1/7</span>
                <h3 class="truncate text-sm font-bold" id="tutTitle">Welcome to Ski Manager</h3>
            </div>
            <button type="button" onclick="toggleTutCollapse(true)" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-lg text-muted hover:bg-default hover:text-foreground sm:min-h-8 sm:min-w-8" aria-label="Minimize tutorial" title="Minimize tutorial"><i class="fa-solid fa-minus" aria-hidden="true"></i></button>
        </div>

        <div id="tutBody">
            <div class="flex gap-3">
                <span id="tutIcon" class="mt-0.5 shrink-0 text-xl text-primary" aria-hidden="true"><i class="fa-solid fa-mountain-sun"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm leading-relaxed text-muted" id="tutText"></p>
                    <div id="tutAction" hidden class="mt-2">
                        <p class="flex items-center gap-1.5 text-sm font-semibold text-primary">
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            <span id="tutActionText"></span>
                        </p>
                        <p id="tutWaiting" hidden class="mt-1 text-xs text-muted">Next unlocks when this is done.</p>
                    </div>
                    <div id="tutEstimate" hidden class="mt-3 rounded-lg bg-surface-secondary p-2.5 text-xs">
                        <p class="font-semibold text-foreground">Tonight's estimate</p>
                        <p class="mt-0.5 text-muted" id="tutEstimateText"></p>
                    </div>
                    <nav id="tutLinks" hidden class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm" aria-label="Where to go next"></nav>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between gap-2">
                <button type="button" onclick="confirmSkipTutorial(true)" class="min-h-9 rounded-lg px-1 text-xs font-medium text-muted underline-offset-2 hover:text-foreground hover:underline">Skip tutorial</button>
                <div class="flex items-center gap-2">
                    <span class="hidden text-xs tabular-nums text-muted sm:inline" id="tutProgressText"></span>
                    <a id="tutGoBtn" hidden href="/dashboard" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-primary px-3 text-xs font-semibold text-white hover:bg-primary/90">Take me there <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                    <button type="button" id="tutNextBtn" hidden onclick="advanceTutorial()" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-xs font-semibold">Next</button>
                </div>
            </div>
        </div>

        <div id="tutSkipConfirm" hidden role="alertdialog" aria-labelledby="tutSkipText">
            <p class="text-sm" id="tutSkipText">Skip the tutorial? You can restart it in Settings.</p>
            <div class="mt-3 flex justify-end gap-2">
                <button type="button" onclick="confirmSkipTutorial(false)" class="inline-flex min-h-9 items-center rounded-lg border border-border px-3 text-xs font-semibold hover:bg-default">Keep going</button>
                <button type="button" onclick="skipTutorial()" class="inline-flex min-h-9 items-center rounded-lg bg-danger px-3 text-xs font-semibold text-danger-foreground hover:bg-danger/90">Skip</button>
            </div>
        </div>
    </section>
</div>

<script>
const tutCsrf = '<?= csrf_hash() ?>';
const tutCsrfName = '<?= csrf_token() ?>';
const tutCurrentPage = window.location.pathname;
let tutTimer = null;
let tutConfirming = false;
<?php if (session()->get('is_demo')) : ?>
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
    const pill = document.getElementById('tutPill');
    const widget = document.getElementById('tutorialWidget');
    if (!pill || !widget) return;
    widget.hidden = tutCollapsed;
    pill.hidden = !tutCollapsed;
}

function hideTutorial() {
    const c = document.getElementById('tutorialWidgetContainer');
    if (c) c.hidden = true;
    if (tutTimer) { clearInterval(tutTimer); tutTimer = null; }
}

// The nightly settle in the player's own time, e.g. "12:05 AM EST"
function tutSettleText(iso) {
    const at = new Date(iso);
    if (isNaN(at)) return '00:05 server time';
    return at.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' });
}

function loadTutorial() {
    if (tutConfirming) return;
    fetch('/tutorial/check?page=' + encodeURIComponent(tutCurrentPage), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(d => {
            if (d.done) { hideTutorial(); return; }
            const c = document.getElementById('tutorialWidgetContainer');
            if (c) c.hidden = false;
            updateTutDisplay();
            renderTutStep(d);
        })
        .catch(() => hideTutorial());
}

function renderTutStep(d) {
    const last = d.step >= d.total - 1;
    const settle = tutSettleText(d.settleAt);
    document.getElementById('tutStep').textContent = (d.step + 1) + '/' + d.total;
    document.getElementById('tutProgressText').textContent = 'Step ' + (d.step + 1) + ' of ' + d.total;
    document.getElementById('tutTitle').textContent = d.data.title;
    document.getElementById('tutPillText').textContent = 'Tutorial ' + (d.step + 1) + '/' + d.total;
    document.getElementById('tutText').textContent = d.data.text.replaceAll('{settle}', settle);
    // Font Awesome swaps each <i> for an <svg>, so changing a class later does nothing: put in a new <i>
    const icon = document.createElement('i');
    icon.className = d.data.icon;
    document.getElementById('tutIcon').replaceChildren(icon);

    const actionDiv = document.getElementById('tutAction');
    const waiting = document.getElementById('tutWaiting');
    const goBtn = document.getElementById('tutGoBtn');
    const nextBtn = document.getElementById('tutNextBtn');

    // Reset the buttons every time: a step that was waiting must not leave Next grey on the next one
    goBtn.hidden = true;
    waiting.hidden = true;
    nextBtn.hidden = false;
    nextBtn.disabled = false;
    nextBtn.removeAttribute('aria-disabled');
    nextBtn.className = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-xs font-semibold '
        + (last ? 'bg-success text-success-foreground hover:bg-success/90' : 'bg-primary text-white hover:bg-primary/90');
    nextBtn.innerHTML = last ? '<i class="fa-solid fa-trophy" aria-hidden="true"></i> Finish' : 'Next';

    actionDiv.hidden = !d.data.action;
    if (d.data.action) {
        document.getElementById('tutActionText').textContent = d.data.action;
        if (!d.canAdvance) {
            if (d.data.page && d.data.page !== tutCurrentPage) {
                nextBtn.hidden = true;
                goBtn.href = d.data.page;
                goBtn.hidden = false;
            } else {
                nextBtn.disabled = true;
                nextBtn.setAttribute('aria-disabled', 'true');
                nextBtn.className = 'inline-flex min-h-9 cursor-not-allowed items-center justify-center rounded-lg bg-default px-3 text-xs font-semibold text-muted';
                waiting.hidden = false;
            }
        }
    }

    const est = document.getElementById('tutEstimate');
    est.hidden = !d.estimate;
    if (d.estimate) {
        document.getElementById('tutEstimateText').textContent =
            'About ' + d.estimate.skiers + ' skiers, ' + d.estimate.income + ' income and ' + d.estimate.costs + ' costs.';
    }

    const links = document.getElementById('tutLinks');
    links.replaceChildren();
    (d.data.links || []).forEach(l => {
        const a = document.createElement('a');
        a.href = l.href;
        a.textContent = l.label;
        a.className = 'inline-flex min-h-9 items-center font-medium text-primary underline-offset-2 hover:underline';
        links.appendChild(a);
    });
    links.hidden = !(d.data.links || []).length;
}

function advanceTutorial() {
    const nextBtn = document.getElementById('tutNextBtn');
    if (nextBtn.disabled) return;
    nextBtn.disabled = true;
    fetch('/tutorial/advance', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ [tutCsrfName]: tutCsrf })
    }).then(r => r.json()).then(d => {
        if (d.done || d.completed) {
            hideTutorial();
            if (d.completed) {
                const toast = document.createElement('div');
                toast.className = 'fixed top-4 right-4 z-50';
                toast.setAttribute('role', 'status');
                toast.innerHTML = '<div class="flex items-center gap-2 rounded-xl border border-success/30 bg-success/10 text-success p-3 shadow-lg text-sm font-semibold"><i class="fa-solid fa-trophy" aria-hidden="true"></i><span>Tutorial complete. See you tomorrow for your first results.</span></div>';
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 5000);
            }
        } else {
            renderTutStep(d);
        }
    }).catch(() => { nextBtn.disabled = false; });
}

function confirmSkipTutorial(show) {
    tutConfirming = show;
    document.getElementById('tutBody').hidden = show;
    document.getElementById('tutSkipConfirm').hidden = !show;
}

function skipTutorial() {
    fetch('/tutorial/skip', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ [tutCsrfName]: tutCsrf })
    }).then(() => { tutConfirming = false; hideTutorial(); });
}

<?php // Nothing covers the demo save form or the first-visit profile form
      if ($__currentPath !== '/demo/save' && $__currentPath !== '/complete-profile') : ?>
loadTutorial();
tutTimer = setInterval(loadTutorial, 10000);
<?php endif ?>
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
