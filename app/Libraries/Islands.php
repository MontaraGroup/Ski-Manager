<?php

namespace App\Libraries;

/**
 * React islands on PHP pages (resources/js/main.tsx). A view prints a mount point with
 * island('Name', $props); the layout then adds the bundle and the CSRF meta tag to <head>,
 * on those pages only. Views render before their layout, so by the time <head> is printed
 * every island on the page has been registered.
 */
class Islands
{
    public const ENTRY = 'resources/js/main.tsx';

    private static array $used = [];

    /**
     * Mount point plus props. $fallback is plain HTML shown until the island mounts (and
     * without JavaScript); it is printed as given, so escape anything a player wrote.
     */
    public static function render(string $name, array $props = [], string $fallback = ''): string
    {
        self::$used[$name] = true;
        $json = json_encode($props, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return '<div data-island="' . esc($name, 'attr') . '">' . $fallback . '</div>'
            . '<script type="application/json">' . $json . '</script>';
    }

    /**
     * $always: the layout passes true when it will mount islands itself further down the page
     * (the signed-in shell), since <head> is printed before the layout body runs
     */
    public static function headTags(bool $always = false): string
    {
        if (self::$used === [] && !$always) return '';
        return '<meta name="csrf-token" content="' . esc(csrf_hash(), 'attr') . '" data-header="' . esc(config('Security')->headerName, 'attr') . '">'
            . "\n    " . Vite::tags(self::ENTRY);
    }
}
