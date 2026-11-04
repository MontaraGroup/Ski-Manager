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

        return view('notifications/index', ['page' => [
            'filter' => $filter === 'unread' ? 'unread' : 'all',
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
            'items' => array_map(fn($n) => [
                'id' => (int) $n['id'], 'title' => (string) $n['title'], 'message' => (string) $n['message'],
                'icon' => preg_match('/^[a-z0-9 -]+$/i', (string) $n['icon']) ? (string) $n['icon'] : 'fa-solid fa-bell',
                'isRead' => (bool) $n['is_read'], 'time' => date('M j, H:i', strtotime($n['created_at'])),
            ], $notifications),
        ]]);
    }

    /** GET /notifications/open/{id}: mark one of your notifications read and go where it points */
    public function open(int $id)
    {
        $db = db_connect();
        $n = $db->table('notifications')->where('id', $id)->where('user_id', auth()->id())->get()->getRowArray();
        if (!$n) return redirect()->to('/notifications');

        if (!(int) $n['is_read']) {
            $db->table('notifications')->where('id', $id)->update(['is_read' => 1]);
        }
        // Only follow on-site paths
        $link = (string) ($n['link'] ?? '');
        $safe = $link !== '' && $link[0] === '/' && !str_starts_with($link, '//');
        return redirect()->to($safe ? $link : '/notifications');
    }

    public function readAll()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 0)->update(['is_read' => 1]);
        return $this->done('All notifications marked as read.');
    }

    /** Old GET link: changing data on GET let link prefetchers mark everything read */
    public function readAllLegacy()
    {
        return redirect()->to('/notifications');
    }

    public function deleteAll()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->delete();
        return $this->done('All notifications cleared.');
    }

    public function deleteRead()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 1)->delete();
        return $this->done('Read notifications cleared.');
    }

    /** The page submits these in the background and expects JSON; plain form posts get a redirect */
    private function done(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'message' => $message, 'csrf_hash' => csrf_hash()]);
        }
        return redirect()->to('/notifications')->with('success', $message);
    }

    public function getLatestAsync()
    {
        $userId = auth()->id();
        if (!$userId) return $this->response->setJSON(["unread" => 0, "list" => []]);

        $db = db_connect();
        $unread = $db->table("notifications")->where("user_id", $userId)->where("is_read", 0)->countAllResults();
        $list = $db->table("notifications")->select("id, title, message, icon, link, is_read")
                    ->where("user_id", $userId)
                    ->orderBy("created_at", "DESC")
                    ->limit(5)
                    ->get()
                    ->getResultArray();

        return $this->response->setJSON(["unread" => $unread, "list" => $list]);
    }
}

