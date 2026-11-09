<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Stores the default settings for the ContentSecurityPolicy, if you
 * choose to use it. The values here will be read in and set as defaults
 * for the site. If needed, they can be overridden on a page-by-page basis.
 *
 * Suggested reference for explanations:
 *
 * @see https://www.html5rocks.com/en/tutorials/security/content-security-policy/
 */
class ContentSecurityPolicy extends BaseConfig
{
    // -------------------------------------------------------------------------
    // Broadbrush CSP management
    // -------------------------------------------------------------------------

    /**
     * Default CSP report context.
     * Report-only mode allows verifying all third-party integrations (AdSense, Sentry, GTM)
     * without blocking functionality in production.
     */
    public bool $reportOnly = true;

    /**
     * Specifies a URL where a browser will send reports
     * when a content security policy is violated.
     */
    public ?string $reportURI = null;

    /**
     * Specifies a reporting endpoint to which violation reports ought to be sent.
     */
    public ?string $reportTo = null;

    /**
     * Instructs user agents to rewrite URL schemes, changing
     * HTTP to HTTPS. This directive is for websites with
     * large numbers of old URLs that need to be rewritten.
     */
    public bool $upgradeInsecureRequests = true;

    // -------------------------------------------------------------------------
    // CSP DIRECTIVES SETTINGS
    // NOTE: once you set a policy to 'none', it cannot be further restricted
    // -------------------------------------------------------------------------

    /**
     * Will default to `'self'` if not overridden
     *
     * @var list<string>|string|null
     */
    public $defaultSrc = 'self';

    /**
     * Lists allowed scripts' URLs.
     *
     * @var list<string>|string
     */
    public $scriptSrc = [
        'self',
        'unsafe-inline',
        'unsafe-eval',
        'https://browser.sentry-cdn.com',
        'https://www.googletagmanager.com',
        'https://kit.fontawesome.com',
        'https://ka-f.fontawesome.com',
        'https://pagead2.googlesyndication.com',
        'https://fundingchoicesmessages.google.com',
        'https://*.google.com',
        'https://*.googlesyndication.com',
        'https://*.doubleclick.net',
        'https://ep1.adtrafficquality.google',
        'https://ep2.adtrafficquality.google',
        'https://static.cloudflareinsights.com',
    ];

    /**
     * Specifies valid sources for JavaScript <script> elements.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcElem = [
        'self',
        'unsafe-inline',
        'unsafe-eval',
        'https://browser.sentry-cdn.com',
        'https://www.googletagmanager.com',
        'https://kit.fontawesome.com',
        'https://ka-f.fontawesome.com',
        'https://pagead2.googlesyndication.com',
        'https://fundingchoicesmessages.google.com',
        'https://*.google.com',
        'https://*.googlesyndication.com',
        'https://*.doubleclick.net',
        'https://ep1.adtrafficquality.google',
        'https://ep2.adtrafficquality.google',
        'https://static.cloudflareinsights.com',
    ];

    /**
     * Specifies valid sources for JavaScript inline event
     * handlers and JavaScript URLs.
     *
     * @var list<string>|string
     */
    public array|string $scriptSrcAttr = ['self', 'unsafe-inline'];

    /**
     * Lists allowed stylesheets' URLs.
     *
     * @var list<string>|string
     */
    public $styleSrc = [
        'self',
        'unsafe-inline',
        'https://fonts.googleapis.com',
        'https://ka-f.fontawesome.com',
        'https://cdnjs.cloudflare.com',
    ];

    /**
     * Specifies valid sources for stylesheets <link> elements.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcElem = [
        'self',
        'unsafe-inline',
        'https://fonts.googleapis.com',
        'https://ka-f.fontawesome.com',
        'https://cdnjs.cloudflare.com',
    ];

    /**
     * Specifies valid sources for stylesheets inline
     * style attributes and `<style>` elements.
     *
     * @var list<string>|string
     */
    public array|string $styleSrcAttr = ['self', 'unsafe-inline'];

    /**
     * Defines the origins from which images can be loaded.
     *
     * @var list<string>|string
     */
    public $imageSrc = [
        'self',
        'data:',
        'blob:',
        'https:',
    ];

    /**
     * Restricts the URLs that can appear in a page's `<base>` element.
     *
     * Will default to self if not overridden
     *
     * @var list<string>|string|null
     */
    public $baseURI = 'self';

    /**
     * Lists the URLs for workers and embedded frame contents
     *
     * @var list<string>|string
     */
    public $childSrc = [
        'self',
        'blob:',
    ];

    /**
     * Limits the origins that you can connect to (via XHR,
     * WebSockets, and EventSource).
     *
     * @var list<string>|string
     */
    public $connectSrc = [
        'self',
        'https://*.sentry.io',
        'https://*.ingest.sentry.io',
        'https://*.google-analytics.com',
        'https://*.analytics.google.com',
        'https://*.googletagmanager.com',
        'https://ka-f.fontawesome.com',
        'https://pagead2.googlesyndication.com',
        'https://*.doubleclick.net',
        'https://static.cloudflareinsights.com',
        'https://ep1.adtrafficquality.google',
        'https://ep2.adtrafficquality.google',
        'https://*.adtrafficquality.google',
    ];

    /**
     * Specifies the origins that can serve web fonts.
     *
     * @var list<string>|string
     */
    public $fontSrc = [
        'self',
        'data:',
        'https://fonts.gstatic.com',
        'https://ka-f.fontawesome.com',
        'https://cdnjs.cloudflare.com',
    ];

    /**
     * Lists valid endpoints for submission from `<form>` tags.
     *
     * @var list<string>|string
     */
    public $formAction = 'self';

    /**
     * Specifies the sources that can embed the current page.
     * This directive applies to `<frame>`, `<iframe>`, `<embed>`,
     * and `<applet>` tags. This directive can't be used in
     * `<meta>` tags and applies only to non-HTML resources.
     *
     * @var list<string>|string|null
     */
    public $frameAncestors = 'self';

    /**
     * The frame-src directive restricts the URLs which may
     * be loaded into nested browsing contexts.
     *
     * @var list<string>|string|null
     */
    public $frameSrc = [
        'self',
        'https://www.google.com',
        'https://*.google.com',
        'https://*.googlesyndication.com',
        'https://*.doubleclick.net',
        'https://fundingchoicesmessages.google.com',
        'https://ep1.adtrafficquality.google',
        'https://ep2.adtrafficquality.google',
        'https://*.adtrafficquality.google',
    ];

    /**
     * Restricts the origins allowed to deliver video and audio.
     *
     * @var list<string>|string|null
     */
    public $mediaSrc = 'self';

    /**
     * Allows control over Flash and other plugins.
     *
     * @var list<string>|string
     */
    public $objectSrc = 'none';

    /**
     * @var list<string>|string|null
     */
    public $manifestSrc = 'self';

    /**
     * @var list<string>|string
     */
    public array|string $workerSrc = ['self', 'blob:'];

    /**
     * Limits the kinds of plugins a page may invoke.
     *
     * @var list<string>|string|null
     */
    public $pluginTypes;

    /**
     * List of actions allowed.
     *
     * @var list<string>|string|null
     */
    public $sandbox;

    /**
     * Nonce placeholder for style tags.
     */
    public string $styleNonceTag = '{csp-style-nonce}';

    /**
     * Nonce placeholder for script tags.
     */
    public string $scriptNonceTag = '{csp-script-nonce}';

    /**
     * Replace nonce tag automatically?
     */
    public bool $autoNonce = false;
}
