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
    <link rel="preload" href="<?= esc($mapConfig['image']) ?>" as="image">
<style>
#map{height:calc(100vh - 160px);width:100%;background:#1a1a2e;position:relative;z-index:0}
.map-legend{position:absolute;bottom:12px;left:12px;z-index:800;background:rgba(30,30,46,.9);border-radius:8px;padding:10px 14px;font-size:12px;color:#ccc;display:flex;flex-direction:column;gap:4px;backdrop-filter:blur(6px)}
.map-legend-item{display:flex;align-items:center;gap:6px}
.map-legend-item span{width:20px;height:3px;border-radius:2px;display:inline-block}
.build-fab{position:fixed;bottom:24px;right:24px;z-index:900}
</style>

<div class="flex items-center justify-between px-4 py-3 border-b border-base-300">
    <div class="flex items-center gap-3">
        <a href="/resort" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i></a>
        <h1 class="text-lg font-bold">Trail Map</h1>
    </div>
    <div class="flex items-center gap-2">
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

<div id="map" data-image="<?= $isAdmin ? esc(str_replace(".jpg", "-Big.jpg", $mapConfig["image"])) : esc($mapConfig["image"]) ?>"></div>
    <div id="mapLoader" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:100;text-align:center"><span class="loading loading-spinner loading-lg text-primary"></span><p class="text-sm text-base-content/50 mt-2">Loading trail map...</p></div>

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

    <div id="segmentList" class="flex flex-col gap-2 mb-4">
        <p class="text-sm text-base-content/50 text-center py-4">Select a purple line to build</p>
    </div>

    <div id="segmentDetail">
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
        <p class="text-xs font-semibold text-base-content/50 mb-3 uppercase tracking-wider">Admin: Draw New Path</p>
        <div class="flex gap-2 mb-3">
            <button id="drawLift" class="btn btn-sm btn-outline btn-warning flex-1"><i class="fa-solid fa-elevator mr-1"></i> Lift Line</button>
            <button id="drawSlope" class="btn btn-sm btn-outline btn-success flex-1"><i class="fa-solid fa-person-skiing mr-1"></i> Slope</button>
        </div>
        <div id="drawControls">
            <p class="text-xs text-base-content/60 mb-2">Click on the map to place points. Double-click or press Finish to complete.</p>
            <div class="flex flex-col gap-1 mb-3">
                <input type="text" id="drawName" class="input input-sm input-bordered w-full" placeholder="Segment name">
                <select id="drawSlopeType" class="select select-sm select-bordered w-full" style="display:none">
                    <option value="downhill">Downhill</option>
                    <option value="crosscountry">Cross-Country</option>
                    <option value="snowpark">Snow Park</option>
                    <option value="luge">Luge</option>
                </select>
                <select id="drawDifficulty" class="select select-sm select-bordered w-full">
                    <option value="">No difficulty</option>
                    <option value="green">Green</option>
                    <option value="blue">Blue</option>
                    <option value="black">Black</option>
                    <option value="double_black">Double Black</option>
                </select>
                <select id="drawSector" class="select select-sm select-bordered w-full">
                    <option value="">No Sector</option>
                    <?php foreach ($sectors as $sec): ?>
                    <option value="<?= esc($sec['name']) ?>"><?= esc($sec['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button id="drawFinish" class="btn btn-sm btn-success flex-1">Finish</button>
                <button id="drawUndo" class="btn btn-sm btn-warning flex-1">Undo</button>
                <button id="drawCancel" class="btn btn-sm btn-error flex-1">Cancel</button>
            </div>
        </div>

        <div class="border-t border-base-300 pt-4 mt-4">
            <p class="text-xs font-semibold text-base-content/50 mb-3 uppercase tracking-wider">Sectors</p>
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
    var COST_PER_METER = {button:800,chair_fixed:1500,chair_detach:2500,gondola:4000,cable_car:6000};
    var SEAT_MULT = {1:0.7,2:1.0,3:1.15,4:1.3,6:1.6,8:2.0,10:2.5,20:4.0,30:5.0};
    var SEAT_OPTIONS = {button:[1,2],chair_fixed:[2,3,4],chair_detach:[4,6,8],gondola:[6,8,10],cable_car:[20,30]};

    var map, segmentLayers={}, selectedSegId=null;
    var drawingMode=false, drawType=null, drawPoints=[], drawLine=null;

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
    }

    var buildMode=null;
    function renderSegments(mode){
        buildMode=mode||null;
        Object.values(segmentLayers).forEach(function(l){map.removeLayer(l);});
        segmentLayers={};
        SEGMENTS.forEach(function(seg){
            var pts=typeof seg.points==='string'?JSON.parse(seg.points):seg.points;
            if(!pts||pts.length<2) return;
            var ll=pts.map(function(p){return[p[0]||p.lat||0,p[1]||p.lng||0];});
            var built=BUILT_IDS.indexOf(String(seg.id))!==-1||BUILT_IDS.indexOf(Number(seg.id))!==-1;
            if(!buildMode&&!built&&!IS_ADMIN) return;
            if(buildMode&&!built){
                if(buildMode==='lift'&&seg.type!=='lift') return;
                if(buildMode==='slope'&&seg.type==='lift') return;
            if(!IS_ADMIN&&RELEASED_IDS.indexOf(String(seg.sector))===-1) return;
            }
            var c=(built||!buildMode)?segColor(seg):'#a855f7';
            var line=L.polyline(ll,{color:c,weight:built?5:4,opacity:built?1:0.7,dashArray:built?null:'6,4'}).addTo(map);
            if(built){line.bindPopup("<div style=\"text-align:center;min-width:120px\"><b>"+seg.name+"</b><br>"+Math.round(seg.length_meters||0)+" <?= distanceUnit() ?><br><span style=\"opacity:.6\">"+seg.type+"</span></div>");line.bindTooltip(seg.name||seg.type,{sticky:true});}
            if(!built){line.bindTooltip("<div style=\"text-align:center\"><b>"+seg.name+"</b><br>"+Math.round(seg.length_meters||0)+" <?= distanceUnit() ?><br><small>"+seg.type+"</small></div>");line.on("click",function(){if(!drawingMode) selectSegment(seg);});}
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

    function bindUI(){
        var drawer=document.getElementById('buildDrawer'),fab=document.getElementById('buildFab');
        fab.addEventListener('click',function(){drawer.style.display='block';fab.style.display='none';renderSegments('lift');});
        document.getElementById('closeDrawer').addEventListener('click',function(){drawer.style.display='none';fab.style.display='';deselectSeg();renderSegments();var ld=document.getElementById('mapLoader');if(ld)ld.remove();});
        document.querySelectorAll('.build-tab').forEach(function(t){
            t.addEventListener('click',function(){
                document.querySelectorAll('.build-tab').forEach(function(b){b.classList.remove('btn-primary');b.classList.add('btn-ghost');});
                t.classList.add('btn-primary');t.classList.remove('btn-ghost');renderSegments(t.dataset.tab);
                deselectSeg();
            });
        });
        document.querySelectorAll('input[name="liftType"],input[name="seats"]').forEach(function(el){el.addEventListener('change',function(){if(el.name==='liftType')updateSeats();else updateCost();});});
        document.getElementById('btnBuild').addEventListener('click',doBuild);
    }

    var activeTab='lift';
    function populateList(type){
        activeTab=type;
        var list=document.getElementById('segmentList');
        var avail=SEGMENTS.filter(function(s){
            if(type==='lift'&&s.type!=='lift') return false;
            if(type==='slope'&&s.type==='lift') return false;
            return BUILT_IDS.indexOf(String(s.id))===-1&&BUILT_IDS.indexOf(Number(s.id))===-1;
        });
        if(!avail.length){list.innerHTML='<p class="text-sm text-base-content/50 text-center py-4">No '+type+'s available</p>';return;}
        list.innerHTML='';
        avail.forEach(function(seg){
            var btn=document.createElement('button');
            btn.className='btn btn-sm btn-ghost justify-start text-left w-full';
            var c=segColor(seg);
            btn.innerHTML='<span style="width:8px;height:8px;border-radius:50%;background:'+c+';flex-shrink:0"></span> <span class="truncate">'+(seg.name||'Unnamed')+'</span><span class="ml-auto text-base-content/40 text-xs">'+Math.round(seg.length_meters||0)+' <?= distanceUnit() ?></span>';
            btn.addEventListener('click',function(){selectSegment(seg);});
            list.appendChild(btn);
        });
    }

    function selectSegment(seg){
        deselectSeg();selectedSegId=seg.id;
        if(segmentLayers[seg.id]) segmentLayers[seg.id].setStyle({weight:6,opacity:1,color:'#fff'});
        document.getElementById('segmentDetail').style.display='block';
        document.getElementById('selSegName').textContent=seg.name||'Unnamed';
        document.getElementById('selSegLength').textContent=Math.round(seg.length_meters||0)+' <?= distanceUnit() ?>';
        var days=(seg.length_meters||0)<200?1:((seg.length_meters||0)<500?2:3);
        document.getElementById('selSegTime').textContent=days+' day'+(days>1?'s':'');
        document.getElementById('liftOptions').style.display=seg.type==='lift'?'block':'none';
        document.getElementById('slopeOptions').style.display=seg.type==='lift'?'none':'block';if(seg.type!=='lift'){var sr=document.querySelector('input[name="slopeType"][value="'+seg.type+'"]');if(sr)sr.checked=true;}
        document.getElementById('buildDrawer').style.display='block';
        document.getElementById('buildFab').style.display='none';
        if(seg.type==='lift')updateSeats();else updateCost();
    }

    function deselectSeg(){
        if(selectedSegId&&segmentLayers[selectedSegId]){
            var s=SEGMENTS.find(function(x){return x.id==selectedSegId;});
            if(s){var b=BUILT_IDS.indexOf(String(s.id))!==-1||BUILT_IDS.indexOf(Number(s.id))!==-1;segmentLayers[selectedSegId].setStyle({color:(b||!buildMode)?segColor(s):'#a855f7',weight:b?4:3,opacity:b?1:0.7});}
        }
        selectedSegId=null;document.getElementById('segmentDetail').style.display='none';
    }

    function updateCost(){
        if(!selectedSegId) return;
        var seg=SEGMENTS.find(function(s){return s.id==selectedSegId;});if(!seg) return;
        var m=parseFloat(seg.length_meters)||0,cost;
        if(seg.type==='lift'){
            var lt=document.querySelector('input[name="liftType"]:checked'),st=document.querySelector('input[name="seats"]:checked');
            cost=m*(COST_PER_METER[lt?lt.value:'chair_fixed']||2000)*(SEAT_MULT[st?parseInt(st.value):4]||1);
        }else{cost=m*500;}
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
            if(res.success){BUILT_IDS.push(String(seg.id));deselectSeg();renderSegments(buildMode);}
            else{alert(res.error||'Build failed');}
        });
    }

    function bindAdmin(){
        document.getElementById('drawLift').addEventListener('click',function(){startDraw('lift');});
        document.getElementById('drawSlope').addEventListener('click',function(){startDraw('slope');});
        document.getElementById('drawFinish').addEventListener('click',finishDraw);
        document.getElementById('drawUndo').addEventListener('click',function(){drawPoints.pop();updateDrawLine();});
        document.getElementById('drawCancel').addEventListener('click',cancelDraw);
        document.getElementById('newSector').addEventListener('click',function(){
            var n=prompt('Sector name:');if(!n) return;
            postJSON('/map/sector/create',{name:n},function(){location.reload();});
        });
        document.getElementById('autoAssign').addEventListener('click',function(){
            postJSON('/map/sector/auto-assign',{},function(r){alert('Assigned '+(r.assigned||0)+' segments');});
        });
        document.querySelectorAll('.toggle-sector').forEach(function(b){
            b.addEventListener('click',function(){postJSON('/map/sector/toggle/'+b.dataset.id,{},function(){location.reload();});});
        });
        document.querySelectorAll('.delete-sector').forEach(function(b){
            b.addEventListener('click',function(){if(!confirm('Delete this sector?')) return;postJSON('/map/sector/delete/'+b.dataset.id,{},function(){location.reload();});});
        });
        map.on('click',function(e){if(!drawingMode) return;drawPoints.push([e.latlng.lat,e.latlng.lng]);updateDrawLine();});
        map.on('dblclick',function(e){if(!drawingMode) return;L.DomEvent.stopPropagation(e);finishDraw();});
    }

    function startDraw(type){
        drawingMode=true;drawType=type;drawPoints=[];
        if(drawLine){map.removeLayer(drawLine);drawLine=null;}
        map.getContainer().style.cursor='crosshair';
        map.doubleClickZoom.disable();
        document.getElementById('drawControls').style.display='block';
        document.getElementById('drawName').value='';document.getElementById('drawSlopeType').style.display=type==='slope'?'block':'none';
        document.getElementById('drawDifficulty').value='';
    }

    function updateDrawLine(){
        if(drawLine) map.removeLayer(drawLine);
        if(drawPoints.length>0){
            drawLine=L.polyline(drawPoints,{color:drawType==='lift'?COLORS.lift:COLORS.green,weight:3,dashArray:'4,4'}).addTo(map);
        }
    }

    function cancelDraw(){
        drawingMode=false;drawPoints=[];
        if(drawLine){map.removeLayer(drawLine);drawLine=null;}
        map.getContainer().style.cursor='';
        map.doubleClickZoom.enable();
        document.getElementById('drawControls').style.display='none';
    }

    function finishDraw(){
        if(drawPoints.length<2){alert('Need at least 2 points');return;}
        drawingMode=false;map.getContainer().style.cursor='';map.doubleClickZoom.enable();
        var nameInput=document.getElementById('drawName').value;var segType=drawType==='slope'?(document.getElementById('drawSlopeType')?document.getElementById('drawSlopeType').value:'downhill'):drawType;var typeCount=SEGMENTS.filter(function(s){return drawType==='lift'?s.type==='lift':s.type!=='lift';}).length+1;var name=nameInput||(drawType==='lift'?'Lift Line '+typeCount:'Slope Path '+typeCount);
        var diff=document.getElementById('drawDifficulty').value;
        var sector=document.getElementById('drawSector')?document.getElementById('drawSector').value:'';
        var totalM=0;
        for(var i=1;i<drawPoints.length;i++){
            var a=map.latLngToContainerPoint(L.latLng(drawPoints[i-1][0],drawPoints[i-1][1]));
            var b=map.latLngToContainerPoint(L.latLng(drawPoints[i][0],drawPoints[i][1]));
            totalM+=a.distanceTo(b);
        }
        totalM=Math.round(totalM*4.38);
        postJSON('/map/segment',{type:(drawType==="slope"?(document.getElementById("drawSlopeType").value||"downhill"):drawType),name:name,points:drawPoints,length_meters:Math.round(totalM),difficulty:diff,sector:sector},function(res){
            if(res.success){var newSeg={id:res.id,type:drawType==="slope"?(document.getElementById("drawSlopeType")?document.getElementById("drawSlopeType").value:"downhill"):drawType,name:document.getElementById("drawName").value||"Unnamed",points:JSON.stringify(drawPoints),length_meters:Math.round(totalM),difficulty:document.getElementById("drawDifficulty").value,sector:document.getElementById("drawSector")?document.getElementById("drawSector").value:""};SEGMENTS.push(newSeg);if(drawLine){map.removeLayer(drawLine);drawLine=null;}renderSegments(buildMode);document.getElementById("drawControls").style.display="none";}else{alert(res.error||"Save failed");}
        });
    }

    function postJSON(url,data,cb){
        data[CSRF_NAME]=CSRF_HASH;
        fetch(url,{method:'POST',headers:{'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},body:JSON.stringify(data)})
        .then(function(r){return r.json();}).then(function(d){cb(d);})
        .catch(function(e){console.error(e);alert('Request failed');});
    }
})();
</script>

<?= $this->endSection() ?>
