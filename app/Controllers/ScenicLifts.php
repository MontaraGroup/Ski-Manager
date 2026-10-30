<?php

namespace App\Controllers;

use App\Libraries\Scenic;
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

        $seasonDay = getSeasonDay();
        $isSummer = $seasonDay > getWinterDays();

        $totalDailyRevenue = 0;
        $seasonRevenue = 0;
        foreach ($scenicLifts as &$sl) {
            $price = Scenic::clampPrice((int) ($sl['scenic']['ticket_price'] ?? Scenic::REF_PRICE));
            $sl['scenic'] += ['price' => $price, 'riders' => Scenic::riders($price, $isSummer),
                'today' => Scenic::dailyRevenue($sl['scenic'], $isSummer), 'summer' => Scenic::dailyRevenue($sl['scenic'], true)];
            $totalDailyRevenue += $sl['scenic']['summer'];
            if ($sl['status'] === 'open') $seasonRevenue += $sl['scenic']['today'];
        }
        unset($sl);

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
        $ticketPrice = Scenic::clampPrice((int) ($this->request->getPost('ticket_price') ?: Scenic::REF_PRICE));

        $itemModel = new PlayerItemModel();
        $lift = $itemModel->where('id', $itemId)->where('user_id', $userId)->where('item_type', 'lift')->first();
        if (!$lift) return redirect()->back()->with('error', 'Lift not found.');

        $db = db_connect();
        $exists = $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->countAllResults();
        if ($exists) return redirect()->back()->with('error', 'Already designated.');

        $db->table('scenic_lifts')->insert([
            'user_id' => $userId,
            'item_id' => $itemId,
            'ticket_price' => $ticketPrice,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity($userId, 'Scenic', $lift['name'] . ' is now a scenic lift', 'fa-solid fa-camera');
        return redirect()->to('/scenic-lifts')->with('success', $lift['name'] . ' designated for scenic rides!');
    }

    public function updatePrice(int $itemId)
    {
        $userId = auth()->id();
        $db = db_connect();
        $price = Scenic::clampPrice((int) $this->request->getPost('price'));

        if ($db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->countAllResults() === 0) {
            return redirect()->to('/scenic-lifts')->with('error', 'Scenic lift not found.');
        }
        // Only the ticket price changes; upgrades bought for the lift are kept
        $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->update(['ticket_price' => $price]);
        return redirect()->to('/scenic-lifts')->with('success', 'Ticket price set to ' . currency($price) . ' per ride: about ' . Scenic::riders($price, true) . ' riders a summer day.');
    }

    public function remove(int $itemId)
    {
        $userId = auth()->id();
        $db = db_connect();
        $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $itemId)->delete();

        log_activity($userId, 'Scenic', 'Removed scenic designation', 'fa-solid fa-xmark');
        return redirect()->to('/scenic-lifts')->with('success', 'Scenic designation removed.');
    }

    public function upgrade()
    {
        $userId = auth()->id();
        $upgradeKey = $this->request->getPost('upgrade');
        $itemId = (int) $this->request->getPost('item_id');

        if (!isset(self::SCENIC_UPGRADES[$upgradeKey])) {
            return redirect()->to('/scenic-lifts')->with('error', 'Invalid upgrade type.');
        }

        $up = self::SCENIC_UPGRADES[$upgradeKey];
        $db = db_connect();

        $query = $db->table('scenic_lifts')->where('user_id', $userId);
        if ($itemId > 0) {
            $query->where('item_id', $itemId);
        }
        $scenicLift = $query->get()->getRowArray();

        if (!$scenicLift) {
            return redirect()->to('/scenic-lifts')->with('error', 'You need at least one scenic lift to install upgrades.');
        }

        $installed = json_decode((string) ($scenicLift['upgrades'] ?? ''), true);
        $installed = is_array($installed) ? $installed : [];
        if (in_array($upgradeKey, $installed, true)) {
            return redirect()->to('/scenic-lifts')->with('error', $up['name'] . ' is already installed on that lift.');
        }

        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $up['cost'])
            ->set('cash', 'cash - ' . (int) $up['cost'], false)->set('total_expenses', 'total_expenses + ' . (int) $up['cost'], false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/scenic-lifts')->with('error', 'Not enough cash to purchase ' . $up['name'] . ' (' . currency($up['cost']) . ' required).');
        }
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Scenic Upgrades', 'description' => 'Installed ' . $up['name'],
            'amount' => $up['cost'], 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $installed[] = $upgradeKey;
        $db->table('scenic_lifts')->where('id', $scenicLift['id'])->update(['upgrades' => json_encode(array_values(array_unique($installed)))]);
        $db->transComplete();

        $liftItem = $db->table('player_items')->where('id', $scenicLift['item_id'])->get()->getRowArray();
        $liftName = $liftItem['name'] ?? 'Scenic Lift';

        log_activity($userId, 'Scenic', 'Installed ' . $up['name'] . ' on ' . $liftName . ' (+' . currency($up['revenue_boost']) . '/day)', 'fa-solid fa-arrow-up');

        return redirect()->to('/scenic-lifts')->with('success', $up['name'] . ' installed on ' . $liftName . '! Daily revenue increased by ' . currency($up['revenue_boost']) . '.');
    }
}
