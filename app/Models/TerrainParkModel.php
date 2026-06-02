<?php

namespace App\Models;

use CodeIgniter\Model;

class TerrainParkModel extends Model
{
    protected $table = 'terrain_parks';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'name', 'park_type', 'size', 'condition_pct',
        'popularity', 'daily_visitors', 'status', 'build_days_left', 'slope_id',
    ];

    public const PARK_CONFIG = [
        'halfpipe' => [
            'label' => 'Halfpipe',
            'icon' => 'fa-ring',
            'sizes' => [
                'small'  => ['cost' => 45000,  'upkeep' => 800,  'build_days' => 3, 'capacity' => 40,  'popularity_base' => 15],
                'medium' => ['cost' => 90000,  'upkeep' => 1500, 'build_days' => 5, 'capacity' => 80,  'popularity_base' => 30],
                'large'  => ['cost' => 180000, 'upkeep' => 2800, 'build_days' => 8, 'capacity' => 150, 'popularity_base' => 55],
            ],
        ],
        'jump_line' => [
            'label' => 'Jump Line',
            'icon' => 'fa-arrow-up-from-ground-water',
            'sizes' => [
                'small'  => ['cost' => 30000,  'upkeep' => 600,  'build_days' => 2, 'capacity' => 50,  'popularity_base' => 12],
                'medium' => ['cost' => 65000,  'upkeep' => 1200, 'build_days' => 4, 'capacity' => 100, 'popularity_base' => 25],
                'large'  => ['cost' => 140000, 'upkeep' => 2200, 'build_days' => 7, 'capacity' => 200, 'popularity_base' => 45],
            ],
        ],
        'rail_garden' => [
            'label' => 'Rail Garden',
            'icon' => 'fa-grip-lines',
            'sizes' => [
                'small'  => ['cost' => 20000,  'upkeep' => 400,  'build_days' => 1, 'capacity' => 35,  'popularity_base' => 10],
                'medium' => ['cost' => 45000,  'upkeep' => 800,  'build_days' => 3, 'capacity' => 70,  'popularity_base' => 20],
                'large'  => ['cost' => 95000,  'upkeep' => 1500, 'build_days' => 5, 'capacity' => 130, 'popularity_base' => 38],
            ],
        ],
        'slopestyle' => [
            'label' => 'Slopestyle Course',
            'icon' => 'fa-mountain-sun',
            'sizes' => [
                'small'  => ['cost' => 55000,  'upkeep' => 1000, 'build_days' => 4, 'capacity' => 60,  'popularity_base' => 20],
                'medium' => ['cost' => 120000, 'upkeep' => 2000, 'build_days' => 6, 'capacity' => 120, 'popularity_base' => 40],
                'large'  => ['cost' => 250000, 'upkeep' => 3500, 'build_days' => 10, 'capacity' => 250, 'popularity_base' => 70],
            ],
        ],
    ];

    public static function getConfig(string $type, string $size): array
    {
        return self::PARK_CONFIG[$type]['sizes'][$size] ?? [];
    }

    public static function getLabel(string $type): string
    {
        return self::PARK_CONFIG[$type]['label'] ?? ucfirst($type);
    }

    public static function getIcon(string $type): string
    {
        return self::PARK_CONFIG[$type]['icon'] ?? 'fa-mountain';
    }
}
