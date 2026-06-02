<?php
namespace App\Controllers;
class ActivityLog extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $logs = $db->table('activity_log')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        return view('activity/index', ['logs' => $logs]);
    }
}
