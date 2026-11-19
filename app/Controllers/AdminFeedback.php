<?php

namespace App\Controllers;

use App\Libraries\AppFeedback;

/** /admin/feedback: reports sent from the Android app, with the diagnostics and screenshot the player attached */
class AdminFeedback extends BaseController
{
    private const STATUSES = ['new', 'read', 'closed'];

    private function isAdmin(): bool
    {
        $user = auth()->user();
        return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
    }

    /** GET /admin/feedback and /admin/feedback/{id}: the list, with one report open */
    public function index(?int $id = null): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        AppFeedback::purge();
        helper('time');
        $db = db_connect();

        $rows = $db->table('app_feedback f')->select('f.id, f.user_id, f.category, f.message, f.app_version, f.device, f.status, f.created_at, f.screenshot, u.username')
            ->join('users u', 'u.id = f.user_id', 'left')->orderBy('f.id', 'DESC')->limit(200)->get()->getResultArray();

        $open = null;
        if ($id !== null) {
            $row = $db->table('app_feedback f')->select('f.*, u.username')->join('users u', 'u.id = f.user_id', 'left')->where('f.id', $id)->get()->getRowArray();
            if (!$row) return redirect()->to('/admin/feedback')->with('error', 'That report no longer exists.');
            if ($row['status'] === 'new') {
                $db->table('app_feedback')->where('id', $id)->update(['status' => 'read']);
                $row['status'] = 'read';
                foreach ($rows as &$r) if ((int) $r['id'] === $id) $r['status'] = 'read';
                unset($r);
            }
            $open = $this->summary($row) + [
                'diagnostics' => $row['diagnostics'] ? json_decode($row['diagnostics'], true) : null,
                'screenshotUrl' => $row['screenshot'] ? '/admin/feedback/' . $id . '/screenshot' : null,
                'purged' => !$row['diagnostics'] && !$row['screenshot'] && strtotime($row['created_at']) < time() - AppFeedback::RETENTION_DAYS * 86400,
            ];
        }

        $page = [
            'reports' => array_map(fn($r) => $this->summary($r), $rows),
            'open' => $open,
            'retentionDays' => AppFeedback::RETENTION_DAYS,
        ];
        return view('admin/feedback', ['page' => $page]);
    }

    private function summary(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'userId' => (int) $r['user_id'],
            'username' => $r['username'] ?? ('Player #' . $r['user_id']),
            'category' => $r['category'],
            'message' => $r['message'],
            'appVersion' => $r['app_version'],
            'device' => $r['device'],
            'status' => $r['status'],
            'hasScreenshot' => !empty($r['screenshot']),
            'timeAgo' => timeAgo($r['created_at']),
            'createdAt' => $r['created_at'],
        ];
    }

    /** GET /admin/feedback/{id}/screenshot: streamed from writable/, never a public URL */
    public function screenshot(int $id)
    {
        if (!$this->isAdmin()) return $this->response->setStatusCode(404);
        $name = db_connect()->table('app_feedback')->select('screenshot')->where('id', $id)->get()->getRow('screenshot');
        $path = $name ? AppFeedback::screenshotPath($name) : null;
        if (!$path || !is_file($path)) return $this->response->setStatusCode(404);

        return $this->response
            ->setHeader('Content-Type', str_ends_with($path, '.png') ? 'image/png' : 'image/jpeg')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Content-Disposition', 'inline; filename="feedback-' . $id . '"')
            ->setBody((string) file_get_contents($path));
    }

    /** POST /admin/feedback/{id}/status with status=new|read|closed */
    public function setStatus(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $status = (string) $this->request->getPost('status');
        if (!in_array($status, self::STATUSES, true)) return redirect()->back()->with('error', 'Unknown status.');
        db_connect()->table('app_feedback')->where('id', $id)->update(['status' => $status]);
        return redirect()->to('/admin/feedback/' . $id)->with('success', $status === 'closed' ? 'Report closed.' : 'Report marked ' . $status . '.');
    }
}
