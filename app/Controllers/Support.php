<?php

namespace App\Controllers;

class Support extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $db->table('support_messages')->where('user_id', $userId)->where('sender', 'admin')->where('is_read', 0)->update(['is_read' => 1]);

        $messages = $db->table('support_messages')->where('user_id', $userId)->orderBy('created_at', 'ASC')->limit(100)->get()->getResultArray();

        return view('support/index', ['messages' => $messages]);
    }

    public function send()
    {
        $userId = auth()->id();
        $message = trim($this->request->getPost('message') ?? '');

        if (strlen($message) < 1 || strlen($message) > 1000) {
            return redirect()->back()->with('error', 'Message must be 1-1000 characters.');
        }

        db_connect()->table('support_messages')->insert([
            'user_id' => $userId,
            'sender' => 'player',
            'message' => $message,
        ]);

        return redirect()->to('/support')->with('success', 'Message sent! We\'ll reply soon.');
    }

    public function adminIndex(): string
    {
        if (auth()->id() !== 1) return redirect()->to('/dashboard');
        $db = db_connect();

        $conversations = $db->query("
            SELECT sm.user_id, u.username,
                (SELECT COUNT(*) FROM support_messages WHERE user_id = sm.user_id AND sender = 'player' AND is_read = 0) as unread,
                (SELECT message FROM support_messages WHERE user_id = sm.user_id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM support_messages WHERE user_id = sm.user_id ORDER BY created_at DESC LIMIT 1) as last_at
            FROM support_messages sm
            JOIN users u ON u.id = sm.user_id
            GROUP BY sm.user_id, u.username
            ORDER BY last_at DESC
        ")->getResultArray();

        return view('support/admin', ['conversations' => $conversations]);
    }

    public function adminView(int $userId): string
    {
        if (auth()->id() !== 1) return redirect()->to('/dashboard');
        $db = db_connect();

        $db->table('support_messages')->where('user_id', $userId)->where('sender', 'player')->where('is_read', 0)->update(['is_read' => 1]);

        $messages = $db->table('support_messages')->where('user_id', $userId)->orderBy('created_at', 'ASC')->get()->getResultArray();
        $player = $db->table('users')->where('id', $userId)->get()->getRowArray();

        return view('support/admin_chat', ['messages' => $messages, 'player' => $player, 'chatUserId' => $userId]);
    }

    public function adminReply(int $userId)
    {
        if (auth()->id() !== 1) return redirect()->to('/dashboard');
        $message = trim($this->request->getPost('message') ?? '');

        if (strlen($message) < 1) return redirect()->back()->with('error', 'Empty message.');

        db_connect()->table('support_messages')->insert([
            'user_id' => $userId,
            'sender' => 'admin',
            'message' => $message,
        ]);

        notify($userId, 'support', 'New support reply', 'The team responded to your message.', 'fa-solid fa-headset', '/support');

        return redirect()->to('/admin/support/' . $userId);
    }
}
