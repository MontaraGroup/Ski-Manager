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

    public function index()
    {
        $userId = auth()->id();
        // Temporary Emergency Seeder
        $db = db_connect();
        if (!$db->table('staff_roles')->where('role_key', 'ski_patrol')->get()->getRow()) {
            $db->table('staff_roles')->insert([
                'role_key' => 'ski_patrol',
                'name' => 'Ski Patrol',
                'description' => 'Responds to incidents, monitors trails, and maintains mountain safety.',
                'salary' => 150,
                'icon' => 'fa-solid fa-person-skiing',
                'color' => 'text-error',
                'sort_order' => 1
            ]);
        }

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

    /** What each role does in the nightly tick (GameTick), shown on the hire page */
    public const EFFECTS = [
        'ski_patrol' => 'Assigned to a slope: +2% skiers each, and fewer accidents (each costs 2,500–8,000 €).',
        'instructor' => 'Assigned to a slope: +3% skiers each, and up to 10 ski lessons a day at 50 € (about 8% of your skiers want one).',
        'mechanic' => 'Assigned to a lift: that lift wears 1% a day less, so it needs repairs less often. One mechanic per lift.',
        'groomer' => 'Drives one groomer each night, restoring your worst slopes; higher levels restore more.',
        'snowmaker' => 'Assigned to a snow gun: that gun wears 1% a day less.',
        'receptionist' => 'Assigned to a hotel: hotels earn up to 15% more with one receptionist per hotel.',
        'chef' => 'Assigned to a restaurant: restaurants earn up to 15% more with one chef per restaurant.',
        'medic' => 'Assigned to a patrol station: the biggest single cut in accidents.',
        'manager' => 'Assigned: +5% skiers each, and better odds of VIP guests.',
        'park_crew' => 'Assigned to a terrain park: parks wear 0.4% a day less each (up to 1.5%).',
    ];

    public function hire(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $roles = $this->getRoles();
        $costMult = ((int) getDifficultyConfig('cost_multiplier', '100')) / 100;
        $staff = $this->staffModel->where('user_id', $userId)->where('status', 'active')->findAll();
        $targets = $this->getAssignmentTargets($userId, $db);
        $groomers = $db->table('equipment')->where('user_id', $userId)->where('equipment_type', 'groomer')->countAllResults();
        $lifts = count($targets['mechanic']);

        foreach ($roles as $key => &$r) {
            $mine = array_filter($staff, fn($s) => $s['role'] === $key);
            $assigned = count(array_filter($mine, fn($s) => !empty($s['assigned_to'])));
            $posts = count($targets[$key] ?? []);
            $r += ['effect' => self::EFFECTS[$key] ?? $r['desc'], 'daily' => (int) round($r['salary'] * $costMult),
                'have' => count($mine), 'assigned' => $assigned, 'idle' => count($mine) - $assigned, 'posts' => $posts, 'free' => max(0, $posts - $assigned)];
            [$r['verdict'], $r['why']] = match (true) {
                $key === 'groomer' => $groomers === 0 ? ['none', 'Buy a groomer first.'] : (count($mine) < $groomers
                    ? ['needed', $groomers === 1 ? 'Your groomer has no driver.' : ($groomers - count($mine)) . ' of your ' . $groomers . ' groomers ' . ($groomers - count($mine) === 1 ? 'has' : 'have') . ' no driver.']
                    : ['enough', 'Every groomer has a driver.']),
                $key === 'mechanic' && $lifts > 0 && $r['idle'] === 0 && $assigned < $lifts => ['needed', $lifts === 1 ? 'Your lift has no mechanic.' : ($lifts - $assigned) . ' of your ' . $lifts . ' lifts ' . ($lifts - $assigned === 1 ? 'has' : 'have') . ' no mechanic.'],
                in_array($key, ['receptionist', 'chef'], true) => $posts === 0 ? ['none', 'Build a ' . ($key === 'chef' ? 'restaurant' : 'hotel') . ' first.']
                    : ($assigned < $posts ? ['needed', ($posts - $assigned) . ' ' . ($key === 'chef' ? 'restaurant' : 'hotel') . ($posts - $assigned === 1 ? ' has' : 's have') . ' none.'] : ['enough', 'Every ' . ($key === 'chef' ? 'restaurant' : 'hotel') . ' has one.']),
                $key === 'park_crew' && $posts > 0 && $assigned >= 4 => ['enough', 'Parks can\'t wear any slower.'],
                $r['idle'] > 0 => ['enough', 'You already have ' . $r['idle'] . ' with nothing to do. Assign them first.'],
                $posts === 0 => ['none', match ($key) { 'ski_patrol', 'instructor' => 'Build a slope first.', 'mechanic' => 'Build a lift first.', 'snowmaker' => 'Buy a snow gun first.', 'medic' => 'Build a ski patrol station first.', 'park_crew' => 'Build a terrain park first.', default => 'No post for this role yet.' }],
                $assigned >= $posts => ['enough', 'Every post is filled.'],
                default => ['useful', $r['free'] . ' free post' . ($r['free'] === 1 ? '' : 's') . '.'],
            };
        }
        unset($r);

        $candidates = \App\Libraries\Candidates::today($userId, $roles);
        foreach ($candidates as &$list) {
            foreach ($list as &$c) $c['daily'] = (int) round((int) $c['salary'] * $costMult);
        }
        unset($list, $c);

        return view('staff/hire', [
            'roles' => $roles,
            'candidates' => $candidates,
            'cash' => (int) ($db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow()->cash ?? 0),
            'payroll' => (int) round(array_sum(array_column($staff, 'salary')) * $costMult),
            'staffCount' => count($staff),
        ]);
    }

    public function doHire()
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to("/login")->with("error", "Please log in first.");
        $roles = $this->getRoles();
        $db = db_connect();
        $c = $db->table('staff_candidates')->where('id', (int) $this->request->getPost('candidate'))->where('user_id', $userId)
            ->where('game_day', getGameDay())->where('hired', 0)->get()->getRowArray();
        if (!$c || !isset($roles[$c['role']])) {
            return redirect()->to('/staff/hire')->with('error', 'That candidate is no longer available. Today\'s candidates are listed below.');
        }
        $role = $c['role'];

        // Start the new hire on the first free post for the role, so they work (and keep their morale) from tonight
        $taken = array_flip(array_column($this->staffModel->select('assigned_to')->where('user_id', $userId)->where('role', $role)
            ->where('status', 'active')->where('assigned_to IS NOT NULL')->findAll(), 'assigned_to'));
        $post = null;
        foreach ($this->getAssignmentTargets($userId, $db)[$role] ?? [] as $t) {
            if (!isset($taken[$t])) { $post = $t; break; }
        }

        $db->transStart();
        // Taking the candidate off the list is what reserves them, so a double click can't hire them twice
        $db->table('staff_candidates')->where('id', $c['id'])->where('hired', 0)->update(['hired' => 1]);
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/staff/hire')->with('error', $c['name'] . ' has already been hired.');
        }
        $this->staffModel->insert([
            'user_id' => $userId, 'name' => $c['name'], 'role' => $role, 'level' => (int) $c['level'],
            'salary' => (int) $c['salary'], 'morale' => (int) $c['morale'], 'experience' => 0,
            'assigned_to' => $post, 'status' => 'active',
        ]);
        $db->transComplete();
        log_activity($userId, 'Staff', 'Hired ' . $c['name'] . ' as ' . $roles[$role]['name'] . ($post ? ' (' . $post . ')' : ''), 'fa-solid fa-user-plus');

        $where = $post ? ' and assigned to ' . $post . '.' : '. There is no free post for them yet, so they\'ll lose morale until you give them one.';
        return redirect()->to('/staff/hire')->with('success', $c['name'] . ' hired as ' . $roles[$role]['name'] . $where);
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

    public const TRAIN_COST_PER_LEVEL = 5000;

    /**
     * Highest level worth training to. Only groomer operators use their level (Grooming::restore:
     * RESTORE_BASE + RESTORE_PER_LEVEL x average level, capped at RESTORE_MAX), so training other
     * roles cost money for nothing.
     */
    public static function maxUsefulLevel(string $role): int
    {
        if ($role !== 'groomer') return 1;
        return (int) ceil((\App\Libraries\Grooming::RESTORE_MAX - \App\Libraries\Grooming::RESTORE_BASE) / \App\Libraries\Grooming::RESTORE_PER_LEVEL);
    }

    public static function trainCost(array $member): int
    {
        return self::TRAIN_COST_PER_LEVEL * (int) $member['level'];
    }

    public function train(int $id)
    {
        $userId = auth()->id();
        $member = $this->staffModel->where('id', $id)->where('user_id', $userId)->where('status', 'active')->first();
        if (!$member) return redirect()->back()->with('error', 'Staff not found.');
        if ((int) $member['level'] >= self::maxUsefulLevel($member['role'])) {
            return redirect()->to('/staff')->with('error', $member['role'] === 'groomer'
                ? esc($member['name']) . ' is already at the level where training stops helping.'
                : 'Training has no effect for this role: only groomer operators get better with level.');
        }

        $cost = self::trainCost($member);
        $db = db_connect();
        $db->transStart();
        $db->table('player_finances')->where('user_id', $userId)->where('cash >=', $cost)
            ->set('cash', 'cash - ' . $cost, false)->set('total_expenses', 'total_expenses + ' . $cost, false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/staff')->with('error', 'Not enough cash: training costs ' . currency($cost) . '.');
        }
        // Only raise the level we charged for, so a double submit can't pay twice for one level
        $db->table('staff')->where('id', $id)->where('level', (int) $member['level'])->set('level', 'level + 1', false)->update();
        if ($db->affectedRows() !== 1) {
            $db->transRollback();
            return redirect()->to('/staff')->with('error', esc($member['name']) . ' was already trained.');
        }
        $db->table('financial_transactions')->insert([
            'user_id' => $userId, 'game_day' => getGameDay(), 'category' => 'Staff', 'description' => 'Trained ' . $member['name'] . ' to level ' . ((int) $member['level'] + 1),
            'amount' => $cost, 'type' => 'expense', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->transComplete();

        log_activity($userId, 'Staff', $member['name'] . ' trained to Lv.' . ((int) $member['level'] + 1) . ' for ' . currency($cost), 'fa-solid fa-graduation-cap');
        return redirect()->to('/staff')->with('success', esc($member['name']) . ' trained to Lv.' . ((int) $member['level'] + 1) . ' for ' . currency($cost) . '.');
    }
}
