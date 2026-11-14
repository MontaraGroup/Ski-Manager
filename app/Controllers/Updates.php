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

            // Roadmap items that shipped in a release link to it as /updates#update-{id}
            $fromRoadmap = [];
            if ($this->db->fieldExists('updates_url', 'roadmap_items')) {
                $shipped = $this->db->table('roadmap_items')->select('id, title, updates_url')
                    ->like('updates_url', '/updates#update-', 'after')->get()->getResultArray();
                foreach ($shipped as $r) {
                    $fromRoadmap[(int) substr((string) $r['updates_url'], strlen('/updates#update-'))][] = ['id' => (int) $r['id'], 'title' => (string) $r['title']];
                }
            }

            $itemsByUpdate = [];
            $updateIds = array_values(array_filter(array_map(fn($u) => (int) ($u['id'] ?? 0), $updates)));
            if ($updateIds) {
                $allItems = $this->db->table('update_items')
                    ->whereIn('update_id', $updateIds)
                    ->orderBy("FIELD(type, 'new', 'improved', 'fixed', 'removed'), sort_order", '', false)
                    ->get()->getResultArray();
                foreach ($allItems as $it) {
                    $itemsByUpdate[(int) $it['update_id']][] = $it;
                }
            }

            foreach ($updates as $idx => $update) {
                $items = $itemsByUpdate[(int) $update['id']] ?? [];

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
                                // update_items keeps the line in `text`; it has no title/description columns
                                'title' => $it['title'] ?? '',
                                'description' => $it['description'] ?? $it['text'] ?? '',
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
                    'roadmapItems' => $fromRoadmap[(int) $update['id']] ?? [],
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
