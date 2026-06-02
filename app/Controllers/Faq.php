<?php

namespace App\Controllers;

class Faq extends BaseController
{
    public function index(): string
    {
        return view('faq/index');
    }
}
