<?php

use App\Libraries\Islands;

if (!function_exists('island')) {
    /** Mount point for a React island (see App\Libraries\Islands) */
    function island(string $name, array $props = [], string $fallback = ''): string
    {
        return Islands::render($name, $props, $fallback);
    }
}
