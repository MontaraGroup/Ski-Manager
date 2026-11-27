<?php

namespace App\Libraries;

/**
 * Energy and water rules, shared by the nightly tick and the /energy and /water pages.
 * Sources (resource_types) produce a fixed amount per day while active; any demand above
 * that is bought from the municipal grid / water network at the tariff.
 */
class Utilities
{
    public const GRID_TARIFF = 0.25;   // EUR per kWh imported
    public const WATER_TARIFF = 0.01;  // EUR per litre imported

    /** Daily energy draw: lifts by type per metre, buildings by level, per lit night-skiing area */
    public const LIFT_KWH_PER_M = ['button' => 0.4, 'chair_fixed' => 0.8, 'chair_detach' => 1.0, 'gondola' => 1.4, 'cable_car' => 1.8];
    public const BUILDING_KWH = [1 => 20, 2 => 40, 3 => 80];
    public const DRAW_NIGHT_LIGHT = 250;

    /** Most of a source type one resort may own (there's only one connection to the grid) */
    public const LIMIT = ['grid' => 1];

    /** Source types for 'energy' or 'water', keyed by resource_key */
    public static function config(string $category): array
    {
        static $cache = [];
        if (!isset($cache[$category])) {
            $cache[$category] = [];
            foreach (db_connect()->table('resource_types')->where('category', $category)->orderBy('sort_order')->get()->getResultArray() as $r) {
                $cache[$category][$r['resource_key']] = [
                    'label' => $r['label'], 'icon' => $r['icon'], 'cost' => (int) $r['cost'], 'build_days' => (int) $r['build_days'],
                    'capacity' => (int) $r['capacity'], 'output' => (int) $r['output'], 'upkeep' => (int) $r['upkeep'], 'decay' => (float) $r['decay_rate'],
                ];
            }
        }
        return $cache[$category];
    }

    public static function liftKwh(string $type, int $meters): int
    {
        return (int) round($meters * (self::LIFT_KWH_PER_M[$type] ?? 0.8));
    }

    /**
     * Energy draw (kWh/day) by consumer: running snowmakers' rated energy_kwh, lit night-skiing
     * areas, open lifts (rows with subtype and length_meters) and open buildings (rows with level).
     */
    public static function energyDemand(array $activeSnowmakers, int $lights, array $openLifts, array $openBuildings, int $winterSportsKwh = 0): array
    {
        $d = [
            'snowmaking' => (int) array_sum(array_column($activeSnowmakers, 'energy_kwh')),
            'night_skiing' => $lights * self::DRAW_NIGHT_LIGHT,
            'lifts' => array_sum(array_map(fn($l) => self::liftKwh($l['subtype'] ?? '', (int) ($l['length_meters'] ?? 0)), $openLifts)),
            'buildings' => array_sum(array_map(fn($b) => self::BUILDING_KWH[(int) ($b['level'] ?? 1)] ?? 20, $openBuildings)),
        ];
        if ($winterSportsKwh > 0) {
            $d['winter_sports'] = $winterSportsKwh;
        }
        return $d + ['total' => array_sum($d)];
    }

    public static function waterDemand(array $activeSnowmakers): int
    {
        return (int) array_sum(array_column($activeSnowmakers, 'water_liters'));
    }

    public static function importCost(string $category, int $deficit): int
    {
        return (int) round(max(0, $deficit) * ($category === 'energy' ? self::GRID_TARIFF : self::WATER_TARIFF));
    }

    /**
     * What an active source is worth per day against importing: the import it avoids (only
     * as much of its output as is actually needed) minus its upkeep. Negative = costs more than importing.
     */
    public static function dailyValue(string $category, array $cfg, int $usefulOutput, float $costMult = 1.0): int
    {
        return self::importCost($category, $usefulOutput) - (int) round($cfg['upkeep'] * $costMult);
    }
}
