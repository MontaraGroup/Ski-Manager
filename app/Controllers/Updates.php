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
        $updates = $this->db->table('updates')->orderBy('id', 'DESC')->get()->getResultArray();

        foreach ($updates as &$update) {
            $items = $this->db->table('update_items')
                ->where('update_id', $update['id'])
                ->orderBy("FIELD(type, 'new', 'improved', 'fixed', 'removed'), sort_order", '', false)
                ->get()->getResultArray();

            $typeLabels = ["new" => "New Features", "improved" => "Improvements", "fixed" => "Bug Fixes", "removed" => "Removed"];
            $grouped = [];
            foreach (["new", "improved", "fixed", "removed"] as $t) {
                $filtered = array_filter($items, fn($i) => $i["type"] === $t);
                if (!empty($filtered)) $grouped[$typeLabels[$t]] = array_values($filtered);
            }
            $update["categories"] = $grouped;
        }

        return view('updates/index', ['updates' => $updates]);
    }
}
