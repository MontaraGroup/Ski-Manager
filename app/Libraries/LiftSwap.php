<?php

namespace App\Libraries;

use App\Controllers\ResortMap;

/**
 * Replacing a built lift with another system on the same line, instead of demolishing it and
 * building again. The price is what demolish + rebuild would cost (new build less the 25% salvage)
 * with a small discount for keeping the line. A swap to a cheaper lift pays back exactly what
 * demolish + rebuild would, so swapping back and forth can never make money.
 */
class LiftSwap
{
    /** Share of the new lift's build cost taken off when the swap costs money */
    public const DISCOUNT = 0.05;

    /** Seats a built lift has; lifts built before seats were stored count as their type's default, like ResortMap::buildCost */
    public static function currentSeats(array $item): int
    {
        return (int) ($item['seats'] ?? 0) ?: match ($item['subtype'] ?? '') {
            'button' => 1, 'chair_detach' => 4, 'gondola' => 6, 'cable_car' => 20, default => 2,
        };
    }

    /** True for a lift type and seat count the build menu offers */
    public static function isValid(string $type, int $seats): bool
    {
        return in_array($seats, ResortMap::SEAT_OPTIONS[$type] ?? [], true);
    }

    /**
     * @return array{price: int, rebuild: int, newCost: int} price > 0 is paid, price < 0 is paid back;
     *         rebuild is what demolish + rebuild would come to, on the same sign convention
     */
    public static function quote(array $item, string $type, int $seats): array
    {
        $newCost = ResortMap::buildCost(['item_type' => 'lift', 'subtype' => $type, 'seats' => $seats, 'length_meters' => $item['length_meters'] ?? 0]);
        $rebuild = $newCost - ResortMap::salvageValue($item);
        $price = $rebuild > 0 ? max(0, $rebuild - (int) round($newCost * self::DISCOUNT)) : $rebuild;
        return ['price' => $price, 'rebuild' => $rebuild, 'newCost' => $newCost];
    }

    /** Every system the lift can be swapped to, with the numbers the confirm dialog shows */
    public static function options(array $item): array
    {
        if (($item['item_type'] ?? '') !== 'lift') return [];
        $meters = (int) ($item['length_meters'] ?? 0);
        $current = [(string) ($item['subtype'] ?? ''), self::currentSeats($item)];
        $options = [];
        foreach (ResortMap::SEAT_OPTIONS as $type => $seatList) {
            foreach ($seatList as $seats) {
                if ([$type, $seats] === $current) continue;
                $q = self::quote($item, $type, $seats);
                $options[] = [
                    'lift_type' => $type,
                    'seats'     => $seats,
                    'capacity'  => Economy::liftCapacityPerHour($type, $seats),
                    'run_cost'  => (int) round(Economy::liftRunningCost($type, $meters)),
                    'price'     => $q['price'],
                    'rebuild'   => $q['rebuild'],
                ];
            }
        }
        return $options;
    }
}
