<?php

namespace App\Libraries;

/**
 * Finds players whose numbers the game itself can't explain (for /admin/suspicious).
 *
 * Signals, strongest first. Each gives points; a player's score is the sum.
 * - Unexplained cash (3): cash minus (starting cash + ledger income - ledger expenses) is over 100,000.
 *   Every legitimate cash change goes through the ledger totals, so a gap means cash arrived another way.
 * - Unexplained Génépis (2): balance is more than 20 above total earned minus total spent.
 * - Income spike (2): one game day's income is over 250,000 and over 4x the player's 14-day average.
 * - Rapid repeats (2): 5 or more identical income entries within one minute (double-submit exploits).
 * - Shared IP (1): 3 or more accounts signed in from the same IP in 14 days (alt accounts; households too).
 * Demo accounts and unfinished profiles are skipped. Admin edits in the last 30 days are shown as context,
 * because they explain gaps.
 */
class Suspicion
{
    private const START_CASH = "CASE pf.difficulty WHEN 'easy' THEN 1000000 WHEN 'hard' THEN 200000 ELSE 500000 END";

    /** @return array<int, array{id:int, username:string, score:int, reasons:list<array{signal:string, points:int, detail:string}>}> */
    public static function scan(): array
    {
        $db = db_connect();
        $out = [];
        $add = function (int $id, string $username, string $signal, int $points, string $detail) use (&$out) {
            $out[$id] ??= ['id' => $id, 'username' => $username, 'score' => 0, 'reasons' => []];
            $out[$id]['score'] += $points;
            $out[$id]['reasons'][] = ['signal' => $signal, 'points' => $points, 'detail' => $detail];
        };
        $real = "pf.profile_completed = 1 AND COALESCE(u.status, '') <> 'demo'";

        foreach ($db->query("
            SELECT u.id, u.username, CAST(pf.cash AS SIGNED) - (" . self::START_CASH . " + CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED)) AS gap
            FROM player_finances pf JOIN users u ON u.id = pf.user_id
            WHERE $real HAVING gap > 100000")->getResultArray() as $r) {
            $add((int) $r['id'], $r['username'], 'Unexplained cash', 3, currency((int) $r['gap']) . ' more than starting cash plus recorded income minus expenses');
        }

        foreach ($db->query("
            SELECT u.id, u.username, CAST(g.balance AS SIGNED) - (CAST(g.total_earned AS SIGNED) - CAST(g.total_spent AS SIGNED)) AS gap
            FROM genepis g JOIN users u ON u.id = g.user_id JOIN player_finances pf ON pf.user_id = u.id
            WHERE $real HAVING gap > 20")->getResultArray() as $r) {
            $add((int) $r['id'], $r['username'], 'Unexplained Génépis', 2, $r['gap'] . ' Génépis more than earned minus spent');
        }

        foreach ($db->query("
            SELECT u.id, u.username, s.mx, s.av FROM (
                SELECT user_id, MAX(d) mx, AVG(d) av, COUNT(*) days FROM (
                    SELECT user_id, game_day, SUM(amount) d FROM financial_transactions
                    WHERE type = 'income' AND category = 'Daily Income' AND created_at >= NOW() - INTERVAL 14 DAY
                    GROUP BY user_id, game_day) x
                GROUP BY user_id HAVING days >= 4 AND mx > 250000 AND mx > 4 * av) s
            JOIN users u ON u.id = s.user_id JOIN player_finances pf ON pf.user_id = u.id WHERE $real")->getResultArray() as $r) {
            $add((int) $r['id'], $r['username'], 'Income spike', 2, 'Best day ' . currency((int) $r['mx']) . ' against a 14-day average of ' . currency((int) $r['av']));
        }

        foreach ($db->query("
            SELECT u.id, u.username, r.description, r.n FROM (
                SELECT user_id, description, COUNT(*) n FROM financial_transactions
                WHERE type = 'income' AND category NOT IN ('Daily Income', 'Daily Expenses') AND created_at >= NOW() - INTERVAL 7 DAY
                GROUP BY user_id, description, UNIX_TIMESTAMP(created_at) DIV 60 HAVING n >= 5) r
            JOIN users u ON u.id = r.user_id JOIN player_finances pf ON pf.user_id = u.id WHERE $real")->getResultArray() as $r) {
            $add((int) $r['id'], $r['username'], 'Rapid repeats', 2, $r['n'] . ' x "' . mb_substr($r['description'], 0, 60) . '" within one minute');
        }

        foreach ($db->query("
            SELECT ip_address, GROUP_CONCAT(DISTINCT user_id) ids FROM auth_logins
            WHERE success = 1 AND user_id IS NOT NULL AND date >= NOW() - INTERVAL 14 DAY
            GROUP BY ip_address HAVING COUNT(DISTINCT user_id) >= 3")->getResultArray() as $g) {
            $ids = array_map('intval', explode(',', (string) $g['ids']));
            $users = $db->table('users u')->select('u.id, u.username')->join('player_finances pf', 'pf.user_id = u.id')
                ->whereIn('u.id', $ids)->where($real, null, false)->get()->getResultArray();
            if (count($users) < 3) continue;
            $names = implode(', ', array_column($users, 'username'));
            foreach ($users as $u) {
                $add((int) $u['id'], $u['username'], 'Shared IP', 1, count($users) . ' accounts on one IP in 14 days: ' . $names);
            }
        }

        // Context: recent admin edits explain gaps, and past reviews
        $ids = array_keys($out);
        if ($ids) {
            foreach ($db->table('admin_audit_log')->select('target_user_id, action, created_at')->whereIn('target_user_id', $ids)
                ->whereIn('action', ['set_cash', 'set_genepis', 'adjust_cash', 'adjust_genepis', 'flagged', 'cleared'])
                ->orderBy('created_at', 'DESC')->get()->getResultArray() as $a) {
                $id = (int) $a['target_user_id'];
                if (in_array($a['action'], ['flagged', 'cleared'], true)) {
                    $out[$id]['review'] ??= ['decision' => $a['action'], 'at' => (string) $a['created_at']];
                } elseif (strtotime((string) $a['created_at']) >= strtotime('-30 days')) {
                    $out[$id]['adminEdits'] = ($out[$id]['adminEdits'] ?? 0) + 1;
                }
            }
        }

        usort($out, fn($a, $b) => $b['score'] <=> $a['score'] ?: $a['id'] <=> $b['id']);
        return array_values($out);
    }
}
