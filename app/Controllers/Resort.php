<?php

namespace App\Controllers;

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

        $sectors = [];
        foreach ($items as $item) {
            $s = (int) $item['sector'];
            if (!isset($sectors[$s])) $sectors[$s] = ['lifts' => [], 'slopes' => []];
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

    public function toggleItem(int $id)
    {
        $userId = auth()->id();
        $itemModel = new PlayerItemModel();
        $item = $itemModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$item) return redirect()->back()->with('error', 'Not found.');

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
}
