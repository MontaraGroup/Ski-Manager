<?php

namespace App\Controllers;

class Settings extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $user = auth()->user();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $units = $finance['units'] ?? session()->get('units') ?? 'metric';
        $resort = session()->get('resort') ?? ['name' => 'My Resort', 'location' => '', 'description' => ''];
        $tutorial = $db->table('tutorial_progress')->where('user_id', $userId)->get()->getRowArray();
        $notifCount = $db->table('notifications')->where('user_id', $userId)->countAllResults();
        $activityCount = $db->table('activity_log')->where('user_id', $userId)->countAllResults();

        $identity = $db->table('auth_identities')->where('user_id', $userId)->where('type', 'email_password')->get()->getRowArray();
        $googleId = $db->table('auth_identities')->where('user_id', $userId)->where('type', 'google')->get()->getRowArray();
        $code = session('account_code');
        $codeFor = is_array($code) && ($code['expires'] ?? 0) > time() ? ($code['purpose'] ?? null) : null;
        $isDemo = \App\Controllers\Demo::isDemoUser();

        $page = [
            'user' => [
                'id' => (int) $userId,
                'username' => $user->username ?? '',
                'email' => $identity['secret'] ?? '',
                'hasGoogle' => !empty($googleId),
                'isDemo' => $isDemo,
            ],
            'resort' => [
                'name' => $resort['name'] ?? 'My Resort',
            ],
            'codeFor' => $codeFor,
            'units' => $units,
            'difficulty' => $finance['difficulty'] ?? 'standard',
            'allowTours' => (bool) ($finance['allow_tours'] ?? true),
            'tutorial' => $tutorial ? [
                'step' => (int) ($tutorial['current_step'] ?? 1),
            ] : null,
            'notifCount' => (int) $notifCount,
            'activityCount' => (int) $activityCount,
            // Players see the results only once voting has closed
            'seasonVote' => \App\Libraries\SeasonVote::state((int) $userId) + [
                'closed' => \App\Libraries\SeasonVote::isClosed(),
                'myComment' => (string) (\App\Libraries\SeasonVote::row((int) $userId)['comment'] ?? ''),
                'results' => \App\Libraries\SeasonVote::isClosed() ? \App\Libraries\SeasonVote::results() : null,
            ],
        ];

        return view('settings/index', ['page' => $page]);
    }

    public function save()
    {
        $userId = auth()->id();
        $units = $this->request->getPost("units");
        if (!in_array($units, ["metric", "imperial"])) $units = "metric";
        db_connect()->table("player_finances")->where("user_id", $userId)->update(["units" => $units]);
        session()->set("units", $units);
        session()->set("currency", $units === "imperial" ? "USD" : "EUR");
        return redirect()->to("/settings")->with("success", "Units & currency updated.");
    }

    public function updateResortName()
    {
        $name = trim($this->request->getPost('resort_name'));
        if (empty($name) || strlen($name) > 50) {
            return redirect()->to('/settings')->with('error', 'Resort name must be 1-50 characters.');
        }
        $resort = session()->get('resort') ?? ['name' => 'My Resort', 'location' => '', 'description' => '', 'altitude' => 'medium', 'aspect' => 'north', 'is_open' => true];
        $resort['name'] = $name;
        session()->set('resort', $resort);
        // Persist it so it survives logout and shows up in season archives
        $db = db_connect();
        if ($db->fieldExists('resort_name', 'player_finances')) {
            $db->table('player_finances')->where('user_id', auth()->id())->update(['resort_name' => $name]);
        }
        log_activity(auth()->id(), 'settings', 'Renamed resort to ' . $name);
        return redirect()->to('/settings')->with('success', 'Resort name updated!');
    }

    public function resetTutorial()
    {
        $userId = auth()->id();
        db_connect()->table('tutorial_progress')->where('user_id', $userId)->delete();
        return redirect()->to('/settings')->with('success', 'Tutorial reset. It will start again on your next page load.');
    }

    public function clearNotifications()
    {
        $userId = auth()->id();
        db_connect()->table('notifications')->where('user_id', $userId)->delete();
        return redirect()->to('/settings')->with('success', 'All notifications cleared.');
    }

    public function clearActivityLog()
    {
        $userId = auth()->id();
        db_connect()->table('activity_log')->where('user_id', $userId)->delete();
        return redirect()->to('/settings')->with('success', 'Activity log cleared.');
    }

    public function difficulty()
    {
        $userId = auth()->id();
        $difficulty = $this->request->getPost('difficulty');
        if (!in_array($difficulty, ['easy', 'standard', 'hard'])) {
            return redirect()->to('/settings')->with('error', 'Invalid difficulty.');
        }
        $db = db_connect();
        $db->table('player_finances')->where('user_id', $userId)->update(['difficulty' => $difficulty]);
        log_activity($userId, 'Settings', 'Changed difficulty to ' . ucfirst($difficulty), 'fa-solid fa-gauge');
        return redirect()->to('/settings')->with('success', 'Difficulty set to ' . ucfirst($difficulty) . '.');
    }
    public function toggleTours()
    {
        $userId = auth()->id();
        $db = db_connect();
        $finance = $db->table("player_finances")->where("user_id", $userId)->get()->getRowArray();
        $newVal = ($finance["allow_tours"] ?? 1) ? 0 : 1;
        $db->table("player_finances")->where("user_id", $userId)->update(["allow_tours" => $newVal]);
        $msg = $newVal ? "Resort tours enabled" : "Resort tours disabled";
        log_activity($userId, "Settings", $msg, "fa-solid fa-binoculars");
        return redirect()->to("/settings")->with("success", $msg . ".");
    }
}
