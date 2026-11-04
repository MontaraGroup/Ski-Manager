<?php

namespace App\Controllers;

use App\Libraries\Ledger;
use App\Models\ParkingModel;

class Parking extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('parking'); if ($locked) return $locked;
        $userId = auth()->id();
        $parkingModel = new ParkingModel();

        $facilities = $parkingModel->where('user_id', $userId)->findAll();
        $finances = model('FinanceModel')->where('user_id', $userId)->first();

        $totalCapacity = ParkingModel::getTotalCapacity($facilities);
        $totalOccupied = array_sum(array_column($facilities, 'occupied'));
        $totalRevenue = array_sum(array_column($facilities, 'daily_revenue'));
        $cash = (int) ($finances['cash'] ?? 0);
        $occupancyPct = $totalCapacity > 0 ? (int) round($totalOccupied / $totalCapacity * 100) : 0;

        $formattedFacilities = [];
        foreach ($facilities as $f) {
            $fCap = (int) $f['capacity'];
            $fOcc = (int) $f['occupied'];
            $fOccPct = $fCap > 0 ? (int) round($fOcc / $fCap * 100) : 0;
            $rawIcon = ParkingModel::getIcon($f['parking_type']);
            $icon = preg_match('/^[a-z0-9 -]+$/i', $rawIcon) ? $rawIcon : 'fa-square-parking';
            $cfg = ParkingModel::getConfig($f['parking_type']) ?? [];
            $defaultFee = (int) ($cfg['default_fee'] ?? 10);
            $repairCost = (int) round(($cfg['cost'] ?? 0) * 0.10 * (1 - (float) $f['condition_pct'] / 100));

            $formattedFacilities[] = [
                'id' => (int) $f['id'],
                'name' => (string) $f['name'],
                'parking_type' => (string) $f['parking_type'],
                'typeLabel' => ParkingModel::getLabel($f['parking_type']),
                'icon' => $icon,
                'capacity' => $fCap,
                'capacityText' => number_format($fCap),
                'occupied' => $fOcc,
                'occupiedText' => number_format($fOcc),
                'occupancyPct' => $fOccPct,
                'dailyRevenue' => (int) $f['daily_revenue'],
                'dailyRevenueText' => currency((int) $f['daily_revenue']),
                'condition_pct' => (int) $f['condition_pct'],
                'status' => (string) $f['status'],
                'build_days_left' => (int) $f['build_days_left'],
                'fee_per_day' => (int) $f['fee_per_day'],
                'defaultFeeText' => currency($defaultFee),
                'repairCost' => $repairCost,
                'repairCostText' => currency($repairCost),
                'canRepair' => (float) $f['condition_pct'] < 100,
            ];
        }

        $formattedConfig = [];
        foreach (ParkingModel::PARKING_CONFIG as $key => $cfg) {
            $rawIcon = $cfg['icon'] ?? 'fa-square-parking';
            $icon = preg_match('/^[a-z0-9 -]+$/i', $rawIcon) ? $rawIcon : 'fa-square-parking';
            $formattedConfig[] = [
                'key' => (string) $key,
                'label' => (string) $cfg['label'],
                'icon' => $icon,
                'capacity' => (int) $cfg['capacity'],
                'capacityText' => number_format((int) $cfg['capacity']),
                'cost' => (int) $cfg['cost'],
                'costText' => currency((int) $cfg['cost']),
                'originalCostText' => currency((int) ($cfg['cost'] * 1.2)),
                'upkeepText' => currency((int) $cfg['upkeep']),
                'build_days' => (int) $cfg['build_days'],
                'canAfford' => $cash >= (int) $cfg['cost'],
            ];
        }

        $page = [
            'stats' => [
                'totalCapacity' => $totalCapacity,
                'totalCapacityText' => number_format($totalCapacity),
                'totalOccupied' => $totalOccupied,
                'occupancyPct' => $occupancyPct,
                'totalRevenueText' => currency($totalRevenue),
                'facilityCount' => count($facilities),
                'nearlyFull' => $occupancyPct > 90 && count($facilities) > 0,
            ],
            'facilities' => $formattedFacilities,
            'catalog' => $formattedConfig,
        ];

        return view('parking/index', ['page' => $page]);
    }

    public function build()
    {
        $userId = auth()->id();
        $parkingModel = new ParkingModel();
        $financeModel = model('FinanceModel');

        $parkingType = $this->request->getPost('parking_type');
        $name = $this->request->getPost('name');

        $config = ParkingModel::getConfig($parkingType);
        if (empty($config)) {
            return redirect()->to('/parking')->with('error', 'Invalid parking type.');
        }

        $db = db_connect();
        $db->transStart();
        if (!Ledger::charge($userId, (int) $config['cost'], 'Parking', 'Built ' . $config['label'])) {
            $db->transRollback();
            return redirect()->to('/parking')->with('error', 'Not enough cash: ' . $config['label'] . ' costs ' . currency($config['cost']) . '.');
        }

        $parkingModel->insert([
            'user_id' => $userId,
            'name' => $name ?: $config['label'],
            'parking_type' => $parkingType,
            'capacity' => $config['capacity'],
            'fee_per_day' => $config['default_fee'],
            'condition_pct' => 100,
            'status' => 'under_construction',
            'build_days_left' => $config['build_days'],
        ]);
        $db->transComplete();

        log_activity($userId, 'parking_build', "Started building " . $config['label'] . " for " . currency($config['cost']));

        return redirect()->to('/parking')->with('success', $config['label'] . ' is now under construction!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $parkingModel = new ParkingModel();

        $facility = $parkingModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$facility || $facility['status'] === 'under_construction') {
            return redirect()->to('/parking')->with('error', 'Cannot toggle this facility.');
        }

        $newStatus = ($facility['status'] === 'open' || $facility['status'] === 'full') ? 'closed' : 'open';
        $parkingModel->update($id, ['status' => $newStatus]);

        log_activity($userId, 'parking_toggle', ucfirst($newStatus) . " " . $facility['name']);

        return redirect()->to('/parking')->with('success', $facility['name'] . ' is now ' . $newStatus . '.');
    }

    public function updateFee(int $id)
    {
        $userId = auth()->id();
        $parkingModel = new ParkingModel();

        $facility = $parkingModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$facility) {
            return redirect()->to('/parking')->with('error', 'Facility not found.');
        }

        $newFee = (float) $this->request->getPost('fee');
        if ($newFee < 0 || $newFee > 100) {
            return redirect()->to('/parking')->with('error', 'Fee must be between ' . currency(0) . ' and ' . currency(100) . '.');
        }

        $parkingModel->update($id, ['fee_per_day' => $newFee]);

        log_activity($userId, 'parking_fee', "Updated " . $facility['name'] . " fee to " . currency($newFee));

        return redirect()->to('/parking')->with('success', 'Parking fee updated.');
    }

    public function repair(int $id)
    {
        $userId = auth()->id();
        $parkingModel = new ParkingModel();
        $financeModel = model('FinanceModel');

        $facility = $parkingModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$facility) {
            return redirect()->to('/parking')->with('error', 'Facility not found.');
        }

        $config = ParkingModel::getConfig($facility['parking_type']);
        $repairCost = (int) round($config['cost'] * 0.10 * (1 - $facility['condition_pct'] / 100));
        if ($repairCost <= 0) return redirect()->to('/parking')->with('error', $facility['name'] . ' is already in perfect condition.');

        $db = db_connect();
        $db->transStart();
        if (!Ledger::charge($userId, $repairCost, 'Parking', 'Repaired ' . $facility['name'])) {
            $db->transRollback();
            return redirect()->to('/parking')->with('error', 'Not enough cash: repairs cost ' . currency($repairCost) . '.');
        }
        $parkingModel->update($id, ['condition_pct' => 100.00]);
        $db->transComplete();

        log_activity($userId, 'parking_repair', "Repaired " . $facility['name'] . " for " . currency($repairCost));

        return redirect()->to('/parking')->with('success', $facility['name'] . ' repaired!');
    }

    public function demolish(int $id)
    {
        $userId = auth()->id();
        $parkingModel = new ParkingModel();

        $facility = $parkingModel->where('id', $id)->where('user_id', $userId)->first();
        if (!$facility) {
            return redirect()->to('/parking')->with('error', 'Facility not found.');
        }

        $config = ParkingModel::getConfig($facility['parking_type']);
        $refund = (int) round($config['cost'] * 0.20);

        $db = db_connect();
        $db->transStart();
        $parkingModel->delete($id);
        if ($db->affectedRows() === 1) {
            Ledger::credit($userId, $refund, 'Parking', 'Demolished ' . $facility['name']);
        }
        $db->transComplete();

        log_activity($userId, 'parking_demolish', "Demolished " . $facility['name'] . ", refunded " . currency($refund));

        return redirect()->to('/parking')->with('success', $facility['name'] . ' demolished. ' . currency($refund) . ' refunded.');
    }
}
