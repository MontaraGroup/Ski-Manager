<?php

namespace App\Controllers;

use App\Libraries\AndroidTesting as Testing;

/** GET /beta/android: claim an access code for the Android app's closed test on Google Play */
class AndroidTesting extends BaseController
{
    public function index()
    {
        return view('beta/android', ['page' => Testing::state((int) auth()->id())]);
    }
}
