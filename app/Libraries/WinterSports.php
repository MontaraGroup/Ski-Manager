<?php

namespace App\Libraries;

/**
 * Other winter sport activities & facilities (ice rink, cross-country skiing, ice hockey venue,
 * ski jump ramp, bobsleigh track). Guests pay entry/pass fees plus optional gear rentals and
 * concessions; venues support level upgrades, add-on upgrades, session modes, surface quality,
 * and unlock dedicated events on /tournaments. Behind PostHog flag FLAG.
 */
class WinterSports
{
    public const FLAG = 'winter-sports';
    public const MAX_LEVEL = 3;
    public const MAX_PER_TYPE = 2;

    public const SESSION_MODES = [
        'standard' => ['label' => 'Daytime', 'guest_mult' => 1.0, 'upkeep_mult' => 1.0, 'wear_mult' => 1.0, 'desc' => 'Standard daytime operating hours.'],
        'extended' => ['label' => 'Day + Evening', 'guest_mult' => 1.25, 'upkeep_mult' => 1.25, 'wear_mult' => 1.20, 'desc' => 'Open into the evening (+25% guests, +25% upkeep). Needs Floodlights or indoor arena.'],
        'training' => ['label' => 'Club Training', 'guest_mult' => 0.80, 'upkeep_mult' => 0.85, 'wear_mult' => 0.65, 'desc' => 'Lower wear and upkeep with steady club bookings.'],
    ];

    public const UPGRADES = [
        'floodlights' => [
            'name' => 'Evening Floodlights',
            'icon' => 'fa-lightbulb',
            'cost' => 18000,
            'upkeep' => 180,
            'desc' => 'Unlocks Day + Evening sessions (+25% guest capacity).',
        ],
        'rental_booth' => [
            'name' => 'Gear & Skate Rental',
            'icon' => 'fa-person-skating',
            'cost' => 24000,
            'upkeep' => 220,
            'desc' => 'Adds per-guest rental income from skates, Nordic skis, or helmets.',
        ],
        'concessions' => [
            'name' => 'Warm-Up Stands',
            'icon' => 'fa-mug-hot',
            'cost' => 20000,
            'upkeep' => 160,
            'desc' => 'Hot drinks & spectator snacks (+25 visitor draw and concession spend).',
        ],
        'pro_surface' => [
            'name' => 'Pro Surface & Cooling',
            'icon' => 'fa-snowflake',
            'cost' => 32000,
            'upkeep' => 260,
            'desc' => 'Cuts daily wear by 45% and keeps outdoor ice/snow crisp in mild weather.',
        ],
    ];

    /**
     * Facility catalog: build cost, base daily upkeep, guest capacity, ticket price bounds,
     * daily visitor draw, rental spend, weather profile, and level names (1..3).
     */
    public const CATALOG = [
        'ice_rink' => [
            'name' => 'Ice Rink',
            'icon' => 'fa-snowflake',
            'desc' => 'Skating rink for families, lessons, and evening sessions.',
            'surface' => 'Ice Sheet',
            'weather' => 'Outdoor (mild thaw risk > 4°C)',
            'indoor' => false,
            'build_cost' => 35000,
            'upkeep' => 600,
            'capacity' => 180,
            'default_price' => 18,
            'min_price' => 5,
            'max_price' => 60,
            'rental_per_guest' => 8,
            'concession_per_guest' => 5,
            'visitor_share' => 0.22,
            'spectator_draw' => 40,
            'summer_factor' => 0.45,
            'wear_per_day' => 1.5,
            'resurface_cost' => 350,
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
            'desc' => 'Groomed Nordic classic & skate loops with a trailhead warming lodge.',
            'surface' => 'Groomed Track',
            'weather' => 'Outdoor trail network',
            'indoor' => false,
            'build_cost' => 55000,
            'upkeep' => 850,
            'capacity' => 260,
            'default_price' => 22,
            'min_price' => 8,
            'max_price' => 65,
            'rental_per_guest' => 11,
            'concession_per_guest' => 6,
            'visitor_share' => 0.24,
            'spectator_draw' => 65,
            'summer_factor' => 0.30,
            'wear_per_day' => 1.6,
            'resurface_cost' => 450,
            'levels' => [
                1 => 'Nordic Loop Trail (5 km)',
                2 => 'Groomed Cross-Country Network (15 km)',
                3 => 'Nordic Ski Center (30 km)',
            ],
            'event_key' => 'cross_country',
            'event_name' => 'Cross-Country Marathon',
        ],
        'hockey_arena' => [
            'name' => 'Ice Hockey Venue',
            'icon' => 'fa-hockey-puck',
            'desc' => 'Indoor arena with spectator stands for league games and tournaments.',
            'surface' => 'Arena Ice',
            'weather' => 'Indoor (all-weather)',
            'indoor' => true,
            'build_cost' => 95000,
            'upkeep' => 1600,
            'capacity' => 350,
            'default_price' => 28,
            'min_price' => 10,
            'max_price' => 90,
            'rental_per_guest' => 9,
            'concession_per_guest' => 9,
            'visitor_share' => 0.26,
            'spectator_draw' => 85,
            'summer_factor' => 0.55,
            'wear_per_day' => 2.0,
            'resurface_cost' => 600,
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
            'desc' => 'Spectator jump hill for aerial training and international cup meets.',
            'surface' => 'In-Run & Landing',
            'weather' => 'Outdoor (wind sensitive >= 45 km/h)',
            'indoor' => false,
            'build_cost' => 140000,
            'upkeep' => 2200,
            'capacity' => 420,
            'default_price' => 32,
            'min_price' => 10,
            'max_price' => 110,
            'rental_per_guest' => 10,
            'concession_per_guest' => 10,
            'visitor_share' => 0.28,
            'spectator_draw' => 110,
            'summer_factor' => 0.25,
            'wear_per_day' => 2.2,
            'resurface_cost' => 800,
            'levels' => [
                1 => 'Normal Hill (K90)',
                2 => 'Large Hill (K120)',
                3 => 'Ski Flying Hill (HS225)',
            ],
            'event_key' => 'ski_jump_cup',
            'event_name' => 'Ski Jumping Cup',
        ],
        'bobsleigh_track' => [
            'name' => 'Bobsleigh Track',
            'icon' => 'fa-sleigh',
            'desc' => 'Refrigerated ice chute for guest passenger runs and world-cup racing.',
            'surface' => 'Refrigerated Chute',
            'weather' => 'Refrigerated outdoor track',
            'indoor' => false,
            'build_cost' => 220000,
            'upkeep' => 3400,
            'capacity' => 480,
            'default_price' => 45,
            'min_price' => 15,
            'max_price' => 150,
            'rental_per_guest' => 15,
            'concession_per_guest' => 11,
            'visitor_share' => 0.30,
            'spectator_draw' => 150,
            'summer_factor' => 0.45,
            'wear_per_day' => 2.5,
            'resurface_cost' => 1100,
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

    /** Parse stored upgrades string (comma-separated or JSON array) into valid upgrade keys */
    public static function parseUpgrades(?string $raw): array
    {
        if (!$raw) return [];
        $decoded = json_decode($raw, true);
        $list = is_array($decoded) ? $decoded : explode(',', $raw);
        $valid = [];
        foreach ($list as $key) {
            $k = trim((string) $key);
            if ($k !== '' && isset(self::UPGRADES[$k]) && !in_array($k, $valid, true)) {
                $valid[] = $k;
            }
        }
        return $valid;
    }

    public static function levelMultiplier(int $level): float
    {
        return match (max(1, min(self::MAX_LEVEL, $level))) {
            1 => 1.0,
            2 => 1.5,
            default => 2.1,
        };
    }

    public static function upkeepFor(string $type, int $level, array $upgrades = [], string $sessionMode = 'standard'): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $mult = match (max(1, min(self::MAX_LEVEL, $level))) {
            1 => 1.0,
            2 => 1.45,
            default => 1.95,
        };
        $base = (int) round($cfg['upkeep'] * $mult);
        foreach ($upgrades as $u) {
            $base += (int) (self::UPGRADES[$u]['upkeep'] ?? 0);
        }
        $modeMult = (float) (self::SESSION_MODES[$sessionMode]['upkeep_mult'] ?? 1.0);
        return (int) round($base * $modeMult);
    }

    public static function capacityFor(string $type, int $level, string $sessionMode = 'standard'): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $modeMult = (float) (self::SESSION_MODES[$sessionMode]['guest_mult'] ?? 1.0);
        return (int) round($cfg['capacity'] * self::levelMultiplier($level) * $modeMult);
    }

    public static function upgradeCost(string $type, int $currentLevel): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg || $currentLevel >= self::MAX_LEVEL) return 0;
        $factor = $currentLevel === 1 ? 0.85 : 1.35;
        return (int) round($cfg['build_cost'] * $factor);
    }

    public static function investedValue(string $type, int $level, array $upgrades = []): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $total = (int) $cfg['build_cost'];
        for ($l = 1; $l < min(self::MAX_LEVEL, $level); $l++) {
            $total += self::upgradeCost($type, $l);
        }
        foreach ($upgrades as $u) {
            $total += (int) (self::UPGRADES[$u]['cost'] ?? 0);
        }
        return $total;
    }

    public static function salvageValue(string $type, int $level, array $upgrades = []): int
    {
        return (int) round(self::investedValue($type, $level, $upgrades) * 0.5);
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
     * @return array{guests: int, ticketRevenue: int, rentalRevenue: int, concessionRevenue: int, revenue: int, upkeep: int, visitorBoost: int, weatherNote: ?string}
     */
    public static function settleDay(array $facility, int $resortVisitors, bool $isWinter = true, ?int $tempC = null, ?int $windKmh = null): array
    {
        $type = (string) ($facility['facility_type'] ?? '');
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg || ($facility['status'] ?? '') !== 'open') {
            return [
                'guests' => 0, 'ticketRevenue' => 0, 'rentalRevenue' => 0, 'concessionRevenue' => 0,
                'revenue' => 0, 'upkeep' => 0, 'visitorBoost' => 0, 'weatherNote' => null,
            ];
        }

        $level = max(1, min(self::MAX_LEVEL, (int) ($facility['level'] ?? 1)));
        $condition = max(0, min(100, (int) ($facility['condition_pct'] ?? 100)));
        $iceQuality = max(0, min(100, (int) ($facility['ice_quality'] ?? 90)));
        $upgrades = self::parseUpgrades($facility['upgrades'] ?? null);
        $sessionMode = (string) ($facility['session_mode'] ?? 'standard');
        if (!isset(self::SESSION_MODES[$sessionMode])) $sessionMode = 'standard';
        if ($sessionMode === 'extended' && !in_array('floodlights', $upgrades, true) && empty($cfg['indoor'])) {
            $sessionMode = 'standard';
        }

        $price = max((int) $cfg['min_price'], min((int) $cfg['max_price'], (int) ($facility['ticket_price'] ?? $cfg['default_price'])));
        $capacity = self::capacityFor($type, $level, $sessionMode);
        $upkeep = self::upkeepFor($type, $level, $upgrades, $sessionMode);
        $levelMult = self::levelMultiplier($level);

        $hasConcessions = in_array('concessions', $upgrades, true);
        $hasRental = in_array('rental_booth', $upgrades, true);
        $hasProSurface = in_array('pro_surface', $upgrades, true);

        $baseDraw = (int) round(($cfg['spectator_draw'] + ($hasConcessions ? 25 : 0)) * $levelMult);
        $seasonFactor = $isWinter ? 1.0 : (float) $cfg['summer_factor'];
        $condFactor = ($condition / 100) * (0.65 + 0.35 * ($iceQuality / 100));

        // Weather effect on outdoor venues (mitigated by Pro Surface or indoor arena)
        $weatherFactor = 1.0;
        $weatherNote = null;
        if (empty($cfg['indoor'])) {
            if ($type === 'ski_jump' && $windKmh !== null && $windKmh >= 45) {
                $weatherFactor = 0.65;
                $weatherNote = 'High wind reduced jump attendance (-35%)';
            } elseif ($tempC !== null && $tempC > 4 && !$hasProSurface && $type !== 'bobsleigh_track') {
                $weatherFactor = 0.75;
                $weatherNote = 'Mild weather softened surface (-25%)';
            }
        }

        // Higher prices reduce demand smoothly so players optimize price rather than maxing it
        $ratio = $price / max(1, (int) $cfg['default_price']);
        $priceElasticity = $ratio <= 1.0 ? min(1.15, 1.0 + (1.0 - $ratio) * 0.25) : max(0.2, 1.0 / pow($ratio, 1.35));

        $modeGuestMult = (float) (self::SESSION_MODES[$sessionMode]['guest_mult'] ?? 1.0);
        $rawDemand = ($resortVisitors * (float) $cfg['visitor_share'] * $levelMult + $baseDraw)
            * $modeGuestMult * $seasonFactor * $condFactor * $weatherFactor * $priceElasticity;

        $guests = min($capacity, max(0, (int) round($rawDemand)));
        $ticketRevenue = $guests * $price;
        $rentalRevenue = $hasRental ? (int) round($guests * 0.65 * (int) $cfg['rental_per_guest']) : 0;
        $concessionRevenue = $hasConcessions ? (int) round($guests * 0.55 * (int) $cfg['concession_per_guest']) : 0;
        $revenue = $ticketRevenue + $rentalRevenue + $concessionRevenue;
        $visitorBoost = (int) round($baseDraw * $seasonFactor * $condFactor * $weatherFactor);

        return [
            'guests' => $guests,
            'ticketRevenue' => $ticketRevenue,
            'rentalRevenue' => $rentalRevenue,
            'concessionRevenue' => $concessionRevenue,
            'revenue' => $revenue,
            'upkeep' => $upkeep,
            'visitorBoost' => $visitorBoost,
            'weatherNote' => $weatherNote,
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
            $upgrades = self::parseUpgrades($f['upgrades'] ?? null);
            $hasConcessions = in_array('concessions', $upgrades, true);
            $cond = max(0, min(100, (int) ($f['condition_pct'] ?? 100))) / 100;
            $season = $isWinter ? 1.0 : (float) $cfg['summer_factor'];
            $boost += (int) round(($cfg['spectator_draw'] + ($hasConcessions ? 25 : 0)) * self::levelMultiplier($level) * $season * $cond);
        }
        return $boost;
    }
}
