<?php

namespace App\Controllers;

use CodeIgniter\Database\BaseConnection;

class Updates extends BaseController
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    public function index(): string
    {
        $formattedUpdates = [];

        try {
            $updates = $this->db->table('updates')->orderBy('released_at', 'DESC')->orderBy('version', 'DESC')->get()->getResultArray();

            $typeMeta = [
                'new' => ['label' => 'New Features', 'icon' => 'sparkles', 'badge' => 'success'],
                'improved' => ['label' => 'Improvements', 'icon' => 'arrow-up-long', 'badge' => 'info'],
                'fixed' => ['label' => 'Bug Fixes', 'icon' => 'wrench', 'badge' => 'warning'],
                'removed' => ['label' => 'Removed', 'icon' => 'trash', 'badge' => 'danger'],
            ];

            foreach ($updates as $idx => $update) {
                $items = $this->db->table('update_items')
                    ->where('update_id', $update['id'])
                    ->orderBy("FIELD(type, 'new', 'improved', 'fixed', 'removed'), sort_order", '', false)
                    ->get()->getResultArray();

                $categories = [];
                foreach (['new', 'improved', 'fixed', 'removed'] as $t) {
                    $filtered = array_filter($items, fn($i) => $i['type'] === $t);
                    if (!empty($filtered)) {
                        $categories[] = [
                            'type' => $t,
                            'label' => $typeMeta[$t]['label'],
                            'icon' => $typeMeta[$t]['icon'],
                            'badge' => $typeMeta[$t]['badge'],
                            'items' => array_map(fn($it) => [
                                'title' => $it['title'] ?? '',
                                'description' => $it['description'] ?? '',
                            ], array_values($filtered)),
                        ];
                    }
                }

                $date = !empty($update['released_at']) ? date('F d, Y', strtotime($update['released_at'])) : '';

                $formattedUpdates[] = [
                    'id' => (int) $update['id'],
                    'version' => $update['version'],
                    'title' => $update['title'] ?? ('Release ' . $update['version']),
                    'description' => $update['description'] ?? '',
                    'release_type' => $update['release_type'] ?? 'patch',
                    'released_at' => $update['released_at'] ?? '',
                    'releasedAtFormatted' => $date,
                    'isLatest' => $idx === 0,
                    'categories' => $categories,
                ];
            }
        } catch (\Throwable $e) {
            $formattedUpdates = [];
        }

        if (empty($formattedUpdates)) {
            $archivePath = APPPATH . 'Views/updates/updates.json';
            if (is_file($archivePath)) {
                $json = json_decode((string) file_get_contents($archivePath), true);
                if (is_array($json)) {
                    $formattedUpdates = $json;
                }
            }
        }

        $page = [
            'updates' => $formattedUpdates,
        ];

        return view('updates/index', ['page' => $page]);
    }
}
