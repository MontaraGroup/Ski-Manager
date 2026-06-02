<?php

namespace App\Controllers;

use App\Models\StaffModel;
use App\Models\PlayerItemModel;

class Grooming extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $staffModel = new StaffModel();
        $itemModel = new PlayerItemModel();

        $groomers = $staffModel->where('user_id', $userId)->where('role', 'groomer')->where('status !=', 'fired')->findAll();
        $slopes = $itemModel->where('user_id', $userId)->where('item_type', 'slope')->findAll();

        $sectors = [];
        foreach ($slopes as $slope) {
            $s = (int) $slope['sector'];
            if (!isset($sectors[$s])) $sectors[$s] = ['slopes' => [], 'groomers_needed' => 0, 'groomers_assigned' => 0];
            $sectors[$s]['slopes'][] = $slope;
        }

        foreach ($sectors as $s => &$sector) {
            $sector['groomers_needed'] = max(1, (int) ceil(count($sector['slopes']) / 3));
        }

        $assignedGroomers = [];
        $unassignedGroomers = [];
        foreach ($groomers as $g) {
            if ($g['assigned_to'] && preg_match('/^sector_(\d+)$/', $g['assigned_to'], $m)) {
                $sNum = (int) $m[1];
                if (isset($sectors[$sNum])) {
                    $sectors[$sNum]['groomers_assigned']++;
                    $assignedGroomers[] = $g;
                } else {
                    $unassignedGroomers[] = $g;
                }
            } else {
                $unassignedGroomers[] = $g;
            }
        }

        $totalNeeded = array_sum(array_column($sectors, 'groomers_needed'));
        $totalAssigned = array_sum(array_column($sectors, 'groomers_assigned'));

        return view('grooming/index', [
            'groomers' => $groomers,
            'unassignedGroomers' => $unassignedGroomers,
            'sectors' => $sectors,
            'totalNeeded' => $totalNeeded,
            'totalAssigned' => $totalAssigned,
            'slopeCount' => count($slopes),
        ]);
    }

    public function assign()
    {
        $userId = auth()->id();
        $groomerId = (int) $this->request->getPost('groomer_id');
        $sector = $this->request->getPost('sector');

        $staffModel = new StaffModel();
        $groomer = $staffModel->where('id', $groomerId)->where('user_id', $userId)->where('role', 'groomer')->first();

        if (!$groomer) return redirect()->back()->with('error', 'Groomer not found.');

        $assignment = $sector !== '' ? 'sector_' . (int) $sector : null;
        $staffModel->update($groomerId, ['assigned_to' => $assignment]);

        $msg = $assignment ? $groomer['name'] . ' assigned to Sector ' . (int) $sector : $groomer['name'] . ' unassigned';
        log_activity($userId, 'Grooming', $msg, 'fa-solid fa-truck-plow');

        return redirect()->to('/grooming')->with('success', $msg);
    }
}
