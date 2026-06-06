<?php

namespace App\Controllers;

class Tournaments extends BaseController
{
    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $tournaments = $db->table('tournaments')->where('host_id', $userId)->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();
        $events = $db->table('special_events')->orderBy('game_day', 'DESC')->limit(10)->get()->getResultArray();

        if (empty($events)) {
            $defaults = [
                ['name' => 'Early Bird Bonus', 'desc' => '+20% visitor boost for early registrants', 'type' => 'bonus', 'effect' => 'visitors', 'value' => 20, 'day' => 1, 'duration' => 3],
                ['name' => 'Blizzard Warning', 'desc' => 'Heavy snowfall expected — prepare your slopes!', 'type' => 'weather', 'effect' => 'snow', 'value' => 30, 'day' => 5, 'duration' => 2],
                ['name' => 'Celebrity Visit', 'desc' => 'A famous skier is visiting — reputation boost!', 'type' => 'celebrity', 'effect' => 'reputation', 'value' => 50, 'day' => 8, 'duration' => 1],
                ['name' => 'Equipment Sale', 'desc' => '25% off all new builds this weekend', 'type' => 'sale', 'effect' => 'cost', 'value' => -25, 'day' => 14, 'duration' => 2],
            ];
            foreach ($defaults as $d) {
                $db->table('special_events')->insert(['name' => $d['name'], 'description' => $d['desc'], 'event_type' => $d['type'], 'effect_type' => $d['effect'], 'effect_value' => $d['value'], 'game_day' => $d['day'], 'duration_days' => $d['duration'], 'active' => $gameDay >= $d['day'] && $gameDay < $d['day'] + $d['duration'] ? 1 : 0, 'created_at' => date('Y-m-d H:i:s')]);
            }
            $events = $db->table('special_events')->orderBy('game_day', 'DESC')->limit(10)->get()->getResultArray();
        }

        $tournamentTypes = [
            'slalom' => ['name' => 'Slalom Race', 'icon' => 'fa-solid fa-flag', 'visitors' => 500, 'reputation' => 100, 'cost' => 25000, 'duration' => 1, 'desc' => 'Fast-paced slalom competition'],
            'giant_slalom' => ['name' => 'Giant Slalom', 'icon' => 'fa-solid fa-mountain', 'visitors' => 800, 'reputation' => 200, 'cost' => 50000, 'duration' => 2, 'desc' => 'Major alpine racing event'],
            'downhill' => ['name' => 'Downhill Championship', 'icon' => 'fa-solid fa-person-skiing', 'visitors' => 1200, 'reputation' => 350, 'cost' => 80000, 'duration' => 2, 'desc' => 'High-speed downhill racing'],
            'freestyle' => ['name' => 'Freestyle Competition', 'icon' => 'fa-solid fa-person-snowboarding', 'visitors' => 1000, 'reputation' => 300, 'cost' => 60000, 'duration' => 2, 'desc' => 'Tricks, jumps, and style'],
            'cross_country' => ['name' => 'Cross-Country Marathon', 'icon' => 'fa-solid fa-person-skiing-nordic', 'visitors' => 600, 'reputation' => 150, 'cost' => 30000, 'duration' => 1, 'desc' => 'Endurance skiing event'],
            'winter_festival' => ['name' => 'Winter Festival', 'icon' => 'fa-solid fa-snowflake', 'visitors' => 2000, 'reputation' => 500, 'cost' => 150000, 'duration' => 3, 'desc' => 'Massive multi-day winter celebration'],
            'night_race' => ['name' => 'Night Race', 'icon' => 'fa-solid fa-moon', 'visitors' => 700, 'reputation' => 200, 'cost' => 40000, 'duration' => 1, 'desc' => 'Racing under the lights — requires night skiing'],
            'kids_cup' => ['name' => "Kids' Cup", 'icon' => 'fa-solid fa-child', 'visitors' => 400, 'reputation' => 100, 'cost' => 15000, 'duration' => 1, 'desc' => 'Youth skiing competition'],
        ];

        return view('tournaments/index', [
            'tournaments' => $tournaments,
            'events' => $events,
            'gameDay' => $gameDay,
            'tournamentTypes' => $tournamentTypes,
        ]);
    }

    public function host()
    {
        $userId = auth()->id();
        $db = db_connect();
        $type = $this->request->getPost('type');
        $startDate = getSeasonStartDate();
        $gameDay = max(1, (int)((strtotime(date('Y-m-d')) - strtotime($startDate)) / 86400) + 1);

        $types = [
            'slalom' => ['name' => 'Slalom Race', 'visitors' => 500, 'reputation' => 100, 'cost' => 25000, 'duration' => 1],
            'giant_slalom' => ['name' => 'Giant Slalom', 'visitors' => 800, 'reputation' => 200, 'cost' => 50000, 'duration' => 2],
            'downhill' => ['name' => 'Downhill Championship', 'visitors' => 1200, 'reputation' => 350, 'cost' => 80000, 'duration' => 2],
            'freestyle' => ['name' => 'Freestyle Competition', 'visitors' => 1000, 'reputation' => 300, 'cost' => 60000, 'duration' => 2],
            'cross_country' => ['name' => 'Cross-Country Marathon', 'visitors' => 600, 'reputation' => 150, 'cost' => 30000, 'duration' => 1],
            'winter_festival' => ['name' => 'Winter Festival', 'visitors' => 2000, 'reputation' => 500, 'cost' => 150000, 'duration' => 3],
            'night_race' => ['name' => 'Night Race', 'visitors' => 700, 'reputation' => 200, 'cost' => 40000, 'duration' => 1],
            'kids_cup' => ['name' => "Kids' Cup", 'visitors' => 400, 'reputation' => 100, 'cost' => 15000, 'duration' => 1],
        ];

        if (!isset($types[$type])) {
            return redirect()->back()->with('error', 'Invalid tournament type.');
        }

        $t = $types[$type];

        $db->table('tournaments')->insert([
            'name' => $t['name'],
            'description' => 'Hosted by ' . auth()->user()->username,
            'start_day' => $gameDay + 1,
            'end_day' => $gameDay + $t['duration'],
            'prize_pool' => $t['cost'],
            'metric' => $type,
            'status' => 'upcoming',
            'host_id' => $userId,
            'visitors_boost' => $t['visitors'],
            'reputation_boost' => $t['reputation'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        log_activity($userId, 'Tournament', 'Hosting ' . $t['name'] . ' (starts tomorrow)', 'fa-solid fa-trophy');

        return redirect()->to('/tournaments')->with('success', $t['name'] . ' scheduled! Starts tomorrow. Cost: ' . currency($t['cost']));
    }

    public function cancel(int $id)
    {
        $userId = auth()->id();
        $db = db_connect();
        $t = $db->table('tournaments')->where('id', $id)->where('host_id', $userId)->get()->getRowArray();
        if (!$t) return redirect()->back()->with('error', 'Tournament not found.');

        $db->table('tournaments')->where('id', $id)->update(['status' => 'ended']);
        log_activity($userId, 'Tournament', 'Cancelled ' . $t['name'], 'fa-solid fa-xmark');

        return redirect()->to('/tournaments')->with('success', $t['name'] . ' cancelled.');
    }
}
