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

        return view('parking/index', [
            'facilities' => $facilities,
            'parkingConfig' => ParkingModel::PARKING_CONFIG,
            'cash' => $finances['cash'] ?? 0,
            'totalCapacity' => $totalCapacity,
            'totalOccupied' => $totalOccupied,
            'totalRevenue' => $totalRevenue,
        ]);
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
