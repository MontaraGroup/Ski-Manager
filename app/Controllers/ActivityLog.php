<?php
namespace App\Controllers;
class ActivityLog extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $logs = $db->table('activity_log')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        return view('activity/index', ['page' => ['logs' => array_map(fn($l) => [
            'id' => (int) $l['id'], 'message' => (string) $l['message'], 'day' => (int) $l['game_day'], 'category' => (string) $l['category'],
            'icon' => preg_match('/^[a-z0-9 -]+$/i', (string) $l['icon']) ? (string) $l['icon'] : 'fa-solid fa-circle',
        ], $logs)]]);
    }
}
