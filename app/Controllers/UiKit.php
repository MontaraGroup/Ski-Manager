<?php

namespace App\Controllers;

/**
 * /ui-kit, admins only: the HeroUI components in the Ski Manager theme, both themes, plus a
 * live call to the islands API. The reference page for moving pages to React (DESIGN.md).
 */
class UiKit extends BaseController
{
    public function index(): string
    {
        return view('ui_kit/index');
    }
}
