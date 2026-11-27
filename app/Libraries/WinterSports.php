<?php

namespace App\Libraries;

/**
 * Other winter sport facilities (ice rink, ice hockey venue, ski jump ramp, bobsleigh track).
 * Guests pay to use or watch events at these venues, and open venues unlock dedicated
 * winter-sport tournaments on /tournaments. Behind PostHog flag FLAG.
 */
class WinterSports
{
    public const FLAG = 'winter-sports';
    public const MAX_LEVEL = 3;
    public const MAX_PER_TYPE = 2;

    /**
     * Facility catalog: build cost, base daily upkeep, guest capacity, ticket price bounds,
     * daily visitor draw, and level names (1..3).
     */
    public const CATALOG = [
        'ice_rink' => [
            'name' => 'Ice Rink',
            'icon' => 'fa-snowflake',
            'desc' => 'Outdoor skating rink for families and evening guests.',
            'build_cost' => 35000,
            'upkeep' => 600,
            'capacity' => 180,
            'default_price' => 18,
            'min_price' => 5,
            'max_price' => 60,
            'visitor_share' => 0.22,
            'spectator_draw' => 40,
            'summer_factor' => 0.45,
            'wear_per_day' => 1.5,
            'levels' => [
                1 => 'Outdoor Ice Rink',
                2 => 'Covered Ice Rink',
                3 => 'Alpine Ice Pavilion',
            ],
            'event_key' => 'ice_gala',
            'event_name' => 'Winter Ice Gala',
        ],
        'cross_country' => [
            'name' => 'Cross-Country Skiing',
            'icon' => 'fa-person-skiing-nordic',
            'desc' => 'Groomed Nordic trails and trailhead lodge for endurance skiers.',
            'build_cost' => 55000,
            'upkeep' => 850,
            'capacity' => 260,
            'default_price' => 22,
            'min_price' => 8,
            'max_price' => 65,
            'visitor_share' => 0.24,
            'spectator_draw' => 65,
            'summer_factor' => 0.30,
            'wear_per_day' => 1.6,
            'levels' => [
                1 => 'Nordic Loop Trail',
                2 => 'Groomed Cross-Country Network',
                3 => 'Nordic Ski Center',
            ],
            'event_key' => 'cross_country',
            'event_name' => 'Cross-Country Marathon',
        ],
        'hockey_arena' => [
            'name' => 'Ice Hockey Venue',
            'icon' => 'fa-hockey-puck',
            'desc' => 'Covered rink and stands for league games and spectator crowds.',
            'build_cost' => 95000,
            'upkeep' => 1600,
            'capacity' => 350,
            'default_price' => 28,
            'min_price' => 10,
            'max_price' => 90,
            'visitor_share' => 0.26,
            'spectator_draw' => 85,
            'summer_factor' => 0.45,
            'wear_per_day' => 2.0,
            'levels' => [
                1 => 'Community Hockey Rink',
                2 => 'Regional Ice Arena',
                3 => 'Championship Hockey Venue',
            ],
            'event_key' => 'hockey_classic',
            'event_name' => 'Ice Hockey Classic',
        ],
        'ski_jump' => [
            'name' => 'Ski Jump Ramp',
            'icon' => 'fa-person-skiing',
            'desc' => 'High-altitude jump hill drawing spectators and aerial competitions.',
            'build_cost' => 140000,
            'upkeep' => 2200,
            'capacity' => 420,
            'default_price' => 32,
            'min_price' => 10,
            'max_price' => 110,
            'visitor_share' => 0.28,
            'spectator_draw' => 110,
            'summer_factor' => 0.25,
            'wear_per_day' => 2.2,
            'levels' => [
                1 => 'Normal Hill (K90)',
                2 => 'Large Hill (K120)',
                3 => 'Ski Flying Hill',
            ],
            'event_key' => 'ski_jump_cup',
            'event_name' => 'Ski Jumping Cup',
        ],
        'bobsleigh_track' => [
            'name' => 'Bobsleigh Track',
            'icon' => 'fa-sleigh',
            'desc' => 'Refrigerated ice chute for guest rides and high-speed cup racing.',
            'build_cost' => 220000,
            'upkeep' => 3400,
            'capacity' => 480,
            'default_price' => 45,
            'min_price' => 15,
            'max_price' => 150,
            'visitor_share' => 0.30,
            'spectator_draw' => 150,
            'summer_factor' => 0.45,
            'wear_per_day' => 2.5,
            'levels' => [
                1 => 'Club Ice Chute',
                2 => 'National Bobsleigh Track',
                3 => 'Olympic Bobsleigh Track',
            ],
            'event_key' => 'bobsleigh_gp',
            'event_name' => 'Bobsleigh Grand Prix',
        ],
    ];

    public static function enabledFor(int $userId): bool
    {
        return function_exists('featureEnabledFor') && featureEnabledFor(self::FLAG, $userId);
    }

    public static function levelMultiplier(int $level): float
    {
        return match (max(1, min(self::MAX_LEVEL, $level))) {
            1 => 1.0,
            2 => 1.5,
            default => 2.1,
        };
    }

    public static function upkeepFor(string $type, int $level): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $mult = match (max(1, min(self::MAX_LEVEL, $level))) {
            1 => 1.0,
            2 => 1.45,
            default => 1.95,
        };
        return (int) round($cfg['upkeep'] * $mult);
    }

    public static function capacityFor(string $type, int $level): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        return (int) round($cfg['capacity'] * self::levelMultiplier($level));
    }

    public static function upgradeCost(string $type, int $currentLevel): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg || $currentLevel >= self::MAX_LEVEL) return 0;
        $factor = $currentLevel === 1 ? 0.85 : 1.35;
        return (int) round($cfg['build_cost'] * $factor);
    }

    public static function investedValue(string $type, int $level): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $total = (int) $cfg['build_cost'];
        for ($l = 1; $l < min(self::MAX_LEVEL, $level); $l++) {
            $total += self::upgradeCost($type, $l);
        }
        return $total;
    }

    public static function salvageValue(string $type, int $level): int
    {
        return (int) round(self::investedValue($type, $level) * 0.5);
    }

    public static function repairCost(string $type, int $conditionPct): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $missing = max(0, 100 - min(100, $conditionPct));
        if ($missing === 0) return 0;
        return max(250, (int) round($cfg['build_cost'] * 0.0025 * $missing));
    }

    /**
     * Pure daily revenue & guest count estimate/settlement for one open facility.
     * @return array{guests: int, revenue: int, upkeep: int, visitorBoost: int}
     */
    public static function settleDay(array $facility, int $resortVisitors, bool $isWinter = true): array
    {
        $type = (string) ($facility['facility_type'] ?? '');
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg || ($facility['status'] ?? '') !== 'open') {
            return ['guests' => 0, 'revenue' => 0, 'upkeep' => 0, 'visitorBoost' => 0];
        }

        $level = max(1, min(self::MAX_LEVEL, (int) ($facility['level'] ?? 1)));
        $condition = max(0, min(100, (int) ($facility['condition_pct'] ?? 100)));
        $price = max((int) $cfg['min_price'], min((int) $cfg['max_price'], (int) ($facility['ticket_price'] ?? $cfg['default_price'])));

        $capacity = self::capacityFor($type, $level);
        $upkeep = self::upkeepFor($type, $level);
        $levelMult = self::levelMultiplier($level);

        $baseDraw = (int) round($cfg['spectator_draw'] * $levelMult);
        $seasonFactor = $isWinter ? 1.0 : (float) $cfg['summer_factor'];
        $condFactor = $condition / 100;

        // Higher prices reduce demand smoothly so players optimize price rather than maxing it
        $ratio = $price / max(1, (int) $cfg['default_price']);
        $priceElasticity = $ratio <= 1.0 ? min(1.15, 1.0 + (1.0 - $ratio) * 0.25) : max(0.2, 1.0 / pow($ratio, 1.35));

        $rawDemand = ($resortVisitors * (float) $cfg['visitor_share'] * $levelMult + $baseDraw) * $seasonFactor * $condFactor * $priceElasticity;
        $guests = min($capacity, max(0, (int) round($rawDemand)));
        $revenue = $guests * $price;
        $visitorBoost = (int) round($baseDraw * $seasonFactor * $condFactor);

        return [
            'guests' => $guests,
            'revenue' => $revenue,
            'upkeep' => $upkeep,
            'visitorBoost' => $visitorBoost,
        ];
    }

    /** Total extra visitors drawn by all open winter sport facilities */
    public static function totalVisitorBoost(array $facilities, bool $isWinter = true): int
    {
        $boost = 0;
        foreach ($facilities as $f) {
            if (($f['status'] ?? '') !== 'open') continue;
            $type = (string) ($f['facility_type'] ?? '');
            $cfg = self::CATALOG[$type] ?? null;
            if (!$cfg) continue;
            $level = max(1, min(self::MAX_LEVEL, (int) ($f['level'] ?? 1)));
            $cond = max(0, min(100, (int) ($f['condition_pct'] ?? 100))) / 100;
            $season = $isWinter ? 1.0 : (float) $cfg['summer_factor'];
            $boost += (int) round($cfg['spectator_draw'] * self::levelMultiplier($level) * $season * $cond);
        }
        return $boost;
    }
}
