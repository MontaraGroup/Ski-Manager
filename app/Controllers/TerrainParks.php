<?php

namespace App\Controllers;

use App\Models\TerrainParkModel;
use App\Models\StaffModel;
use App\Models\PlayerItemModel;

class TerrainParks extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $parkModel = new TerrainParkModel();
        $staffModel = new StaffModel();
        $itemModel = new PlayerItemModel();

        $parks = $parkModel->where('user_id', $userId)->findAll();
        $parkCrew = $staffModel->where('user_id', $userId)->where('role', 'park_crew')->where('status !=', 'fired')->findAll();
        $slopes = $itemModel->where('user_id', $userId)->where('item_type', 'slope')->findAll();
        $finances = model('FinanceModel')->where('user_id', $userId)->first();

        return view('terrain_parks/index', [
            'parks' => $parks,
            'parkCrew' => $parkCrew,
            'slopes' => $slopes,
            'parkConfig' => TerrainParkModel::loadParkConfig(),
            'cash' => $finances['cash'] ?? 0,
        ]);
    }

    public function build()
    {
        $userId = auth()->id();
        $parkModel = new TerrainParkModel();
        $financeModel = model('FinanceModel');

        $parkType = $this->request->getPost('park_type');
        $size = $this->request->getPost('size');
        $name = $this->request->getPost('name');
        $slopeId = $this->request->getPost('slope_id');

        $parkConfig = TerrainParkModel::loadParkConfig();
        if (!isset($parkConfig[$parkType])) {
            return redirect()->to('/terrain-parks')->with('error', 'Invalid park type.');
        }

        $config = TerrainParkModel::getConfig($parkType, $size);
        if (empty($config)) {
            return redirect()->to('/terrain-parks')->with('error', 'Invalid size.');
        }

        $finances = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $config['cost']) {
            return redirect()->to('/terrain-parks')->with('error', 'Not enough cash to build this park feature.');
        }

        $financeModel->where('user_id', $userId)->set('cash', "cash - {$config['cost']}", false)->update();

        $parkModel->insert([
            'user_id' => $userId,
            'name' => $name ?: TerrainParkModel::getLabel($parkType),
            'park_type' => $parkType,
            'size' => $size,
            'condition_pct' => 100,
            'status' => 'under_construction',
            'build_days_left' => $config['build_days'],
            'slope_id' => $slopeId ?: null,
        ]);

        log_activity($userId, 'terrain_park_build', "Started building " . TerrainParkModel::getLabel($parkType) . " ({$size}) for " . currency($config['cost']));
        return redirect()->to('/terrain-parks')->with('success', TerrainParkModel::getLabel($parkType) . ' is now under construction!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $parkModel = new TerrainParkModel();
        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$park || $park['status'] === 'under_construction') {
            return redirect()->to('/terrain-parks')->with('error', 'Cannot toggle this park feature.');
        }
        $newStatus = $park['status'] === 'open' ? 'closed' : 'open';
        $parkModel->update($id, ['status' => $newStatus]);
        log_activity($userId, 'terrain_park_toggle', ucfirst($newStatus) . " " . $park['name']);
        return redirect()->to('/terrain-parks')->with('success', $park['name'] . ' is now ' . $newStatus . '.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $parkModel = new TerrainParkModel();
        $financeModel = model('FinanceModel');
        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$park) return redirect()->to('/terrain-parks')->with('error', 'Park feature not found.');

        $config = TerrainParkModel::getConfig($park['park_type'], $park['size']);
        $repairCost = round($config['cost'] * 0.15 * (1 - $park['condition_pct'] / 100));
        $finances = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $repairCost) return redirect()->to('/terrain-parks')->with('error', 'Not enough cash for repairs.');

        $financeModel->where('user_id', $userId)->set('cash', "cash - {$repairCost}", false)->update();
        $parkModel->update($id, ['condition_pct' => 100.00, 'status' => 'open']);
        log_activity($userId, 'terrain_park_repair', "Repaired " . $park['name'] . " for " . currency($repairCost));
        return redirect()->to('/terrain-parks')->with('success', $park['name'] . ' has been fully repaired!');
    }

    public function demolish(int $id)
    {
        $userId = auth()->id();
        $parkModel = new TerrainParkModel();
        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$park) return redirect()->to('/terrain-parks')->with('error', 'Park feature not found.');

        $config = TerrainParkModel::getConfig($park['park_type'], $park['size']);
        $refund = round($config['cost'] * 0.25);
        $financeModel = model('FinanceModel');
        $financeModel->where('user_id', $userId)->set('cash', "cash + {$refund}", false)->update();
        $parkModel->delete($id);
        log_activity($userId, 'terrain_park_demolish', "Demolished " . $park['name'] . ", refunded " . currency($refund));
        return redirect()->to('/terrain-parks')->with('success', $park['name'] . ' demolished. ' . currency($refund) . ' refunded.');
    }

    public function hireCrew()
    {
        $userId = auth()->id();
        $staffModel = new StaffModel();
        $financeModel = model('FinanceModel');
        $hireCost = 2000;
        $finances = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $hireCost) return redirect()->to('/terrain-parks')->with('error', 'Not enough cash to hire park crew.');

        $names = ['Jake', 'Tyler', 'Kai', 'Mika', 'Sasha', 'Riley', 'Quinn', 'Devon', 'Avery', 'Jordan'];
        $financeModel->where('user_id', $userId)->set('cash', "cash - {$hireCost}", false)->update();
        $staffModel->insert([
            'user_id' => $userId,
            'name' => $names[array_rand($names)] . ' ' . chr(rand(65, 90)) . '.',
            'role' => 'park_crew', 'salary' => 350, 'morale' => 80,
            'level' => rand(1, 3), 'status' => 'active',
        ]);
        log_activity($userId, 'park_crew_hire', "Hired park crew member for " . currency($hireCost));
        return redirect()->to('/terrain-parks')->with('success', 'New park crew member hired!');
    }
}
