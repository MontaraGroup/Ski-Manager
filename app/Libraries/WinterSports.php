<?php

namespace App\Libraries;

/**
 * Five distinct Winter Activities, each with its own gameplay loop, upgrades, weather/energy
 * rules, and event integration:
 *   1. ice_rink        - 3-slot daily ice schedule (Open Skate, Lessons, Ice Disco, Resurface) + chiller/Zamboni
 *   2. cross_country   - Named Nordic trail loops (3/7/10/15 km), Classic/Skate grooming, real snow-base gauge
 *   3. hockey_arena    - Matchday booking board (Youth/League/Derby/Pro) + seating tiers + dasher sponsors & skyboxes
 *   4. ski_jump        - Multi-hill complex (K60/K90/K120/HS225), live wind safety gauge, team camps, hill record
 *   5. bobsleigh_track - Taxi-Bob thrill rides vs. National Team rentals, Ammonia cooling power (kWh), speed trap
 */
class WinterSports
{
    public const FLAG = 'winter-sports';
    public const MAX_LEVEL = 3;
    public const MAX_PER_TYPE = 1; // One flagship venue per activity

    /** Order of the 5 flagship tabs */
    public const TYPES = ['ice_rink', 'cross_country', 'hockey_arena', 'ski_jump', 'bobsleigh_track'];

    // =========================================================================
    // 1. ICE RINK: 3-slot daily session options
    // =========================================================================
    public const RINK_SESSIONS = [
        'open_skate' => ['label' => 'Open Skate', 'desc' => 'High-volume village skating with rental demand.', 'guests' => 1.0, 'fee_mult' => 1.0, 'wear' => 1.0, 'evening_only' => false, 'needs_lights' => false],
        'lessons'    => ['label' => 'Skate Lessons', 'desc' => 'Small-group coaching (+45% per-guest fee, family appeal).', 'guests' => 0.55, 'fee_mult' => 1.45, 'wear' => 0.65, 'evening_only' => false, 'needs_lights' => false],
        'ice_disco'  => ['label' => 'Evening Ice Disco', 'desc' => 'Music & lights skate night (+35% crowd & higher ticket). Needs String Lights.', 'guests' => 1.35, 'fee_mult' => 1.30, 'wear' => 1.25, 'evening_only' => true, 'needs_lights' => true],
        'resurface'  => ['label' => 'Ice Maintenance', 'desc' => 'Dedicated resurfacing block: restores +20% ice quality each night.', 'guests' => 0.0, 'fee_mult' => 0.0, 'wear' => -1.0, 'evening_only' => false, 'needs_lights' => false],
    ];

    // =========================================================================
    // 2. CROSS-COUNTRY SKIING: Named Nordic trail loops & grooming styles
    // =========================================================================
    public const NORDIC_LOOPS = [
        'meadow_3k'   => ['name' => 'Meadow Green Loop', 'km' => 3, 'difficulty' => 'green', 'cost' => 0, 'upkeep' => 250, 'capacity' => 90, 'draw' => 25, 'desc' => 'Gentle valley loop included with the Trailhead Lodge.'],
        'forest_7k'   => ['name' => 'Pine Forest Classic', 'km' => 7, 'difficulty' => 'blue', 'cost' => 24000, 'upkeep' => 320, 'capacity' => 130, 'draw' => 35, 'desc' => 'Wind-sheltered timber trail with classic double tracks.'],
        'lakeside_10k'=> ['name' => 'Lakeside Skate Loop', 'km' => 10, 'difficulty' => 'blue', 'cost' => 38000, 'upkeep' => 450, 'capacity' => 170, 'draw' => 45, 'desc' => 'Wide groomed deck built for fast skate-skiing.'],
        'ridge_15k'   => ['name' => 'Alpine Ridge Marathon', 'km' => 15, 'difficulty' => 'black', 'cost' => 62000, 'upkeep' => 620, 'capacity' => 210, 'draw' => 65, 'desc' => 'Championship loop. Unlocks Cross-Country Marathon prestige bonus.'],
    ];

    public const NORDIC_GROOMING = [
        'classic' => ['label' => 'Classic Tracks', 'guest_mult' => 0.95, 'upkeep_mult' => 0.90, 'desc' => 'Parallel diagonal-stride tracks (lower grooming cost).'],
        'skate'   => ['label' => 'Skate Deck', 'guest_mult' => 1.00, 'upkeep_mult' => 1.00, 'desc' => 'Wide flat corduroy deck for skate-skiers.'],
        'dual'    => ['label' => 'Dual-Track', 'guest_mult' => 1.25, 'upkeep_mult' => 1.25, 'desc' => 'Groom both classic & skate lanes (+25% guests & upkeep).'],
    ];

    // =========================================================================
    // 3. ICE HOCKEY VENUE: Matchday booking board
    // =========================================================================
    public const HOCKEY_MATCHES = [
        'club_rental'    => ['name' => 'Club Ice Rental (Off-Day)', 'min_level' => 1, 'needs_pro_lockers' => false, 'booking_cost' => 0, 'fill_rate' => 0.18, 'ticket_mult' => 0.80, 'rep' => 0, 'desc' => 'Steady local club practice rental when no match is booked.'],
        'youth_cup'      => ['name' => 'Youth Alpine Cup', 'min_level' => 1, 'needs_pro_lockers' => false, 'booking_cost' => 4000, 'fill_rate' => 0.62, 'ticket_mult' => 1.00, 'rep' => 15, 'desc' => 'Family-packed junior showcase with strong concession sales.'],
        'league_game'    => ['name' => 'Alpine League Fixture', 'min_level' => 1, 'needs_pro_lockers' => false, 'booking_cost' => 9500, 'fill_rate' => 0.82, 'ticket_mult' => 1.20, 'rep' => 35, 'desc' => 'Competitive regional league match with strong ticket demand.'],
        'regional_derby' => ['name' => 'Mountain Rival Derby', 'min_level' => 2, 'needs_pro_lockers' => false, 'booking_cost' => 18000, 'fill_rate' => 0.95, 'ticket_mult' => 1.45, 'rep' => 70, 'desc' => 'Near-sellout rivalry night. Requires Regional Ice Arena (L2).'],
        'pro_exhibition' => ['name' => 'Pro Exhibition Showcase', 'min_level' => 2, 'needs_pro_lockers' => true, 'booking_cost' => 32000, 'fill_rate' => 1.00, 'ticket_mult' => 1.85, 'rep' => 130, 'desc' => 'Headline pro matchup with max crowd & reputation. Needs Pro Locker Rooms.'],
    ];

    // =========================================================================
    // 4. SKI JUMP RAMP: Multi-hill complex & daily allocation
    // =========================================================================
    public const JUMP_HILLS = [
        'k60'  => ['name' => 'K60 Training Hill', 'k_point' => 60, 'max_m' => 69, 'cost' => 0, 'upkeep' => 700, 'capacity' => 140, 'draw' => 35, 'desc' => 'Junior & training hill included with the complex.'],
        'k90'  => ['name' => 'K90 Normal Hill', 'k_point' => 90, 'max_m' => 106, 'cost' => 55000, 'upkeep' => 950, 'capacity' => 240, 'draw' => 60, 'desc' => 'Olympic normal hill for daily viewing and regional camps.'],
        'k120' => ['name' => 'K120 Large Hill', 'k_point' => 120, 'max_m' => 144, 'cost' => 110000, 'upkeep' => 1400, 'capacity' => 380, 'draw' => 95, 'desc' => 'Championship large hill for Ski Jumping Cup spectacles.'],
        'hs225'=> ['name' => 'HS225 Ski Flying Hill', 'k_point' => 200, 'max_m' => 245, 'cost' => 210000, 'upkeep' => 2200, 'capacity' => 540, 'draw' => 145, 'desc' => 'Rare ski-flying giant drawing massive crowds & prestige.'],
    ];

    public const JUMP_ALLOCATIONS = [
        'public'   => ['label' => 'Spectator Viewing', 'spectator_mult' => 1.15, 'camp_fee' => 0, 'rep' => 0, 'desc' => 'Open stands & hill tour all day for resort guests.'],
        'team_camp'=> ['label' => 'National Team Camp', 'spectator_mult' => 0.35, 'camp_fee' => 4500, 'rep' => 20, 'desc' => 'Guaranteed daily contract fee + reputation per open hill.'],
        'combined' => ['label' => 'Camp + Spectators', 'spectator_mult' => 0.85, 'camp_fee' => 2400, 'rep' => 10, 'desc' => 'Morning team sessions with afternoon spectator viewing.'],
    ];

    // =========================================================================
    // 5. BOBSLEIGH TRACK: Operations mix & Ammonia cooling levels
    // =========================================================================
    public const BOB_MODES = [
        'taxi_bob' => ['label' => 'Taxi-Bob Thrill Rides', 'rider_mult' => 1.15, 'team_fee' => 0, 'wear_mult' => 1.25, 'rep' => 5, 'desc' => 'High-margin 4-man passenger runs with pro pilots.'],
        'team_rent'=> ['label' => 'National Team Rental', 'rider_mult' => 0.25, 'team_fee' => 9500, 'wear_mult' => 0.70, 'rep' => 30, 'desc' => 'Guaranteed sliding team contract, low track wear, strong prestige.'],
        'split'    => ['label' => 'Split Schedule', 'rider_mult' => 0.75, 'team_fee' => 5200, 'wear_mult' => 1.00, 'rep' => 18, 'desc' => 'Morning team training + afternoon Taxi-Bob thrill rides.'],
    ];

    public const COOLING_LEVELS = [
        'eco'       => ['label' => 'Eco Chilling', 'kwh' => 180, 'cost' => 350, 'speed_bonus' => 0, 'max_temp' => 2, 'ice_bonus' => -4, 'desc' => 'Lowest power draw; ice softens above 2°C.'],
        'standard'  => ['label' => 'Standard Ammonia', 'kwh' => 340, 'cost' => 700, 'speed_bonus' => 6, 'max_temp' => 7, 'ice_bonus' => 0, 'desc' => 'Balanced cooling up to 7°C and brisk track speeds.'],
        'race_spec' => ['label' => 'Race-Spec Hard Ice', 'kwh' => 560, 'cost' => 1250, 'speed_bonus' => 14, 'max_temp' => 14, 'ice_bonus' => 6, 'desc' => 'Fastest Olympic-grade ice up to 14°C; higher power draw.'],
    ];

    // =========================================================================
    // VENUE-SPECIFIC UPGRADES (unique per activity)
    // =========================================================================
    public const VENUE_UPGRADES = [
        'ice_rink' => [
            'skate_shop'   => ['name' => 'Skate Rental Shop', 'icon' => 'fa-person-skating', 'cost' => 14000, 'upkeep' => 140, 'kwh' => 20, 'desc' => 'Rent figure & hockey skates to guests without their own gear.'],
            'string_lights'=> ['name' => 'String Lights & Sound', 'icon' => 'fa-music', 'cost' => 12000, 'upkeep' => 120, 'kwh' => 65, 'desc' => 'Unlocks the high-margin Evening Ice Disco session slot.'],
            'ice_chiller'  => ['name' => 'Ice Chiller & Roof', 'icon' => 'fa-snowflake', 'cost' => 28000, 'upkeep' => 260, 'kwh' => 160, 'desc' => 'Refrigerates the sheet so mild days (> 4°C) never soften your ice.'],
            'zamboni'      => ['name' => 'Zamboni Resurfacer', 'icon' => 'fa-tractor', 'cost' => 22000, 'upkeep' => 180, 'kwh' => 40, 'desc' => 'Auto-resurfaces between sessions, cutting nightly ice wear by 55%.'],
        ],
        'cross_country' => [
            'wax_bench'    => ['name' => 'Nordic Rental & Wax Bench', 'icon' => 'fa-person-skiing-nordic', 'cost' => 18000, 'upkeep' => 160, 'kwh' => 25, 'desc' => 'Classic/skate ski rentals and hot-wax service (+rental income).'],
            'warming_hut'  => ['name' => 'Trailhead Warming Hut', 'icon' => 'fa-mug-hot', 'cost' => 20000, 'upkeep' => 180, 'kwh' => 45, 'desc' => 'Soup, cocoa & fireplace draw +30 daily visitors and cafe income.'],
            'snow_support' => ['name' => 'Loop Snowmaking Hydrants', 'icon' => 'fa-cloud-meatball', 'cost' => 34000, 'upkeep' => 280, 'kwh' => 140, 'desc' => 'Keeps Nordic loops skiable even when natural snow base is thin (< 20 cm).'],
            'trail_lanterns'=> ['name' => 'Night Trail Lanterns', 'icon' => 'fa-lightbulb', 'cost' => 16000, 'upkeep' => 150, 'kwh' => 90, 'desc' => 'Lit twilight skiing extends hours for +20% daily trail passes.'],
        ],
        'hockey_arena' => [
            'dasher_sponsors'=> ['name' => 'Dasher Board Sponsors', 'icon' => 'fa-rectangle-ad', 'cost' => 24000, 'upkeep' => 100, 'kwh' => 10, 'desc' => 'Rink-board ads pay a guaranteed +2,200/day sponsorship income.'],
            'jumbotron'      => ['name' => 'Jumbotron & Arena Sound', 'icon' => 'fa-tv', 'cost' => 36000, 'upkeep' => 280, 'kwh' => 110, 'desc' => 'Boosts match attendance by +18% and spectator concessions by +30%.'],
            'vip_skyboxes'   => ['name' => 'VIP Skyboxes', 'icon' => 'fa-champagne-glasses', 'cost' => 52000, 'upkeep' => 340, 'kwh' => 80, 'desc' => 'Adds premium skybox suites (+35% matchday ticket yield).'],
            'pro_lockers'    => ['name' => 'Pro Locker Rooms', 'icon' => 'fa-shirt', 'cost' => 42000, 'upkeep' => 240, 'kwh' => 60, 'desc' => 'Unlocks the headline Pro Exhibition Showcase on the Match Board.'],
        ],
        'ski_jump' => [
            'wind_nets'    => ['name' => 'Wind Protection Nets', 'icon' => 'fa-wind', 'cost' => 38000, 'upkeep' => 220, 'kwh' => 15, 'desc' => 'Raises safe jumping wind limit from 35 km/h to 55 km/h.'],
            'ceramic_inrun'=> ['name' => 'Refrigerated Ceramic In-Run', 'icon' => 'fa-bolt', 'cost' => 48000, 'upkeep' => 300, 'kwh' => 150, 'desc' => 'Consistent takeoff speed (+6 m hill record potential, -45% wear).'],
            'judges_tower' => ['name' => 'Judges Tower & Distance Board', 'icon' => 'fa-tower-observation', 'cost' => 32000, 'upkeep' => 200, 'kwh' => 55, 'desc' => 'Live distance readouts boost spectator spend & camp contracts by +25%.'],
            'summer_mat'   => ['name' => 'Summer Plastic Landing Mat', 'icon' => 'fa-sun', 'cost' => 54000, 'upkeep' => 260, 'kwh' => 30, 'desc' => 'Enables year-round summer jumping camps and off-season viewing (75% demand).'],
        ],
        'bobsleigh_track' => [
            'taxi_fleet'   => ['name' => '4-Man Taxi-Bob Fleet', 'icon' => 'fa-sleigh', 'cost' => 58000, 'upkeep' => 420, 'kwh' => 20, 'desc' => 'Doubles daily public Taxi-Bob thrill-ride capacity.'],
            'push_track'   => ['name' => 'Start-House Push Track', 'icon' => 'fa-stopwatch', 'cost' => 46000, 'upkeep' => 280, 'kwh' => 70, 'desc' => 'Refrigerated indoor push start (+40% National Team rental fees).'],
            'photo_booth'  => ['name' => 'Speed-Trap Photo Booth', 'icon' => 'fa-camera', 'cost' => 26000, 'upkeep' => 160, 'kwh' => 35, 'desc' => 'High-margin finish-line photos & speed certificates (+24/rider).'],
            'sun_shades'   => ['name' => 'Full Track Sun-Shades', 'icon' => 'fa-umbrella-beach', 'cost' => 64000, 'upkeep' => 220, 'kwh' => 0, 'desc' => 'Cuts ammonia cooling kWh & cost by 35% and shields curves from sun.'],
        ],
    ];

    /** Core catalog for the 5 flagship venues */
    public const CATALOG = [
        'ice_rink' => [
            'name' => 'Ice Rink',
            'tagline' => 'Village skating, lessons & evening ice disco',
            'icon' => 'fa-snowflake',
            'desc' => 'Plan morning, afternoon and evening ice sessions for resort families and village nightlife.',
            'surface' => 'Ice Sheet',
            'weather' => 'Outdoor (> 4°C softens ice without Chiller)',
            'indoor' => false,
            'base_kwh' => 70,
            'build_cost' => 35000,
            'upkeep' => 550,
            'capacity' => 180,
            'default_price' => 18,
            'min_price' => 5,
            'max_price' => 60,
            'default_rental_price' => 10,
            'visitor_share' => 0.22,
            'spectator_draw' => 45,
            'summer_factor' => 0.40,
            'wear_per_day' => 1.5,
            'resurface_cost' => 300,
            'levels' => [1 => 'Village Rink', 2 => 'Expanded Plaza Rink', 3 => 'Alpine Ice Pavilion'],
            'event_key' => 'ice_gala',
            'event_name' => 'Winter Ice Gala',
        ],
        'cross_country' => [
            'name' => 'Cross-Country Skiing',
            'tagline' => 'Nordic loop network & classic/skate track setting',
            'icon' => 'fa-person-skiing-nordic',
            'desc' => 'Build named valley and ridge loops, choose track grooming styles, and link your Trail Map Nordic runs.',
            'surface' => 'Nordic Tracks',
            'weather' => 'Depends on resort snow base (< 20 cm reduces quality)',
            'indoor' => false,
            'base_kwh' => 45,
            'build_cost' => 48000,
            'upkeep' => 450,
            'capacity' => 90,
            'default_price' => 22,
            'min_price' => 8,
            'max_price' => 65,
            'default_rental_price' => 14,
            'visitor_share' => 0.24,
            'spectator_draw' => 40,
            'summer_factor' => 0.25,
            'wear_per_day' => 1.4,
            'resurface_cost' => 400,
            'levels' => [1 => 'Trailhead Cabin', 2 => 'Nordic Touring Center', 3 => 'Championship Nordic Center'],
            'event_key' => 'cross_country',
            'event_name' => 'Cross-Country Marathon',
        ],
        'hockey_arena' => [
            'name' => 'Ice Hockey Venue',
            'tagline' => 'Matchday arena, seating tiers & board sponsors',
            'icon' => 'fa-hockey-puck',
            'desc' => 'Book Youth, League, Derby and Pro games, pack your arena stands, and monetize skyboxes & sponsors.',
            'surface' => 'Arena Ice',
            'weather' => 'Indoor all-weather arena',
            'indoor' => true,
            'base_kwh' => 280,
            'build_cost' => 95000,
            'upkeep' => 1450,
            'capacity' => 400,
            'default_price' => 26,
            'min_price' => 10,
            'max_price' => 95,
            'visitor_share' => 0.20,
            'spectator_draw' => 90,
            'summer_factor' => 0.65,
            'wear_per_day' => 1.8,
            'resurface_cost' => 550,
            'levels' => [1 => '400-Seat Bleachers', 2 => '1,200-Seat Regional Bowl', 3 => '3,000-Seat Championship Arena'],
            'seat_tiers' => [1 => 400, 2 => 1200, 3 => 3000],
            'event_key' => 'hockey_classic',
            'event_name' => 'Ice Hockey Classic',
        ],
        'ski_jump' => [
            'name' => 'Ski Jump Ramp',
            'tagline' => 'K60 to HS225 hills, wind safety & team camps',
            'icon' => 'fa-person-skiing',
            'desc' => 'Construct hills up to HS225 Ski Flying, watch live wind safety limits, host national training camps, and chase hill records.',
            'surface' => 'In-Run & Landing',
            'weather' => 'Wind hold >= 35 km/h (55 km/h with Wind Nets)',
            'indoor' => false,
            'base_kwh' => 120,
            'build_cost' => 135000,
            'upkeep' => 950,
            'capacity' => 140,
            'default_price' => 30,
            'min_price' => 10,
            'max_price' => 110,
            'visitor_share' => 0.25,
            'spectator_draw' => 55,
            'summer_factor' => 0.20,
            'wear_per_day' => 2.0,
            'resurface_cost' => 750,
            'levels' => [1 => 'Training Complex', 2 => 'International Hill Complex', 3 => 'World Cup Flying Complex'],
            'event_key' => 'ski_jump_cup',
            'event_name' => 'Ski Jumping Cup',
        ],
        'bobsleigh_track' => [
            'name' => 'Bobsleigh Track',
            'tagline' => 'Taxi-Bob thrill rides, cooling power & team rentals',
            'icon' => 'fa-sleigh',
            'desc' => 'Run high-ticket Taxi-Bob rides and national sliding camps while tuning ammonia cooling against live mountain temperatures.',
            'surface' => 'Ice Wall Chute',
            'weather' => 'Active cooling required above 2°C',
            'indoor' => false,
            'base_kwh' => 0,
            'build_cost' => 210000,
            'upkeep' => 2600,
            'capacity' => 110,
            'default_price' => 85,
            'min_price' => 35,
            'max_price' => 180,
            'visitor_share' => 0.14,
            'spectator_draw' => 135,
            'summer_factor' => 0.45,
            'wear_per_day' => 2.4,
            'resurface_cost' => 1000,
            'levels' => [1 => 'Club Ice Chute (10 curves)', 2 => 'National Track (14 curves)', 3 => 'Olympic Sliding Track (16 curves)'],
            'event_key' => 'bobsleigh_gp',
            'event_name' => 'Bobsleigh Grand Prix',
        ],
    ];

    public static function enabledFor(int $userId): bool
    {
        return function_exists('featureEnabledFor') && featureEnabledFor(self::FLAG, $userId);
    }

    /** Default per-activity JSON configuration when newly built or migrating legacy rows */
    public static function defaultConfig(string $type): array
    {
        return match ($type) {
            'ice_rink' => [
                'slots' => ['morning' => 'open_skate', 'afternoon' => 'lessons', 'evening' => 'open_skate'],
                'rental_price' => 10,
            ],
            'cross_country' => [
                'loops' => ['meadow_3k'],
                'grooming' => 'classic',
                'rental_price' => 14,
            ],
            'hockey_arena' => [
                'booked_match' => 'club_rental',
                'matches_hosted' => 0,
            ],
            'ski_jump' => [
                'hills' => ['k60'],
                'allocation' => 'combined',
                'hill_record_m' => 58,
            ],
            'bobsleigh_track' => [
                'mode' => 'split',
                'cooling' => 'standard',
                'top_speed_kmh' => 118,
            ],
            default => [],
        };
    }

    public static function parseConfig(string $type, ?string $raw): array
    {
        $def = self::defaultConfig($type);
        if (!$raw) return $def;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) return $def;
        return array_replace_recursive($def, $decoded);
    }

    public static function parseUpgrades(string $type, ?string $raw): array
    {
        if (!$raw) return [];
        $allowed = self::VENUE_UPGRADES[$type] ?? [];
        $decoded = json_decode($raw, true);
        $list = is_array($decoded) ? $decoded : explode(',', $raw);
        $valid = [];
        foreach ($list as $key) {
            $k = trim((string) $key);
            if ($k !== '' && isset($allowed[$k]) && !in_array($k, $valid, true)) {
                $valid[] = $k;
            }
        }
        return $valid;
    }

    public static function upgradeCost(string $type, int $currentLevel): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg || $currentLevel >= self::MAX_LEVEL) return 0;
        return (int) round($cfg['build_cost'] * ($currentLevel === 1 ? 0.85 : 1.35));
    }

    public static function repairCost(string $type, int $conditionPct): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $missing = max(0, 100 - min(100, $conditionPct));
        if ($missing === 0) return 0;
        return max(250, (int) round($cfg['build_cost'] * 0.0025 * $missing));
    }

    public static function investedValue(string $type, int $level, array $upgrades = [], array $config = []): int
    {
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $total = (int) $cfg['build_cost'];
        for ($l = 1; $l < min(self::MAX_LEVEL, $level); $l++) {
            $total += self::upgradeCost($type, $l);
        }
        $uCatalog = self::VENUE_UPGRADES[$type] ?? [];
        foreach ($upgrades as $u) {
            $total += (int) ($uCatalog[$u]['cost'] ?? 0);
        }
        if ($type === 'cross_country') {
            foreach ($config['loops'] ?? [] as $lk) {
                $total += (int) (self::NORDIC_LOOPS[$lk]['cost'] ?? 0);
            }
        }
        if ($type === 'ski_jump') {
            foreach ($config['hills'] ?? [] as $hk) {
                $total += (int) (self::JUMP_HILLS[$hk]['cost'] ?? 0);
            }
        }
        return $total;
    }

    public static function salvageValue(string $type, int $level, array $upgrades = [], array $config = []): int
    {
        return (int) round(self::investedValue($type, $level, $upgrades, $config) * 0.5);
    }

    /** Daily electrical draw (kWh/day) for one open venue, feeding into Utilities::energyDemand() */
    public static function energyDrawFor(array $facility): int
    {
        if (($facility['status'] ?? '') !== 'open') return 0;
        $type = (string) ($facility['facility_type'] ?? '');
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg) return 0;
        $level = max(1, min(self::MAX_LEVEL, (int) ($facility['level'] ?? 1)));
        $upgrades = self::parseUpgrades($type, $facility['upgrades'] ?? null);
        $config = self::parseConfig($type, $facility['config_json'] ?? null);

        $kwh = (int) round((int) ($cfg['base_kwh'] ?? 0) * (1 + 0.35 * ($level - 1)));
        $uCatalog = self::VENUE_UPGRADES[$type] ?? [];
        foreach ($upgrades as $u) {
            $kwh += (int) ($uCatalog[$u]['kwh'] ?? 0);
        }
        if ($type === 'bobsleigh_track') {
            $cool = self::COOLING_LEVELS[$config['cooling'] ?? 'standard'] ?? self::COOLING_LEVELS['standard'];
            $coolKwh = (int) $cool['kwh'];
            if (in_array('sun_shades', $upgrades, true)) {
                $coolKwh = (int) round($coolKwh * 0.65);
            }
            $kwh += $coolKwh;
        }
        return $kwh;
    }

    /** Total kWh/day across all open winter sport facilities for a player */
    public static function totalEnergyDraw(array $facilities): int
    {
        $sum = 0;
        foreach ($facilities as $f) {
            $sum += self::energyDrawFor($f);
        }
        return $sum;
    }

    /**
     * Pure daily settlement for any of the 5 bespoke winter activities.
     * @return array{guests: int, capacity: int, ticketRevenue: int, addonRevenue: int, contractRevenue: int, revenue: int, upkeep: int, kwh: int, visitorBoost: int, repBonus: int, wear: float, iceDelta: int, weatherNote: ?string, highlight: string}
     */
    public static function settleDay(
        array $facility,
        int $resortVisitors,
        bool $isWinter = true,
        ?int $tempC = null,
        ?int $windKmh = null,
        ?int $snowBaseCm = null,
        int $mapNordicKm = 0
    ): array {
        $type = (string) ($facility['facility_type'] ?? '');
        $cfg = self::CATALOG[$type] ?? null;
        if (!$cfg || ($facility['status'] ?? '') !== 'open') {
            return [
                'guests' => 0, 'capacity' => 0, 'ticketRevenue' => 0, 'addonRevenue' => 0, 'contractRevenue' => 0,
                'revenue' => 0, 'upkeep' => 0, 'kwh' => 0, 'visitorBoost' => 0, 'repBonus' => 0,
                'wear' => 0.0, 'iceDelta' => 0, 'weatherNote' => null, 'highlight' => 'Closed',
            ];
        }

        $level = max(1, min(self::MAX_LEVEL, (int) ($facility['level'] ?? 1)));
        $cond = max(0, min(100, (int) ($facility['condition_pct'] ?? 100)));
        $ice = max(0, min(100, (int) ($facility['ice_quality'] ?? 90)));
        $upgrades = self::parseUpgrades($type, $facility['upgrades'] ?? null);
        $config = self::parseConfig($type, $facility['config_json'] ?? null);
        $price = max((int) $cfg['min_price'], min((int) $cfg['max_price'], (int) ($facility['ticket_price'] ?? $cfg['default_price'])));
        $has = fn(string $u): bool => in_array($u, $upgrades, true);

        $uCatalog = self::VENUE_UPGRADES[$type] ?? [];
        $addonUpkeep = 0;
        foreach ($upgrades as $u) $addonUpkeep += (int) ($uCatalog[$u]['upkeep'] ?? 0);

        $ratio = $price / max(1, (int) $cfg['default_price']);
        $elasticity = $ratio <= 1.0 ? min(1.15, 1.0 + (1.0 - $ratio) * 0.25) : max(0.22, 1.0 / pow($ratio, 1.35));
        $qualityFactor = ($cond / 100) * (0.65 + 0.35 * ($ice / 100));
        $kwh = self::energyDrawFor($facility);

        if ($type === 'ice_rink') {
            $slots = $config['slots'] ?? ['morning' => 'open_skate', 'afternoon' => 'lessons', 'evening' => 'open_skate'];
            $capPerSlot = (int) round((int) $cfg['capacity'] * (1 + 0.35 * ($level - 1)) / 3);
            $weatherFactor = 1.0;
            $weatherNote = null;
            if ($tempC !== null && $tempC > 4 && !$has('ice_chiller')) {
                $weatherFactor = 0.65;
                $weatherNote = 'Warm air (' . $tempC . '°C) softening outdoor ice (-35%). Build Ice Chiller & Roof to stop thaw.';
            }
            $seasonFactor = $isWinter ? 1.0 : ($has('ice_chiller') ? 0.65 : (float) $cfg['summer_factor']);

            $guests = 0;
            $ticketRev = 0;
            $activeSlots = 0;
            $wearSum = 0.0;
            $iceDelta = 0;
            foreach (['morning', 'afternoon', 'evening'] as $slotName) {
                $sKey = (string) ($slots[$slotName] ?? 'open_skate');
                $sDef = self::RINK_SESSIONS[$sKey] ?? self::RINK_SESSIONS['open_skate'];
                if ($sKey === 'ice_disco' && ($slotName !== 'evening' || !$has('string_lights'))) {
                    $sDef = self::RINK_SESSIONS['open_skate'];
                    $sKey = 'open_skate';
                }
                if ($sKey === 'resurface') {
                    $iceDelta += 20;
                    continue;
                }
                $activeSlots++;
                $slotDemand = (($resortVisitors * 0.09) + 22) * (float) $sDef['guests'] * $seasonFactor * $weatherFactor * $qualityFactor * $elasticity;
                $slotGuests = min($capPerSlot, max(0, (int) round($slotDemand)));
                $guests += $slotGuests;
                $ticketRev += (int) round($slotGuests * $price * (float) $sDef['fee_mult']);
                $wearSum += (float) $sDef['wear'];
                $iceDelta -= (int) round(4 * (float) $sDef['wear']);
            }
            $rentalPrice = max(3, min(30, (int) ($config['rental_price'] ?? 10)));
            $addonRev = $has('skate_shop') ? (int) round($guests * 0.62 * $rentalPrice) : 0;
            $upkeep = (int) round((int) $cfg['upkeep'] * (1 + 0.35 * ($level - 1))) + $addonUpkeep;
            $wear = max(0.3, (float) $cfg['wear_per_day'] * max(0.4, $wearSum / 3) * ($has('zamboni') ? 0.45 : 1.0));
            if ($has('zamboni')) $iceDelta = (int) round($iceDelta * 0.45);

            return [
                'guests' => $guests,
                'capacity' => $capPerSlot * max(1, $activeSlots),
                'ticketRevenue' => $ticketRev,
                'addonRevenue' => $addonRev,
                'contractRevenue' => 0,
                'revenue' => $ticketRev + $addonRev,
                'upkeep' => $upkeep,
                'kwh' => $kwh,
                'visitorBoost' => (int) round((45 + ($has('string_lights') ? 20 : 0)) * $seasonFactor * $weatherFactor),
                'repBonus' => 0,
                'wear' => $wear,
                'iceDelta' => $iceDelta,
                'weatherNote' => $weatherNote,
                'highlight' => $activeSlots . ' active session' . ($activeSlots === 1 ? '' : 's') . ' scheduled',
            ];
        }

        if ($type === 'cross_country') {
            $loops = $config['loops'] ?? ['meadow_3k'];
            if (empty($loops)) $loops = ['meadow_3k'];
            $groomKey = (string) ($config['grooming'] ?? 'classic');
            $groom = self::NORDIC_GROOMING[$groomKey] ?? self::NORDIC_GROOMING['classic'];

            $totalKm = $mapNordicKm;
            $capacity = $mapNordicKm * 12;
            $loopUpkeep = 0;
            $loopDraw = 0;
            foreach ($loops as $lk) {
                $l = self::NORDIC_LOOPS[$lk] ?? null;
                if (!$l) continue;
                $totalKm += (int) $l['km'];
                $capacity += (int) $l['capacity'];
                $loopUpkeep += (int) $l['upkeep'];
                $loopDraw += (int) $l['draw'];
            }
            $capacity = max(90, (int) round($capacity * (float) $groom['guest_mult']));

            $weatherFactor = 1.0;
            $weatherNote = null;
            if ($isWinter && $snowBaseCm !== null && $snowBaseCm < 20 && !$has('snow_support')) {
                $weatherFactor = 0.60;
                $weatherNote = 'Thin snow base (' . $snowBaseCm . ' cm) degrades Nordic tracks (-40%). Add Loop Snowmaking Hydrants.';
            }
            $seasonFactor = $isWinter ? 1.0 : (float) $cfg['summer_factor'];
            $lanternMult = $has('trail_lanterns') ? 1.20 : 1.0;
            $kmBonus = 1.0 + min(0.8, $totalKm / 40);

            $rawDemand = ($resortVisitors * 0.22 * $kmBonus + $loopDraw + ($has('warming_hut') ? 30 : 0))
                * (float) $groom['guest_mult'] * $lanternMult * $seasonFactor * $weatherFactor * $qualityFactor * $elasticity;
            $guests = min($capacity, max(0, (int) round($rawDemand)));
            $ticketRev = $guests * $price;
            $rentalPrice = max(5, min(40, (int) ($config['rental_price'] ?? 14)));
            $addonRev = ($has('wax_bench') ? (int) round($guests * 0.55 * $rentalPrice) : 0)
                + ($has('warming_hut') ? (int) round($guests * 0.60 * 7) : 0);
            $upkeep = (int) round(((int) $cfg['upkeep'] + $loopUpkeep) * (float) $groom['upkeep_mult']) + $addonUpkeep;

            return [
                'guests' => $guests,
                'capacity' => $capacity,
                'ticketRevenue' => $ticketRev,
                'addonRevenue' => $addonRev,
                'contractRevenue' => 0,
                'revenue' => $ticketRev + $addonRev,
                'upkeep' => $upkeep,
                'kwh' => $kwh,
                'visitorBoost' => (int) round(($loopDraw + ($has('warming_hut') ? 30 : 0)) * $seasonFactor * $weatherFactor),
                'repBonus' => 0,
                'wear' => (float) $cfg['wear_per_day'],
                'iceDelta' => -7,
                'weatherNote' => $weatherNote,
                'highlight' => $totalKm . ' km groomed (' . $groom['label'] . ')',
            ];
        }

        if ($type === 'hockey_arena') {
            $seats = (int) ($cfg['seat_tiers'][$level] ?? 400);
            $matchKey = (string) ($config['booked_match'] ?? 'club_rental');
            $match = self::HOCKEY_MATCHES[$matchKey] ?? self::HOCKEY_MATCHES['club_rental'];
            if ($level < (int) $match['min_level'] || (!empty($match['needs_pro_lockers']) && !$has('pro_lockers'))) {
                $matchKey = 'club_rental';
                $match = self::HOCKEY_MATCHES['club_rental'];
            }

            $seasonFactor = $isWinter ? 1.0 : (float) $cfg['summer_factor'];
            $jumboMult = $has('jumbotron') ? 1.18 : 1.0;
            $skyboxMult = $has('vip_skyboxes') ? 1.35 : 1.0;
            $fill = min(1.0, (float) $match['fill_rate'] * $jumboMult * $seasonFactor * $qualityFactor * $elasticity);
            $guests = max(0, (int) round($seats * $fill));

            $ticketRev = (int) round($guests * $price * (float) $match['ticket_mult'] * $skyboxMult);
            $concessionPerGuest = $has('jumbotron') ? 12 : 8;
            $addonRev = (int) round($guests * 0.60 * $concessionPerGuest);
            $contractRev = $has('dasher_sponsors') ? 2200 : 0;
            $upkeep = (int) round((int) $cfg['upkeep'] * (1 + 0.55 * ($level - 1))) + $addonUpkeep;

            return [
                'guests' => $guests,
                'capacity' => $seats,
                'ticketRevenue' => $ticketRev,
                'addonRevenue' => $addonRev,
                'contractRevenue' => $contractRev,
                'revenue' => $ticketRev + $addonRev + $contractRev,
                'upkeep' => $upkeep,
                'kwh' => $kwh,
                'visitorBoost' => (int) round(($matchKey === 'club_rental' ? 40 : $guests * 0.25)),
                'repBonus' => (int) $match['rep'],
                'wear' => (float) $cfg['wear_per_day'] * ($matchKey === 'club_rental' ? 0.65 : 1.2),
                'iceDelta' => $matchKey === 'club_rental' ? -5 : -10,
                'weatherNote' => null,
                'highlight' => $match['name'] . ' (' . $guests . '/' . $seats . ' seats)',
            ];
        }

        if ($type === 'ski_jump') {
            $hills = $config['hills'] ?? ['k60'];
            if (empty($hills)) $hills = ['k60'];
            $allocKey = (string) ($config['allocation'] ?? 'combined');
            $alloc = self::JUMP_ALLOCATIONS[$allocKey] ?? self::JUMP_ALLOCATIONS['combined'];

            $capacity = 0;
            $hillUpkeep = 0;
            $hillDraw = 0;
            $maxK = 60;
            foreach ($hills as $hk) {
                $h = self::JUMP_HILLS[$hk] ?? null;
                if (!$h) continue;
                $capacity += (int) $h['capacity'];
                $hillUpkeep += (int) $h['upkeep'];
                $hillDraw += (int) $h['draw'];
                $maxK = max($maxK, (int) $h['k_point']);
            }

            $safeWind = $has('wind_nets') ? 55 : 35;
            $weatherFactor = 1.0;
            $weatherNote = null;
            if ($windKmh !== null && $windKmh >= $safeWind) {
                $weatherFactor = 0.25;
                $weatherNote = 'Wind hold (' . $windKmh . ' km/h >= ' . $safeWind . ' km/h limit): jumping paused (-75% attendance).';
            } elseif ($windKmh !== null && $windKmh >= ($safeWind - 10)) {
                $weatherFactor = 0.75;
                $weatherNote = 'Gusty crosswinds (' . $windKmh . ' km/h) delaying jump rounds (-25%).';
            }

            $seasonFactor = $isWinter ? 1.0 : ($has('summer_mat') ? 0.75 : (float) $cfg['summer_factor']);
            $towerMult = $has('judges_tower') ? 1.25 : 1.0;
            $rawDemand = ($resortVisitors * 0.22 + $hillDraw) * (float) $alloc['spectator_mult'] * $towerMult * $seasonFactor * $weatherFactor * $qualityFactor * $elasticity;
            $guests = min($capacity, max(0, (int) round($rawDemand)));

            $ticketRev = $guests * $price;
            $contractRev = (int) round((int) $alloc['camp_fee'] * count($hills) * $towerMult * $seasonFactor * max(0.5, $weatherFactor));
            $addonRev = $has('judges_tower') ? (int) round($guests * 0.40 * 10) : 0;
            $upkeep = (int) $cfg['upkeep'] + $hillUpkeep + $addonUpkeep;
            $wear = (float) $cfg['wear_per_day'] * ($has('ceramic_inrun') ? 0.55 : 1.0);

            return [
                'guests' => $guests,
                'capacity' => $capacity,
                'ticketRevenue' => $ticketRev,
                'addonRevenue' => $addonRev,
                'contractRevenue' => $contractRev,
                'revenue' => $ticketRev + $addonRev + $contractRev,
                'upkeep' => $upkeep,
                'kwh' => $kwh,
                'visitorBoost' => (int) round($hillDraw * $seasonFactor * $weatherFactor),
                'repBonus' => (int) $alloc['rep'] + ($maxK >= 120 ? 10 : 0),
                'wear' => $wear,
                'iceDelta' => $has('ceramic_inrun') ? -4 : -8,
                'weatherNote' => $weatherNote,
                'highlight' => count($hills) . ' hill' . (count($hills) === 1 ? '' : 's') . ' active (up to K' . $maxK . ')',
            ];
        }

        // Bobsleigh Track
        $modeKey = (string) ($config['mode'] ?? 'split');
        $mode = self::BOB_MODES[$modeKey] ?? self::BOB_MODES['split'];
        $coolKey = (string) ($config['cooling'] ?? 'standard');
        $cool = self::COOLING_LEVELS[$coolKey] ?? self::COOLING_LEVELS['standard'];

        $capacity = (int) round((int) $cfg['capacity'] * (1 + 0.35 * ($level - 1)) * ($has('taxi_fleet') ? 2.0 : 1.0));
        $coolMaxTemp = (int) $cool['max_temp'] + ($has('sun_shades') ? 4 : 0);
        $weatherFactor = 1.0;
        $weatherNote = null;
        if ($tempC !== null && $tempC > $coolMaxTemp) {
            $weatherFactor = 0.55;
            $weatherNote = 'Temp (' . $tempC . '°C) exceeds ' . $cool['label'] . ' limit (' . $coolMaxTemp . '°C). Raise cooling power or add Sun-Shades.';
        }

        $seasonFactor = $isWinter ? 1.0 : ($has('sun_shades') ? 0.65 : (float) $cfg['summer_factor']);
        $rawRiders = ($resortVisitors * 0.14 + 55) * (float) $mode['rider_mult'] * $seasonFactor * $weatherFactor * $qualityFactor * $elasticity;
        $guests = min($capacity, max(0, (int) round($rawRiders)));
        $ticketRev = $guests * $price;
        $addonRev = $has('photo_booth') ? (int) round($guests * 0.72 * 24) : 0;
        $contractRev = (int) round((int) $mode['team_fee'] * ($has('push_track') ? 1.40 : 1.0) * $seasonFactor * max(0.6, $weatherFactor));

        $coolCost = (int) round((int) $cool['cost'] * ($has('sun_shades') ? 0.65 : 1.0));
        $upkeep = (int) round((int) $cfg['upkeep'] * (1 + 0.35 * ($level - 1))) + $coolCost + $addonUpkeep;
        $wear = (float) $cfg['wear_per_day'] * (float) $mode['wear_mult'];
        $iceDelta = -7 + (int) $cool['ice_bonus'];

        return [
            'guests' => $guests,
            'capacity' => $capacity,
            'ticketRevenue' => $ticketRev,
            'addonRevenue' => $addonRev,
            'contractRevenue' => $contractRev,
            'revenue' => $ticketRev + $addonRev + $contractRev,
            'upkeep' => $upkeep,
            'kwh' => $kwh,
            'visitorBoost' => (int) round(135 * $seasonFactor * $weatherFactor),
            'repBonus' => (int) $mode['rep'],
            'wear' => $wear,
            'iceDelta' => $iceDelta,
            'weatherNote' => $weatherNote,
            'highlight' => $mode['label'] . ' - ' . $cool['label'],
        ];
    }

    /** Compute a potential new Ski Jump Hill Record (meters) for the nightly tick */
    public static function nextHillRecord(array $hills, int $currentRecord, bool $ceramicInRun, int $iceQuality): int
    {
        $maxCap = 69;
        foreach ($hills as $hk) {
            $maxCap = max($maxCap, (int) (self::JUMP_HILLS[$hk]['max_m'] ?? 69));
        }
        $target = (int) round($maxCap * (0.86 + 0.10 * ($iceQuality / 100)) + ($ceramicInRun ? 5 : 0));
        $target = min($maxCap + ($ceramicInRun ? 6 : 0), $target);
        return max($currentRecord, $target);
    }

    /** Compute Bobsleigh Track speed rating (km/h) based on level, cooling, and ice quality */
    public static function trackSpeedKmh(int $level, string $coolingKey, int $iceQuality): int
    {
        $cool = self::COOLING_LEVELS[$coolingKey] ?? self::COOLING_LEVELS['standard'];
        return (int) round(108 + ($level - 1) * 9 + (int) $cool['speed_bonus'] + ($iceQuality - 70) * 0.18);
    }
}
