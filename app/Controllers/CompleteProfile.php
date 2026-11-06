<?php

namespace App\Controllers;

class CompleteProfile extends BaseController
{
    public function index()
    {
        $userId = auth()->id();
        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();

        if ($finance && ($finance['profile_completed'] ?? 0)) {
            return redirect()->to('/dashboard');
        }

        $page = [
            'actionUrl'    => '/complete-profile',
            'csrfToken'    => csrf_hash(),
            'csrfField'    => csrf_token(),
            'difficulties' => [
                [
                    'key'          => 'easy',
                    'name'         => 'Easy',
                    'badge'        => '●',
                    'badgeColor'   => 'text-[#23a55a]',
                    'startingCash' => 1000000,
                    'desc'         => 'High revenue, relaxed upkeep, low wear.',
                ],
                [
                    'key'          => 'standard',
                    'name'         => 'Standard',
                    'badge'        => '■',
                    'badgeColor'   => 'text-[#2f6fd6]',
                    'startingCash' => 500000,
                    'desc'         => 'Standard economy and mountain wear.',
                ],
                [
                    'key'          => 'hard',
                    'name'         => 'Hard',
                    'badge'        => '◆',
                    'badgeColor'   => 'text-foreground',
                    'startingCash' => 200000,
                    'desc'         => 'Tight margins, unforgiving weather.',
                ],
            ],
            'resorts' => [
                ['key' => 'ParkCity', 'name' => 'Park City', 'location' => 'Park City, UT', 'icon' => 'fa-mountain-sun', 'enabled' => true],
                ['key' => 'Vail', 'name' => 'Vail', 'location' => 'Vail, CO', 'icon' => 'fa-mountain', 'enabled' => false],
                ['key' => 'AspenSnowmass', 'name' => 'Aspen', 'location' => 'Aspen, CO', 'icon' => 'fa-tree', 'enabled' => false],
                ['key' => 'DeerValley', 'name' => 'Deer Valley', 'location' => 'Deer Valley, UT', 'icon' => 'fa-person-skiing', 'enabled' => false],
                ['key' => 'Killington', 'name' => 'Killington', 'location' => 'Killington, VT', 'icon' => 'fa-snowflake', 'enabled' => false],
                ['key' => 'BigSkyCombo', 'name' => 'Big Sky', 'location' => 'Big Sky, MT', 'icon' => 'fa-cloud-sun', 'enabled' => false],
                ['key' => 'PalisadesTahoe', 'name' => 'Palisades', 'location' => 'Palisades, CA', 'icon' => 'fa-water', 'enabled' => false],
            ],
            'error'  => session('error'),
            'errors' => session('errors'),
        ];

        return view('auth/complete_profile', ['page' => $page]);
    }

    public function save()
    {
        $userId = auth()->id();
        $db = db_connect();

        $difficulty = $this->request->getPost('difficulty');
        $resortMap = $this->request->getPost('resort_map');
        $terms = $this->request->getPost('terms');

        if (!$terms) {
            return redirect()->back()->with('error', 'You must accept the Terms of Service.');
        }

        if (!in_array($difficulty, ['easy', 'standard', 'hard'])) $difficulty = 'standard';
        if (!in_array($resortMap, ['ParkCity', 'Vail', 'AspenSnowmass', 'DeerValley', 'Killington', 'BigSkyCombo', 'PalisadesTahoe'])) $resortMap = 'ParkCity';

        $cash = match($difficulty) { 'easy' => 1000000, 'hard' => 200000, default => 500000 };

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if ($finance) {
            $db->table('player_finances')->where('user_id', $userId)->update([
                'difficulty' => $difficulty,
                'resort_map' => $resortMap,
                'cash' => $cash,
                'profile_completed' => 1,
            ]);
        }

        return redirect()->to('/dashboard');
    }
}
