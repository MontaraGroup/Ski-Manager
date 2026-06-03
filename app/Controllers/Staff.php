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

    private function getRoles(): array
    {
        $db = db_connect();
        $rows = $db->table('staff_roles')->orderBy('sort_order')->get()->getResultArray();
        $roles = [];
        foreach ($rows as $r) {
            $roles[$r['role_key']] = [
                'name' => $r['name'], 'icon' => $r['icon'], 'color' => $r['color'],
                'salary' => (int) $r['salary'], 'desc' => $r['description'],
            ];
        }
        return $roles;
    }

    public function index(): string
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");
        $staff = $this->staffModel->where('user_id', $userId)->where('status !=', 'fired')->findAll();
        $roles = $this->getRoles();
        $totalSalary = array_sum(array_column($staff, 'salary'));

        return view('staff/index', [
            'staff' => $staff,
            'roles' => $roles,
            'totalSalary' => $totalSalary,
        ]);
    }

    public function hire(): string
    {
        return view('staff/hire', ['roles' => $this->getRoles()]);
    }

    public function doHire()
    {
        $role = $this->request->getPost('role');
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");

        $roles = $this->getRoles();
        if (!isset($roles[$role])) {
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
            'salary' => $roles[$role]['salary'],
            'morale' => rand(70, 100),
            'experience' => 0,
            'status' => 'active',
        ]);

        return redirect()->to('/staff')->with('success', $name . ' hired as ' . str_replace('_', ' ', $role) . '!');
    }

    public function fire(int $id)
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");
        $member = $this->staffModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$member) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }

        $this->staffModel->update($id, ['status' => 'fired']);
        log_activity($userId, 'Staff', 'Fired ' . $member['name'], 'fa-solid fa-user-minus');

        return redirect()->to('/staff')->with('success', $member['name'] . ' has been let go.');
    }
}
