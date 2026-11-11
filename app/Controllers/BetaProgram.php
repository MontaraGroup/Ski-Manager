<?php

namespace App\Controllers;

use App\Libraries\Beta;

/** GET /beta: every feature at the beta level, with the player's switch and feedback */
class BetaProgram extends BaseController
{
    public function index()
    {
        $userId = (int) auth()->id();
        return view('beta/index', ['page' => Beta::state($userId) + ['commentMax' => Beta::COMMENT_MAX]]);
    }
}
