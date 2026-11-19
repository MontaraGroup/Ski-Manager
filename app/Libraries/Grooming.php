<?php

namespace App\Libraries;

/**
 * Slope grooming rules (economy v2), shared by the nightly tick and the grooming page.
 * Slopes wear down every day; each groomer machine with an operator restores a few slopes
 * overnight, and can do the same number of paid extra runs during the day.
 */
class Grooming
{
    /** Daily wear by difficulty, in condition points */
    public const DECAY = ['green' => 2, 'blue' => 3, 'red' => 4, 'black' => 4, 'double_black' => 5];
    public const DECAY_DEFAULT = 3;
    public const WARM_EXTRA = 2;          // base temperature above 0°C, or rain
    public const FRESH_SNOW_RELIEF = 2;   // 5 cm or more of new snow
    public const THIN_BASE_EXTRA = 2;     // slope base (natural + made snow) under Weather::THIN_BASE
    public const RESTORE_BASE = 20;       // points one grooming pass restores
    public const RESTORE_PER_LEVEL = 2;   // plus this per average operator level
    public const RESTORE_MAX = 35;
    public const EXTRA_RUN_PER_M = 0.15;  // EUR of fuel per metre for a daytime run

    /**
     * @param array $weather weather row (temp = base °C, snowfall in cm, snow_base in cm, condition_name)
     * The slope's made snow (made_snow_cm, App\Libraries\Snowmaking) adds to the natural base.
     */
    public static function decay(array $slope, array $weather): int
    {
        $d = self::DECAY[$slope['difficulty'] ?? ''] ?? self::DECAY_DEFAULT;
        $condition = $weather['condition_name'] ?? $weather['condition'] ?? '';
        if ((int) ($weather['temp'] ?? $weather['base_temp'] ?? -5) > 0 || in_array($condition, ['Rain', 'Freezing Rain'], true)) $d += self::WARM_EXTRA;
        if ((int) ($weather['snowfall'] ?? 0) >= 5) $d -= self::FRESH_SNOW_RELIEF;
        if (isset($weather['snow_base']) && (int) $weather['snow_base'] + (int) ($slope['made_snow_cm'] ?? 0) < Weather::THIN_BASE) $d += self::THIN_BASE_EXTRA;
        return max(0, $d);
    }

    /**
     * Machines that can run (active, with an operator), biggest first.
     * @return array{machines: array, capacity: int, operators: int, idle_machines: int}
     */
    public static function fleet(array $machines, array $operators): array
    {
        $active = array_values(array_filter($machines, fn($m) => ($m['status'] ?? '') === 'active'));
        usort($active, fn($a, $b) => (int) $b['capacity'] <=> (int) $a['capacity']);
        $crewed = array_slice($active, 0, count($operators));
        return [
            'machines' => $crewed,
            'capacity' => array_sum(array_map(fn($m) => max(1, (int) $m['capacity']), $crewed)),
            'operators' => count($operators),
            'idle_machines' => count($active) - count($crewed),
        ];
    }

    /** Condition points one pass restores, better with experienced operators */
    public static function restore(array $operators): int
    {
        $avgLevel = $operators ? array_sum(array_map(fn($o) => (int) ($o['level'] ?? 1), $operators)) / count($operators) : 1;
        return (int) min(self::RESTORE_MAX, round(self::RESTORE_BASE + self::RESTORE_PER_LEVEL * $avgLevel));
    }

    /** Slopes grooming can work on: open ones, and ones closed because they wore down to 0% (grooming reopens them) */
    public static function groomable(array $slope): bool
    {
        return in_array($slope['status'] ?? 'open', ['open', 'broken'], true) && (int) $slope['condition_pct'] < 100;
    }

    /** The slopes a run of $capacity passes should groom, worst first */
    public static function pick(array $slopes, int $capacity): array
    {
        $due = array_values(array_filter($slopes, [self::class, 'groomable']));
        usort($due, fn($a, $b) => (int) $a['condition_pct'] <=> (int) $b['condition_pct']);
        return array_slice($due, 0, max(0, $capacity));
    }

    public static function extraRunCost(array $slope): int
    {
        return (int) round(max(1, (int) ($slope['length_meters'] ?? 0)) * self::EXTRA_RUN_PER_M);
    }
}
