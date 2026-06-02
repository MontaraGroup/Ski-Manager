<?php

namespace App\Controllers;

use App\Models\PlayerItemModel;

class ScenicLifts extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $itemModel = new PlayerItemModel();
        $lifts = $itemModel->where('user_id', $userId)->where('item_type', 'lift')->findAll();

        $db = db_connect();
        $scenicDesignations = $db->table('scenic_lifts')->where('user_id', $userId)->get()->getResultArray();
        $scenicIds = array_column($scenicDesignations, 'item_id');

        $scenicLifts = array_filter($lifts, fn($l) => in_array($l['id'], $scenicIds));
        $availableLifts = array_filter($lifts, fn($l) => !in_array($l['id'], $scenicIds));

        $totalRevenue = count($scenicLifts) * 1500;

        return view('scenic/index', [
            'lifts' => $lifts,
            'scenicLifts' => $scenicLifts,
            'availableLifts' => $availableLifts,
            'totalRevenue' => $totalRevenue,
        ]);
    }

    public function designate()
    {
        $userId = auth()->id();
        $itemId = (int) $this->request->getPost('item_id');

        $itemModel = new PlayerItemModel();
        $lift = $itemModel->where('id', $itemId)->where('user_id', $userId)->where('item_type', 'lift')->first();
        if (!$lift) return redirect()->back()->with('error', 'Lift not found.');

        $db = db_connect();
        $exists = $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->countAllResults();
        if ($exists) return redirect()->back()->with('error', 'Already designated as scenic.');

        $db->table('scenic_lifts')->insert([
            'user_id' => $userId,
            'item_id' => $itemId,
            'revenue_per_day' => 1500,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity($userId, 'Scenic', $lift['name'] . ' designated for scenic operations', 'fa-solid fa-camera');
        return redirect()->to('/scenic-lifts')->with('success', $lift['name'] . ' is now a scenic lift!');
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
