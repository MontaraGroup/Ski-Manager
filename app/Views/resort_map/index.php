<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Trail Map<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div style="position:relative; z-index:0; overflow:hidden;">

    <!-- Top Bar -->
    <div class="bg-base-100 border-b border-base-300 px-4 py-2 flex items-center justify-between" style="position:relative; z-index:5;">
        <div class="flex items-center gap-3">
            <a href="/dashboard" class="btn btn-ghost btn-sm btn-circle">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <h1 class="text-lg font-bold">Trail Map</h1>
        </div>
            <?php if (auth()->id() === 1) : ?>
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
        <div class="flex items-center gap-2">
            <?php if (auth()->id() === 1) : ?>
            <div class="join">
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Draw Lift Line" id="drawLiftBtn">
                    <i class="fa-solid fa-cable-car"></i>
                </button>
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Draw Slope Path" id="drawSlopeBtn">
                    <i class="fa-solid fa-person-skiing"></i>
                </button>
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Select" id="selectBtn">
                    <i class="fa-solid fa-arrow-pointer"></i>
                <button class="btn btn-sm join-item tooltip tooltip-bottom" data-tip="Draw Sector Boundary" id="drawSectorBtn">
                    <i class="fa-solid fa-draw-polygon"></i>
                </button>
                </button>
            </div>
            <?php endif ?>
            <button class="btn btn-primary btn-sm gap-1" id="buildModeBtn">
                <i class="fa-solid fa-plus"></i> Build
            </button>
        </div>
    </div>

    <?php if ($isAdmin ?? false) : ?>
    <!-- Admin Sector Panel -->
    <div class="bg-base-100 border-b border-base-300 px-4 py-2 flex items-center gap-3 flex-wrap" style="position:relative; z-index:5;">
        <span class="text-xs font-semibold text-base-content/50"><i class="fa-solid fa-layer-group mr-1"></i>Sectors:</span>
        <?php foreach ($sectors as $sec) : ?>
        <div class="flex items-center gap-1">
            <span class="badge badge-sm <?= $sec["visible"] ? "badge-success" : "badge-ghost" ?>"><?= esc($sec["name"]) ?></span>
            <form action="/map/sector/toggle/<?= $sec["id"] ?>" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-xs"><i class="fa-solid <?= $sec["visible"] ? "fa-eye" : "fa-eye-slash" ?> text-[10px]"></i></button></form>
            <form action="/map/sector/delete/<?= $sec["id"] ?>" method="post" class="inline" onsubmit="return confirm('Delete this sector?')"><?= csrf_field() ?><button class="btn btn-ghost btn-xs text-error"><i class="fa-solid fa-xmark text-[10px]"></i></button></form>
        </div>
        <?php endforeach ?>
        <form action="/map/sector/create" method="post" class="inline"><?= csrf_field() ?><button class="btn btn-ghost btn-xs gap-1"><i class="fa-solid fa-plus text-[10px]"></i> New Sector</button></form>
    </div>
    <?php endif ?>
    <!-- Drawing Status Bar -->
    <?php if (auth()->id() === 1) : ?>
    <div id="drawingStatus" class="bg-info/10 border-b border-info/30 px-4 py-1.5 text-xs text-info flex items-center justify-between hidden" style="position:relative; z-index:5;">
        <div class="flex items-center gap-2">
            <span class="loading loading-ring loading-xs"></span>
            <span id="drawingStatusText">Click on the map to start drawing...</span>
        </div>
        <div class="flex gap-2">
            <select id="sectorSelect" class="select select-xs select-bordered">
                <option value="0">No Sector</option>
                <?php foreach ($sectors as $sec) : ?>
                <option value="<?= $sec["id"] ?>"><?= esc($sec["name"]) ?></option>
                <?php endforeach ?>
            </select>
            <button class="btn btn-xs btn-success" id="finishDrawBtn" disabled>Finish</button>
            <button class="btn btn-xs btn-ghost" id="undoDrawBtn" disabled>Undo</button>
            <button class="btn btn-xs btn-ghost text-error" id="cancelDrawBtn">Cancel</button>
        </div>
    </div>

    <?php endif ?>
    <div class="flex relative">

        <!-- Build Panel -->
        <div id="buildPanel" class="absolute right-0 top-0 bottom-0 w-80 bg-base-100 border-l border-base-300 translate-x-full transition-transform duration-300 flex flex-col shadow-xl" style="z-index:500;">

            <div class="p-4 border-b border-base-300 flex items-center justify-between shrink-0">
                <h2 class="font-bold" id="panelTitle">Build</h2>
                <button class="btn btn-ghost btn-sm btn-circle" id="closeBuildPanel">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Step 1: Choose what to build -->
            <div id="step1" class="p-4 flex-1 overflow-y-auto">
                <p class="text-sm font-semibold mb-2">Available Runs</p>
                <p class="text-sm text-base-content/60 mb-3">Select a run to build on:</p>
                <div id="drawnPathsList" class="space-y-2 mb-4">
                    <div class="text-xs text-base-content/40 text-center py-4">No paths drawn yet. Use the draw tools above.</div>
                </div>
                <?php if (auth()->id() === 1) : ?><div class="divider text-xs">Admin: Draw new path</div>
                <div class="space-y-2">
                    <button class="btn btn-outline w-full justify-start gap-3 h-auto py-3" id="quickDrawLift">
                        <span class="w-8 h-8 rounded-lg bg-info/20 flex items-center justify-center text-info shrink-0"><i class="fa-solid fa-cable-car text-lg"></i></span>
                        <div class="text-left"><div class="font-semibold text-sm">Draw Lift Line</div><div class="text-xs text-base-content/50">Click points on the map</div></div>
                    </button>
                    <button class="btn btn-outline w-full justify-start gap-3 h-auto py-3" id="quickDrawSlope">
                        <span class="w-8 h-8 rounded-lg bg-success/20 flex items-center justify-center text-success shrink-0"><i class="fa-solid fa-person-skiing text-lg"></i></span>
                        <div class="text-left"><div class="font-semibold text-sm">Draw Slope Path</div><div class="text-xs text-base-content/50">Click points on the map</div></div>
                    </button>
                    </div>
                <?php endif ?>
                </div>
            </div>

            <!-- Step 2: Configure (Lift) -->
            <div id="step2Lift" class="p-4 flex-1 overflow-y-auto hidden">
                <button class="btn btn-ghost btn-xs mb-3 gap-1 step-back"><i class="fa-solid fa-chevron-left text-xs"></i> Back</button>
                <p class="text-sm font-semibold mb-1" id="selectedPathName">-</p>
                <p class="text-xs text-base-content/50 mb-4" id="selectedPathLength">-</p>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold block mb-2">Lift Type</label>
                        <div class="grid grid-cols-1 gap-1.5">
                            <label class="cursor-pointer"><input type="radio" name="liftType" value="button" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors"><div class="text-xs"><span class="font-semibold">Button Lift</span> - <span class="text-base-content/50">1,000 skiers/hr, 3 m/s</span></div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="liftType" value="chair_fixed" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors"><div class="text-xs"><span class="font-semibold">Chairlift (Fixed)</span> - <span class="text-base-content/50">2,400 skiers/hr, 3 m/s</span></div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="liftType" value="chair_detach" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors"><div class="text-xs"><span class="font-semibold">Chairlift (Detachable)</span> - <span class="text-base-content/50">3,400 skiers/hr, 5 m/s</span></div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="liftType" value="gondola" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors"><div class="text-xs"><span class="font-semibold">Gondola</span> - <span class="text-base-content/50">3,500 skiers/hr, 6 m/s</span></div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="liftType" value="cable_car" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 peer-checked:border-primary peer-checked:bg-primary/10 hover:bg-base-200 transition-colors"><div class="text-xs"><span class="font-semibold">Cable Car</span> - <span class="text-base-content/50">4,000 skiers/hr, 8 m/s</span></div></div></label>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-2">Seats</label>
                        <div class="flex gap-2">
                            <label class="flex-1 cursor-pointer"><input type="radio" name="liftCap" value="2" class="peer hidden"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">2</div></label>
                            <label class="flex-1 cursor-pointer"><input type="radio" name="liftCap" value="4" class="peer hidden"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">4</div></label>
                            <label class="flex-1 cursor-pointer"><input type="radio" name="liftCap" value="6" class="peer hidden"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">6</div></label>
                            <label class="flex-1 cursor-pointer"><input type="radio" name="liftCap" value="8" class="peer hidden"><div class="btn btn-outline btn-sm w-full peer-checked:btn-primary">8</div></label>
                        </div>
                    </div>
                <?php if (auth()->id() === 1) : ?>
                <div class="mt-3 pt-3 border-t border-base-300">
                    <label class="text-xs font-semibold block mb-2"><i class="fa-solid fa-circle-dot mr-1"></i>Midstations</label>
                    <div id="midstationList" class="text-xs text-base-content/50 mb-2">No midstations</div>
                    <button type="button" id="addMidstationBtn" class="btn btn-outline btn-xs gap-1"><i class="fa-solid fa-plus"></i> Add Midstation</button>
                    <p class="text-[10px] text-base-content/40 mt-1">Click a point on the lift line to place a midstation.</p>
                </div>
                <?php endif ?>
                </div>
            </div>

            <!-- Step 2: Configure (Slope) -->
            <div id="step2Slope" class="p-4 flex-1 overflow-y-auto hidden">
                <button class="btn btn-ghost btn-xs mb-3 gap-1 step-back"><i class="fa-solid fa-chevron-left text-xs"></i> Back</button>
                <p class="text-sm font-semibold mb-1" id="selectedSlopeName">-</p>
                <p class="text-xs text-base-content/50 mb-4" id="selectedSlopeLength">-</p>
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold block mb-2">Type</label>
                        <div class="grid grid-cols-2 gap-1.5">
                            <label class="cursor-pointer"><input type="radio" name="slopeType" value="downhill" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 text-center text-xs peer-checked:border-success peer-checked:bg-success/10 hover:bg-base-200 transition-colors font-semibold">Downhill</div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeType" value="crosscountry" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 text-center text-xs peer-checked:border-success peer-checked:bg-success/10 hover:bg-base-200 transition-colors font-semibold">Cross-Country</div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeType" value="snowpark" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 text-center text-xs peer-checked:border-success peer-checked:bg-success/10 hover:bg-base-200 transition-colors font-semibold">Snow Park</div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeType" value="luge" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 text-center text-xs peer-checked:border-success peer-checked:bg-success/10 hover:bg-base-200 transition-colors font-semibold">Luge</div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeType" value="boardercross" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 text-center text-xs peer-checked:border-success peer-checked:bg-success/10 hover:bg-base-200 transition-colors font-semibold">Boardercross</div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeType" value="halfpipe" class="peer hidden"><div class="border border-base-300 rounded-lg p-2.5 text-center text-xs peer-checked:border-success peer-checked:bg-success/10 hover:bg-base-200 transition-colors font-semibold">Halfpipe</div></label>
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-semibold block mb-2">Difficulty</label>
                        <div class="grid grid-cols-4 gap-1.5">
                            <label class="cursor-pointer"><input type="radio" name="slopeDiff" value="green" class="peer hidden"><div class="border-2 border-base-300 rounded-lg p-2 text-center text-xs peer-checked:border-green-500 hover:bg-base-200 transition-colors"><div class="w-4 h-4 rounded-full mx-auto mb-1" style="background:#22c55e"></div><div class="font-semibold">Green</div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeDiff" value="blue" class="peer hidden"><div class="border-2 border-base-300 rounded-lg p-2 text-center text-xs peer-checked:border-blue-500 hover:bg-base-200 transition-colors"><div class="w-4 h-4 rounded-full mx-auto mb-1" style="background:#3b82f6"></div><div class="font-semibold">Blue</div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeDiff" value="red" class="peer hidden"><div class="border-2 border-base-300 rounded-lg p-2 text-center text-xs peer-checked:border-red-500 hover:bg-base-200 transition-colors"><div class="w-4 h-4 rounded-full mx-auto mb-1" style="background:#ef4444"></div><div class="font-semibold">Red</div></div></label>
                            <label class="cursor-pointer"><input type="radio" name="slopeDiff" value="black" class="peer hidden"><div class="border-2 border-base-300 rounded-lg p-2 text-center text-xs peer-checked:border-gray-500 hover:bg-base-200 transition-colors"><div class="w-4 h-4 rounded-full mx-auto mb-1" style="background:#1f2937"></div><div class="font-semibold">Black</div></div></label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Confirm bar -->
            <div id="step3" class="p-4 border-t border-base-300 shrink-0 hidden">
                <div class="bg-base-200 rounded-lg p-3 text-xs space-y-1 mb-3">
                    <div class="flex justify-between"><span class="text-base-content/50">Length</span><span class="font-mono" id="confirmLength">-</span></div>
                    <div class="flex justify-between"><span class="text-base-content/50">Build time</span><span class="font-mono" id="confirmTime">-</span></div>
                    <div class="flex justify-between font-semibold text-sm"><span>Total Cost</span><span class="font-mono text-primary" id="confirmCost">-</span></div>
                </div>
                <div class="flex gap-2">
                    <button class="btn btn-success btn-sm flex-1" id="confirmBuildBtn">Build</button>
                    <button class="btn btn-ghost btn-sm" id="cancelBuildBtn">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Map -->
        <div class="flex-1">
        <!-- Map Stats Overlay -->
        <div style="position:absolute;top:80px;left:10px;z-index:400;pointer-events:none;">
            <div class="card bg-base-100/90 shadow-lg" style="pointer-events:auto;backdrop-filter:blur(8px);">
                <div class="card-body p-3">
                    <div class="flex gap-4 text-xs">
                        <?php
                            $mapDb = db_connect();
                            $mapSlopes = $mapDb->table("player_items")->where("user_id", auth()->id())->where("item_type", "slope")->countAllResults(false);
                            $mapLifts = $mapDb->table("player_items")->where("user_id", auth()->id())->where("item_type", "lift")->countAllResults(false);
                            $mapSegments = count($segments ?? []);
                        ?>
                        <div class="text-center"><div class="font-bold text-info text-lg"><?= $mapSlopes ?></div><div class="text-base-content/50">Slopes</div></div>
                        <div class="text-center"><div class="font-bold text-success text-lg"><?= $mapLifts ?></div><div class="text-base-content/50">Lifts</div></div>
                        <div class="text-center"><div class="font-bold text-warning text-lg"><?= $mapSegments ?></div><div class="text-base-content/50">Segments</div></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Map Legend -->
        <div style="position:absolute;bottom:10px;left:10px;z-index:400;pointer-events:none;">
            <div class="card bg-base-100/90 shadow-lg" style="pointer-events:auto;backdrop-filter:blur(8px);">
                <div class="card-body p-2">
                    <div class="text-xs space-y-1">
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#3b82f6;border-radius:2px;display:inline-block;"></span> Lift</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#22c55e;border-radius:2px;display:inline-block;"></span> Green Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#3b82f6;border-radius:2px;display:inline-block;"></span> Blue Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#ef4444;border-radius:2px;display:inline-block;"></span> Red Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#1a1a2e;border-radius:2px;display:inline-block;"></span> Black Run</div>
                        <div class="flex items-center gap-2"><span style="width:16px;height:3px;background:#a855f7;border-radius:2px;display:inline-block;border-style:dashed;"></span> Drawing</div>
                    </div>
                </div>
            </div>
        </div>
            <div id="mapid" class="w-full" style="background: #1a2332; height: calc(100vh - 130px);"></div>
<style>#mapid{will-change:transform;transform:translateZ(0);-webkit-transform:translateZ(0);-webkit-backface-visibility:hidden;backface-visibility:hidden;perspective:1000px;contain:layout style paint;}#mapid .leaflet-tile-pane,#mapid .leaflet-overlay-pane,#mapid .leaflet-image-layer{will-change:transform;transform:translateZ(0);-webkit-backface-visibility:hidden;backface-visibility:hidden;image-rendering:optimizeSpeed;}#mapid .leaflet-zoom-anim .leaflet-zoom-animated{will-change:transform;transition:transform 0.25s cubic-bezier(0,0,0.25,1);}.sector-label{background:rgba(0,0,0,0.7)!important;border:none!important;color:#fff!important;font-size:11px!important;font-weight:bold!important;padding:2px 6px!important;border-radius:4px!important;}</style>
        </div>
    </div>
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>

<script>
(function() {
    var drawMode = null;
    var drawPoints = [];
    var drawLine = null;
    var drawnPaths = [];
    var pathIdCounter = 0;
    var selectedPathId = null;
    var tempMarkers = [];

    var panel = document.getElementById('buildPanel');
    var step1 = document.getElementById('step1');
    var step2Lift = document.getElementById('step2Lift');
    var step2Slope = document.getElementById('step2Slope');
    var step3 = document.getElementById('step3');
    var panelTitle = document.getElementById('panelTitle');
    var drawingStatus = document.getElementById('drawingStatus');
    var drawingStatusText = document.getElementById('drawingStatusText');
    var pathsList = document.getElementById('drawnPathsList');
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
    var sectorData = <?= json_encode($sectors ?? []) ?>;
    var sectorColors = ["#22c55e","#3b82f6","#ef4444","#f97316","#a855f7","#06b6d4","#ec4899","#eab308"];
    sectorData.forEach(function(sec, i) {
        if (sec.boundary_points) {
            var pts = JSON.parse(sec.boundary_points);
            if (pts.length >= 3) {
                L.polygon(pts, {
                    color: sectorColors[i % sectorColors.length],
                    fillColor: sectorColors[i % sectorColors.length],
                    fillOpacity: 0.1,
                    weight: 2,
                    dashArray: "5,5"
                }).addTo(map).bindTooltip(sec.name, {permanent: true, direction: "center", className: "sector-label"});
            }
        }
    });
    dbSegments.forEach(function(seg) {
        var points = JSON.parse(seg.points);
        var color = seg.type === 'lift' ? '#facc15' : '#22c55e';
        var dashArray = seg.type === 'lift' ? '8 4' : null;
        var line = L.polyline(points, { color: color, weight: 4, opacity: 0.9, dashArray: dashArray }).addTo(map);
        var startMarker = L.circleMarker(points[0], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
        var endMarker = L.circleMarker(points[points.length-1], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
        var path = { id: parseInt(seg.id), dbId: parseInt(seg.id), type: seg.type, points: points, length: parseInt(seg.length_meters), line: line, markers: [startMarker, endMarker], name: seg.name };
        // Render midstations for lifts
        if (seg.type === "lift" && seg.midstations) {
            var mids = typeof seg.midstations === "string" ? JSON.parse(seg.midstations) : seg.midstations;
            if (mids && mids.length) {
                mids.forEach(function(mp) {
                    L.circleMarker([mp[0], mp[1]], {radius:6, color:"#facc15", fillColor:"#fff", fillOpacity:1, weight:2}).addTo(map).bindTooltip("Midstation", {direction:"top"});
                });
            }
        }
        drawnPaths.push(path);
        if (seg.id > pathIdCounter) pathIdCounter = parseInt(seg.id);
        line.on('click', function() { selectPath(path.id); });
        line.on('mouseover', function() { line.setStyle({ weight: 6 }); });
        line.on('mouseout', function() { line.setStyle({ weight: 4 }); });
    });

    function startDraw(type) {
        drawMode = type;
        drawPoints = [];
        tempMarkers = [];
        if (drawLine) { map.removeLayer(drawLine); drawLine = null; }
        drawingStatus.classList.remove('hidden');
        drawingStatusText.textContent = 'Click on the map to place points. Click Finish when done.';
        finishBtn.disabled = true;
        undoBtn.disabled = true;
        map.getContainer().style.cursor = 'crosshair';
        document.getElementById('drawLiftBtn').classList.toggle('btn-active', type === 'lift');
        document.getElementById('drawSlopeBtn').classList.toggle('btn-active', type === 'slope');
        document.getElementById('selectBtn').classList.remove('btn-active');
    }

    function cancelDraw() {
        drawMode = null;
        drawPoints = [];
        if (drawLine) { map.removeLayer(drawLine); drawLine = null; }
        tempMarkers.forEach(function(m) { map.removeLayer(m); });
        tempMarkers = [];
        drawingStatus.classList.add('hidden');
        map.getContainer().style.cursor = '';
        document.getElementById('drawLiftBtn').classList.remove('btn-active');
        document.getElementById('drawSlopeBtn').classList.remove('btn-active');
    }

    function undoDraw() {
        if (drawPoints.length > 0) {
            drawPoints.pop();
            if (tempMarkers.length > 0) map.removeLayer(tempMarkers.pop());
            updateDrawLine();
        }
        if (drawPoints.length === 0) { undoBtn.disabled = true; finishBtn.disabled = true; }
        if (drawPoints.length < 2) { finishBtn.disabled = true; }
    }

    function updateDrawLine() {
        var color = drawMode === 'lift' ? '#666' : '#22c55e';
        var dashArray = drawMode === 'lift' ? '8 4' : null;
        if (drawLine) map.removeLayer(drawLine);
        if (drawPoints.length > 0) {
            drawLine = L.polyline(drawPoints, { color: color, weight: 4, opacity: 0.8, dashArray: dashArray }).addTo(map);
        }
    }

    function calcLength(points) {
        var total = 0;
        for (var i = 1; i < points.length; i++) {
            var dx = points[i][0] - points[i-1][0];
            var dy = points[i][1] - points[i-1][1];
            total += Math.sqrt(dx*dx + dy*dy);
        }
        return Math.round(total * 15);
    }

    function finishDraw() {
        if (drawPoints.length < 2) return;
        var type = drawMode;
        var points = drawPoints.slice();
        var length = calcLength(points);
        var name = (type === 'lift' ? 'Lift Line' : 'Slope Path') + ' ' + (pathIdCounter + 1);

        var formData = new FormData();
        formData.append('type', type);
        formData.append('name', name);
        formData.append('points', JSON.stringify(points));
        formData.append('length_meters', length);
        formData.append('sector', document.getElementById('sectorSelect') ? document.getElementById('sectorSelect').value : 0);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('/map/segment', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success) return;
            var id = data.id;
            pathIdCounter = Math.max(pathIdCounter, id);
            var color = type === 'lift' ? '#facc15' : '#22c55e';
            var dashArray = type === 'lift' ? '8 4' : null;
            if (drawLine) map.removeLayer(drawLine);
            drawLine = null;
            tempMarkers.forEach(function(m) { map.removeLayer(m); });
            tempMarkers = [];
            var permanentLine = L.polyline(points, { color: color, weight: 4, opacity: 0.9, dashArray: dashArray }).addTo(map);
            var startMarker = L.circleMarker(points[0], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
            var endMarker = L.circleMarker(points[points.length-1], { radius: 5, color: color, fillColor: color, fillOpacity: 1 }).addTo(map);
            var path = { id: id, dbId: id, type: type, points: points, length: length, line: permanentLine, markers: [startMarker, endMarker], name: name };
            drawnPaths.push(path);
            permanentLine.on('click', function() { selectPath(id); });
            permanentLine.on('mouseover', function() { permanentLine.setStyle({ weight: 6 }); });
            permanentLine.on('mouseout', function() { permanentLine.setStyle({ weight: 4 }); });
            cancelDraw();
            updatePathsList();
            openPanel();
            selectPath(id);
        });
    }

    function deletePath(id) {
        var idx = drawnPaths.findIndex(function(p) { return p.id === id; });
        if (idx === -1) return;
        var path = drawnPaths[idx];
        fetch('/map/segment/delete/' + path.dbId, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function() {
            map.removeLayer(path.line);
            path.markers.forEach(function(m) { map.removeLayer(m); });
            drawnPaths.splice(idx, 1);
            updatePathsList();
            if (selectedPathId === id) { selectedPathId = null; showStep(step1); step3.classList.add('hidden'); }
        });
    }

    function selectPath(id) {
        var path = drawnPaths.find(function(p) { return p.id === id; });
        if (!path) return;
        selectedPathId = id;
        drawnPaths.forEach(function(p) { p.line.setStyle({ weight: p.id === id ? 6 : 4 }); });
        if (path.type === 'lift') {
            panelTitle.textContent = 'Build Lift';
            document.getElementById('selectedPathName').textContent = path.name;
            var mCount = path.midstations ? path.midstations.length : 0; document.getElementById('selectedPathLength').textContent = path.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?>';
            showStep(step2Lift); var ml = document.getElementById('midstationList'); if(ml) ml.textContent = mCount ? mCount + ' midstation' + (mCount>1?'s':'') : 'No midstations';
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
        var costPerMeter = path.type === 'lift' ? 2000 : 600;
        var timePerMeter = path.type === 'lift' ? 200 : 100;
        document.getElementById('confirmLength').textContent = path.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?>';
        document.getElementById('confirmTime').textContent = Math.ceil(path.length * timePerMeter / 86400) + ' days';
        document.getElementById('confirmCost').textContent = (path.length * costPerMeter).toLocaleString() + ' <?= currencySymbol() ?>';
        step3.classList.remove('hidden');
    }

    function updatePathsList() {
        if (drawnPaths.length === 0) {
            pathsList.innerHTML = '<div class="text-xs text-base-content/40 text-center py-4">No paths drawn yet. Use the draw tools above.</div>';
            return;
        }
        var html = '';
        drawnPaths.forEach(function(p) {
            var icon = p.type === 'lift' ? '<i class="fa-solid fa-cable-car text-warning"></i>' : '<i class="fa-solid fa-person-skiing text-success"></i>';
            html += '<div class="flex items-center gap-2 bg-base-200 rounded-lg p-2 cursor-pointer hover:bg-base-300 transition-colors" data-path-id="' + p.id + '">'
                + '<span class="text-lg">' + icon + '</span>'
                + '<div class="flex-1 min-w-0"><div class="text-xs font-semibold truncate">' + p.name + '</div><div class="text-xs text-base-content/50">' + p.length.toLocaleString() + ' <?= isImperial() ? "ft" : "m" ?></div></div>'
                + '<button class="btn btn-ghost btn-xs text-error delete-path" data-id="' + p.id + '"><i class="fa-solid fa-xmark"></i></button></div>';
        });
        pathsList.innerHTML = html;
        pathsList.querySelectorAll('[data-path-id]').forEach(function(el) {
            el.addEventListener('click', function(e) { if (e.target.closest('.delete-path')) return; selectPath(parseInt(this.dataset.pathId)); });
        });
        pathsList.querySelectorAll('.delete-path').forEach(function(el) {
            el.addEventListener('click', function(e) { e.stopPropagation(); if (confirm('Delete this segment?')) deletePath(parseInt(this.dataset.id)); });
        });
    }


    // Snap-to-endpoint for connecting segments
    var SNAP_DISTANCE = 15;
    function findNearestEndpoint(latlng) {
        var nearest = null, minDist = Infinity;
        drawnPaths.forEach(function(p) {
            var pts = p.points;
            [pts[0], pts[pts.length-1]].forEach(function(ep) {
                var d = map.latLngToContainerPoint(L.latLng(ep[0], ep[1])).distanceTo(map.latLngToContainerPoint(latlng));
                if (d < SNAP_DISTANCE && d < minDist) { minDist = d; nearest = ep; }
            });
            if (p.midstations) {
                p.midstations.forEach(function(mp) {
                    var d = map.latLngToContainerPoint(L.latLng(mp[0], mp[1])).distanceTo(map.latLngToContainerPoint(latlng));
                    if (d < SNAP_DISTANCE && d < minDist) { minDist = d; nearest = mp; }
                });
            }
        });
        return nearest;
    }

    // Add midstation to a lift (admin only, right-click on a lift line)
    map.on('contextmenu', function(e) {
        if (!<?= json_encode($isAdmin ?? false) ?>) return;
        drawnPaths.forEach(function(p) {
            if (p.type !== 'lift') return;
            var closest = L.GeometryUtil ? null : null;
            var pts = p.points;
            for (var i = 0; i < pts.length - 1; i++) {
                var a = L.latLng(pts[i]), b = L.latLng(pts[i+1]);
                var d = L.latLng(e.latlng.lat, e.latlng.lng).distanceTo(a) + L.latLng(e.latlng.lat, e.latlng.lng).distanceTo(b) - a.distanceTo(b);
                if (Math.abs(d) < 5) {
                    var mp = [e.latlng.lat, e.latlng.lng];
                    if (!p.midstations) p.midstations = [];
                    p.midstations.push(mp);
                    L.circleMarker(mp, {radius:6, color:"#facc15", fillColor:"#fff", fillOpacity:1, weight:2}).addTo(map).bindTooltip("Midstation", {direction:"top"});
                    // Save to server
                    var fd = new FormData();
                    fd.append("<?= csrf_token() ?>", "<?= csrf_hash() ?>");
                    fd.append("midstations", JSON.stringify(p.midstations));
                    fetch("/map/segment/midstation/" + p.dbId, { method: "POST", body: fd });
                    return;
                }
            }
        });
    });
    map.on('click', function(e) {
        if (!drawMode) return;
        var snap = findNearestEndpoint(e.latlng);
        var pt = snap ? snap : [e.latlng.lat, e.latlng.lng];
        drawPoints.push(pt);
        updateDrawLine();
        var m = L.circleMarker([e.latlng.lat, e.latlng.lng], { radius: 4, color: '#fff', fillColor: '#fff', fillOpacity: 0.8, weight: 1 }).addTo(map);
        tempMarkers.push(m);
        undoBtn.disabled = false;
        if (drawPoints.length >= 2) finishBtn.disabled = false;
        drawingStatusText.textContent = drawPoints.length + ' point(s). ' + (drawPoints.length < 2 ? 'Add at least one more.' : 'Click Finish or keep adding.');
    });

    function showStep(step) { [step1, step2Lift, step2Slope].forEach(function(s) { s.classList.add('hidden'); }); step.classList.remove('hidden'); }
    function openPanel() { panel.classList.remove('translate-x-full'); panelTitle.textContent = 'Build'; showStep(step1); updatePathsList(); }
    function closePanel() { panel.classList.add('translate-x-full'); step3.classList.add('hidden'); selectedPathId = null; drawnPaths.forEach(function(p) { p.line.setStyle({ weight: 4 }); }); }

    document.getElementById('buildModeBtn').addEventListener('click', openPanel);
    document.getElementById('closeBuildPanel').addEventListener('click', closePanel);
    document.getElementById('drawLiftBtn').addEventListener('click', function() { startDraw('lift'); });
    document.getElementById('drawSlopeBtn').addEventListener('click', function() { startDraw('slope'); });
    document.getElementById('selectBtn').addEventListener('click', function() { cancelDraw(); this.classList.add('btn-active'); });
    document.getElementById('cancelDrawBtn').addEventListener('click', cancelDraw);

    // Midstation button mode
    var midstationMode = false;
    var addMidBtn = document.getElementById('addMidstationBtn');
    if (addMidBtn) {
        addMidBtn.addEventListener('click', function() {
            if (!selectedPathId) return;
            midstationMode = true;
            this.classList.add('btn-active');
            map.getContainer().style.cursor = 'crosshair';
            drawingStatus.classList.remove('hidden');
            drawingStatusText.textContent = 'Click on the lift line to place a midstation.';
        });
    }
    map.on('click', function(e) {
        if (!midstationMode || !selectedPathId) return;
        var path = drawnPaths.find(function(p) { return p.id === selectedPathId; });
        if (!path || path.type !== 'lift') return;
        var mp = [e.latlng.lat, e.latlng.lng];
        if (!path.midstations) path.midstations = [];
        path.midstations.push(mp);
        L.circleMarker(mp, {radius:6, color:"#facc15", fillColor:"#fff", fillOpacity:1, weight:2}).addTo(map).bindTooltip("Midstation", {direction:"top"});
        var fd = new FormData();
        fd.append("<?= csrf_token() ?>", "<?= csrf_hash() ?>");
        fd.append("midstations", JSON.stringify(path.midstations));
        fetch("/map/segment/midstation/" + path.dbId, { method: "POST", body: fd });
        // Update panel list
        var list = document.getElementById('midstationList');
        if (list) list.textContent = path.midstations.length + ' midstation' + (path.midstations.length > 1 ? 's' : '');
        midstationMode = false;
        if (addMidBtn) addMidBtn.classList.remove('btn-active');
        map.getContainer().style.cursor = '';
        drawingStatus.classList.add('hidden');
    });

    // Sector boundary drawing
    var sectorDrawMode = false, sectorDrawPoints = [], sectorDrawPolygon = null, sectorDrawMarkers = [];
    var drawSectorBtn = document.getElementById("drawSectorBtn");
    if (drawSectorBtn) {
        drawSectorBtn.addEventListener("click", function() {
            cancelDraw();
            sectorDrawMode = true;
            sectorDrawPoints = [];
            sectorDrawMarkers = [];
            drawingStatus.classList.remove("hidden");
            drawingStatusText.textContent = "Click to place sector boundary points. Click Finish when done (min 3 points).";
            finishBtn.disabled = true;
            map.getContainer().style.cursor = "crosshair";
            drawSectorBtn.classList.add("btn-active");
        });
    }
    map.on("click", function(e) {
        if (!sectorDrawMode) return;
        sectorDrawPoints.push([e.latlng.lat, e.latlng.lng]);
        var m = L.circleMarker(e.latlng, {radius:4, color:"#a855f7", fillColor:"#a855f7", fillOpacity:1}).addTo(map);
        sectorDrawMarkers.push(m);
        if (sectorDrawPolygon) map.removeLayer(sectorDrawPolygon);
        if (sectorDrawPoints.length >= 2) {
            sectorDrawPolygon = L.polygon(sectorDrawPoints, {color:"#a855f7", fillColor:"#a855f7", fillOpacity:0.15, weight:2, dashArray:"5,5"}).addTo(map);
        }
        finishBtn.disabled = sectorDrawPoints.length < 3;
        drawingStatusText.textContent = sectorDrawPoints.length + " points placed. " + (sectorDrawPoints.length < 3 ? "Need at least 3." : "Click Finish to save.");
    });
    finishBtn.addEventListener("click", function() {
        if (!sectorDrawMode || sectorDrawPoints.length < 3) return;
        var secSelect = document.getElementById("sectorSelect");
        var secId = secSelect ? secSelect.value : 0;
        if (!secId || secId === "0") { alert("Select a sector first"); return; }
        var fd = new FormData();
        fd.append("<?= csrf_token() ?>", "<?= csrf_hash() ?>");
        fd.append("points", JSON.stringify(sectorDrawPoints));
        fetch("/map/sector/boundary/" + secId, { method: "POST", body: fd })
            .then(function(r){return r.json()})
            .then(function(d){ if(d.success) location.reload(); });
    });
    document.getElementById("cancelDrawBtn").addEventListener("click", function() {
        if (sectorDrawMode) {
            sectorDrawMode = false;
            sectorDrawPoints = [];
            sectorDrawMarkers.forEach(function(m){map.removeLayer(m);});
            if (sectorDrawPolygon) map.removeLayer(sectorDrawPolygon);
            sectorDrawPolygon = null;
            if (drawSectorBtn) drawSectorBtn.classList.remove("btn-active");
            drawingStatus.classList.add("hidden");
            map.getContainer().style.cursor = "";
        }
    });
    document.getElementById('finishDrawBtn').addEventListener('click', finishDraw);
    document.getElementById('undoDrawBtn').addEventListener('click', undoDraw);
    document.getElementById('quickDrawLift').addEventListener('click', function() { closePanel(); startDraw('lift'); });
    document.getElementById('quickDrawSlope').addEventListener('click', function() { closePanel(); startDraw('slope'); });
    document.querySelectorAll('.step-back').forEach(function(btn) {
        btn.addEventListener('click', function() { panelTitle.textContent = 'Build'; showStep(step1); step3.classList.add('hidden'); selectedPathId = null; drawnPaths.forEach(function(p) { p.line.setStyle({ weight: 4 }); }); });
    });
    document.getElementById('cancelBuildBtn').addEventListener('click', function() { step3.classList.add('hidden'); });
    document.getElementById('confirmBuildBtn').addEventListener('click', function() {
        var path = drawnPaths.find(function(p) { return p.id === selectedPathId; });
        if (!path) return;
        var formData = new FormData();
        formData.append('segment_id', path.dbId);
        formData.append('item_type', path.type);
        var liftType = document.querySelector('input[name="liftType"]:checked');
        var liftCap = document.querySelector('input[name="liftCap"]:checked');
        var slopeType = document.querySelector('input[name="slopeType"]:checked');
        var slopeDiff = document.querySelector('input[name="slopeDiff"]:checked');
        formData.append('subtype', path.type === 'lift' ? (liftType ? liftType.value : 'button') : (slopeType ? slopeType.value : 'downhill'));
        formData.append('difficulty', slopeDiff ? slopeDiff.value : 'green');
        formData.append('seats', liftCap ? liftCap.value : '4');
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
        fetch('/map/build', { method: 'POST', body: formData })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                alert('Built ' + data.name + '!');
                step3.classList.add('hidden');
                showStep(step1);
                path.line.setStyle({ color: path.type === 'lift' ? '#60a5fa' : '#4ade80', weight: 5, dashArray: null, opacity: 1 });
            } else {
                alert('Error: ' + (data.error || 'Build failed'));
            }
        });
    });
    document.querySelectorAll('input[name="liftType"], input[name="liftCap"], input[name="slopeType"], input[name="slopeDiff"]').forEach(function(el) {
        el.addEventListener('change', function() { var path = drawnPaths.find(function(p) { return p.id === selectedPathId; }); if (path) updateConfirm(path); });
    });
    updatePathsList();
})();
</script>
<?= $this->endSection() ?>
