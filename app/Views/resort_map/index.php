<?= $this->extend('layouts/main') ?>
<?= $this->section('title') ?>Trail Map<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div style="position:relative; z-index:0; overflow:hidden;">

    <!-- Top Bar -->
    <div class="bg-base-100 border-b border-base-300 px-4 py-2 flex items-center justify-between" style="position:relative; z-index:5;">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-chevron-left"></i></a>
            <h1 class="text-lg font-bold">Trail Map</h1>
            <?php if ($isAdmin ?? false) : ?>
            <div class="dropdown dropdown-bottom">
                <div tabindex="0" class="btn btn-ghost btn-xs gap-1"><i class="fa-solid fa-map-location-dot"></i> <?= $mapConfig['name'] ?></div>
                <div tabindex="0" class="dropdown-content bg-base-100 rounded-box shadow-xl z-50 w-56 mt-2 p-2">
                    <?php foreach (\App\Controllers\ResortMap::RESORT_MAPS as $key => $rm) : ?>
                    <form action="/map/change-map" method="post" class="inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="resort_map" value="<?= $key ?>">
                        <button type="submit" class="w-full text-left px-2 py-1.5 rounded hover:bg-base-200 text-sm <?= $key === $selectedMap ? 'font-bold text-primary' : '' ?>"><?= $rm['name'] ?></button>
                    </form>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif ?>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($isAdmin ?? false) : ?>
            <div class="join">
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Draw Lift Line" id="drawLiftBtn"><i class="fa-solid fa-cable-car"></i></button>
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Draw Slope Path" id="drawSlopeBtn"><i class="fa-solid fa-person-skiing"></i></button>
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Draw Sector" id="drawSectorBtn"><i class="fa-solid fa-draw-polygon"></i></button>
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Select" id="selectBtn"><i class="fa-solid fa-arrow-pointer"></i></button>
            </div>
            <?php endif ?>
            <button class="btn btn-primary btn-sm gap-1" id="buildModeBtn"><i class="fa-solid fa-plus"></i> Build</button>
        </div>
    </div>

    <?php if ($isAdmin ?? false) : ?>
    <!-- Drawing Status Bar -->
    <div id="drawingStatus" class="bg-info/10 border-b border-info/30 px-4 py-1.5 text-xs text-info flex items-center justify-between hidden" style="position:relative; z-index:5;">
        <div class="flex items-center gap-2">
            <span class="loading loading-ring loading-xs"></span>
            <span id="drawingStatusText">Click on the map to start drawing...</span>
        </div>
        <div class="flex gap-2">
            <select id="sectorSelect" class="select select-xs select-bordered">
                <option value="0">No Sector</option>
                <?php foreach ($sectors as $sec) : ?>
                <option value="<?= $sec['id'] ?>"><?= esc($sec['name']) ?></option>
                <?php endforeach ?>
            </select>
            <button class="btn btn-xs btn-success" id="finishDrawBtn" disabled>Finish</button>
            <button class="btn btn-xs btn-ghost" id="undoDrawBtn" disabled>Undo</button>
            <button class="btn btn-xs btn-ghost text-error" id="cancelDrawBtn">Cancel</button>
        </div>
    </div>
    <?php endif ?>

    <!-- Map Container -->
    <div class="relative">
        <!-- Stats Overlay -->
        <div style="position:absolute;top:10px;left:10px;z-index:400;pointer-events:none;">
            <div class="card bg-base-100/90 shadow-lg" style="pointer-events:auto;backdrop-filter:blur(8px);">
                <div class="card-body p-3">
                    <div class="flex gap-4 text-xs">
                        <?php
                            $mapDb = db_connect();
                            $mapSlopes = $mapDb->table('player_items')->where('user_id', auth()->id())->where('item_type', 'slope')->where('status', 'open')->countAllResults(false);
                            $mapLifts = $mapDb->table('player_items')->where('user_id', auth()->id())->where('item_type', 'lift')->where('status', 'open')->countAllResults(false);
                            $mapSegs = count($segments ?? []);
                        ?>
                        <div class="text-center"><div class="font-bold text-info"><?= $mapSlopes ?></div><div class="text-base-content/50">Slopes</div></div>
                        <div class="text-center"><div class="font-bold text-warning"><?= $mapLifts ?></div><div class="text-base-content/50">Lifts</div></div>
                        <div class="text-center"><div class="font-bold text-primary"><?= $mapSegs ?></div><div class="text-base-content/50">Segments</div></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div style="position:absolute;bottom:30px;left:10px;z-index:400;pointer-events:none;">
            <div class="card bg-base-100/90 shadow-sm" style="pointer-events:auto;backdrop-filter:blur(8px);">
                <div class="card-body p-2">
                    <div class="space-y-1 text-xs">
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#facc15;display:inline-block;"></span> Lift</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#22c55e;display:inline-block;"></span> Green Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#3b82f6;display:inline-block;"></span> Blue Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#ef4444;display:inline-block;"></span> Red Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#111827;display:inline-block;"></span> Black Run</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="mapid" class="w-full" style="background: #1a2332; height: calc(100vh - 130px);"></div>
    </div>
</div>

<!-- Build Drawer (outside map container) -->
<div id="buildDrawer" style="position:fixed;top:0;right:0;bottom:0;width:320px;background:#2a2d3e;z-index:10000;transform:translateX(100%);transition:transform 0.3s ease;box-shadow:-4px 0 20px rgba(0,0,0,0.3);overflow-y:auto;">
    <div style="padding:16px;border-bottom:1px solid #3a3d4e;display:flex;align-items:center;justify-content:space-between;">
        <h2 style="font-weight:bold;font-size:16px;color:#e0e0e0;" id="panelTitle">Build</h2>
        <button id="closeBuildPanel" style="background:none;border:none;color:#888;cursor:pointer;font-size:18px;padding:4px 8px;">&times;</button>
    </div>

    <!-- Step 1: Segment List -->
    <div id="step1" style="padding:16px;">
        <p style="font-size:14px;font-weight:600;color:#ccc;margin-bottom:8px;">Available Segments</p>
        <p style="font-size:12px;color:#888;margin-bottom:12px;">Click a segment to build on it:</p>
        <div id="drawnPathsList" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
            <div style="text-align:center;padding:16px;color:#666;font-size:12px;">No segments available.</div>
        </div>
        <?php if ($isAdmin ?? false) : ?>
        <div style="border-top:1px solid #3a3d4e;padding-top:12px;margin-top:8px;">
            <p style="font-size:11px;color:#666;margin-bottom:8px;">Admin: Draw new path</p>
            <button class="btn btn-outline btn-sm w-full mb-2" id="quickDrawLift"><i class="fa-solid fa-cable-car mr-2"></i>Draw Lift Line</button>
            <button class="btn btn-outline btn-sm w-full mb-2" id="quickDrawSlope"><i class="fa-solid fa-person-skiing mr-2"></i>Draw Slope Path</button>
            <div style="border-top:1px solid #3a3d4e;padding-top:12px;margin-top:8px;">
                <p style="font-size:11px;color:#666;margin-bottom:8px;">Sectors</p>
                <?php foreach ($sectors as $sec) : ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:4px 8px;margin-bottom:4px;background:#333;border-radius:6px;">
                    <span style="font-size:12px;color:<?= $sec['visible'] ? '#22c55e' : '#666' ?>;"><?= esc($sec['name']) ?></span>
                    <div style="display:flex;gap:4px;">
                        <form action="/map/sector/toggle/<?= $sec['id'] ?>" method="post" style="display:inline;"><?= csrf_field() ?><button class="btn btn-ghost btn-xs"><i class="fa-solid <?= $sec['visible'] ? 'fa-eye' : 'fa-eye-slash' ?>" style="font-size:10px;"></i></button></form>
                        <form action="/map/sector/delete/<?= $sec['id'] ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs" style="color:#ef4444;"><i class="fa-solid fa-trash" style="font-size:10px;"></i></button></form>
                    </div>
                </div>
                <?php endforeach ?>
                <form action="/map/sector/create" method="post"><?= csrf_field() ?><button class="btn btn-outline btn-xs w-full mb-2"><i class="fa-solid fa-plus mr-1"></i> New Sector</button></form>
                    <form action="/map/sector/auto-assign" method="post"><?= csrf_field() ?><button class="btn btn-outline btn-xs w-full"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Auto-assign</button></form>
            </div>
        </div>
        <?php endif ?>
    </div>

    <!-- Step 2: Lift Config -->
    <div id="step2Lift" style="padding:16px;display:none;">
        <button class="btn btn-ghost btn-xs mb-3 step-back"><i class="fa-solid fa-chevron-left"></i> Back</button>
        <p style="font-size:14px;font-weight:600;color:#ccc;" id="selectedPathName">-</p>
        <p style="font-size:12px;color:#888;margin-bottom:12px;" id="selectedPathLength">-</p>
        <p style="font-size:12px;font-weight:600;color:#ccc;margin-bottom:8px;">Lift Type</p>
        <div style="display:flex;flex-direction:column;gap:4px;margin-bottom:12px;">
            <label style="cursor:pointer;"><input type="radio" name="liftType" value="button" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-primary peer-checked:bg-primary/10" style="font-size:12px;"><b>Button Lift</b> - 1,000/hr</div></label>
            <label style="cursor:pointer;"><input type="radio" name="liftType" value="chair_fixed" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-primary peer-checked:bg-primary/10" style="font-size:12px;"><b>Chairlift (Fixed)</b> - 2,400/hr</div></label>
            <label style="cursor:pointer;"><input type="radio" name="liftType" value="chair_detach" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-primary peer-checked:bg-primary/10" style="font-size:12px;"><b>Chairlift (Detach)</b> - 3,400/hr</div></label>
            <label style="cursor:pointer;"><input type="radio" name="liftType" value="gondola" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-primary peer-checked:bg-primary/10" style="font-size:12px;"><b>Gondola</b> - 3,500/hr</div></label>
            <label style="cursor:pointer;"><input type="radio" name="liftType" value="cable_car" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-primary peer-checked:bg-primary/10" style="font-size:12px;"><b>Cable Car</b> - 4,000/hr</div></label>
        </div>
        <p style="font-size:12px;font-weight:600;color:#ccc;margin-bottom:8px;">Seats</p>
        <div style="display:flex;gap:8px;margin-bottom:12px;">
            <label style="flex:1;cursor:pointer;"><input type="radio" name="liftCap" value="2" style="display:none;" class="peer"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">2</div></label>
            <label style="flex:1;cursor:pointer;"><input type="radio" name="liftCap" value="4" style="display:none;" class="peer"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">4</div></label>
            <label style="flex:1;cursor:pointer;"><input type="radio" name="liftCap" value="6" style="display:none;" class="peer"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">6</div></label>
            <label style="flex:1;cursor:pointer;"><input type="radio" name="liftCap" value="8" style="display:none;" class="peer"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">8</div></label>
        </div>
        <?php if ($isAdmin ?? false) : ?>
        <div style="border-top:1px solid #3a3d4e;padding-top:12px;">
            <p style="font-size:12px;font-weight:600;color:#ccc;margin-bottom:4px;"><i class="fa-solid fa-circle-dot mr-1"></i>Midstations</p>
            <p style="font-size:11px;color:#888;margin-bottom:8px;" id="midstationList">No midstations</p>
            <button type="button" id="addMidstationBtn" class="btn btn-outline btn-xs"><i class="fa-solid fa-plus mr-1"></i> Add Midstation</button>
        </div>
        <?php endif ?>
    </div>

    <!-- Step 2: Slope Config -->
    <div id="step2Slope" style="padding:16px;display:none;">
        <button class="btn btn-ghost btn-xs mb-3 step-back"><i class="fa-solid fa-chevron-left"></i> Back</button>
        <p style="font-size:14px;font-weight:600;color:#ccc;" id="selectedSlopeName">-</p>
        <p style="font-size:12px;color:#888;margin-bottom:12px;" id="selectedSlopeLength">-</p>
        <p style="font-size:12px;font-weight:600;color:#ccc;margin-bottom:8px;">Type</p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-bottom:12px;">
            <label style="cursor:pointer;"><input type="radio" name="slopeType" value="downhill" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10" style="font-size:12px;font-weight:600;">Downhill</div></label>
            <label style="cursor:pointer;"><input type="radio" name="slopeType" value="crosscountry" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10" style="font-size:12px;font-weight:600;">Cross-Country</div></label>
            <label style="cursor:pointer;"><input type="radio" name="slopeType" value="snowpark" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10" style="font-size:12px;font-weight:600;">Snow Park</div></label>
            <label style="cursor:pointer;"><input type="radio" name="slopeType" value="luge" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10" style="font-size:12px;font-weight:600;">Luge</div></label>
            <label style="cursor:pointer;"><input type="radio" name="slopeType" value="boardercross" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10" style="font-size:12px;font-weight:600;">Boardercross</div></label>
            <label style="cursor:pointer;"><input type="radio" name="slopeType" value="halfpipe" style="display:none;" class="peer"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10" style="font-size:12px;font-weight:600;">Halfpipe</div></label>
        </div>
        <p style="font-size:12px;font-weight:600;color:#ccc;margin-bottom:8px;">Difficulty</p>
        <div style="display:flex;gap:4px;margin-bottom:12px;">
            <label style="flex:1;cursor:pointer;"><input type="radio" name="slopeDiff" value="green" style="display:none;" class="peer"><div class="btn btn-sm w-full peer-checked:btn-success" style="background:#22c55e20;">Green</div></label>
            <label style="flex:1;cursor:pointer;"><input type="radio" name="slopeDiff" value="blue" style="display:none;" class="peer"><div class="btn btn-sm w-full peer-checked:btn-info" style="background:#3b82f620;">Blue</div></label>
            <label style="flex:1;cursor:pointer;"><input type="radio" name="slopeDiff" value="red" style="display:none;" class="peer"><div class="btn btn-sm w-full peer-checked:btn-error" style="background:#ef444420;">Red</div></label>
            <label style="flex:1;cursor:pointer;"><input type="radio" name="slopeDiff" value="black" style="display:none;" class="peer"><div class="btn btn-sm w-full peer-checked:btn-neutral">Black</div></label>
        </div>
    </div>

    <!-- Step 3: Confirm Build -->
    <div id="step3" style="padding:16px;border-top:1px solid #3a3d4e;display:none;">
        <div style="display:flex;flex-direction:column;gap:4px;font-size:12px;color:#aaa;margin-bottom:12px;">
            <div style="display:flex;justify-content:space-between;"><span>Length</span><span style="font-family:monospace;" id="confirmLength">-</span></div>
            <div style="display:flex;justify-content:space-between;"><span>Build time</span><span style="font-family:monospace;" id="confirmTime">-</span></div>
            <div style="display:flex;justify-content:space-between;font-weight:bold;font-size:14px;color:#3b82f6;"><span>Total Cost</span><span id="confirmCost">-</span></div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-success btn-sm" style="flex:1;" id="confirmBuildBtn">Build</button>
            <button class="btn btn-ghost btn-sm" id="cancelBuildBtn">Cancel</button>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<style>
#mapid{will-change:transform;transform:translateZ(0);}
.sector-label{background:rgba(0,0,0,0.7)!important;border:none!important;color:#fff!important;font-size:11px!important;font-weight:bold!important;padding:2px 6px!important;border-radius:4px!important;}
</style>
<script>
    var csrfName = '<?= csrf_token() ?>', csrfHash = '<?= csrf_hash() ?>';
    var panel = document.getElementById('buildDrawer');
    var panelTitle = document.getElementById('panelTitle');
    var step1 = document.getElementById('step1');
    var step2Lift = document.getElementById('step2Lift');
    var step2Slope = document.getElementById('step2Slope');
    var step3 = document.getElementById('step3');
    var pathsList = document.getElementById('drawnPathsList');
    var drawingStatus = document.getElementById('drawingStatus');
    var drawingStatusText = document.getElementById('drawingStatusText');
    var finishBtn = document.getElementById('finishDrawBtn');
    var undoBtn = document.getElementById('undoDrawBtn');

    var mapImage = '/img/<?= $selectedMap ?>.jpg';
    var imgH = <?= $mapConfig['height'] ?>, imgW = <?= $mapConfig['width'] ?>;
    var bounds = [[0, 0], [imgH, imgW]];

    var map = L.map('mapid', {
        preferCanvas: true,
        crs: L.CRS.Simple,
        minZoom: -2,
        maxZoom: 4,
        zoomSnap: 0.5,
        attributionControl: false,
        maxBounds: bounds,
        maxBoundsViscosity: 1.0
    });

    var img = new Image(); img.onload = function(){ L.imageOverlay(mapImage, bounds).addTo(map); }; img.src = mapImage;
    map.fitBounds(bounds);
    map.setView([170, 300], 1);
    map.setMinZoom(map.getZoom());

    var dbSegments = <?= json_encode($segments ?? []) ?>;
    var builtSegmentIds = <?= json_encode($builtSegmentIds ?? []) ?>;
    var builtIds = builtSegmentIds.map(String);
    var sectorData = <?= json_encode($sectors ?? []) ?>;
    var sectorColors = ["#22c55e","#3b82f6","#ef4444","#f97316","#a855f7","#06b6d4","#ec4899","#eab308"];

    // Render sector boundaries (admin only)
    var isAdmin = <?= json_encode($isAdmin ?? false) ?>;
    if (isAdmin) {
    sectorData.forEach(function(sec, i) {
        if (sec.boundary_points) {
            var pts = JSON.parse(sec.boundary_points);
            if (pts.length >= 3) {
                L.polygon(pts, { color: sectorColors[i % sectorColors.length], fillColor: sectorColors[i % sectorColors.length], fillOpacity: 0.1, weight: 2, dashArray: "5,5" }).addTo(map).bindTooltip(sec.name, {permanent: true, direction: "center", className: "sector-label"});
            }
        }
    });
    }

    var drawnPaths = [];
    var selectedPathId = null;
    var drawMode = null, drawPoints = [], drawLine = null, tempMarkers = [];

    // Render existing segments
    dbSegments.forEach(function(seg) {
        var points = JSON.parse(seg.points);
        var color = seg.type === 'lift' ? '#facc15' : '#22c55e';
        var dashArray = seg.type === 'lift' ? '8 4' : null;
        var line = L.polyline(points, { color: color, weight: 4, opacity: 0.9, dashArray: dashArray }).addTo(map);
        var startMarker = L.circleMarker(points[0], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
        var endMarker = L.circleMarker(points[points.length-1], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
        var path = { id: parseInt(seg.id), dbId: parseInt(seg.id), type: seg.type, points: points, length: parseInt(seg.length_meters), line: line, markers: [startMarker, endMarker], name: seg.name, midstations: seg.midstations };
        drawnPaths.push(path);
        line.on('click', function() { selectPath(path.id); });

        // Render midstations
        if (seg.type === 'lift' && seg.midstations) {
            var mids = typeof seg.midstations === 'string' ? JSON.parse(seg.midstations) : seg.midstations;
            if (mids && mids.length) {
                mids.forEach(function(mp) {
                    L.circleMarker([mp[0], mp[1]], {radius:6, color:'#facc15', fillColor:'#fff', fillOpacity:1, weight:2}).addTo(map).bindTooltip('Midstation', {direction:'top'});
                });
            }
        }
    });


    // Detect and render connection points
    var connectionPoints = {};
    drawnPaths.forEach(function(p) {
        var start = p.points[0][0].toFixed(2) + ',' + p.points[0][1].toFixed(2);
        var end = p.points[p.points.length-1][0].toFixed(2) + ',' + p.points[p.points.length-1][1].toFixed(2);
        if (!connectionPoints[start]) connectionPoints[start] = { pt: p.points[0], count: 0, types: [] };
        if (!connectionPoints[end]) connectionPoints[end] = { pt: p.points[p.points.length-1], count: 0, types: [] };
        connectionPoints[start].count++;
        connectionPoints[start].types.push(p.type);
        connectionPoints[end].count++;
        connectionPoints[end].types.push(p.type);
    });
    Object.keys(connectionPoints).forEach(function(key) {
        var cp = connectionPoints[key];
        if (cp.count >= 2) {
            var hasLift = cp.types.indexOf('lift') !== -1;
            var hasSlope = cp.types.indexOf('slope') !== -1;
            var color = (hasLift && hasSlope) ? '#a855f7' : (hasLift ? '#facc15' : '#22c55e');
            L.circleMarker(cp.pt, {
                radius: 8, color: '#fff', fillColor: color, fillOpacity: 1, weight: 3
            }).addTo(map).bindTooltip(cp.count + ' segments connected', {direction: 'top'});
        }
    });

    // Point-in-polygon test (ray casting)
    function pointInPolygon(pt, polygon) {
        var x = pt[0], y = pt[1], inside = false;
        for (var i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            var xi = polygon[i][0], yi = polygon[i][1];
            var xj = polygon[j][0], yj = polygon[j][1];
            if ((yi > y) !== (yj > y) && x < (xj - xi) * (y - yi) / (yj - yi) + xi) inside = !inside;
        }
        return inside;
    }

    // Auto-detect sector for a point
    function detectSector(pt) {
        for (var i = 0; i < sectorData.length; i++) {
            if (sectorData[i].boundary_points) {
                var poly = JSON.parse(sectorData[i].boundary_points);
                if (poly.length >= 3 && pointInPolygon(pt, poly)) return sectorData[i].id;
            }
        }
        return 0;
    }
    // Panel functions
    function showStep(step) {
        [step1, step2Lift, step2Slope].forEach(function(s) { s.style.display = 'none'; });
        step.style.display = 'block';
    }
    function openPanel() { panel.style.transform = 'translateX(0)'; panelTitle.textContent = 'Build'; showStep(step1); updatePathsList(); }
    function closePanel() { panel.style.transform = 'translateX(100%)'; step3.style.display = 'none'; selectedPathId = null; drawnPaths.forEach(function(p) { p.line.setStyle({ weight: 4 }); }); }

    function selectPath(id) {
        var path = drawnPaths.find(function(p) { return p.id === id; });
        if (!path) return;
        selectedPathId = id;
        panel.style.transform = 'translateX(0)';
        drawnPaths.forEach(function(p) { p.line.setStyle({ weight: p.id === id ? 6 : 4 }); });
        if (path.type === 'lift') {
            panelTitle.textContent = 'Build Lift';
            document.getElementById('selectedPathName').textContent = path.name;
            var mCount = path.midstations ? (typeof path.midstations === 'string' ? JSON.parse(path.midstations) : path.midstations).length : 0;
            document.getElementById('selectedPathLength').textContent = path.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?>';
            showStep(step2Lift);
            var ml = document.getElementById('midstationList'); if(ml) ml.textContent = mCount ? mCount + ' midstation' + (mCount>1?'s':'') : 'No midstations';
            updateConfirm(path);
        } else {
            panelTitle.textContent = 'Build Slope';
            document.getElementById('selectedSlopeName').textContent = path.name;
            document.getElementById('selectedSlopeLength').textContent = path.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?>';
            showStep(step2Slope);
            updateConfirm(path);
        }
    }

    function updateConfirm(path) {
        var costPerM = path.type === 'lift' ? 2000 : 600;
        var cost = path.length * costPerM;
        var days = Math.max(1, Math.ceil(path.length / 500));
        document.getElementById('confirmLength').textContent = path.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?>';
        document.getElementById('confirmTime').textContent = days + ' day' + (days > 1 ? 's' : '');
        document.getElementById('confirmCost').textContent = '<?= isImperial() ? "$" : "€" ?>' + cost.toLocaleString();
        step3.style.display = 'block';
    }

    function updatePathsList() {
        var isAdmin = <?= json_encode($isAdmin ?? false) ?>;
        var available = drawnPaths.filter(function(p) { return isAdmin || builtIds.indexOf(String(p.dbId)) === -1; });
        if (available.length === 0) {
            pathsList.innerHTML = '<div style="text-align:center;padding:16px;color:#666;font-size:12px;">No available segments.</div>';
            return;
        }
        var html = '';
        available.forEach(function(p) {
            var icon = p.type === 'lift' ? '<i class="fa-solid fa-cable-car" style="color:#facc15;"></i>' : '<i class="fa-solid fa-person-skiing" style="color:#22c55e;"></i>';
            var built = builtIds.indexOf(String(p.dbId)) !== -1;
            html += '<div style="display:flex;align-items:center;gap:8px;padding:8px;background:#333;border-radius:8px;cursor:pointer;' + (built ? 'opacity:0.5;' : '') + '" data-path-id="' + p.id + '">'
                + '<span style="font-size:16px;">' + icon + '</span>'
                + '<div style="flex:1;min-width:0;"><div style="font-size:12px;font-weight:600;color:#ccc;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + p.name + (built ? ' (Built)' : '') + '</div><div style="font-size:11px;color:#888;">' + p.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?></div></div>';
            if (isAdmin) html += '<button class="delete-path" data-id="' + p.id + '" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;"><i class="fa-solid fa-xmark"></i></button>';
            html += '</div>';
        });
        pathsList.innerHTML = html;
        pathsList.querySelectorAll('[data-path-id]').forEach(function(el) {
            el.addEventListener('click', function(e) { if (e.target.closest('.delete-path')) return; selectPath(parseInt(this.dataset.pathId)); });
        });
        if (isAdmin) {
            pathsList.querySelectorAll('.delete-path').forEach(function(el) {
                el.addEventListener('click', function(e) { e.stopPropagation(); if (confirm('Delete this segment?')) deletePath(parseInt(this.dataset.id)); });
            });
        }
    }

    function deletePath(id) {
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        fetch('/map/segment/delete/' + id, { method: 'POST', body: fd }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                var idx = drawnPaths.findIndex(function(p) { return p.dbId === id; });
                if (idx !== -1) {
                    map.removeLayer(drawnPaths[idx].line);
                    drawnPaths[idx].markers.forEach(function(m) { map.removeLayer(m); });
                    drawnPaths.splice(idx, 1);
                }
                if (selectedPathId === id) { selectedPathId = null; showStep(step1); step3.style.display = 'none'; }
                updatePathsList();
            }
        });
    }

    // Drawing functions
    function startDraw(type) {
        drawMode = type;
        drawPoints = [];
        tempMarkers = [];
        if (drawLine) { map.removeLayer(drawLine); drawLine = null; }
        if (drawingStatus) drawingStatus.classList.remove('hidden');
        if (drawingStatusText) drawingStatusText.textContent = 'Click on the map to place points. Click Finish when done.';
        if (finishBtn) finishBtn.disabled = true;
        if (undoBtn) undoBtn.disabled = true;
        map.getContainer().style.cursor = 'crosshair';
        var lb = document.getElementById('drawLiftBtn'), sb = document.getElementById('drawSlopeBtn');
        if (lb) lb.classList.toggle('btn-active', type === 'lift');
        if (sb) sb.classList.toggle('btn-active', type === 'slope');
    }

    function cancelDraw() {
        drawMode = null;
        drawPoints = [];
        if (drawLine) { map.removeLayer(drawLine); drawLine = null; }
        tempMarkers.forEach(function(m) { map.removeLayer(m); });
        tempMarkers = [];
        if (drawingStatus) drawingStatus.classList.add('hidden');
        map.getContainer().style.cursor = '';
        var lb = document.getElementById('drawLiftBtn'), sb = document.getElementById('drawSlopeBtn'), selb = document.getElementById('selectBtn');
        if (lb) lb.classList.remove('btn-active');
        if (sb) sb.classList.remove('btn-active');
        if (selb) selb.classList.remove('btn-active');
    }

    function updateDrawLine() {
        var color = drawMode === 'lift' ? '#facc15' : '#22c55e';
        var dashArray = drawMode === 'lift' ? '8 4' : null;
        if (drawLine) map.removeLayer(drawLine);
        if (drawPoints.length >= 2) drawLine = L.polyline(drawPoints, { color: color, weight: 4, opacity: 0.8, dashArray: dashArray }).addTo(map);
    }

    // Snap to endpoint
    var SNAP_DISTANCE = 15;
    function findNearestEndpoint(latlng) {
        var nearest = null, minDist = Infinity;
        drawnPaths.forEach(function(p) {
            [p.points[0], p.points[p.points.length-1]].forEach(function(ep) {
                var d = map.latLngToContainerPoint(L.latLng(ep[0], ep[1])).distanceTo(map.latLngToContainerPoint(latlng));
                if (d < SNAP_DISTANCE && d < minDist) { minDist = d; nearest = ep; }
            });
        });
        return nearest;
    }

    // Map click for drawing
    map.on('click', function(e) {
        if (!drawMode) return;
        var snap = findNearestEndpoint(e.latlng);
        var pt = snap ? snap : [e.latlng.lat, e.latlng.lng];
        drawPoints.push(pt);
        updateDrawLine();
        var m = L.circleMarker(pt, { radius: 4, color: '#fff', fillColor: '#fff', fillOpacity: 0.8, weight: 1 }).addTo(map);
        tempMarkers.push(m);
        if (undoBtn) undoBtn.disabled = false;
        if (drawPoints.length >= 2 && finishBtn) finishBtn.disabled = false;
        if (drawingStatusText) drawingStatusText.textContent = drawPoints.length + ' point(s). ' + (drawPoints.length < 2 ? 'Add at least one more.' : 'Click Finish or keep adding.');
    });

    // Finish drawing
    if (finishBtn) finishBtn.addEventListener('click', function() {
        if (!drawMode || drawPoints.length < 2) return;
        var type = drawMode;
        var points = drawPoints.slice();
        cancelDraw();

        var totalLen = 0;
        for (var i = 1; i < points.length; i++) {
            var a = map.latLngToContainerPoint(L.latLng(points[i-1][0], points[i-1][1]));
            var b = map.latLngToContainerPoint(L.latLng(points[i][0], points[i][1]));
            totalLen += a.distanceTo(b);
        }
        var length = Math.round(totalLen * 3);
        var name = type === 'lift' ? 'Lift Line' : 'Slope Path';

        var color = type === 'lift' ? '#facc15' : '#22c55e';
        var dashArray = type === 'lift' ? '8 4' : null;
        var permanentLine = L.polyline(points, { color: color, weight: 4, opacity: 0.9, dashArray: dashArray }).addTo(map);
        var startMarker = L.circleMarker(points[0], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
        var endMarker = L.circleMarker(points[points.length-1], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);

        var sectorSel = document.getElementById('sectorSelect');
        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('type', type);
        formData.append('name', name);
        formData.append('points', JSON.stringify(points));
        formData.append('length_meters', length);
        var selSector = sectorSel ? sectorSel.value : "0";
        if (selSector === "0") { var mid = points[Math.floor(points.length/2)]; selSector = detectSector(mid) || 0; }
        formData.append("sector", selSector);

        fetch('/map/segment', { method: 'POST', body: formData }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                var id = d.id;
                var path = { id: id, dbId: id, type: type, points: points, length: length, line: permanentLine, markers: [startMarker, endMarker], name: name + ' ' + id };
                drawnPaths.push(path);
                permanentLine.on('click', function() { selectPath(id); });
                updatePathsList();
                selectPath(id);
            }
        });
    });

    // Event listeners
    document.getElementById('buildModeBtn').addEventListener('click', openPanel);
    document.getElementById('closeBuildPanel').addEventListener('click', closePanel);
    var qdl = document.getElementById('quickDrawLift'), qds = document.getElementById('quickDrawSlope');
    if (qdl) qdl.addEventListener('click', function() { closePanel(); startDraw('lift'); });
    if (qds) qds.addEventListener('click', function() { closePanel(); startDraw('slope'); });
    var dlb = document.getElementById('drawLiftBtn'), dsb = document.getElementById('drawSlopeBtn'), selb = document.getElementById('selectBtn');
    if (dlb) dlb.addEventListener('click', function() { startDraw('lift'); });
    if (dsb) dsb.addEventListener('click', function() { startDraw('slope'); });
    if (selb) selb.addEventListener('click', function() { cancelDraw(); this.classList.add('btn-active'); });
    if (document.getElementById('cancelDrawBtn')) document.getElementById('cancelDrawBtn').addEventListener('click', cancelDraw);
    if (undoBtn) undoBtn.addEventListener("click", function() {
        if (sectorDrawMode) {
            sectorDrawPoints.pop();
            var m = sectorDrawMarkers.pop();
            if (m) map.removeLayer(m);
            if (sectorDrawPolygon) map.removeLayer(sectorDrawPolygon);
            if (sectorDrawPoints.length >= 2) sectorDrawPolygon = L.polygon(sectorDrawPoints, {color:"#a855f7", fillColor:"#a855f7", fillOpacity:0.15, weight:2, dashArray:"5,5"}).addTo(map);
            else sectorDrawPolygon = null;
            if (finishBtn) finishBtn.disabled = sectorDrawPoints.length < 3;
            if (undoBtn) undoBtn.disabled = sectorDrawPoints.length === 0;
        } else {
            drawPoints.pop();
            var m = tempMarkers.pop();
            if (m) map.removeLayer(m);
            updateDrawLine();
            if (finishBtn) finishBtn.disabled = drawPoints.length < 2;
            if (undoBtn) undoBtn.disabled = drawPoints.length === 0;
        }
    });

    // Back buttons
    document.querySelectorAll('.step-back').forEach(function(btn) {
        btn.addEventListener('click', function() { panelTitle.textContent = 'Build'; showStep(step1); step3.style.display = 'none'; selectedPathId = null; drawnPaths.forEach(function(p) { p.line.setStyle({ weight: 4 }); }); });
    });
    document.getElementById('cancelBuildBtn').addEventListener('click', function() { step3.style.display = 'none'; });

    // Build confirm
    document.getElementById('confirmBuildBtn').addEventListener('click', function() {
        if (!selectedPathId) return;
        var path = drawnPaths.find(function(p) { return p.id === selectedPathId; });
        if (!path) return;

        var formData = new FormData();
        formData.append(csrfName, csrfHash);
        formData.append('segment_id', path.dbId);
        formData.append('item_type', path.type);

        if (path.type === 'lift') {
            var liftType = document.querySelector('input[name="liftType"]:checked');
            var liftCap = document.querySelector('input[name="liftCap"]:checked');
            formData.append('subtype', liftType ? liftType.value : 'chair_detach');
            formData.append('seats', liftCap ? liftCap.value : 4);
            formData.append('difficulty', '');
        } else {
            var slopeType = document.querySelector('input[name="slopeType"]:checked');
            var slopeDiff = document.querySelector('input[name="slopeDiff"]:checked');
            formData.append('subtype', slopeType ? slopeType.value : 'downhill');
            formData.append('difficulty', slopeDiff ? slopeDiff.value : 'green');
            formData.append('seats', 0);
        }

        fetch('/map/build', { method: 'POST', body: formData }).then(function(r) { return r.json(); }).then(function(d) {
            if (d.success) {
                builtIds.push(String(path.dbId));
                step3.style.display = 'none';
                showStep(step1);
                panelTitle.textContent = 'Build';
                selectedPathId = null;
                updatePathsList();
                location.reload();
            } else {
                alert(d.error || 'Build failed');
            }
        });
    });

    // Update confirm on radio change
    document.querySelectorAll('input[name="liftType"],input[name="liftCap"],input[name="slopeType"],input[name="slopeDiff"]').forEach(function(r) {
        r.addEventListener('change', function() {
            if (selectedPathId) {
                var path = drawnPaths.find(function(p) { return p.id === selectedPathId; });
                if (path) updateConfirm(path);
            }
        });
    });

    // Midstation button
    var midstationMode = false;
    var addMidBtn = document.getElementById('addMidstationBtn');
    if (addMidBtn) {
        addMidBtn.addEventListener('click', function() {
            if (!selectedPathId) return;
            midstationMode = true;
            this.classList.add('btn-active');
            map.getContainer().style.cursor = 'crosshair';
        });
    }
    map.on('click', function(e) {
        if (!midstationMode || !selectedPathId) return;
        var path = drawnPaths.find(function(p) { return p.id === selectedPathId; });
        if (!path || path.type !== 'lift') return;
        var mp = [e.latlng.lat, e.latlng.lng];
        var mids = path.midstations ? (typeof path.midstations === 'string' ? JSON.parse(path.midstations) : path.midstations) : [];
        mids.push(mp);
        path.midstations = mids;
        L.circleMarker(mp, {radius:6, color:'#facc15', fillColor:'#fff', fillOpacity:1, weight:2}).addTo(map).bindTooltip('Midstation', {direction:'top'});
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        fd.append('midstations', JSON.stringify(mids));
        fetch('/map/segment/midstation/' + path.dbId, { method: 'POST', body: fd });
        var ml = document.getElementById('midstationList');
        if (ml) ml.textContent = mids.length + ' midstation' + (mids.length > 1 ? 's' : '');
        midstationMode = false;
        if (addMidBtn) addMidBtn.classList.remove('btn-active');
        map.getContainer().style.cursor = '';
    });

    // Sector boundary drawing
    var sectorDrawMode = false, sectorDrawPoints = [], sectorDrawPolygon = null, sectorDrawMarkers = [];
    var drawSectorBtn = document.getElementById('drawSectorBtn');
    if (drawSectorBtn) {
        drawSectorBtn.addEventListener('click', function() {
            cancelDraw();
            midstationMode = false;
            sectorDrawMode = true;
            sectorDrawPoints = [];
            sectorDrawMarkers = [];
            if (drawingStatus) drawingStatus.classList.remove('hidden');
            if (drawingStatusText) drawingStatusText.textContent = 'Click to place sector boundary points. Click Finish when done (min 3).';
            if (finishBtn) finishBtn.disabled = true;
            map.getContainer().style.cursor = 'crosshair';
            drawSectorBtn.classList.add('btn-active');
        });
    }
    map.on('click', function(e) {
        if (!sectorDrawMode) return;
        sectorDrawPoints.push([e.latlng.lat, e.latlng.lng]);
        var m = L.circleMarker(e.latlng, {radius:4, color:'#a855f7', fillColor:'#a855f7', fillOpacity:1}).addTo(map);
        sectorDrawMarkers.push(m);
        if (sectorDrawPolygon) map.removeLayer(sectorDrawPolygon);
        if (sectorDrawPoints.length >= 2) {
            sectorDrawPolygon = L.polygon(sectorDrawPoints, {color:'#a855f7', fillColor:'#a855f7', fillOpacity:0.15, weight:2, dashArray:'5,5'}).addTo(map);
        }
        if (finishBtn) finishBtn.disabled = sectorDrawPoints.length < 3;
        if (drawingStatusText) drawingStatusText.textContent = sectorDrawPoints.length + ' points. ' + (sectorDrawPoints.length < 3 ? 'Need at least 3.' : 'Click Finish to save.');
    });
    if (finishBtn) finishBtn.addEventListener('click', function() {
        if (!sectorDrawMode || sectorDrawPoints.length < 3) return;
        var secSelect = document.getElementById('sectorSelect');
        var secId = secSelect ? secSelect.value : 0;
        if (!secId || secId === "0") { var fd3 = new FormData(); fd3.append(csrfName, csrfHash); fd3.append("points", JSON.stringify(sectorDrawPoints)); fetch("/map/sector/create-with-boundary", { method: "POST", body: fd3 }).then(function(r) { return r.json(); }).then(function(d) { if (d.success) location.reload(); }); sectorDrawMode = false; return; }
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        fd.append('points', JSON.stringify(sectorDrawPoints));
        fetch('/map/sector/boundary/' + secId, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.success) location.reload(); });
        sectorDrawMode = false;
        sectorDrawPoints = [];
        if (drawSectorBtn) drawSectorBtn.classList.remove('btn-active');
        if (drawingStatus) drawingStatus.classList.add('hidden');
        map.getContainer().style.cursor = '';
    });
    if (document.getElementById('cancelDrawBtn')) {
        document.getElementById('cancelDrawBtn').addEventListener('click', function() {
            if (sectorDrawMode) {
                sectorDrawMode = false;
                sectorDrawPoints = [];
                sectorDrawMarkers.forEach(function(m) { map.removeLayer(m); });
                if (sectorDrawPolygon) { map.removeLayer(sectorDrawPolygon); sectorDrawPolygon = null; }
                if (drawSectorBtn) drawSectorBtn.classList.remove('btn-active');
            }
        });
    }
</script>
<?= $this->endSection() ?>
