<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * GET api/v1/app/play-games: what the Android app mirrors to Google Play Games. The leaderboard
 * number follows the site's leaderboard (season profit), floored
 * at 0 because Play Games scores can't be negative; null while the resort has nothing built, as the
 * site doesn't rank those. Achievements: the completed ones, by key and name.
 */
class PlayGames extends BaseController
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        $userId = (int) auth()->id();
        // Same refresh the achievements page does, so a goal reached today counts now and not after the nightly tick
        \App\Libraries\Achievements::sync($userId, false);
        $db = db_connect();
        $pf = $db->table('player_finances')->select('cash, total_income, total_expenses')->where('user_id', $userId)->get()->getRowArray();
        $built = $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope', 'lift'])->countAllResults()
            + $db->table('buildings')->where('user_id', $userId)->countAllResults();

        $score = null;
        if ($pf && $built > 0) {
            $score = max(0, (int) $pf['total_income'] - (int) $pf['total_expenses']);
        }

        $done = $db->table('achievements')->select('achievement_key, name')->where('user_id', $userId)->where('completed', 1)->get()->getResultArray();
        return $this->ok([
            'leaderboardScore' => $score,
            'leaderboardMetric' => 'Season profit',
            'achievements' => array_map(fn($a) => ['key' => (string) $a['achievement_key'], 'name' => (string) $a['name']], $done),
        ]);
    }
}
