<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        // Agent discovery (RFC 8288 Link headers, RFC 9727 section 3)
        $this->response->setHeader('Link', implode(', ', [
            '</.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"',
            '</openapi.json>; rel="service-desc"; type="application/vnd.oai.openapi+json"',
            '</developers>; rel="service-doc"; type="text/html"',
            '</sitemap.xml>; rel="describedby"; type="text/xml"',
        ]));

        $raw = $this->getHomepageStats();

        $weather = $raw['weather'] ?? null;
        $snowfall = $weather ? (int) ($weather['snowfall'] ?? 0) : 0;
        $weatherFormatted = null;

        if ($weather) {
            $weatherFormatted = [
                'condition' => $weather['condition'] ?? '',
                'conditionName' => $weather['condition_name'] ?? ($weather['condition'] ?? 'Clear'),
                'temp' => isset($weather['temp']) ? temp((int) $weather['temp']) : '–',
                'snowBase' => isset($weather['snow_base']) ? snow((int) $weather['snow_base']) : '–',
                'snowfallText' => $snowfall > 0 ? (snow($snowfall) . ' new snow') : 'No new snow',
                'windText' => isset($weather['wind']) ? speed((int) $weather['wind']) : '–',
            ];
        }

        $topPlayer = null;
        if (!empty($raw['topPlayer'])) {
            $topPlayer = [
                'username' => $raw['topPlayer']['username'] ?? 'Player',
                'cashText' => currency((int) ($raw['topPlayer']['cash'] ?? 0)),
            ];
        }

        $standings = [];
        if (!empty($raw['standings'])) {
            foreach ($raw['standings'] as $idx => $s) {
                $standings[] = [
                    'rank' => $idx + 1,
                    'username' => $s['username'] ?? '',
                    'resortName' => $s['resort_name'] ?? '',
                    'totalLifts' => (int) ($s['total_lifts'] ?? 0),
                    'totalSlopes' => (int) ($s['total_slopes'] ?? 0),
                    'cashText' => currency((int) ($s['cash'] ?? 0)),
                ];
            }
        }

        $recent = [];
        if (!empty($raw['recent'])) {
            foreach ($raw['recent'] as $r) {
                $recent[] = [
                    'username' => $r['username'] ?? '',
                    'name' => $r['name'] ?? '',
                    'itemType' => $r['item_type'] ?? 'slope',
                    'difficulty' => $r['difficulty'] ?? 'blue',
                    'timeAgoText' => function_exists('timeAgo') ? timeAgo($r['created_at']) : '',
                ];
            }
        }

        $page = [
            'isLoggedIn' => (bool) (function_exists('auth') && auth()->loggedIn()),
            'playerCount' => (int) $raw['playerCount'],
            'playerCountFormatted' => number_format((int) $raw['playerCount']),
            'totalSlopes' => (int) $raw['totalSlopes'],
            'totalSlopesFormatted' => number_format((int) $raw['totalSlopes']),
            'topPlayer' => $topPlayer,
            'weather' => $weatherFormatted,
            'season' => [
                'current' => (int) $raw['currentSeasonNum'],
                'day' => (int) $raw['seasonDay'],
                'length' => (int) $raw['seasonLen'],
                'daysRemaining' => (int) $raw['daysRemaining'],
                'startDateFormatted' => date('F j, Y', $raw['seasonStart']),
            ],
            's2' => [
                'live' => (bool) $raw['s2Live'],
                'justOpened' => (bool) $raw['s2JustOpened'],
                'runs' => (int) $raw['s2Runs'],
                'lifts' => (int) $raw['s2Lifts'],
                'steep' => (int) $raw['s2Steep'],
            ],
            'standings' => $standings,
            'recent' => $recent,
        ];

        return view('home', ['page' => $page]);
    }

    /**
     * Gathers public statistics and season metrics for the landing page.
     */
    protected function getHomepageStats(): array
    {
        $db = db_connect();

        $playerCount = $db->table('users')->where('id !=', 1)->countAllResults(false);
        $totalSlopes = $db->table('player_items')->where('item_type', 'slope')->countAllResults(false);
        $topPlayer = $db->query(
            "SELECT u.username, pf.cash FROM player_finances pf JOIN users u ON u.id = pf.user_id WHERE u.id != 1 ORDER BY pf.cash DESC LIMIT 1"
        )->getRowArray();
        $weather = getCurrentWeather();

        $currentSeasonNum = (int) getSeasonNumber();
        $seasonDay = (int) getSeasonDay();
        $seasonLen = (int) getSeasonLength();
        $daysRemaining = max(0, $seasonLen - $seasonDay);
        $seasonStart = strtotime(getSeasonStartDate());

        // Season 2 note: only once Sector 2 is actually released, with counts from the real map
        $s2 = $db->table('resort_sectors')->where('resort_map', 'ParkCity')->where('name', 'Sector 2')->get()->getRowArray();
        $s2Live = $currentSeasonNum >= 2 && $s2 && (int) $s2['released'] === 1;
        $s2Runs = 0;
        $s2Lifts = 0;
        $s2Steep = 0;
        $standings = [];

        if ($s2Live) {
            $segments = $db->table('map_segments')
                ->select('type, difficulty, COUNT(*) AS n')
                ->where('sector', $s2['id'])
                ->where('active', 1)
                ->groupBy('type, difficulty')
                ->get()
                ->getResultArray();

            foreach ($segments as $r) {
                if ($r['type'] === 'lift') {
                    $s2Lifts += (int) $r['n'];
                    continue;
                }
                $s2Runs += (int) $r['n'];
                if (in_array($r['difficulty'], ['black', 'double_black'], true)) {
                    $s2Steep += (int) $r['n'];
                }
            }

            if ($db->tableExists('season_leaderboard_archives')) {
                // Demo accounts were ranked by the Season 1 rollover; keep them off the public standings
                $standings = $db->table('season_leaderboard_archives a')
                    ->select('a.username, a.resort_name, a.cash, a.total_lifts, a.total_slopes')
                    ->join('users u', 'u.id = a.user_id', 'left')
                    ->where('a.season_number', 1)
                    ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'demo')->groupEnd()
                    ->orderBy('a.rank_position', 'ASC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();
            }
        }

        $s2JustOpened = $s2Live && $currentSeasonNum === 2 && $seasonDay <= 14;

        // What people built lately (real players only)
        $recent = $db->table('player_items pi')
            ->select('pi.name, pi.item_type, pi.difficulty, pi.created_at, u.username')
            ->join('users u', 'u.id = pi.user_id')
            ->where('u.id !=', 1)
            ->groupStart()->where('u.status IS NULL')->orWhere('u.status !=', 'demo')->groupEnd()
            ->orderBy('pi.id', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();

        return [
            'playerCount'     => $playerCount,
            'totalSlopes'     => $totalSlopes,
            'topPlayer'       => $topPlayer,
            'weather'         => $weather,
            'currentSeasonNum'=> $currentSeasonNum,
            'seasonDay'       => $seasonDay,
            'seasonLen'       => $seasonLen,
            'daysRemaining'   => $daysRemaining,
            'seasonStart'     => $seasonStart,
            's2Live'          => $s2Live,
            's2Runs'          => $s2Runs,
            's2Lifts'         => $s2Lifts,
            's2Steep'         => $s2Steep,
            's2JustOpened'    => $s2JustOpened,
            'standings'       => $standings,
            'recent'          => $recent,
        ];
    }

    public function maintenance(): string
    {
        $info = function_exists('getMaintenanceInfo') ? getMaintenanceInfo() : [
            'enabled'           => true,
            'until'             => '2026-10-25 18:00:00 UTC',
            'message'           => 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for Season 2.',
            'remaining_seconds' => 345600,
        ];
        return view('maintenance', ['maintenance' => $info]);
    }
}
