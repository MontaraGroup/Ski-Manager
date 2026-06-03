<?php

namespace App\Controllers;

class Settings extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $finance = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $units = $finance['units'] ?? session()->get('units') ?? 'metric';
        $resort = session()->get('resort') ?? ['name' => 'My Resort', 'location' => '', 'description' => ''];
        $tutorial = $db->table('tutorial_progress')->where('user_id', $userId)->get()->getRowArray();
        $notifCount = $db->table('notifications')->where('user_id', $userId)->countAllResults();
        $activityCount = $db->table('activity_log')->where('user_id', $userId)->countAllResults();

        return view('settings/index', [
            'units' => $units,
            'resort' => $resort,
            'tutorial' => $tutorial,
            'notifCount' => $notifCount,
            'activityCount' => $activityCount,
        ]);
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
        log_activity(auth()->id(), 'settings', 'Renamed resort to ' . $name);
        return redirect()->to('/settings')->with('success', 'Resort name updated!');
    }

    public function resetTutorial()
    {
        $userId = auth()->id();
        db_connect()->table('tutorial_progress')->where('user_id', $userId)->delete();
        return redirect()->to('/settings')->with('success', 'Tutorial reset — it will start again on your next page load.');
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
}
