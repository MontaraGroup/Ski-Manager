<?php

namespace App\Libraries;

/**
 * Scenic lift rides for sightseeing tourists. Riders per day fall as the ticket price rises (the same
 * curve as lift tickets, Economy::PRICE_ELASTICITY), so revenue = riders x price, plus a fixed daily
 * amount for each upgrade bought. Winter brings a share of the summer tourists.
 */
class Scenic
{
    public const REF_PRICE = 25;      // EUR per ride at which BASE_RIDERS ride
    public const MIN_PRICE = 10;
    public const MAX_PRICE = 100;
    public const BASE_RIDERS = 60;    // per open scenic lift per summer day at REF_PRICE
    public const WINTER_SHARE = 0.3;

    public static function clampPrice(int $price): int
    {
        return max(self::MIN_PRICE, min(self::MAX_PRICE, $price));
    }

    public static function riders(int $price, bool $summer): int
    {
        $factor = min(Economy::MAX_DEMAND, exp(-Economy::PRICE_ELASTICITY * (self::clampPrice($price) / self::REF_PRICE - 1)));
        return (int) round(self::BASE_RIDERS * $factor * ($summer ? 1 : self::WINTER_SHARE));
    }

    /** Daily bonus from the upgrades installed on a scenic lift (upgrades JSON on the row) */
    public static function upgradeBonus(array $row, bool $summer): int
    {
        $keys = json_decode((string) ($row['upgrades'] ?? ''), true);
        $bonus = 0;
        foreach (is_array($keys) ? array_unique($keys) : [] as $k) {
            $bonus += (int) (\App\Controllers\ScenicLifts::SCENIC_UPGRADES[$k]['revenue_boost'] ?? 0);
        }
        return (int) round($bonus * ($summer ? 1 : self::WINTER_SHARE));
    }

    /** Revenue one open scenic lift earns in a day (before the difficulty's revenue multiplier) */
    public static function dailyRevenue(array $row, bool $summer): int
    {
        $price = self::clampPrice((int) ($row['ticket_price'] ?? self::REF_PRICE));
        return self::riders($price, $summer) * $price + self::upgradeBonus($row, $summer);
    }
}
