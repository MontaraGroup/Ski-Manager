<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    private array $availableWidgets = [
        'stats' => ['name' => 'Key Stats', 'icon' => 'fa-solid fa-chart-bar', 'default' => true, 'size' => 'large'],
        'season' => ['name' => 'Season Progress', 'icon' => 'fa-solid fa-calendar', 'default' => true, 'size' => 'large'],
        'weather_mini' => ['name' => 'Weather', 'icon' => 'fa-solid fa-cloud-sun', 'default' => true, 'size' => 'medium'],
        'achievements_mini' => ['name' => 'Achievement Alerts', 'icon' => 'fa-solid fa-bell', 'default' => true, 'size' => 'large'],
        'actions' => ['name' => 'Quick Actions', 'icon' => 'fa-solid fa-bolt', 'default' => true, 'size' => 'medium'],
        'overview' => ['name' => 'Resort Overview', 'icon' => 'fa-solid fa-mountain-sun', 'default' => true, 'size' => 'large'],
        'achievements' => ['name' => 'Achievements', 'icon' => 'fa-solid fa-award', 'default' => true, 'size' => 'medium'],
        'activity' => ['name' => 'Recent Activity', 'icon' => 'fa-solid fa-clock-rotate-left', 'default' => true, 'size' => 'medium'],
        'parking_mini' => ['name' => 'Parking', 'icon' => 'fa-solid fa-square-parking', 'default' => false, 'size' => 'small'],
        'terrain_parks_mini' => ['name' => 'Terrain Parks', 'icon' => 'fa-solid fa-person-snowboarding', 'default' => false, 'size' => 'small'],
        'finances_mini' => ['name' => 'Finances', 'icon' => 'fa-solid fa-coins', 'default' => false, 'size' => 'medium'],
        'staff_mini' => ['name' => 'Staff Overview', 'icon' => 'fa-solid fa-users', 'default' => false, 'size' => 'medium'],
        'equipment_mini' => ['name' => 'Equipment', 'icon' => 'fa-solid fa-toolbox', 'default' => false, 'size' => 'small'],
        'insurance_mini' => ['name' => 'Insurance', 'icon' => 'fa-solid fa-shield-halved', 'default' => false, 'size' => 'small'],
        'loans_mini' => ['name' => 'Loans', 'icon' => 'fa-solid fa-landmark', 'default' => false, 'size' => 'small'],
        'marketing_mini' => ['name' => 'Marketing', 'icon' => 'fa-solid fa-bullhorn', 'default' => false, 'size' => 'small'],
    ];

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();

        if (empty($widgets)) {
            $order = 0;
            foreach ($this->availableWidgets as $key => $w) {
                $db->table('dashboard_widgets')->insert([
                    'user_id' => $userId, 'widget_key' => $key, 'visible' => $w['default'] ? 1 : 0, 'size' => $w['size'], 'sort_order' => $order++,
                ]);
            }
            $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();
        }

        $existingKeys = array_column($widgets, 'widget_key');
        $maxOrder = max(array_column($widgets, 'sort_order') ?: [0]);
        foreach ($this->availableWidgets as $key => $w) {
            if (!in_array($key, $existingKeys)) {
                $maxOrder++;
                $db->table('dashboard_widgets')->insert([
                    'user_id' => $userId, 'widget_key' => $key, 'visible' => 0, 'size' => $w['size'], 'sort_order' => $maxOrder,
                ]);
            }
        }
        if (count($existingKeys) < count($this->availableWidgets)) {
            $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();
        }

        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $weather = $db->table('weather')->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();
        $genepis = $db->table('genepis')->where('user_id', $userId)->get()->getRowArray();
        $slopeCount = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->where('status', 'open')->countAllResults(false);
        $liftCount = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->where('status', 'open')->countAllResults(false);
        $staffCount = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->countAllResults(false);
        $buildingCount = $db->table('buildings')->where('user_id', $userId)->countAllResults(false);
        $unclaimedAchievements = $db->table('achievements')->where('user_id', $userId)->where('completed', 1)->where('claimed', 0)->countAllResults(false);
        $inProgressAchievements = $db->table('achievements')->where('user_id', $userId)->where('completed', 0)->orderBy('progress', 'DESC')->limit(5)->get()->getResultArray();
        $recentActivity = $db->table('activity_log')->where('user_id', $userId)->orderBy('created_at', 'DESC')->limit(8)->get()->getResultArray();
        $dailyBonus = $db->table('daily_bonus')->where('user_id', $userId)->get()->getRowArray();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime('2026-06-01')) / 86400) + 1);
        $bonusAvailable = !$dailyBonus || (int)($dailyBonus['last_claim_day'] ?? 0) < $gameDay;
        $parkingFacilities = $db->table('parking')->where('user_id', $userId)->get()->getResultArray();
        $terrainParks = $db->table('terrain_parks')->where('user_id', $userId)->get()->getResultArray();
        $staffAll = $db->table('staff')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $equipment = $db->table('equipment')->where('user_id', $userId)->get()->getResultArray();
        $insurance = $db->table('insurance')->where('user_id', $userId)->get()->getResultArray();
        $loans = $db->table('loans')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $marketing = $db->table('marketing_campaigns')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();

        return view('dashboard/index', [
            'widgets' => $widgets, 'availableWidgets' => $this->availableWidgets,
            'finance' => $finance, 'weather' => $weather, 'genepis' => $genepis,
            'slopeCount' => $slopeCount, 'liftCount' => $liftCount, 'staffCount' => $staffCount, 'buildingCount' => $buildingCount,
            'unclaimedAchievements' => $unclaimedAchievements, 'inProgressAchievements' => $inProgressAchievements,
            'recentActivity' => $recentActivity, 'bonusAvailable' => $bonusAvailable, 'gameDay' => $gameDay,
            'parkingFacilities' => $parkingFacilities, 'terrainParks' => $terrainParks,
            'staffAll' => $staffAll, 'equipment' => $equipment, 'insurance' => $insurance, 'loans' => $loans, 'marketing' => $marketing,
        ]);
    }

    public function toggleWidget()
    {
        $userId = auth()->id();
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $key = $data['widget_key'] ?? null;
        $db = db_connect();

        $widget = $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->get()->getRowArray();
        if ($widget) {
            $newVisible = $widget['visible'] ? 0 : 1;
            $db->table('dashboard_widgets')->where('id', $widget['id'])->update(['visible' => $newVisible]);
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => true, 'visible' => (bool) $newVisible]);
            }
        }
        return redirect()->to('/dashboard');
    }

    public function resizeWidget()
    {
        $userId = auth()->id();
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $key = $data['widget_key'] ?? null;
        $size = $data['size'] ?? null;
        $db = db_connect();

        if (!in_array($size, ['small', 'medium', 'large'])) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['success' => false]);
            return redirect()->to('/dashboard');
        }

        $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->update(['size' => $size]);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true, 'size' => $size]);
        }
        return redirect()->to('/dashboard');
    }

    public function reorderWidgets()
    {
        $userId = auth()->id();
        $data = $this->request->getJSON(true) ?? $this->request->getPost();
        $order = $data['order'] ?? [];
        $db = db_connect();

        if (is_array($order)) {
            foreach ($order as $i => $key) {
                $db->table('dashboard_widgets')->where('user_id', $userId)->where('widget_key', $key)->update(['sort_order' => $i]);
            }
        }
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => true]);
        }
        return redirect()->to('/dashboard');
    }

    public function moveWidget()
    {
        $userId = auth()->id();
        $key = $this->request->getPost('widget_key');
        $direction = $this->request->getPost('direction');
        $db = db_connect();

        $widgets = $db->table('dashboard_widgets')->where('user_id', $userId)->orderBy('sort_order')->get()->getResultArray();
        $index = array_search($key, array_column($widgets, 'widget_key'));
        if ($index === false) return redirect()->to('/dashboard');

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swapIndex < 0 || $swapIndex >= count($widgets)) return redirect()->to('/dashboard');

        $db->table('dashboard_widgets')->where('id', $widgets[$index]['id'])->update(['sort_order' => $widgets[$swapIndex]['sort_order']]);
        $db->table('dashboard_widgets')->where('id', $widgets[$swapIndex]['id'])->update(['sort_order' => $widgets[$index]['sort_order']]);
        return redirect()->to('/dashboard');
    }
}
