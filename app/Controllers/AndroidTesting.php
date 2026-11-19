<?php

namespace App\Controllers;

use App\Libraries\AndroidTesting as Testing;
use App\Libraries\Beta;

/**
 * GET /beta/android: the player's access code for the Android app's closed test on Google Play.
 * An eligible player gets their code on the first visit (Testing::claim keeps one per account),
 * so the invite notification can say "see your access code" and the page shows it straight away.
 */
class AndroidTesting extends BaseController
{
    public function index()
    {
        $userId = (int) auth()->id();
        if (Beta::eligible($userId)) Testing::claim($userId);
        return view('beta/android', ['page' => Testing::state($userId)]);
    }
}
