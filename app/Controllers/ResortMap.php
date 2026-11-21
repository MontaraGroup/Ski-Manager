<?php

namespace App\Controllers;

use App\Libraries\Ledger;
use App\Libraries\LiftSwap;
use App\Models\MapSegmentModel;

class ResortMap extends BaseController
{
    protected const RESORT_MAPS = [
        'ParkCity'       => ['name' => 'Park City',       'location' => 'Utah, USA',       'image' => '/img/ParkCity.jpg',       'admin_image' => '/img/ParkCity-Big.jpg', 'width' => 600, 'height' => 340],
        'DeerValley'     => ['name' => 'Deer Valley',     'location' => 'Utah, USA',       'image' => '/img/DeerValley.jpg',     'width' => 600, 'height' => 340],
        'AspenSnowmass'  => ['name' => 'Aspen Snowmass',  'location' => 'Colorado, USA',   'image' => '/img/AspenSnowmass.jpg',  'width' => 600, 'height' => 340],
        'BigSkyCombo'    => ['name' => 'Big Sky',         'location' => 'Montana, USA',    'image' => '/img/BigSkyCombo.jpg',    'width' => 600, 'height' => 340],
        'Vail'           => ['name' => 'Vail',            'location' => 'Colorado, USA',   'image' => '/img/Vail.jpg',           'width' => 600, 'height' => 340],
        'PalisadesTahoe' => ['name' => 'Palisades Tahoe', 'location' => 'California, USA', 'image' => '/img/PalisadesTahoe.jpg', 'width' => 600, 'height' => 340],
        'Killington'     => ['name' => 'Killington',      'location' => 'Vermont, USA',    'image' => '/img/Killington.jpg',     'width' => 600, 'height' => 340],
    ];

    public static function getResortMapNames(): array { return array_map(fn($m) => $m["name"], self::RESORT_MAPS); }

    public static function mapImageUrls(string $mapKey, bool $isAdmin = true): array
    {
        $config = self::RESORT_MAPS[$mapKey] ?? self::RESORT_MAPS['ParkCity'];
        $image = ($isAdmin && !empty($config['admin_image'])) ? $config['admin_image'] : $config['image'];
        $webp = self::webpSizes((string)$image);
        $basePath = preg_replace('/\.jpe?g$/i', '', $image);
        return [
            'key'         => $mapKey,
            'name'        => $config['name'],
            'location'    => $config['location'],
            'width'       => $config['width'] ?? 600,
            'height'      => $config['height'] ?? 340,
            'image'       => $image,
            'admin_image' => $config['admin_image'] ?? null,
            'webp'        => $webp,
            'urls'        => [
                'low'          => $basePath . ($webp['low'] ? '_low.webp' : '_low.jpg'),
                'med'          => $basePath . ($webp['med'] ? '_med.webp' : '_med.jpg'),
                'full'         => $webp['full'] ? ($basePath . '.webp') : $image,
                'fallbackLow'  => $basePath . '_low.jpg',
                'fallbackMed'  => $basePath . '_med.jpg',
                'fallbackFull' => $image,
            ],
        ];
    }

    public static function getAllResortMaps(bool $isAdmin = true): array
    {
        $result = [];
        foreach (self::RESORT_MAPS as $key => $cfg) {
            $result[$key] = self::mapImageUrls($key, $isAdmin);
        }
        return $result;
    }

    protected function getSelectedMap(?string $requestedMap = null): string
    {
        $candidate = $requestedMap;
        if (!$candidate && $this->request) {
            $candidate = $this->request->getGet('map') ?? $this->request->getGet('resort_map');
            if (!$candidate) {
                $json = $this->request->getJSON(true);
                if (is_array($json)) {
                    $candidate = $json['map'] ?? $json['resort_map'] ?? null;
                }
            }
        }
        if ($candidate && isset(self::RESORT_MAPS[$candidate])) {
            return $candidate;
        }

        $userId = auth()->id();
        if ($userId) {
            $fin = db_connect()->table('player_finances')
                ->where('user_id', $userId)
                ->get()->getRowArray();
            if ($fin && !empty($fin['resort_map']) && isset(self::RESORT_MAPS[$fin['resort_map']])) {
                return $fin['resort_map'];
            }
        }
        return 'ParkCity';
    }

    protected function isAdmin(): bool
    {
        if (!function_exists('auth') || !auth()->loggedIn()) {
            return false;
        }
        $user = auth()->user();
        return auth()->id() === 1 || ($user && $user->inGroup('admin', 'superadmin'));
    }

    public function changeMap()
    {
        $userId = auth()->id();
        if (!$userId) return redirect()->to('/login');

        $json = $this->request->getJSON(true);
        $map = $this->request->getPost('map') ?? $this->request->getPost('resort_map') ?? ($json['map'] ?? $json['resort_map'] ?? null);

        // Check either key or name
        $foundKey = null;
        if ($map && isset(self::RESORT_MAPS[$map])) {
            $foundKey = $map;
        } else {
            foreach (self::RESORT_MAPS as $k => $info) {
                if ($info['name'] === $map) {
                    $foundKey = $k;
                    break;
                }
            }
        }

        $isJsonOrAjax = $this->request->isAJAX() ||
            str_contains((string) $this->request->getHeaderLine('Accept'), 'application/json') ||
            str_starts_with(trim($this->request->getUri()->getPath(), '/'), 'api/');

        if (!$foundKey) {
            if ($isJsonOrAjax) {
                return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid map selected.', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }
            return redirect()->to('/map')->with('error', 'Invalid map selected.');
        }

        $db = db_connect();
        $db->table('player_finances')->where('user_id', $userId)->update([
            'resort_map' => $foundKey,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($isJsonOrAjax) {
            return $this->response->setJSON(['success' => true, 'map' => $foundKey, 'mapName' => self::RESORT_MAPS[$foundKey]['name'], 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        return redirect()->to('/map')->with('success', 'Switched trail map to ' . self::RESORT_MAPS[$foundKey]['name'] . '.');
    }

    public function index()
    {
        $page = $this->pageData();
        return view('resort_map/index', [
            'page'      => $page,
            'mapConfig' => $page['mapConfig'],
        ]);
    }

    /** What the page shows; the mobile API sends the same data (Api\\V1\\App) */
    protected function pageData(): array
    {
        $resortMap  = $this->getSelectedMap();
        $mapConfig  = self::RESORT_MAPS[$resortMap] ?? self::RESORT_MAPS['ParkCity'];
        $isAdmin    = $this->isAdmin();
        // Admins trace runs over <Map>-Big.jpg (the same view with lifts and runs drawn in) when it is on the server
        if ($isAdmin) {
            $adminImage = $mapConfig['admin_image'] ?? preg_replace('/\.jpe?g$/i', '-Big.jpg', $mapConfig['image']);
            if (is_file(FCPATH . ltrim($adminImage, '/'))) {
                $mapConfig['image'] = $adminImage;
            }
        }
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
        $builtItems      = [];
        $userCash        = 0;
        if ($userId) {
            $built = $db->table('player_items')
                ->select('id, segment_id, item_type, subtype, name, length_meters, capacity, seats, difficulty, sector, status, snow_quality')
                ->where('user_id', $userId)
                ->where('segment_id IS NOT NULL')
                ->get()->getResultArray();
            $builtSegmentIds = array_column($built, 'segment_id');
            foreach ($built as $b) {
                $builtItems[(int) $b['segment_id']] = self::withSwaps($b + ['salvage' => self::salvageValue($b)]);
            }
            $finRow   = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
            $userCash = (int) ($finRow['cash'] ?? 0);
        }

        $releasedSectorIds = array_column(array_filter($sectors, fn($s) => $s['released']), 'id');
        $lifts = $slopes = 0;
        foreach ($segments as $s) {
            $s['type'] === 'lift' ? $lifts++ : $slopes++;
        }

        $distUnit   = function_exists('distanceUnit') ? distanceUnit() : 'm';
        $isImperial = function_exists('isImperial') ? isImperial() : false;
        $userCashText = function_exists('currency') ? currency($userCash) : '$' . number_format($userCash);

        $page = [
            'resortMap'         => $resortMap,
            'resortMaps'        => self::RESORT_MAPS,
            'mapConfig'         => $mapConfig + ['webp' => self::webpSizes((string) $mapConfig['image']), 'tiles' => self::tiles((string) $mapConfig['image'])],
            'segments'          => $segments,
            'sectors'           => $sectors,
            'builtSegmentIds'   => $builtSegmentIds,
            'builtItems'        => $builtItems,
            'releasedSectorIds' => $releasedSectorIds,
            'userCash'          => $userCash,
            'userCashText'      => $userCashText,
            'isAdmin'           => $isAdmin,
            'liftCount'         => $lifts,
            'slopeCount'        => $slopes,
            'segmentCount'      => count($segments),
            'distUnit'          => $distUnit,
            'isImperial'        => $isImperial,
            'economyV2'         => (function_exists('featureEnabled') && featureEnabled('economy_v2')) ? [
                'capacity' => \App\Libraries\Economy::LIFT_CAPACITY,
                'maxSeats' => \App\Libraries\Economy::LIFT_MAX_SEATS,
                'liftRun'  => \App\Libraries\Economy::LIFT_RUN_PER_M,
                'slopeRun' => \App\Libraries\Economy::SLOPE_RUN_PER_M,
                'perDay'   => \App\Libraries\Economy::LIFT_HOURS / \App\Libraries\Economy::RIDES_PER_SKIER,
                'liftKwh'  => \App\Libraries\Utilities::LIFT_KWH_PER_M,
            ] : null,
            // The same table calcCost() charges with, so the page never shows a different price
            'costs'             => [
                'liftPerM'    => self::LIFT_COST_PER_M,
                'seatMult'    => self::SEAT_COST_MULT,
                'seatOptions' => self::SEAT_OPTIONS,
                'slopePerM'   => self::SLOPE_COST_PER_M,
            ],
            // Next nightly settle, shown in the player's own time after their first lift and slope
            'settleAt'          => self::nextSettleAt(),
            'csrf'              => [
                'name' => csrf_token(),
                'hash' => csrf_hash(),
            ],
        ];

        return $page;
    }

    /**
     * Which map sizes have a WebP copy next to the JPEG (low, med, full). The copies are made on the
     * server (often 15-30% smaller); a map without them keeps loading the JPEGs.
     */
    /**
     * The map's tile set (scripts/make_map_tiles.py, run by deploy.sh), or null until it has been built:
     * the page then falls back to the low/med/full images. meta.json is written last, so its presence means
     * every tile is there; its version goes in the URL because /img/ is cached as immutable.
     */
    public static function tiles(string $image): ?array
    {
        $name = basename((string) preg_replace('/\.jpe?g$/i', '', $image));
        $meta = FCPATH . 'img/tiles/v2/' . $name . '/meta.json';
        if (!preg_match('/^[A-Za-z0-9_-]+$/', $name) || !is_file($meta)) return null;
        $info = json_decode((string) file_get_contents($meta), true);
        if (!is_array($info) || empty($info['version'])) return null;
        return [
            'url'     => '/img/tiles/v2/' . $name . '/{z}/{x}_{y}.webp?v=' . rawurlencode((string) $info['version']),
            'maxZoom' => (int) ($info['maxZoom'] ?? 4),
        ];
    }

    public static function webpSizes(string $image): array
    {
        $base = FCPATH . ltrim(preg_replace('/\.jpe?g$/i', '', $image), '/');
        return [
            'low'  => is_file($base . '_low.webp'),
            'med'  => is_file($base . '_med.webp'),
            'full' => is_file($base . '.webp'),
        ];
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
                ->select('id')
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
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $data  = $this->request->getJSON(true) ?? [];
        $model = new MapSegmentModel();

        $rawPoints = $data['points'] ?? [];
        if (is_string($rawPoints)) {
            $rawPoints = json_decode($rawPoints, true) ?? [];
        }
        $cleanPoints = [];
        foreach ($rawPoints as $pt) {
            if (is_array($pt) && count($pt) >= 2) {
                $cleanPoints[] = [round((float) ($pt[0] ?? 0), 4), round((float) ($pt[1] ?? 0), 4)];
            } elseif (is_array($pt) && isset($pt['lat'], $pt['lng'])) {
                $cleanPoints[] = [round((float) $pt['lat'], 4), round((float) $pt['lng'], 4)];
            }
        }

        if (count($cleanPoints) < 2) {
            return $this->response->setStatusCode(400)->setJSON([
                'success'    => false,
                'error'      => 'A segment requires at least 2 points.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $type       = trim((string) ($data['type'] ?? 'lift')) ?: 'lift';
        $difficulty = $type === 'lift' ? '' : trim((string) ($data['difficulty'] ?? ''));
        $sectorId   = (int) ($data['sector'] ?? 0);
        $lengthM    = max(1, (int) ($data['length_meters'] ?? 0));
        $name       = trim((string) ($data['name'] ?? '')) ?: 'Unnamed';
        $row = [
            'type'          => $type,
            'name'          => $name,
            'points'        => json_encode($cleanPoints),
            'length_meters' => $lengthM,
            'sector'        => $sectorId,
            'difficulty'    => $difficulty,
            'resort_map'    => $this->getSelectedMap(),
            'active'        => 1,
        ];

        if (!empty($data['id'])) {
            $segId = (int) $data['id'];
            $existing = $model->find($segId);
            if (!$existing) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Segment not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }
            if (!empty($existing['resort_map'])) {
                $row['resort_map'] = $existing['resort_map'];
            }
            $model->update($segId, $row);

            // Keep built player_items metadata in sync with updated segment details
            db_connect()->table('player_items')
                ->where('segment_id', $segId)
                ->update([
                    'name'          => $name,
                    'length_meters' => $lengthM,
                    'difficulty'    => $difficulty ?: null,
                    'sector'        => $sectorId,
                ]);

            return $this->response->setJSON([
                'success'    => true,
                'id'         => $segId,
                'segment'    => array_merge(['id' => $segId], $row),
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        $id = (int) $model->insert($row);
        return $this->response->setJSON([
            'success'    => true,
            'id'         => $id,
            'segment'    => array_merge(['id' => $id], $row),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function deleteSegment($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $segId = (int) $id;
        if ($segId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $model = new MapSegmentModel();
        $seg = $model->find($segId);
        if (!$seg) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Segment not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $model->delete($segId);

        return $this->response->setJSON([
            'success'    => true,
            'id'         => $segId,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function buildItem()
    {
        $userId = auth()->id();
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Login required', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $data      = $this->request->getJSON(true) ?? [];
        $segmentId = (int) ($data['segment_id'] ?? 0);
        if ($segmentId <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing segment', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $segment = (new MapSegmentModel())->find($segmentId);
        if (!$segment || empty($segment['active'])) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Segment not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $currentMap = $this->getSelectedMap();
        if (($segment['resort_map'] ?? '') !== $currentMap) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Segment does not belong to your active resort map.', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }

        $db = db_connect();

        if (!$this->isAdmin() && !empty($segment['sector'])) {
            $sec = $db->table('resort_sectors')
                ->where('id', (int) $segment['sector'])
                ->where('resort_map', $currentMap)
                ->where('visible', 1)
                ->get()->getRowArray();
            if (!$sec) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'This sector is currently unavailable.', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
            }
        }

        $liftType = $data['lift_type'] ?? 'chair_fixed';
        if ($liftType === 'fixed') {
            $liftType = 'chair_fixed';
        }
        $seats = (int) ($data['seats'] ?? 4);

        $validSlopeTypes = ['slope', 'downhill', 'crosscountry', 'snowpark', 'luge'];
        $rawSlopeType    = (string) ($data['slope_type'] ?? '');
        $slopeType       = in_array($rawSlopeType, $validSlopeTypes, true)
            ? $rawSlopeType
            : (in_array($segment['type'], $validSlopeTypes, true) ? $segment['type'] : 'slope');

        $seatOptions = self::SEAT_OPTIONS;
        if (!isset($seatOptions[$liftType])) {
            $liftType = 'chair_fixed';
        }
        if (!in_array($seats, $seatOptions[$liftType], true)) {
            $seats = $seatOptions[$liftType][0];
        }

        $length = (float) ($segment['length_meters'] ?? 0);
        $cost   = self::calcCost($segment['type'], $liftType, $seats, $length);

        // Resolve game day and any pending season rollover before checking funds and beginning transaction
        $gameDay = getGameDay();

        $itemData = [
            'user_id'       => $userId,
            'segment_id'    => $segmentId,
            'item_type'     => $segment['type'] === 'lift' ? 'lift' : 'slope',
            'subtype'       => $segment['type'] === 'lift' ? $liftType : $slopeType,
            'name'          => $segment['name'] ?? 'Unnamed',
            'length_meters' => (int) ($segment['length_meters'] ?? 0),
            'capacity'      => $segment['type'] === 'lift' ? \App\Libraries\Economy::liftCapacityPerHour($liftType, $seats) : 0,
            'seats'         => $segment['type'] === 'lift' ? $seats : 0,
            'difficulty'    => $segment['difficulty'] ?: null,
            'sector'        => (int) ($segment['sector'] ?? 0),
            'created_at'    => date('Y-m-d H:i:s'),
        ];

        $db->transStart();

        // 1. Lock the user's finance row to serialize build requests for this user
        $fin = $db->query(
            'SELECT cash FROM player_finances WHERE user_id = ? FOR UPDATE',
            [$userId]
        )->getRowArray();

        $cash = (int) ($fin['cash'] ?? 0);
        if (!$fin || $cash < $cost) {
            $db->transRollback();
            return $this->response->setStatusCode(400)->setJSON([
                'error'      => 'Not enough cash: this costs ' . currency($cost) . ' and you have ' . currency($cash) . '.',
                'cost'       => $cost,
                'cash'       => $cash,
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // 2. Duplicate check within serialized transaction
        $existing = $db->table('player_items')
            ->where('user_id', $userId)
            ->where('segment_id', $segmentId)
            ->countAllResults();

        if ($existing > 0) {
            $db->transRollback();
            return $this->response->setStatusCode(400)->setJSON([
                'error'      => 'Already built',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        // 3. Deduct cash
        $db->table('player_finances')
            ->where('user_id', $userId)
            ->set('cash', "cash - {$cost}", false)
            ->update();

        // 4. Insert built item and financial transaction
        $db->table('player_items')->insert($itemData);
        $itemData['id']      = (int) $db->insertID();
        $itemData['salvage'] = self::salvageValue($itemData);
        $itemData = self::withSwaps($itemData);

        $db->table('financial_transactions')->insert([
            'user_id'     => $userId,
            'game_day'    => $gameDay,
            'category'    => 'Construction',
            'description' => 'Constructed ' . $itemData['name'],
            'amount'      => $cost,
            'type'        => 'expense',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        $db->transComplete();

        if (!$db->transStatus()) {
            return $this->response->setStatusCode(500)->setJSON([
                'error'      => 'Construction failed due to a database error. Please try again.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash(),
            ]);
        }

        log_activity($userId, 'Construction', 'Constructed ' . ($itemData['item_type'] === 'lift' ? 'lift' : 'slope') . ' ' . $itemData['name'] . ' for ' . currency($cost), 'fa-solid fa-person-skiing');

        $newFin  = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
        $newCash = (int) ($newFin['cash'] ?? 0);

        return $this->response->setJSON([
            'success'    => true,
            'cost'       => $cost,
            'new_cash'   => $newCash,
            'item'       => $itemData,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /**
     * Demolish a built lift (50% salvage refund) or slope (25%, the same rate as Buildings::sell).
     * Answers JSON for the map, or redirects back with a flash message for plain form posts.
     */
    public function demolishItem($id = null)
    {
        $userId = auth()->id();
        $isAjax = $this->request->isAJAX();
        $fail = function (int $code, string $msg) use ($isAjax) {
            return $isAjax
                ? $this->response->setStatusCode($code)->setJSON(['error' => $msg, 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()])
                : redirect()->back()->with('error', $msg);
        };
        if (!$userId) {
            return $fail(401, 'Login required');
        }

        $itemId = (int) $id;
        if ($itemId <= 0) {
            return $fail(400, 'Invalid item specified.');
        }

        $db   = db_connect();
        $item = $db->table('player_items')->where('id', $itemId)->where('user_id', $userId)->get()->getRowArray();
        if (!$item) {
            return $fail(404, 'That lift or slope was not found.');
        }

        $refund = self::salvageValue($item);
        $label  = $item['item_type'] === 'lift' ? 'lift' : 'slope';

        $db->transStart();
        $db->table('player_items')->where('id', $item['id'])->where('user_id', $userId)->delete();
        // Detach things that pointed at it rather than deleting them
        $db->table('equipment')->where('user_id', $userId)->where('assigned_to', 'slope_' . $item['id'])->update(['assigned_to' => null]);
        $db->table('terrain_parks')->where('user_id', $userId)->where('slope_id', $item['id'])->update(['slope_id' => null]);
        $db->table('scenic_lifts')->where('user_id', $userId)->where('item_id', $item['id'])->delete();
        if ($refund > 0) {
            $db->table('player_finances')->where('user_id', $userId)->set('cash', "cash + {$refund}", false)->update();
            $db->table('financial_transactions')->insert([
                'user_id' => $userId, 'game_day' => getGameDay(),
                'category' => 'Construction', 'description' => 'Demolished ' . $item['name'] . ' (Salvage Refund)',
                'amount' => $refund, 'type' => 'income', 'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        $db->transComplete();

        if (!$db->transStatus()) {
            return $fail(500, 'Could not demolish ' . $item['name'] . '. Nothing was changed.');
        }

        log_activity($userId, 'Construction', 'Demolished ' . $label . ' ' . $item['name'] . ' for ' . currency($refund) . ' salvage', 'fa-solid fa-trash-can');

        $msg = $item['name'] . ' demolished. ' . currency($refund) . ' refunded (' . (int) round(self::salvageRate($item) * 100) . '% salvage).';
        if (!$isAjax) {
            return redirect()->back()->with('success', $msg);
        }
        $fin = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
        return $this->response->setJSON([
            'success'    => true,
            'message'    => $msg,
            'refund'     => $refund,
            'segment_id' => $item['segment_id'] !== null ? (int) $item['segment_id'] : null,
            'new_cash'   => (int) ($fin['cash'] ?? 0),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /** A built lift with the systems it can be swapped to (LiftSwap), for the map and the app */
    public static function withSwaps(array $item): array
    {
        if (($item['item_type'] ?? '') === 'lift') {
            $item['seats']    = LiftSwap::currentSeats($item);
            $item['run_cost'] = (int) round(\App\Libraries\Economy::liftRunningCost((string) $item['subtype'], (int) ($item['length_meters'] ?? 0)));
            $item['swaps']    = LiftSwap::options($item);
        }
        return $item;
    }

    /**
     * Replace a built lift with another system on the same line (POST JSON {lift_type, seats}). Costs what
     * demolish + rebuild would, less LiftSwap::DISCOUNT; a swap to a cheaper lift pays back the difference.
     * The lift keeps its name, line and anything assigned to it, and comes back at 100% condition.
     */
    public function swapLift($id = null)
    {
        $userId = auth()->id();
        $fail = fn(int $code, string $msg) => $this->response->setStatusCode($code)->setJSON(['error' => $msg, 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        if (!$userId) {
            return $fail(401, 'Login required');
        }

        $data  = $this->request->getJSON(true) ?? [];
        $type  = (string) ($data['lift_type'] ?? '');
        $seats = (int) ($data['seats'] ?? 0);
        if (!LiftSwap::isValid($type, $seats)) {
            return $fail(400, 'Pick a lift system and seat count from the list.');
        }

        $db = db_connect();
        $db->transStart();
        $item = $db->query("SELECT * FROM player_items WHERE id = ? AND user_id = ? AND item_type = 'lift' FOR UPDATE", [(int) $id, $userId])->getRowArray();
        if (!$item) {
            $db->transRollback();
            return $fail(404, 'That lift was not found.');
        }
        if ($item['subtype'] === $type && LiftSwap::currentSeats($item) === $seats) {
            $db->transRollback();
            return $fail(400, $item['name'] . ' already is that lift.');
        }
        if (!in_array($item['status'], ['open', 'closed', 'broken'], true)) {
            $db->transRollback();
            return $fail(400, $item['name'] . ' isn\'t ready yet.');
        }

        $price   = LiftSwap::quote($item, $type, $seats)['price'];
        $oldName = self::liftLabel($item['subtype'], LiftSwap::currentSeats($item));
        $newName = self::liftLabel($type, $seats);
        $what    = 'Replaced ' . $item['name'] . ': ' . $oldName . ' to ' . $newName;
        if ($price > 0 && !Ledger::charge($userId, $price, 'Construction', $what)) {
            $db->transRollback();
            $cash = (int) ($db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRow('cash') ?? 0);
            return $fail(400, 'Not enough cash: this costs ' . currency($price) . ' and you have ' . currency($cash) . '.');
        }
        if ($price < 0) {
            Ledger::credit($userId, -$price, 'Construction', $what);
        }
        $update = [
            'subtype'       => $type,
            'seats'         => $seats,
            'capacity'      => \App\Libraries\Economy::liftCapacityPerHour($type, $seats),
            'condition_pct' => 100,
            'status'        => $item['status'] === 'closed' ? 'closed' : 'open',
            'updated_at'    => date('Y-m-d H:i:s'),
        ];
        $db->table('player_items')->where('id', $item['id'])->update($update);
        $db->transComplete();

        if (!$db->transStatus()) {
            return $fail(500, 'Could not replace ' . $item['name'] . '. Nothing was changed.');
        }

        $money = $price > 0 ? ' for ' . currency($price) : ($price < 0 ? ', ' . currency(-$price) . ' back' : '');
        log_activity($userId, 'Construction', $what . $money, 'fa-solid fa-arrows-rotate');

        $item = self::withSwaps(array_merge($item, $update));
        $item['salvage'] = self::salvageValue($item);
        $fin = $db->table('player_finances')->select('cash')->where('user_id', $userId)->get()->getRowArray();
        return $this->response->setJSON([
            'success'    => true,
            'message'    => $item['name'] . ' is now a ' . $newName . ($price > 0 ? '. Paid ' . currency($price) . '.' : ($price < 0 ? '. ' . currency(-$price) . ' paid back.' : '.')),
            'price'      => $price,
            'item'       => array_intersect_key($item, array_flip(['id', 'segment_id', 'item_type', 'subtype', 'name', 'length_meters', 'capacity', 'seats', 'difficulty', 'sector', 'status', 'snow_quality', 'salvage', 'run_cost', 'swaps'])),
            'new_cash'   => (int) ($fin['cash'] ?? 0),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    /** "Fixed-grip chairlift, 4 seats" */
    public static function liftLabel(string $type, int $seats): string
    {
        $name = self::LIFT_NAMES[$type] ?? ucwords(str_replace('_', ' ', $type));
        return $name . ', ' . $seats . ($seats === 1 ? ' seat' : ' seats');
    }

    /** Same names the map panel uses (resort-map/shared.tsx LIFT_TYPES) */
    public const LIFT_NAMES = [
        'button'       => 'Surface lift',
        'chair_fixed'  => 'Fixed-grip chairlift',
        'chair_detach' => 'Detachable chairlift',
        'gondola'      => 'Gondola',
        'cable_car'    => 'Cable car',
    ];

    /** Share of the build cost a demolish pays back: lifts keep more resale value than graded slopes */
    public const LIFT_SALVAGE = 0.5;
    public const SLOPE_SALVAGE = 0.25;

    public static function salvageRate(array $item): float
    {
        return ($item['item_type'] ?? '') === 'lift' ? self::LIFT_SALVAGE : self::SLOPE_SALVAGE;
    }

    /** What demolishing the item pays back (salvageRate() of its build cost) */
    public static function salvageValue(array $item): int
    {
        return (int) round(self::buildCost($item) * self::salvageRate($item));
    }

    /**
     * What a built lift or slope cost. Lifts built before seats were stored are priced at the
     * cheapest carrier for their type, so this can come out low, never high.
     */
    public static function buildCost(array $item): int
    {
        $meters = (float) ($item['length_meters'] ?? 0);
        if (($item['item_type'] ?? '') !== 'lift') {
            return self::calcCost('slope', null, 0, $meters);
        }
        return self::calcCost('lift', $item['subtype'] ?? 'chair_fixed', LiftSwap::currentSeats($item), $meters);
    }

    /** Build price per metre for each lift type; the map page gets these too, so its prices match the charge */
    public const LIFT_COST_PER_M = [
        'button'       => 80,
        'chair_fixed'  => 150,
        'chair_detach' => 250,
        'gondola'      => 400,
        'cable_car'    => 600,
    ];

    /** Price multiplier by carrier size */
    public const SEAT_COST_MULT = [1 => 0.7, 2 => 1.0, 3 => 1.15, 4 => 1.3, 6 => 1.6, 8 => 2.0, 10 => 2.5, 20 => 4.0, 30 => 5.0];

    /** Carrier sizes each lift type can have; the first is the default */
    public const SEAT_OPTIONS = [
        'button'       => [1, 2],
        'chair_fixed'  => [2, 3, 4],
        'chair_detach' => [4, 6, 8],
        'gondola'      => [6, 8, 10],
        'cable_car'    => [20, 30],
    ];

    public const SLOPE_COST_PER_M = 50;

    /** The nightly game:tick runs at 00:05 in the app's timezone (root crontab on the VPS) */
    private const SETTLE_TIME = '00:05';

    /** Next nightly settle as ISO 8601, so the browser can show it in the player's own timezone */
    private static function nextSettleAt(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone(config('App')->appTimezone));
        $at  = $now->modify('today ' . self::SETTLE_TIME);
        return ($at <= $now ? $at->modify('+1 day') : $at)->format(DATE_ATOM);
    }

    protected static function calcCost(string $type, ?string $liftType, int $seats, float $meters): int
    {
        if ($type === 'lift') {
            $base = self::LIFT_COST_PER_M[$liftType] ?? self::LIFT_COST_PER_M['chair_fixed'];
            $mult = self::SEAT_COST_MULT[$seats] ?? 1.0;
            return (int) ($meters * $base * $mult);
        }
        return (int) ($meters * self::SLOPE_COST_PER_M);
    }

    public function saveMidstation($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true) ?? [];
        $id   = (int) ($id ?: ($data['segment_id'] ?? 0));
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $seg = (new MapSegmentModel())->find($id);
        if (!$seg) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Segment not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        (new MapSegmentModel())->update($id, ['midstations' => json_encode($data['midstations'] ?? [])]);
        return $this->response->setJSON(['success' => true, 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
    }

    public function createSector()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true) ?? [];
        $db   = db_connect();
        $name = trim((string) ($data['name'] ?? 'New Sector')) ?: 'New Sector';
        $desc = trim((string) ($data['description'] ?? ''));
        $color = (string) ($data['color'] ?? '#3b82f6');
        if (!preg_match('/^#[a-fA-F0-9]{3,8}$/', $color)) {
            $color = '#3b82f6';
        }
        $row  = [
            'resort_map'      => $this->getSelectedMap(),
            'name'            => $name,
            'description'     => $desc,
            'color'           => $color,
            'visible'         => 1,
            'released'        => 0,
            'sort_order'      => 0,
            'boundary_points' => json_encode($data['boundary_points'] ?? []),
        ];
        $db->table('resort_sectors')->insert($row);
        $newId = (int) $db->insertID();
        return $this->response->setJSON([
            'success'    => true,
            'id'         => $newId,
            'sector'     => array_merge(['id' => $newId], $row),
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function toggleSector($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        
        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $db     = db_connect();
        $sector = $db->table('resort_sectors')->where('id', $id)->get()->getRowArray();
        if (!$sector) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $newVis = $sector['visible'] ? 0 : 1;
        $db->table('resort_sectors')->where('id', $id)->update(['visible' => $newVis]);
        return $this->response->setJSON([
            'success'    => true,
            'visible'    => (bool) $newVis,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function deleteSector($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        
        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        db_connect()->table('resort_sectors')->where('id', $id)->delete();
        // Reset segments that belonged to this deleted sector back to 0
        db_connect()->table('map_segments')->where('sector', $id)->update(['sector' => 0]);
        return $this->response->setJSON([
            'success'    => true,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    public function saveSectorBoundary($id = null)
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $data = $this->request->getJSON(true) ?? [];
        $id   = (int) ($id ?: ($data['id'] ?? 0));
        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing ID', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $db = db_connect();
        $sector = $db->table('resort_sectors')->where('id', $id)->get()->getRowArray();
        if (!$sector) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Sector not found', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $bp = $data['boundary_points'] ?? [];
        $db->table('resort_sectors')->where('id', $id)->update([
            'boundary_points' => json_encode($bp),
        ]);
        return $this->response->setJSON([
            'success'         => true,
            'id'              => $id,
            'boundary_points' => $bp,
            'csrf_token'      => csrf_token(),
            'csrf_hash'       => csrf_hash(),
        ]);
    }

    public function createSectorWithBoundary()
    {
        return $this->createSector();
    }

    public function autoAssignSectors()
    {
        if (!$this->isAdmin()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Unauthorized', 'csrf_token' => csrf_token(), 'csrf_hash' => csrf_hash()]);
        }
        $resortMap = $this->getSelectedMap();
        $db        = db_connect();
        $sectors   = $db->table('resort_sectors')->where('resort_map', $resortMap)->get()->getResultArray();
        $segModel  = new MapSegmentModel();
        $segments  = $segModel->where('resort_map', $resortMap)->where('active', 1)->findAll();
        $count     = 0;
        $updated   = [];

        foreach ($segments as $seg) {
            $pts = json_decode($seg['points'], true);
            if (empty($pts)) continue;
            // Check start point, midpoint, and end point
            $midIdx     = (int) floor(count($pts) / 2);
            $testPoints = [$pts[0], $pts[$midIdx], $pts[count($pts) - 1]];
            foreach ($sectors as $sec) {
                $bp = json_decode($sec['boundary_points'], true);
                if (empty($bp) || count($bp) < 3) continue;
                $matched = false;
                foreach ($testPoints as $tp) {
                    if ($this->pip($tp, $bp)) {
                        $matched = true;
                        break;
                    }
                }
                if ($matched) {
                    $secId = (int) $sec['id'];
                    $segModel->update($seg['id'], ['sector' => $secId]);
                    $updated[(int) $seg['id']] = $secId;
                    $count++;
                    break;
                }
            }
        }
        return $this->response->setJSON([
            'success'    => true,
            'assigned'   => $count,
            'updated'    => $updated,
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash(),
        ]);
    }

    protected function pip(array $pt, array $poly): bool
    {
        $x = (float) ($pt[1] ?? $pt['lng'] ?? 0);
        $y = (float) ($pt[0] ?? $pt['lat'] ?? 0);
        $in = false;
        $n  = count($poly);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = (float) ($poly[$i][0] ?? 0); $xi = (float) ($poly[$i][1] ?? 0);
            $yj = (float) ($poly[$j][0] ?? 0); $xj = (float) ($poly[$j][1] ?? 0);
            $dy = $yj - $yi;
            if (($yi > $y) !== ($yj > $y) && abs($dy) > 1e-9 && $x < ($xj - $xi) * ($y - $yi) / $dy + $xi) {
                $in = !$in;
            }
        }
        return $in;
    }
}
