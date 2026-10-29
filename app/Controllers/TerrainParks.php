<?php

namespace App\Controllers;

use App\Models\PlayerItemModel;
use App\Models\StaffModel;
use App\Models\TerrainParkModel;

class TerrainParks extends BaseController
{
    public function index(): string
    {
        $locked = checkFeatureUnlock('terrain_parks');
        if ($locked) {
            return $locked;
        }

        $userId     = auth()->id();
        $parkModel  = new TerrainParkModel();
        $staffModel = new StaffModel();
        $itemModel  = new PlayerItemModel();

        $parks    = $parkModel->where('user_id', $userId)->findAll();
        $parkCrew = $staffModel->where('user_id', $userId)->where('role', 'park_crew')->where('status !=', 'fired')->findAll();
        $slopes   = $itemModel->where('user_id', $userId)->where('item_type', 'slope')->findAll();
        $finances = model('FinanceModel')->where('user_id', $userId)->first();

        // Slope dictionary for quick lookup
        $slopeMap = [];
        foreach ($slopes as $slope) {
            $slopeMap[$slope['id']] = $slope['name'] ?? ('Slope #' . $slope['id']);
        }

        // Current game day for event cooldown calculations
        $startDate = function_exists('getSeasonStartDate') ? getSeasonStartDate() : date('Y-m-d');
        $gameDay   = max(1, (int) ((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        // Economic metrics
        $parkConfig = TerrainParkModel::loadParkConfig();
        $totalDailyUpkeep = 0;
        foreach ($parks as $p) {
            if ($p['status'] === 'open' || $p['status'] === 'under_construction') {
                $cfg = TerrainParkModel::getConfig($p['park_type'], $p['size']);
                $totalDailyUpkeep += $cfg['upkeep'] ?? 0;
            }
        }

        $totalDailyRevenue = array_sum(array_column($parks, 'daily_revenue'));
        $totalVisitors     = array_sum(array_column($parks, 'daily_visitors'));

        // Maintenance calculation (how much crew slows decay)
        $crewCount   = count($parkCrew);
        $crewSavings = min($crewCount * 0.4, 1.5);
        $dailyDecay  = max(0.3, 2.0 - $crewSavings);

        return view('terrain_parks/index', [
            'parks'             => $parks,
            'parkCrew'          => $parkCrew,
            'slopes'            => $slopes,
            'slopeMap'          => $slopeMap,
            'parkConfig'        => $parkConfig,
            'cash'              => (int) ($finances['cash'] ?? 0),
            'gameDay'           => $gameDay,
            'totalDailyRevenue' => $totalDailyRevenue,
            'totalDailyUpkeep'  => $totalDailyUpkeep,
            'netDailyProfit'    => $totalDailyRevenue - $totalDailyUpkeep,
            'totalVisitors'     => $totalVisitors,
            'crewSavings'       => $crewSavings,
            'dailyDecay'        => $dailyDecay,
        ]);
    }

    public function build()
    {
        $userId       = auth()->id();
        $parkModel    = new TerrainParkModel();
        $financeModel = model('FinanceModel');

        $parkType = (string) $this->request->getPost('park_type');
        $size     = (string) $this->request->getPost('size');
        $name     = trim((string) $this->request->getPost('name'));
        $slopeId  = $this->request->getPost('slope_id');

        $parkConfig = TerrainParkModel::loadParkConfig();
        if (! isset($parkConfig[$parkType])) {
            return redirect()->to('/terrain-parks')->with('error', 'Invalid park feature type.');
        }

        $config = TerrainParkModel::getConfig($parkType, $size);
        if (empty($config)) {
            return redirect()->to('/terrain-parks')->with('error', 'Invalid feature size.');
        }

        $finances = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $config['cost']) {
            return redirect()->to('/terrain-parks')->with('error', 'Insufficient funds: you need ' . currency($config['cost']) . ' to build this ' . $config['cost'] . ' feature.');
        }

        $financeModel->where('user_id', $userId)->set('cash', "cash - {$config['cost']}", false)->update();

        $defaultName = TerrainParkModel::getLabel($parkType) . ' ' . ucfirst($size);
        $parkModel->insert([
            'user_id'         => $userId,
            'name'            => $name ?: $defaultName,
            'park_type'       => $parkType,
            'size'            => $size,
            'condition_pct'   => 100.00,
            'popularity'      => (int) ($config['popularity_base'] ?? 10),
            'daily_visitors'  => 0,
            'daily_revenue'   => 0,
            'last_event_day'  => 0,
            'status'          => 'under_construction',
            'build_days_left' => $config['build_days'],
            'slope_id'        => ! empty($slopeId) ? (int) $slopeId : null,
        ]);

        log_activity($userId, 'terrain_park_build', 'Started building ' . TerrainParkModel::getLabel($parkType) . " ({$size}) for " . currency($config['cost']), 'fa-solid fa-person-snowboarding');

        return redirect()->to('/terrain-parks')->with('success', TerrainParkModel::getLabel($parkType) . ' is now under construction! Ready in ' . $config['build_days'] . ' day(s).');
    }

    public function upgrade(int $id)
    {
        $userId       = auth()->id();
        $parkModel    = new TerrainParkModel();
        $financeModel = model('FinanceModel');

        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (! $park) {
            return redirect()->to('/terrain-parks')->with('error', 'Terrain park feature not found.');
        }

        if ($park['status'] === 'under_construction') {
            return redirect()->to('/terrain-parks')->with('error', 'This feature is already under construction.');
        }

        $nextSize = TerrainParkModel::getNextSize($park['size']);
        if (! $nextSize) {
            return redirect()->to('/terrain-parks')->with('error', 'This feature is already at maximum tier (Large).');
        }

        $currentConfig = TerrainParkModel::getConfig($park['park_type'], $park['size']);
        $nextConfig    = TerrainParkModel::getConfig($park['park_type'], $nextSize);

        if (empty($nextConfig)) {
            return redirect()->to('/terrain-parks')->with('error', 'Unable to retrieve upgrade configuration.');
        }

        // Differential upgrade pricing
        $costDiff  = max(1000, $nextConfig['cost'] - ($currentConfig['cost'] ?? 0));
        $daysDiff  = max(1, $nextConfig['build_days'] - ($currentConfig['build_days'] ?? 0));

        $finances = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $costDiff) {
            return redirect()->to('/terrain-parks')->with('error', 'Insufficient funds: Upgrade requires ' . currency($costDiff) . '.');
        }

        $financeModel->where('user_id', $userId)->set('cash', "cash - {$costDiff}", false)->update();

        $parkModel->update($id, [
            'size'            => $nextSize,
            'status'          => 'under_construction',
            'build_days_left' => $daysDiff,
            'condition_pct'   => 100.00,
        ]);

        log_activity($userId, 'terrain_park_upgrade', "Upgraded {$park['name']} to " . ucfirst($nextSize) . " tier for " . currency($costDiff), 'fa-solid fa-arrow-up');

        return redirect()->to('/terrain-parks')->with('success', "Upgraded {$park['name']} to " . ucfirst($nextSize) . "! Upgrades complete in {$daysDiff} day(s).");
    }

    public function repair(int $id)
    {
        $userId       = auth()->id();
        $parkModel    = new TerrainParkModel();
        $financeModel = model('FinanceModel');

        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (! $park) {
            return redirect()->to('/terrain-parks')->with('error', 'Park feature not found.');
        }

        $config     = TerrainParkModel::getConfig($park['park_type'], $park['size']);
        $condition  = (float) $park['condition_pct'];
        if ($condition >= 99.9) {
            return redirect()->to('/terrain-parks')->with('error', 'Feature is already in pristine condition.');
        }

        $repairCost = max(50, (int) round(($config['cost'] ?? 20000) * 0.15 * (1 - ($condition / 100))));
        $finances   = $financeModel->where('user_id', $userId)->first();

        if (($finances['cash'] ?? 0) < $repairCost) {
            return redirect()->to('/terrain-parks')->with('error', 'Not enough cash for repairs. Requires ' . currency($repairCost) . '.');
        }

        $financeModel->where('user_id', $userId)->set('cash', "cash - {$repairCost}", false)->update();
        $parkModel->update($id, [
            'condition_pct' => 100.00,
            'status'        => 'open',
        ]);

        log_activity($userId, 'terrain_park_repair', "Repaired {$park['name']} to 100% condition for " . currency($repairCost), 'fa-solid fa-wrench');

        return redirect()->to('/terrain-parks')->with('success', "{$park['name']} has been fully restored to 100% condition!");
    }

    public function hostEvent(int $id)
    {
        $userId       = auth()->id();
        $parkModel    = new TerrainParkModel();
        $financeModel = model('FinanceModel');

        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (! $park) {
            return redirect()->to('/terrain-parks')->with('error', 'Terrain park feature not found.');
        }

        if ($park['status'] !== 'open') {
            return redirect()->to('/terrain-parks')->with('error', 'Events can only be hosted in open features.');
        }

        if ($park['condition_pct'] < 70) {
            return redirect()->to('/terrain-parks')->with('error', 'Feature condition is too low to safely host a competition. Must be at least 70% condition.');
        }

        $startDate = function_exists('getSeasonStartDate') ? getSeasonStartDate() : date('Y-m-d');
        $gameDay   = max(1, (int) ((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $lastEvent = (int) ($park['last_event_day'] ?? 0);
        if ($lastEvent > 0 && ($gameDay - $lastEvent) < 3) {
            $daysWait = 3 - ($gameDay - $lastEvent);
            return redirect()->to('/terrain-parks')->with('error', "Riders need rest! You can host another jam at this feature in {$daysWait} day(s).");
        }

        $eventPurse = 2500;
        $finances   = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $eventPurse) {
            return redirect()->to('/terrain-parks')->with('error', 'Insufficient funds: Hosting a park jam requires ' . currency($eventPurse) . ' for prizes and setup.');
        }

        $financeModel->where('user_id', $userId)->set('cash', "cash - {$eventPurse}", false)->update();

        $popularityGain = rand(15, 25);
        $newPopularity  = min(100, ((int) $park['popularity']) + $popularityGain);

        $parkModel->update($id, [
            'popularity'     => $newPopularity,
            'last_event_day' => $gameDay,
            'condition_pct'  => max(30, (float) $park['condition_pct'] - 8.0), // Event wear and tear
        ]);

        log_activity($userId, 'terrain_park_event', "Hosted Freestyle Park Jam at {$park['name']}! Popularity surged +{$popularityGain}% (now {$newPopularity}%)", 'fa-solid fa-trophy');

        return redirect()->to('/terrain-parks')->with('success', "The Freestyle Jam was a sensation! Popularity increased by +{$popularityGain}% (now {$newPopularity}%).");
    }

    public function toggle(int $id)
    {
        $userId    = auth()->id();
        $parkModel = new TerrainParkModel();
        $park      = $parkModel->where('id', $id)->where('user_id', $userId)->first();

        if (! $park || $park['status'] === 'under_construction') {
            return redirect()->to('/terrain-parks')->with('error', 'Cannot toggle this feature while under construction.');
        }

        $newStatus = $park['status'] === 'open' ? 'closed' : 'open';
        $parkModel->update($id, ['status' => $newStatus]);

        log_activity($userId, 'terrain_park_toggle', ucfirst($newStatus) . " {$park['name']}", 'fa-solid fa-power-off');

        return redirect()->to('/terrain-parks')->with('success', "{$park['name']} is now {$newStatus}.");
    }

    public function demolish(int $id)
    {
        $userId       = auth()->id();
        $parkModel    = new TerrainParkModel();
        $financeModel = model('FinanceModel');

        $park = $parkModel->where('id', $id)->where('user_id', $userId)->first();
        if (! $park) {
            return redirect()->to('/terrain-parks')->with('error', 'Park feature not found.');
        }

        $config = TerrainParkModel::getConfig($park['park_type'], $park['size']);
        $refund = (int) round(($config['cost'] ?? 20000) * 0.25);

        $financeModel->where('user_id', $userId)->set('cash', "cash + {$refund}", false)->update();
        $parkModel->delete($id);

        log_activity($userId, 'terrain_park_demolish', "Demolished {$park['name']}, salvaged " . currency($refund), 'fa-solid fa-trash');

        return redirect()->to('/terrain-parks')->with('success', "{$park['name']} has been demolished. " . currency($refund) . ' in materials salvaged.');
    }

    public function hireCrew()
    {
        $userId       = auth()->id();
        $staffModel   = new StaffModel();
        $financeModel = model('FinanceModel');
        $hireCost     = 2000;

        $finances = $financeModel->where('user_id', $userId)->first();
        if (($finances['cash'] ?? 0) < $hireCost) {
            return redirect()->to('/terrain-parks')->with('error', 'Not enough cash to hire park crew. Requires ' . currency($hireCost) . '.');
        }

        $names = ['Jake', 'Tyler', 'Kai', 'Mika', 'Sasha', 'Riley', 'Quinn', 'Devon', 'Avery', 'Jordan', 'Cole', 'Zion', 'Rowan'];
        $financeModel->where('user_id', $userId)->set('cash', "cash - {$hireCost}", false)->update();

        $staffModel->insert([
            'user_id' => $userId,
            'name'    => $names[array_rand($names)] . ' ' . chr(rand(65, 90)) . '.',
            'role'    => 'park_crew',
            'salary'  => 350,
            'morale'  => 85,
            'level'   => rand(1, 3),
            'status'  => 'active',
        ]);

        log_activity($userId, 'park_crew_hire', 'Hired new terrain park crew specialist for ' . currency($hireCost), 'fa-solid fa-user-plus');

        return redirect()->to('/terrain-parks')->with('success', 'New park crew specialist hired! Feature condition decay has been reduced.');
    }
}
