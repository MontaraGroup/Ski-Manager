<?php

namespace App\Controllers;

use App\Libraries\NotificationFeed;

class Notifications extends BaseController
{
    public function index(): string
    {
        return view('notifications/index', ['page' => $this->pageData()]);
    }

    /** GET /notifications/feed: the next page of the list, for "Load more" */
    public function feed()
    {
        return $this->response->setJSON($this->pageData());
    }

    /**
     * What the page shows; the mobile API sends the same data (Api\V1\App). Query: filter=all|unread,
     * category (NotificationFeed::CATEGORIES), before = id to continue after.
     */
    protected function pageData(): array
    {
        $userId = (int) auth()->id();
        $filter = $this->request->getGet('filter') === 'unread' ? 'unread' : 'all';
        $category = (string) ($this->request->getGet('category') ?? '');
        $category = isset(NotificationFeed::CATEGORIES[$category]) ? $category : null;
        $before = (int) ($this->request->getGet('before') ?? 0);

        $db = db_connect();
        $query = $db->table('notifications')->select('id, type, title, message, icon, link, is_read, created_at')
            ->where('user_id', $userId);
        if ($filter === 'unread') $query->where('is_read', 0);
        // Continue after the last row shown: older, or same second and lower id
        $after = $before > 0 ? $db->table('notifications')->select('created_at')->where('id', $before)->where('user_id', $userId)->get()->getRowArray() : null;
        if ($after) {
            $query->groupStart()->where('created_at <', $after['created_at'])
                ->orGroupStart()->where('created_at', $after['created_at'])->where('id <', $before)->groupEnd()->groupEnd();
        } elseif ($before > 0) {
            $query->where('id <', $before); // that row was deleted meanwhile
        }
        NotificationFeed::whereCategory($query, $category);
        $rows = $query->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->limit(NotificationFeed::PAGE_SIZE + 1)->get()->getResultArray();

        $more = count($rows) > NotificationFeed::PAGE_SIZE;
        $rows = array_slice($rows, 0, NotificationFeed::PAGE_SIZE);
        $counts = NotificationFeed::counts($userId);

        return [
            'filter' => $filter,
            'category' => $category,
            'unreadCount' => $counts['all']['unread'],
            'totalCount' => $counts['all']['total'],
            'categories' => array_map(fn($key, $label) => ['key' => $key, 'label' => $label] + $counts[$key], array_keys(NotificationFeed::CATEGORIES), NotificationFeed::CATEGORIES),
            'items' => array_map([NotificationFeed::class, 'item'], $rows),
            'nextCursor' => $more && $rows ? (int) end($rows)['id'] : null,
        ];
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
        return redirect()->to(NotificationFeed::link($n) ?? '/notifications');
    }

    /** POST /notifications/{id}/read: mark one read without leaving the page */
    public function markRead(int $id)
    {
        $userId = (int) auth()->id();
        db_connect()->table('notifications')->where('id', $id)->where('user_id', $userId)->update(['is_read' => 1]);
        return $this->done('Marked as read.');
    }

    /** Marks every unread one read, or only one category's when the form sends category */
    public function readAll()
    {
        $userId = (int) auth()->id();
        $category = (string) ($this->request->getPost('category') ?? '');
        $query = db_connect()->table('notifications')->where('user_id', $userId)->where('is_read', 0);
        $label = NotificationFeed::CATEGORIES[$category] ?? null;
        if ($label !== null) NotificationFeed::whereCategory($query, $category);
        $query->update(['is_read' => 1]);
        return $this->done($label !== null ? $label . ' notifications marked as read.' : 'All notifications marked as read.');
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
            return $this->response->setJSON([
                'success' => true, 'message' => $message, 'csrf_hash' => csrf_hash(),
                'unread' => unreadNotificationCount((int) auth()->id()),
            ]);
        }
        return redirect()->to('/notifications')->with('success', $message);
    }

    /** GET /api/notifications/live: the bell polls this */
    public function getLatestAsync()
    {
        $userId = (int) auth()->id();
        if (!$userId) return $this->response->setJSON(['unread' => 0, 'items' => []]);

        return $this->response->setJSON([
            'unread' => unreadNotificationCount($userId),
            'items' => NotificationFeed::latest($userId),
        ]);
    }
}
