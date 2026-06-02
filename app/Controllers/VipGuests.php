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

        $activeVips = $db->table('vip_guests')->where('user_id', $userId)->whereIn('status', ['arriving', 'visiting'])->get()->getResultArray();
        $pastVips = $db->table('vip_guests')->where('user_id', $userId)->whereIn('status', ['satisfied', 'disappointed', 'departed'])->orderBy('created_at', 'DESC')->limit(10)->get()->getResultArray();

        return view('vip_guests/index', [
            'activeVips' => $activeVips,
            'pastVips' => $pastVips,
            'vipTypes' => self::VIP_TYPES,
        ]);
    }
}
