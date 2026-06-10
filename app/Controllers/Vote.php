<?php

namespace App\Controllers;

class Vote extends BaseController
{
    private function getResortOptions(): array
    {
        return [
            'DeerValley' => ['name' => 'Deer Valley', 'location' => 'Park City, Utah', 'desc' => 'Known for luxury skiing, groomed runs, and no snowboarders allowed. Upscale dining and pristine conditions.', 'icon' => 'fa-solid fa-gem', 'color' => 'text-primary'],
            'AspenSnowmass' => ['name' => 'Aspen Snowmass', 'location' => 'Aspen, Colorado', 'desc' => 'Four mountains in one. Mix of celebrity culture, expert terrain, and world-class snowfall.', 'icon' => 'fa-solid fa-mountain-sun', 'color' => 'text-info'],
            'BigSkyCombo' => ['name' => 'Big Sky', 'location' => 'Big Sky, Montana', 'desc' => 'The biggest skiing in America. Massive vertical drop, wide open bowls, and uncrowded slopes.', 'icon' => 'fa-solid fa-mountain', 'color' => 'text-success'],
            'Vail' => ['name' => 'Vail', 'location' => 'Vail, Colorado', 'desc' => 'Legendary back bowls, massive front-side grooming, and a vibrant village. The gold standard of American skiing.', 'icon' => 'fa-solid fa-crown', 'color' => 'text-warning'],
            'PalisadesTahoe' => ['name' => 'Palisades Tahoe', 'location' => 'Olympic Valley, California', 'desc' => 'Host of the 1960 Winter Olympics. Steep chutes, lake views, and California sunshine.', 'icon' => 'fa-solid fa-sun', 'color' => 'text-warning'],
            'Killington' => ['name' => 'Killington', 'location' => 'Killington, Vermont', 'desc' => 'The Beast of the East. Longest season on the East Coast, aggressive snowmaking, and rowdy apres-ski.', 'icon' => 'fa-solid fa-snowflake', 'color' => 'text-error'],
        ];
    }

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $options = $this->getResortOptions();

        $userVote = $db->table('resort_votes')->where('user_id', $userId)->get()->getRowArray();
        $results = $db->query("SELECT resort_key, COUNT(*) as votes FROM resort_votes GROUP BY resort_key ORDER BY votes DESC")->getResultArray();

        $voteCounts = [];
        $totalVotes = 0;
        foreach ($results as $r) {
            $voteCounts[$r['resort_key']] = (int) $r['votes'];
            $totalVotes += (int) $r['votes'];
        }

        return view('vote/index', [
            'options' => $options,
            'userVote' => $userVote,
            'voteCounts' => $voteCounts,
            'totalVotes' => $totalVotes,
        ]);
    }

    public function cast()
    {
        $userId = auth()->id();
        $db = db_connect();
        $resort = $this->request->getPost('resort');
        $options = $this->getResortOptions();

        if (!isset($options[$resort])) {
            return redirect()->back()->with('error', 'Invalid resort.');
        }

        $existing = $db->table('resort_votes')->where('user_id', $userId)->get()->getRowArray();
        if ($existing) {
            $db->table('resort_votes')->where('user_id', $userId)->update(['resort_key' => $resort, 'created_at' => date('Y-m-d H:i:s')]);
        } else {
            $db->table('resort_votes')->insert(['user_id' => $userId, 'resort_key' => $resort]);
        }

        log_activity($userId, 'Vote', 'Voted for ' . $options[$resort]['name'] . ' as Season 4 resort', 'fa-solid fa-check-to-slot');
        return redirect()->to('/vote')->with('success', 'Vote cast for ' . $options[$resort]['name'] . '!');
    }
}
