<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

class Demo extends BaseController
{
    public function start(): ResponseInterface
    {
        // If already logged in as a permanent player, take them straight to dashboard
        if (auth()->loggedIn() && !session()->get('is_demo')) {
            return redirect()->to('/dashboard');
        }

        // Resume existing active demo session if still valid
        if (session()->get('is_demo') && auth()->loggedIn()) {
            return redirect()->to('/dashboard')->with('info', 'Resumed your active Demo Mountain sandbox.');
        }

        $db = db_connect();
        $token = bin2hex(random_bytes(6));
        $demoUsername = 'Demo Director ' . strtoupper(substr($token, 0, 4));
        $demoEmail = 'guest_' . $token . '@demo.ski-manager.net';

        // 1. Create ephemeral guest user
        $db->table('users')->insert([
            'username'   => $demoUsername,
            'status'     => 'demo',
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $demoUserId = (int) $db->insertID();

        // 2. Insert auth identity for CodeIgniter Shield
        $db->table('auth_identities')->insert([
            'user_id'    => $demoUserId,
            'type'       => 'email_password',
            'name'       => $demoUsername,
            'secret'     => $demoEmail,
            'secret2'    => password_hash($token, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // 3. Seed pre-configured Park City mountain
        self::seedDemoMountain($demoUserId);

        // 4. Authenticate session via Shield
        auth()->loginById($demoUserId);
        session()->set('is_demo', true);
        session()->set('demo_token', $token);
        session()->set('demo_created_at', time());

        return redirect()->to('/dashboard')->with('success', '🏔️ Welcome to Demo Mountain! You have full operational control of Sector 1.');
    }

    public static function seedDemoMountain(int $userId): void
    {
        $db = db_connect();

        // 1. Player Finances with profile_completed = 1 so visitor lands directly on dashboard
        $db->table('player_finances')->insert([
            'user_id'           => $userId,
            'cash'              => 250000,
            'resort_open'       => 1,
            'units'             => 'metric',
            'daily_visitors'    => 480,
            'difficulty'        => 'standard',
            'allow_tours'       => 1,
            'resort_map'        => 'ParkCity',
            'profile_completed' => 1,
            'total_income'      => 0,
            'total_expenses'    => 0,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        // 2. Lifts & Slopes (dynamically match map segments if available)
        $liftSegments = $db->table('map_segments')
            ->where('resort_map', 'ParkCity')
            ->where('type', 'lift')
            ->where('active', 1)
            ->limit(4)
            ->get()->getResultArray();

        $defaultLifts = [
            ['name' => 'First Time Quad', 'length_meters' => 650, 'capacity' => 1400],
            ['name' => 'Payday Express', 'length_meters' => 1400, 'capacity' => 2400],
            ['name' => 'Bonanza High-Speed 6', 'length_meters' => 1850, 'capacity' => 3000],
            ['name' => 'Town Lift Triple', 'length_meters' => 1100, 'capacity' => 1200],
        ];

        for ($i = 0; $i < 4; $i++) {
            $seg = $liftSegments[$i] ?? null;
            $db->table('player_items')->insert([
                'user_id'       => $userId,
                'segment_id'    => $seg ? (int) $seg['id'] : 0,
                'item_type'     => 'lift',
                'subtype'       => 'chair_fixed',
                'name'          => $seg['name'] ?? $defaultLifts[$i]['name'],
                'level'         => 1,
                'length_meters' => $seg ? (int) $seg['length_meters'] : $defaultLifts[$i]['length_meters'],
                'capacity'      => $defaultLifts[$i]['capacity'],
                'condition_pct' => 100,
                'status'        => 'open',
                'sector'        => $seg ? (int) ($seg['sector'] ?? 0) : 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        $slopeSegments = $db->table('map_segments')
            ->where('resort_map', 'ParkCity')
            ->where('type', 'slope')
            ->where('active', 1)
            ->limit(8)
            ->get()->getResultArray();

        $defaultSlopes = [
            ['name' => 'First Time Cruiser', 'difficulty' => 'green', 'length_meters' => 750],
            ['name' => 'Payday Run', 'difficulty' => 'blue', 'length_meters' => 1450],
            ['name' => 'Crescent Ridge', 'difficulty' => 'blue', 'length_meters' => 1650],
            ['name' => 'Lost Prospector', 'difficulty' => 'green', 'length_meters' => 1100],
            ['name' => 'Silver King Chute', 'difficulty' => 'black', 'length_meters' => 950],
            ['name' => 'Thaynes Canyon', 'difficulty' => 'black', 'length_meters' => 1300],
            ['name' => '3 Kings Park', 'difficulty' => 'blue', 'length_meters' => 850],
            ['name' => "Quit'N Time", 'difficulty' => 'blue', 'length_meters' => 800],
        ];

        for ($i = 0; $i < 8; $i++) {
            $seg = $slopeSegments[$i] ?? null;
            $diff = $seg['difficulty'] ?? $defaultSlopes[$i]['difficulty'];
            $db->table('player_items')->insert([
                'user_id'       => $userId,
                'segment_id'    => $seg ? (int) $seg['id'] : 0,
                'item_type'     => 'slope',
                'subtype'       => $diff,
                'name'          => $seg['name'] ?? $defaultSlopes[$i]['name'],
                'level'         => 1,
                'length_meters' => $seg ? (int) $seg['length_meters'] : $defaultSlopes[$i]['length_meters'],
                'capacity'      => 0,
                'difficulty'    => $diff,
                'snow_quality'  => 'groomed',
                'condition_pct' => 100,
                'status'        => 'open',
                'sector'        => $seg ? (int) ($seg['sector'] ?? 0) : 0,
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        // 3. Buildings
        $buildings = [
            ['building_type' => 'restaurant', 'name' => 'Legacy Snow Lodge', 'level' => 1, 'capacity' => 150, 'revenue_per_day' => 850, 'upkeep_per_day' => 180],
            ['building_type' => 'rental', 'name' => 'Summit Sports Rental', 'level' => 1, 'capacity' => 200, 'revenue_per_day' => 620, 'upkeep_per_day' => 120],
            ['building_type' => 'hotel', 'name' => 'Base Camp Lodge', 'level' => 1, 'capacity' => 80, 'revenue_per_day' => 1200, 'upkeep_per_day' => 350],
        ];
        foreach ($buildings as $b) {
            $db->table('buildings')->insert(array_merge($b, [
                'user_id'       => $userId,
                'condition_pct' => 100,
                'status'        => 'open',
                'created_at'    => date('Y-m-d H:i:s'),
                'updated_at'    => date('Y-m-d H:i:s'),
            ]));
        }

        // 4. Equipment
        $db->table('equipment')->insert([
            'user_id'        => $userId,
            'equipment_type' => 'groomer',
            'model_key'      => 'pb600',
            'name'           => 'PistenBully 600',
            'brand'          => 'Kässbohrer',
            'capacity'       => 500,
            'fuel_cost'      => 45,
            'output_per_day' => 100,
            'energy_kwh'     => 120,
            'water_liters'   => 0,
            'condition_pct'  => 100,
            'status'         => 'active',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        $db->table('equipment')->insert([
            'user_id'        => $userId,
            'equipment_type' => 'snowmaker',
            'model_key'      => 'tf10',
            'name'           => 'TechnoAlpin TF10',
            'brand'          => 'TechnoAlpin',
            'capacity'       => 400,
            'fuel_cost'      => 30,
            'output_per_day' => 80,
            'energy_kwh'     => 95,
            'water_liters'   => 1200,
            'condition_pct'  => 100,
            'status'         => 'active',
            'created_at'     => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ]);

        // 5. Default Lift Tickets
        $tickets = [
            ['ticket_type' => 'half_day', 'price' => 45],
            ['ticket_type' => 'full_day', 'price' => 65],
            ['ticket_type' => 'two_day', 'price' => 110],
            ['ticket_type' => 'weekly', 'price' => 280],
            ['ticket_type' => 'season', 'price' => 850],
            ['ticket_type' => 'child', 'price' => 30],
            ['ticket_type' => 'senior', 'price' => 45],
            ['ticket_type' => 'group', 'price' => 50],
        ];
        foreach ($tickets as $t) {
            $db->table('lift_tickets')->insert([
                'user_id'     => $userId,
                'ticket_type' => $t['ticket_type'],
                'price'       => $t['price'],
                'active'      => 1,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        // 6. Staff
        $staffMembers = [
            ['name' => 'Lars Lindqvist', 'role' => 'ski_patrol', 'salary' => 150, 'level' => 2, 'morale' => 100, 'experience' => 40],
            ['name' => 'Marc Dubois', 'role' => 'groomer', 'salary' => 110, 'level' => 2, 'morale' => 100, 'experience' => 50],
            ['name' => 'Otto Huber', 'role' => 'mechanic', 'salary' => 130, 'level' => 3, 'morale' => 100, 'experience' => 70],
            ['name' => 'Elena Rossi', 'role' => 'instructor', 'salary' => 95, 'level' => 2, 'morale' => 100, 'experience' => 35],
        ];
        foreach ($staffMembers as $st) {
            $db->table('staff')->insert(array_merge($st, [
                'user_id'    => $userId,
                'status'     => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]));
        }

        // 7. Genepis & Daily Bonus
        $db->table('genepis')->insert(['user_id' => $userId, 'balance' => 10]);
        $db->table('daily_bonus')->insert(['user_id' => $userId, 'last_claim_day' => 0, 'streak' => 1]);
    }

    public function reset(): ResponseInterface
    {
        if (!session()->get('is_demo') || !auth()->loggedIn()) {
            return redirect()->to('/demo');
        }

        $userId = auth()->id();
        $db = db_connect();

        $tables = [
            'player_items', 'buildings', 'equipment', 'lift_tickets',
            'staff', 'player_finances', 'financial_transactions',
            'ticket_sales', 'genepis', 'daily_bonus'
        ];
        foreach ($tables as $t) {
            $db->table($t)->where('user_id', $userId)->delete();
        }

        self::seedDemoMountain($userId);
        return redirect()->to('/dashboard')->with('info', 'Demo Mountain reset to pristine factory starter setup.');
    }

    public function exit(): ResponseInterface
    {
        if (session()->get('is_demo')) {
            auth()->logout();
            session()->remove(['is_demo', 'demo_token', 'demo_created_at']);
        }
        return redirect()->to('/')->with('info', 'Exited Demo Mountain.');
    }

    public function claim(): ResponseInterface
    {
        if (!session()->get('is_demo') || !auth()->loggedIn()) {
            return redirect()->to('/register');
        }

        $userId = auth()->id();
        $db = db_connect();

        $rules = [
            'username'         => 'required|max_length[30]|min_length[3]|regex_match[\A[a-zA-Z0-9\s\-_]+\z]',
            'email'            => 'required|max_length[254]|valid_email',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = trim($this->request->getPost('username'));
        $email    = trim($this->request->getPost('email'));
        $password = $this->request->getPost('password');

        // Check if username is already taken by another user
        $existingUser = $db->table('users')
            ->where('username', $username)
            ->where('id !=', $userId)
            ->get()->getRow();
        if ($existingUser) {
            return redirect()->back()->withInput()->with('error', 'That username is already taken. Please choose another.');
        }

        // Check if email is already taken by another user
        $existingEmail = $db->table('auth_identities')
            ->where('secret', $email)
            ->where('user_id !=', $userId)
            ->get()->getRow();
        if ($existingEmail) {
            return redirect()->back()->withInput()->with('error', 'An account with that email already exists. Please log in or use a different email.');
        }

        // Promote the ephemeral user into a permanent account!
        $db->table('users')->where('id', $userId)->update([
            'username'   => $username,
            'status'     => 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $db->table('auth_identities')->where('user_id', $userId)->update([
            'name'       => $username,
            'secret'     => $email,
            'secret2'    => password_hash($password, PASSWORD_DEFAULT),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Remove demo tags from session
        session()->remove(['is_demo', 'demo_token', 'demo_created_at']);

        // Log celebration
        if (function_exists('log_activity')) {
            log_activity($userId, 'register', 'Claimed and saved Demo Mountain into permanent director account');
        }

        return redirect()->to('/dashboard')->with('success', '🎉 Welcome Director ' . esc($username) . '! Your resort, lifts, slopes, and treasury have been claimed and permanently saved.');
    }
}
