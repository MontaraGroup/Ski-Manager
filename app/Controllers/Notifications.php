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

    public function getLatestAsync()
    {
        $userId = auth()->id();
        if (!$userId) return $this->response->setJSON(["unread" => 0, "list" => []]);

        $db = db_connect();
        $unread = $db->table("notifications")->where("user_id", $userId)->where("is_read", 0)->countAllResults();
        $list = $db->table("notifications")
                    ->where("user_id", $userId)
                    ->orderBy("created_at", "DESC")
                    ->limit(5)
                    ->get()
                    ->getResultArray();

        return $this->response->setJSON(["unread" => $unread, "list" => $list]);
    }
}

