<?php

namespace App\Controllers;

class Notifications extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $filter = $this->request->getGet('filter') ?? 'all';

        $query = $db->table('notifications')->where('user_id', $userId);
        if ($filter === 'unread') $query->where('is_read', 0);
        $notifications = $query->orderBy('created_at', 'DESC')->limit(100)->get()->getResultArray();

        $unreadCount = $db->table('notifications')->where('user_id', $userId)->where('is_read', 0)->countAllResults();
        $totalCount = $db->table('notifications')->where('user_id', $userId)->countAllResults();

        return view('notifications/index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
        ]);
    }

    public function readAll()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->update(['is_read' => 1]);
        return redirect()->to('/notifications')->with('success', 'All notifications marked as read.');
    }

    public function deleteAll()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->delete();
        return redirect()->to('/notifications')->with('success', 'All notifications cleared.');
    }

    public function deleteRead()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 1)->delete();
        return redirect()->to('/notifications')->with('success', 'Read notifications cleared.');
    }
}
