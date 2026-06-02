<?php
namespace App\Controllers;
class Emergency extends BaseController
{
    public function index(): string { return view('emergency/index'); }
}
