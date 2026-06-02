<?php

namespace App\Controllers;

use App\Models\StaffModel;

class Staff extends BaseController
{
    protected StaffModel $staffModel;

    public function __construct()
    {
        $this->staffModel = new StaffModel();
    }

    public function index(): string
    {
        $userId = auth()->id();
        $staff = $this->staffModel->where('user_id', $userId)->where('status !=', 'fired')->findAll();

        $roles = [
            'ski_patrol' => ['name' => 'Ski Patrol', 'icon' => 'fa-solid fa-shield-halved', 'color' => 'text-error'],
            'instructor' => ['name' => 'Instructor', 'icon' => 'fa-solid fa-chalkboard-user', 'color' => 'text-info'],
            'mechanic' => ['name' => 'Lift Mechanic', 'icon' => 'fa-solid fa-wrench', 'color' => 'text-warning'],
            'groomer' => ['name' => 'Groomer Operator', 'icon' => 'fa-solid fa-tractor', 'color' => 'text-success'],
            'receptionist' => ['name' => 'Receptionist', 'icon' => 'fa-solid fa-bell-concierge', 'color' => 'text-primary'],
            'chef' => ['name' => 'Chef', 'icon' => 'fa-solid fa-utensils', 'color' => 'text-warning'],
            'medic' => ['name' => 'Medic', 'icon' => 'fa-solid fa-kit-medical', 'color' => 'text-error'],
            'manager' => ['name' => 'Resort Manager', 'icon' => 'fa-solid fa-user-tie', 'color' => 'text-primary'],
            'snowmaker' => ['name' => 'Snowmaker', 'icon' => 'fa-solid fa-snowflake', 'color' => 'text-info'],
            'park_crew' => ['name' => 'Park Crew', 'icon' => 'fa-solid fa-person-snowboarding', 'color' => 'text-secondary', 'salary' => 1600, 'desc' => 'Maintains terrain park features and builds jumps'],
        ];

        $totalSalary = array_sum(array_column($staff, 'salary'));

        return view('staff/index', [
            'staff' => $staff,
            'roles' => $roles,
            'totalSalary' => $totalSalary,
        ]);
    }

    public function hire(): string
    {
        $roles = [
            'ski_patrol' => ['name' => 'Ski Patrol', 'icon' => 'fa-solid fa-shield-halved', 'color' => 'text-error', 'salary' => 1500, 'desc' => 'Ensures slope safety and responds to accidents'],
            'instructor' => ['name' => 'Instructor', 'icon' => 'fa-solid fa-chalkboard-user', 'color' => 'text-info', 'salary' => 2000, 'desc' => 'Teaches skiing to guests, increases satisfaction'],
            'mechanic' => ['name' => 'Lift Mechanic', 'icon' => 'fa-solid fa-wrench', 'color' => 'text-warning', 'salary' => 1800, 'desc' => 'Maintains and repairs lifts'],
            'groomer' => ['name' => 'Groomer Operator', 'icon' => 'fa-solid fa-tractor', 'color' => 'text-success', 'salary' => 1600, 'desc' => 'Operates snow groomers to maintain slope conditions'],
            'receptionist' => ['name' => 'Receptionist', 'icon' => 'fa-solid fa-bell-concierge', 'color' => 'text-primary', 'salary' => 1200, 'desc' => 'Handles check-ins and guest services'],
            'chef' => ['name' => 'Chef', 'icon' => 'fa-solid fa-utensils', 'color' => 'text-warning', 'salary' => 2200, 'desc' => 'Prepares food at resort restaurants'],
            'medic' => ['name' => 'Medic', 'icon' => 'fa-solid fa-kit-medical', 'color' => 'text-error', 'salary' => 2500, 'desc' => 'Provides medical care at resort clinic'],
            'manager' => ['name' => 'Resort Manager', 'icon' => 'fa-solid fa-user-tie', 'color' => 'text-primary', 'salary' => 3500, 'desc' => 'Boosts overall resort efficiency by 5%'],
            'snowmaker' => ['name' => 'Snowmaker', 'icon' => 'fa-solid fa-snowflake', 'color' => 'text-info', 'salary' => 1700, 'desc' => 'Operates snow cannons to produce artificial snow'],
            'park_crew' => ['name' => 'Park Crew', 'icon' => 'fa-solid fa-person-snowboarding', 'color' => 'text-secondary', 'salary' => 1600, 'desc' => 'Maintains terrain park features and builds jumps'],
        ];

        return view('staff/hire', ['roles' => $roles]);
    }

    public function doHire()
    {
        $role = $this->request->getPost('role');
        $userId = auth()->id();

        $salaries = [
            'ski_patrol' => 1500, 'instructor' => 2000, 'mechanic' => 1800,
            'groomer' => 1600, 'receptionist' => 1200, 'chef' => 2200,
            'medic' => 2500, 'manager' => 3500, 'snowmaker' => 1700,
        ];

        if (!isset($salaries[$role])) {
            return redirect()->back()->with('error', 'Invalid role.');
        }

        $firstNames = ['Alex', 'Marie', 'Pierre', 'Sophie', 'Lucas', 'Emma', 'Hugo', 'Léa', 'Thomas', 'Camille', 'Max', 'Clara', 'Jules', 'Alice', 'Liam', 'Chloé'];
        $lastNames = ['Dupont', 'Martin', 'Bernard', 'Dubois', 'Moreau', 'Laurent', 'Simon', 'Michel', 'Lefebvre', 'Roux', 'Blanc', 'Fournier', 'Morel', 'Girard'];

        $name = $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];

        $this->staffModel->insert([
            'user_id' => $userId,
            'name' => $name,
            'role' => $role,
            'level' => 1,
            'salary' => $salaries[$role],
            'morale' => rand(70, 100),
            'experience' => 0,
            'status' => 'active',
        ]);

        return redirect()->to('/staff')->with('success', $name . ' hired as ' . str_replace('_', ' ', $role) . '!');
    }

    public function fire(int $id)
    {
        $userId = auth()->id();
        $member = $this->staffModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$member) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }

        $this->staffModel->update($id, ['status' => 'fired']);
        log_activity($userId, 'Staff', 'Fired ' . $member['name'], 'fa-solid fa-user-minus');

        return redirect()->to('/staff')->with('success', $member['name'] . ' has been let go.');
    }
}
