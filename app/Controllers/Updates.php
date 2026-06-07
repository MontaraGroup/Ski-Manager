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

            $grouped = [];
            foreach ($items as $item) {
                $grouped[$item['category']][] = $item;
            }
            foreach ($grouped as &$catItems) { usort($catItems, function($a, $b) { $order = ["new" => 0, "improved" => 1, "fixed" => 2, "removed" => 3]; return ($order[$a["type"]] ?? 9) - ($order[$b["type"]] ?? 9); }); } unset($catItems);
            $update['categories'] = $grouped;
        }

        return view('updates/index', ['updates' => $updates]);
    }
}
