<?php

namespace App\Libraries;

use Config\Services;

/**
 * Cap, the self-hosted CAPTCHA (capd at https://cap.ski-manager.net, /srv/ski-manager/cap on the VPS).
 * CAP_ENDPOINT is the site key's URL with a trailing slash ("https://cap.ski-manager.net/<siteKey>/");
 * CAP_SECRET is the key's secret (sk-...), server side only. Without both, Cap is off: no widget, no check.
 */
class Cap
{
    /** Name of the hidden field the widget adds to the form */
    public const FIELD = 'cap-token';

    public static function endpoint(): string
    {
        $url = trim((string) env('CAP_ENDPOINT', ''));
        return $url === '' ? '' : rtrim($url, '/') . '/';
    }

    public static function enabled(): bool
    {
        return self::endpoint() !== '' && trim((string) env('CAP_SECRET', '')) !== '';
    }

    /** The widget script, served by capd itself (Instance settings > Widget files) */
    public static function widgetUrl(): string
    {
        $parts = parse_url(self::endpoint());
        return isset($parts['scheme'], $parts['host']) ? $parts['scheme'] . '://' . $parts['host'] . '/assets/widget.js' : '';
    }

    /**
     * The widget script tag, for the head of a page with a widget. The widget downloads its solver
     * (WebAssembly) from cdn.jsdelivr.net unless told otherwise, and the site's CSP blocks that host,
     * so the widget could never finish ("Error. Try again."). capd serves the same files under /assets.
     */
    public static function scriptTag(): string
    {
        if (!self::enabled() || self::widgetUrl() === '') return '';
        $assets = substr(self::widgetUrl(), 0, -strlen('widget.js'));
        return '<script>window.CAP_CUSTOM_WASM_URL=' . json_encode($assets . 'cap_wasm_bg.wasm', JSON_UNESCAPED_SLASHES)
            . ';window.CAP_CUSTOM_HASHWX_URL=' . json_encode($assets . 'hashwx.wasm', JSON_UNESCAPED_SLASHES) . ';</script>'
            . '<script src="' . esc(self::widgetUrl(), 'attr') . '" async></script>';
    }

    /**
     * Checks a widget token with capd, exactly once (tokens are single use). Fails closed: a missing
     * token, a timeout or an error all count as not verified.
     */
    public static function verify(?string $token): bool
    {
        $token = trim((string) $token);
        if ($token === '' || strlen($token) > 2048 || !self::enabled()) {
            return false;
        }
        try {
            // Own client: a shared curlrequest keeps whatever options an earlier caller gave it
            $client   = Services::curlrequest(['timeout' => 5, 'connect_timeout' => 3, 'http_errors' => false], null, null, false);
            $response = $client->post(self::endpoint() . 'siteverify', [
                'json' => ['secret' => trim((string) env('CAP_SECRET')), 'response' => $token],
            ]);
            $body = json_decode((string) $response->getBody(), true);
            if ($response->getStatusCode() === 200 && is_array($body) && ($body['success'] ?? false) === true) {
                return true;
            }
            log_message('notice', 'Cap verify failed: HTTP ' . $response->getStatusCode() . ' ' . (is_array($body) ? (string) ($body['error'] ?? '') : ''));
        } catch (\Throwable $e) {
            log_message('error', 'Cap verify error: ' . $e->getMessage());
        }
        return false;
    }
}
