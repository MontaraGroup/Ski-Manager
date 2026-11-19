<?php

namespace App\Controllers;

use App\Libraries\Beta;

/** GET /beta: every feature at the beta level, with the player's switch and feedback */
class BetaProgram extends BaseController
{
    public function index()
    {
        return view('beta/index', ['page' => $this->pageData()]);
    }

    /** What the page shows: the web view and the app API (Api\V1\App) both use it */
    protected function pageData(): array
    {
        $userId = (int) auth()->id();
        return Beta::state($userId) + ['commentMax' => Beta::COMMENT_MAX];
    }
}
