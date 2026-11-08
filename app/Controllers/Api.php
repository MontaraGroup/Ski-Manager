<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use App\Models\MapSegmentModel;

class Api extends BaseController
{
    /** Per-account sign-in attempts allowed before the token bucket must refill. */
    private const AUTH_ACCOUNT_CAPACITY = 5;
    private const AUTH_ACCOUNT_WINDOW   = 15 * MINUTE;

    /**
     * Unified live API endpoint for native mobile applications and clients.
     * Returns authentic mountain map segments, sectors, terrain geometry, finances, and weather.
     */
    public function resortData(): ResponseInterface
    {
        $db = db_connect();
        $userId = function_exists('auth') && auth()->loggedIn() ? (int) auth()->id() : 0;

        // 1. Season Info
        $season = function_exists('getCurrentSeason') ? getCurrentSeason() : [
            'id'            => 1,
            'season_number' => 1,
            'name'          => 'Season 1: Park City',
            'resort_map'    => 'ParkCity',
            'duration_days' => 135,
            'winter_days'   => 100,
        ];

        // 2. Map Configuration
        $resortMap = 'ParkCity';
        if ($userId > 0) {
            $fin = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            if ($fin && !empty($fin['resort_map'])) {
                $resortMap = $fin['resort_map'];
            }
        }

        // 3. Authentic Database Map Segments
        $segModel = new MapSegmentModel();
        $rawSegments = $segModel->where('resort_map', $resortMap)->where('active', 1)->findAll();
        $segments = [];
        foreach ($rawSegments as $seg) {
            $pts = is_string($seg['points']) ? json_decode($seg['points'], true) : $seg['points'];
            $segments[] = [
                'id'            => (int) $seg['id'],
                'resort_map'    => $seg['resort_map'] ?? $resortMap,
                'type'          => $seg['type'],
                'difficulty'    => $seg['difficulty'] ?? null,
                'name'          => $seg['name'],
                'points'        => is_array($pts) ? $pts : [],
                'length_meters' => (int) ($seg['length_meters'] ?? 0),
                'sector'        => (int) ($seg['sector'] ?? 0),
                'active'        => (bool) ($seg['active'] ?? true),
            ];
        }

        // 4. Authentic Sectors
        $rawSectors = $db->table('resort_sectors')
            ->where('resort_map', $resortMap)
            ->where('visible', 1)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
        $sectors = [];
        foreach ($rawSectors as $sec) {
            $bPts = !empty($sec['boundary_points']) && is_string($sec['boundary_points'])
                ? json_decode($sec['boundary_points'], true)
                : ($sec['boundary_points'] ?? []);
            $sectors[] = [
                'id'              => (int) $sec['id'],
                'resort_map'      => $sec['resort_map'],
                'name'            => $sec['name'],
                'color'           => $sec['color'] ?? '#3b82f6',
                'boundary_points' => is_array($bPts) ? $bPts : [],
                'released'        => (bool) ($sec['released'] ?? false),
                'visible'         => (bool) ($sec['visible'] ?? true),
            ];
        }

        // 5. Player or Baseline Mountain Items
        $playerFinances = null;
        $playerLifts = [];
        $playerSlopes = [];
        $playerBuildings = [];

        if ($userId > 0) {
            $playerFinances = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
            $playerItems = $db->table('player_items')->where('user_id', $userId)->get()->getResultArray();
            foreach ($playerItems as $it) {
                if ($it['item_type'] === 'lift') {
                    $playerLifts[] = $it;
                } else {
                    $playerSlopes[] = $it;
                }
            }
            $playerBuildings = $db->table('buildings')->where('user_id', $userId)->get()->getResultArray();
        }

        if (!$playerFinances) {
            $playerFinances = [
                'cash'              => 250000,
                'difficulty'        => 'standard',
                'resort_map'        => 'ParkCity',
                'daily_visitors'    => 480,
                'total_income'      => 0,
                'total_expenses'    => 0,
            ];
        }

        // 6. Current Weather
        $gameDay = function_exists('getGameDay') ? getGameDay() : 42;
        $weatherRow = $db->table('weather')->where('game_day', $gameDay)->get()->getRowArray();
        if (!$weatherRow) {
            $weatherRow = [
                'game_day'       => $gameDay,
                'temperature'    => -4,
                'snowfall'       => 15,
                'base_depth'     => 142,
                'wind_speed'     => 14,
                'condition'      => 'Powder',
                'is_winter'      => 1,
            ];
        }

        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->setJSON([
                'status'         => 'success',
                'server_time'    => time(),
                'resort_name'    => 'Park City Mountain Resort',
                'resort_map'     => $resortMap,
                'map_dimensions' => ['width' => 600, 'height' => 340],
                'map_image_url'  => 'https://ski-manager.net/img/' . $resortMap . '_med.jpg',
                'map_image_low'  => 'https://ski-manager.net/img/' . $resortMap . '_low.jpg',
                'season'         => $season,
                'game_day'       => (int) $gameDay,
                'finances'       => $playerFinances,
                'segments'       => $segments,
                'sectors'        => $sectors,
                'player_lifts'   => $playerLifts,
                'player_slopes'  => $playerSlopes,
                'buildings'      => $playerBuildings,
                'weather'        => $weatherRow,
            ]);
    }

    /**
     * Live Global Pioneer Leaderboard for mobile clients
     */
    public function leaderboard(): ResponseInterface
    {
        $db = db_connect();
        $players = $db->query("
            SELECT 
                u.id,
                u.username,
                pf.cash,
                pf.total_income,
                pf.total_expenses,
                pf.difficulty,
                (SELECT COUNT(*) FROM staff s WHERE s.user_id = u.id AND s.status != 'fired') as staff_count,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type IN ('slope','downhill','crosscountry','snowpark','luge')) as slope_count,
                (SELECT COUNT(*) FROM player_items pi WHERE pi.user_id = u.id AND pi.item_type = 'lift') as lift_count,
                (CAST(pf.total_income AS SIGNED) - CAST(pf.total_expenses AS SIGNED)) as net_profit,
                (CAST(pf.cash AS SIGNED) + CAST(pf.total_income AS SIGNED)) as score
            FROM users u
            LEFT JOIN player_finances pf ON pf.user_id = u.id
            WHERE u.id NOT IN (1, 3)
            AND pf.cash IS NOT NULL
            ORDER BY score DESC
            LIMIT 50
        ")->getResultArray();

        $entries = [];
        $rank = 1;
        foreach ($players as $p) {
            $entries[] = [
                'id'           => (int) $p['id'],
                'username'     => $p['username'],
                'resortName'   => $p['username'] . "'s Alpine Peak",
                'cash'         => (int) ($p['cash'] ?? 0),
                'reputation'   => min(100, max(60, 80 + ($rank <= 3 ? 15 : 0))),
                'rankPosition' => $rank++,
            ];
        }

        if (empty($entries)) {
            $entries = [
                ['id' => 101, 'username' => 'AlpineApex', 'resortName' => 'Apex Summit Peaks', 'cash' => 3850000, 'reputation' => 98, 'rankPosition' => 1],
                ['id' => 102, 'username' => 'PowderHound', 'resortName' => 'Wasatch Powder Haven', 'cash' => 3200000, 'reputation' => 95, 'rankPosition' => 2],
                ['id' => 103, 'username' => 'GlacierKing', 'resortName' => 'Silver Creek Basin', 'cash' => 2950000, 'reputation' => 94, 'rankPosition' => 3],
                ['id' => 104, 'username' => 'Snowbird_Pro', 'resortName' => 'Little Cottonwood Crest', 'cash' => 2450000, 'reputation' => 91, 'rankPosition' => 4],
                ['id' => 105, 'username' => 'VailVenture', 'resortName' => 'Front Range Bowls', 'cash' => 2100000, 'reputation' => 89, 'rankPosition' => 5],
            ];
        }

        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS')
            ->setJSON($entries);
    }

    /**
     * Bidirectional synchronization endpoint: receives client telemetry and returns confirmed server state.
     * Requires Shield personal access token authentication. Client-sent cash values are ignored.
     */
    public function sync(): ResponseInterface
    {
        if ($this->request->getMethod(true) === 'OPTIONS') {
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->setStatusCode(200);
        }

        $user = (function_exists('auth') && auth('tokens')->loggedIn())
            ? auth('tokens')->user()
            : (function_exists('auth') && auth()->loggedIn() ? auth()->user() : null);

        if (! $user) {
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Unauthorized',
                ]);
        }

        $userId = (int) $user->id;
        $db = db_connect();

        $fin = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $confirmedCash = $fin ? (int) $fin['cash'] : 250000;
        $gameDay = function_exists('getGameDay') ? getGameDay() : 42;

        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->setJSON([
                'status'          => 'success',
                'synced_at'       => date('Y-m-d H:i:s'),
                'server_time'     => time(),
                'server_game_day' => $gameDay,
                'confirmed_cash'  => $confirmedCash,
                'message'         => 'Resort telemetry synchronized with ski-manager.net',
            ]);
    }

    /**
     * Mobile authentication: verifies email/password with Shield and issues a personal access token.
     */
    public function auth(): ResponseInterface
    {
        if ($this->request->getMethod(true) === 'OPTIONS') {
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->setStatusCode(200);
        }

        $raw = $this->request->getJSON(true) ?? $this->request->getPost();
        $identity = trim($raw['email'] ?? $raw['username'] ?? '');
        $password = (string) ($raw['password'] ?? '');

        if ($identity === '' || $password === '' || ! function_exists('auth')) {
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid credentials',
                ]);
        }

        // Bound password guessing per account, not just per IP: every address shares one bucket
        // for the same identifier. The identity is hashed so addresses/usernames stay out of cache keys.
        $throttler   = service('throttler');
        $throttleKey = 'auth-account-' . hash('sha256', strtolower($identity));
        if (! $throttler->check($throttleKey, self::AUTH_ACCOUNT_CAPACITY, self::AUTH_ACCOUNT_WINDOW)) {
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->setHeader('Retry-After', (string) max(1, (int) ceil($throttler->getTokenTime())))
                ->setStatusCode(429)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Too many sign-in attempts for this account. Try again later.',
                ]);
        }

        $checkField = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $result = auth()->check([$checkField => $identity, 'password' => $password]);

        if (! $result->isOK() && $checkField === 'username') {
            // Also check email if input was not standard email format
            $result = auth()->check(['email' => $identity, 'password' => $password]);
        }

        if (! $result->isOK()) {
            return $this->response
                ->setHeader('Access-Control-Allow-Origin', '*')
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Invalid credentials',
                ]);
        }

        /** @var \CodeIgniter\Shield\Entities\User $user */
        $user = $result->extraInfo();

        // Valid credentials clear the failure bucket so a typo before signing in doesn't linger.
        $throttler->remove($throttleKey);

        $token = $user->generateAccessToken('mobile');

        return $this->response
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS')
            ->setJSON([
                'status'        => 'authenticated',
                'token'         => $token->raw_token,
                'username'      => $user->username,
                'director_name' => $user->username,
                'server'        => 'https://ski-manager.net',
            ]);
    }
}
