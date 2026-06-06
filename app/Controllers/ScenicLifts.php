<?php

namespace App\Controllers;

use App\Models\PlayerItemModel;

class ScenicLifts extends BaseController
{
    public const SCENIC_UPGRADES = [
        'photo_spot' => ['name' => 'Photo Spot', 'icon' => 'fa-solid fa-camera-retro', 'cost' => 5000, 'revenue_boost' => 500, 'desc' => 'Designated photo point at summit with panoramic views'],
        'audio_guide' => ['name' => 'Audio Guide', 'icon' => 'fa-solid fa-headphones', 'cost' => 8000, 'revenue_boost' => 800, 'desc' => 'Multilingual audio commentary about local history and nature'],
        'glass_cabin' => ['name' => 'Glass-Bottom Cabin', 'icon' => 'fa-solid fa-window-maximize', 'cost' => 25000, 'revenue_boost' => 2000, 'desc' => 'Transparent floor cabins for a thrilling ride experience'],
        'sunset_ride' => ['name' => 'Sunset Experience', 'icon' => 'fa-solid fa-sun', 'cost' => 12000, 'revenue_boost' => 1200, 'desc' => 'Evening rides timed with sunset — premium ticket pricing'],
    ];

    public function index(): string
    {
        $userId = auth()->id();
        $itemModel = new PlayerItemModel();
        $db = db_connect();

        $lifts = $itemModel->where('user_id', $userId)->where('item_type', 'lift')->findAll();
        $scenicData = $db->table('scenic_lifts')->where('user_id', $userId)->get()->getResultArray();
        $scenicMap = [];
        foreach ($scenicData as $sd) {
            $scenicMap[$sd['item_id']] = $sd;
        }

        $scenicLifts = [];
        $availableLifts = [];
        foreach ($lifts as $lift) {
            if (isset($scenicMap[$lift['id']])) {
                $lift['scenic'] = $scenicMap[$lift['id']];
                $scenicLifts[] = $lift;
            } else {
                $availableLifts[] = $lift;
            }
        }

        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
        $seasonDay = getSeasonDay();
        $isSummer = $seasonDay > getWinterDays();

        $baseRevenue = 1500;
        $totalDailyRevenue = 0;
        foreach ($scenicLifts as $sl) {
            $rev = (int) ($sl['scenic']['revenue_per_day'] ?? $baseRevenue);
            $totalDailyRevenue += $rev;
        }

        $seasonRevenue = $isSummer ? $totalDailyRevenue : round($totalDailyRevenue * 0.3);

        return view('scenic/index', [
            'lifts' => $lifts,
            'scenicLifts' => $scenicLifts,
            'availableLifts' => $availableLifts,
            'totalDailyRevenue' => $totalDailyRevenue,
            'seasonRevenue' => $seasonRevenue,
            'isSummer' => $isSummer,
            'seasonDay' => $seasonDay,
            'upgrades' => self::SCENIC_UPGRADES,
        ]);
    }

    public function designate()
    {
        $userId = auth()->id();
        $itemId = (int) $this->request->getPost('item_id');
        $ticketPrice = (int) ($this->request->getPost('ticket_price') ?? 1500);

        $itemModel = new PlayerItemModel();
        $lift = $itemModel->where('id', $itemId)->where('user_id', $userId)->where('item_type', 'lift')->first();
        if (!$lift) return redirect()->back()->with('error', 'Lift not found.');

        $db = db_connect();
        $exists = $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->countAllResults();
        if ($exists) return redirect()->back()->with('error', 'Already designated.');

        $db->table('scenic_lifts')->insert([
            'user_id' => $userId,
            'item_id' => $itemId,
            'revenue_per_day' => max(500, min(5000, $ticketPrice)),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity($userId, 'Scenic', $lift['name'] . ' is now a scenic lift', 'fa-solid fa-camera');
        return redirect()->to('/scenic-lifts')->with('success', $lift['name'] . ' designated for scenic rides!');
    }

    public function updatePrice(int $itemId)
    {
        $userId = auth()->id();
        $db = db_connect();
        $price = (int) $this->request->getPost('price');
        $price = max(500, min(5000, $price));

        $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->update(['revenue_per_day' => $price]);
        return redirect()->to('/scenic-lifts')->with('success', 'Ticket price updated to ' . currency($price) . '/day.');
    }

    public function remove(int $itemId)
    {
        $userId = auth()->id();
        $db = db_connect();
        $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->delete();

        log_activity($userId, 'Scenic', 'Removed scenic designation', 'fa-solid fa-xmark');
        return redirect()->to('/scenic-lifts')->with('success', 'Scenic designation removed.');
    }
}
