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
        $bannedUsers = $db->table('users')->where('active', 0)->countAllResults();

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

        return view('admin/index', [
            'totalUsers' => $totalUsers, 'totalStaff' => $totalStaff,
            'totalBuildings' => $totalBuildings, 'totalItems' => $totalItems,
            'totalLoans' => $totalLoans, 'totalCash' => (int) $totalCash,
            'totalParking' => $totalParking, 'totalParks' => $totalParks,
            'gameDay' => $gameDay, 'users' => $users, 'weather' => $weather,
            'recentLogs' => $recentLogs, 'onlineRecent' => $onlineRecent,
            'newUsers7d' => $newUsers7d, 'bannedUsers' => $bannedUsers,
            'season' => $season, 'unreadSupport' => $unreadSupport, 'lastTick' => $lastTick,
            'server' => $server, 'isStaging' => str_contains(ROOTPATH, 'staging'),
        ]);
    }

    public function editUser(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');

        $db = db_connect();
        $user = $db->table('users')->where('id', $id)->get()->getRowArray();
        if (!$user) return redirect()->to('/admin')->with('error', 'User not found.');

        $finance = $db->table('player_finances')->where('user_id', $id)->get()->getRowArray();
        $staff = $db->table('staff')->where('user_id', $id)->where('status !=', 'fired')->get()->getResultArray();
        $buildings = $db->table('buildings')->where('user_id', $id)->get()->getResultArray();
        $items = $db->table('player_items')->where('user_id', $id)->get()->getResultArray();
        $loans = $db->table('loans')->where('user_id', $id)->where('status', 'active')->get()->getResultArray();
        $genepis = $db->table('genepis')->where('user_id', $id)->get()->getRowArray();
        $logs = $db->table('activity_log')->where('user_id', $id)->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        $identity = $db->table('auth_identities')->where('user_id', $id)->where('type', 'email_password')->get()->getRowArray();
        $parking = $db->table('parking')->where('user_id', $id)->get()->getResultArray();
        $terrainParks = $db->table('terrain_parks')->where('user_id', $id)->get()->getResultArray();
        $achievements = $db->table('achievements')->where('user_id', $id)->get()->getResultArray();
        $tutorial = $db->table('tutorial_progress')->where('user_id', $id)->get()->getRowArray();

        return view('admin/user', [
            'user' => $user, 'finance' => $finance, 'staff' => $staff,
            'buildings' => $buildings, 'items' => $items, 'loans' => $loans,
            'genepis' => $genepis, 'logs' => $logs, 'identity' => $identity,
            'parking' => $parking, 'terrainParks' => $terrainParks,
            'achievements' => $achievements, 'tutorial' => $tutorial,
        ]);
    }

    public function updateCash()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $cash = (int) $this->request->getPost('cash');
        db_connect()->table('player_finances')->where('user_id', $userId)->update(['cash' => $cash]);
        log_activity($userId, 'admin_cash', "Admin set cash to " . currency($cash));
        return redirect()->to('/admin/user/' . $userId)->with('success', 'Cash updated.');
    }

    public function updateGenepis()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $amount = (int) $this->request->getPost('genepis');
        db_connect()->table('genepis')->where('user_id', $userId)->update(['balance' => $amount]);
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

    public function activityLog(): string
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
        $logs = $query->orderBy('al.created_at', 'DESC')->limit($perPage, $offset)->get()->getResultArray();

        return view('admin/activity', [
            'logs' => $logs, 'total' => $total, 'page' => $page,
            'perPage' => $perPage, 'filter' => $filter, 'userFilter' => $userFilter,
        ]);
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
            $lines = array_filter($all, fn($l) => !str_contains($l, 'DEBUG') && !str_contains($l, 'Session:'));
            $lines = array_slice(array_values($lines), -30);
        }
        return view('admin/errors', ['lines' => $lines]);
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
        $logs = db_connect()->table('admin_audit_log a')
            ->select('a.*, u.username as admin_name, t.username as target_name')
            ->join('users u', 'u.id = a.admin_id', 'left')
            ->join('users t', 't.id = a.target_user_id', 'left')
            ->orderBy('a.created_at', 'DESC')->limit(100)->get()->getResultArray();
        return view('admin/audit', ['logs' => $logs]);
    }

    public function playerComparison(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $users = $db->table('users')->select('id, username')->orderBy('username')->get()->getResultArray();
        $a = $this->request->getGet('a');
        $b = $this->request->getGet('b');
        $dataA = $dataB = null;
        if ($a) {
            $dataA = $db->table('player_finances')->where('user_id', $a)->get()->getRowArray() ?: [];
            $userA = $db->table('users')->where('id', $a)->get()->getRowArray();
            $dataA['username'] = $userA['username'] ?? '';
            $dataA['staff'] = $db->table('staff')->where('user_id', $a)->where('status !=', 'fired')->countAllResults();
            $dataA['buildings'] = $db->table('buildings')->where('user_id', $a)->countAllResults();
            $dataA['items'] = $db->table('player_items')->where('user_id', $a)->countAllResults();
        }
        if ($b) {
            $dataB = $db->table('player_finances')->where('user_id', $b)->get()->getRowArray() ?: [];
            $userB = $db->table('users')->where('id', $b)->get()->getRowArray();
            $dataB['username'] = $userB['username'] ?? '';
            $dataB['staff'] = $db->table('staff')->where('user_id', $b)->where('status !=', 'fired')->countAllResults();
            $dataB['buildings'] = $db->table('buildings')->where('user_id', $b)->countAllResults();
            $dataB['items'] = $db->table('player_items')->where('user_id', $b)->countAllResults();
        }
        return view('admin/compare', ['users' => $users, 'dataA' => $dataA, 'dataB' => $dataB, 'a' => $a, 'b' => $b]);
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
        $counts = ['everyone' => 0, 'admins' => 0, 'off' => 0, 'unused' => 0];

        foreach ($flagsRaw as $f) {
            $lvl = (int) $f['enabled'];
            if ($lvl === 2) $counts['everyone']++;
            elseif ($lvl === 1) $counts['admins']++;
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
        return $usage;
    }

    /** POST /admin/features/set/{id} with level 0 (off), 1 (admins only) or 2 (everyone) */
    public function setFlag(int $id)
    {
        if (!$this->checkAdmin()) return redirect()->to('/admin/features')->with('error', 'Unauthorized access.');
        $level = (int) $this->request->getPost('level');
        if (!in_array($level, [0, 1, 2], true)) return redirect()->to('/admin/features')->with('error', 'Unknown level.');

        $db = db_connect();
        $flag = $db->table('feature_flags')->where('id', $id)->get()->getRowArray();
        if (!$flag) return redirect()->to('/admin/features')->with('error', 'Flag not found.');

        $labels = ['off', 'admins only', 'everyone'];
        if ((int) $flag['enabled'] !== $level) {
            $db->table('feature_flags')->where('id', $id)->update(['enabled' => $level]);
            $this->auditLog('feature_flag', null, $flag['flag_key'] . ': ' . $labels[(int) $flag['enabled']] . ' -> ' . $labels[$level]);
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
            $new = $isBeta ? ((int) $flag["enabled"] + 1) % 3 : ($flag["enabled"] ? 0 : 2);
            $db->table("feature_flags")->where("id", $id)->update(["enabled" => $new]);
            $this->auditLog('feature_flag', null, $flag['flag_key'] . ' ' . ["off","admin only","everyone"][$new]);
        }
        return redirect()->to('/admin/features')->with('success', 'Flag updated.');
    }

    public function suspiciousActivity(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard')->with('error', 'Unauthorized access.');
        $db = db_connect();
        $suspects = $db->query("
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

        $recent = $db->query("
            SELECT u.id, u.username, f.cash, f.difficulty,
                a1.message as latest, a1.created_at
            FROM users u
            JOIN player_finances f ON f.user_id = u.id
            JOIN activity_log a1 ON a1.user_id = u.id
            WHERE a1.message LIKE '%Admin set cash%'
            ORDER BY a1.created_at DESC
            LIMIT 10
        ")->getResultArray();

        return view('admin/suspicious', ['suspects' => $suspects, 'recent' => $recent]);
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
}
