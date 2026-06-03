<?php

namespace App\Controllers;

use CodeIgniter\Shield\Models\UserModel;

class Leaderboard extends BaseController
{
    public function index(): string
    {
        $db = db_connect();

        $players = $db->query("
            SELECT 
                u.id,
                u.username,
                u.created_at,
                (SELECT COUNT(*) FROM staff s WHERE s.user_id = u.id AND s.status != 'fired') as staff_count
            FROM users u
            WHERE u.id != 1
            ORDER BY u.created_at ASC
        ")->getResultArray();

        return view('leaderboard/index', ['players' => $players]);
    }
}
