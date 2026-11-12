<?php

namespace App\Controllers;

class Admin extends BaseController
{
    private function checkAdmin(): bool
    {
        if (!function_exists('auth') || !auth()->loggedIn()) return false;
        $user = auth()->user();
        return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
    }

    /**
     * users.active = 0 means two things: Shield sets it until the player confirms their email, and
     * banUser() sets it too. A pending email_activate identity tells them apart; deleted accounts have
     * no users row at all (AccountPurge and deleteUser remove it), so they never reach these lists.
     */
    private const UNCONFIRMED_SQL = "EXISTS (SELECT 1 FROM auth_identities ai WHERE ai.user_id = users.id AND ai.type = 'email_activate')";

    /** 'active', 'banned' or 'unconfirmed' for one users row (needs the pending-activation ids) */
    private static function accountState(array $user, array $unconfirmedIds): string
    {
        if (!isset($user['active']) || (int) $user['active'] === 1) return 'active';
        return in_array((int) $user['id'], $unconfirmedIds, true) ? 'unconfirmed' : 'banned';
    }

    /** Ids among $ids that still have a pending email confirmation */
    private static function unconfirmedIds(array $ids): array
    {
        if ($ids === []) return [];
        return array_map('intval', array_column(db_connect()->table('auth_identities')->select('user_id')->distinct()
            ->where('type', 'email_activate')->whereIn('user_id', $ids)->get()->getResultArray(), 'user_id'));
    }

    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');

        $db = db_connect();
        $totalUsers = $db->table('users')->countAllResults();
        $totalStaff = $db->table('staff')->where('status !=', 'fired')->countAllResults();
        $totalBuildings = $db->table('buildings')->countAllResults();
        $totalItems = $db->table('player_items')->countAllResults();
        $totalLoans = $db->table('loans')->where('status', 'active')->countAllResults();
        $totalCashRow = $db->table('player_finances')->selectSum('cash')->get()->getRowArray();
        $totalCash = $totalCashRow['cash'] ?? 0;
        $totalParking = $db->table('parking')->countAllResults();
        $totalParks = $db->table('terrain_parks')->countAllResults();

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $weather = getCurrentWeather();
        // The simulated day only advances when the tick runs; match the navbar rather than the calendar
        if (!empty($weather['game_day'])) $gameDay = (int) $weather['game_day'];
        $recentLogs = $db->query("SELECT al.*, u.username FROM activity_log al JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 30")->getResultArray();

        $users = $db->table('users')->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        if (!empty($users)) {
            $userIds = array_column($users, 'id');

            // 1. Batch fetch finances
            $finRows = $db->table('player_finances')->whereIn('user_id', $userIds)->get()->getResultArray();
            $finMap = [];
            foreach ($finRows as $f) {
                $finMap[$f['user_id']] = $f;
            }

            // 2. Batch fetch active staff counts
            $staffRows = $db->table('staff')->select('user_id, COUNT(*) as cnt')->whereIn('user_id', $userIds)->where('status !=', 'fired')->groupBy('user_id')->get()->getResultArray();
            $staffMap = array_column($staffRows, 'cnt', 'user_id');

            // 3. Batch fetch building counts
            $bldRows = $db->table('buildings')->select('user_id, COUNT(*) as cnt')->whereIn('user_id', $userIds)->groupBy('user_id')->get()->getResultArray();
            $bldMap = array_column($bldRows, 'cnt', 'user_id');

            // 4. Batch fetch player item counts
            $itemRows = $db->table('player_items')->select('user_id, COUNT(*) as cnt')->whereIn('user_id', $userIds)->groupBy('user_id')->get()->getResultArray();
            $itemMap = array_column($itemRows, 'cnt', 'user_id');

            // 5. Batch fetch latest activity for each user
            $latestLogs = $db->query("
                SELECT al1.* 
                FROM activity_log al1
                INNER JOIN (
                    SELECT user_id, MAX(id) as max_id 
                    FROM activity_log 
                    WHERE user_id IN (" . implode(',', array_map('intval', $userIds)) . ")
                    GROUP BY user_id
                ) al2 ON al1.id = al2.max_id
            ")->getResultArray();
            $actMap = [];
            foreach ($latestLogs as $log) {
                $actMap[$log['user_id']] = $log;
            }

            foreach ($users as &$u) {
                $uid = $u['id'];
                $fin = $finMap[$uid] ?? null;
                $u['cash'] = $fin ? (int) ($fin['cash'] ?? 0) : 0;
                $u['last_active'] = $fin['last_active'] ?? null;
                $u['reputation'] = $fin ? (int) ($fin['reputation'] ?? 0) : 0;
                $u['staff_count'] = (int) ($staffMap[$uid] ?? 0);
                $u['building_count'] = (int) ($bldMap[$uid] ?? 0);
                $u['item_count'] = (int) ($itemMap[$uid] ?? 0);
                $u['last_activity'] = $actMap[$uid] ?? null;
            }
        }

        $onlineRecentRow = $db->query("SELECT COUNT(DISTINCT user_id) as cnt FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->getRowArray();
        $onlineRecent = $onlineRecentRow['cnt'] ?? 0;
        $newUsers7d = $db->table('users')->where('created_at >', date('Y-m-d H:i:s', strtotime('-7 days')))->countAllResults();
        $bannedUsers = $db->table('users')->where('active', 0)->where('NOT ' . self::UNCONFIRMED_SQL, null, false)->countAllResults();
        $unconfirmedUsers = $db->table('users')->where('active', 0)->where(self::UNCONFIRMED_SQL, null, false)->countAllResults();

        $season = $db->table('seasons')->where('active', 1)->get()->getRowArray() ?? [];
        $unreadSupport = $db->table('support_messages')->where('sender', 'player')->where('is_read', 0)->countAllResults();
        // The tick writes tomorrow's weather row, so the newest row tells us when it last ran
        $lastTick = $db->table('weather')->select('game_day, created_at')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();

        $diskTotal = @disk_total_space('/') ?: 0;
        $diskFree = @disk_free_space('/') ?: 0;
        $cores = (int) (@shell_exec('nproc') ?: 1);
        $dbSizeRow = $db->query("SELECT ROUND(SUM(data_length + index_length)/1048576, 1) AS mb FROM information_schema.tables WHERE table_schema = ?", [$db->getDatabase()])->getRowArray();
        $server = [
            'diskTotal' => $diskTotal,
            'diskUsed' => $diskTotal - $diskFree,
            'diskPct' => $diskTotal > 0 ? (int) round(($diskTotal - $diskFree) / $diskTotal * 100) : 0,
            'load' => function_exists('sys_getloadavg') ? (sys_getloadavg() ?: [0, 0, 0]) : [0, 0, 0],
            'cores' => max(1, $cores),
            'memLimit' => ini_get('memory_limit'),
            'memPeak' => round(memory_get_peak_usage(true) / 1048576, 1),
            'dbVersion' => $db->getVersion(),
            'dbSize' => $dbSizeRow['mb'] ?? 0,
        ];

        $seasonLen = max(1, (int) ($season['duration_days'] ?? 135));
        $seasonPct = min(100, (int) round($gameDay / $seasonLen * 100));
        $tickAge = $lastTick ? time() - strtotime($lastTick['created_at']) : null;
        $tickStale = $tickAge === null || $tickAge > 26 * 3600;
        $activePct = $totalUsers > 0 ? (int) round($onlineRecent / $totalUsers * 100) : 0;
        $avgCash = $totalUsers > 0 ? (int) ($totalCash / $totalUsers) : 0;
        $gb = fn($b) => number_format($b / 1073741824, 1) . ' GB';

        $unconfirmed = self::unconfirmedIds(array_column($users, 'id'));
        $playersFormatted = array_map(function($u) use ($unconfirmed) {
            $state = self::accountState($u, $unconfirmed);
            $lastTs = !empty($u['last_active']) ? strtotime($u['last_active']) : 0;
            return [
                'id' => (int) $u['id'],
                'username' => $u['username'],
                'cashFormatted' => currency((int) ($u['cash'] ?? 0)),
                'cashRaw' => (int) ($u['cash'] ?? 0),
                'staff_count' => (int) ($u['staff_count'] ?? 0),
                'building_count' => (int) ($u['building_count'] ?? 0),
                'item_count' => (int) ($u['item_count'] ?? 0),
                'joinedFormatted' => date('M j', strtotime($u['created_at'])),
                'created_ts' => strtotime($u['created_at']),
                'last_active_ts' => $lastTs,
                'lastActiveFormatted' => $lastTs ? timeAgo($u['last_active']) : 'Never',
                'isOnline' => $lastTs > strtotime('-15 minutes'),
                'state' => $state,
                'isBanned' => $state === 'banned',
                'lastActivityMessage' => $u['last_activity']['message'] ?? '',
            ];
        }, $users);

        $recentLogsFormatted = array_map(function($l) {
            return [
                'id' => (int) ($l['id'] ?? 0),
                'username' => $l['username'] ?? 'Player',
                'message' => $l['message'] ?? '',
                'icon' => str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $l['icon'] ?? 'circle-dot'),
                'game_day' => (int) ($l['game_day'] ?? 1),
                'timeAgo' => !empty($l['created_at']) ? timeAgo($l['created_at']) : '',
            ];
        }, $recentLogs);

        // What needs attention: every number is a live query
        $since = fn(string $mod) => date('Y-m-d H:i:s', strtotime($mod));
        $errors24h = 0;
        foreach ([date('Y-m-d'), date('Y-m-d', strtotime('-1 day'))] as $day) {
            $f = WRITEPATH . 'logs/log-' . $day . '.log';
            if (!is_file($f)) continue;
            foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (preg_match('/^(ERROR|CRITICAL|ALERT|EMERGENCY) - (\S+ \S+)/', $line, $m) && strtotime($m[2]) >= strtotime('-24 hours')) $errors24h++;
            }
        }
        $attention = [
            'supportOpen' => (int) $db->query("SELECT COUNT(DISTINCT user_id) n FROM support_messages WHERE sender = 'player' AND is_read = 0")->getRow('n'),
            'errors24h' => $errors24h,
            'signupsToday' => $db->table('users')->where('created_at >=', date('Y-m-d 00:00:00'))->countAllResults(),
            'signups7d' => $db->table('users')->where('created_at >=', $since('-7 days'))->countAllResults(),
            'activeToday' => $db->table('users')->where('last_active >=', date('Y-m-d 00:00:00'))->countAllResults(),
            'active7d' => $db->table('users')->where('last_active >=', $since('-7 days'))->countAllResults(),
            'roadmapToReview' => $db->tableExists('roadmap_items') ? $db->table('roadmap_items')->where('status', 'suggested')->countAllResults() : 0,
            // Same thresholds as suspiciousActivity()
            'suspicious' => (int) $db->query("SELECT COUNT(*) n FROM users u JOIN player_finances f ON f.user_id = u.id
                WHERE f.cash > (CASE f.difficulty WHEN 'easy' THEN 5000000 WHEN 'hard' THEN 2000000 ELSE 3000000 END)")->getRow('n'),
            'backupMonitorUrl' => 'https://ski-manager.sentry.io/crons/ski-manager/db-dump/',
        ];

        $page = [
            'money' => ['symbol' => currencySymbol(), 'prefix' => isImperial()],
            'attention' => $attention,
            'season' => [
                'name' => $season['name'] ?? 'Season 1',
                'season_number' => (int) ($season['season_number'] ?? 1),
                'resort_map' => $season['resort_map'] ?? 'ParkCity',
                'start_date' => $season['start_date'] ?? 'N/A',
                'duration_days' => $seasonLen,
                'game_day' => (int) $gameDay,
                'days_left' => max(0, $seasonLen - $gameDay),
                'progress_pct' => $seasonPct,
                'maintenance' => (bool) ($season['maintenance'] ?? 0),
            ],
            'isStaging' => str_contains(ROOTPATH, 'staging'),
            'isDev' => ENVIRONMENT === 'development',
            'envWritable' => is_writable(ROOTPATH . '.env'),
            'serverTime' => date('g:ia'),
            'serverTimezone' => date_default_timezone_get(),
            'unreadSupport' => (int) $unreadSupport,
            'lastTick' => [
                'timeAgo' => $lastTick ? timeAgo($lastTick['created_at']) : 'never',
                'created_at' => $lastTick['created_at'] ?? null,
                'isStale' => $tickStale,
            ],
            'kpis' => [
                'totalUsers' => number_format($totalUsers),
                'newUsers7d' => (int) $newUsers7d,
                'bannedUsers' => (int) $bannedUsers,
                'unconfirmedUsers' => (int) $unconfirmedUsers,
                'onlineRecent' => number_format($onlineRecent),
                'activePct' => $activePct,
                'totalCashFormatted' => currency((int) $totalCash),
                'avgCashFormatted' => currency((int) $avgCash),
                'totalLoans' => number_format($totalLoans),
                'totalStaff' => number_format($totalStaff),
                'totalBuildings' => number_format($totalBuildings),
                'totalItems' => number_format($totalItems),
                'totalFacilities' => number_format($totalParking + $totalParks),
                'totalParking' => (int) $totalParking,
                'totalParks' => (int) $totalParks,
            ],
            'players' => $playersFormatted,
            'recentLogs' => $recentLogsFormatted,
            'weather' => [
                'condition_name' => $weather['condition_name'] ?? 'Light Snow',
                'temp' => (int) ($weather['temp'] ?? -5),
                'tempFormatted' => temp((int) ($weather['temp'] ?? -5)),
                'wind' => (int) ($weather['wind'] ?? 15),
                'snowfall' => (int) ($weather['snowfall'] ?? 0),
                'snow_base' => (int) ($weather['snow_base'] ?? 50),
                'conditions' => array_keys(\App\Libraries\Weather::VISIBILITY),
            ],
            'server' => [
                'diskUsedFormatted' => $gb($server['diskUsed']),
                'diskTotalFormatted' => $gb($server['diskTotal']),
                'diskPct' => (int) $server['diskPct'],
                'load' => array_map(fn($l) => round((float)$l, 2), $server['load']),
                'cores' => (int) $server['cores'],
                'phpVersion' => PHP_VERSION,
                'ciVersion' => \CodeIgniter\CodeIgniter::CI_VERSION,
                'memPeak' => $server['memPeak'],
                'memLimit' => (string) $server['memLimit'],
                'dbVersion' => (string) $server['dbVersion'],
                'dbSize' => $server['dbSize'],
            ],
        ];

        return view('admin/index', ['page' => $page]);
    }

    public function editUser(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');

        $db = db_connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();
        if (!$user) return redirect()->to('/admin')->with('error', 'User not found.');

        $finance = $db->table('player_finances')->where('user_id', $id)->get()->getRowArray();
        $staffRaw = $db->table('staff')->where('user_id', $id)->where('status !=', 'fired')->get()->getResultArray();
        $buildingsRaw = $db->table('buildings')->where('user_id', $id)->get()->getResultArray();
        $itemsRaw = $db->table('player_items')->where('user_id', $id)->get()->getResultArray();
        $loansRaw = $db->table('loans')->where('user_id', $id)->where('status', 'active')->get()->getResultArray();
        $genepis = $db->table('genepis')->where('user_id', $id)->get()->getRowArray();
        $logsRaw = $db->table('activity_log')->where('user_id', $id)->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        $identity = $db->table('auth_identities')->where('user_id', $id)->where('type', 'email_password')->get()->getRowArray();
        $parking = $db->table('parking')->where('user_id', $id)->get()->getResultArray();
        $terrainParks = $db->table('terrain_parks')->where('user_id', $id)->get()->getResultArray();
        $achievementsRaw = $db->table('achievements')->where('user_id', $id)->get()->getResultArray();
        $tutorial = $db->table('tutorial_progress')->where('user_id', $id)->get()->getRowArray();

        $staff = array_map(function($s) {
            return [
                'id' => (int) ($s['id'] ?? 0),
                'role' => $s['role'] ?? $s['type'] ?? '',
                'name' => $s['name'] ?? '',
                'salaryFormatted' => currency((int) ($s['salary'] ?? $s['daily_salary'] ?? 0)),
                'morale' => (int) ($s['morale'] ?? 100),
            ];
        }, $staffRaw);

        $items = array_map(function($it) {
            return [
                'id' => (int) ($it['id'] ?? 0),
                'name' => $it['name'] ?? 'Unnamed',
                'type' => $it['item_type'] ?? $it['type'] ?? '',
                'lengthFormatted' => distance((int) ($it['length_meters'] ?? 0)),
                'status' => $it['status'] ?? 'open',
            ];
        }, $itemsRaw);

        $buildings = array_map(function($b) {
            return [
                'id' => (int) ($b['id'] ?? 0),
                'name' => $b['name'] ?? '',
                'type' => $b['type'] ?? $b['building_type'] ?? '',
                'level' => (int) ($b['level'] ?? 1),
            ];
        }, $buildingsRaw);

        $loans = array_map(function($l) {
            return [
                'id' => (int) ($l['id'] ?? 0),
                'type' => $l['loan_type'] ?? 'Loan',
                'remainingFormatted' => currency((int) ($l['remaining'] ?? $l['amount'] ?? 0)),
                'daysRemaining' => (int) ($l['days_remaining'] ?? 0),
            ];
        }, $loansRaw);

        $logs = array_map(function($l) {
            return [
                'icon' => str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $l['icon'] ?? 'circle-dot'),
                'message' => $l['message'] ?? '',
                'game_day' => (int) ($l['game_day'] ?? 1),
            ];
        }, $logsRaw);

        $achievements = array_values(array_map(function($a) {
            return [
                'key' => $a['achievement_key'] ?? (string) $a['id'],
                'name' => $a['name'] ?? $a['achievement_key'] ?? '',
                'claimed' => (bool) ($a['claimed'] ?? 0),
            ];
        }, array_filter($achievementsRaw, fn($a) => (int) ($a['completed'] ?? 0) === 1)));

        $transactions = array_map(fn($t) => [
            'id' => (int) $t['id'],
            'gameDay' => (int) $t['game_day'],
            'category' => (string) $t['category'],
            'description' => (string) $t['description'],
            'amountFormatted' => ($t['type'] === 'expense' ? '−' : '+') . currency(abs((int) $t['amount'])),
            'isExpense' => $t['type'] === 'expense',
            'when' => $t['created_at'] ? date('M j, H:i', strtotime($t['created_at'])) : '',
        ], $db->table('financial_transactions')->where('user_id', $id)->orderBy('id', 'DESC')->limit(20)->get()->getResultArray());

        $genepisLog = array_map(fn($g) => [
            'id' => (int) $g['id'],
            'amountFormatted' => ($g['type'] === 'spent' ? '−' : '+') . number_format(abs((int) $g['amount'])),
            'isSpent' => $g['type'] === 'spent',
            'reason' => (string) $g['reason'],
            'when' => $g['created_at'] ? date('M j, H:i', strtotime($g['created_at'])) : '',
        ], $db->table('genepis_log')->where('user_id', $id)->orderBy('id', 'DESC')->limit(20)->get()->getResultArray());

        // Keep the network, drop the host: 203.0.113.42 -> 203.0.113.x, IPv6 keeps its first 3 groups
        $maskIp = function (string $ip): string {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return preg_replace('/\.\d+$/', '.x', $ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) return implode(':', array_slice(explode(':', $ip), 0, 3)) . ':x';
            return 'unknown';
        };
        $logins = array_map(fn($l) => [
            'id' => (int) $l['id'],
            'when' => date('M j, Y H:i', strtotime($l['date'])),
            'success' => (bool) $l['success'],
            'ip' => $maskIp((string) $l['ip_address']),
        ], $db->table('auth_logins')->select('id, date, success, ip_address')->where('user_id', $id)->orderBy('id', 'DESC')->limit(10)->get()->getResultArray());

        $notes = $db->tableExists('admin_notes') ? array_map(fn($n) => [
            'id' => (int) $n['id'],
            'note' => (string) $n['note'],
            'admin' => (string) ($n['admin_name'] ?? ('#' . $n['admin_id'])),
            'when' => date('M j, Y H:i', strtotime($n['created_at'])),
        ], $db->table('admin_notes n')->select('n.*, u.username AS admin_name')->join('users u', 'u.id = n.admin_id', 'left')
            ->where('n.user_id', $id)->orderBy('n.id', 'DESC')->get()->getResultArray()) : [];

        $page = [
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'active' => isset($user['active']) ? (bool) $user['active'] : true,
                'state' => self::accountState($user, self::unconfirmedIds([(int) $user['id']])),
                'isOwner' => ((int) $user['id'] === 1),
                'createdFormatted' => date('M j, Y', strtotime($user['created_at'])),
                'email' => $identity['secret'] ?? 'N/A',
            ],
            'finance' => [
                'cashRaw' => (int) ($finance['cash'] ?? 0),
                'cashFormatted' => currency((int) ($finance['cash'] ?? 0)),
                'reputation' => (int) ($finance['reputation'] ?? 50),
                'difficulty' => $finance['difficulty'] ?? 'standard',
                'units' => $finance['units'] ?? 'metric',
                'resortMap' => $finance['resort_map'] ?? 'ParkCity',
                'resortOpen' => (bool) ($finance['resort_open'] ?? true),
                'resortName' => (string) ($finance['resort_name'] ?? ''),
                'dailyVisitors' => number_format((int) ($finance['daily_visitors'] ?? 0)),
                'lastActive' => !empty($finance['last_active']) ? timeAgo($finance['last_active']) : 'never',
            ],
            'genepis' => [
                'balance' => (int) ($genepis['balance'] ?? 0),
                'balanceFormatted' => number_format((int) ($genepis['balance'] ?? 0)),
            ],
            'stats' => [
                'staffCount' => count($staff),
                'buildingCount' => count($buildings),
                'itemCount' => count($items),
                'loanCount' => count($loans),
                'achievementCount' => count($achievements),
                'facilityCount' => count($parking) + count($terrainParks),
            ],
            'staff' => $staff,
            'items' => $items,
            'buildings' => $buildings,
            'loans' => $loans,
            'achievements' => $achievements,
            'tutorial' => $tutorial ? [
                'step' => (int) ($tutorial['current_step'] ?? 1),
            ] : null,
            'logs' => $logs,
            'transactions' => $transactions,
            'money' => ['symbol' => currencySymbol(), 'prefix' => isImperial()],
            'genepisLog' => $genepisLog,
            'logins' => $logins,
            'notes' => $notes,
        ];

        return view('admin/user', ['page' => $page]);
    }

    public function updateCash()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $cash = (int) $this->request->getPost('cash');
        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') return redirect()->to('/admin/user/' . $userId)->with('error', 'Give a reason for the change.');
        $before = (int) (db_connect()->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow('cash') ?? 0);
        db_connect()->table('player_finances')->where('user_id', $userId)->update(['cash' => $cash]);
        $this->auditLog('set_cash', $userId, json_encode(['from' => $before, 'to' => $cash, 'reason' => $reason]));
        log_activity($userId, 'admin_cash', "Admin set cash to " . currency($cash));
        return redirect()->to('/admin/user/' . $userId)->with('success', 'Cash updated.');
    }

    public function updateGenepis()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $amount = (int) $this->request->getPost('genepis');
        $reason = trim((string) $this->request->getPost('reason'));
        if ($reason === '') return redirect()->to('/admin/user/' . $userId)->with('error', 'Give a reason for the change.');
        $before = (int) (db_connect()->table('genepis')->select('balance')->where('user_id', $userId)->get()->getRow('balance') ?? 0);
        db_connect()->table('genepis')->where('user_id', $userId)->update(['balance' => $amount]);
        $this->auditLog('set_genepis', $userId, json_encode(['from' => $before, 'to' => $amount, 'reason' => $reason]));
        log_activity($userId, 'admin_genepis', "Admin set génépis to " . $amount);
        return redirect()->to('/admin/user/' . $userId)->with('success', 'Génépis updated.');
    }

    public function updateReputation()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $rep = (int) $this->request->getPost('reputation');
        db_connect()->table('player_finances')->where('user_id', $userId)->update(['reputation' => $rep]);
        log_activity($userId, 'admin_reputation', "Admin set reputation to " . $rep);
        return redirect()->to('/admin/user/' . $userId)->with('success', 'Reputation updated.');
    }

    public function resetTutorial(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();
        $db->table('tutorial_progress')->where('user_id', $id)->delete();
        return redirect()->to('/admin/user/' . $id)->with('success', 'Tutorial reset.');
    }

    public function resetUser(int $id)
    {
        if (!$this->checkAdmin() || $id === 1) return redirect()->to('/admin');
        $db = db_connect();
        $tables = ['staff', 'buildings', 'snow_cannons', 'night_skiing', 'equipment', 'marketing_campaigns', 'loans', 'regulations', 'insurance', 'achievements', 'daily_bonus', 'financial_transactions', 'activity_log', 'lift_tickets', 'ticket_sales', 'player_items', 'genepis_log', 'environmental', 'dashboard_widgets', 'parking', 'terrain_parks', 'tutorial_progress', 'scenic_lifts'];
        foreach ($tables as $t) { try { $db->table($t)->where('user_id', $id)->delete(); } catch (\Exception $e) {} }
        $db->table('player_finances')->where('user_id', $id)->update(['cash' => 500000, 'total_income' => 0, 'total_expenses' => 0, 'reputation' => 0]);
        $db->table('genepis')->where('user_id', $id)->update(['balance' => 0]);
        log_activity($id, 'admin_reset', 'Account reset by admin');
        return redirect()->to('/admin/user/' . $id)->with('success', 'User reset to fresh start with 500,000 cash.');
    }

    public function banUser(int $id)
    {
        if (!$this->checkAdmin() || $id === 1) return redirect()->to('/admin');
        db_connect()->table('users')->where('id', $id)->update(['active' => 0]);
        return redirect()->to('/admin')->with('success', 'User banned.');
    }

    public function unbanUser(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin');
        db_connect()->table('users')->where('id', $id)->update(['active' => 1]);
        return redirect()->to('/admin')->with('success', 'User unbanned.');
    }

    public function deleteUser(int $id)
    {
        if (!$this->checkAdmin() || $id === 1) return redirect()->to('/admin');
        $db = db_connect();
        $tables = ['staff', 'buildings', 'snow_cannons', 'night_skiing', 'equipment', 'marketing_campaigns', 'loans', 'regulations', 'insurance', 'achievements', 'daily_bonus', 'player_finances', 'financial_transactions', 'activity_log', 'lift_tickets', 'ticket_sales', 'player_items', 'genepis', 'genepis_log', 'environmental', 'dashboard_widgets', 'parking', 'terrain_parks', 'tutorial_progress', 'scenic_lifts'];
        foreach ($tables as $t) { try { $db->table($t)->where('user_id', $id)->delete(); } catch (\Exception $e) {} }
        $db->table('auth_identities')->where('user_id', $id)->delete();
        $db->table('auth_logins')->where('user_id', $id)->delete();
        $db->table('auth_remember_tokens')->where('user_id', $id)->delete();
        $db->table('users')->where('id', $id)->delete();
        return redirect()->to('/admin')->with('success', 'User deleted.');
    }

    public function broadcast(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $page = [];
        return view('admin/broadcast', ['page' => $page]);
    }

    public function sendBroadcast()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $message = strip_tags(trim($this->request->getPost('message')));
        $type = $this->request->getPost('type') ?: 'info';
        $icon = $this->request->getPost('icon') ?: 'fa-solid fa-bullhorn';
        if (empty($message)) return redirect()->back()->with('error', 'Message is empty.');

        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $users = $db->table('users')->get()->getResultArray();
        $activityBatch = [];
        $notifBatch = [];
        $now = date('Y-m-d H:i:s');
        foreach ($users as $u) {
            $activityBatch[] = [
                'user_id' => $u['id'],
                'game_day' => $gameDay,
                'category' => 'System',
                'message' => $message,
                'icon' => $icon,
                'created_at' => $now,
            ];
            $notifBatch[] = [
                'user_id' => $u['id'],
                'type' => $type,
                'title' => 'Server Announcement',
                'message' => $message,
                'icon' => $icon,
                'link' => '/activity',
                'is_read' => 0,
                'created_at' => $now,
            ];
        }
        if (!empty($activityBatch)) {
            $db->table('activity_log')->insertBatch($activityBatch);
        }
        if (!empty($notifBatch)) {
            $db->table('notifications')->insertBatch($notifBatch);
        }
        return redirect()->to('/admin/broadcast')->with('success', 'Broadcast sent to ' . count($users) . ' players.');
    }

    public function triggerTick()
    {
        if (!$this->checkAdmin()) return redirect()->to("/admin");
        try {
            // Use this install's own spark: a hardcoded path made staging tick production
            exec("php " . escapeshellarg(ROOTPATH . "spark") . " game:tick > /dev/null 2>&1 &");

            return redirect()->to("/admin")->with("success", "Game tick started in the background. Refresh in a minute to see the results.");
        } catch (\Throwable $e) {
            return redirect()->to("/admin")->with("error", "Tick error: " . $e->getMessage());
        }
    }

    public function setWeather()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/settings');
        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $condition = $this->request->getPost('condition');
        $temp = (int) $this->request->getPost('temp');
        $wind = (int) $this->request->getPost('wind');
        $snowBase = (int) $this->request->getPost('snow_base');
        $snowfall = (int) $this->request->getPost('snowfall');

        $visMap = \App\Libraries\Weather::VISIBILITY;
        if (!isset($visMap[$condition])) return redirect()->to('/admin/settings')->with('error', 'Unknown weather condition.');
        \App\Libraries\Weather::ensure($gameDay + 1);

        $db->table('weather')->where('game_day', $gameDay)->update([
            'condition_name' => $condition, 'temp' => $temp,
            'wind' => $wind, 'snow_base' => $snowBase, 'snowfall' => $snowfall,
            'visibility' => $visMap[$condition] ?? 'Good',
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/admin/settings')->with('success', "Weather updated for day {$gameDay}.");
    }

    public function grantAchievement()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $achievementId = (int) $this->request->getPost('achievement_id');
        if ($userId <= 0 || $achievementId <= 0) return redirect()->to('/admin')->with('error', 'Give a player ID and an achievement ID.');
        $achRow = db_connect()->table('achievement_defs')->where('id', $achievementId)->get()->getRowArray() ?: db_connect()->table('achievements')->where('id', $achievementId)->get()->getRowArray();
        $target = $achRow['target'] ?? 100;
        db_connect()->table('achievements')->where('id', $achievementId)->where('user_id', $userId)->update(['completed' => 1, 'progress' => $target]);
        log_activity($userId, 'admin_achievement', 'Achievement granted by admin');
        return redirect()->to('/admin/user/' . $userId)->with('success', 'Achievement granted.');
    }

    public function addCashAll()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin');
        $amount = (int) $this->request->getPost('amount');
        if ($amount <= 0 || $amount > 10000000) return redirect()->to('/admin')->with('error', 'Invalid amount.');
        $db = db_connect();
        $db->table('player_finances')->set('cash', "cash + {$amount}", false)->update();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime(getSeasonStartDate())) / 86400) + 1);
        $users = $db->table('users')->get()->getResultArray();
        foreach ($users as $u) {
            $db->table('activity_log')->insert([
                'user_id' => $u['id'], 'game_day' => $gameDay,
                'category' => 'System', 'message' => 'Received ' . currency($amount) . ' bonus from admin!',
                'icon' => 'fa-solid fa-gift', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('/admin')->with('success', currency($amount) . ' added to all ' . count($users) . ' players.');
    }

    public function addGenepisAll()
    {
        if (!$this->checkAdmin()) return redirect()->to("/admin");
        $amount = (int) $this->request->getPost("amount");
        if ($amount <= 0 || $amount > 100000) return redirect()->to("/admin")->with("error", "Invalid amount.");
        $db = db_connect();
        $db->table("genepis")->set("balance", "balance + {$amount}", false)->set("total_earned", "total_earned + {$amount}", false)->update();
        $this->auditLog("genepis_all", null, "+" . $amount . " to all players");
        return redirect()->to("/admin")->with("success", number_format($amount) . " Genepis added to all players.");
    }
    public function gameSettings(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();
        $weatherRaw = $db->table('weather')->orderBy('game_day', 'DESC')->limit(7)->get()->getResultArray();
        $weather = array_map(function($w) {
            return [
                'game_day' => (int) $w['game_day'],
                'condition_name' => $w['condition_name'],
                'tempFormatted' => temp($w['temp']),
                'windFormatted' => speed($w['wind']),
                'snowBaseFormatted' => snow($w['snow_base']),
            ];
        }, $weatherRaw);

        $season = $db->table('seasons')->where('active', 1)->get()->getRowArray();
        $resortMap = $season['resort_map'] ?? 'ParkCity';
        $sectorsRaw = $db->table('resort_sectors')->where('resort_map', $resortMap)->orderBy('sort_order')->get()->getResultArray();
        $sectors = array_map(function($s) {
            return [
                'id' => (int) $s['id'],
                'name' => $s['name'],
                'color' => $s['color'] ?? '#3b82f6',
                'visible' => (bool) $s['visible'],
                'released' => (bool) $s['released'],
            ];
        }, $sectorsRaw);

        $segCount = $db->table('map_segments')->where('active', 1)->where('resort_map', $resortMap)->countAllResults();

        $tournamentsRaw = $db->table('tournaments')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();
        $tournaments = array_map(function($t) {
            return [
                'id' => (int) $t['id'],
                'name' => $t['name'],
                'status' => $t['status'] ?? 'upcoming',
            ];
        }, $tournamentsRaw);

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $page = [
            'season' => $season ? [
                'id' => (int) $season['id'],
                'name' => $season['name'],
                'season_number' => (int) $season['season_number'],
                'resort_map' => $season['resort_map'],
                'start_date' => $season['start_date'],
                'duration_days' => (int) $season['duration_days'],
                'winter_days' => (int) $season['winter_days'],
            ] : null,
            'sectors' => $sectors,
            'segmentCount' => $segCount,
            'weather' => $weather,
            'tournaments' => $tournaments,
            'gameDay' => $gameDay,
        ];

        return view('admin/settings', ['page' => $page]);
    }

    public function activityLog(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();
        $page = (int) ($this->request->getGet('page') ?? 1);
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $filter = $this->request->getGet('filter') ?? '';
        $userFilter = $this->request->getGet('user') ?? '';

        $query = $db->table('activity_log al')->select('al.*, u.username')->join('users u', 'u.id = al.user_id');
        if ($filter) $query->like('al.message', $filter);
        if ($userFilter) $query->where('al.user_id', (int) $userFilter);

        $total = $query->countAllResults(false);
        $logsRaw = $query->orderBy('al.created_at', 'DESC')->limit($perPage, $offset)->get()->getResultArray();
        $logs = array_map(function($l) {
            return [
                'id' => (int) ($l['id'] ?? 0),
                'user_id' => (int) ($l['user_id'] ?? 0),
                'username' => $l['username'] ?? 'System',
                'game_day' => (int) ($l['game_day'] ?? 1),
                'category' => $l['category'] ?? 'General',
                'message' => $l['message'] ?? '',
                'icon' => str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $l['icon'] ?? 'circle-dot'),
                'timeFormatted' => isset($l['created_at']) ? date('M d H:i', strtotime($l['created_at'])) : '-',
            ];
        }, $logsRaw);

        $pageData = [
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => max(1, (int) ceil($total / $perPage)),
            'filter' => $filter,
            'userFilter' => $userFilter,
        ];

        return view('admin/activity', ['page' => $pageData]);
    }

    public function economy(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();

        $finances = $db->table('player_finances')->get()->getResultArray();
        $totalCash = array_sum(array_column($finances, 'cash'));
        $totalIncome = array_sum(array_column($finances, 'total_income'));
        $totalExpenses = array_sum(array_column($finances, 'total_expenses'));
        $avgCash = count($finances) > 0 ? round($totalCash / count($finances)) : 0;

        $richestRaw = $db->query("SELECT pf.*, u.username FROM player_finances pf JOIN users u ON u.id = pf.user_id ORDER BY pf.cash DESC LIMIT 10")->getResultArray();
        $richest = array_map(function($r) {
            return [
                'user_id' => (int) $r['user_id'],
                'username' => $r['username'],
                'cashFormatted' => currency($r['cash']),
                'incomeFormatted' => currency($r['total_income']),
                'expensesFormatted' => currency($r['total_expenses']),
            ];
        }, $richestRaw);

        $recentTransactionsRaw = $db->table('financial_transactions')->orderBy('created_at', 'DESC')->limit(30)->get()->getResultArray();
        $recentTransactions = array_map(function($t) {
            return [
                'id' => (int) $t['id'],
                'type' => $t['type'] === 'income' ? 'income' : 'expense',
                'amountFormatted' => ($t['type'] === 'income' ? '+' : '-') . currency(abs($t['amount'])),
                'description' => $t['description'],
                'game_day' => (int) $t['game_day'],
            ];
        }, $recentTransactionsRaw);

        $loanStats = $db->table('loans')->where('status', 'active')->get()->getResultArray();
        $totalDebt = array_sum(array_column($loanStats, 'remaining'));

        $page = [
            'totalCashFormatted' => currency($totalCash),
            'avgCashFormatted' => currency($avgCash),
            'totalIncomeFormatted' => currency($totalIncome),
            'totalExpensesFormatted' => currency($totalExpenses),
            'totalDebtFormatted' => currency($totalDebt),
            'playerCount' => count($finances),
            'richest' => $richest,
            'recentTransactions' => $recentTransactions,
        ];

        $page['demoStats'] = Demo::stats();
        return view('admin/economy', ['page' => $page]);
    }

    public function toggleMaintenance()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $season = $db->table('seasons')->where('active', 1)->get()->getRowArray();
        if ($season) {
            $new = !empty($season['maintenance']) ? 0 : 1;
            $update = ['maintenance' => $new];
            if ($new === 1) {
                if (empty($season['maintenance_until'])) {
                    $update['maintenance_until'] = date('Y-m-d H:i:s', time() + 345600); // 4 days
                }
                if (empty($season['maintenance_message'])) {
                    $update['maintenance_message'] = 'Ski Manager is temporarily paused for 4 days of scheduled maintenance and active development for the launch of Season 2.';
                }
            }
            $db->table('seasons')->where('id', $season['id'])->update($update);
            $this->auditLog('maintenance', null, $new ? 'enabled' : 'disabled');
            return redirect()->to('/admin')->with('success', 'Maintenance mode ' . ($new ? 'enabled' : 'disabled'));
        }
        return redirect()->to('/admin')->with('error', 'No active season');
    }

    public function updateSeason()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/settings')->with('error', 'Unauthorized access.');
        $d = $this->request->getPost();
        db_connect()->table('seasons')->where('active', 1)->update([
            'name' => $d['name'], 'start_date' => $d['start_date'],
            'duration_days' => (int) $d['duration_days'], 'winter_days' => (int) $d['winter_days'],
        ]);
        return redirect()->to('/admin/settings')->with('success', 'Season updated.');
    }

    public function toggleSectorRelease(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/settings')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $s = $db->table('resort_sectors')->where('id', $id)->get()->getRowArray();
        if ($s) $db->table('resort_sectors')->where('id', $id)->update(['released' => $s['released'] ? 0 : 1]);
        return redirect()->to('/admin/settings')->with('success', 'Sector updated.');
    }

    public function errorLog(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $file = WRITEPATH . 'logs/log-' . date('Y-m-d') . '.log';
        $lines = [];
        if (file_exists($file)) {
            $all = file($file, FILE_IGNORE_NEW_LINES);
            $filtered = array_filter($all, fn($l) => !str_contains($l, 'DEBUG') && !str_contains($l, 'Session:'));
            $lines = array_slice(array_values($filtered), -50);
        }

        $page = [
            'lines' => $lines,
            'today' => date('M j, Y'),
        ];

        return view('admin/errors', ['page' => $page]);
    }

    public function impersonate(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();
        if (!$user) return redirect()->to('/admin')->with('error', 'User not found.');
        $adminId = auth()->id();
        auth()->logout();
        session()->set('admin_original_id', $adminId);
        auth()->login(auth()->getProvider()->findById($id));
        $this->auditLog('impersonate', $id);
        return redirect()->to('/dashboard')->with('success', 'Impersonating ' . $user['username'] . '. Visit /admin/stop-impersonate to return.');
    }

    public function stopImpersonate()
    {
        $originalId = session()->get('admin_original_id');
        if ($originalId) {
            auth()->logout();
            auth()->login(auth()->getProvider()->findById($originalId));
            session()->remove('admin_original_id');
        }
        $this->auditLog('stop_impersonate');
        return redirect()->to('/admin')->with('success', 'Back to admin account.');
    }

    public function toggleEnvironment()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin')->with('error', 'Unauthorized access.');
        $envFile = ROOTPATH . '.env';

        if (!file_exists($envFile)) {
            return redirect()->to('/admin')->with('error', 'The .env configuration file was not found at ' . ROOTPATH);
        }

        if (!is_readable($envFile)) {
            return redirect()->to('/admin')->with('error', 'Cannot read .env file: permission denied.');
        }

        if (!is_writable($envFile)) {
            return redirect()->to('/admin')->with('error', 'Cannot toggle environment: .env file is read-only (permission denied for web server). To switch modes, update CI_ENVIRONMENT directly on the server or run: chmod 664 .env');
        }

        try {
            $content = file_get_contents($envFile);
            if ($content === false) {
                return redirect()->to('/admin')->with('error', 'Failed to read .env file.');
            }

            if (preg_match('/^(\s*#?\s*CI_ENVIRONMENT\s*=\s*[\'"]?)production([\'"]?)/m', $content)) {
                $content = preg_replace('/^(\s*#?\s*CI_ENVIRONMENT\s*=\s*[\'"]?)production([\'"]?)/m', 'CI_ENVIRONMENT = development', $content);
                $msg = 'Switched to DEVELOPMENT mode';
            } elseif (preg_match('/^(\s*#?\s*CI_ENVIRONMENT\s*=\s*[\'"]?)development([\'"]?)/m', $content)) {
                $content = preg_replace('/^(\s*#?\s*CI_ENVIRONMENT\s*=\s*[\'"]?)development([\'"]?)/m', 'CI_ENVIRONMENT = production', $content);
                $msg = 'Switched to PRODUCTION mode';
            } else {
                if (str_contains($content, 'CI_ENVIRONMENT = production')) {
                    $content = str_replace('CI_ENVIRONMENT = production', 'CI_ENVIRONMENT = development', $content);
                    $msg = 'Switched to DEVELOPMENT mode';
                } else {
                    $content = str_replace('CI_ENVIRONMENT = development', 'CI_ENVIRONMENT = production', $content);
                    $msg = 'Switched to PRODUCTION mode';
                }
            }

            $written = @file_put_contents($envFile, $content);
            if ($written === false) {
                return redirect()->to('/admin')->with('error', 'Failed to write to .env file (permission denied). Adjust server file permissions.');
            }

            $this->auditLog('toggle_environment', null, $msg);
            return redirect()->to('/admin')->with('success', $msg);
        } catch (\Throwable $e) {
            log_message('error', 'toggleEnvironment error: ' . $e->getMessage());
            return redirect()->to('/admin')->with('error', 'Could not toggle environment: ' . $e->getMessage());
        }
    }

    private function auditLog(string $action, ?int $targetUserId = null, ?string $details = null): void
    {
        db_connect()->table('admin_audit_log')->insert([
            'admin_id' => auth()->id(),
            'action' => $action,
            'target_user_id' => $targetUserId,
            'details' => $details,
        ]);
    }

    public function viewAuditLog(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $logsRaw = db_connect()->table('admin_audit_log a')
            ->select('a.*, u.username as admin_name, t.username as target_name')
            ->join('users u', 'u.id = a.admin_id', 'left')
            ->join('users t', 't.id = a.target_user_id', 'left')
            ->orderBy('a.created_at', 'DESC')->limit(100)->get()->getResultArray();

        $logs = array_map(function($l) {
            return [
                'id' => (int) ($l['id'] ?? 0),
                'timeFormatted' => isset($l['created_at']) ? date('M j H:i', strtotime($l['created_at'])) : '-',
                'admin_name' => $l['admin_name'] ?? 'System',
                'action' => $l['action'] ?? '',
                'target_name' => $l['target_name'] ?? null,
                'target_user_id' => isset($l['target_user_id']) ? (int) $l['target_user_id'] : null,
                'details' => $l['details'] ?? '',
            ];
        }, $logsRaw);

        $page = ['logs' => $logs];

        return view('admin/audit', ['page' => $page]);
    }

    public function playerComparison(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $usersRaw = $db->table('users')->select('id, username')->orderBy('username')->get()->getResultArray();
        $users = array_map(fn($u) => ['id' => (int) $u['id'], 'username' => $u['username']], $usersRaw);

        $a = $this->request->getGet('a');
        $b = $this->request->getGet('b');
        $dataA = $dataB = null;
        if ($a) {
            $rawA = $db->table('player_finances')->where('user_id', $a)->get()->getRowArray() ?: [];
            $userA = $db->table('users')->where('id', $a)->get()->getRowArray();
            $dataA = [
                'id' => (int) $a,
                'username' => $userA['username'] ?? '',
                'cashFormatted' => currency((int) ($rawA['cash'] ?? 0)),
                'difficulty' => $rawA['difficulty'] ?? '-',
                'resort_map' => $rawA['resort_map'] ?? '-',
                'staff' => $db->table('staff')->where('user_id', $a)->where('status !=', 'fired')->countAllResults(),
                'buildings' => $db->table('buildings')->where('user_id', $a)->countAllResults(),
                'items' => $db->table('player_items')->where('user_id', $a)->countAllResults(),
            ];
        }
        if ($b) {
            $rawB = $db->table('player_finances')->where('user_id', $b)->get()->getRowArray() ?: [];
            $userB = $db->table('users')->where('id', $b)->get()->getRowArray();
            $dataB = [
                'id' => (int) $b,
                'username' => $userB['username'] ?? '',
                'cashFormatted' => currency((int) ($rawB['cash'] ?? 0)),
                'difficulty' => $rawB['difficulty'] ?? '-',
                'resort_map' => $rawB['resort_map'] ?? '-',
                'staff' => $db->table('staff')->where('user_id', $b)->where('status !=', 'fired')->countAllResults(),
                'buildings' => $db->table('buildings')->where('user_id', $b)->countAllResults(),
                'items' => $db->table('player_items')->where('user_id', $b)->countAllResults(),
            ];
        }

        $page = [
            'users' => $users,
            'dataA' => $dataA,
            'dataB' => $dataB,
            'selectedA' => $a ? (int) $a : null,
            'selectedB' => $b ? (int) $b : null,
        ];

        return view('admin/compare', ['page' => $page]);
    }

    public function exportPlayers()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $rows = $db->query("SELECT u.id, u.username, u.created_at, f.cash, f.difficulty, f.resort_map, f.units,
            (SELECT COUNT(*) FROM staff s WHERE s.user_id=u.id AND s.status!='fired') as staff,
            (SELECT COUNT(*) FROM buildings b WHERE b.user_id=u.id) as buildings,
            (SELECT COUNT(*) FROM player_items p WHERE p.user_id=u.id) as items
            FROM users u LEFT JOIN player_finances f ON f.user_id=u.id ORDER BY u.id")->getResultArray();
        $this->response->setHeader('Content-Type', 'text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="players_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        if (!empty($rows)) { fputcsv($out, array_keys($rows[0])); foreach ($rows as $r) fputcsv($out, $r); }
        fclose($out);
        $this->response->send();
        exit;
    }

    public function changelogManager(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $entriesRaw = db_connect()->table('changelogs')->orderBy('created_at', 'DESC')->get()->getResultArray();
        $entries = array_map(function($e) {
            return [
                'id' => (int) $e['id'],
                'version' => $e['version'],
                'title' => $e['title'],
                'content' => $e['content'],
                'published' => (bool) $e['published'],
                'created_at' => $e['created_at'],
                'dateFormatted' => date('M j, Y', strtotime($e['created_at'])),
            ];
        }, $entriesRaw);

        $page = [
            'entries' => $entries,
        ];

        return view('admin/changelogs', ['page' => $page]);
    }

    public function saveChangelog()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/changelogs')->with('error', 'Unauthorized access.');
        $d = $this->request->getPost();
        $db = db_connect();
        if (!empty($d['id'])) {
            $db->table('changelogs')->where('id', $d['id'])->update(['version' => $d['version'], 'title' => $d['title'], 'content' => $d['content'], 'published' => isset($d['published']) ? 1 : 0]);
        } else {
            $db->table('changelogs')->insert(['version' => $d['version'], 'title' => $d['title'], 'content' => $d['content'], 'published' => isset($d['published']) ? 1 : 0]);
        }
        $this->auditLog('changelog', null, $d['title']);
        return redirect()->to('/admin/changelogs')->with('success', 'Changelog saved.');
    }

    public function deleteChangelog(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/changelogs')->with('error', 'Unauthorized access.');
        db_connect()->table('changelogs')->where('id', $id)->delete();
        return redirect()->to('/admin/changelogs')->with('success', 'Deleted.');
    }

    public function featureFlags(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $flagsRaw = db_connect()->table('feature_flags')->orderBy('name')->get()->getResultArray();
        $usage = $this->featureFlagUsage();

        $flags = [];
        $counts = ['everyone' => 0, 'admins' => 0, 'beta' => 0, 'off' => 0, 'unused' => 0];
        $beta = \App\Libraries\Beta::adminSummary();
        $counts['members'] = $beta['members'];

        foreach ($flagsRaw as $f) {
            $lvl = (int) $f['enabled'];
            if ($lvl === 2) $counts['everyone']++;
            elseif ($lvl === 1) $counts['admins']++;
            elseif ($lvl === FEATURE_LEVEL_BETA) $counts['beta']++;
            else $counts['off']++;

            $isUnused = empty($usage[$f['flag_key']]);
            if ($isUnused) $counts['unused']++;

            $cleanName = str_replace('[BETA] ', '', $f['name']);
            $isBeta = str_starts_with($f['flag_key'], 'beta_');

            $flags[] = [
                'id' => (int) $f['id'],
                'name' => $f['name'],
                'cleanName' => $cleanName,
                'flag_key' => $f['flag_key'],
                'description' => $f['description'] ?? '',
                'enabled' => $lvl,
                'isBeta' => $isBeta,
                'usage' => $usage[$f['flag_key']] ?? null,
                // Beta level: members with it on (members minus those who switched it off), and feedback
                'betaMembersOn' => $lvl === FEATURE_LEVEL_BETA ? max(0, $beta['members'] - ($beta['off'][$f['flag_key']] ?? 0)) : null,
                'betaSince' => !empty($f['beta_since']) ? date('j M Y', strtotime($f['beta_since'])) : null,
                'feedback' => $beta['feedback'][$f['flag_key']] ?? null,
            ];
        }

        $page = [
            'flags' => $flags,
            'counts' => $counts,
        ];

        return view('admin/features', ['page' => $page]);
    }

    /**
     * Where each flag is actually used: pages it gates (FeatureGate) and code that checks it.
     * Found by scanning the code so the page can point out flags that don't control anything.
     */
    private function featureFlagUsage(): array
    {
        // Cached for a day; every deploy runs spark cache:clear, which is when this can change
        $cached = cache('admin_feature_flag_usage');
        if (is_array($cached)) return $cached;
        $usage = [];
        foreach (\App\Filters\FeatureGate::ROUTE_FLAGS as $route => $flag) {
            $usage[$flag]['pages'][] = '/' . $route;
        }
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php' || str_contains($file->getFilename(), '.bak')) continue;
            if (!preg_match_all('/featureEnabled(?:For)?\(\s*[\'"]([a-z0-9_]+)[\'"]\s*[,)]/', file_get_contents($file->getPathname()), $m)) continue;
            $where = str_replace([APPPATH, '.php'], '', $file->getPathname());
            foreach (array_unique($m[1]) as $flag) {
                $usage[$flag]['code'][] = $where;
            }
        }
        cache()->save('admin_feature_flag_usage', $usage, DAY);
        return $usage;
    }

    /** POST /admin/features/set/{id} with level 0 (off), 1 (admins only), 3 (beta: admins and beta members) or 2 (everyone) */
    public function setFlag(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/features')->with('error', 'Unauthorized access.');
        $level = (int) $this->request->getPost('level');
        if (!in_array($level, [0, 1, 2, FEATURE_LEVEL_BETA], true)) return redirect()->to('/admin/features')->with('error', 'Unknown level.');

        $db = db_connect();
        $flag = $db->table('feature_flags')->where('id', $id)->get()->getRowArray();
        if (!$flag) return redirect()->to('/admin/features')->with('error', 'Flag not found.');

        $labels = [0 => 'off', 1 => 'admins only', 2 => 'everyone', FEATURE_LEVEL_BETA => 'beta'];
        if ((int) $flag['enabled'] !== $level) {
            $update = ['enabled' => $level];
            // When it entered beta, for the "In beta since" line on /beta; cleared once it leaves beta
            if ($level === FEATURE_LEVEL_BETA) $update['beta_since'] = date('Y-m-d H:i:s');
            elseif ((int) $flag['enabled'] === FEATURE_LEVEL_BETA) $update['beta_since'] = null;
            $db->table('feature_flags')->where('id', $id)->update($update);
            $this->auditLog('feature_flag', null, $flag['flag_key'] . ': ' . ($labels[(int) $flag['enabled']] ?? $flag['enabled']) . ' -> ' . $labels[$level]);
        }
        return redirect()->to('/admin/features#flag-' . $id)->with('success', $flag['name'] . ' is now ' . $labels[$level] . '.');
    }

    public function toggleFlag(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/features')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $flag = $db->table('feature_flags')->where('id', $id)->get()->getRowArray();
        if ($flag) {
            $isBeta = str_starts_with($flag["flag_key"], "beta_");
            // beta_ keys step through off, admins only, beta, everyone; others switch off/everyone
            $cycle = [0 => 1, 1 => FEATURE_LEVEL_BETA, FEATURE_LEVEL_BETA => 2, 2 => 0];
            $old = (int) $flag["enabled"];
            $new = $isBeta ? ($cycle[$old] ?? 0) : ($old ? 0 : 2);
            $update = ["enabled" => $new];
            if ($new === FEATURE_LEVEL_BETA) $update['beta_since'] = date('Y-m-d H:i:s');
            elseif ($old === FEATURE_LEVEL_BETA) $update['beta_since'] = null;
            $db->table("feature_flags")->where("id", $id)->update($update);
            $this->auditLog('feature_flag', null, $flag['flag_key'] . ' ' . ([0 => "off", 1 => "admin only", 2 => "everyone", FEATURE_LEVEL_BETA => "beta"][$new]));
        }
        return redirect()->to('/admin/features')->with('success', 'Flag updated.');
    }

    public function suspiciousActivity(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $suspectsRaw = $db->query("
            SELECT u.id, u.username, f.cash, f.difficulty,
                (SELECT COUNT(*) FROM admin_audit_log WHERE target_user_id = u.id AND action = 'flagged') as times_flagged,
                (SELECT message FROM activity_log WHERE user_id = u.id AND message LIKE '%income%' ORDER BY created_at DESC LIMIT 1) as last_income
            FROM users u
            JOIN player_finances f ON f.user_id = u.id
            WHERE f.cash > (
                CASE f.difficulty
                    WHEN 'easy' THEN 5000000
                    WHEN 'hard' THEN 2000000
                    ELSE 3000000
                END
            )
            ORDER BY f.cash DESC
            LIMIT 20
        ")->getResultArray();

        $suspects = array_map(function($s) {
            return [
                'id' => (int) $s['id'],
                'username' => $s['username'],
                'cashFormatted' => currency((int) $s['cash']),
                'difficulty' => $s['difficulty'] ?? 'standard',
                'times_flagged' => (int) ($s['times_flagged'] ?? 0),
                'last_income' => $s['last_income'] ?? '-',
            ];
        }, $suspectsRaw);

        $recentRaw = $db->query("
            SELECT u.id, u.username, f.cash, f.difficulty,
                a1.message as latest, a1.created_at
            FROM users u
            JOIN player_finances f ON f.user_id = u.id
            JOIN activity_log a1 ON a1.user_id = u.id
            WHERE a1.message LIKE '%Admin set cash%'
            ORDER BY a1.created_at DESC
            LIMIT 10
        ")->getResultArray();

        $recent = array_map(function($r) {
            return [
                'id' => (int) $r['id'],
                'username' => $r['username'],
                'cashFormatted' => currency((int) ($r['cash'] ?? 0)),
                'latest' => $r['latest'] ?? '',
                'timeFormatted' => isset($r['created_at']) ? date('M j H:i', strtotime($r['created_at'])) : '-',
            ];
        }, $recentRaw);

        $page = [
            'suspects' => $suspects,
            'recent' => $recent,
        ];

        return view('admin/suspicious', ['page' => $page]);
    }

    public function createSeason()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/seasons')->with('error', 'Unauthorized access.');
        $d = $this->request->getPost();
        db_connect()->table('seasons')->insert([
            'season_number' => (int) $d['season_number'],
            'name'          => $d['name'],
            'resort_map'    => $d['resort_map'],
            'start_date'    => $d['start_date'],
            'duration_days' => (int) $d['duration_days'],
            'winter_days'   => (int) $d['winter_days'],
            'active'        => 0,
        ]);
        $this->auditLog('create_season', null, $d['name']);
        return redirect()->to('/admin/seasons')->with('success', 'Season planned.');
    }

    public function seasonPlanner(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $seasonsRaw = $db->table('seasons')->orderBy('season_number')->get()->getResultArray();
        $seasons = array_map(function($s) {
            return [
                'id' => (int) $s['id'],
                'season_number' => (int) $s['season_number'],
                'name' => $s['name'],
                'resort_map' => $s['resort_map'],
                'start_date' => $s['start_date'],
                'duration_days' => (int) $s['duration_days'],
                'winter_days' => (int) $s['winter_days'],
                'active' => (bool) $s['active'],
            ];
        }, $seasonsRaw);

        $resortMaps = \App\Controllers\ResortMap::getResortMapNames();

        $page = [
            'seasons' => $seasons,
            'resortMaps' => $resortMaps,
            'nextSeasonNumber' => count($seasons) + 1,
        ];

        return view('admin/seasons', ['page' => $page]);
    }

    public function activateSeason(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/seasons')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $targetSeason = $db->table('seasons')->where('id', $id)->get()->getRowArray();
        if ($targetSeason && function_exists('triggerSeasonRollover')) {
            $rolledOver = triggerSeasonRollover((int) $targetSeason['season_number'], true);
            if ($rolledOver) {
                $this->auditLog('activate_season', null, 'Season #' . $targetSeason['season_number']);
                return redirect()->to('/admin/seasons')->with('success', "Season #{$targetSeason['season_number']} activated, leaderboard archived, and Sector 2 unlocked.");
            }
        }
        $db->table('seasons')->update(['active' => 0]);
        $db->table('seasons')->where('id', $id)->update(['active' => 1, 'maintenance' => 0, 'maintenance_until' => null]);
        $this->auditLog('activate_season', null, 'Season #' . $id);
        return redirect()->to('/admin/seasons')->with('success', 'Season activated.');
    }

    public function enableAllFlags()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/features')->with('error', 'Unauthorized access.');
        db_connect()->table('feature_flags')->update(['enabled' => 2]);
        $this->auditLog('Enabled all feature flags');
        return redirect()->to('/admin/features')->with('success', 'All features enabled for everyone.');
    }

    public function disableAllBeta()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/features')->with('error', 'Unauthorized access.');
        db_connect()->table('feature_flags')->where('flag_key LIKE', 'beta_%')->update(['enabled' => 0]);
        $this->auditLog('Disabled all beta features');
        return redirect()->to('/admin/features')->with('success', 'All beta features disabled.');
    }

    public function activity(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) {
            return redirect()->to("/dashboard")->with('error', 'Unauthorized access.');
        }

        $db = \Config\Database::connect();
        
        $data["logs"] = $db->table("activity_log")
                           ->orderBy("created_at", "DESC")
                           ->limit(200)
                           ->get()
                           ->getResultArray();

        return view("admin/activity", $data);
    }

    public function optimizeStorage()
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin');
        $db = \Config\Database::connect();

        // 1. Prune stale activity logs older than 90 days
        $prunedLogs = $db->table('activity_log')->where('created_at <', date('Y-m-d H:i:s', strtotime('-90 days')))->delete();

        // 2. Prune old financial transactions older than 180 days
        $prunedTrans = $db->table('financial_transactions')->where('created_at <', date('Y-m-d H:i:s', strtotime('-180 days')))->delete();

        // 3. Clear temporary files in WRITEPATH
        $tempFiles = glob(WRITEPATH . 'tmp_*.pdf') ?: [];
        $deletedFiles = 0;
        foreach ($tempFiles as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 3600) {
                @unlink($file);
                $deletedFiles++;
            }
        }

        // 4. Optimize core database tables
        $tables = ['activity_log', 'financial_transactions', 'player_items', 'equipment', 'staff', 'buildings'];
        foreach ($tables as $t) {
            try {
                $db->query("OPTIMIZE TABLE `{$t}`");
            } catch (\Throwable $e) {}
        }

        $this->auditLog('optimize_storage', null, "Cleaned {$deletedFiles} temp files and optimized tables.");
        return redirect()->to('/admin')->with('success', "Storage maintenance complete: optimized tables and cleaned {$deletedFiles} orphaned temporary files.");
    }

    /** Season format vote: results, comments, and the close date */
    public function seasonVote(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $closes = \App\Libraries\SeasonVote::closesAt();
        $level = featureLevel('season_mode_vote');
        $flag = db_connect()->table('feature_flags')->select('id')->where('flag_key', 'season_mode_vote')->get()->getRowArray();

        $page = [
            'flagLevel' => $level,
            'flagId' => $flag ? (int) $flag['id'] : null,
            'closesAt' => $closes ? date('Y-m-d\\TH:i', strtotime($closes)) : '',
            'closesLabel' => \App\Libraries\SeasonVote::closesLabel($closes),
            'open' => $level > 0 && $closes !== null && strtotime($closes) > time(),
            'closed' => $closes !== null && strtotime($closes) <= time(),
            'results' => \App\Libraries\SeasonVote::results(true),
        ];
        return view('admin/season_vote', ['page' => $page]);
    }

    public function saveSeasonVote(): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $raw = trim((string) $this->request->getPost('closes_at'));
        if ($raw === '') {
            setting()->forget('SeasonVote.closesAt');
            $this->auditLog('season_vote_close_date', null, 'removed');
            return redirect()->to('/admin/season-vote')->with('success', 'Close date removed. The vote is closed.');
        }
        $ts = strtotime($raw);
        if (!$ts) return redirect()->to('/admin/season-vote')->with('error', 'That date could not be read. Use the date picker.');
        setting('SeasonVote.closesAt', date('Y-m-d H:i:s', $ts));
        $this->auditLog('season_vote_close_date', null, date('Y-m-d H:i', $ts));
        return redirect()->to('/admin/season-vote')->with('success', 'Votes close on ' . date('j F Y, H:i', $ts) . '.');
    }

    /** GET /admin/search?q= : players by username, email or ID (JSON, at most 10) */
    public function searchPlayers()
    {
        if (!$this->checkAdmin()) return $this->response->setStatusCode(403)->setJSON(['results' => []]);
        $q = trim((string) $this->request->getGet('q'));
        if ($q === '') return $this->response->setJSON(['results' => []]);
        $db = db_connect();
        $b = $db->table('users u')->select('u.id, u.username, ai.secret AS email')
            ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left');
        if (ctype_digit($q)) {
            $b->where('u.id', (int) $q);
        } else {
            $b->groupStart()->like('u.username', $q)->orLike('ai.secret', $q)->groupEnd();
        }
        $rows = $b->orderBy('u.id', 'DESC')->limit(10)->get()->getResultArray();
        return $this->response->setJSON(['results' => array_map(fn($r) => [
            'id' => (int) $r['id'], 'username' => (string) $r['username'], 'email' => (string) ($r['email'] ?? ''),
        ], $rows)]);
    }

    /** POST /admin/user/{id}/adjust : add or take cash or Génépis, with a reason, through the ledger */
    public function adjustBalance(int $userId)
    {
        if (!$this->checkAdmin()) return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        $currency = (string) $this->request->getPost('currency');
        $amount = (int) $this->request->getPost('amount');
        $reason = trim((string) $this->request->getPost('reason'));
        // 180: the reason goes into description/reason columns of 200 after the "Admin adjustment: " prefix
        if (!in_array($currency, ['cash', 'genepis'], true) || $amount === 0 || $reason === '' || mb_strlen($reason) > 180) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Choose cash or Génépis, a non-zero amount and a reason.']);
        }
        if (!db_connect()->table('users')->where('id', $userId)->countAllResults()) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false, 'message' => 'Player not found.']);
        }
        $label = 'Admin adjustment: ' . $reason;
        $ok = true;
        if ($currency === 'cash') {
            if ($amount > 0) \App\Libraries\Ledger::credit($userId, $amount, 'Admin', $label);
            else $ok = \App\Libraries\Ledger::charge($userId, -$amount, 'Admin', $label);
        } else {
            if ($amount > 0) \App\Libraries\Ledger::creditGenepis($userId, $amount, $label);
            else $ok = \App\Libraries\Ledger::chargeGenepis($userId, -$amount, $label);
        }
        // With DBDebug off (production) a failed write doesn't throw; the ledger's transaction rolls back and only transStatus() says so
        if (!db_connect()->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'The change was not saved. Nothing was written.']);
        }
        if (!$ok) {
            return $this->response->setStatusCode(409)->setJSON(['success' => false, 'message' => 'The player does not have that much to take.']);
        }
        $this->auditLog('adjust_' . $currency, $userId, json_encode(['amount' => $amount, 'reason' => $reason]));
        return $this->response->setJSON(['success' => true]);
    }

    /** POST /admin/user/{id}/note : a private admin note on the player */
    public function addNote(int $userId)
    {
        if (!$this->checkAdmin()) return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        $note = trim((string) $this->request->getPost('note'));
        if ($note === '' || mb_strlen($note) > 2000) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'message' => 'Write a note of up to 2000 characters.']);
        }
        $saved = db_connect()->table('admin_notes')->insert([
            'user_id' => $userId, 'admin_id' => (int) auth()->id(), 'note' => $note, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        if (!$saved) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'message' => 'The note was not saved.']);
        }
        $this->auditLog('note', $userId, json_encode(['note' => mb_substr($note, 0, 200)]));
        return $this->response->setJSON(['success' => true]);
    }
}
