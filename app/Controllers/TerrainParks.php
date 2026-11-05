<?php

namespace App\Controllers;

use App\Models\TerrainParkModel;
use App\Models\StaffModel;
use App\Models\PlayerItemModel;

class TerrainParks extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('terrain_parks'); if ($locked) return $locked;
        $userId = auth()->id();
        $parkModel = new TerrainParkModel();
        $staffModel = new StaffModel();
        $itemModel = new PlayerItemModel();

        $parks = $parkModel->where('user_id', $userId)->findAll();
        $parkCrew = $staffModel->where('user_id', $userId)->where('role', 'park_crew')->where('status !=', 'fired')->findAll();
        $slopes = $itemModel->where('user_id', $userId)->where('item_type', 'slope')->findAll();
        $finances = model('FinanceModel')->where('user_id', $userId)->first();

        $rawConfig = TerrainParkModel::loadParkConfig();
        $cash = (int) ($finances['cash'] ?? 0);

        $slopesMap = [];
        foreach ($slopes as $sl) {
            $slopesMap[$sl['id']] = $sl['name'] ?? ('Slope #' . $sl['id']);
        }

        $formattedParks = array_map(function($p) use ($rawConfig, $slopesMap, $cash) {
            $typeKey = $p['park_type'];
            $sizeKey = $p['size'];
            $cfg = $rawConfig[$typeKey]['sizes'][$sizeKey] ?? ['cost' => 0, 'upkeep' => 0, 'capacity' => 0];
            $cost = (int) ($cfg['cost'] ?? 0);
            $cond = (float) $p['condition_pct'];
            $repairCost = (int) round($cost * 0.15 * (1 - $cond / 100));
            $refundAmount = (int) round($cost * 0.25);
            $iconRaw = $rawConfig[$typeKey]['icon'] ?? 'mountain';
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $iconRaw);

            return [
                'id' => (int) $p['id'],
                'name' => $p['name'],
                'park_type' => $typeKey,
                'typeLabel' => $rawConfig[$typeKey]['label'] ?? ucfirst($typeKey),
                'icon' => $cleanIcon,
                'size' => $sizeKey,
                'status' => $p['status'],
                'condition_pct' => (int) round($cond),
                'popularity' => (int) ($p['popularity'] ?? 50),
                'daily_visitors' => (int) ($p['daily_visitors'] ?? 0),
                'build_days_left' => (int) ($p['build_days_left'] ?? 0),
                'slope_id' => $p['slope_id'] ? (int) $p['slope_id'] : null,
                'slope_name' => $p['slope_id'] ? ($slopesMap[$p['slope_id']] ?? null) : null,
                'upkeepFormatted' => currency((int) ($cfg['upkeep'] ?? 0)),
                'repair_cost' => $repairCost,
                'repair_cost_formatted' => currency($repairCost),
                'refund_amount' => $refundAmount,
                'refund_formatted' => currency($refundAmount),
                'canAffordRepair' => $cash >= $repairCost,
            ];
        }, $parks);

        $formattedCrew = array_map(function($c) {
            return [
                'id' => (int) $c['id'],
                'name' => $c['name'],
                'level' => (int) ($c['level'] ?? 1),
                'morale' => (int) ($c['morale'] ?? 100),
                'salary' => (int) ($c['salary'] ?? 0),
                'salaryFormatted' => currency((int) ($c['salary'] ?? 0)),
            ];
        }, $parkCrew);

        $formattedSlopes = array_map(function($s) {
            return [
                'id' => (int) $s['id'],
                'name' => $s['name'] ?? ('Slope #' . $s['id']),
            ];
        }, $slopes);

        $cleanConfig = [];
        foreach ($rawConfig as $key => $typeData) {
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $typeData['icon'] ?? 'mountain');
            $sizes = [];
            foreach ($typeData['sizes'] as $sz => $szData) {
                $sizes[$sz] = [
                    'cost' => (int) $szData['cost'],
                    'costFormatted' => currency((int) $szData['cost']),
                    'upkeep' => (int) $szData['upkeep'],
                    'upkeepFormatted' => currency((int) $szData['upkeep']),
                    'build_days' => (int) $szData['build_days'],
                    'capacity' => (int) $szData['capacity'],
                    'popularity_base' => (int) ($szData['popularity_base'] ?? 50),
                ];
            }
            $cleanConfig[$key] = [
                'key' => $key,
                'label' => $typeData['label'],
                'icon' => $cleanIcon,
                'sizes' => $sizes,
            ];
        }

        $openParks = array_filter($parks, fn($p) => $p['status'] === 'open');
        $buildingParks = array_filter($parks, fn($p) => $p['status'] === 'under_construction');
        $totalVisitors = array_sum(array_column($parks, 'daily_visitors'));
        $avgCondition = count($parks) > 0 ? (int) round(array_sum(array_column($parks, 'condition_pct')) / count($parks)) : 0;

        $page = [
            'parks' => $formattedParks,
            'parkCrew' => $formattedCrew,
            'slopes' => $formattedSlopes,
            'parkConfig' => $cleanConfig,
            'cash' => $cash,
            'cashFormatted' => currency($cash),
            'stats' => [
                'openCount' => count($openParks),
                'buildingCount' => count($buildingParks),
                'dailyVisitors' => $totalVisitors,
                'crewCount' => count($parkCrew),
                'avgCondition' => $avgCondition,
            ],
        ];

        return view('terrain_parks/index', ['page' => $page]);
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
