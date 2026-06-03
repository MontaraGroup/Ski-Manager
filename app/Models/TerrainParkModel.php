<?php

namespace App\Models;

use CodeIgniter\Model;

class TerrainParkModel extends Model
{
    protected $table = 'terrain_parks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'name', 'park_type', 'size', 'condition_pct',
        'status', 'build_days_left', 'slope_id',
    ];

    private static ?array $cachedConfig = null;

    public static function loadParkConfig(): array
    {
        if (self::$cachedConfig !== null) return self::$cachedConfig;

        $db = db_connect();
        $rows = $db->table('park_feature_types')->orderBy('sort_order')->get()->getResultArray();
        $config = [];
        foreach ($rows as $r) {
            if (!isset($config[$r['type_key']])) {
                $config[$r['type_key']] = ['label' => $r['label'], 'icon' => $r['icon'], 'sizes' => []];
            }
            $config[$r['type_key']]['sizes'][$r['size']] = [
                'cost' => (int) $r['cost'], 'upkeep' => (int) $r['upkeep'],
                'build_days' => (int) $r['build_days'], 'capacity' => (int) $r['capacity'],
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

    public static function getLabel(string $type): string
    {
        $config = self::loadParkConfig();
        return $config[$type]['label'] ?? ucfirst($type);
    }

    public static function getIcon(string $type): string
    {
        $config = self::loadParkConfig();
        return $config[$type]['icon'] ?? 'fa-mountain';
    }
}
