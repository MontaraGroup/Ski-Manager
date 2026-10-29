<?php

namespace App\Models;

use CodeIgniter\Model;

class TerrainParkModel extends Model
{
    protected $table = 'terrain_parks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'name',
        'park_type',
        'size',
        'condition_pct',
        'popularity',
        'daily_visitors',
        'daily_revenue',
        'last_event_day',
        'status',
        'build_days_left',
        'slope_id',
    ];

    private static ?array $cachedConfig = null;

    public static function loadParkConfig(): array
    {
        if (self::$cachedConfig !== null) {
            return self::$cachedConfig;
        }

        $descriptions = [
            'halfpipe'    => 'Carved U-shaped snow transition for inverted aerials and pipe tricks.',
            'jump_line'   => 'Progressive kickers and table-top jumps with tuned transition angles.',
            'rail_garden' => 'Diverse metal rails, boxes, and street-style urban jib features.',
            'slopestyle'  => 'Championship multi-tier course combining flowing rails and big air jumps.',
        ];

        $db = db_connect();
        $rows = $db->table('park_feature_types')->orderBy('sort_order')->get()->getResultArray();
        $config = [];

        foreach ($rows as $r) {
            if (! isset($config[$r['type_key']])) {
                $config[$r['type_key']] = [
                    'label'       => $r['label'],
                    'icon'        => $r['icon'],
                    'description' => $descriptions[$r['type_key']] ?? 'Freestyle terrain park feature.',
                    'sizes'       => [],
                ];
            }
            $config[$r['type_key']]['sizes'][$r['size']] = [
                'cost'            => (int) $r['cost'],
                'upkeep'          => (int) $r['upkeep'],
                'build_days'      => (int) $r['build_days'],
                'capacity'        => (int) $r['capacity'],
                'popularity_base' => (int) $r['popularity_base'],
            ];
        }

        self::$cachedConfig = $config;
        return $config;
    }

    public static function getConfig(string $type, string $size): array
    {
        $config = self::loadParkConfig();
        return $config[$type]['sizes'][$size] ?? [];
    }

    public static function getNextSize(string $currentSize): ?string
    {
        return match ($currentSize) {
            'small'  => 'medium',
            'medium' => 'large',
            default  => null,
        };
    }

    public static function getLabel(string $type): string
    {
        $config = self::loadParkConfig();
        return $config[$type]['label'] ?? ucfirst(str_replace('_', ' ', $type));
    }

    public static function getIcon(string $type): string
    {
        $config = self::loadParkConfig();
        $icon = $config[$type]['icon'] ?? null;
        if ($icon && $icon !== 'fa-ring' && $icon !== 'fa-arrow-up-from-ground-water') {
            return $icon;
        }

        return match ($type) {
            'halfpipe'    => 'fa-bezier-curve',
            'jump_line'   => 'fa-angles-up',
            'rail_garden' => 'fa-bars-staggered',
            'slopestyle'  => 'fa-flag-checkered',
            default       => 'fa-person-snowboarding',
        };
    }
}
