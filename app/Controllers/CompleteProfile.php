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

        return view('auth/complete_profile');
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
