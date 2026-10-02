<?php

namespace App\Controllers;

class Vote extends BaseController
{
    public static function ensureSchema(): void
    {
        $db = db_connect();
        if (!$db->tableExists('resort_votes')) {
            $migration = new \App\Database\Migrations\CreateResortVotesTable();
            $migration->up();
        }
    }

    public function getResortOptions(): array
    {
        return [
            'DeerValley' => [
                'name'         => 'Deer Valley',
                'location'     => 'Park City, Utah',
                'tagline'      => 'Pristine Grooming & Luxury Alpine Hospitality',
                'desc'         => 'Renowned worldwide for meticulously groomed corduroy, ski-only exclusivity, and 5-star mountain dining. A sanctuary for high-net-worth skiers seeking uncompromised luxury.',
                'icon'         => 'fa-solid fa-gem',
                'color'        => 'text-amber-400',
                'border_color' => 'border-amber-400/40',
                'bg_accent'    => 'bg-amber-400/10',
                'bar_color'    => 'bg-amber-400',
                'specs'        => [
                    'vertical' => '3,000 ft',
                    'acres'    => '2,026 ac',
                    'summit'   => '9,570 ft',
                    'snowfall' => '300 in',
                    'difficulty' => [
                        'green'        => 27,
                        'blue'         => 41,
                        'black'        => 28,
                        'double_black' => 4,
                    ],
                ],
                'perk' => [
                    'title' => 'Luxury Haven',
                    'badge' => 'High Wealth Profile',
                    'icon'  => 'fa-solid fa-champagne-glasses',
                    'desc'  => '+15% Lift ticket price tolerance & +25% fine dining restaurant spend.',
                ],
            ],
            'AspenSnowmass' => [
                'name'         => 'Aspen Snowmass',
                'location'     => 'Aspen, Colorado',
                'tagline'      => 'Four Iconic Peaks & World-Class Prestige',
                'desc'         => 'Four interconnected mountains spanning Snowmass, Aspen Mountain, Highlands, and Buttermilk. Blends celebrity culture, extreme chutes, and massive high-alpine terrain.',
                'icon'         => 'fa-solid fa-mountain-sun',
                'color'        => 'text-sky-400',
                'border_color' => 'border-sky-400/40',
                'bg_accent'    => 'bg-sky-400/10',
                'bar_color'    => 'bg-sky-400',
                'specs'        => [
                    'vertical' => '4,406 ft',
                    'acres'    => '5,524 ac',
                    'summit'   => '12,510 ft',
                    'snowfall' => '300 in',
                    'difficulty' => [
                        'green'        => 6,
                        'blue'         => 47,
                        'black'        => 30,
                        'double_black' => 17,
                    ],
                ],
                'perk' => [
                    'title' => 'Four Peak Empire',
                    'badge' => 'Multi-Sector Lodging',
                    'icon'  => 'fa-solid fa-hotel',
                    'desc'  => '+20% Luxury hotel and real estate revenue, +15% celebrity VIP visitor spawn rate.',
                ],
            ],
            'BigSkyCombo' => [
                'name'         => 'Big Sky Resort',
                'location'     => 'Big Sky, Montana',
                'tagline'      => 'The Biggest Skiing in America',
                'desc'         => 'Towering above Montana with the legendary Lone Peak Tram, sweeping wide-open powder bowls, and rugged freeride lines without the crowds.',
                'icon'         => 'fa-solid fa-mountain',
                'color'        => 'text-emerald-400',
                'border_color' => 'border-emerald-400/40',
                'bg_accent'    => 'bg-emerald-400/10',
                'bar_color'    => 'bg-emerald-400',
                'specs'        => [
                    'vertical' => '4,350 ft',
                    'acres'    => '5,850 ac',
                    'summit'   => '11,166 ft',
                    'snowfall' => '400 in',
                    'difficulty' => [
                        'green'        => 15,
                        'blue'         => 25,
                        'black'        => 42,
                        'double_black' => 18,
                    ],
                ],
                'perk' => [
                    'title' => 'Lone Peak Freeride',
                    'badge' => 'Extreme Powder Terrain',
                    'icon'  => 'fa-solid fa-person-skiing',
                    'desc'  => '+25% Expert slope capacity, +30% avalanche control efficiency, +20% gear rental yield.',
                ],
            ],
            'Vail' => [
                'name'         => 'Vail Resort',
                'location'     => 'Vail, Colorado',
                'tagline'      => 'Legendary Back Bowls & Bavarian Village',
                'desc'         => 'Sprawling across seven legendary back bowls and blue-sky basins, anchored by an internationally renowned pedestrian village and luxury retail hub.',
                'icon'         => 'fa-solid fa-crown',
                'color'        => 'text-purple-400',
                'border_color' => 'border-purple-400/40',
                'bg_accent'    => 'bg-purple-400/10',
                'bar_color'    => 'bg-purple-400',
                'specs'        => [
                    'vertical' => '3,450 ft',
                    'acres'    => '5,317 ac',
                    'summit'   => '11,570 ft',
                    'snowfall' => '354 in',
                    'difficulty' => [
                        'green'        => 18,
                        'blue'         => 29,
                        'black'        => 32,
                        'double_black' => 21,
                    ],
                ],
                'perk' => [
                    'title' => 'Legendary Back Bowls',
                    'badge' => 'Massive Footprint & Retail',
                    'icon'  => 'fa-solid fa-shop',
                    'desc'  => '+20% Village commerce and dining spend, +15% global tourist marketing pull.',
                ],
            ],
            'PalisadesTahoe' => [
                'name'         => 'Palisades Tahoe',
                'location'     => 'Olympic Valley, California',
                'tagline'      => 'Olympic Pedigree & Sierra Spring Sunshine',
                'desc'         => 'Host of the 1960 Winter Olympics. Famous for iconic granite cliff bands, deep Sierra cement dumps, vibrant spring skiing, and breathtaking Lake Tahoe views.',
                'icon'         => 'fa-solid fa-sun',
                'color'        => 'text-amber-500',
                'border_color' => 'border-amber-500/40',
                'bg_accent'    => 'bg-amber-500/10',
                'bar_color'    => 'bg-amber-500',
                'specs'        => [
                    'vertical' => '2,850 ft',
                    'acres'    => '6,000 ac',
                    'summit'   => '9,050 ft',
                    'snowfall' => '400 in',
                    'difficulty' => [
                        'green'        => 25,
                        'blue'         => 45,
                        'black'        => 20,
                        'double_black' => 10,
                    ],
                ],
                'perk' => [
                    'title' => 'Olympic Heritage',
                    'badge' => 'Extended Spring Season',
                    'icon'  => 'fa-solid fa-medal',
                    'desc'  => '+25% Spring skiing ticket demand & duration, +15% steep terrain challenge score.',
                ],
            ],
            'Killington' => [
                'name'         => 'Killington',
                'location'     => 'Killington, Vermont',
                'tagline'      => 'The Beast of the East & Monster Snowmaking',
                'desc'         => 'The powerhouse of Eastern skiing across six peaks. Boasts the longest season on the East Coast, unmatched snowmaking firepower, and legendary après-ski energy.',
                'icon'         => 'fa-solid fa-snowflake',
                'color'        => 'text-rose-400',
                'border_color' => 'border-rose-400/40',
                'bg_accent'    => 'bg-rose-400/10',
                'bar_color'    => 'bg-rose-400',
                'specs'        => [
                    'vertical' => '3,050 ft',
                    'acres'    => '1,509 ac',
                    'summit'   => '4,241 ft',
                    'snowfall' => '250 in',
                    'difficulty' => [
                        'green'        => 28,
                        'blue'         => 33,
                        'black'        => 24,
                        'double_black' => 15,
                    ],
                ],
                'perk' => [
                    'title' => 'The Beast of the East',
                    'badge' => 'Snowmaking Titan',
                    'icon'  => 'fa-solid fa-snowflake',
                    'desc'  => '+25% Snowmaking machine efficiency, lowest operational temp limit, earlier season opening.',
                ],
            ],
        ];
    }

    public function index(): string
    {
        self::ensureSchema();
        helper(['season', 'activity', 'time']);

        $userId = auth()->id();
        $db = db_connect();
        $options = $this->getResortOptions();

        $userVote = $db->table('resort_votes')->where('user_id', $userId)->get()->getRowArray();
        $results = $db->table('resort_votes')
            ->select('resort_key, COUNT(*) as votes')
            ->groupBy('resort_key')
            ->orderBy('votes', 'DESC')
            ->get()
            ->getResultArray();

        $voteCounts = [];
        $totalVotes = 0;
        $leadingResort = null;
        $maxVotes = 0;

        foreach (array_keys($options) as $k) {
            $voteCounts[$k] = 0;
        }

        foreach ($results as $r) {
            $cnt = (int) $r['votes'];
            $k = $r['resort_key'];
            if (isset($voteCounts[$k])) {
                $voteCounts[$k] = $cnt;
            }
            $totalVotes += $cnt;
            if ($cnt > $maxVotes) {
                $maxVotes = $cnt;
                $leadingResort = $k;
            }
        }

        // Live recent community votes
        $recentVotes = $db->table('resort_votes')
            ->select('resort_votes.resort_key, resort_votes.created_at, resort_votes.updated_at, users.username')
            ->join('users', 'users.id = resort_votes.user_id', 'left')
            ->orderBy('COALESCE(resort_votes.updated_at, resort_votes.created_at)', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();

        return view('vote/index', [
            'options'        => $options,
            'userVote'       => $userVote,
            'voteCounts'     => $voteCounts,
            'totalVotes'     => $totalVotes,
            'leadingResort'  => $leadingResort,
            'recentVotes'    => $recentVotes,
            'seasonNumber'   => getSeasonNumber(),
            'seasonDay'      => getSeasonDay(),
            'seasonLength'   => getSeasonLength(),
        ]);
    }

    public function cast()
    {
        self::ensureSchema();
        helper(['activity']);

        $userId = auth()->id();
        $db = db_connect();
        $resort = (string) $this->request->getPost('resort');
        $options = $this->getResortOptions();

        if (!isset($options[$resort])) {
            return redirect()->back()->with('error', 'Invalid resort candidate selected.');
        }

        $now = date('Y-m-d H:i:s');
        $existing = $db->table('resort_votes')->where('user_id', $userId)->get()->getRowArray();
        
        if ($existing) {
            $db->table('resort_votes')->where('user_id', $userId)->update([
                'resort_key' => $resort,
                'updated_at' => $now,
            ]);
        } else {
            $db->table('resort_votes')->insert([
                'user_id'    => $userId,
                'resort_key' => $resort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        log_activity($userId, 'Vote', 'Voted for ' . $options[$resort]['name'] . ' as Season 4 resort', 'fa-solid fa-check-to-slot');
        return redirect()->to('/vote')->with('success', 'Your vote for ' . $options[$resort]['name'] . ' has been recorded!');
    }

    public function retract()
    {
        self::ensureSchema();
        helper(['activity']);

        $userId = auth()->id();
        $db = db_connect();
        $existing = $db->table('resort_votes')->where('user_id', $userId)->get()->getRowArray();

        if (!$existing) {
            return redirect()->to('/vote')->with('error', 'You do not have an active vote to retract.');
        }

        $options = $this->getResortOptions();
        $resortName = $options[$existing['resort_key']]['name'] ?? 'resort';

        $db->table('resort_votes')->where('user_id', $userId)->delete();
        log_activity($userId, 'Vote Retracted', 'Retracted Season 4 vote for ' . $resortName, 'fa-solid fa-rotate-left');

        return redirect()->to('/vote')->with('success', 'Your vote for ' . $resortName . ' has been retracted.');
    }
}
