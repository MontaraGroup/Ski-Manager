<?php

namespace App\Controllers;

/**
 * /leaderboard: this season's profit, one board per difficulty (economy v2: resorts restart each
 * season). Only resorts that exist are ranked; ties share a rank. Past seasons come from
 * season_leaderboard_archives.
 */
class Leaderboard extends BaseController
{
    public const SHOWN = 50;

    public function index(): string
    {
        $db = db_connect();
        $v2 = featureEnabled('economy_v2');
        $difficulties = ['easy', 'standard', 'hard'];
        $userId = auth()->loggedIn() ? (int) auth()->id() : 0;

        $difficulty = 'all';
        if ($v2) {
            $difficulty = (string) $this->request->getGet('difficulty');
            if ($difficulty !== 'all' && !in_array($difficulty, $difficulties, true)) {
                $mine = $userId ? $db->table('player_finances')->select('difficulty')->where('user_id', $userId)->get()->getRow('difficulty') : null;
                $difficulty = in_array($mine, $difficulties, true) ? $mine : 'standard';
            }
        }

        $where = "u.id NOT IN (1, 3) AND u.active = 1 AND (u.status IS NULL OR u.status != 'demo')";
        $binds = [];
        if ($v2 && $difficulty !== 'all') { $where .= ' AND pf.difficulty = ?'; $binds[] = $difficulty; }
        $rows = $db->query("
            SELECT u.id, u.username, u.created_at, pf.cash, pf.difficulty, pf.daily_visitors, pf.resort_name,
                   CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED) AS net_profit,
                   pf.cash + pf.total_income AS score,
                   COALESCE(it.slopes, 0) AS slope_count, COALESCE(it.lifts, 0) AS lift_count,
                   COALESCE(b.n, 0) AS building_count, COALESCE(st.n, 0) AS staff_count
            FROM users u
            JOIN player_finances pf ON pf.user_id = u.id
            LEFT JOIN (SELECT user_id, SUM(item_type = 'slope') slopes, SUM(item_type = 'lift') lifts FROM player_items GROUP BY user_id) it ON it.user_id = u.id
            LEFT JOIN (SELECT user_id, COUNT(*) n FROM buildings GROUP BY user_id) b ON b.user_id = u.id
            LEFT JOIN (SELECT user_id, COUNT(*) n FROM staff WHERE status != 'fired' GROUP BY user_id) st ON st.user_id = u.id
            WHERE {$where}
            ORDER BY " . ($v2 ? 'net_profit DESC, pf.cash DESC' : 'score DESC') . ', u.id ASC', $binds)->getResultArray();

        // Only resorts that exist this season are ranked; a fresh account with nothing built isn't
        $ranked = []; $unranked = 0; $rank = 0; $prev = null;
        foreach ($rows as $i => $r) {
            if ((int) $r['slope_count'] + (int) $r['lift_count'] + (int) $r['building_count'] === 0) { $unranked++; continue; }
            $value = $v2 ? (int) $r['net_profit'] : (int) $r['score'];
            if ($value !== $prev) { $rank = count($ranked) + 1; $prev = $value; }
            $r['rank'] = $rank;
            $ranked[] = $r;
        }
        $me = null;
        foreach ($ranked as $r) {
            if ((int) $r['id'] === $userId) { $me = $r; break; }
        }

        $players = array_slice($ranked, 0, self::SHOWN);
        $past = $this->pastSeasons();
        $metric = fn(array $p) => $v2 ? (int) $p['net_profit'] : (int) $p['score'];
        $signed = fn(int $v) => ($v > 0 ? '+' : '') . currency($v);
        $row = fn(array $p) => [
            'id' => (int) $p['id'], 'rank' => (int) $p['rank'], 'username' => (string) $p['username'], 'resortName' => (string) ($p['resort_name'] ?? ''),
            'profit' => $signed((int) $p['net_profit']), 'profitPositive' => (int) $p['net_profit'] >= 0,
            'skiers' => number_format((int) $p['daily_visitors']), 'cash' => currency((int) $p['cash']),
            'score' => $v2 ? null : number_format((int) $p['score']),
            'slopes' => (int) $p['slope_count'], 'lifts' => (int) $p['lift_count'], 'buildings' => (int) $p['building_count'],
            'isMe' => (int) $p['id'] === $userId,
        ];

        return view('leaderboard/index', ['page' => [
            'v2' => $v2,
            'subtitle' => getSeasonName() . ' · day ' . getSeasonDay() . ' · ' . ($v2 ? 'ranked by profit this season' : 'ranked by score'),
            'difficulty' => $difficulty,
            'me' => $me ? $row($me) + [
                'behind' => ($me['rank'] > 1 && isset($players[0])) ? currency(max(0, $metric($players[0]) - $metric($me))) : null,
            ] : null,
            'meUnranked' => !$me && $userId && (bool) array_filter($rows, fn($r) => (int) $r['id'] === $userId),
            'meOutsideTop' => $me && $me['rank'] > self::SHOWN,
            'players' => array_map($row, $players),
            'shown' => min(self::SHOWN, count($ranked)),
            'rankedCount' => count($ranked),
            'unranked' => $unranked,
            'pastSeasons' => array_values(array_map(fn($num, $season) => [
                'number' => $num, 'name' => $season['name'] ?: 'Season ' . $num,
                'rows' => array_map(fn($r) => ['rank' => (int) $r['rank_position'], 'username' => (string) $r['username'], 'resortName' => (string) ($r['resort_name'] ?? ''),
                    'cash' => currency((int) $r['cash']), 'slopes' => (int) $r['total_slopes'], 'lifts' => (int) $r['total_lifts']], $season['rows']),
            ], array_keys($past), $past)),
        ]]);
    }

    /** Top 10 of each finished season, demo accounts left out */
    private function pastSeasons(): array
    {
        $db = db_connect();
        if (!$db->tableExists('season_leaderboard_archives')) return [];
        $rows = $db->table('season_leaderboard_archives a')
            ->select('a.season_number, a.season_name, a.rank_position, a.username, a.resort_name, a.cash, a.total_slopes, a.total_lifts')
            ->join('users u', 'u.id = a.user_id', 'left')
            ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'demo')->groupEnd()
            ->where('a.rank_position <=', 10)
            ->orderBy('a.season_number', 'DESC')->orderBy('a.rank_position', 'ASC')
            ->get()->getResultArray();
        $seasons = [];
        foreach ($rows as $r) {
            $seasons[(int) $r['season_number']]['name'] = $r['season_name'];
            $seasons[(int) $r['season_number']]['rows'][] = $r;
        }
        return $seasons;
    }
}
