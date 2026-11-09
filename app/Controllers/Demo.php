<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\AccountPurge;
use App\Libraries\Economy;
use App\Libraries\Utilities;
use App\Libraries\Weather;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\HTTP\ResponseInterface;

class Demo extends BaseController
{
    /** Index of "Set Your Ticket Prices" in Tutorial::STEPS */
    private const TUTORIAL_START_STEP = 3;

    /** New demos per IP per hour */
    private const STARTS_PER_HOUR = 5;

    /** The page behind every /demo link: safe for crawlers and prefetch, since only its button creates anything */
    public function landing()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }
        $this->response->setHeader('X-Robots-Tag', 'noindex');
        return view('demo/start');
    }

    /** Old links and prefetch: change nothing */
    public function resetGet(): ResponseInterface
    {
        return redirect()->to(auth()->loggedIn() ? '/dashboard' : '/demo');
    }

    public function exitGet(): ResponseInterface
    {
        return redirect()->to(auth()->loggedIn() ? '/dashboard' : '/');
    }

    public function start(): ResponseInterface
    {
        // If already logged in as a permanent player, take them straight to dashboard
        if (auth()->loggedIn() && !session()->get('is_demo')) {
            return redirect()->to('/dashboard');
        }

        // Resume existing active demo session if still valid
        if (session()->get('is_demo') && auth()->loggedIn()) {
            return redirect()->to('/dashboard');
        }

        // A half-finished sign-in in this browser (an account still waiting for its activation code,
        // say) makes Shield refuse any new login, which crashed here after the guest was created.
        // Starting a demo replaces it; the account itself is untouched and can be logged into later.
        $authenticator = auth('session')->getAuthenticator();
        if ($authenticator->isPending()) {
            $authenticator->logout();
        }

        if (!service('throttler')->check('demo-start-' . md5($this->request->getIPAddress()), self::STARTS_PER_HOUR, HOUR)) {
            return redirect()->to('/demo')->with('error', 'Too many demo resorts from your network. Try again in an hour, or create a free account.');
        }

        $db = db_connect();
        $token = bin2hex(random_bytes(6));
        $demoUsername = 'Demo Director ' . strtoupper(substr($token, 0, 4));
        $demoEmail = 'guest_' . $token . '@demo.ski-manager.net';

        // Create the guest and its mountain together, so a failure can't leave a half-made account
        try {
            $db->transStart();
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
            $db->transComplete();
            if (!$db->transStatus()) throw new \RuntimeException('transaction failed');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Demo: could not create the demo mountain: ' . $e->getMessage());
            return redirect()->to('/')->with('error', 'The demo mountain could not be set up right now. Please try again in a moment.');
        }

        self::bumpStat('started');

        // 4. Authenticate session via Shield
        auth()->loginById($demoUserId);
        session()->set('is_demo', true);
        session()->set('demo_token', $token);
        session()->set('demo_created_at', time());

        return redirect()->to('/dashboard')->with('success', 'Welcome to Demo Mountain! You have full operational control of Sector 1.');
    }

    /** Counts survive cleanup, unlike the accounts. Never gets in the way of the player */
    private static function bumpStat(string $column): void
    {
        try {
            $today = date('Y-m-d');
            db_connect()->query("INSERT INTO demo_stats (day, {$column}) VALUES (?, 1) ON DUPLICATE KEY UPDATE {$column} = {$column} + 1", [$today]);
        } catch (\Throwable $e) {
            log_message('error', 'Demo: could not count ' . $column . ': ' . $e->getMessage());
        }
    }

    /** Started, claimed and claim rate for /admin/economy */
    public static function stats(int $days = 30): array
    {
        $db = db_connect();
        $row = $db->query('SELECT COALESCE(SUM(started), 0) AS started, COALESCE(SUM(claimed), 0) AS claimed FROM demo_stats WHERE day >= ?', [date('Y-m-d', strtotime("-{$days} days"))])->getRow();
        $started = (int) $row->started;
        $claimed = (int) $row->claimed;
        return [
            'days' => $days,
            'started' => $started,
            'claimed' => $claimed,
            'rate' => $started ? round($claimed * 100 / $started, 1) . '%' : 'n/a',
            'active' => $db->table('users')->where('status', 'demo')->countAllResults(),
        ];
    }

    /** What the player would keep, in real numbers. Null unless this is a demo and demo_v2 is on */
    public static function summary(int $userId): ?array
    {
        if (!featureEnabled('demo_v2') || !self::isDemoUser()) return null;
        $db = db_connect();
        $count = fn(string $type) => $db->table('player_items')->where('user_id', $userId)->where('item_type', $type)->countAllResults();
        return [
            'cash' => currency((int) ($db->table('player_finances')->where('user_id', $userId)->get()->getRow('cash') ?? 0)),
            'lifts' => $count('lift'),
            'runs' => $count('slope'),
            'buildings' => $db->table('buildings')->where('user_id', $userId)->countAllResults(),
        ];
    }

    /** For the demo bar: the summary, and the save prompts this player has earned so far, newest event first */
    public static function barState(int $userId): ?array
    {
        $summary = self::summary($userId);
        if ($summary === null) return null;

        $db = db_connect();
        $prompts = [];

        // Everything seeded shares one moment; anything over a minute newer, the player built
        foreach (['player_items', 'buildings'] as $table) {
            $range = $db->table($table)->selectMin('created_at', 'first')->where('user_id', $userId)->get()->getRow();
            if (!$range || !$range->first) continue;
            $built = $db->table($table)->where('user_id', $userId)->where('created_at >', date('Y-m-d H:i:s', strtotime($range->first) + 60))
                ->orderBy('id', 'DESC')->limit(1)->get()->getRow();
            if ($built) {
                $prompts[] = ['key' => $userId . ':built', 'title' => 'You built ' . $built->name . '.',
                    'body' => "Save your resort so it isn't deleted after 7 days without play."];
                break;
            }
        }

        // The first night's ledger rows, so the number stays true on later days too
        $firstDay = fn(string $type) => $db->table('financial_transactions')->where('user_id', $userId)->where('type', $type)->like('category', 'Daily', 'after')->orderBy('id', 'ASC')->limit(1)->get()->getRow('amount');
        $income = $firstDay('income');
        $expense = $firstDay('expense');
        if ($income !== null && (int) $income - (int) $expense > 0) {
            $prompts[] = ['key' => $userId . ':first_profit', 'title' => 'Your resort made ' . currency((int) $income - (int) $expense) . ' on its first day.',
                'body' => 'Save it to keep this resort, its cash and its staff.'];
        }

        return $summary + ['prompts' => $prompts];
    }

    /** Editor placeholder names ("Lift Line 6") aren't for players */
    private static function displayName(?string $segmentName, string $default): string
    {
        return $segmentName === null || preg_match('/^(Lift Line|Slope Path) \\d+$/', $segmentName) ? $default : $segmentName;
    }

    /**
     * A resort that works as one: the shortest lifts of the first released sector with segments, and
     * runs that start at a lift's top and finish at a lift's base, in a mix of difficulties.
     * Null if the map hasn't got enough, so the seed falls back to the first segments it finds.
     */
    private static function starterSegments(): ?array
    {
        $db = db_connect();
        $released = array_column($db->table('resort_sectors')->select('id')->where('resort_map', 'ParkCity')->where('released', 1)->orderBy('id')->get()->getResultArray(), 'id');
        $segments = fn(string $type, int $sector) => $db->table('map_segments')
            ->where(['resort_map' => 'ParkCity', 'type' => $type, 'active' => 1, 'sector' => $sector])->get()->getResultArray();

        foreach ($released as $sector) {
            $lifts = $segments('lift', (int) $sector);
            if (count($lifts) < 4) continue;
            usort($lifts, fn($a, $b) => $a['length_meters'] <=> $b['length_meters']);
            $lifts = array_slice($lifts, 0, 4);

            // Lift paths run base to top, runs top to bottom
            $ends = fn(array $seg) => [json_decode($seg['points'], true)[0], array_slice(json_decode($seg['points'], true), -1)[0]];
            $gap = fn(array $p, array $q) => hypot($p[0] - $q[0], $p[1] - $q[1]);
            $runs = [];
            foreach ($segments('downhill', (int) $sector) as $run) {
                [$start, $finish] = $ends($run);
                $toTop = min(array_map(fn($l) => $gap($start, $ends($l)[1]), $lifts));
                $toBase = min(array_map(fn($l) => $gap($finish, $ends($l)[0]), $lifts));
                $runs[] = $run + ['fit' => $toTop + $toBase];
            }
            usort($runs, fn($a, $b) => $a['fit'] <=> $b['fit']);

            // Best-fitting run of each difficulty, in the order of the seed's default names, which match them
            $picked = [];
            foreach (['green', 'blue', 'blue', 'green', 'black', 'black', 'blue', 'blue'] as $difficulty) {
                foreach ($runs as $i => $run) {
                    if ($run['difficulty'] === $difficulty) {
                        $picked[] = $run;
                        unset($runs[$i]);
                        break;
                    }
                }
            }
            if (count($picked) === 8) return ['lifts' => $lifts, 'slopes' => $picked];
        }
        return null;
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
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);

        // 2. Lifts & Slopes (dynamically match map segments if available)
        $starter = featureEnabled('demo_v2') ? self::starterSegments() : null;
        $liftSegments = $starter['lifts'] ?? $db->table('map_segments')
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
                'name'          => self::displayName($seg['name'] ?? null, $defaultLifts[$i]['name']),
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

        $slopeSegments = $starter['slopes'] ?? $db->table('map_segments')
            ->where('resort_map', 'ParkCity')
            ->where('type', 'downhill')
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
                'name'          => self::displayName($seg['name'] ?? null, $defaultSlopes[$i]['name']),
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

        // 4. Equipment: a groomer and a snow gun from the shop catalog, switched on
        foreach (['pb600', 'ta_tr9'] as $modelKey) {
            $m = $db->table('equipment_catalog')->where('model_key', $modelKey)->get()->getRowArray();
            if (!$m) continue;
            $db->table('equipment')->insert([
                'user_id'        => $userId,
                'equipment_type' => $m['equipment_type'],
                'model_key'      => $m['model_key'],
                'name'           => $m['name'] . ' #1',
                'brand'          => $m['brand'],
                'capacity'       => $m['capacity'],
                'fuel_cost'      => $m['fuel_cost'],
                'output_per_day' => $m['output_per_day'],
                'energy_kwh'     => $m['energy_kwh'],
                'water_liters'   => $m['water_liters'],
                'condition_pct'  => 100,
                'status'         => 'active',
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ]);
        }

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

        // 8. The mountain is already built, so the tutorial starts at the first thing left to do: ticket prices
        if (featureEnabled('demo_v2')) {
            $db->table('tutorial_progress')->insert(['user_id' => $userId, 'current_step' => self::TUTORIAL_START_STEP]);
        }
    }

    /**
     * What tonight's tick should pay this player, for the dashboard until their first real day. Uses the
     * tick's own Economy formulas with today's weather; boosts and events are left out, so it's an estimate.
     * New real accounts get it too: without it, nothing on the dashboard says the game settles once a day.
     * Null once the player has had a tick, or while their resort is closed.
     */
    public static function estimate(int $userId): ?array
    {
        static $cache = [];
        if (array_key_exists($userId, $cache)) return $cache[$userId];
        if (!featureEnabled('demo_v2') || !featureEnabled('economy_v2')) return null;

        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        if (!$finance || !empty($finance['last_tick']) || (int) ($finance['resort_open'] ?? 1) !== 1) return null;

        $config = array_column($db->table('difficulty_config')->where('difficulty', $finance['difficulty'] ?? 'standard')->get()->getResultArray(), 'config_value', 'config_key');
        $revMult = ((int) ($config['revenue_multiplier'] ?? 100)) / 100;
        $costMult = ((int) ($config['cost_multiplier'] ?? 100)) / 100;
        $visMult = ((int) ($config['visitor_multiplier'] ?? 100)) / 100;

        $items = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
        $open = array_filter($items, fn($i) => $i['status'] === 'open');
        $weather = getCurrentWeather();
        $isWinter = getSeasonDay() <= getWinterDays();

        $demand = Economy::slopeDemand(array_values(array_filter($open, fn($i) => $i['item_type'] === 'slope')))
            * $visMult * Weather::demandFactor((string) ($weather['condition_name'] ?? ''), (int) ($weather['wind'] ?? 0));
        $demand = Economy::marketCap($demand);
        $terms = Economy::ticketTerms($db->table('lift_tickets')->where('user_id', $userId)->get()->getResultArray());
        $tourists = $isWinter ? 0 : (int) round($demand * Economy::SUMMER_TOURIST_SHARE);
        $liftCapacity = Economy::liftDailyCapacity(array_values(array_filter($open, fn($i) => $i['item_type'] === 'lift')));
        $skiers = min((int) round($demand * $terms['demand'] * ($isWinter ? 1 : 0.15)), $liftCapacity);

        $buildings = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        $util = Economy::buildingUtilization($buildings, $skiers, $tourists);
        $income = $skiers * $terms['per_visit'] * $revMult;
        $costs = Economy::runningCost($items) * $costMult;
        foreach ($buildings as $b) {
            $income += (int) $b['revenue_per_day'] * ($util[$b['building_type']] ?? 0) * $revMult;
            $costs += (int) $b['upkeep_per_day'] * $costMult;
        }
        $costs += (int) ($db->table('staff')->selectSum('salary')->where('user_id', $userId)->where('status', 'active')->get()->getRow('salary') ?? 0) * $costMult;

        // Equipment fuel, and the energy and water a demo has no sources for, so it imports them
        $equipment = $db->table('equipment')->where('user_id', $userId)->where('status', 'active')->get()->getResultArray();
        $costs += array_sum(array_column($equipment, 'fuel_cost')) * $costMult;
        $snowmakers = array_values(array_filter($equipment, fn($e) => (int) $e['energy_kwh'] > 0));
        $openLifts = array_values(array_filter($open, fn($i) => $i['item_type'] === 'lift'));
        $utilities = Utilities::importCost('energy', Utilities::energyDemand($snowmakers, 0, $openLifts, $buildings)['total'])
            + Utilities::importCost('water', Utilities::waterDemand($snowmakers));
        $costs += $utilities;

        $ticketIncome = (int) round($skiers * $terms['per_visit'] * $revMult);
        $running = (int) round(Economy::runningCost($items) * $costMult);
        return $cache[$userId] = [
            'skiers' => number_format($skiers),
            'income' => currency((int) round($income)),
            'costs'  => currency((int) round($costs)),
            'visitors' => $skiers,
            // Same keys as player_finances.last_tick, so the dashboard's cards can use it until the first tick.
            // The cards have no line for utilities, so they go with running costs and the totals match the panel.
            'tick' => ['tickets' => $ticketIncome, 'buildings' => (int) round($income) - $ticketIncome, 'lift_slope_running' => $running + $utilities],
        ];
    }

    /**
     * The signed-in player is on a demo mountain: their session says so, or (if the session was lost)
     * their account is still a demo one.
     */
    public static function isDemoUser(): bool
    {
        if (!auth()->loggedIn()) return false;
        if (session()->get('is_demo')) return true;
        return (string) (auth()->user()->status ?? '') === 'demo';
    }

    /** /demo/save: the form that turns the demo into a permanent account (/register sends signed-in players away) */
    public function save()
    {
        if (!self::isDemoUser()) return redirect()->to(auth()->loggedIn() ? '/dashboard' : '/register');
        session()->set('is_demo', true);
        return view('register');
    }

    public function reset(): ResponseInterface
    {
        if (!session()->get('is_demo') || !auth()->loggedIn()) {
            return redirect()->to('/demo');
        }

        $userId = (int) auth()->id();
        $db = db_connect();

        // This wipes the account, so the session flag alone isn't enough: the row must still be a demo
        $isDemo = $db->table('users')->where('id', $userId)->where('status', 'demo')->countAllResults() === 1;
        if (!$isDemo) {
            session()->remove(['is_demo', 'demo_token', 'demo_created_at']);
            return redirect()->to('/dashboard');
        }

        // Everything the player has, except what signs them in
        $tables = array_filter(AccountPurge::tables(), fn($t) => !str_starts_with($t, 'auth_'));
        try {
            $db->transStart();
            foreach ($tables as $t) {
                $db->table($t)->where('user_id', $userId)->delete();
            }
            $db->table('tournaments')->where('host_id', $userId)->delete();
            $db->table('resort_likes')->where('resort_user_id', $userId)->delete();
            self::seedDemoMountain($userId);
            $db->transComplete();
            if (!$db->transStatus()) throw new \RuntimeException('transaction failed');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Demo: could not reset the demo mountain: ' . $e->getMessage());
            return redirect()->to('/dashboard')->with('error', 'The demo mountain could not be reset right now. Please try again.');
        }
        return redirect()->to('/dashboard')->with('info', 'Your demo resort is back to its starting setup.');
    }

    public function exit(): ResponseInterface
    {
        if (session()->get('is_demo')) {
            auth()->logout();
            session()->remove(['is_demo', 'demo_token', 'demo_created_at']);
        }
        return redirect()->to('/')->with('info', 'You left the demo.');
    }

    public function claim(): ResponseInterface
    {
        if (!self::isDemoUser()) {
            return redirect()->to('/register');
        }

        $userId = auth()->id();
        $db = db_connect();

        $rules = [
            'username'         => 'required|max_length[30]|min_length[3]|regex_match[\A[a-zA-Z0-9\s\-_]+\z]',
            'email'            => 'required|max_length[254]|valid_email',
            'password'         => 'required|max_byte[72]|strong_password[]',
            'password_confirm' => 'required|matches[password]',
            'terms'            => 'required',
        ];
        $messages = ['terms' => ['required' => 'Please accept the Terms of Service and Privacy Policy.']];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = trim($this->request->getPost('username'));
        $email    = strtolower(trim($this->request->getPost('email')));
        $password = $this->request->getPost('password');

        // Generated guest addresses are what the cleanup looks for
        if (str_ends_with($email, '@demo.ski-manager.net')) {
            return redirect()->back()->withInput()->with('error', 'Please use your own email address.');
        }

        $db->transBegin();

        // Lock demo user record to prevent concurrent cleanup purge race
        $lockedUser = $db->query("SELECT id FROM users WHERE id = ? AND status = 'demo' FOR UPDATE", [$userId])->getRow();
        if (!$lockedUser) {
            $db->transRollback();
            if (function_exists('auth')) {
                auth()->logout();
            }
            session()->remove(['is_demo', 'demo_token', 'demo_created_at']);
            return redirect()->to('/register')->with('error', 'Your demo session has expired or been removed. Please register a new account.');
        }

        // Check if username is already taken by another user
        $existingUser = $db->table('users')
            ->where('username', $username)
            ->where('id !=', $userId)
            ->get()->getRow();
        if ($existingUser) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'That username is already taken. Please choose another.');
        }

        // Check if email is already taken by another user
        $existingEmail = $db->table('auth_identities')
            ->where('secret', $email)
            ->where('user_id !=', $userId)
            ->get()->getRow();
        if ($existingEmail) {
            $db->transRollback();
            return redirect()->back()->withInput()->with('error', 'An account with that email already exists. Please log in or use a different email.');
        }

        // Promote the ephemeral user into a permanent account. It stays inactive until the email is
        // confirmed, same as any sign-up, so nobody can hold an address they don't own without a code.
        try {
            $db->table('users')->where('id', $userId)->update([
                'username'   => $username,
                'status'     => null,
                'active'     => 0,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->table('auth_identities')->where('user_id', $userId)->where('type', 'email_password')->update([
                'name'       => $username,
                'secret'     => $email,
                'secret2'    => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $db->transCommit();
        } catch (\Throwable $e) {
            // Someone took the name or email between the check and the update (unique index)
            $db->transRollback();
            log_message('error', 'Demo: claim failed for #' . $userId . ': ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'That username or email was just taken. Please choose another.');
        }

        self::bumpStat('claimed');
        session()->remove(['is_demo', 'demo_token', 'demo_created_at']);
        if (featureEnabled('demo_v2')) session()->set('demo_saved_as', $username);
        log_activity($userId, 'register', 'Claimed and saved Demo Mountain into permanent director account');

        // The email code page; the resort is already theirs if they come back to it later
        auth('session')->getAuthenticator()->startUpAction('register', model(UserModel::class)->findById($userId));
        return redirect()->to('auth/a/show');
    }
}
