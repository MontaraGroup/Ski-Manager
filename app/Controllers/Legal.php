<?php

namespace App\Controllers;

class Legal extends BaseController
{
    public function terms(): string
    {
        return view('legal/terms');
    }

    public function privacy(): string
    {
        return view('legal/privacy');
    }

    public function cookies(): string
    {
        return view('legal/cookies');
    }
    public function disclaimer(): string
    {
        return view("legal/disclaimer");
    }
    public function sitemap(): string
    {
        return view("legal/sitemap");
    }
}
