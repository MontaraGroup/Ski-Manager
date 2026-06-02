<?php

namespace App\Controllers;

use App\Models\BuildingModel;

class Buildings extends BaseController
{
    protected BuildingModel $model;
    protected array $buildingDefs;

    public function __construct()
    {
        $this->model = new BuildingModel();
        $this->buildingDefs = [
            'hotel' => [
                'label' => 'Hotels',
                'singular' => 'Hotel',
                'icon' => 'fa-solid fa-hotel',
                'color' => 'text-primary',
                'desc' => 'Provide accommodation for overnight guests',
                'route' => 'hotels',
                'levels' => [
                    1 => ['name' => 'Budget Lodge', 'capacity' => 50, 'revenue' => 2500, 'upkeep' => 500, 'cost' => 50000],
                    2 => ['name' => 'Comfort Hotel', 'capacity' => 120, 'revenue' => 6000, 'upkeep' => 1200, 'cost' => 150000],
                    3 => ['name' => 'Luxury Resort Hotel', 'capacity' => 250, 'revenue' => 15000, 'upkeep' => 3000, 'cost' => 400000],
                ],
            ],
            'restaurant' => [
                'label' => 'Restaurants',
                'singular' => 'Restaurant',
                'icon' => 'fa-solid fa-utensils',
                'color' => 'text-warning',
                'desc' => 'Feed visitors and generate dining revenue',
                'route' => 'restaurants',
                'levels' => [
                    1 => ['name' => 'Snack Bar', 'capacity' => 40, 'revenue' => 1500, 'upkeep' => 400, 'cost' => 25000],
                    2 => ['name' => 'Mountain Restaurant', 'capacity' => 100, 'revenue' => 4000, 'upkeep' => 900, 'cost' => 80000],
                    3 => ['name' => 'Gourmet Restaurant', 'capacity' => 60, 'revenue' => 8000, 'upkeep' => 2000, 'cost' => 200000],
                ],
            ],
            'rental' => [
                'label' => 'Ski Rentals',
                'singular' => 'Rental Shop',
                'icon' => 'fa-solid fa-bag-shopping',
                'color' => 'text-info',
                'desc' => 'Rent ski and snowboard equipment to visitors',
                'route' => 'rentals',
                'levels' => [
                    1 => ['name' => 'Basic Rental Counter', 'capacity' => 30, 'revenue' => 1000, 'upkeep' => 300, 'cost' => 15000],
                    2 => ['name' => 'Full Service Rental', 'capacity' => 80, 'revenue' => 3000, 'upkeep' => 700, 'cost' => 50000],
                    3 => ['name' => 'Premium Rental Center', 'capacity' => 150, 'revenue' => 6000, 'upkeep' => 1500, 'cost' => 120000],
                ],
            ],
            'real_estate' => [
                'label' => 'Real Estate',
                'singular' => 'Property',
                'icon' => 'fa-solid fa-city',
                'color' => 'text-success',
                'desc' => 'Build housing and commercial properties for passive income',
                'route' => 'real-estate',
                'levels' => [
                    1 => ['name' => 'Small Chalet', 'capacity' => 10, 'revenue' => 800, 'upkeep' => 100, 'cost' => 30000],
                    2 => ['name' => 'Apartment Block', 'capacity' => 40, 'revenue' => 3500, 'upkeep' => 500, 'cost' => 120000],
                    3 => ['name' => 'Luxury Villa', 'capacity' => 8, 'revenue' => 5000, 'upkeep' => 800, 'cost' => 250000],
                ],
            ],
            'transportation' => [
                'label' => 'Transportation',
                'singular' => 'Transport',
                'icon' => 'fa-solid fa-bus',
                'color' => 'text-accent',
                'desc' => 'Get visitors to and from your resort',
                'route' => 'transportation',
                'levels' => [
                    1 => ['name' => 'Shuttle Bus', 'capacity' => 40, 'revenue' => 800, 'upkeep' => 300, 'cost' => 20000],
                    2 => ['name' => 'Express Coach', 'capacity' => 80, 'revenue' => 2000, 'upkeep' => 700, 'cost' => 60000],
                    3 => ['name' => 'Aerial Tram Link', 'capacity' => 200, 'revenue' => 5000, 'upkeep' => 1500, 'cost' => 200000],
                ],
            ],
            'retail' => [
                'label' => 'Retail Stores',
                'singular' => 'Store',
                'icon' => 'fa-solid fa-store',
                'color' => 'text-secondary',
                'desc' => 'Sell merchandise and souvenirs to visitors',
                'route' => 'retail',
                'levels' => [
                    1 => ['name' => 'Souvenir Kiosk', 'capacity' => 20, 'revenue' => 600, 'upkeep' => 150, 'cost' => 10000],
                    2 => ['name' => 'Ski Shop', 'capacity' => 50, 'revenue' => 2000, 'upkeep' => 400, 'cost' => 40000],
                    3 => ['name' => 'Mountain Boutique', 'capacity' => 30, 'revenue' => 4000, 'upkeep' => 800, 'cost' => 100000],
                ],
            ],
            'off_season' => [
                'label' => 'Off-Season Activities',
                'singular' => 'Activity',
                'icon' => 'fa-solid fa-sun',
                'color' => 'text-warning',
                'desc' => 'Summer activities to generate year-round revenue',
                'route' => 'off-season',
                'levels' => [
                    1 => ['name' => 'Hiking Trail', 'capacity' => 60, 'revenue' => 500, 'upkeep' => 100, 'cost' => 8000],
                    2 => ['name' => 'Mountain Bike Park', 'capacity' => 40, 'revenue' => 2500, 'upkeep' => 500, 'cost' => 45000],
                    3 => ['name' => 'Alpine Coaster', 'capacity' => 100, 'revenue' => 6000, 'upkeep' => 1000, 'cost' => 150000],
                ],
            ],
            'ski_patrol' => [
                'label' => 'Ski Patrol Stations',
                'singular' => 'Patrol Station',
                'icon' => 'fa-solid fa-shield-halved',
                'color' => 'text-error',
                'desc' => 'Keep slopes safe and respond to emergencies',
                'route' => 'ski-patrol',
                'levels' => [
                    1 => ['name' => 'First Aid Post', 'capacity' => 1, 'revenue' => 0, 'upkeep' => 600, 'cost' => 20000],
                    2 => ['name' => 'Patrol Station', 'capacity' => 3, 'revenue' => 0, 'upkeep' => 1500, 'cost' => 60000],
                    3 => ['name' => 'Mountain Rescue Center', 'capacity' => 6, 'revenue' => 0, 'upkeep' => 3000, 'cost' => 150000],
                ],
            ],
        ];
    }

    public function show(string $type): string
    {
        if (!isset($this->buildingDefs[$type])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $userId = auth()->id();
        $def = $this->buildingDefs[$type];
        $buildings = $this->model->where('user_id', $userId)->where('building_type', $type)->findAll();

        $openBuildings = array_filter($buildings, fn($b) => $b['status'] === 'open');
        $totalCapacity = array_sum(array_column($openBuildings, 'capacity'));
        $totalRevenue = array_sum(array_column($openBuildings, 'revenue_per_day'));
        $totalUpkeep = array_sum(array_column($openBuildings, 'upkeep_per_day'));

        return view('buildings/index', [
            'type' => $type,
            'def' => $def,
            'buildings' => $buildings,
            'totalCapacity' => $totalCapacity,
            'totalRevenue' => $totalRevenue,
            'totalUpkeep' => $totalUpkeep,
        ]);
    }

    public function build()
    {
        $userId = auth()->id();
        $type = $this->request->getPost('type');
        $level = (int) $this->request->getPost('level');

        if (!isset($this->buildingDefs[$type]) || !isset($this->buildingDefs[$type]['levels'][$level])) {
            return redirect()->back()->with('error', 'Invalid building.');
        }

        $def = $this->buildingDefs[$type];
        $lvl = $def['levels'][$level];
        $count = $this->model->where('user_id', $userId)->where('building_type', $type)->countAllResults();

        $this->model->insert([
            'user_id' => $userId,
            'building_type' => $type,
            'name' => $lvl['name'] . ' #' . ($count + 1),
            'level' => $level,
            'capacity' => $lvl['capacity'],
            'revenue_per_day' => $lvl['revenue'],
            'upkeep_per_day' => $lvl['upkeep'],
            'condition_pct' => 100,
            'status' => 'open',
        ]);

        return redirect()->to('/' . $def['route'])->with('success', $lvl['name'] . ' built!');
    }

    public function toggle(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $new = $building['status'] === 'open' ? 'closed' : 'open';
        $this->model->update($id, ['status' => $new]);

        $def = $this->buildingDefs[$building['building_type']] ?? null;
        $route = $def ? $def['route'] : 'dashboard';
        return redirect()->to('/' . $route)->with('success', $building['name'] . ' ' . $new . '.');
    }

    public function upgrade(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $type = $building['building_type'];
        $nextLevel = (int) $building['level'] + 1;

        if (!isset($this->buildingDefs[$type]['levels'][$nextLevel])) {
            return redirect()->back()->with('error', 'Already max level.');
        }

        $next = $this->buildingDefs[$type]['levels'][$nextLevel];
        $this->model->update($id, [
            'level' => $nextLevel,
            'capacity' => $next['capacity'],
            'revenue_per_day' => $next['revenue'],
            'upkeep_per_day' => $next['upkeep'],
            'name' => $next['name'] . ' #' . substr($building['name'], -2),
        ]);
        log_activity($userId, 'Building', 'Upgraded ' . $building['name'] . ' to Lv.' . $nextLevel, 'fa-solid fa-arrow-up');

        $def = $this->buildingDefs[$type];
        return redirect()->to('/' . $def['route'])->with('success', 'Upgraded to ' . $next['name'] . '!');
    }

    public function sell(int $id)
    {
        $userId = auth()->id();
        $building = $this->model->where('id', $id)->where('user_id', $userId)->first();
        if (!$building) return redirect()->back()->with('error', 'Building not found.');

        $def = $this->buildingDefs[$building['building_type']] ?? null;
        $route = $def ? $def['route'] : 'dashboard';

        $this->model->delete($id);
        log_activity($userId, 'Building', 'Sold ' . $building['name'], 'fa-solid fa-money-bill-wave');
        return redirect()->to('/' . $route)->with('success', $building['name'] . ' sold.');
    }
}
