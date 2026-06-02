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
        $db = db_connect();

        $groomers = $staffModel->where('user_id', $userId)->where('role', 'groomer')->where('status !=', 'fired')->findAll();
        $slopes = $itemModel->where('user_id', $userId)->where('item_type', 'slope')->findAll();
        $equipment = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->get()->getResultArray();

        $sectors = [];
        foreach ($slopes as $slope) {
            $s = (int) $slope['sector'];
            if (!isset($sectors[$s])) $sectors[$s] = ['slopes' => [], 'groomers_needed' => 0, 'groomers_assigned' => 0, 'avg_condition' => 0];
            $sectors[$s]['slopes'][] = $slope;
        }

        foreach ($sectors as $s => &$sector) {
            $sector['groomers_needed'] = max(1, (int) ceil(count($sector['slopes']) / 3));
            $conditions = array_column($sector['slopes'], 'condition_pct');
            $sector['avg_condition'] = count($conditions) > 0 ? round(array_sum($conditions) / count($conditions)) : 0;
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
        $overallCondition = count($slopes) > 0 ? round(array_sum(array_column($slopes, 'condition_pct')) / count($slopes)) : 0;

        $criticalSlopes = array_filter($slopes, fn($s) => (int) $s['condition_pct'] < 40);
        $closedSlopes = array_filter($slopes, fn($s) => $s['status'] !== 'open');

        $activeEquipment = array_filter($equipment, fn($e) => $e['status'] === 'active');
        $brokenEquipment = array_filter($equipment, fn($e) => $e['status'] === 'broken');

        return view('grooming/index', [
            'groomers' => $groomers,
            'unassignedGroomers' => $unassignedGroomers,
            'sectors' => $sectors,
            'totalNeeded' => $totalNeeded,
            'totalAssigned' => $totalAssigned,
            'slopeCount' => count($slopes),
            'slopes' => $slopes,
            'overallCondition' => $overallCondition,
            'criticalSlopes' => $criticalSlopes,
            'closedSlopes' => $closedSlopes,
            'equipment' => $equipment,
            'activeEquipment' => count($activeEquipment),
            'brokenEquipment' => count($brokenEquipment),
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
        log_activity($userId, 'Grooming', $msg, 'fa-solid fa-tractor');
        return redirect()->to('/grooming')->with('success', $msg);
    }

    public function groomAll()
    {
        $userId = auth()->id();
        $db = db_connect();

        $activeGroomers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->where('status', 'active')->countAllResults();
        if ($activeGroomers === 0) {
            return redirect()->to('/grooming')->with('error', 'No active groomers available. Turn on equipment first.');
        }

        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('condition_pct <', 100)->get()->getResultArray();
        if (empty($slopes)) {
            return redirect()->to('/grooming')->with('error', 'All slopes are already at 100% condition.');
        }

        $boostPerGroomer = 5;
        $totalBoost = min($activeGroomers * $boostPerGroomer, 20);

        foreach ($slopes as $slope) {
            $newCond = min(100, (int) $slope['condition_pct'] + $totalBoost);
            $db->table('player_items')->where('id', $slope['id'])->update(['condition_pct' => $newCond]);
        }

        log_activity($userId, 'Grooming', 'Manual grooming run: +' . $totalBoost . '% condition on ' . count($slopes) . ' slopes', 'fa-solid fa-tractor');
        return redirect()->to('/grooming')->with('success', 'Groomed ' . count($slopes) . ' slopes! +' . $totalBoost . '% condition (based on ' . $activeGroomers . ' active machines).');
    }
}
