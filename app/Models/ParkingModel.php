<?php

namespace App\Models;

use CodeIgniter\Model;

class ParkingModel extends Model
{
    protected $table = 'parking';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'name', 'parking_type', 'capacity', 'occupied',
        'fee_per_day', 'daily_revenue', 'condition_pct', 'status', 'build_days_left',
    ];

    public const PARKING_CONFIG = [
        'surface_lot' => [
            'label' => 'Surface Lot',
            'icon' => 'fa-square-parking',
            'cost' => 15000,
            'upkeep' => 200,
            'build_days' => 1,
            'capacity' => 100,
            'default_fee' => 10.00,
            'condition_decay' => 0.3,
        ],
        'garage' => [
            'label' => 'Parking Garage',
            'icon' => 'fa-warehouse',
            'cost' => 120000,
            'upkeep' => 1500,
            'build_days' => 5,
            'capacity' => 500,
            'default_fee' => 25.00,
            'condition_decay' => 0.2,
        ],
        'shuttle_stop' => [
            'label' => 'Shuttle Stop',
            'icon' => 'fa-bus',
            'cost' => 35000,
            'upkeep' => 800,
            'build_days' => 2,
            'capacity' => 200,
            'default_fee' => 5.00,
            'condition_decay' => 0.4,
        ],
        'village_gondola' => [
            'label' => 'Village Gondola',
            'icon' => 'fa-cable-car',
            'cost' => 300000,
            'upkeep' => 3000,
            'build_days' => 8,
            'capacity' => 800,
            'default_fee' => 15.00,
            'condition_decay' => 0.5,
        ],
    ];

    public static function getConfig(string $type): array
    {
        return self::PARKING_CONFIG[$type] ?? [];
    }

    public static function getLabel(string $type): string
    {
        return self::PARKING_CONFIG[$type]['label'] ?? ucfirst($type);
    }

    public static function getIcon(string $type): string
    {
        return self::PARKING_CONFIG[$type]['icon'] ?? 'fa-square-parking';
    }

    public static function getTotalCapacity(array $parkingFacilities): int
    {
        $total = 0;
        foreach ($parkingFacilities as $p) {
            if ($p['status'] === 'open' || $p['status'] === 'full') {
                $total += $p['capacity'];
            }
        }
        return $total;
    }
}
