<?php

namespace App\Controllers;

class Tour extends BaseController
{
    public function view(int $userId)
    {
        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (!$finance || !$finance['allow_tours']) {
            return redirect()->to('/leaderboard')->with('error', 'This resort is not open for tours.');
        }

        $owner = $db->table('users')->where('id', $userId)->get()->getRowArray();
        if (!$owner) return redirect()->to('/leaderboard')->with('error', 'Player not found.');

        $items = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $slopes = array_filter($items, fn($i) => $i['item_type'] === 'slope');
        $lifts = array_filter($items, fn($i) => $i['item_type'] === 'lift');
        $openSlopes = array_filter($slopes, fn($i) => $i['status'] === 'open');
        $openLifts = array_filter($lifts, fn($i) => $i['status'] === 'open');
        $staffCount = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->countAllResults();
        $buildingCount = $db->table('buildings')->where('user_id', $userId)->countAllResults();
        $parks = $db->table('terrain_parks')->where('user_id', $userId)->where('status', 'open')->countAllResults();

        $sectors = [];
        foreach ($items as $item) {
            $s = (int) $item['sector'];
            if (!isset($sectors[$s])) $sectors[$s] = ['lifts' => [], 'slopes' => []];
            $sectors[$s][$item['item_type'] === 'lift' ? 'lifts' : 'slopes'][] = $item;
        }
        ksort($sectors);

        $likeCount = $db->table('resort_likes')->where('resort_user_id', $userId)->countAllResults();
        $currentUser = auth()->user();
        $hasLiked = false;
        if ($currentUser) {
            $hasLiked = $db->table('resort_likes')->where('user_id', $currentUser->id)->where('resort_user_id', $userId)->countAllResults() > 0;
        }

        helper('rating');
        $rating = function_exists('resortRating') ? resortRating($userId) : ['stars' => 0, 'score' => 0, 'max' => 200];

        return view('tour/index', [
            'owner' => $owner,
            'finance' => $finance,
            'slopes' => $slopes,
            'lifts' => $lifts,
            'openSlopes' => count($openSlopes),
            'openLifts' => count($openLifts),
            'staffCount' => $staffCount,
            'buildingCount' => $buildingCount,
            'parks' => $parks,
            'sectors' => $sectors,
            'likeCount' => $likeCount,
            'hasLiked' => $hasLiked,
            'rating' => $rating,
        ]);
    }

    public function like(int $userId)
    {
        $currentUser = auth()->user();
        if (!$currentUser) return redirect()->to('/login');
        if ($currentUser->id == $userId) return redirect()->back()->with('error', 'You can\'t like your own resort.');

        $db = db_connect();
        $existing = $db->table('resort_likes')->where('user_id', $currentUser->id)->where('resort_user_id', $userId)->countAllResults();

        if ($existing) {
            $db->table('resort_likes')->where('user_id', $currentUser->id)->where('resort_user_id', $userId)->delete();
        } else {
            $db->table('resort_likes')->insert([
                'user_id' => $currentUser->id,
                'resort_user_id' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $db->table('player_finances')->where('user_id', $userId)->set('reputation', 'reputation + 1', false)->update();
        }

        return redirect()->to('/tour/' . $userId);
    }
}
