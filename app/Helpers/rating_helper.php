<?php

if (!function_exists('resortRating')) {
    function resortRating(int $userId): array
    {
        $db = db_connect();
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->countAllResults(false);
        $lifts = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults(false);
        $staff = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->countAllResults(false);
        $buildings = $db->table('buildings')->where('user_id', $userId)->countAllResults(false);
        $parking = $db->table('parking')->where('user_id', $userId)->where('status', 'open')->countAllResults(false);
        $parks = $db->table('terrain_parks')->where('user_id', $userId)->where('status', 'open')->countAllResults(false);

        $items = $db->table('player_items')->where('user_id', $userId)->where('status', 'open')->get()->getResultArray();
        $avgCondition = count($items) > 0 ? array_sum(array_column($items, 'condition_pct')) / count($items) : 0;

        $staffAll = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $avgMorale = count($staffAll) > 0 ? array_sum(array_column($staffAll, 'morale')) / count($staffAll) : 0;

        $score = 0;
        $score += min($slopes * 8, 40);
        $score += min($lifts * 10, 40);
        $score += min($staff * 3, 30);
        $score += min($buildings * 5, 30);
        $score += min($parking * 5, 15);
        $score += min($parks * 8, 25);
        $score += $avgCondition * 0.1;
        $score += $avgMorale * 0.1;

        $stars = match(true) {
            $score >= 160 => 5,
            $score >= 120 => 4,
            $score >= 80 => 3,
            $score >= 40 => 2,
            $score >= 10 => 1,
            default => 0,
        };

        return ['stars' => $stars, 'score' => round($score), 'max' => 200];
    }
}
