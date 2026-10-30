<?php

namespace App\Controllers;

use App\Libraries\Ledger;
use App\Models\PlayerItemModel;
use App\Models\StaffModel;
use App\Models\BuildingModel;

class Resort extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $finance = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
        $resort = session()->get('resort') ?? [
            'name' => 'My Resort', 'location' => '', 'description' => '',
            'altitude' => 'medium', 'aspect' => 'north', 'is_open' => (bool) ($finance['resort_open'] ?? 1),
        ];

        $resort["is_open"] = (bool) ($finance["resort_open"] ?? 1);
        $itemModel = new PlayerItemModel();
        $staffModel = new StaffModel();
        $buildingModel = new BuildingModel();

        $items = $itemModel->where('user_id', $userId)->findAll();
        $lifts = array_filter($items, fn($i) => $i['item_type'] === 'lift');
        $slopes = array_filter($items, fn($i) => $i['item_type'] === 'slope');
        $openLifts = array_filter($lifts, fn($i) => $i['status'] === 'open');
        $openSlopes = array_filter($slopes, fn($i) => $i['status'] === 'open');
        $staffCount = $staffModel->where('user_id', $userId)->where('status !=', 'fired')->countAllResults();
        $buildingCount = $buildingModel->where('user_id', $userId)->countAllResults();

        $db = db_connect();
        $sectorNames = [];
        $sectorRows = $db->table('resort_sectors')->get()->getResultArray();
        foreach ($sectorRows as $sr) { $sectorNames[(int)$sr['id']] = $sr['name']; }

        $sectors = [];
        foreach ($items as $item) {
            $s = (int) $item['sector'];
            if (!isset($sectors[$s])) $sectors[$s] = ['lifts' => [], 'slopes' => [], 'name' => $sectorNames[$s] ?? 'Unassigned'];
            $sectors[$s][$item['item_type'] === 'lift' ? 'lifts' : 'slopes'][] = $item;
        }
        ksort($sectors);

        return view('resort/index', [
            'resort' => $resort,
            'lifts' => $lifts,
            'slopes' => $slopes,
            'openLifts' => count($openLifts),
            'openSlopes' => count($openSlopes),
            'staffCount' => $staffCount,
            'buildingCount' => $buildingCount,
            'sectors' => $sectors,
        ]);
    }

    public function edit()
    {
        if ($this->request->getMethod() === 'POST') {
            $resort = [
                'name' => strip_tags(trim($this->request->getPost('name'))),
                'location' => strip_tags(trim($this->request->getPost('location'))),
                'description' => strip_tags(trim($this->request->getPost('description'))),
                'altitude' => $this->request->getPost('altitude'),
                'aspect' => $this->request->getPost('aspect'),
                'is_open' => $this->request->getPost('is_open') ? true : false,
            ];
            session()->set('resort', $resort);
            log_activity(auth()->id(), 'Resort', 'Updated resort settings', 'fa-solid fa-pen-to-square');
            return redirect()->to('/resort')->with('success', 'Resort updated successfully.');
        }

        $finance = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
        $resort = session()->get('resort') ?? [
            'name' => 'My Resort', 'location' => '', 'description' => '',
            'altitude' => 'medium', 'aspect' => 'north', 'is_open' => (bool) ($finance['resort_open'] ?? 1),
        ];

        return view('resort/edit', ['resort' => $resort]);
    }

    /** Repairing a lift from 0% costs this share of what it cost to build, less as condition is higher */
    public const LIFT_REPAIR_SHARE = 0.3;

    public static function liftRepairCost(array $lift): int
    {
        return (int) round(ResortMap::buildCost($lift) * self::LIFT_REPAIR_SHARE * (100 - (int) $lift['condition_pct']) / 100);
    }

    /** Lifts wear every day and break at 0%; this restores one to 100% (a broken lift reopens) */
    public function repairLift(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $lift = $db->table('player_items')->where('id', $id)->where('user_id', $userId)->where('item_type', 'lift')->get()->getRowArray();
        if (!$lift) return redirect()->to('/resort')->with('error', 'Lift not found.');
        if ((int) $lift['condition_pct'] >= 100) return redirect()->to('/resort')->with('error', $lift['name'] . ' is already in perfect condition.');

        $cost = self::liftRepairCost($lift);
        $db->transStart();
        if (!Ledger::charge($userId, $cost, 'Lifts', 'Repaired ' . $lift['name'])) {
            $db->transRollback();
            return redirect()->to('/resort')->with('error', 'Not enough cash: repairing ' . $lift['name'] . ' costs ' . currency($cost) . '.');
        }
        $db->table('player_items')->where('id', $id)->update([
            'condition_pct' => 100, 'status' => $lift['status'] === 'broken' ? 'open' : $lift['status'], 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Lift', 'Repaired ' . $lift['name'] . ' for ' . currency($cost), 'fa-solid fa-wrench');
        return redirect()->to('/resort')->with('success', $lift['name'] . ' repaired for ' . currency($cost) . ($lift['status'] === 'broken' ? ' and reopened.' : '.'));
    }

    public function toggleItem(int $id)
    {
        $userId = auth()->id();
        $itemModel = new PlayerItemModel();
        $item = $itemModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$item) return redirect()->back()->with('error', 'Not found.');
        // A broken lift needs a repair, a broken slope needs grooming: switching them on did nothing useful
        if ($item['status'] === 'broken') {
            return redirect()->to('/resort')->with('error', $item['name'] . ' is broken: ' . ($item['item_type'] === 'lift' ? 'repair it first.' : 'groom it to bring it back.'));
        }
        if (!in_array($item['status'], ['open', 'closed'], true)) return redirect()->to('/resort')->with('error', $item['name'] . ' isn\'t ready yet.');

        $new = $item['status'] === 'open' ? 'closed' : 'open';
        $itemModel->update($id, ['status' => $new]);
        log_activity($userId, $item['item_type'] === 'lift' ? 'Lift' : 'Slope', $item['name'] . ' ' . $new, 'fa-solid fa-power-off');
        return redirect()->to('/resort')->with('success', $item['name'] . ' ' . $new . '.');
    }

    public function toggleResort()
    {
        $userId = auth()->id();
        $db = db_connect();
        $finance = $db->table("player_finances")->where("user_id", $userId)->get()->getRowArray();
        $current = (int) ($finance["resort_open"] ?? 1);
        $new = $current ? 0 : 1;
        $db->table("player_finances")->where("user_id", $userId)->update(["resort_open" => $new]);
        $status = $new ? "opened" : "closed";
        log_activity($userId, "resort_toggle", "Resort " . $status);
        notify($userId, "resort", "Resort " . $status, "Your resort is now " . $status . ". " . ($new ? "Visitors are arriving!" : "No visitors while closed — upkeep still applies."), $new ? "fa-solid fa-door-open" : "fa-solid fa-door-closed", "/resort");
        return redirect()->to("/resort")->with("success", "Resort " . $status . "!");
    }

    public function openAll()
    {
        $userId = auth()->id();
        $db = db_connect();
        $db->table('player_items')->where('user_id', $userId)->where('status !=', 'broken')->update(['status' => 'open']);
        log_activity($userId, 'Resort', 'Opened all slopes and lifts', 'fa-solid fa-door-open');
        return redirect()->to('/resort')->with('success', 'All slopes and lifts opened.');
    }

    public function closeAll()
    {
        $userId = auth()->id();
        $db = db_connect();
        $db->table('player_items')->where('user_id', $userId)->where('status', 'open')->update(['status' => 'closed']);
        log_activity($userId, 'Resort', 'Closed all slopes and lifts', 'fa-solid fa-door-closed');
        return redirect()->to('/resort')->with('success', 'All slopes and lifts closed.');
    }
}
