<?php
namespace App\Controllers;
class SkiLessons extends BaseController
{
    public function index(): string { return view('lessons/index'); }
}
