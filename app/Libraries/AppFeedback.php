<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Feedback from the Android app: a message, a category, the diagnostics the player ticked and an
 * optional screenshot. The message also lands in the player's support thread, so admins read and
 * answer it in the support inbox they already use; /admin/feedback shows the attachments.
 *
 * Screenshots live in writable/uploads/feedback (outside public/) under a random name and are only
 * served to admins. Diagnostics and screenshots are deleted after RETENTION_DAYS, as the app's
 * consent line promises; the message stays like any support message.
 */
class AppFeedback
{
    public const CATEGORIES = ['bug', 'idea', 'other'];
    public const MAX_MESSAGE = 2000;
    public const MAX_DIAGNOSTICS = 262144; // 256 KB of JSON
    public const MAX_SCREENSHOT = 3145728; // 3 MB
    public const PER_HOUR = 5;
    public const PER_DAY = 20;
    public const RETENTION_DAYS = 90;
    private const TYPES = ['image/png' => 'png', 'image/jpeg' => 'jpg'];

    public static function dir(): string
    {
        return WRITEPATH . 'uploads/feedback/';
    }

    /** Null when the player may send now, otherwise why not */
    public static function rateLimited(int $userId): ?string
    {
        $db = db_connect();
        $hour = $db->table('app_feedback')->where('user_id', $userId)->where('created_at >', date('Y-m-d H:i:s', time() - 3600))->countAllResults();
        if ($hour >= self::PER_HOUR) return 'You sent ' . self::PER_HOUR . ' reports in the last hour. Try again later.';
        $day = $db->table('app_feedback')->where('user_id', $userId)->where('created_at >', date('Y-m-d H:i:s', time() - 86400))->countAllResults();
        if ($day >= self::PER_DAY) return 'You sent ' . self::PER_DAY . ' reports today. Try again tomorrow.';
        return null;
    }

    /**
     * Field name to error, empty when the input is fine. $diagnostics is the raw JSON string from the app.
     */
    public static function validate(string $message, string $category, string $diagnostics, ?UploadedFile $shot): array
    {
        $errors = [];
        $length = mb_strlen($message);
        if ($length < 1) $errors['message'] = 'Write a message.';
        elseif ($length > self::MAX_MESSAGE) $errors['message'] = 'Keep the message under ' . self::MAX_MESSAGE . ' characters.';
        if (!in_array($category, self::CATEGORIES, true)) $errors['category'] = 'Pick bug, idea or other.';
        if ($diagnostics !== '') {
            if (strlen($diagnostics) > self::MAX_DIAGNOSTICS) $errors['diagnostics'] = 'The attached details are too large.';
            elseif (!is_array(json_decode($diagnostics, true))) $errors['diagnostics'] = 'The attached details could not be read.';
        }
        if ($shot !== null && $shot->getError() !== UPLOAD_ERR_NO_FILE) {
            if (!$shot->isValid()) $errors['screenshot'] = 'The screenshot did not upload.';
            elseif ($shot->getSize() > self::MAX_SCREENSHOT) $errors['screenshot'] = 'The screenshot is over 3 MB.';
            elseif (self::imageType($shot->getTempName()) === null) $errors['screenshot'] = 'The screenshot must be a PNG or JPEG.';
        }
        return $errors;
    }

    /** png or jpg, judged by the bytes rather than the name or the client's content type */
    private static function imageType(string $path): ?string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path) ?: '';
        $info = @getimagesize($path);
        if (!isset(self::TYPES[$mime]) || !$info || ($info['mime'] ?? '') !== $mime) return null;
        if ($info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000) return null;
        return self::TYPES[$mime];
    }

    /**
     * Saves the report, posts it into the support thread and tells the admins. Returns the report id.
     * Call validate() first.
     */
    public static function store(int $userId, string $message, string $category, string $diagnostics, ?UploadedFile $shot): int
    {
        self::purge();
        $db = db_connect();

        $diag = $diagnostics !== '' ? json_decode($diagnostics, true) : [];
        if (!empty($diag['include']['notificationHistory'])) {
            $diag['notificationHistory'] = array_map(fn($n) => [
                'title' => (string) $n['title'],
                'type' => (string) $n['type'],
                'read' => (bool) $n['is_read'],
                'at' => (string) $n['created_at'],
            ], $db->table('notifications')->select('title, type, is_read, created_at')->where('user_id', $userId)
                ->orderBy('id', 'DESC')->limit(20)->get()->getResultArray());
        }
        $device = $diag['device'] ?? [];
        $deviceLine = trim(($device['manufacturer'] ?? '') . ' ' . ($device['model'] ?? '') . (isset($device['android']) ? ' · Android ' . $device['android'] : ''));

        $file = null;
        if ($shot !== null && $shot->getError() !== UPLOAD_ERR_NO_FILE) {
            $ext = self::imageType($shot->getTempName());
            if ($ext !== null) {
                if (!is_dir(self::dir())) mkdir(self::dir(), 0775, true);
                $file = bin2hex(random_bytes(16)) . '.' . $ext;
                $shot->move(self::dir(), $file);
            }
        }

        $db->table('app_feedback')->insert([
            'user_id' => $userId,
            'category' => $category,
            'message' => $message,
            'app_version' => mb_substr((string) ($diag['app']['version'] ?? ''), 0, 40) ?: null,
            'device' => mb_substr($deviceLine, 0, 120) ?: null,
            'diagnostics' => $diag ? json_encode($diag, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            'screenshot' => $file,
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $db->insertID();

        // Into the support thread, so the reply happens where support replies already happen
        $label = ['bug' => 'Bug report', 'idea' => 'Idea', 'other' => 'Feedback'][$category];
        $db->table('support_messages')->insert([
            'user_id' => $userId,
            'sender' => 'player',
            'message' => mb_strimwidth($label . ' from the app (#' . $id . '): ' . $message, 0, 1000, '…'),
        ]);

        helper('notify');
        $username = $db->table('users')->select('username')->where('id', $userId)->get()->getRow('username') ?? ('Player #' . $userId);
        foreach (self::adminIds() as $adminId) {
            if ($adminId === $userId) continue;
            notify($adminId, 'support', $label . ' from ' . $username, mb_strimwidth($message, 0, 80, '…'), 'fa-solid fa-comment-dots', '/admin/feedback/' . $id);
        }

        return $id;
    }

    /** Drops diagnostics and screenshots older than RETENTION_DAYS. Cheap; runs on each submit and admin visit */
    public static function purge(): void
    {
        $db = db_connect();
        if (!$db->tableExists('app_feedback')) return;
        $cutoff = date('Y-m-d H:i:s', time() - self::RETENTION_DAYS * 86400);
        $old = $db->table('app_feedback')->select('id, screenshot')->where('created_at <', $cutoff)
            ->groupStart()->where('diagnostics IS NOT NULL')->orWhere('screenshot IS NOT NULL')->groupEnd()
            ->limit(200)->get()->getResultArray();
        foreach ($old as $row) {
            if ($row['screenshot'] && ($path = self::screenshotPath($row['screenshot'])) && is_file($path)) unlink($path);
            $db->table('app_feedback')->where('id', $row['id'])->update(['diagnostics' => null, 'screenshot' => null]);
        }
    }

    /** Full path of a stored screenshot, or null if the name isn't one this class made */
    public static function screenshotPath(string $name): ?string
    {
        return preg_match('/^[a-f0-9]{32}\.(png|jpg)$/', $name) ? self::dir() . $name : null;
    }

    private static function adminIds(): array
    {
        $ids = array_map('intval', array_column(db_connect()->table('auth_groups_users')->select('user_id')
            ->whereIn('group', ['admin', 'superadmin'])->get()->getResultArray(), 'user_id'));
        return array_values(array_unique(array_merge([1], $ids)));
    }
}
