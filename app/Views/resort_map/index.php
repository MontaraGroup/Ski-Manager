<?php
$csrfName  = csrf_token();
$csrfHash  = csrf_hash();
$segmentsJson      = json_encode($segments ?? []);
$sectorsJson       = json_encode($sectors ?? []);
$builtIdsJson      = json_encode($builtSegmentIds ?? []);
$releasedIdsJson   = json_encode($releasedSectorIds ?? []);
$resortMapsJson    = json_encode($resortMaps ?? []);
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="/css/leaflet.css" />
    <link rel="preload" href="<?= esc($mapConfig['image']) ?>" as="image" fetchpriority="high">
<style>
#map{height:100%;width:100%;background:#1a1a2e;position:relative;z-index:0}
.map-legend{position:absolute;bottom:12px;left:12px;z-index:800;background:rgba(30,30,46,.9);border-radius:8px;padding:10px 14px;font-size:12px;color:#ccc;display:flex;flex-direction:column;gap:4px;backdrop-filter:blur(6px)}
.map-legend-item{display:flex;align-items:center;gap:6px}
.map-legend-item span{width:20px;height:3px;border-radius:2px;display:inline-block}
.build-fab{position:fixed;bottom:24px;right:24px;z-index:900}
/* Ensure tutorial widget does not cover the build button on the map page */
#tutorialWidget {
    bottom: 5.5rem !important;
}
.leaflet-interactive {
    cursor: pointer !important;
}

/* Admin Floating Top Editor Toolbar */
.admin-editor-toolbar {
    position: absolute;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 850;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    background: rgba(22, 27, 34, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    box-shadow: 0 12px 30px -5px rgba(0, 0, 0, 0.65), 0 4px 12px rgba(0,0,0,0.4);
    padding: 6px 12px;
    max-width: 95vw;
}

/* Custom Vertex Handle */
.custom-vertex-wrap {
    background: transparent;
    border: none;
}
.custom-vertex-handle {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #f59e0b;
    box-shadow: 0 2px 6px rgba(0,0,0,0.7);
    cursor: grab;
    transition: transform 0.1s ease;
}
.custom-vertex-handle:hover {
    transform: scale(1.35);
}
.custom-vertex-handle:active {
    cursor: grabbing;
}

/* Custom Midpoint Handle */
.custom-midpoint-wrap {
    background: transparent;
    border: none;
}
.custom-midpoint-handle {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.5);
    cursor: copy;
    opacity: 0.85;
    transition: all 0.15s ease;
}
.custom-midpoint-handle:hover {
    transform: scale(1.5);
    opacity: 1;
}

/* Admin Popover */
.admin-leaflet-popup .leaflet-popup-content-wrapper {
    background: #1d232a !important;
    color: #e2e8f0 !important;
    border: 1px solid rgba(255,255,255,0.12) !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 28px rgba(0,0,0,0.6) !important;
    padding: 4px;
}
.admin-leaflet-popup .leaflet-popup-tip {
    background: #1d232a !important;
}
</style>

<div class="flex items-center justify-between px-4 py-3 border-b border-base-300">
    <div class="flex items-center gap-3">
        <a href="/resort" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-lg font-bold">Trail Map</h1>
    </div>
    <div class="flex items-center gap-2">
        <button id="headerBuildBtn" class="btn btn-primary btn-sm gap-1.5 shadow-sm"><i class="fa-solid fa-hammer"></i> Build Runs</button>
        <?php if ($isAdmin): ?>
        <select id="resortSelect" class="select select-sm select-bordered">
            <?php foreach ($resortMaps as $key => $rm): ?>
            <option value="<?= $key ?>" <?= $key === $resortMap ? 'selected' : '' ?>><?= esc($rm['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php else: ?>
        <span class="badge badge-primary"><?= esc($mapConfig['name']) ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="flex items-center gap-4 px-4 py-2 text-sm text-base-content/70">
    <span class="flex items-center gap-1"><i class="fa-solid fa-person-skiing text-success"></i> <?= $slopeCount ?> Slopes</span>
    <span class="flex items-center gap-1"><i class="fa-solid fa-elevator text-warning"></i> <?= $liftCount ?> Lifts</span>
    <span class="flex items-center gap-1"><i class="fa-solid fa-route text-info"></i> <?= $segmentCount ?> Segments</span>
</div>

<div class="relative w-full overflow-hidden" style="height:calc(100vh - 160px)">
    <div id="map" data-image="<?= $isAdmin ? esc(str_replace(".jpg", "-Big.jpg", $mapConfig["image"])) : esc($mapConfig["image"]) ?>"></div>
    <div id="mapLoader" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:100;text-align:center"><span class="loading loading-spinner loading-lg text-primary"></span><p class="text-sm text-base-content/50 mt-2">Loading trail map...</p></div>

    <?php if ($isAdmin): ?>
    <div id="adminEditorToolbar" class="admin-editor-toolbar shadow-2xl">
        <!-- Idle Mode View -->
        <div id="toolIdleView" class="flex items-center gap-2 flex-wrap">
            <span class="badge badge-primary font-bold text-xs gap-1 py-2.5 px-3">
                <i class="fa-solid fa-compass-drafting text-[11px]"></i> Map Editor
            </span>
            <div class="flex items-center gap-1.5 bg-base-300/60 rounded-lg px-2 py-0.5 border border-white/5">
                <span class="text-[11px] font-semibold text-base-content/60">Sector:</span>
                <select id="toolActiveSector" class="select select-xs select-bordered bg-base-200/90 text-xs py-0 h-6 min-h-0">
                    <option value="">Default (0)</option>
                    <?php foreach ($sectors as $sec): ?>
                    <option value="<?= esc($sec['id']) ?>"><?= esc($sec['name']) ?> (ID: <?= esc($sec['id']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="h-4 w-px bg-white/10 mx-1 hidden sm:block"></div>
            <button id="btnNewLift" class="btn btn-xs btn-warning font-semibold gap-1.5 shadow-sm hover:scale-105 transition-all">
                <i class="fa-solid fa-elevator"></i> + New Lift
            </button>
            <button id="btnNewSlope" class="btn btn-xs btn-success font-semibold gap-1.5 shadow-sm hover:scale-105 transition-all">
                <i class="fa-solid fa-person-skiing"></i> + New Slope
            </button>
            <span class="text-[11px] text-base-content/40 hidden md:inline ml-1">
                <i class="fa-solid fa-circle-info mr-1"></i>Click line to edit/delete
            </span>
        </div>

        <!-- Active Draw / Edit Mode View -->
        <div id="toolActiveView" class="items-center gap-2 flex-wrap" style="display:none">
            <span id="toolModeBadge" class="badge badge-warning text-xs font-bold gap-1 py-2.5 px-2.5">
                <i class="fa-solid fa-pen-nib text-[10px]"></i> <span id="toolModeText">New Lift</span>
            </span>
            
            <!-- Live HUD metrics -->
            <div class="flex items-center gap-2 bg-base-300/80 px-2.5 py-0.5 rounded-lg text-xs font-mono border border-primary/20 shadow-inner">
                <span class="flex items-center gap-1 text-primary font-bold">
                    <i class="fa-solid fa-ruler text-[11px]"></i>
                    <span id="toolLiveLength">0</span> m
                </span>
                <span class="opacity-30">•</span>
                <span class="flex items-center gap-1 text-info">
                    <i class="fa-solid fa-location-dot text-[11px]"></i>
                    <span id="toolPointCount">0</span> pts
                </span>
            </div>

            <!-- Inline Name -->
            <input type="text" id="toolSegName" class="input input-xs input-bordered w-32 md:w-44 text-xs font-medium" placeholder="Segment name">

            <!-- Inline Sector -->
            <select id="toolSegSector" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0">
                <option value="">No Sector (0)</option>
                <?php foreach ($sectors as $sec): ?>
                <option value="<?= esc($sec['id']) ?>"><?= esc($sec['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Inline Slope Type (hidden for lifts) -->
            <select id="toolSlopeType" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" style="display:none">
                <option value="downhill">Downhill</option>
                <option value="crosscountry">Cross-Country</option>
                <option value="snowpark">Snow Park</option>
                <option value="luge">Luge</option>
            </select>

            <!-- Inline Difficulty (hidden for lifts) -->
            <select id="toolSegDiff" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0">
                <option value="">Difficulty (None)</option>
                <option value="green">Green (Easy)</option>
                <option value="blue">Blue (Intermediate)</option>
                <option value="black">Black (Advanced)</option>
                <option value="double_black">Double Black (Expert)</option>
            </select>

            <div class="h-4 w-px bg-white/10 mx-1 hidden sm:block"></div>

            <!-- Actions -->
            <button id="toolBtnSave" class="btn btn-xs btn-success font-bold gap-1 shadow-sm">
                <i class="fa-solid fa-check"></i> Save
            </button>
            <button id="toolBtnUndo" class="btn btn-xs btn-warning btn-outline gap-1" title="Undo point (Z)">
                <i class="fa-solid fa-rotate-left"></i> Undo
            </button>
            <button id="toolBtnCancel" class="btn btn-xs btn-ghost text-error gap-1" title="Cancel (Esc)">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
        </div>
    </div>

    <!-- Admin Toast Notification -->
    <div id="adminToast" class="toast toast-top toast-end z-[999] pointer-events-none transition-all duration-300 opacity-0 translate-y-[-10px]">
        <div id="adminToastAlert" class="alert alert-success py-2 px-3 text-xs shadow-lg font-medium">
            <span id="adminToastMsg">Success</span>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="map-legend" id="mapLegend">
    <div class="map-legend-item"><span style="background:#f59e0b"></span> Lift</div>
    <div class="map-legend-item"><span style="background:#22c55e"></span> Green Run</div>
    <div class="map-legend-item"><span style="background:#3b82f6"></span> Blue Run</div>
    <div class="map-legend-item"><span style="background:#111"></span> Black</div>
    <div class="map-legend-item"><span style="background:#dc2626"></span> Double Black</div>
    <div class="map-legend-item"><span style="background:#a855f7"></span> Terrain Park</div>
</div>

<button class="build-fab btn btn-primary btn-circle btn-lg shadow-xl" id="buildFab" title="Build">
    <i class="fa-solid fa-hammer text-xl"></i>
</button>

<div id="buildDrawer" style="display:none;position:fixed;top:0;right:0;width:340px;height:100vh;background-color:#1d232a;border-left:1px solid rgba(255,255,255,.08);z-index:1000;overflow-y:auto;padding:16px;box-shadow:-4px 0 24px rgba(0,0,0,.4)">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold">Build</h2>
        <button id="closeDrawer" class="btn btn-ghost btn-sm btn-circle"><i class="fa-solid fa-xmark"></i></button>
    </div>

    <div class="flex gap-2 mb-4">
        <button class="build-tab btn btn-sm btn-primary" data-tab="lift"><i class="fa-solid fa-elevator mr-1"></i> Build Lift</button>
        <button class="build-tab btn btn-sm btn-ghost" data-tab="slope"><i class="fa-solid fa-person-skiing mr-1"></i> Build Slope</button>
    </div>

    <div id="segmentList" class="flex flex-col gap-1 mb-4">
        <p class="text-sm text-base-content/50 text-center py-4">Select a purple line to build</p>
    </div>

    <div id="segmentDetail" style="display:none">
        <div class="border border-base-300 rounded-lg p-3 mb-4 bg-base-200/50">
            <p class="font-semibold text-sm mb-2" id="selSegName">-</p>
            <div class="grid grid-cols-2 gap-2 text-xs text-base-content/70 mb-3">
                <div><i class="fa-solid fa-ruler mr-1"></i> <span id="selSegLength">-</span></div>
                <div><i class="fa-solid fa-clock mr-1"></i> <span id="selSegTime">-</span></div>
            </div>

            <div id="liftOptions">
                <p class="text-xs font-semibold text-base-content/60 mb-2">Lift Type</p>
                <div class="grid grid-cols-1 gap-1 mb-3" id="liftTypeGrid">
                    <label class="cursor-pointer"><input type="radio" name="liftType" value="button" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-success peer-checked:bg-success/10 text-xs"><b>Button Lift</b> - 1,000/hr</div></label>
                    <label class="cursor-pointer"><input type="radio" name="liftType" value="chair_fixed" class="peer hidden" checked><div class="border border-base-300 rounded-lg p-2 peer-checked:border-success peer-checked:bg-success/10 text-xs"><b>Chairlift (Fixed)</b> - 2,400/hr</div></label>
                    <label class="cursor-pointer"><input type="radio" name="liftType" value="chair_detach" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-success peer-checked:bg-success/10 text-xs"><b>Chairlift (Detach)</b> - 3,400/hr</div></label>
                    <label class="cursor-pointer"><input type="radio" name="liftType" value="gondola" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-success peer-checked:bg-success/10 text-xs"><b>Gondola</b> - 3,500/hr</div></label>
                    <label class="cursor-pointer"><input type="radio" name="liftType" value="cable_car" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 peer-checked:border-success peer-checked:bg-success/10 text-xs"><b>Cable Car</b> - 4,000/hr</div></label>
                </div>

                <p class="text-xs font-semibold text-base-content/60 mb-2">Seats</p>
                <div class="flex gap-2 mb-3" id="seatGrid">
                    <label class="cursor-pointer flex-1"><input type="radio" name="seats" value="2" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">2</div></label>
                    <label class="cursor-pointer flex-1"><input type="radio" name="seats" value="4" class="peer hidden" checked><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">4</div></label>
                    <label class="cursor-pointer flex-1"><input type="radio" name="seats" value="6" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">6</div></label>
                    <label class="cursor-pointer flex-1"><input type="radio" name="seats" value="8" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">8</div></label>
                </div>
            </div>

            <div id="slopeOptions">
                <p class="text-xs font-semibold text-base-content/60 mb-2">Type</p>
                <div class="grid grid-cols-2 gap-1 mb-3">
                    <label class="cursor-pointer"><input type="radio" name="slopeType" value="downhill" class="peer hidden" checked><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">Downhill</div></label>
                    <label class="cursor-pointer"><input type="radio" name="slopeType" value="crosscountry" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">Cross-Country</div></label>
                    <label class="cursor-pointer"><input type="radio" name="slopeType" value="snowpark" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">Snow Park</div></label>
                    <label class="cursor-pointer"><input type="radio" name="slopeType" value="luge" class="peer hidden"><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">Luge</div></label>
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-base-300 pt-3">
                <div>
                    <p class="text-xs text-base-content/50">Total Cost</p>
                    <p class="text-lg font-bold text-primary" id="selSegCost">-</p>
                </div>
                <button id="btnBuild" class="btn btn-success btn-sm"><i class="fa-solid fa-hammer mr-1"></i> Build</button>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="border-t border-base-300 pt-4 mt-4">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold text-base-content/50 uppercase tracking-wider">Sectors Management</p>
            <span class="badge badge-primary badge-xs">Admin</span>
        </div>
            <div id="sectorList" class="flex flex-col gap-1 mb-3">
                <?php foreach ($sectors as $sec): ?>
                <div class="flex items-center justify-between p-2 rounded bg-base-200/50 text-sm" data-sector-id="<?= $sec['id'] ?>">
                    <span class="flex items-center gap-2">
                        <span style="width:10px;height:10px;border-radius:50%;background:<?= esc($sec['color']) ?>;display:inline-block"></span>
                        <?= esc($sec['name']) ?>
                    </span>
                    <span class="flex gap-1">
                        <button class="btn btn-ghost btn-xs toggle-sector" data-id="<?= $sec['id'] ?>" title="Toggle visibility">
                            <i class="fa-solid fa-eye<?= $sec['visible'] ? '' : '-slash' ?>"></i>
                        </button>
                        <button class="btn btn-ghost btn-xs text-error delete-sector" data-id="<?= $sec['id'] ?>" title="Delete">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="flex gap-2">
                <button id="newSector" class="btn btn-sm btn-outline flex-1"><i class="fa-solid fa-plus mr-1"></i> New Sector</button>
                <button id="autoAssign" class="btn btn-sm btn-outline flex-1"><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Auto-assign</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script data-cfasync="false" src="/js/leaflet.js"></script>

<script data-cfasync="false">
(function(){
    'use strict';

    var SEGMENTS     = <?= $segmentsJson ?>;
    var SECTORS      = <?= $sectorsJson ?>;
    var BUILT_IDS    = <?= $builtIdsJson ?>;
    var RELEASED_IDS = <?= $releasedIdsJson ?>;
    var IS_ADMIN     = <?= $isAdmin ? 'true' : 'false' ?>;
    var CSRF_NAME    = '<?= $csrfName ?>';
    var CSRF_HASH    = '<?= $csrfHash ?>';
    var MAP_IMAGE    = document.getElementById('map').dataset.image;

    var COLORS = {
        lift:'#f59e0b',green:'#22c55e',blue:'#3b82f6',black:'#333',
        double_black:'#dc2626',terrain_park:'#a855f7','default':'#94a3b8'
    };
    var COST_PER_METER = {button:80,chair_fixed:150,chair_detach:250,gondola:400,cable_car:600};
    var SEAT_MULT = {1:0.7,2:1.0,3:1.15,4:1.3,6:1.6,8:2.0,10:2.5,20:4.0,30:5.0};
    var SEAT_OPTIONS = {button:[1,2],chair_fixed:[2,3,4],chair_detach:[4,6,8],gondola:[6,8,10],cable_car:[20,30]};

    var map, segmentLayers={}, selectedSegId=null;
    var buildMode=null, activeTab='lift';

    // Admin Editor State
    var editorMode = null; // null | 'draw' | 'edit'
    var editorType = 'lift'; // 'lift' | 'slope'
    var editingSegId = null;
    var editorPoints = [];
    var editorLine = null;
    var vertexMarkers = [];
    var midpointMarkers = [];

    document.addEventListener('DOMContentLoaded', init);

    function init(){
        var el = document.getElementById('map');
        if(!el || typeof L==='undefined'){console.error('Leaflet not loaded or #map missing');return;}

        map = L.map('map',{crs:L.CRS.Simple,minZoom:-5,maxZoom:4,zoomControl:true,attributionControl:false}).setView([0,0],0);

        var img = new Image();
        var basePath = MAP_IMAGE.replace('.jpg','');
        var imgLow = basePath + '_low.jpg';
        var imgMed = basePath + '_med.jpg';
        var imgFull = MAP_IMAGE;
        var h=<?= $mapConfig['height'] ?>, w=<?= $mapConfig['width'] ?>;
        var bounds=[[0,0],[h,w]];
        var currentLayer = null;
        var loadedLayers = {};

        function setOverlay(url) {
            if (currentLayer && currentLayer._url === url) return;
            if (!loadedLayers[url]) loadedLayers[url] = L.imageOverlay(url, bounds);
            if (currentLayer) map.removeLayer(currentLayer);
            currentLayer = loadedLayers[url];
            currentLayer.addTo(map);
        }

        function pickResolution() {
            var z = map.getZoom();
            if (z >= 2) setOverlay(imgFull);
            else if (z >= 0) setOverlay(imgMed);
            else setOverlay(imgLow);
        }

        setOverlay(imgLow);
        setTimeout(function(){var m=new Image();m.src=imgMed;m.onload=function(){var f=new Image();f.src=imgFull;};},1000);
        map.fitBounds(bounds);
        map.setMaxBounds([[-h*0.1,-w*0.1],[h*1.1,w*1.1]]);
        map.on('zoomend', pickResolution);
        var ld=document.getElementById('mapLoader');if(ld)ld.remove();
        renderSegments();
        bindUI();if(IS_ADMIN) bindAdmin();

        // If user has no built slopes or lifts, auto-open the slope build drawer so options are immediately obvious
        if(BUILT_IDS.length === 0 && !IS_ADMIN) {
            setTimeout(function(){ openDrawer('slope'); }, 300);
        }
    }

    function isSegmentReleased(seg) {
        if (IS_ADMIN) return true;
        if (!RELEASED_IDS || RELEASED_IDS.length === 0) return true;
        var secStr = String(seg.sector !== null && typeof seg.sector !== 'undefined' ? seg.sector : '');
        var secNum = Number(seg.sector);
        var isRel = RELEASED_IDS.some(function(r){ return String(r) === secStr || Number(r) === secNum; });
        if (isRel) return true;
        // Unassigned segments (sector 0 / empty) are accessible by default
        return seg.sector === 0 || seg.sector === '0' || seg.sector === '' || seg.sector === null || typeof seg.sector === 'undefined';
    }

    function renderSegments(mode){
        buildMode=mode||null;
        Object.values(segmentLayers).forEach(function(l){map.removeLayer(l);});
        segmentLayers={};
        SEGMENTS.forEach(function(seg){
            if (editingSegId && seg.id == editingSegId) return;
            var pts=typeof seg.points==='string'?JSON.parse(seg.points):seg.points;
            if(!pts||pts.length<2) return;
            var ll=pts.map(function(p){return[p[0]||p.lat||0,p[1]||p.lng||0];});
            var built=BUILT_IDS.indexOf(String(seg.id))!==-1||BUILT_IDS.indexOf(Number(seg.id))!==-1;
            if(!buildMode&&!built&&!IS_ADMIN) return;
            if(buildMode&&!built){
                if(buildMode==='lift'&&seg.type!=='lift') return;
                if(buildMode==='slope'&&seg.type==='lift') return;
                if(!isSegmentReleased(seg)) return;
            }
            var c=(built||!buildMode)?segColor(seg):'#a855f7';
            var line=L.polyline(ll,{color:c,weight:built?5:6,opacity:built?1:0.85,dashArray:built?null:'6,4'}).addTo(map);
            if(built){
                line.bindTooltip(seg.name||seg.type,{sticky:true});
                if(IS_ADMIN){
                    line.on("click", function(e){
                        if (L.DomEvent) L.DomEvent.stopPropagation(e);
                        if (editorMode) return;
                        openAdminSegmentPopup(seg, e.latlng);
                    });
                } else {
                    line.bindPopup("<div style=\"text-align:center;min-width:120px\"><b>"+(seg.name||seg.type)+"</b><br>"+Math.round(seg.length_meters||0)+" <?= distanceUnit() ?><br><span style=\"opacity:.6\">"+seg.type+"</span></div>");
                }
            }
            if(!built){
                line.bindTooltip("<div style=\"text-align:center;padding:2px 4px\"><b>"+(seg.name||seg.type)+"</b><br>"+Math.round(seg.length_meters||0)+" <?= distanceUnit() ?><br><span style=\"color:#a855f7;font-weight:600\">" + (IS_ADMIN ? "Click for options" : "Click to select") + "</span></div>");
                line.on("mouseover", function(){
                    if(!selectedSegId || selectedSegId !== seg.id){
                        line.setStyle({weight:8, opacity:1});
                    }
                });
                line.on("mouseout", function(){
                    if(!selectedSegId || selectedSegId !== seg.id){
                        line.setStyle({weight:6, opacity:0.85});
                    }
                });
                line.on("click",function(e){
                    if (L.DomEvent) L.DomEvent.stopPropagation(e);
                    if (editorMode) return;
                    if (IS_ADMIN) {
                        openAdminSegmentPopup(seg, e.latlng);
                    } else {
                        selectSegment(seg);
                    }
                });
            }
            segmentLayers[seg.id]=line;
        });
    }

    function segColor(s){
        if(s.type==='lift') return COLORS.lift;
        var d=s.difficulty||'';
        if(d==='green') return COLORS.green;
        if(d==='blue') return COLORS.blue;
        if(d==='black') return COLORS.black;
        if(d==='double_black') return COLORS.double_black;
        if(s.type==='snowpark'||s.type==='terrain_park') return COLORS.terrain_park;
        return COLORS['default'];
    }

    function openDrawer(tab){
        var drawer=document.getElementById('buildDrawer'),fab=document.getElementById('buildFab');
        drawer.style.display='block';
        if(fab) fab.style.display='none';
        var targetTab = tab || 'lift';
        document.querySelectorAll('.build-tab').forEach(function(b){
            if (b.dataset.tab === targetTab) {
                b.classList.add('btn-primary');
                b.classList.remove('btn-ghost');
            } else {
                b.classList.remove('btn-primary');
                b.classList.add('btn-ghost');
            }
        });
        renderSegments(targetTab);
        populateList(targetTab);
        deselectSeg();
    }

    function closeDrawer(){
        var drawer=document.getElementById('buildDrawer'),fab=document.getElementById('buildFab');
        drawer.style.display='none';
        if(fab) fab.style.display='';
        deselectSeg();
        renderSegments();
        var ld=document.getElementById('mapLoader');if(ld)ld.remove();
    }

    function bindUI(){
        var fab=document.getElementById('buildFab');
        var headerBtn=document.getElementById('headerBuildBtn');
        if(fab) fab.addEventListener('click',function(){ openDrawer('lift'); });
        if(headerBtn) headerBtn.addEventListener('click',function(){ openDrawer('lift'); });
        document.getElementById('closeDrawer').addEventListener('click', closeDrawer);
        document.querySelectorAll('.build-tab').forEach(function(t){
            t.addEventListener('click',function(){
                openDrawer(t.dataset.tab);
            });
        });
        document.querySelectorAll('input[name="liftType"],input[name="seats"]').forEach(function(el){
            el.addEventListener('change',function(){if(el.name==='liftType')updateSeats();else updateCost();});
        });
        document.getElementById('btnBuild').addEventListener('click',doBuild);
    }

    function populateList(type){
        activeTab=type;
        var list=document.getElementById('segmentList');
        if(!list) return;
        var avail=SEGMENTS.filter(function(s){
            if(type==='lift'&&s.type!=='lift') return false;
            if(type==='slope'&&s.type==='lift') return false;
            if(!isSegmentReleased(s)) return false;
            return BUILT_IDS.indexOf(String(s.id))===-1&&BUILT_IDS.indexOf(Number(s.id))===-1;
        });
        if(!avail.length){
            list.innerHTML='<p class="text-sm text-base-content/50 text-center py-4">No available '+type+'s found</p>';
            return;
        }
        list.innerHTML='<p class="text-xs text-base-content/60 font-medium mb-1">Click a run below or on the map:</p>';
        avail.forEach(function(seg){
            var btn=document.createElement('button');
            btn.type='button';
            btn.className='btn btn-sm btn-ghost justify-start text-left w-full gap-2 border border-base-300/60 hover:border-primary hover:bg-base-200 transition-all py-1.5 h-auto mb-1';
            var c=segColor(seg);
            var diffBadge = seg.difficulty ? '<span class="badge badge-xs text-[10px] uppercase font-bold" style="background:'+c+';color:#fff">'+seg.difficulty+'</span> ' : '';
            btn.innerHTML='<span style="width:10px;height:10px;border-radius:50%;background:'+c+';flex-shrink:0"></span> <span class="truncate font-medium">'+(seg.name||'Unnamed')+'</span> '+diffBadge+'<span class="ml-auto text-base-content/50 text-xs font-mono">'+Math.round(seg.length_meters||0)+' <?= distanceUnit() ?></span>';
            btn.addEventListener('click',function(){selectSegment(seg);});
            list.appendChild(btn);
        });
    }

    function selectSegment(seg){
        deselectSeg();selectedSegId=seg.id;
        if(segmentLayers[seg.id]) {
            segmentLayers[seg.id].setStyle({weight:8,opacity:1,color:'#fff'});
            try {
                if(typeof segmentLayers[seg.id].getBounds === 'function') {
                    map.panTo(segmentLayers[seg.id].getBounds().getCenter(), {animate: true});
                }
            } catch(e){}
        }
        document.getElementById('segmentDetail').style.display='block';
        document.getElementById('selSegName').textContent=seg.name||'Unnamed';
        document.getElementById('selSegLength').textContent=Math.round(seg.length_meters||0)+' <?= distanceUnit() ?>';
        var days=(seg.length_meters||0)<200?1:((seg.length_meters||0)<500?2:3);
        document.getElementById('selSegTime').textContent=days+' day'+(days>1?'s':'');
        document.getElementById('liftOptions').style.display=seg.type==='lift'?'block':'none';
        document.getElementById('slopeOptions').style.display=seg.type==='lift'?'none':'block';
        if(seg.type!=='lift'){var sr=document.querySelector('input[name="slopeType"][value="'+seg.type+'"]');if(sr)sr.checked=true;}
        document.getElementById('buildDrawer').style.display='block';
        var fab=document.getElementById('buildFab');if(fab)fab.style.display='none';
        if(seg.type==='lift')updateSeats();else updateCost();
    }

    function deselectSeg(){
        if(selectedSegId&&segmentLayers[selectedSegId]){
            var s=SEGMENTS.find(function(x){return x.id==selectedSegId;});
            if(s){var b=BUILT_IDS.indexOf(String(s.id))!==-1||BUILT_IDS.indexOf(Number(s.id))!==-1;segmentLayers[selectedSegId].setStyle({color:(b||!buildMode)?segColor(s):'#a855f7',weight:b?5:6,opacity:b?1:0.85});}
        }
        selectedSegId=null;document.getElementById('segmentDetail').style.display='none';
    }

    function updateCost(){
        if(!selectedSegId) return;
        var seg=SEGMENTS.find(function(s){return s.id==selectedSegId;});if(!seg) return;
        var m=parseFloat(seg.length_meters)||0,cost;
        if(seg.type==='lift'){
            var lt=document.querySelector('input[name="liftType"]:checked'),st=document.querySelector('input[name="seats"]:checked');
            cost=m*(COST_PER_METER[lt?lt.value:'chair_fixed']||200)*(SEAT_MULT[st?parseInt(st.value):4]||1);
        }else{cost=m*50;}
        document.getElementById('selSegCost').textContent='\u20AC'+Math.round(cost).toLocaleString();
    }

    function updateSeats(){var lt=document.querySelector('input[name="liftType"]:checked');var type=lt?lt.value:'chair_fixed';var opts=SEAT_OPTIONS[type]||[2,4,6,8];var grid=document.getElementById('seatGrid');grid.innerHTML='';opts.forEach(function(n,i){var label=document.createElement('label');label.className='cursor-pointer flex-1';label.innerHTML='<input type="radio" name="seats" value="'+n+'" class="peer hidden"'+(i===0?' checked':'')+'><div class="border border-base-300 rounded-lg p-2 text-center peer-checked:border-success peer-checked:bg-success/10 text-xs font-semibold">'+n+'</div>';grid.appendChild(label);});grid.querySelectorAll('input[name="seats"]').forEach(function(el){el.addEventListener('change',updateCost);});updateCost();}
    
    function doBuild(){
        if(!selectedSegId) return;
        var seg=SEGMENTS.find(function(s){return s.id==selectedSegId;});if(!seg) return;
        var body={segment_id:seg.id};
        if(seg.type==='lift'){
            var lt=document.querySelector('input[name="liftType"]:checked'),st=document.querySelector('input[name="seats"]:checked');
            body.lift_type=lt?lt.value:'fixed';body.seats=st?parseInt(st.value):4;
        }else{
            var sl=document.querySelector('input[name="slopeType"]:checked');
            body.slope_type=sl?sl.value:'downhill';
        }
        postJSON('/map/build',body,function(res){
            if(res.success){
                BUILT_IDS.push(String(seg.id));
                deselectSeg();
                renderSegments(buildMode);
                populateList(activeTab);
                if (typeof loadTutorial === 'function') loadTutorial();
            }
            else{alert(res.error||'Build failed');}
        });
    }

    function postJSON(url, data, callback) {
        var payload = Object.assign({}, data || {});
        payload[CSRF_NAME] = CSRF_HASH;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) {
            return res.json().catch(function() {
                throw new Error('Server returned invalid response');
            });
        })
        .then(function(json) {
            if (json && json.csrf_hash) {
                CSRF_HASH = json.csrf_hash;
            }
            if (callback) callback(json);
        })
        .catch(function(err) {
            console.error('API Error:', err);
            showToast(err.message || 'Request failed', 'error');
        });
    }

    function calcLengthMeters(points) {
        if (!points || points.length < 2) return 0;
        var total = 0;
        for (var i = 1; i < points.length; i++) {
            var dy = points[i][0] - points[i-1][0];
            var dx = points[i][1] - points[i-1][1];
            total += Math.sqrt(dy * dy + dx * dx);
        }
        return Math.round(total * 15.0);
    }

    function getSectorName(secId) {
        if (!secId || secId === '0' || secId === 0) return 'Default (0)';
        var sec = SECTORS.find(function(s) { return String(s.id) === String(secId) || s.name === String(secId); });
        return sec ? sec.name : 'Sector ' + secId;
    }

    function showToast(msg, type) {
        var toast = document.getElementById('adminToast');
        var alertEl = document.getElementById('adminToastAlert');
        var msgEl = document.getElementById('adminToastMsg');
        if (!toast || !alertEl || !msgEl) {
            if (type === 'error') alert(msg);
            return;
        }
        msgEl.textContent = msg;
        alertEl.className = 'alert py-2 px-3 text-xs shadow-lg font-medium ' +
            (type === 'error' ? 'alert-error' : (type === 'warning' ? 'alert-warning' : (type === 'info' ? 'alert-info' : 'alert-success')));
        toast.classList.remove('opacity-0', 'translate-y-[-10px]');
        toast.classList.add('opacity-100', 'translate-y-0');
        if (window._toastTimeout) clearTimeout(window._toastTimeout);
        window._toastTimeout = setTimeout(function() {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-[-10px]');
        }, 2800);
    }

    function clearEditorMarkers() {
        vertexMarkers.forEach(function(m) { map.removeLayer(m); });
        vertexMarkers = [];
        midpointMarkers.forEach(function(m) { map.removeLayer(m); });
        midpointMarkers = [];
    }

    function createVertexMarker(latlng, index, accentColor) {
        var icon = L.divIcon({
            className: 'custom-vertex-wrap',
            html: '<div class="custom-vertex-handle" style="border-color:' + accentColor + '"></div>',
            iconSize: [14, 14],
            iconAnchor: [7, 7]
        });
        var marker = L.marker(latlng, {
            icon: icon,
            draggable: true,
            zIndexOffset: 1000
        });
        marker.on('drag', function() {
            var ll = marker.getLatLng();
            editorPoints[index] = [ll.lat, ll.lng];
            updateEditorDisplay(false);
        });
        marker.on('dragend', function() {
            updateEditorDisplay(true);
        });
        marker.on('contextmenu', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            if (editorPoints.length > 2) {
                editorPoints.splice(index, 1);
                updateEditorDisplay(true);
            } else {
                showToast('A line requires at least 2 points', 'warning');
            }
        });
        return marker;
    }

    function createMidpointMarker(index, accentColor) {
        var p1 = editorPoints[index];
        var p2 = editorPoints[index + 1];
        var midLat = (p1[0] + p2[0]) / 2;
        var midLng = (p1[1] + p2[1]) / 2;

        var icon = L.divIcon({
            className: 'custom-midpoint-wrap',
            html: '<div class="custom-midpoint-handle" style="background:' + accentColor + '"></div>',
            iconSize: [10, 10],
            iconAnchor: [5, 5]
        });

        var marker = L.marker([midLat, midLng], {
            icon: icon,
            draggable: true,
            zIndexOffset: 900
        });

        var insertedIndex = -1;

        marker.on('dragstart', function() {
            insertedIndex = index + 1;
            var ll = marker.getLatLng();
            editorPoints.splice(insertedIndex, 0, [ll.lat, ll.lng]);
        });

        marker.on('drag', function() {
            if (insertedIndex !== -1) {
                var ll = marker.getLatLng();
                editorPoints[insertedIndex] = [ll.lat, ll.lng];
                updateEditorDisplay(false);
            }
        });

        marker.on('dragend', function() {
            insertedIndex = -1;
            updateEditorDisplay(true);
        });

        return marker;
    }

    function updateEditorDisplay(rebuildMarkers) {
        var diff = document.getElementById('toolSegDiff') ? document.getElementById('toolSegDiff').value : '';
        var accentColor = editorType === 'lift' ? COLORS.lift : (COLORS[diff] || COLORS.green);

        if (editorLine) {
            map.removeLayer(editorLine);
            editorLine = null;
        }
        if (editorPoints.length > 0) {
            editorLine = L.polyline(editorPoints, {
                color: accentColor,
                weight: 5,
                opacity: 0.95,
                dashArray: '6, 6'
            }).addTo(map);
        }

        var lengthM = calcLengthMeters(editorPoints);
        var lenEl = document.getElementById('toolLiveLength');
        var ptsEl = document.getElementById('toolPointCount');
        if (lenEl) lenEl.textContent = lengthM.toLocaleString();
        if (ptsEl) ptsEl.textContent = editorPoints.length;

        if (rebuildMarkers) {
            clearEditorMarkers();
            editorPoints.forEach(function(pt, idx) {
                var vm = createVertexMarker(pt, idx, accentColor);
                vm.addTo(map);
                vertexMarkers.push(vm);
            });
            if (editorPoints.length >= 2) {
                for (var i = 0; i < editorPoints.length - 1; i++) {
                    var mm = createMidpointMarker(i, accentColor);
                    mm.addTo(map);
                    midpointMarkers.push(mm);
                }
            }
        }
    }

    function startDraw(type) {
        if (editorMode) cancelEditor(true);
        editorMode = 'draw';
        editingSegId = null;
        editorType = type;
        editorPoints = [];

        document.getElementById('toolIdleView').style.display = 'none';
        var activeView = document.getElementById('toolActiveView');
        activeView.style.display = 'flex';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge text-xs font-bold gap-1 py-2.5 px-2.5 ' + (type === 'lift' ? 'badge-warning' : 'badge-success');
        document.getElementById('toolModeText').textContent = 'New ' + (type === 'lift' ? 'Lift' : 'Slope');

        var typeCount = SEGMENTS.filter(function(s) { return type === 'lift' ? s.type === 'lift' : s.type !== 'lift'; }).length + 1;
        document.getElementById('toolSegName').value = type === 'lift' ? 'Lift Line ' + typeCount : 'Slope Path ' + typeCount;

        var activeSec = document.getElementById('toolActiveSector') ? document.getElementById('toolActiveSector').value : '';
        document.getElementById('toolSegSector').value = activeSec;

        var slopeTypeEl = document.getElementById('toolSlopeType');
        var diffEl = document.getElementById('toolSegDiff');
        if (type !== 'lift') {
            slopeTypeEl.style.display = 'inline-block';
            slopeTypeEl.value = 'downhill';
            diffEl.style.display = 'inline-block';
            diffEl.value = 'blue';
        } else {
            slopeTypeEl.style.display = 'none';
            diffEl.style.display = 'none';
        }

        map.getContainer().style.cursor = 'crosshair';
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Click anywhere on the mountain to add points', 'info');
    }

    function startEditSegment(seg) {
        if (editorMode) cancelEditor(true);
        editorMode = 'edit';
        editingSegId = seg.id;
        editorType = seg.type;

        var pts = typeof seg.points === 'string' ? JSON.parse(seg.points) : seg.points;
        editorPoints = (pts || []).map(function(p) { return [p[0] || p.lat || 0, p[1] || p.lng || 0]; });

        // Hide static layer
        if (segmentLayers[seg.id]) {
            map.removeLayer(segmentLayers[seg.id]);
        }

        document.getElementById('toolIdleView').style.display = 'none';
        var activeView = document.getElementById('toolActiveView');
        activeView.style.display = 'flex';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge text-xs font-bold gap-1 py-2.5 px-2.5 ' + (seg.type === 'lift' ? 'badge-warning' : 'badge-success');
        document.getElementById('toolModeText').textContent = 'Editing ' + (seg.type === 'lift' ? 'Lift' : 'Slope');

        document.getElementById('toolSegName').value = seg.name || '';
        document.getElementById('toolSegSector').value = seg.sector || '';

        var slopeTypeEl = document.getElementById('toolSlopeType');
        var diffEl = document.getElementById('toolSegDiff');
        if (seg.type !== 'lift') {
            slopeTypeEl.style.display = 'inline-block';
            slopeTypeEl.value = seg.type || 'downhill';
            diffEl.style.display = 'inline-block';
            diffEl.value = seg.difficulty || '';
        } else {
            slopeTypeEl.style.display = 'none';
            diffEl.style.display = 'none';
        }

        map.getContainer().style.cursor = 'crosshair';
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Editing path: drag handles to adjust or drag midpoints to bend', 'info');
    }

    function cancelEditor(skipRender) {
        editorMode = null;
        editingSegId = null;
        editorPoints = [];

        if (editorLine) {
            map.removeLayer(editorLine);
            editorLine = null;
        }
        clearEditorMarkers();

        map.getContainer().style.cursor = '';
        map.doubleClickZoom.enable();

        var activeView = document.getElementById('toolActiveView');
        var idleView = document.getElementById('toolIdleView');
        if (activeView) activeView.style.display = 'none';
        if (idleView) idleView.style.display = 'flex';

        if (!skipRender) {
            renderSegments(buildMode);
        }
    }

    function saveEditor() {
        if (editorPoints.length < 2) {
            showToast('Please place at least 2 points for a line', 'warning');
            return;
        }

        var name = document.getElementById('toolSegName').value.trim();
        if (!name) {
            var typeCount = SEGMENTS.filter(function(s) { return editorType === 'lift' ? s.type === 'lift' : s.type !== 'lift'; }).length + 1;
            name = editorType === 'lift' ? 'Lift Line ' + typeCount : 'Slope Path ' + typeCount;
        }

        var sector = document.getElementById('toolSegSector').value;
        var slopeType = editorType === 'slope' ? (document.getElementById('toolSlopeType').value || 'downhill') : editorType;
        var difficulty = editorType === 'slope' ? (document.getElementById('toolSegDiff').value || '') : '';
        var lengthM = calcLengthMeters(editorPoints);

        var payload = {
            name: name,
            type: slopeType,
            sector: sector,
            difficulty: difficulty,
            length_meters: lengthM,
            points: editorPoints
        };
        if (editingSegId) {
            payload.id = editingSegId;
        }

        var btnSave = document.getElementById('toolBtnSave');
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Saving...';
        }

        postJSON('/map/segment', payload, function(res) {
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fa-solid fa-check"></i> Save';
            }

            if (res && res.success) {
                var savedId = res.id || editingSegId;
                var updatedSeg = {
                    id: savedId,
                    name: name,
                    type: slopeType,
                    sector: sector,
                    difficulty: difficulty,
                    length_meters: lengthM,
                    points: JSON.stringify(editorPoints),
                    active: 1
                };

                if (editingSegId) {
                    var idx = SEGMENTS.findIndex(function(s) { return s.id == editingSegId; });
                    if (idx !== -1) {
                        SEGMENTS[idx] = updatedSeg;
                    }
                    showToast('Segment "' + name + '" updated!', 'success');
                } else {
                    SEGMENTS.push(updatedSeg);
                    showToast('New segment "' + name + '" created!', 'success');
                }

                cancelEditor(false);
            } else {
                showToast((res && res.error) ? res.error : 'Failed to save segment', 'error');
            }
        });
    }

    function undoLastPoint() {
        if (!editorMode || editorPoints.length === 0) return;
        editorPoints.pop();
        updateEditorDisplay(true);
    }

    function openAdminSegmentPopup(seg, latlng) {
        var c = segColor(seg);
        var secName = getSectorName(seg.sector);
        var diffBadge = seg.difficulty ? '<span class="badge badge-xs text-[10px] font-bold uppercase" style="background:'+c+';color:#fff">'+seg.difficulty+'</span>' : '';
        var popupContent = document.createElement('div');
        popupContent.className = 'admin-segment-popover p-1 text-xs';
        popupContent.innerHTML = 
            '<div class="flex items-center justify-between gap-2 mb-2 pb-1 border-b border-base-content/10">' +
                '<div class="font-bold text-sm flex items-center gap-1.5">' +
                    (seg.type === 'lift' ? '<i class="fa-solid fa-elevator text-warning"></i>' : '<i class="fa-solid fa-person-skiing text-success"></i>') +
                    ' <span class="truncate max-w-[140px]">' + (seg.name || 'Unnamed') + '</span>' +
                '</div>' +
                diffBadge +
            '</div>' +
            '<div class="space-y-1 mb-3 text-base-content/80 text-[11px]">' +
                '<div class="flex items-center justify-between"><span>Length:</span> <span class="font-mono font-bold">' + Math.round(seg.length_meters || 0) + ' <?= distanceUnit() ?></span></div>' +
                '<div class="flex items-center justify-between"><span>Type:</span> <span class="badge badge-ghost badge-xs capitalize">' + seg.type + '</span></div>' +
                '<div class="flex items-center justify-between"><span>Sector:</span> <span class="badge badge-outline badge-xs">' + secName + '</span></div>' +
            '</div>' +
            '<div class="grid grid-cols-2 gap-1.5 mb-1.5">' +
                '<button class="btn btn-xs btn-primary font-bold edit-path-btn gap-1"><i class="fa-solid fa-pen-to-square"></i> Edit Path</button>' +
                '<button class="btn btn-xs btn-error btn-outline font-bold delete-seg-btn gap-1"><i class="fa-solid fa-trash"></i> Delete</button>' +
            '</div>' +
            '<button class="btn btn-xs btn-ghost border border-base-300 w-full font-semibold build-as-player-btn gap-1"><i class="fa-solid fa-hammer text-[10px]"></i> Open in Build Drawer</button>';

        popupContent.querySelector('.edit-path-btn').addEventListener('click', function() {
            map.closePopup();
            startEditSegment(seg);
        });
        popupContent.querySelector('.delete-seg-btn').addEventListener('click', function() {
            map.closePopup();
            confirmDeleteSegment(seg);
        });
        popupContent.querySelector('.build-as-player-btn').addEventListener('click', function() {
            map.closePopup();
            selectSegment(seg);
        });

        L.popup({ minWidth: 210, maxWidth: 280, className: 'admin-leaflet-popup' })
            .setLatLng(latlng)
            .setContent(popupContent)
            .openOn(map);
    }

    function confirmDeleteSegment(seg) {
        if (!confirm('Are you sure you want to delete "' + (seg.name || 'Segment #' + seg.id) + '"? This cannot be undone.')) {
            return;
        }
        postJSON('/map/segment/delete/' + seg.id, {}, function(res) {
            if (res && res.success) {
                SEGMENTS = SEGMENTS.filter(function(s) { return s.id != seg.id; });
                if (segmentLayers[seg.id]) {
                    map.removeLayer(segmentLayers[seg.id]);
                    delete segmentLayers[seg.id];
                }
                renderSegments(buildMode);
                showToast('Segment deleted', 'success');
            } else {
                showToast((res && res.error) ? res.error : 'Failed to delete segment', 'error');
            }
        });
    }

    function bindAdmin() {
        var btnLift = document.getElementById('btnNewLift');
        var btnSlope = document.getElementById('btnNewSlope');
        var btnSave = document.getElementById('toolBtnSave');
        var btnUndo = document.getElementById('toolBtnUndo');
        var btnCancel = document.getElementById('toolBtnCancel');

        if (btnLift) btnLift.addEventListener('click', function() { startDraw('lift'); });
        if (btnSlope) btnSlope.addEventListener('click', function() { startDraw('slope'); });
        if (btnSave) btnSave.addEventListener('click', saveEditor);
        if (btnUndo) btnUndo.addEventListener('click', undoLastPoint);
        if (btnCancel) btnCancel.addEventListener('click', function() { cancelEditor(false); });

        var diffEl = document.getElementById('toolSegDiff');
        if (diffEl) {
            diffEl.addEventListener('change', function() {
                if (editorMode) updateEditorDisplay(true);
            });
        }

        var newSecBtn = document.getElementById('newSector');
        if (newSecBtn) {
            newSecBtn.addEventListener('click', function() {
                var n = prompt('Sector name:'); if (!n) return;
                postJSON('/map/sector/create', {name: n}, function() { location.reload(); });
            });
        }

        var autoAssignBtn = document.getElementById('autoAssign');
        if (autoAssignBtn) {
            autoAssignBtn.addEventListener('click', function() {
                postJSON('/map/sector/auto-assign', {}, function(r) { alert('Assigned ' + (r.assigned || 0) + ' segments'); });
            });
        }

        document.querySelectorAll('.toggle-sector').forEach(function(b) {
            b.addEventListener('click', function() { postJSON('/map/sector/toggle/' + b.dataset.id, {}, function() { location.reload(); }); });
        });

        document.querySelectorAll('.delete-sector').forEach(function(b) {
            b.addEventListener('click', function() {
                if (!confirm('Delete this sector?')) return;
                postJSON('/map/sector/delete/' + b.dataset.id, {}, function() { location.reload(); });
            });
        });

        map.on('click', function(e) {
            if (!editorMode) return;
            editorPoints.push([e.latlng.lat, e.latlng.lng]);
            updateEditorDisplay(true);
        });

        map.on('dblclick', function(e) {
            if (!editorMode) return;
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            saveEditor();
        });

        document.addEventListener('keydown', function(e) {
            var tag = e.target.tagName ? e.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'select' || tag === 'textarea') {
                if (e.key === 'Enter' && editorMode) saveEditor();
                else if (e.key === 'Escape' && editorMode) cancelEditor(false);
                return;
            }

            if (editorMode) {
                if (e.key === 'Enter') { e.preventDefault(); saveEditor(); }
                else if (e.key === 'Escape') { e.preventDefault(); cancelEditor(false); }
                else if (e.key === 'z' || e.key === 'Z') { e.preventDefault(); undoLastPoint(); }
            } else if (IS_ADMIN) {
                if (e.key === 'l' || e.key === 'L') { e.preventDefault(); startDraw('lift'); }
                else if (e.key === 's' || e.key === 'S') { e.preventDefault(); startDraw('slope'); }
            }
        });
    }

    var resortSelect = document.getElementById('resortSelect');
    if (resortSelect) {
        resortSelect.addEventListener('change', function() {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = '/map/change-map';
            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = CSRF_NAME;
            csrfInput.value = CSRF_HASH;
            form.appendChild(csrfInput);
            var mapInput = document.createElement('input');
            mapInput.type = 'hidden';
            mapInput.name = 'map';
            mapInput.value = this.value;
            form.appendChild(mapInput);
            document.body.appendChild(form);
            form.submit();
        });
    }
})();
</script>

<?= $this->endSection() ?>
