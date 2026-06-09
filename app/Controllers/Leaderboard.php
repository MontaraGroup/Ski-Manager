<?php

namespace App\Controllers;

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
                pf.cash,
                pf.total_income,
                pf.total_expenses,
                pf.difficulty,
                (SELECT COUNT(*) FROM staff s WHERE s.user_id = u.id AND s.status != 'fired') as staff_count,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type IN ('slope','downhill','crosscountry','snowpark','luge')) as slope_count,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'lift') as lift_count,
                (SELECT COUNT(*) FROM buildings b WHERE b.user_id = u.id) as building_count,
                (CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED)) as net_profit,
                (pf.cash + pf.total_income) as score
            FROM users u
            LEFT JOIN player_finances pf ON pf.user_id = u.id
            WHERE u.id NOT IN (1, 3)
            AND pf.cash IS NOT NULL
            ORDER BY score DESC
        ")->getResultArray();

        $currentUserId = auth()->loggedIn() ? auth()->id() : 0;

        return view('leaderboard/index', [
            'players' => $players,
            'currentUserId' => $currentUserId,
        ]);
    }
}
