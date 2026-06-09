<?php

namespace App\Controllers;

use App\Models\StaffModel;
use App\Models\PlayerItemModel;

class Grooming extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('grooming'); if ($locked) return $locked;
        $userId = auth()->id();
        $staffModel = new StaffModel();
        $itemModel = new PlayerItemModel();
        $db = db_connect();

        $groomers = $staffModel->where('user_id', $userId)->where('role', 'groomer')->where('status !=', 'fired')->findAll();
        $slopes = $itemModel->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->findAll();
        $equipment = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->get()->getResultArray();

        $sectors = [];
        foreach ($slopes as $slope) {
            $s = (int) ($slope['sector'] ?? 0);
            if (!isset($sectors[$s])) $sectors[$s] = ['slopes' => [], 'groomers_needed' => 0, 'groomers_assigned' => 0, 'avg_condition' => 0];
            $sectors[$s]['slopes'][] = $slope;
        }

        $sectorNames = [];
        $sectorRows = $db->table('resort_sectors')->get()->getResultArray();
        foreach ($sectorRows as $sr) { $sectorNames[(int)$sr['id']] = $sr['name']; }

        foreach ($sectors as $s => &$sector) {
            $sector['name'] = $sectorNames[$s] ?? 'Unassigned';
            $sector['groomers_needed'] = max(1, (int) ceil(count($sector['slopes']) / 3));
            $conditions = array_column($sector['slopes'], 'condition_pct');
            $sector['avg_condition'] = count($conditions) > 0 ? round(array_sum($conditions) / count($conditions)) : 0;
        }

        foreach ($groomers as $g) {
            if ($g['assigned_to'] && preg_match('/^sector_(\d+)$/', $g['assigned_to'], $m)) {
                $sNum = (int) $m[1];
                if (isset($sectors[$sNum])) $sectors[$sNum]['groomers_assigned']++;
            }
        }

        $totalNeeded = array_sum(array_column($sectors, 'groomers_needed'));
        $totalAssigned = array_sum(array_column($sectors, 'groomers_assigned'));
        $overallCondition = count($slopes) > 0 ? round(array_sum(array_column($slopes, 'condition_pct')) / count($slopes)) : 0;
        $criticalSlopes = array_filter($slopes, fn($s) => (int) ($s['condition_pct'] ?? 100) < 40);

        $activeEquipment = count(array_filter($equipment, fn($e) => $e['status'] === 'active'));
        $brokenEquipment = count(array_filter($equipment, fn($e) => $e['status'] === 'broken'));

        $groomBoost = $this->calcGroomBoost($userId, $activeEquipment);
        $dailyDecay = count($slopes) > 0 ? 5 : 0;

        $dailyFuelCost = array_sum(array_map(fn($e) => $e['status'] === 'active' ? (int)($e['daily_cost'] ?? 0) : 0, $equipment));
        $crewSalaryCost = array_sum(array_column($groomers, 'salary'));

        $weather = $db->table('weather')->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();
        $weatherTemp = (int) ($weather['temp'] ?? -2);
        $weatherDesc = $weather['condition_name'] ?? 'Clear';
        if (function_exists('isImperial') && isImperial()) {
            $weatherTemp = round($weatherTemp * 9 / 5 + 32);
        }

        return view('grooming/index', [
            'groomers' => $groomers,
            'sectors' => $sectors,
            'totalNeeded' => $totalNeeded,
            'totalAssigned' => $totalAssigned,
            'slopeCount' => count($slopes),
            'slopes' => $slopes,
            'overallCondition' => $overallCondition,
            'criticalSlopes' => $criticalSlopes,
            'equipment' => $equipment,
            'activeEquipment' => $activeEquipment,
            'brokenEquipment' => $brokenEquipment,
            'groomBoost' => $groomBoost,
            'dailyDecay' => $dailyDecay,
            'dailyFuelCost' => $dailyFuelCost,
            'crewSalaryCost' => $crewSalaryCost,
            'weatherTemp' => $weatherTemp,
            'weatherDesc' => $weatherDesc,
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
        return $this->doGroom('all');
    }

    public function groomSingle()
    {
        return $this->doGroom('single', (int) $this->request->getPost('slope_id'));
    }

    public function groomCritical()
    {
        return $this->doGroom('critical');
    }

    public function groomSector()
    {
        return $this->doGroom('sector', 0, (int) $this->request->getPost('sector'));
    }

    private function doGroom(string $mode, int $slopeId = 0, int $sectorNum = 0)
    {
        $userId = auth()->id();
        $db = db_connect();

        $activeGroomers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->where('status', 'active')->countAllResults();
        if ($activeGroomers === 0) {
            return redirect()->to('/grooming')->with('error', 'No active groomers. Turn on equipment or buy a groomer.');
        }

        $boost = $this->calcGroomBoost($userId, $activeGroomers);

        $query = $db->table('player_items')->where('user_id', $userId)->whereIn('item_type', ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'])->where('condition_pct <', 100);

        if ($mode === 'single') {
            $query->where('id', $slopeId);
            $boost += 5;
        } elseif ($mode === 'critical') {
            $query->where('condition_pct <', 40);
            $boost += 3;
        } elseif ($mode === 'sector') {
            $query->where('sector', $sectorNum);
        }

        $slopes = $query->get()->getResultArray();
        if (empty($slopes)) {
            return redirect()->to('/grooming')->with('error', 'No slopes need grooming.');
        }

        foreach ($slopes as $slope) {
            $newCond = min(100, (int) $slope['condition_pct'] + $boost);
            $db->table('player_items')->where('id', $slope['id'])->update(['condition_pct' => $newCond]);
        }

        $label = match($mode) {
            'single' => $slopes[0]['name'],
            'critical' => count($slopes) . ' critical slope(s)',
            'sector' => 'Sector ' . $sectorNum,
            default => count($slopes) . ' slopes',
        };

        log_activity($userId, 'Grooming', 'Groomed ' . $label . ': +' . $boost . '%', 'fa-solid fa-tractor');
        return redirect()->to('/grooming')->with('success', 'Groomed ' . $label . '! +' . $boost . '% condition.');
    }

    private function calcGroomBoost(int $userId, int $activeGroomers): int
    {
        $staffModel = new StaffModel();
        $groomers = $staffModel->where('user_id', $userId)->where('role', 'groomer')->where('status', 'active')->findAll();

        $baseBoost = min($activeGroomers * 5, 20);
        $crewBonus = 0;
        foreach ($groomers as $g) {
            if ($g['assigned_to']) $crewBonus += (int) $g['level'];
        }

        return min($baseBoost + $crewBonus, 30);
    }
}
