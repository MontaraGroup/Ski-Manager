<?php

namespace App\Controllers;

class Support extends BaseController
{
    private const MAX_LENGTH = 1000;
    private const MAX_PER_HOUR = 10;

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $db->table('support_messages')->where('user_id', $userId)->where('sender', 'admin')->where('is_read', 0)->update(['is_read' => 1]);

        // Newest 100, shown oldest first
        $messages = array_reverse($db->table('support_messages')->where('user_id', $userId)->orderBy('id', 'DESC')->limit(100)->get()->getResultArray());

        $formattedMessages = array_map(fn($r) => [
            'id' => (int) $r['id'],
            'sender' => (string) $r['sender'],
            'message' => (string) $r['message'],
            'time' => date('g:ia', strtotime($r['created_at'])),
            'date' => date('Y-m-d', strtotime($r['created_at'])),
            'dateFormatted' => date('Y-m-d', strtotime($r['created_at'])) === date('Y-m-d')
                ? 'Today'
                : (date('Y-m-d', strtotime($r['created_at'])) === date('Y-m-d', strtotime('-1 day'))
                    ? 'Yesterday'
                    : date('F j, Y', strtotime($r['created_at']))),
        ], $messages);

        $page = [
            'messages' => $formattedMessages,
            'lastId' => $messages ? (int) end($messages)['id'] : 0,
            'maxLength' => self::MAX_LENGTH,
            'defaultMessage' => (string) (old('message') ?? ''),
        ];

        return view('support/index', ['page' => $page]);
    }

    /** GET /support/messages?after={id}: new messages for the open chat (polled while the tab is visible) */
    public function poll()
    {
        $userId = auth()->id();
        $after = max(0, (int) $this->request->getGet('after'));
        $db = db_connect();

        $rows = $db->table('support_messages')->select('id, sender, message, created_at')
            ->where('user_id', $userId)->where('id >', $after)->orderBy('id', 'ASC')->limit(50)->get()->getResultArray();
        if ($rows) {
            $db->table('support_messages')->where('user_id', $userId)->where('sender', 'admin')->where('is_read', 0)->update(['is_read' => 1]);
        }

        return $this->response->setJSON(array_map(fn($r) => [
            'id'      => (int) $r['id'],
            'sender'  => $r['sender'],
            'message' => $r['message'],
            'time'    => date('g:ia', strtotime($r['created_at'])),
            'date'    => date('Y-m-d', strtotime($r['created_at'])),
        ], $rows));
    }

    public function send()
    {
        $userId = auth()->id();
        $message = trim($this->request->getPost('message') ?? '');
        $length = mb_strlen($message);

        if ($length < 1 || $length > self::MAX_LENGTH) {
            return redirect()->to('/support')->withInput()->with('error', 'Messages can be up to ' . self::MAX_LENGTH . ' characters.');
        }

        $db = db_connect();
        $recent = $db->table('support_messages')->where('user_id', $userId)->where('sender', 'player')
            ->where('created_at >', date('Y-m-d H:i:s', strtotime('-1 hour')))->countAllResults();
        if ($recent >= self::MAX_PER_HOUR) {
            return redirect()->to('/support')->withInput()->with('error', "You've sent " . self::MAX_PER_HOUR . ' messages in the last hour. Please wait a little before sending more.');
        }

        $db->table('support_messages')->insert([
            'user_id' => $userId,
            'sender' => 'player',
            'message' => $message,
        ]);

        // Let admins know in-game instead of relying on them checking the inbox
        $username = auth()->user()->username ?? ('Player #' . $userId);
        foreach ($this->adminIds() as $adminId) {
            if ($adminId === (int) $userId) continue;
            notify($adminId, 'support', 'Support message from ' . $username, mb_strimwidth($message, 0, 80, '…'), 'fa-solid fa-headset', '/admin/support/' . $userId);
        }

        return redirect()->to('/support')->with('success', 'Sent. You\'ll get a notification when we reply.');
    }

    /** Same rule as AdminFilter: user #1 or anyone in the admin/superadmin groups */
    private function isAdmin(): bool
    {
        $user = auth()->user();
        return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
    }

    private function adminIds(): array
    {
        $ids = array_map('intval', array_column(db_connect()->table('auth_groups_users')->select('user_id')
            ->whereIn('group', ['admin', 'superadmin'])->get()->getResultArray(), 'user_id'));
        return array_values(array_unique(array_merge([1], $ids)));
    }

    public function adminIndex(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();
        helper(['time']);

        $conversationsRaw = $db->query("
            SELECT sm.user_id, u.username,
                (SELECT COUNT(*) FROM support_messages WHERE user_id = sm.user_id AND sender = 'player' AND is_read = 0) as unread,
                (SELECT message FROM support_messages WHERE user_id = sm.user_id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM support_messages WHERE user_id = sm.user_id ORDER BY created_at DESC LIMIT 1) as last_at
            FROM support_messages sm
            JOIN users u ON u.id = sm.user_id
            GROUP BY sm.user_id, u.username
            ORDER BY last_at DESC
        ")->getResultArray();

        $conversations = array_map(function($c) {
            return [
                'user_id' => (int) $c['user_id'],
                'username' => $c['username'] ?? ('User #' . $c['user_id']),
                'unread' => (int) $c['unread'],
                'last_message' => $c['last_message'] ?? '',
                'timeAgo' => !empty($c['last_at']) ? timeAgo($c['last_at']) : '',
            ];
        }, $conversationsRaw);

        $page = [
            'conversations' => $conversations,
            'totalUnread' => array_sum(array_map(fn($c) => (int) $c['unread'], $conversationsRaw)),
        ];

        return view('support/admin', ['page' => $page]);
    }

    public function adminView(int $userId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();

        $db->table('support_messages')->where('user_id', $userId)->where('sender', 'player')->where('is_read', 0)->update(['is_read' => 1]);

        $messagesRaw = $db->table('support_messages')->where('user_id', $userId)->orderBy('created_at', 'ASC')->get()->getResultArray();
        $player = $db->table('users')->where('id', $userId)->get()->getRowArray();

        $today = date('Y-m-d');
        $yest = date('Y-m-d', strtotime('-1 day'));
        $lastDate = null;
        $messages = [];

        foreach ($messagesRaw as $msg) {
            $msgDate = date('Y-m-d', strtotime($msg['created_at']));
            $dateLabel = null;
            if ($msgDate !== $lastDate) {
                $lastDate = $msgDate;
                $dateLabel = $msgDate === $today ? 'Today' : ($msgDate === $yest ? 'Yesterday' : date('F j, Y', strtotime($msg['created_at'])));
            }

            $messages[] = [
                'id' => (int) $msg['id'],
                'sender' => (string) $msg['sender'],
                'message' => (string) $msg['message'],
                'is_read' => (bool) $msg['is_read'],
                'timeFormatted' => date('g:ia', strtotime($msg['created_at'])),
                'dateLabel' => $dateLabel,
            ];
        }

        $page = [
            'chatUserId' => (int) $userId,
            'player' => [
                'id' => (int) ($player['id'] ?? $userId),
                'username' => $player['username'] ?? ('User #' . $userId),
            ],
            'messages' => $messages,
        ];

        return view('support/admin_chat', ['page' => $page]);
    }

    public function adminReply(int $userId)
    {
        if (!$this->isAdmin()) return redirect()->to('/dashboard');
        $message = trim($this->request->getPost('message') ?? '');

        if (mb_strlen($message) < 1 || mb_strlen($message) > 2000) return redirect()->back()->with('error', 'Message must be 1-2000 characters.');

        db_connect()->table('support_messages')->insert([
            'user_id' => $userId,
            'sender' => 'admin',
            'message' => $message,
        ]);

        notify($userId, 'support', 'New support reply', 'The team responded to your message.', 'fa-solid fa-headset', '/support');

        return redirect()->to('/admin/support/' . $userId);
    }

    public function editMessage($id)
    {
        if (!auth()->user() || (!auth()->user()->inGroup('admin') && (int)auth()->id() !== 1)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Unauthorized access.']);
        }

        $message = trim($this->request->getPost('message') ?? '');
        if (mb_strlen($message) < 1 || mb_strlen($message) > 2000) {
            return $this->response->setJSON(['success' => false, 'error' => 'Message length must be between 1 and 2000 characters.']);
        }

        $db = db_connect();
        $msg = $db->table('support_messages')->where('id', $id)->get()->getRowArray();

        if (!$msg || $msg['sender'] !== 'admin') {
            return $this->response->setJSON(['success' => false, 'error' => 'Message not found or not editable.']);
        }

        $db->table('support_messages')->where('id', $id)->update(['message' => $message]);

        return $this->response->setJSON(['success' => true]);
    }

    public function deleteMessage($id)
    {
        if (!auth()->user() || (!auth()->user()->inGroup('admin') && (int)auth()->id() !== 1)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Unauthorized access.']);
        }

        $db = db_connect();
        $msg = $db->table('support_messages')->where('id', $id)->get()->getRowArray();

        if (!$msg || $msg['sender'] !== 'admin') {
            return $this->response->setJSON(['success' => false, 'error' => 'Message not found or cannot be unsent.']);
        }

        $db->table('support_messages')->where('id', $id)->delete();

        return $this->response->setJSON(['success' => true]);
    }
}
