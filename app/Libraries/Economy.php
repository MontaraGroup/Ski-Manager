<?php

namespace App\Libraries;

/**
 * Economy v2 rules (feature flag `economy_v2`). Pure functions so the tick, the pages that
 * preview numbers and the season reset all agree. Tuned against a production snapshot:
 * a starter resort pays back in ~3-5 weeks and income runs ~2x expenses.
 */
class Economy
{
    /** Share of skiers buying each ticket, and how many visits a multi-day ticket covers */
    public const TICKET_MIX = [
        'full_day' => 0.40, 'half_day' => 0.15, 'child' => 0.12, 'two_day' => 0.10,
        'weekly' => 0.07, 'group' => 0.07, 'senior' => 0.06, 'season' => 0.03,
    ];
    public const TICKET_REFERENCE = [
        'full_day' => 55, 'half_day' => 35, 'child' => 25, 'two_day' => 95,
        'weekly' => 250, 'group' => 45, 'senior' => 40, 'season' => 800,
    ];
    public const TICKET_VISITS = ['two_day' => 2, 'weekly' => 6, 'season' => 25];
    public const PRICE_ELASTICITY = 0.9;
    public const MAX_DEMAND = 1.5;
    /** Share of skiers who buy another ticket when theirs isn't on sale */
    public const SWITCH_TICKET = 0.5;

    /** Lift capacity per hour at the largest seat option; fewer seats carry proportionally fewer */
    public const LIFT_CAPACITY = ['button' => 1000, 'chair_fixed' => 2400, 'chair_detach' => 3400, 'gondola' => 3500, 'cable_car' => 4000];
    public const LIFT_MAX_SEATS = ['button' => 2, 'chair_fixed' => 4, 'chair_detach' => 8, 'gondola' => 10, 'cable_car' => 30];
    public const LIFT_HOURS = 6;
    public const RIDES_PER_SKIER = 10;
    public const WALK_UP_VISITORS = 100;

    /**
     * Running cost (maintenance, staff) in EUR per metre per day; a closed lift costs a fifth.
     * Electricity is billed separately (Utilities::LIFT_KWH_PER_M at the grid tariff, or from own sources).
     */
    public const LIFT_RUN_PER_M = ['button' => 0.4, 'chair_fixed' => 0.6, 'chair_detach' => 0.95, 'gondola' => 1.45, 'cable_car' => 2.05];
    public const SLOPE_RUN_PER_M = 0.25;

    /** Regional skier market: demand above this flattens out */
    public const MARKET_SIZE = 3000;

    /** Share of the day's skiers who use each building type */
    public const BUILDING_SHARE = [
        'restaurant' => 0.70, 'rental' => 0.35, 'retail' => 0.30, 'transportation' => 0.25,
        'hotel' => 0.20, 'real_estate' => 0.05, 'off_season' => 0.05,
    ];
    /** Summer tourists (hikers, bikers) as a share of the winter skier demand; only off-season buildings serve them */
    public const SUMMER_TOURIST_SHARE = 0.4;
    public const STAFFED_BONUS = ['hotel' => ['receptionist', StaffEffects::RECEPTIONIST_REVENUE_BOOST], 'restaurant' => ['chef', StaffEffects::CHEF_REVENUE_BOOST]];

    public const LEGACY_PER_DECADE = 50000;
    public const LEGACY_CAP = 250000;

    /** Demand at this price as a multiple of demand at the reference price */
    public static function priceDemand(string $type, float $price): float
    {
        $ref = self::TICKET_REFERENCE[$type] ?? 55;
        return min(self::MAX_DEMAND, exp(-self::PRICE_ELASTICITY * ($price / $ref - 1)));
    }

    /**
     * @param array $tickets lift_tickets rows (ticket_type, price, active); none means the defaults
     * @return array{demand: float, per_visit: float, mix: array<string, float>} demand multiplier,
     *         average revenue per skier-day, and the share of skiers buying each type
     */
    public static function ticketTerms(array $tickets): array
    {
        $prices = [];
        foreach ($tickets ?: [] as $t) {
            if ((int) ($t['active'] ?? 1) === 1 && isset(self::TICKET_MIX[$t['ticket_type']])) {
                $prices[$t['ticket_type']] = (float) $t['price'];
            }
        }
        if (!$tickets) $prices = self::TICKET_REFERENCE;
        if (!$prices) return ['demand' => 0.0, 'per_visit' => 0.0, 'mix' => []];

        // Skiers whose ticket isn't on sale: half buy one of the others (in proportion), half stay home.
        // Otherwise dropping the cheap tickets would push everyone onto pricier ones for free.
        $onSale = array_intersect_key(self::TICKET_MIX, $prices);
        $onSaleShare = array_sum($onSale);
        $scale = 1 + self::SWITCH_TICKET * (1 - $onSaleShare) / $onSaleShare;
        $demand = 0.0; $revenue = 0.0; $weights = [];
        foreach ($onSale as $type => $share) {
            $w = $share * $scale * self::priceDemand($type, $prices[$type]);
            $weights[$type] = $w;
            $demand += $w;
            $revenue += $w * $prices[$type] / (self::TICKET_VISITS[$type] ?? 1);
        }
        $mix = $demand > 0 ? array_map(fn($w) => $w / $demand, $weights) : [];
        return ['demand' => $demand, 'per_visit' => $demand > 0 ? $revenue / $demand : 0.0, 'mix' => $mix];
    }

    /**
     * Skiers the slopes draw per day before boosts: longer runs and a full spread of difficulties
     * draw more, and a worn slope draws less (CONDITION_FLOOR of its skiers at 0%)
     */
    public static function slopeDemand(array $slopes): float
    {
        $base = 0.0; $difficulties = [];
        foreach ($slopes as $s) {
            $base += self::slopeDraw((int) ($s['length_meters'] ?? 0)) * self::conditionFactor((int) ($s['condition_pct'] ?? 100));
            if (!empty($s['difficulty'])) $difficulties[$s['difficulty']] = true;
        }
        return $base * (1 + 0.05 * count($difficulties));
    }

    public static function slopeDraw(int $meters): float
    {
        return 30 + $meters / 40;
    }

    public const CONDITION_FLOOR = 0.6;

    /** Share of a slope's skiers it keeps at this condition: 100% → all, 50% → 80%, 0% → 60% */
    public static function conditionFactor(int $conditionPct): float
    {
        return self::CONDITION_FLOOR + (1 - self::CONDITION_FLOOR) * max(0, min(100, $conditionPct)) / 100;
    }

    public static function liftCapacityPerHour(string $type, int $seats): int
    {
        $base = self::LIFT_CAPACITY[$type] ?? 2400;
        $max = self::LIFT_MAX_SEATS[$type] ?? 4;
        return (int) round($base * min(1, max(1, $seats) / $max));
    }

    /** Skiers per day the open lifts can carry; a resort without lifts gets walk-up skiers only */
    public static function liftDailyCapacity(array $openLifts): int
    {
        $perHour = 0;
        foreach ($openLifts as $l) {
            $perHour += (int) ($l['capacity'] ?? 0) ?: (self::LIFT_CAPACITY[$l['subtype'] ?? ''] ?? 2400);
        }
        return $perHour > 0 ? (int) round($perHour * self::LIFT_HOURS / self::RIDES_PER_SKIER) : self::WALK_UP_VISITORS;
    }

    public static function liftRunningCost(string $type, int $meters, bool $open = true): float
    {
        return $meters * (self::LIFT_RUN_PER_M[$type] ?? 0.8) * ($open ? 1 : 0.2);
    }

    public static function slopeRunningCost(int $meters): float
    {
        return $meters * self::SLOPE_RUN_PER_M;
    }

    /** Daily running cost of all lifts (open or closed) and open slopes */
    public static function runningCost(array $items): float
    {
        $cost = 0.0;
        foreach ($items as $i) {
            $meters = (int) ($i['length_meters'] ?? 0);
            $open = ($i['status'] ?? 'open') === 'open';
            if ($i['item_type'] === 'lift' && in_array($i['status'] ?? 'open', ['open', 'closed'], true)) {
                $cost += self::liftRunningCost($i['subtype'] ?? 'chair_fixed', $meters, $open);
            } elseif ($i['item_type'] === 'slope' && $open) {
                $cost += self::slopeRunningCost($meters);
            }
        }
        return $cost;
    }

    /** Soft cap: small resorts are unaffected, very large demand flattens out */
    public static function marketCap(float $demand): float
    {
        return self::MARKET_SIZE * log1p(max(0, $demand) / self::MARKET_SIZE);
    }

    /**
     * How full each building type is (0-1), given the day's skiers and summer tourists.
     * @param array $buildings open buildings (building_type, capacity)
     */
    public static function buildingUtilization(array $buildings, int $visitors, int $summerTourists = 0): array
    {
        $capacity = [];
        foreach ($buildings as $b) {
            $capacity[$b['building_type']] = ($capacity[$b['building_type']] ?? 0) + max(1, (int) ($b['capacity'] ?? 0));
        }
        $util = [];
        foreach ($capacity as $type => $cap) {
            $guests = $visitors * (self::BUILDING_SHARE[$type] ?? 0);
            if ($type === 'off_season') $guests += $summerTourists;
            $util[$type] = min(1.0, $guests / $cap);
        }
        return $util;
    }

    /** Revenue multiplier from staff: each staffed building of the type earns the bonus, not every one */
    public static function staffBonus(string $type, int $buildingsOfType, array $assignedByRole): float
    {
        if (!isset(self::STAFFED_BONUS[$type]) || $buildingsOfType <= 0) return 1.0;
        [$role, $pct] = self::STAFFED_BONUS[$type];
        return 1 + $pct * min(1, count($assignedByRole[$role] ?? []) / $buildingsOfType);
    }

    /** VIP spending is in proportion to the resort: half a day to two days of ticket revenue */
    public static function vipReward(int $ticketRevenue, int $typeMax): int
    {
        return (int) min($typeMax, max(1000, round($ticketRevenue * mt_rand(50, 200) / 100)));
    }

    /** Extra starting cash after a season reset: 50k per tenfold of starting cash, up to 250k */
    public static function legacyBonus(int $netWorth, int $startingCash): int
    {
        if ($netWorth <= $startingCash || $startingCash <= 0) return 0;
        return (int) min(self::LEGACY_CAP, round(self::LEGACY_PER_DECADE * log10($netWorth / $startingCash)));
    }
}
