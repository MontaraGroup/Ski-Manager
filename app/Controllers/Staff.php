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

        $assigned = count(array_filter($staff, fn($s) => !empty($s['assigned_to'])));
        $unassigned = count($staff) - $assigned;

        return view('staff/index', [
            'staff' => $staff,
            'roles' => $roles,
            'totalSalary' => $totalSalary,
            'assigned' => $assigned,
            'unassigned' => $unassigned,
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

        $firstNames = ['Alex', 'Marie', 'Pierre', 'Sophie', 'Lucas', 'Emma', 'Hugo', 'Lea', 'Thomas', 'Camille', 'Max', 'Clara', 'Jules', 'Alice', 'Liam', 'Chloe'];
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

        return redirect()->to('/staff')->with('success', $name . ' hired as ' . $roles[$role]['name'] . '!');
    }

    public function fire(int $id)
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");
        $member = $this->staffModel->where('id', $id)->where('user_id', $userId)->first();

        if (!$member) {
            return redirect()->back()->with('error', 'Staff member not found.');
        }

        $this->staffModel->update($id, ['status' => 'fired', 'assigned_to' => null]);
        log_activity($userId, 'Staff', 'Fired ' . $member['name'], 'fa-solid fa-user-minus');

        return redirect()->to('/staff')->with('success', $member['name'] . ' has been let go.');
    }

    public function autoAssign()
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");

        $db = db_connect();
        $staff = $this->staffModel->where('user_id', $userId)->where('status', 'active')->findAll();

        // Clear all current assignments
        $this->staffModel->where('user_id', $userId)->set('assigned_to', null)->update();

        $counts = ['assigned' => 0, 'unassigned' => 0];

        // Get assignable targets for each role
        $targets = $this->getAssignmentTargets($userId, $db);

        // Group staff by role
        $staffByRole = [];
        foreach ($staff as $s) {
            $staffByRole[$s['role']][] = $s;
        }

        foreach ($staffByRole as $role => $members) {
            $available = $targets[$role] ?? [];
            foreach ($members as $i => $member) {
                if (isset($available[$i])) {
                    $this->staffModel->update($member['id'], ['assigned_to' => $available[$i]]);
                    $counts['assigned']++;
                } else {
                    $counts['unassigned']++;
                }
            }
        }

        log_activity($userId, 'Staff', 'Auto-assigned ' . $counts['assigned'] . ' staff members', 'fa-solid fa-wand-magic-sparkles');
        return redirect()->to('/staff')->with('success', $counts['assigned'] . ' staff assigned, ' . $counts['unassigned'] . ' unassigned (no available posts).');
    }

    public function clearAssignments()
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");

        $this->staffModel->where('user_id', $userId)->set('assigned_to', null)->update();
        return redirect()->to('/staff')->with('success', 'All assignments cleared.');
    }

    private function getAssignmentTargets(int $userId, $db): array
    {
        $targets = [];

        // Groomer operators → groomer equipment
        $groomers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->get()->getResultArray();
        $targets['groomer'] = array_map(fn($e) => $e['name'], $groomers);

        // Snowmakers → snowmaker equipment
        $snowmakers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'snowmaker')->get()->getResultArray();
        $targets['snowmaker'] = array_map(fn($e) => $e['name'], $snowmakers);

        // Mechanics → lifts
        $lifts = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'lift')->get()->getResultArray();
        $targets['mechanic'] = array_map(fn($l) => $l['name'] ?? 'Lift #' . $l['id'], $lifts);

        // Ski patrol → slopes
        $slopes = $db->table('player_items')->where('user_id', $userId)->where('item_type', 'slope')->get()->getResultArray();
        $targets['ski_patrol'] = array_map(fn($s) => $s['name'] ?? 'Slope #' . $s['id'], $slopes);

        // Instructors → slopes (ski school on slopes)
        $targets['instructor'] = array_map(fn($s) => 'Ski School - ' . ($s['name'] ?? 'Slope #' . $s['id']), $slopes);

        // Chefs → restaurants
        $restaurants = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'restaurant')->get()->getResultArray();
        $targets['chef'] = array_map(fn($b) => $b['name'], $restaurants);

        // Receptionists → hotels
        $hotels = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'hotel')->get()->getResultArray();
        $targets['receptionist'] = array_map(fn($b) => $b['name'], $hotels);

        // Medics → ski patrol stations
        $medStations = $db->table('buildings')->where('user_id', $userId)->where('building_type', 'ski_patrol')->get()->getResultArray();
        $targets['medic'] = array_map(fn($b) => $b['name'], $medStations);

        // Park crew → terrain parks
        $parks = $db->table('terrain_parks')->where('user_id', $userId)->get()->getResultArray();
        $targets['park_crew'] = array_map(fn($p) => $p['name'], $parks);

        // Managers → resort-wide (always assignable)
        $targets['manager'] = ['Resort Operations', 'Guest Relations', 'Financial Oversight', 'Safety Coordination', 'Staff Management'];

        return $targets;
    }
}
