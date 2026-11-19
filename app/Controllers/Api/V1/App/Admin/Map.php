<?php

namespace App\Controllers\Api\V1\App\Admin;

use App\Controllers\Api\V1\App\AppResponses;
use App\Controllers\ResortMap;
use App\Models\MapSegmentModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Admin Map Studio mobile API controller.
 * Reuses ResortMap logic and strictly requires admin access on all endpoints.
 */
class Map extends ResortMap
{
    use AppResponses;

    /**
     * Reject non-admin access with HTTP 403.
     */
    protected function requireAdmin(): ?ResponseInterface
    {
        if (!$this->isAdmin()) {
            return $this->fail('Forbidden: admin access required.', 403, ['code' => 'forbidden']);
        }
        return null;
    }

    /**
     * List all available resort maps with dimensions and progressive image URLs.
     */
    public function maps(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        $currentMap = $this->getSelectedMap();
        $maps = self::getAllResortMaps(true);

        return $this->ok([
            'currentMap' => $currentMap,
            'maps'       => $maps,
        ]);
    }

    /**
     * Get progressive image URLs and dimensions for a specific resort map key.
     */
    public function images(string $mapKey): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        if (!isset(self::RESORT_MAPS[$mapKey])) {
            return $this->fail('Unknown resort map: ' . $mapKey, 404, ['code' => 'not_found']);
        }

        return $this->ok(self::mapImageUrls($mapKey, true));
    }

    /**
     * Switch active resort map.
     */
    public function changeMap(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::changeMap());
    }

    /**
     * Return all active segments and sectors for the active (or requested) resort map.
     */
    public function segments(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        $resortMap = $this->getSelectedMap();
        $db = db_connect();

        $sectors = $db->table('resort_sectors')
            ->where('resort_map', $resortMap)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $segModel = new MapSegmentModel();
        $segments = $segModel->where('resort_map', $resortMap)
            ->where('active', 1)
            ->findAll();

        $mapInfo = self::mapImageUrls($resortMap, true);

        return $this->ok([
            'resortMap' => $resortMap,
            'mapConfig' => $mapInfo,
            'sectors'   => $sectors,
            'segments'  => $segments,
        ]);
    }

    /**
     * Create or update a segment.
     */
    public function saveSegment(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::saveSegment());
    }

    /**
     * Delete a segment by ID.
     */
    public function deleteSegment($id = null): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::deleteSegment($id));
    }

    /**
     * Save lift midstations.
     */
    public function saveMidstation($id = null): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::saveMidstation($id));
    }

    /**
     * Create a new sector.
     */
    public function createSector(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::createSector());
    }

    /**
     * Create a sector with boundary points.
     */
    public function createSectorWithBoundary(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::createSectorWithBoundary());
    }

    /**
     * Save a sector boundary polygon.
     */
    public function saveSectorBoundary($id = null): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::saveSectorBoundary($id));
    }

    /**
     * Toggle sector visibility.
     */
    public function toggleSector($id = null): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::toggleSector($id));
    }

    /**
     * Delete a sector.
     */
    public function deleteSector($id = null): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::deleteSector($id));
    }

    /**
     * Auto-assign segments to sectors via point-in-polygon ray-casting.
     */
    public function autoAssignSectors(): ResponseInterface
    {
        if ($deny = $this->requireAdmin()) {
            return $deny;
        }

        return $this->fromAction(parent::autoAssignSectors());
    }
}
