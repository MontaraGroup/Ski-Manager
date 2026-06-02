<?php

namespace App\Controllers;

use App\Models\MapSegmentModel;
use App\Models\PlayerItemModel;

class ResortMap extends BaseController
{
    public function index(): string
    {
        $model = new MapSegmentModel();
        $segments = $model->where('active', 1)->findAll();

        return view('resort_map/index', ['segments' => $segments]);
    }

    public function saveSegment()
    {
        if (!auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $model = new MapSegmentModel();

        $data = [
            'type' => $this->request->getPost('type'),
            'name' => $this->request->getPost('name'),
            'points' => $this->request->getPost('points'),
            'length_meters' => (int) $this->request->getPost('length_meters'),
            'sector' => (int) $this->request->getPost('sector'),
        ];

        $model->insert($data);

        return $this->response->setJSON(['success' => true, 'id' => $model->getInsertID()]);
    }

    public function deleteSegment(int $id)
    {
        if (!auth()->loggedIn()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $model = new MapSegmentModel();
        $model->delete($id);

        return $this->response->setJSON(['success' => true]);
    }

    public function getSegments()
    {
        $model = new MapSegmentModel();
        $segments = $model->where('active', 1)->findAll();

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
}
