<?php

namespace App\Controllers;

class Notifications extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $notifications = $db->table('notifications')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        $db->table('notifications')->where('user_id', $userId)->where('is_read', 0)->update(['is_read' => 1]);

        return view('notifications/index', ['notifications' => $notifications]);
    }

    public function readAll()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->update(['is_read' => 1]);
        return redirect()->back();
    }
}
