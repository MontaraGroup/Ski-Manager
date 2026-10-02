<?php

namespace App\Models;

use CodeIgniter\Model;

class AllianceModel extends Model
{
    protected $table = 'alliances';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'tag', 'motto', 'description', 'crest_icon', 'crest_color',
        'founder_id', 'level', 'xp', 'treasury_cash', 'max_members',
        'min_reputation', 'is_recruiting', 'pass_name', 'pass_tier'
    ];
    protected $useTimestamps = true;
    protected $returnType = 'array';

    public const PERKS = [
        'bulk_procurement' => [
            'name'        => 'Bulk Equipment Procurement',
            'icon'        => 'fa-solid fa-truck-monster',
            'desc'        => 'Cooperative volume agreement with PistenBully, Prinoth, and TechnoAlpin for fleet discounts.',
            'levels'      => [
                1 => ['cost' => 75000,  'req_level' => 1, 'effect' => '10% discount on all groomers and snowmakers', 'pct' => 10],
                2 => ['cost' => 175000, 'req_level' => 2, 'effect' => '15% discount on all groomers and snowmakers', 'pct' => 15],
                3 => ['cost' => 350000, 'req_level' => 3, 'effect' => '20% discount on all groomers and snowmakers', 'pct' => 20],
            ]
        ],
        'syndicate_marketing' => [
            'name'        => 'Syndicate Marketing Network',
            'icon'        => 'fa-solid fa-bullhorn',
            'desc'        => 'Cross-promotional alpine advertising campaign boosting visitors across member resorts.',
            'levels'      => [
                1 => ['cost' => 60000,  'req_level' => 1, 'effect' => '+10% visitor bonus to all active marketing campaigns', 'pct' => 10],
                2 => ['cost' => 150000, 'req_level' => 2, 'effect' => '+20% visitor bonus to all active marketing campaigns', 'pct' => 20],
                3 => ['cost' => 300000, 'req_level' => 3, 'effect' => '+30% visitor bonus to all active marketing campaigns', 'pct' => 30],
            ]
        ],
        'meteorology_network' => [
            'name'        => 'Meteorological Satellite Radar',
            'icon'        => 'fa-solid fa-satellite-dish',
            'desc'        => 'Cooperative orbital radar providing microclimate telemetry and enhanced snowmaking yield.',
            'levels'      => [
                1 => ['cost' => 80000,  'req_level' => 2, 'effect' => '+5% snow quality and snowmaking yield', 'pct' => 5],
                2 => ['cost' => 200000, 'req_level' => 3, 'effect' => '+10% snow quality and snowmaking yield', 'pct' => 10],
            ]
        ],
        'mountain_rescue' => [
            'name'        => 'Mountain Rescue Fleet',
            'icon'        => 'fa-solid fa-truck-medical',
            'desc'        => 'Shared helicopter evacuation and fast-response mechanics network.',
            'levels'      => [
                1 => ['cost' => 70000,  'req_level' => 2, 'effect' => '-25% chairlift breakdown downtime', 'pct' => 25],
                2 => ['cost' => 180000, 'req_level' => 3, 'effect' => '-50% chairlift breakdown downtime', 'pct' => 50],
            ]
        ],
        'luxury_hospitality' => [
            'name'        => 'Alpine Hospitality Network',
            'icon'        => 'fa-solid fa-hotel',
            'desc'        => 'Centralized booking partnership for village hotels and slope-side chalets.',
            'levels'      => [
                1 => ['cost' => 100000, 'req_level' => 2, 'effect' => '+8% hotel and restaurant revenue', 'pct' => 8],
                2 => ['cost' => 250000, 'req_level' => 4, 'effect' => '+15% hotel and restaurant revenue', 'pct' => 15],
            ]
        ],
    ];

    public const PASS_TIERS = [
        1 => [
            'name'       => 'Regional Mountain Pass',
            'visitor_pct'=> 5,
            'cost'       => 0,
            'req_level'  => 1,
            'desc'       => 'Base cross-resort pass granting +5% visitor volume across all allied resorts.',
            'icon'       => 'fa-solid fa-id-badge',
            'badge'      => 'badge-primary',
        ],
        2 => [
            'name'       => 'Alpine Collective Pass',
            'visitor_pct'=> 10,
            'cost'       => 100000,
            'req_level'  => 2,
            'desc'       => 'Expanded regional network granting +10% visitor volume and higher guest satisfaction.',
            'icon'       => 'fa-solid fa-medal',
            'badge'      => 'badge-info',
        ],
        3 => [
            'name'       => 'Continental Unlimited Pass',
            'visitor_pct'=> 15,
            'cost'       => 250000,
            'req_level'  => 3,
            'desc'       => 'Major multi-state pass partnership granting +15% visitor volume and VIP interest.',
            'icon'       => 'fa-solid fa-star',
            'badge'      => 'badge-secondary',
        ],
        4 => [
            'name'       => 'Global Icon Pass',
            'visitor_pct'=> 20,
            'cost'       => 500000,
            'req_level'  => 4,
            'desc'       => 'Pinnacle world-class ski pass granting +20% visitor volume and elite reputation boost.',
            'icon'       => 'fa-solid fa-crown',
            'badge'      => 'badge-warning',
        ],
    ];

    public const LEVEL_THRESHOLDS = [
        1 => ['xp' => 0,    'max_members' => 5],
        2 => ['xp' => 150,  'max_members' => 7],
        3 => ['xp' => 500,  'max_members' => 9],
        4 => ['xp' => 1200, 'max_members' => 12],
    ];

    public static function ensureSchema(): void
    {
        $db = db_connect();
        if (!$db->tableExists('alliances')) {
            $migration = new \App\Database\Migrations\CreateResortAlliances();
            $migration->up();
        }
    }
}
