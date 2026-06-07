<?php

namespace App\Controllers;

use App\Models\MapSegmentModel;

class ResortMap extends BaseController
{
    private const RESORT_MAPS = [
        'ParkCity'       => ['name' => 'Park City',       'location' => 'Utah, USA',       'image' => '/img/ParkCity.jpg',       'width' => 600, 'height' => 340],
        'DeerValley'     => ['name' => 'Deer Valley',     'location' => 'Utah, USA',       'image' => '/img/DeerValley.jpg',     'width' => 600, 'height' => 340],
        'AspenSnowmass'  => ['name' => 'Aspen Snowmass',  'location' => 'Colorado, USA',   'image' => '/img/AspenSnowmass.jpg',  'width' => 600, 'height' => 340],
        'BigSkyCombo'    => ['name' => 'Big Sky',         'location' => 'Montana, USA',    'image' => '/img/BigSky.jpg',         'width' => 600, 'height' => 340],
        'Vail'           => ['name' => 'Vail',            'location' => 'Colorado, USA',   'image' => '/img/Vail.jpg',           'width' => 600, 'height' => 340],
        'PalisadesTahoe' => ['name' => 'Palisades Tahoe', 'location' => 'California, USA', 'image' => '/img/PalisadesTahoe.jpg', 'width' => 600, 'height' => 340],
        'Killington'     => ['name' => 'Killington',      'location' => 'Vermont, USA',    'image' => '/img/Killington.jpg',     'width' => 600, 'height' => 340],
    ];

    public static function getResortMapNames(): array { return array_map(fn($m) => $m["name"], self::RESORT_MAPS); }
    private function getSelectedMap(): string
    {
        $userId = auth()->id();
        if ($userId) {
            $fin = db_connect()->table('player_finances')
                ->where('user_id', $userId)
                ->get()->getRowArray();
            if ($fin && !empty($fin['resort_map'])) {
                return $fin['resort_map'];
            }
        }
        return 'ParkCity';
    }

    private function isAdmin(): bool
    {
        return auth()->id() === 1;
    }

    public function index()
    {
        $resortMap  = $this->getSelectedMap();
        $mapConfig  = self::RESORT_MAPS[$resortMap] ?? self::RESORT_MAPS['ParkCity'];
        $isAdmin    = $this->isAdmin();
        $userId     = auth()->id();
        $db         = db_connect();

        $sectorsQuery = $db->table('resort_sectors')
            ->where('resort_map', $resortMap)
            ->orderBy('sort_order', 'ASC');
        if (!$isAdmin) {
            $sectorsQuery->where('visible', 1);
        }
        $sectors = $sectorsQuery->get()->getResultArray();

        $segModel = new MapSegmentModel();
        $segQuery = $segModel->where('resort_map', $resortMap)->where('active', 1);
        if (!$isAdmin) {
            $visibleIds = array_column($sectors, 'id');
            if ($visibleIds) {
                $segQuery->groupStart()
                    ->whereIn('sector', $visibleIds)
                    ->orWhere('sector', '0')
                    ->orWhere('sector IS NULL')
                    ->groupEnd();
            }
        }
        $segments = $segQuery->findAll();

        $builtSegmentIds = [];
        if ($userId) {
            $built = $db->table('player_items')
                ->select('segment_id')
                ->where('user_id', $userId)
                ->where('segment_id IS NOT NULL')
                ->get()->getResultArray();
            $builtSegmentIds = array_column($built, 'segment_id');
        }

        $releasedSectorIds = array_column(array_filter($sectors, fn($s) => $s['released']), 'id');
        $lifts = $slopes = 0;
        foreach ($segments as $s) {
            $s['type'] === 'lift' ? $lifts++ : $slopes++;
        }

        return view('resort_map/index', [
            'resortMap'       => $resortMap,
            'resortMaps'      => self::RESORT_MAPS,
            'mapConfig'       => $mapConfig,
            'segments'        => $segments,
            'sectors'         => $sectors,
            'builtSegmentIds' => $builtSegmentIds,
            'isAdmin'         => $isAdmin,
            'liftCount'       => $lifts,
            'slopeCount'      => $slopes,
            'segmentCount'    => count($segments),
            'releasedSectorIds' => $releasedSectorIds,
        ]);
    }

    public function getSegments()
    {
        $resortMap = $this->getSelectedMap();
        $isAdmin   = $this->isAdmin();
        $db        = db_connect();
        $segModel  = new MapSegmentModel();
        $query     = $segModel->where('resort_map', $resortMap)->where('active', 1);

        if (!$isAdmin) {
            $vis = $db->table('resort_sectors')
                ->select('name')
                ->where('resort_map', $resortMap)
                ->where('visible', 1)
                ->get()->getResultArray();
            $ids = array_column($vis, 'id');
            if ($ids) {
                $query->groupStart()
                    ->whereIn('sector', $ids)
                    ->orWhere('sector', '0')
                    ->orWhere('sector IS NULL')
                    ->groupEnd();
            }
        }

        return $this->response->setJSON($query->findAll());
    }

    public function saveSegment()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        $data  = $this->request->getJSON(true);
        $model = new MapSegmentModel();

        $row = [
            'type'          => $data['type'] ?? 'lift',
            'name'          => $data['name'] ?? 'Unnamed',
            'points'        => json_encode($data['points'] ?? []),
            'length_meters' => (int) ($data['length_meters'] ?? 0),
            'sector'        => $data['sector'] ?? '',
            'difficulty'    => $data['difficulty'] ?? '',
            'resort_map'    => $this->getSelectedMap(),
            'active'        => 1,
        ];

        if (!empty($data['id'])) {
            $model->update($data['id'], $row);
            return $this->response->setJSON(['success' => true, 'id' => $data['id']]);
        }

        $id = $model->insert($row);
        return $this->response->setJSON(['success' => true, 'id' => $id]);
    }

    public function deleteSegment($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }

        
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID']);
        }

        (new MapSegmentModel())->delete($id);
        return $this->response->setJSON(['success' => true]);
    }

    public function buildItem()
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Login required']);
        }

        $data      = $this->request->getJSON(true);
        $segmentId = $data['segment_id'] ?? null;
        $liftType  = $data['lift_type']  ?? null;
        $seats     = (int) ($data['seats'] ?? 4);
        $slopeType = $data['slope_type'] ?? null;

        if (!$segmentId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing segment']);
        }

        $segment = (new MapSegmentModel())->find($segmentId);
        if (!$segment) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Segment not found']);
        }

        $db = db_connect();

        $exists = $db->table('player_items')
            ->where('user_id', $userId)
            ->where('segment_id', $segmentId)
            ->countAllResults();
        if ($exists) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Already built']);
        }

        $length = (float) ($segment['length_meters'] ?? 0);
        $cost   = $this->calcCost($segment['type'], $liftType, $seats, $length);

        $fin  = $db->table('player_finances')->where('user_id', $userId)->get()->getRowArray();
        $cash = (int) ($fin['cash'] ?? 0);
        if ($cash < $cost) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Not enough money', 'cost' => $cost, 'cash' => $cash]);
        }

        $db->table('player_finances')->where('user_id', $userId)->update(['cash' => $cash - $cost]);

        $db->table('player_items')->insert([
            'user_id'    => $userId,
            'segment_id' => $segmentId,
            'item_type'     => $segment['type'] === 'lift' ? 'lift' : 'slope',
            'subtype'       => $segment['type'] === 'lift' ? $liftType : ($slopeType ?? $segment['type']),
            'name'          => $segment['name'] ?? 'Unnamed',
            'length_meters' => (int) ($segment['length_meters'] ?? 0),
            'capacity'      => match($segment['type'] === 'lift' ? $liftType : '') { 'button' => 1000, 'chair_fixed' => 2400, 'chair_detach' => 3400, 'gondola' => 3500, 'cable_car' => 4000, default => 0 },
            'difficulty'    => $segment['difficulty'] ?? null,
            'sector'        => (int) ($segment['sector'] ?? 0),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->response->setJSON(['success' => true, 'cost' => $cost, 'new_cash' => $cash - $cost]);
    }

    private function calcCost(string $type, ?string $liftType, int $seats, float $meters): int
    {
        if ($type === 'lift') {
            $base = match ($liftType) {
                'button'     => 800,
                'chair_fixed'  => 1500,
                'chair_detach' => 2500,
                'gondola'    => 4000,
                'cable_car'  => 6000,
                default      => 2000,
            };
            $mult = match ($seats) {
                1 => 0.7, 2 => 1.0, 3 => 1.15, 4 => 1.3, 6 => 1.6, 8 => 2.0, 10 => 2.5, 20 => 4.0, 30 => 5.0, default => 1.0,
            };
            return (int) ($meters * $base * $mult);
        }
        return (int) ($meters * 500);
    }

    public function saveMidstation($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $data = $this->request->getJSON(true);
        $id   = $data['segment_id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID']);
        }
        (new MapSegmentModel())->update($id, ['midstations' => json_encode($data['midstations'] ?? [])]);
        return $this->response->setJSON(['success' => true]);
    }

    public function createSector()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $data = $this->request->getJSON(true);
        $db   = db_connect();
        $db->table('resort_sectors')->insert([
            'resort_map'      => $this->getSelectedMap(),
            'name'            => $data['name'] ?? 'New Sector',
            'description'     => $data['description'] ?? '',
            'color'           => $data['color'] ?? '#3b82f6',
            'visible'         => 0,
            'released'        => 0,
            'sort_order'      => 0,
            'boundary_points' => '[]',
        ]);
        return $this->response->setJSON(['success' => true, 'id' => $db->insertID()]);
    }

    public function toggleSector($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID']);
        }
        $db     = db_connect();
        $sector = $db->table('resort_sectors')->where('id', $id)->get()->getRowArray();
        if (!$sector) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
        }
        $db->table('resort_sectors')->where('id', $id)->update(['visible' => $sector['visible'] ? 0 : 1]);
        return $this->response->setJSON(['success' => true, 'visible' => !$sector['visible']]);
    }

    public function deleteSector($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID']);
        }
        db_connect()->table('resort_sectors')->where('id', $id)->delete();
        return $this->response->setJSON(['success' => true]);
    }

    public function saveSectorBoundary($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $data = $this->request->getJSON(true);
        $id   = $data['id'] ?? null;
        if (!$id) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID']);
        }
        db_connect()->table('resort_sectors')->where('id', $id)->update([
            'boundary_points' => json_encode($data['boundary_points'] ?? []),
        ]);
        return $this->response->setJSON(['success' => true]);
    }

    public function createSectorWithBoundary()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $data = $this->request->getJSON(true);
        $db   = db_connect();
        $db->table('resort_sectors')->insert([
            'resort_map'      => $this->getSelectedMap(),
            'name'            => $data['name'] ?? 'New Sector',
            'description'     => $data['description'] ?? '',
            'color'           => $data['color'] ?? '#3b82f6',
            'visible'         => 0,
            'released'        => 0,
            'sort_order'      => 0,
            'boundary_points' => json_encode($data['boundary_points'] ?? []),
        ]);
        return $this->response->setJSON(['success' => true, 'id' => $db->insertID()]);
    }

    public function autoAssignSectors()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized']);
        }
        $resortMap = $this->getSelectedMap();
        $db        = db_connect();
        $sectors   = $db->table('resort_sectors')->where('resort_map', $resortMap)->get()->getResultArray();
        $segments  = (new MapSegmentModel())->where('resort_map', $resortMap)->where('active', 1)->findAll();
        $count     = 0;

        foreach ($segments as $seg) {
            $pts = json_decode($seg['points'], true);
            if (empty($pts)) continue;
            $ref = $pts[0];
            foreach ($sectors as $sec) {
                $bp = json_decode($sec['boundary_points'], true);
                if (empty($bp)) continue;
                if ($this->pip($ref, $bp)) {
                    (new MapSegmentModel())->update($seg['id'], ['sector' => $sec['name']]);
                    $count++;
                    break;
                }
            }
        }
        return $this->response->setJSON(['success' => true, 'assigned' => $count]);
    }

    private function pip(array $pt, array $poly): bool
    {
        $x = $pt[1] ?? $pt['lng'] ?? 0;
        $y = $pt[0] ?? $pt['lat'] ?? 0;
        $in = false;
        $n  = count($poly);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $poly[$i][0] ?? 0; $xi = $poly[$i][1] ?? 0;
            $yj = $poly[$j][0] ?? 0; $xj = $poly[$j][1] ?? 0;
            if (($yi > $y) !== ($yj > $y) && $x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi) {
                $in = !$in;
            }
        }
        return $in;
    }
}
