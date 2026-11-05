<?php

namespace App\Controllers;

class VipGuests extends BaseController
{
    public const VIP_TYPES = [
        'celebrity' => [
            'names' => ['Shaun White', 'Lindsey Vonn', 'Mikaela Shiffrin', 'Bode Miller', 'Marcel Hirscher', 'Aksel Svindal'],
            'icon' => 'fa-solid fa-star',
            'color' => 'text-warning',
            'requirements' => ['min_slopes' => 2, 'min_staff' => 3],
            'reward' => 25000,
            'rep_bonus' => 15,
            'duration' => 3,
            'rarity' => 15,
        ],
        'film_crew' => [
            'names' => ['ESPN Film Crew', 'Red Bull Media', 'GoPro Team', 'Warren Miller Productions', 'Matchstick Productions'],
            'icon' => 'fa-solid fa-video',
            'color' => 'text-error',
            'requirements' => ['min_slopes' => 3, 'min_terrain_parks' => 1],
            'reward' => 40000,
            'rep_bonus' => 25,
            'duration' => 2,
            'rarity' => 8,
        ],
        'influencer' => [
            'names' => ['@SkiLifeDaily', '@PowderHound', '@AlpineVibes', '@SlopeStyle_', '@MountainMoments'],
            'icon' => 'fa-solid fa-hashtag',
            'color' => 'text-info',
            'requirements' => ['min_buildings' => 1, 'min_staff' => 2],
            'reward' => 15000,
            'rep_bonus' => 20,
            'duration' => 1,
            'rarity' => 25,
        ],
        'royalty' => [
            'names' => ['Prince Henrik of Norway', 'Duchess of Chamonix', 'Baron von Winterberg', 'Countess Alpina'],
            'icon' => 'fa-solid fa-crown',
            'color' => 'text-secondary',
            'requirements' => ['min_slopes' => 4, 'min_buildings' => 2, 'min_staff' => 5],
            'reward' => 75000,
            'rep_bonus' => 40,
            'duration' => 2,
            'rarity' => 3,
        ],
        'royal_family' => [
            'names' => ['Prince Henrik of Norway', 'Duchess of Chamonix', 'Baron von Winterberg', 'Countess Alpina'],
            'icon' => 'fa-solid fa-crown',
            'color' => 'text-secondary',
            'requirements' => ['min_slopes' => 4, 'min_buildings' => 2, 'min_staff' => 5],
            'reward' => 75000,
            'rep_bonus' => 40,
            'duration' => 2,
            'rarity' => 3,
        ],
        'journalist' => [
            'names' => ['National Geographic Travel', 'Ski Magazine Editor', 'Alpine Journal', 'The Telegraph Snow Desk', 'Snowboarder Mag'],
            'icon' => 'fa-solid fa-newspaper',
            'color' => 'text-accent',
            'requirements' => ['min_slopes' => 2, 'min_buildings' => 1],
            'reward' => 12000,
            'rep_bonus' => 20,
            'duration' => 2,
            'rarity' => 20,
        ],
        'corporate' => [
            'names' => ['Tech Venture Summit', 'Alpine Finance Forum', 'Global Retail Retreat', 'Nordic BioTech Group', 'Summit Capital Partners'],
            'icon' => 'fa-solid fa-briefcase',
            'color' => 'text-neutral-content',
            'requirements' => ['min_buildings' => 2, 'min_hotels' => 1],
            'reward' => 50000,
            'rep_bonus' => 10,
            'duration' => 3,
            'rarity' => 10,
        ],
        'ski_team' => [
            'names' => ['Swiss National Team', 'Austrian Ski Federation', 'French Alpine Team', 'US Ski Team', 'Canadian Ski Cross'],
            'icon' => 'fa-solid fa-flag',
            'color' => 'text-primary',
            'requirements' => ['min_slopes' => 3, 'min_lifts' => 2],
            'reward' => 35000,
            'rep_bonus' => 10,
            'duration' => 3,
            'rarity' => 12,
        ],
    ];

    public function index(): string
    {
        $userId = auth()->id();
        $db = db_connect();

        $activeVipsRaw = $db->table('vip_guests')->where('user_id', $userId)->whereIn('status', ['arriving', 'visiting'])->get()->getResultArray();
        $pastVipsRaw = $db->table('vip_guests')->where('user_id', $userId)->whereIn('status', ['satisfied', 'disappointed', 'departed'])->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();

        $satisfied = array_filter($pastVipsRaw, fn($v) => in_array($v['status'], ['satisfied', 'departed']));
        $totalEarned = array_sum(array_map(fn($v) => (int) $v['reward_amount'], $satisfied));

        $activeVips = array_map(function($vip) {
            $type = self::VIP_TYPES[$vip['vip_type']] ?? null;
            $duration = (int) ($type['duration'] ?? max(1, (int) $vip['days_remaining']));
            $daysLeft = (int) $vip['days_remaining'];
            $stayPct = $duration > 0 ? (int) round(($duration - $daysLeft) / $duration * 100) : 0;
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $type['icon'] ?? 'star');

            $reqs = [];
            foreach (($type['requirements'] ?? []) as $k => $v) {
                $reqs[] = $v . ' ' . str_replace(['min_', '_'], ['', ' '], $k);
            }

            return [
                'id' => (int) $vip['id'],
                'name' => $vip['name'],
                'vip_type' => $vip['vip_type'],
                'typeName' => ucwords(str_replace('_', ' ', $vip['vip_type'])),
                'status' => $vip['status'],
                'rewardFormatted' => currency((int) $vip['reward_amount']),
                'reputation_bonus' => (int) $vip['reputation_bonus'],
                'requirements' => implode(', ', $reqs),
                'days_remaining' => $daysLeft,
                'game_day_arrived' => (int) $vip['game_day_arrived'],
                'stayPct' => $stayPct,
                'icon' => $cleanIcon,
                'color' => $type['color'] ?? 'text-warning',
            ];
        }, $activeVipsRaw);

        $pastVips = array_map(function($vip) {
            $type = self::VIP_TYPES[$vip['vip_type']] ?? null;
            $ok = in_array($vip['status'], ['satisfied', 'departed']);
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $type['icon'] ?? 'star');

            return [
                'id' => (int) $vip['id'],
                'name' => $vip['name'],
                'vip_type' => $vip['vip_type'],
                'typeName' => ucwords(str_replace('_', ' ', $vip['vip_type'])),
                'status' => $vip['status'] === 'departed' ? 'Satisfied' : ucfirst($vip['status']),
                'isSatisfied' => $ok,
                'rewardFormatted' => $ok ? currency((int) $vip['reward_amount']) : '—',
                'reputation_bonus' => $ok ? ('+' . (int) $vip['reputation_bonus']) : '0',
                'game_day_arrived' => (int) $vip['game_day_arrived'],
                'icon' => $cleanIcon,
                'color' => $type['color'] ?? 'text-warning',
            ];
        }, $pastVipsRaw);

        $typesCatalog = [];
        foreach (self::VIP_TYPES as $key => $type) {
            $rarity = (int) ($type['rarity'] ?? 0);
            $rLabel = $rarity >= 20 ? 'Common' : ($rarity >= 10 ? 'Uncommon' : ($rarity >= 5 ? 'Rare' : 'Legendary'));
            $cleanIcon = str_replace(['fa-solid ', 'fa-regular ', 'fa-brands '], '', $type['icon'] ?? 'star');

            $reqs = [];
            foreach (($type['requirements'] ?? []) as $k => $v) {
                $reqs[] = $v . ' ' . str_replace(['min_', '_'], ['', ' '], $k);
            }

            $typesCatalog[] = [
                'key' => $key,
                'name' => ucwords(str_replace('_', ' ', $key)),
                'rarityLabel' => $rLabel,
                'rarity' => $rarity,
                'rewardFormatted' => currency((int) $type['reward']),
                'rep_bonus' => (int) $type['rep_bonus'],
                'duration' => (int) $type['duration'],
                'requirements' => implode(', ', $reqs),
                'icon' => $cleanIcon,
                'color' => $type['color'] ?? 'text-warning',
            ];
        }

        $page = [
            'activeCount' => count($activeVips),
            'satisfiedCount' => count($satisfied),
            'totalEarnedFormatted' => currency($totalEarned),
            'activeVips' => $activeVips,
            'pastVips' => $pastVips,
            'typesCatalog' => $typesCatalog,
        ];

        return view('vip_guests/index', ['page' => $page]);
    }
}
