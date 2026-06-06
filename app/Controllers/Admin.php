<?php

namespace App\Controllers;

class Admin extends BaseController
{
    private function checkAdmin()
    {
        return auth()->loggedIn() && auth()->id() === 1;
    }

    public function index(): string
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');

        $db = db_connect();
        $totalUsers = $db->table('users')->countAllResults();
        $totalStaff = $db->table('staff')->where('status !=', 'fired')->countAllResults();
        $totalBuildings = $db->table('buildings')->countAllResults();
        $totalItems = $db->table('player_items')->countAllResults();
        $totalLoans = $db->table('loans')->where('status', 'active')->countAllResults();
        $totalCash = $db->table('player_finances')->selectSum('cash')->get()->getRowArray()['cash'] ?? 0;
        $totalParking = $db->table('parking')->countAllResults();
        $totalParks = $db->table('terrain_parks')->countAllResults();

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $weather = $db->table('weather')->orderBy('game_day', 'DESC')->limit(1)->get()->getRowArray();
        $recentLogs = $db->query("SELECT al.*, u.username FROM activity_log al JOIN users u ON u.id = al.user_id ORDER BY al.created_at DESC LIMIT 30")->getResultArray();

        $users = $db->table('users')->orderBy('created_at', 'DESC')->limit(50)->get()->getResultArray();
        foreach ($users as &$u) {
            $fin = $db->table('player_finances')->where('user_id', $u['id'])->get()->getRowArray();
            $u['cash'] = $fin ? (int) $fin['cash'] : 0;
            $u['reputation'] = $fin ? (int) ($fin['reputation'] ?? 0) : 0;
            $u['staff_count'] = $db->table('staff')->where('user_id', $u['id'])->where('status !=', 'fired')->countAllResults();
            $u['building_count'] = $db->table('buildings')->where('user_id', $u['id'])->countAllResults();
            $u['item_count'] = $db->table('player_items')->where('user_id', $u['id'])->countAllResults();
            $u['last_activity'] = $db->table('activity_log')->where('user_id', $u['id'])->orderBy('created_at', 'DESC')->limit(1)->get()->getRowArray();
        }

        $onlineRecent = $db->query("SELECT COUNT(DISTINCT user_id) as cnt FROM activity_log WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)")->getRowArray()['cnt'] ?? 0;

        return view('admin/index', [
            'totalUsers' => $totalUsers, 'totalStaff' => $totalStaff,
            'totalBuildings' => $totalBuildings, 'totalItems' => $totalItems,
            'totalLoans' => $totalLoans, 'totalCash' => (int) $totalCash,
            'totalParking' => $totalParking, 'totalParks' => $totalParks,
            'gameDay' => $gameDay, 'users' => $users, 'weather' => $weather,
            'recentLogs' => $recentLogs, 'onlineRecent' => $onlineRecent,
        ]);
    }

    public function editUser(int $id): string
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

    public function broadcast(): string
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        return view('admin/broadcast');
    }

    public function sendBroadcast()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $message = strip_tags(trim($this->request->getPost('message')));
        $icon = $this->request->getPost('icon') ?: 'fa-solid fa-bullhorn';
        if (empty($message)) return redirect()->back()->with('error', 'Message is empty.');

        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $users = $db->table('users')->get()->getResultArray();
        foreach ($users as $u) {
            $db->table('activity_log')->insert([
                'user_id' => $u['id'], 'game_day' => $gameDay,
                'category' => 'System', 'message' => $message,
                'icon' => $icon, 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return redirect()->to('/admin')->with('success', 'Broadcast sent to ' . count($users) . ' players.');
    }

    public function triggerTick()
    {
        if (!$this->checkAdmin()) return redirect()->to("/admin");
        try {
            exec("php /www/sites/skiv2/ci4/spark game:tick > /dev/null 2>&1 &");
            
            return redirect()->to("/admin")->with("success", "Game tick executed successfully.");
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
        $snowfall = (int) $this->request->getPost('snowfall');

        $db->table('weather')->where('game_day', $gameDay)->update([
            'condition_name' => $condition, 'temp' => $temp,
            'wind' => $wind, 'snowfall' => $snowfall,
        ]);

        return redirect()->to('/admin/settings')->with('success', "Weather updated for day {$gameDay}.");
    }

    public function grantAchievement()
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $userId = (int) $this->request->getPost('user_id');
        $achievementId = (int) $this->request->getPost('achievement_id');
        db_connect()->table('achievements')->where('id', $achievementId)->where('user_id', $userId)->update(['completed' => 1, 'progress' => db_connect()->table('achievements')->where('id', $achievementId)->get()->getRowArray()['target'] ?? 100]);
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

    public function gameSettings(): string
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();
        $weather = $db->table('weather')->orderBy('game_day', 'DESC')->limit(7)->get()->getResultArray();
        $segments = $db->table('map_segments')->where('active', 1)->get()->getResultArray();
        $tournaments = $db->table('tournaments')->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();
        $events = $db->table('special_events')->orderBy('game_day', 'DESC')->limit(10)->get()->getResultArray();

        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        return view('admin/settings', [
            'weather' => $weather, 'segments' => $segments,
            'tournaments' => $tournaments, 'events' => $events,
            'gameDay' => $gameDay,
        ]);
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

    public function economy(): string
    {
        if (!$this->checkAdmin()) return redirect()->to('/dashboard');
        $db = db_connect();

        $finances = $db->table('player_finances')->get()->getResultArray();
        $totalCash = array_sum(array_column($finances, 'cash'));
        $totalIncome = array_sum(array_column($finances, 'total_income'));
        $totalExpenses = array_sum(array_column($finances, 'total_expenses'));
        $avgCash = count($finances) > 0 ? round($totalCash / count($finances)) : 0;

        $richest = $db->query("SELECT pf.*, u.username FROM player_finances pf JOIN users u ON u.id = pf.user_id ORDER BY pf.cash DESC LIMIT 10")->getResultArray();
        $recentTransactions = $db->table('financial_transactions')->orderBy('created_at', 'DESC')->limit(30)->get()->getResultArray();

        $loanStats = $db->table('loans')->where('status', 'active')->get()->getResultArray();
        $totalDebt = array_sum(array_column($loanStats, 'remaining'));

        return view('admin/economy', [
            'totalCash' => $totalCash, 'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses, 'avgCash' => $avgCash,
            'richest' => $richest, 'recentTransactions' => $recentTransactions,
            'totalDebt' => $totalDebt, 'playerCount' => count($finances),
        ]);
    }
}
