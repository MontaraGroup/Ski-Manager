<?php

namespace App\Controllers;

use App\Models\MapSegmentModel;
use App\Models\PlayerItemModel;

class ResortMap extends BaseController
{
    public const RESORT_MAPS = [
        'AspenSnowmass' => ['name' => 'Aspen Snowmass', 'location' => 'Colorado, USA', 'width' => 600, 'height' => 340],
        'BigSkyCombo' => ['name' => 'Big Sky', 'location' => 'Montana, USA', 'width' => 600, 'height' => 340],
        'DeerValley' => ['name' => 'Deer Valley', 'location' => 'Utah, USA', 'width' => 600, 'height' => 340],
        'Killington' => ['name' => 'Killington', 'location' => 'Vermont, USA', 'width' => 600, 'height' => 340],
        'PalisadesTahoe' => ['name' => 'Palisades Tahoe', 'location' => 'California, USA', 'width' => 600, 'height' => 340],
        'ParkCity' => ['name' => 'Park City', 'location' => 'Utah, USA', 'width' => 600, 'height' => 340],
        'Vail' => ['name' => 'Vail', 'location' => 'Colorado, USA', 'width' => 600, 'height' => 340],
    ];

    private function getSelectedMap(): array
    {
        $finance = db_connect()->table('player_finances')->where('user_id', auth()->id())->get()->getRowArray();
        $selectedMap = $finance['resort_map'] ?? 'Vail';
        return [$selectedMap, self::RESORT_MAPS[$selectedMap] ?? self::RESORT_MAPS['Vail']];
    }

    public function index(): string
    {
        $model = new MapSegmentModel();
        $db = db_connect();
        $isAdmin = auth()->id() === 1;

        [$selectedMap, $mapConfig] = $this->getSelectedMap();

        $sectors = $db->table('resort_sectors')->where('resort_map', $selectedMap)->orderBy('sort_order')->get()->getResultArray();
        $visibleSectorIds = array_column(array_filter($sectors, fn($s) => $s['visible']), 'id');

        if ($isAdmin) {
            $segments = $model->where('active', 1)->where('resort_map', $selectedMap)->findAll();
        } elseif (!empty($visibleSectorIds)) {
            $segments = $model->where('active', 1)->where('resort_map', $selectedMap)->whereIn('sector', $visibleSectorIds)->findAll();
        } else {
            $segments = [];
        }

        return view('resort_map/index', [
            'segments' => $segments,
            'selectedMap' => $selectedMap,
            'mapConfig' => $mapConfig,
            'sectors' => $sectors,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function changeMap()
    {
        $userId = auth()->id();
        $map = $this->request->getPost('resort_map');
        if (!isset(self::RESORT_MAPS[$map])) {
            return redirect()->to('/map')->with('error', 'Invalid map.');
        }
        db_connect()->table('player_finances')->where('user_id', $userId)->update(['resort_map' => $map]);
        log_activity($userId, 'Resort', 'Changed trail map to ' . self::RESORT_MAPS[$map]['name'], 'fa-solid fa-map');
        return redirect()->to('/map')->with('success', 'Map changed to ' . self::RESORT_MAPS[$map]['name'] . '.');
    }

    public function createSector()
    {
        if (auth()->id() !== 1) return redirect()->to('/map');
        $db = db_connect();
        [$selectedMap] = $this->getSelectedMap();
        $count = $db->table('resort_sectors')->where('resort_map', $selectedMap)->countAllResults();
        $db->table('resort_sectors')->insert([
            'resort_map' => $selectedMap,
            'name' => 'Sector ' . ($count + 1),
            'visible' => 0,
            'sort_order' => $count + 1,
        ]);
        return redirect()->to('/map')->with('success', 'Sector ' . ($count + 1) . ' created.');
    }

    public function toggleSector(int $id)
    {
        if (auth()->id() !== 1) return redirect()->to('/map');
        $db = db_connect();
        $sector = $db->table('resort_sectors')->where('id', $id)->get()->getRowArray();
        if ($sector) {
            $db->table('resort_sectors')->where('id', $id)->update(['visible' => $sector['visible'] ? 0 : 1]);
        }
        return redirect()->to('/map')->with('success', 'Sector visibility toggled.');
    }

    public function deleteSector(int $id)
    {
        if (auth()->id() !== 1) return redirect()->to('/map');
        $db = db_connect();
        $db->table('map_segments')->where('sector', $id)->update(['sector' => 0]);
        $db->table('resort_sectors')->where('id', $id)->delete();
        return redirect()->to('/map')->with('success', 'Sector deleted.');
    }

    public function saveSegment()
    {
        if (!auth()->loggedIn() || auth()->id() !== 1) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $model = new MapSegmentModel();
        [$selectedMap] = $this->getSelectedMap();

        $data = [
            'type' => $this->request->getPost('type'),
            'name' => $this->request->getPost('name'),
            'points' => $this->request->getPost('points'),
            'length_meters' => (int) $this->request->getPost('length_meters'),
            'sector' => (int) $this->request->getPost('sector'),
            'user_id' => auth()->id(),
            'resort_map' => $selectedMap,
        ];

        $model->insert($data);

        return $this->response->setJSON(['success' => true, 'id' => $model->getInsertID()]);
    }

    public function deleteSegment(int $id)
    {
        if (!auth()->loggedIn() || auth()->id() !== 1) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $model = new MapSegmentModel();
        $model->delete($id);

        return $this->response->setJSON(['success' => true]);
    }

    public function getSegments()
    {
        $model = new MapSegmentModel();
        $db = db_connect();
        $isAdmin = auth()->id() === 1;
        [$selectedMap] = $this->getSelectedMap();

        if ($isAdmin) {
            $segments = $model->where('active', 1)->where('resort_map', $selectedMap)->findAll();
        } else {
            $visibleSectorIds = array_column($db->table('resort_sectors')->where('resort_map', $selectedMap)->where('visible', 1)->get()->getResultArray(), 'id');
            if (!empty($visibleSectorIds)) {
                $segments = $model->where('active', 1)->where('resort_map', $selectedMap)->whereIn('sector', $visibleSectorIds)->findAll();
            } else {
                $segments = [];
            }
        }

        return $this->response->setJSON($segments);
    }

    public function buildItem()
    {
        if (!auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $userId = auth()->id();
        $segmentId = (int) $this->request->getPost('segment_id');
        $itemType = $this->request->getPost('item_type');
        $subtype = $this->request->getPost('subtype');
        $difficulty = $this->request->getPost('difficulty');
        $seats = (int) $this->request->getPost('seats');

        $segmentModel = new MapSegmentModel();
        $segment = $segmentModel->find($segmentId);

        if (!$segment) {
            return $this->response->setJSON(['success' => false, 'error' => 'Segment not found']);
        }

        $itemModel = new PlayerItemModel();

        $existing = $itemModel->where('user_id', $userId)->where('segment_id', $segmentId)->first();
        if ($existing) {
            return $this->response->setJSON(['success' => false, 'error' => 'Already built on this segment']);
        }

        $length = (int) $segment['length_meters'];

        if ($itemType === 'lift') {
            $liftNames = [
                'button' => 'Button Lift',
                'chair_fixed' => 'Fixed Chairlift',
                'chair_detach' => 'Detachable Chairlift',
                'gondola' => 'Gondola',
                'cable_car' => 'Cable Car',
            ];
            $capacities = [
                'button' => 1000,
                'chair_fixed' => 2400,
                'chair_detach' => 3400,
                'gondola' => 3500,
                'cable_car' => 4000,
            ];
            $name = ($liftNames[$subtype] ?? 'Lift') . ' ' . ($seats ?: '');
            $capacity = ($capacities[$subtype] ?? 1000) + ($seats * 200);
            $costPerMeter = 2000;
        } else {
            $slopeNames = [
                'downhill' => 'Downhill',
                'crosscountry' => 'Cross-Country',
                'snowpark' => 'Snow Park',
                'luge' => 'Luge',
                'boardercross' => 'Boardercross',
                'halfpipe' => 'Halfpipe',
            ];
            $diffNames = ['green' => 'Green', 'blue' => 'Blue', 'red' => 'Red', 'black' => 'Black'];
            $name = ($slopeNames[$subtype] ?? 'Slope') . ' (' . ($diffNames[$difficulty] ?? '') . ')';
            $capacity = 0;
            $costPerMeter = 600;
        }

        $count = $itemModel->where('user_id', $userId)->where('item_type', $itemType)->countAllResults();
        $name = $name . ' #' . ($count + 1);

        $itemModel->insert([
            'user_id' => $userId,
            'segment_id' => $segmentId,
            'item_type' => $itemType,
            'subtype' => $subtype ?: 'default',
            'name' => trim($name),
            'level' => 1,
            'length_meters' => $length,
            'condition_pct' => 100,
            'capacity' => $capacity,
            'difficulty' => $difficulty,
            'status' => 'open',
            'sector' => (int) $segment['sector'],
        ]);

        $cost = $length * $costPerMeter;
        log_activity($userId, $itemType === 'lift' ? 'Lift' : 'Slope', 'Built ' . trim($name) . ' (' . number_format($length) . 'm) for ' . currency($cost), $itemType === 'lift' ? 'fa-solid fa-cable-car' : 'fa-solid fa-person-skiing');

        return $this->response->setJSON([
            'success' => true,
            'id' => $itemModel->getInsertID(),
            'name' => trim($name),
            'cost' => $cost,
        ]);
    }
    public function saveSectorBoundary(int $id)
    {
        if (auth()->id() !== 1) return $this->response->setStatusCode(401)->setJSON(["error" => "Unauthorized"]);
        $db = db_connect();
        $points = $this->request->getPost("points");
        $db->table("resort_sectors")->where("id", $id)->update(["boundary_points" => $points]);
        return $this->response->setJSON(["success" => true]);
    }
}
