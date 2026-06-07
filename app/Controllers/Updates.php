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
                ->orderBy('FIELD(type, "new", "improved", "fixed", "removed")', '', false)->orderBy('sort_order', 'ASC')
                ->get()->getResultArray();

            $grouped = [];
            foreach ($items as $item) {
                $grouped[$item['category']][] = $item;
            }
            $update['categories'] = $grouped;
        }

        return view('updates/index', ['updates' => $updates]);
    }
}
