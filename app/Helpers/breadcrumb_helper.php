<?php

if (!function_exists('breadcrumb')) {
    function breadcrumb(array $items): string
    {
        $html = '<div class="text-sm breadcrumbs mb-4"><ul>';
        foreach ($items as $label => $url) {
            if ($url) {
                $html .= '<li><a href="' . $url . '">' . esc($label) . '</a></li>';
            } else {
                $html .= '<li>' . esc($label) . '</li>';
            }
        }
        $html .= '</ul></div>';
        return $html;
    }
}
