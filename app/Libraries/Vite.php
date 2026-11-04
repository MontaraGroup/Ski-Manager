<?php

namespace App\Libraries;

/**
 * Script and style tags for a Vite entry (vite.config.mts). In production the hashed file names
 * come from public/build/.vite/manifest.json; with VITE_DEV_SERVER set in .env (for example
 * http://localhost:5173) the tags point at `npm run dev` instead, with React hot reload.
 */
class Vite
{
    private static function attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function tags(string $entry): string
    {
        $dev = rtrim((string) env('VITE_DEV_SERVER', ''), '/');
        if ($dev !== '') {
            return self::devTags($dev, $entry);
        }

        $manifest = self::manifest();
        if (!isset($manifest[$entry])) {
            log_message('error', 'Vite entry ' . $entry . ' is missing from public/build/.vite/manifest.json; run npm run build');
            return '';
        }

        $chunk = $manifest[$entry];
        $tags = [];
        foreach (self::cssFor($manifest, $entry) as $css) {
            $tags[] = '<link rel="stylesheet" href="/build/' . self::attr($css) . '">';
        }
        foreach ($chunk['imports'] ?? [] as $import) {
            if (isset($manifest[$import]['file'])) {
                $tags[] = '<link rel="modulepreload" href="/build/' . self::attr($manifest[$import]['file']) . '">';
            }
        }
        // data-cfasync="false": Cloudflare Rocket Loader must leave module scripts alone (they are
        // deferred already, and its reordering broke the Sentry init once)
        $tags[] = '<script type="module" data-cfasync="false" src="/build/' . self::attr($chunk['file']) . '"></script>';
        return implode("\n    ", $tags);
    }

    private static function devTags(string $dev, string $entry): string
    {
        // @vitejs/plugin-react needs this preamble when the HTML isn't served by Vite
        return '<script type="module" data-cfasync="false">'
            . 'import RefreshRuntime from ' . json_encode($dev . '/@react-refresh') . ';'
            . 'RefreshRuntime.injectIntoGlobalHook(window);'
            . 'window.$RefreshReg$ = () => {}; window.$RefreshSig$ = () => (type) => type;'
            . 'window.__vite_plugin_react_preamble_installed__ = true;'
            . '</script>' . "\n    "
            . '<script type="module" data-cfasync="false" src="' . self::attr($dev . '/@vite/client') . '"></script>' . "\n    "
            . '<script type="module" data-cfasync="false" src="' . self::attr($dev . '/' . $entry) . '"></script>';
    }

    /** CSS of the entry and of the chunks it imports statically */
    private static function cssFor(array $manifest, string $key, array &$seen = []): array
    {
        if (isset($seen[$key]) || !isset($manifest[$key])) return [];
        $seen[$key] = true;
        $css = $manifest[$key]['css'] ?? [];
        foreach ($manifest[$key]['imports'] ?? [] as $import) {
            $css = array_merge($css, self::cssFor($manifest, $import, $seen));
        }
        return array_values(array_unique($css));
    }

    private static function manifest(): array
    {
        static $manifest = null;
        if ($manifest === null) {
            $path = FCPATH . 'build/.vite/manifest.json';
            $manifest = is_file($path) ? (json_decode((string) file_get_contents($path), true) ?: []) : [];
        }
        return $manifest;
    }
}
