<?php
$csrfName        = csrf_token();
$csrfHash        = csrf_hash();
$segmentsJson    = json_encode($segments ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$sectorsJson     = json_encode($sectors ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$builtIdsJson    = json_encode($builtSegmentIds ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$builtItemsJson  = json_encode($builtItems ?? new \stdClass(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$releasedIdsJson = json_encode($releasedSectorIds ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$resortMapsJson  = json_encode($resortMaps ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$userCashVal     = (int) ($userCash ?? 0);
$distUnit        = function_exists('distanceUnit') ? distanceUnit() : 'm';
?>

<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<link rel="stylesheet" href="/css/leaflet.css" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="<?= esc(str_replace('.jpg', '_med.jpg', $mapConfig['image'])) ?>" as="image" fetchpriority="high">

<style>
#map {
    height: 100%;
    width: 100%;
    background: #0f172a;
    position: relative;
    z-index: 0;
    user-select: none;
}
/* Legend styled like the key on a printed trail map */
.map-legend {
    position: absolute; bottom: 14px; left: 14px; z-index: 800;
    background: #f7f5f0; color: #1d232b; border: 1px solid rgba(0,0,0,0.18); border-radius: 6px;
    padding: 9px 12px 10px; font-size: 12px; display: flex; flex-direction: column; gap: 5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}
.map-legend-title { font-family: var(--sign); font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 1px; }
.map-legend-item { display: flex; align-items: center; gap: 8px; }
.map-legend-item .legend-sign { width: 26px; display: inline-flex; justify-content: center; }
.map-legend-item span.legend-swatch { width: 20px; height: 4px; border-radius: 2px; display: inline-block; }
.map-legend .tsign.black { box-shadow: none; }
.build-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 900;
}
/* Ensure tutorial widget does not cover the build button on the map page */
#tutorialWidget {
    bottom: 5.5rem !important;
}
.leaflet-interactive {
    cursor: pointer !important;
}
#map.drawing-cursor,
#map.drawing-cursor .leaflet-interactive {
    cursor: crosshair !important;
}

/* Admin Floating Top Editor Toolbar - centered via flexbox wrapper & margin auto (immune to transform overrides) */
.admin-editor-toolbar-wrap {
    position: absolute;
    top: 12px;
    left: 0;
    right: 0;
    z-index: 850;
    display: flex;
    justify-content: center;
    pointer-events: none;
    padding: 0 12px;
}
@media (min-width: 900px) {
    .admin-editor-toolbar-wrap.has-open-drawer {
        right: 406px;
    }
}
.admin-editor-toolbar {
    pointer-events: auto;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    background: rgba(15, 23, 42, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.16);
    border-radius: 10px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.45);
    padding: 7px 12px;
    max-width: 96vw;
    width: fit-content;
}
.admin-editor-toolbar .select,
.admin-editor-toolbar .input,
#resortSelect,
#buildDiffFilter,
#inspectorFilterType {
    width: auto !important;
    display: inline-flex !important;
}

/* Custom Vertex Handle */
.custom-vertex-wrap {
    background: transparent;
    border: none;
}
.custom-vertex-handle {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #ffffff;
    border: 3px solid #f59e0b;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.85);
    cursor: grab !important;
    transition: transform 0.1s ease, box-shadow 0.1s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 9px;
    font-weight: 800;
    color: #0f172a;
}
.custom-vertex-handle.is-start {
    width: 20px;
    height: 20px;
    background: #22c55e;
    border: 2.5px solid #ffffff;
    color: #ffffff;
}
.custom-vertex-handle.is-end {
    width: 20px;
    height: 20px;
    background: #ef4444;
    border: 2.5px solid #ffffff;
    color: #ffffff;
}
.custom-vertex-handle.is-selected {
    box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.75), 0 2px 8px rgba(0, 0, 0, 0.85);
    transform: scale(1.25);
}
.custom-vertex-handle:hover {
    transform: scale(1.3);
}
.custom-vertex-handle:active {
    cursor: grabbing !important;
}

/* Custom Midpoint Handle */
.custom-midpoint-wrap {
    background: transparent;
    border: none;
}
.custom-midpoint-handle {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #ffffff;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.65);
    cursor: copy !important;
    opacity: 0.85;
    transition: all 0.12s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 8px;
    font-weight: 900;
}
.custom-midpoint-handle:hover {
    transform: scale(1.45);
    opacity: 1;
}

/* Snap Indicator Ring */
.snap-indicator-wrap {
    background: transparent;
    border: none;
    pointer-events: none;
}
.snap-indicator-ring {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2.5px solid #38bdf8;
    background: rgba(56, 189, 248, 0.22);
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.9);
    animation: pulseSnap 1s infinite alternate;
}
@keyframes pulseSnap {
    from { transform: scale(0.9); opacity: 0.8; }
    to { transform: scale(1.15); opacity: 1; }
}

/* Admin Popover */
.admin-leaflet-popup .leaflet-popup-content-wrapper {
    background: #1e293b !important;
    color: #f1f5f9 !important;
    border: 1px solid rgba(255, 255, 255, 0.15) !important;
    border-radius: 12px !important;
    box-shadow: 0 14px 32px rgba(0, 0, 0, 0.65) !important;
    padding: 4px;
}
.admin-leaflet-popup .leaflet-popup-tip {
    background: #1e293b !important;
}

/* Side drawers: solid panel, no glass or glow */
/* The tutorial card is fixed bottom-right with a higher z-index than the drawer, so on
   laptop-height screens it sat on top of the Construct button and swallowed the click. */
body.map-drawer-open #tutorialWidgetContainer { right: calc(380px + 2rem) !important; }
@media (max-width: 820px) { body.map-drawer-open #tutorialWidgetContainer { display: none !important; } }
/* Keep cost + Construct visible without scrolling the drawer */
.build-footer { position: sticky; bottom: -14px; margin: 0 -14px -14px; padding: 12px 14px 14px; background: #151b23; z-index: 2; }
#btnBuild:disabled { opacity: 0.45; cursor: not-allowed; }
.map-side-drawer {
    position: absolute; top: 12px; right: 12px; bottom: 12px;
    width: 380px; max-width: calc(100vw - 24px);
    background: #151b23; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;
    z-index: 950; display: flex; flex-direction: column; overflow: hidden;
    box-shadow: 0 8px 24px rgba(0,0,0,0.45); color: #e6e9ee;
}
.drawer-header {
    padding: 14px 14px 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; flex-shrink: 0;
}
.drawer-header h2 { font-family: var(--sign); font-size: 18px !important; font-weight: 600 !important; line-height: 1.2 !important; letter-spacing: -0.02em; color: #fff !important; }
.drawer-header-icon { display: none; }
.drawer-cash-pill { color: #e6e9ee; font-weight: 600; font-size: 12px; font-variant-numeric: tabular-nums; }
.drawer-close-btn {
    width: 30px; height: 30px; border-radius: 6px; background: transparent; border: 0; color: #9aa4b2;
    display: flex; align-items: center; justify-content: center; cursor: pointer; flex-shrink: 0;
}
.drawer-close-btn:hover { background: rgba(255,255,255,0.08); color: #fff; }

/* Tabs: underline, not a glowing pill */
.drawer-segmented-tabs {
    display: grid; grid-template-columns: 1fr 1fr;
    margin: -14px -14px 14px; padding: 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08);
}
.drawer-seg-tab {
    display: flex; align-items: center; justify-content: center; gap: 7px;
    padding: 11px 8px 10px; margin-bottom: -1px; font-size: 13px; font-weight: 600; color: #9aa4b2;
    background: none; border: 0; border-bottom: 2px solid transparent; cursor: pointer;
}
.drawer-seg-tab:hover { color: #e6e9ee; }
.drawer-seg-tab.is-active { color: #fff; border-bottom-color: var(--color-primary); }
.drawer-seg-tab .tab-count-pill { font-size: 11px; font-weight: 500; color: #7d8794; font-variant-numeric: tabular-nums; }

/* Search & filters */
.drawer-search-wrap { position: relative; flex: 1; }
.drawer-search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #6b7685; font-size: 12px; pointer-events: none; }
.drawer-search-input {
    width: 100%; height: 34px; padding: 0 10px 0 30px; background: #0f141a;
    border: 1px solid rgba(255,255,255,0.12); border-radius: 6px; color: #f1f3f5; font-size: 13px; outline: none;
}
.drawer-search-input:focus, .drawer-select:focus { border-color: var(--color-primary); }
.drawer-select {
    height: 34px; padding: 0 26px 0 10px; background: #0f141a; border: 1px solid rgba(255,255,255,0.12);
    border-radius: 6px; color: #e6e9ee; font-size: 12px; outline: none; cursor: pointer;
}

/* Run & lift rows */
.build-item-card {
    display: flex; align-items: center; gap: 12px; width: 100%; padding: 10px 8px; margin: 0;
    background: none; border: 0; border-top: 1px solid rgba(255,255,255,0.06); border-radius: 0;
    text-align: left; cursor: pointer; color: inherit;
}
.build-item-card:hover { background: rgba(255,255,255,0.04); }
.build-item-card.is-selected { background: color-mix(in oklab, var(--color-primary) 16%, transparent); box-shadow: inset 3px 0 0 var(--color-primary); }
.build-item-emblem { width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.build-item-info { flex: 1; min-width: 0; }
.build-item-title-row { display: flex; align-items: baseline; gap: 8px; margin-bottom: 2px; }
.build-item-name { font-size: 13.5px; font-weight: 600; color: #f1f3f5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.build-diff-pill { font-size: 11px; font-weight: 500; color: #8b95a3; flex-shrink: 0; }
.build-item-meta { display: flex; align-items: center; gap: 6px; font-size: 11.5px; color: #8b95a3; font-variant-numeric: tabular-nums; }
.build-item-price-col { text-align: right; flex-shrink: 0; }
.build-item-price { font-family: var(--sign); font-size: 14px; font-weight: 600; line-height: 1.2; font-variant-numeric: tabular-nums; }
.build-item-cta { font-size: 11px; color: #8b95a3; margin-top: 1px; }

/* Trail signs, as printed on real trail maps */
.tsign { display: inline-block; width: 12px; height: 12px; flex-shrink: 0; }
.tsign.green { border-radius: 50%; background: #23a55a; }
.tsign.blue { background: #2f6fd6; }
.tsign.black { width: 10px; height: 10px; background: #0b0d10; transform: rotate(45deg); margin: 0 1px; box-shadow: 0 0 0 1.5px #cfd4da; }
.tsign-pair { display: inline-flex; gap: 3px; }

/* Selected segment */
.blueprint-card {
    background: #1b222c; border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
    padding: 14px; margin-bottom: 14px;
}
.blueprint-stat-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
    padding: 10px 0; margin-bottom: 12px;
    border-top: 1px solid rgba(255,255,255,0.08); border-bottom: 1px solid rgba(255,255,255,0.08);
}
.blueprint-stat-cell { display: flex; flex-direction: column; }
.blueprint-stat-label { font-size: 11px; color: #8b95a3; }
.blueprint-stat-val { font-size: 13px; font-weight: 600; color: #f1f3f5; font-variant-numeric: tabular-nums; margin-top: 1px; }

.build-option-card {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 11px; background: transparent; border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px; cursor: pointer; font-size: 12.5px; color: #cfd4da;
}
.build-option-card:hover { border-color: rgba(255,255,255,0.25); }
.build-option-card.is-checked { background: color-mix(in oklab, var(--color-primary) 14%, transparent); border-color: var(--color-primary); color: #fff; }
.build-option-card.is-checked .fa-circle-dot { color: var(--color-primary) !important; }
.seat-pill-btn {
    flex: 1; text-align: center; padding: 7px 6px; background: transparent;
    border: 1px solid rgba(255,255,255,0.1); border-radius: 6px;
    font-size: 12.5px; font-weight: 600; color: #9aa4b2; cursor: pointer; font-variant-numeric: tabular-nums;
}
.seat-pill-btn:hover { border-color: rgba(255,255,255,0.25); color: #f1f3f5; }
.seat-pill-btn.is-checked { background: color-mix(in oklab, var(--color-primary) 14%, transparent); border-color: var(--color-primary); color: #fff; }

/* Top bar */
.map-title { font-family: var(--sign); font-size: 20px; font-weight: 700; line-height: 1.15; letter-spacing: -0.02em; margin: 0; }
.map-title span { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 12px; font-weight: 500; margin-left: 6px; color: color-mix(in oklab, var(--color-base-content) 55%, transparent); }
.map-counts { display: flex; gap: 12px; margin-top: 2px; font-size: 12px; color: color-mix(in oklab, var(--color-base-content) 60%, transparent); }
.map-counts b { color: var(--color-base-content); font-weight: 600; font-variant-numeric: tabular-nums; }
</style>

<!-- Top Header Bar -->
<div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2 border-b border-base-300 bg-base-100">
    <div class="flex items-center gap-2.5">
        <a href="/resort" class="btn btn-ghost btn-sm btn-square" title="Back to resort"><i class="fa-solid fa-arrow-left"></i></a>
        <div>
            <h1 class="map-title"><?= esc($mapConfig['name']) ?><span>Trail map</span></h1>
            <div class="map-counts">
                <span><b id="slopeCountEl"><?= $slopeCount ?></b> slopes</span>
                <span><b id="liftCountEl"><?= $liftCount ?></b> lifts</span>
                <span><b id="segmentCountEl"><?= $segmentCount ?></b> segments</span>
            </div>
        </div>
    </div>

    <!-- Filter Pills & Map Actions -->
    <div class="flex items-center gap-1.5 flex-wrap">
        <div class="join bg-base-200/80 p-0.5 rounded-lg border border-base-300 hidden md:flex">
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-primary" data-filter="all">All</button>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="lift">Lifts</button>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="slope">Slopes</button>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="built">Built</button>
            <?php if ($isAdmin): ?>
            <button type="button" class="btn btn-xs join-item map-filter-btn btn-ghost" data-filter="unassigned" title="Segments with Sector 0">Unassigned</button>
            <?php endif; ?>
        </div>

        <button type="button" id="btnFitMap" class="btn btn-ghost btn-sm btn-square border border-base-300" title="Reset Map Zoom (F)">
            <i class="fa-solid fa-expand"></i>
        </button>

        <?php if ($isAdmin): ?>
        <button type="button" id="btnOpenInspector" class="btn btn-outline btn-sm gap-1.5" title="Open Segment & Sector Manager">
            <i class="fa-solid fa-list-check text-info"></i> <span class="hidden sm:inline">Segments & Sectors</span>
        </button>
        <?php endif; ?>

        <button type="button" id="headerBuildBtn" class="btn btn-primary btn-sm gap-1.5 shadow-sm">
            <i class="fa-solid fa-hammer"></i> Build
        </button>

        <?php if ($isAdmin): ?>
        <select id="resortSelect" class="select select-sm select-bordered font-medium">
            <?php foreach ($resortMaps as $key => $rm): ?>
            <option value="<?= $key ?>" <?= $key === $resortMap ? 'selected' : '' ?>><?= esc($rm['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
</div>

<!-- Main Map Viewport -->
<div class="relative w-full overflow-hidden" style="height:calc(100vh - 122px)">
    <div id="map" data-image="<?= esc($mapConfig['image']) ?>"></div>

    <!-- Initial Loader -->
    <div id="mapLoader" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:100;text-align:center;pointer-events:none">
        <span class="loading loading-spinner loading-lg text-primary"></span>
        <p class="text-sm text-base-content/60 mt-2">Loading trail map...</p>
    </div>

    <!-- Subtle HD Resolution Indicator -->
    <div id="hdBadge" class="badge badge-neutral badge-sm gap-1.5 py-2.5 px-3 shadow-lg" style="display:none;position:absolute;bottom:14px;right:84px;z-index:800;background:rgba(15,23,42,0.88);border-color:rgba(255,255,255,0.12)">
        <span class="loading loading-spinner loading-xs text-info"></span>
        <span class="text-[11px]">Loading HD terrain...</span>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Admin Floating Top Editor Toolbar -->
    <div class="admin-editor-toolbar-wrap">
    <div id="adminEditorToolbar" class="admin-editor-toolbar">
        <!-- Idle View -->
        <div id="toolIdleView" class="flex items-center gap-2 flex-wrap">
            <span class="badge badge-primary font-bold text-xs gap-1.5 py-2.5 px-3">
                <i class="fa-solid fa-compass-drafting text-[11px]"></i> Map Studio
            </span>

            <div class="flex items-center gap-1.5 bg-base-300/70 rounded-lg px-2 py-0.5 border border-white/10">
                <span class="text-[11px] font-semibold text-base-content/70">Sector:</span>
                <select id="toolActiveSector" class="select select-xs select-bordered bg-base-200 text-xs py-0 h-6 min-h-0">
                    <option value="0">Default (0)</option>
                    <?php foreach ($sectors as $sec): ?>
                    <option value="<?= (int) $sec['id'] ?>"><?= esc($sec['name']) ?> (#<?= (int) $sec['id'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <button type="button" id="btnNewLift" class="btn btn-xs btn-warning font-bold gap-1.5 shadow-sm" title="Draw New Lift (Shortcut: L)">
                <i class="fa-solid fa-elevator"></i> + New Lift
            </button>
            <button type="button" id="btnNewSlope" class="btn btn-xs btn-success font-bold gap-1.5 shadow-sm" title="Draw New Slope (Shortcut: S)">
                <i class="fa-solid fa-person-skiing"></i> + New Slope
            </button>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <button type="button" id="btnToggleSnap" class="btn btn-xs btn-info btn-outline gap-1 font-semibold" title="Magnetic Endpoint Snapping (Hold Alt to bypass)">
                <i class="fa-solid fa-magnet"></i> Snap: ON
            </button>
            <button type="button" id="btnToggleSectors" class="btn btn-xs btn-ghost border border-white/10 gap-1" title="Show/Hide Sector Boundaries on Map">
                <i class="fa-solid fa-draw-polygon text-warning"></i> Sectors
            </button>

            <span class="text-[11px] text-base-content/50 hidden xl:inline ml-1">
                <i class="fa-solid fa-keyboard mr-1"></i><b>L</b> Lift • <b>S</b> Slope • Click any line to edit
            </span>
        </div>

        <!-- Active Draw / Edit View -->
        <div id="toolActiveView" class="items-center gap-2 flex-wrap" style="display:none">
            <span id="toolModeBadge" class="badge badge-warning text-xs font-bold gap-1.5 py-2.5 px-2.5">
                <i class="fa-solid fa-pen-nib text-[10px]"></i> <span id="toolModeText">New Lift</span>
            </span>

            <!-- Live HUD Metrics -->
            <div class="flex items-center gap-2 bg-base-300/90 px-2.5 py-1 rounded-lg text-xs font-mono border border-primary/25 shadow-inner">
                <span class="flex items-center gap-1 text-primary font-bold" title="Calculated Segment Length">
                    <i class="fa-solid fa-ruler text-[11px]"></i>
                    <span id="toolLiveLength">0</span> <?= esc($distUnit) ?>
                </span>
                <span class="opacity-30">|</span>
                <span class="flex items-center gap-1 text-info" title="Number of Vertices">
                    <i class="fa-solid fa-location-dot text-[11px]"></i>
                    <span id="toolPointCount">0</span> pts
                </span>
            </div>

            <!-- Inline Segment Name -->
            <input type="text" id="toolSegName" class="input input-xs input-bordered w-32 md:w-40 text-xs font-semibold" placeholder="Segment name">

            <!-- Inline Sector -->
            <select id="toolSegSector" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" title="Assigned Sector">
                <option value="0">Sector: Default (0)</option>
                <?php foreach ($sectors as $sec): ?>
                <option value="<?= (int) $sec['id'] ?>"><?= esc($sec['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Inline Slope Type (shown for slopes) -->
            <select id="toolSlopeType" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" style="display:none" title="Slope Discipline">
                <option value="downhill">Downhill</option>
                <option value="crosscountry">Cross-Country</option>
                <option value="snowpark">Snow Park</option>
                <option value="luge">Luge</option>
            </select>

            <!-- Inline Difficulty (shown for slopes) -->
            <select id="toolSegDiff" class="select select-xs select-bordered text-xs py-0 h-6 min-h-0" style="display:none" title="Slope Difficulty">
                <option value="green">🟢 Green (Easy)</option>
                <option value="blue">🔵 Blue (Intermediate)</option>
                <option value="black">⚫ Black (Advanced)</option>
                <option value="double_black">🔴 Double Black (Expert)</option>
            </select>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <!-- Path Manipulation Tools -->
            <button type="button" id="toolBtnAppendToggle" class="btn btn-xs btn-info btn-outline gap-1" style="display:none" title="Toggle whether clicking the map appends new points to the end of this path">
                <i class="fa-solid fa-plus"></i> <span id="toolAppendText">Append: OFF</span>
            </button>
            <button type="button" id="toolBtnReverse" class="btn btn-xs btn-ghost border border-white/15 gap-1" title="Reverse Start (S) and End (E) direction">
                <i class="fa-solid fa-right-left"></i> Reverse
            </button>
            <button type="button" id="toolBtnUndo" class="btn btn-xs btn-warning btn-outline gap-1" title="Undo last point (Z)">
                <i class="fa-solid fa-rotate-left"></i> Undo
            </button>

            <div class="h-4 w-px bg-white/15 mx-0.5 hidden sm:block"></div>

            <!-- Save / Save+Next / Cancel -->
            <button type="button" id="toolBtnSave" class="btn btn-xs btn-success font-bold gap-1 shadow-sm" title="Save Segment (Enter)">
                <i class="fa-solid fa-check"></i> Save
            </button>
            <button type="button" id="toolBtnSaveNext" class="btn btn-xs btn-primary font-bold gap-1 shadow-sm" title="Save & Immediately Draw Next (Shift+Enter)">
                <i class="fa-solid fa-forward-step"></i> Save + Next
            </button>
            <button type="button" id="toolBtnCancel" class="btn btn-xs btn-ghost text-error gap-1" title="Cancel (Esc)">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
        </div>

        <!-- Helper Hint Sub-bar (shown while active) -->
        <div id="toolHelperHint" class="text-[11px] text-base-content/60 pt-1.5 mt-1.5 border-t border-white/10 flex items-center justify-between gap-3" style="display:none">
            <span id="toolHintText">
                <i class="fa-solid fa-circle-info text-info mr-1"></i>
                <b>Click map</b> to add points • <b>Drag white/colored dots</b> to move • <b>Click or drag small midpoints</b> to add bends • <b>Right-click or Alt+click</b> a point to delete it
            </span>
            <span id="snapStatusPill" class="badge badge-xs badge-info gap-1 font-mono" style="display:none"></span>
        </div>
    </div>
    </div>
    <?php endif; ?>

    <!-- Toast Notification (for both Admin & Player actions) -->
    <div id="adminToast" class="toast toast-top toast-end z-[1100] pointer-events-none transition-all duration-300 opacity-0 translate-y-[-10px]">
        <div id="adminToastAlert" class="alert alert-success py-2 px-3.5 text-xs shadow-xl font-semibold">
            <span id="adminToastMsg">Success</span>
        </div>
    </div>

    <!-- Map Legend -->
    <div class="map-legend" id="mapLegend">
        <div class="map-legend-title">Trail key</div>
        <div class="map-legend-item"><span class="legend-sign"><i class="tsign green"></i></span><span class="legend-swatch" style="background:#22c55e"></span>Easiest</div>
        <div class="map-legend-item"><span class="legend-sign"><i class="tsign blue"></i></span><span class="legend-swatch" style="background:#3b82f6"></span>More difficult</div>
        <div class="map-legend-item"><span class="legend-sign"><i class="tsign black"></i></span><span class="legend-swatch" style="background:#1e293b"></span>Most difficult</div>
        <div class="map-legend-item"><span class="legend-sign"><span class="tsign-pair"><i class="tsign black"></i><i class="tsign black"></i></span></span><span class="legend-swatch" style="background:#dc2626"></span>Experts only</div>
        <div class="map-legend-item"><span class="legend-sign"><i class="fa-solid fa-elevator" style="font-size:11px;color:#b9770e"></i></span><span class="legend-swatch" style="background:#f59e0b"></span>Lift</div>
        <div class="map-legend-item"><span class="legend-sign"></span><span class="legend-swatch" style="background:#a855f7"></span>Available to build</div>
    </div>

    <!-- Player Build Drawer (Docked Floating Panel inside Map Viewport) -->
    <div id="buildDrawer" class="map-side-drawer" style="display:none">
        <div class="drawer-header">
            <div class="flex items-center gap-3 min-w-0">
                <div class="drawer-header-icon">
                    <i class="fa-solid fa-hammer"></i>
                </div>
                <div class="min-w-0">
                    <h2 style="font-size:15px;font-weight:800;color:#f8fafc;line-height:1.2">Build</h2>
                    <div class="flex items-center gap-1.5 mt-1">
                        <span style="font-size:12px;color:#8b95a3">Cash</span>
                        <span class="drawer-cash-pill" id="drawerCashBalance">$<?= number_format($userCashVal) ?></span>
                    </div>
                </div>
            </div>
            <button type="button" id="closeDrawer" class="drawer-close-btn" title="Close Build Menu (Esc)">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div id="buildDrawerBody" class="p-3.5 flex-1 overflow-y-auto">
            <!-- Segmented Tabs -->
            <div class="drawer-segmented-tabs">
                <button type="button" class="build-tab drawer-seg-tab is-active" data-tab="lift">
                    <i class="fa-solid fa-elevator" style="color:#fbbf24"></i>
                    <span>Lifts</span>
                    <span class="tab-count-pill" id="tabCountLift">0</span>
                </button>
                <button type="button" class="build-tab drawer-seg-tab" data-tab="slope">
                    <i class="fa-solid fa-person-skiing" style="color:#4ade80"></i>
                    <span>Slopes</span>
                    <span class="tab-count-pill" id="tabCountSlope">0</span>
                </button>
            </div>

            <!-- Selected Segment Build Configuration Blueprint Card -->
            <div id="segmentDetail" style="display:none">
                <div class="blueprint-card">
                    <div class="flex items-start justify-between gap-2 mb-2.5">
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 mb-1">
                                <span style="font-size:12px;font-weight:600;color:#38bdf8">Selected</span>
                                <span id="selSegBadge" class="build-diff-pill"></span>
                            </div>
                            <p style="font-size:15px;font-weight:800;color:#ffffff" class="truncate" id="selSegName">-</p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" id="btnZoomSelectedSeg" class="drawer-close-btn" style="width:28px;height:28px;font-size:11px" title="Center map on this run">
                                <i class="fa-solid fa-location-crosshairs"></i>
                            </button>
                            <button type="button" id="btnDeselectSeg" class="drawer-close-btn" style="width:28px;height:28px;font-size:11px" title="Deselect segment">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>

                    <div class="blueprint-stat-grid">
                        <div class="blueprint-stat-cell">
                            <span class="blueprint-stat-label">Length</span>
                            <span class="blueprint-stat-val" id="selSegLength">-</span>
                        </div>
                        <div class="blueprint-stat-cell">
                            <span class="blueprint-stat-label">Build Time</span>
                            <span class="blueprint-stat-val" id="selSegTime">-</span>
                        </div>
                        <div class="blueprint-stat-cell">
                            <span class="blueprint-stat-label">Sector</span>
                            <span class="blueprint-stat-val truncate" id="selSegSector" style="font-family:inherit">-</span>
                        </div>
                    </div>

                    <!-- Lift Configuration -->
                    <div id="liftOptions">
                        <div class="flex items-center justify-between mb-1.5">
                            <span style="font-size:11px;font-weight:700;color:#cbd5e1;">Lift System</span>
                            <span style="font-size:10px;color:#64748b">Hourly Capacity</span>
                        </div>
                        <div class="flex flex-col gap-1.5 mb-3" id="liftTypeGrid">
                            <label class="build-option-card" data-lift-card="button">
                                <input type="radio" name="liftType" value="button" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Button / Surface Lift</div>
                                        <div style="font-size:10px;color:#64748b">$80 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:var(--sign);font-size:14px;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700;color:#fbbf24">1,000/hr</span>
                            </label>
                            <label class="build-option-card is-checked" data-lift-card="chair_fixed">
                                <input type="radio" name="liftType" value="chair_fixed" class="hidden" checked>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#38bdf8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Fixed-Grip Chairlift</div>
                                        <div style="font-size:10px;color:#64748b">$150 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:var(--sign);font-size:14px;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700;color:#fbbf24">2,400/hr</span>
                            </label>
                            <label class="build-option-card" data-lift-card="chair_detach">
                                <input type="radio" name="liftType" value="chair_detach" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Detachable High-Speed</div>
                                        <div style="font-size:10px;color:#64748b">$250 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:var(--sign);font-size:14px;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700;color:#fbbf24">3,400/hr</span>
                            </label>
                            <label class="build-option-card" data-lift-card="gondola">
                                <input type="radio" name="liftType" value="gondola" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">8-10p Cabin Gondola</div>
                                        <div style="font-size:10px;color:#64748b">$400 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:var(--sign);font-size:14px;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700;color:#fbbf24">3,500/hr</span>
                            </label>
                            <label class="build-option-card" data-lift-card="cable_car">
                                <input type="radio" name="liftType" value="cable_car" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-circle-dot text-xs" style="color:#94a3b8"></i>
                                    <div>
                                        <div style="font-weight:700;color:#f8fafc">Aerial Tram / Cable Car</div>
                                        <div style="font-size:10px;color:#64748b">$600 / m base rate</div>
                                    </div>
                                </div>
                                <span style="font-family:var(--sign);font-size:14px;font-variant-numeric:tabular-nums;font-size:11px;font-weight:700;color:#fbbf24">4,000/hr</span>
                            </label>
                        </div>

                        <p style="font-size:11px;font-weight:700;color:#cbd5e1;margin-bottom:6px">Carrier Size (Seats / Cabin)</p>
                        <div class="flex gap-1.5 mb-3" id="seatGrid"></div>
                    </div>

                    <!-- Slope Configuration -->
                    <div id="slopeOptions">
                        <p style="font-size:11px;font-weight:700;color:#cbd5e1;margin-bottom:6px">Trail Discipline</p>
                        <div class="grid grid-cols-2 gap-1.5 mb-3" id="slopeTypeGrid">
                            <label class="build-option-card is-checked" data-slope-card="downhill">
                                <input type="radio" name="slopeType" value="downhill" class="hidden" checked>
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-person-skiing text-success"></i>
                                    <span style="font-weight:700">Downhill</span>
                                </div>
                            </label>
                            <label class="build-option-card" data-slope-card="crosscountry">
                                <input type="radio" name="slopeType" value="crosscountry" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-person-skiing-nordic text-info"></i>
                                    <span style="font-weight:700">Cross-Country</span>
                                </div>
                            </label>
                            <label class="build-option-card" data-slope-card="snowpark">
                                <input type="radio" name="slopeType" value="snowpark" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-person-snowboarding" style="color:#c084fc"></i>
                                    <span style="font-weight:700">Snow Park</span>
                                </div>
                            </label>
                            <label class="build-option-card" data-slope-card="luge">
                                <input type="radio" name="slopeType" value="luge" class="hidden">
                                <div class="flex items-center gap-2">
                                    <i class="fa-solid fa-sleigh" style="color:#f472b6"></i>
                                    <span style="font-weight:700">Alpine Luge</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="build-footer flex items-center justify-between pt-3" style="border-top:1px solid rgba(255,255,255,0.08)">
                        <div>
                            <p style="font-size:10px;color:#94a3b8;font-weight:700">Construction Cost</p>
                            <p style="font-size:20px;font-weight:600;letter-spacing:-0.02em;font-family:var(--sign);font-variant-numeric:tabular-nums;line-height:1.2" id="selSegCost">-</p>
                            <p style="font-size:10px;color:#64748b;font-family:monospace" id="selSegAfterCash"></p>
                            <p style="font-size:10px;color:#94a3b8;margin-top:2px" id="selSegRunInfo" hidden></p>
                        </div>
                        <button type="button" id="btnBuild" class="btn btn-success btn-sm gap-1.5 font-bold shadow-lg px-4" style="height:36px">
                            <i class="fa-solid fa-hammer"></i> Construct
                        </button>
                    </div>
                </div>
            </div>

            <!-- Search, Grade Filter & Sort Bar -->
            <div class="flex flex-col gap-2 mb-3">
                <div class="drawer-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="buildSearchInput" class="drawer-search-input" placeholder="Search by name">
                </div>
                <div class="flex items-center gap-1.5">
                    <select id="buildDiffFilter" class="drawer-select flex-1" style="display:none">
                        <option value="">All Grades</option>
                        <option value="green">🟢 Green (Easy)</option>
                        <option value="blue">🟦 Blue (Intermediate)</option>
                        <option value="black">◆ Black (Advanced)</option>
                        <option value="double_black">◆◆ Double Black</option>
                    </select>
                    <select id="buildSortSelect" class="drawer-select flex-1">
                        <option value="cost_asc">Sort: Cheapest First</option>
                        <option value="cost_desc">Sort: Expensive First</option>
                        <option value="len_desc">Sort: Longest First</option>
                        <option value="name_asc">Sort: Name (A–Z)</option>
                    </select>
                </div>
            </div>

            <div id="segmentList" class="flex flex-col">
                <p class="text-sm text-base-content/50 text-center py-4">Select a purple line to build</p>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Admin Segment & Sector Inspector Drawer (Docked Floating Panel inside Map Viewport) -->
    <div id="adminInspectorDrawer" class="map-side-drawer" style="display:none">
        <div class="drawer-header">
            <div class="flex items-center gap-3 min-w-0">
                <div class="drawer-header-icon">
                    <i class="fa-solid fa-compass-drafting"></i>
                </div>
                <div class="min-w-0">
                    <h2 style="font-size:15px;font-weight:800;color:#f8fafc;line-height:1.2">Map inspector</h2>
                    <p style="font-size:12px;color:#8b95a3;margin-top:3px">Segments and sectors</p>
                </div>
            </div>
            <button type="button" id="closeInspectorBtn" class="drawer-close-btn" title="Close Inspector">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="p-3.5 flex-1 overflow-y-auto space-y-5">
            <!-- Sectors Management -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <p style="font-size:11px;font-weight:800;color:#94a3b8;">Mountain Sectors</p>
                    <div class="flex gap-1">
                        <button type="button" id="autoAssign" class="btn btn-xs btn-outline btn-info gap-1" title="Auto-assign segments inside sector boundaries">
                            <i class="fa-solid fa-object-group"></i> Auto-assign
                        </button>
                        <button type="button" id="newSector" class="btn btn-xs btn-primary gap-1">
                            <i class="fa-solid fa-plus"></i> Add Sector
                        </button>
                    </div>
                </div>

                <!-- Inline New Sector Form (hidden by default) -->
                <div id="newSectorForm" class="p-2.5 rounded-xl bg-base-200 border border-white/10 mb-2 space-y-2" style="display:none">
                    <div class="flex gap-2">
                        <input type="text" id="newSectorName" class="drawer-search-input" style="padding-left:10px" placeholder="Sector name (e.g. Sector 3)">
                        <input type="color" id="newSectorColor" value="#3b82f6" class="w-9 h-8 rounded cursor-pointer bg-transparent border-0">
                    </div>
                    <div class="flex justify-end gap-1.5">
                        <button type="button" id="cancelNewSectorBtn" class="btn btn-xs btn-ghost">Cancel</button>
                        <button type="button" id="saveNewSectorBtn" class="btn btn-xs btn-success">Create</button>
                    </div>
                </div>

                <div id="sectorList" class="flex flex-col gap-1.5"></div>
            </div>

            <!-- Segments Directory -->
            <div style="border-top:1px solid rgba(255,255,255,0.1);padding-top:14px">
                <div class="flex items-center justify-between mb-2.5">
                    <p style="font-size:11px;font-weight:800;color:#94a3b8;">All Segments (<span id="inspectorSegCount"><?= count($segments) ?></span>)</p>
                    <div class="flex gap-1">
                        <button type="button" class="btn btn-xs btn-warning gap-1 font-bold" id="inspectorNewLiftBtn"><i class="fa-solid fa-plus"></i> Lift</button>
                        <button type="button" class="btn btn-xs btn-success gap-1 font-bold" id="inspectorNewSlopeBtn"><i class="fa-solid fa-plus"></i> Slope</button>
                    </div>
                </div>

                <div class="flex gap-1.5 mb-2.5">
                    <div class="drawer-search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="inspectorSearch" class="drawer-search-input" placeholder="Filter by name...">
                    </div>
                    <select id="inspectorFilterType" class="drawer-select">
                        <option value="all">All Types</option>
                        <option value="lift">Lifts Only</option>
                        <option value="slope">Slopes Only</option>
                        <option value="unassigned">Unassigned</option>
                    </select>
                </div>

                <div id="inspectorSegmentList" class="flex flex-col gap-1.5"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Floating Build FAB -->
<button type="button" class="build-fab btn btn-primary btn-circle btn-lg shadow-xl" id="buildFab" title="Open Build Menu">
    <i class="fa-solid fa-hammer text-xl"></i>
</button>

<script data-cfasync="false" src="/js/leaflet.js"></script>

<script data-cfasync="false">
(function(){
    'use strict';

    var SEGMENTS     = <?= $segmentsJson ?>;
    var SECTORS      = <?= $sectorsJson ?>;
    var BUILT_IDS    = <?= $builtIdsJson ?>;
    var BUILT_ITEMS  = <?= $builtItemsJson ?>;
    var RELEASED_IDS = <?= $releasedIdsJson ?>;
    var IS_ADMIN     = <?= $isAdmin ? 'true' : 'false' ?>;
    var USER_CASH    = <?= $userCashVal ?>;
    var DIST_UNIT    = <?= json_encode($distUnit) ?>;
    var CSRF_NAME    = <?= json_encode($csrfName) ?>;
    var CSRF_HASH    = <?= json_encode($csrfHash) ?>;
    var MAP_IMAGE    = document.getElementById('map').dataset.image;
    var MAP_HEIGHT   = <?= (int) $mapConfig['height'] ?>;
    var MAP_WIDTH    = <?= (int) $mapConfig['width'] ?>;

    var COLORS = {
        lift: '#f59e0b',
        green: '#22c55e',
        blue: '#3b82f6',
        black: '#1e293b',
        double_black: '#dc2626',
        snowpark: '#a855f7',
        terrain_park: '#a855f7',
        crosscountry: '#14b8a6',
        luge: '#ec4899',
        'default': '#94a3b8'
    };
    var COST_PER_METER = {button:80, chair_fixed:150, chair_detach:250, gondola:400, cable_car:600};
    var SEAT_MULT      = {1:0.7, 2:1.0, 3:1.15, 4:1.3, 6:1.6, 8:2.0, 10:2.5, 20:4.0, 30:5.0};
    var SEAT_OPTIONS   = {button:[1,2], chair_fixed:[2,3,4], chair_detach:[4,6,8], gondola:[6,8,10], cable_car:[20,30]};
    // Economy v2: what a lift carries and what lifts/slopes cost to run (App\Libraries\Economy)
    var ECONOMY_V2 = <?= json_encode(featureEnabled('economy_v2') ? [
        'capacity' => \App\Libraries\Economy::LIFT_CAPACITY, 'maxSeats' => \App\Libraries\Economy::LIFT_MAX_SEATS,
        'liftRun' => \App\Libraries\Economy::LIFT_RUN_PER_M, 'slopeRun' => \App\Libraries\Economy::SLOPE_RUN_PER_M,
        'perDay' => \App\Libraries\Economy::LIFT_HOURS / \App\Libraries\Economy::RIDES_PER_SKIER,
        'liftKwh' => \App\Libraries\Utilities::LIFT_KWH_PER_M,
    ] : null) ?>;

    var map, mapBounds;
    var segmentLayers = {};
    var hitLayers = {};
    var stationMarkers = [];
    var sectorPolygons = [];
    var showSectorPolygons = false;
    var selectedSegId = null;
    var buildMode = null;
    var activeTab = 'lift';
    var mapFilter = 'all';

    // Admin Editor State
    var editorMode = null; // null | 'draw' | 'edit' | 'sector_boundary'
    var editorType = 'lift'; // 'lift' | 'slope'
    var editingSegId = null;
    var editingSectorId = null;
    var appendInEditMode = false;
    var snapEnabled = true;
    var selectedVertexIdx = -1;
    var editorPoints = [];
    var undoStack = [];
    var editorLine = null;
    var rubberBandLine = null;
    var snapMarker = null;
    var currentSnapTarget = null;
    var vertexMarkers = [];
    var midpointMarkers = [];
    var lastClickTime = 0;

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        var el = document.getElementById('map');
        if (!el || typeof L === 'undefined') {
            console.error('Leaflet not loaded or #map missing');
            return;
        }
        // Guard against double invocation (e.g. Cloudflare Rocket Loader re-dispatches
        // a simulated DOMContentLoaded after the real one), which would make Leaflet
        // throw "Map container is already initialized."
        if (map) return;

        mapBounds = [[0, 0], [MAP_HEIGHT, MAP_WIDTH]];
        map = L.map('map', {
            crs: L.CRS.Simple,
            minZoom: -3,
            maxZoom: 4,
            zoomSnap: 0.25,
            zoomDelta: 0.5,
            zoomControl: true,
            attributionControl: false
        }).setView([MAP_HEIGHT / 2, MAP_WIDTH / 2], 0);

        initProgressiveOverlays();

        map.fitBounds(mapBounds);
        map.setMaxBounds([[-MAP_HEIGHT * 0.2, -MAP_WIDTH * 0.2], [MAP_HEIGHT * 1.2, MAP_WIDTH * 1.2]]);

        var ld = document.getElementById('mapLoader');
        if (ld) ld.remove();

        renderSegments();
        bindUI();
        if (IS_ADMIN) {
            bindAdmin();
            renderInspectorSectors();
            renderInspectorSegments();
        }

        // Auto-open build drawer for new non-admin players with 0 built runs
        if (BUILT_IDS.length === 0 && !IS_ADMIN) {
            setTimeout(function() { openDrawer('slope'); }, 300);
        }
    }

    /**
     * Non-destructive multi-resolution image overlay loader.
     * Never removes the visible base layer before the higher-res image has completely loaded & decoded.
     */
    function initProgressiveOverlays() {
        var basePath = MAP_IMAGE.replace(/\.jpg$/i, '');
        var imgLow   = basePath + '_low.jpg';
        var imgMed   = basePath + '_med.jpg';
        var imgFull  = MAP_IMAGE;

        // 1. Mount low-res base immediately so map is never blank
        var lowOverlay = L.imageOverlay(imgLow, mapBounds, { zIndex: 1 }).addTo(map);
        var medOverlay = null;
        var fullOverlay = null;
        var fullLoading = false;

        // 2. Preload medium resolution right away and layer on top once decoded
        var medPreload = new Image();
        medPreload.onload = function() {
            medOverlay = L.imageOverlay(imgMed, mapBounds, { zIndex: 2 }).addTo(map);
        };
        medPreload.src = imgMed;

        // 3. Load full HD resolution on demand when zooming in (z >= 1.5) without removing medOverlay
        function checkHighRes() {
            if (map.getZoom() >= 1.5 && !fullOverlay && !fullLoading) {
                fullLoading = true;
                var hdBadge = document.getElementById('hdBadge');
                if (hdBadge) hdBadge.style.display = 'inline-flex';

                var fullPreload = new Image();
                fullPreload.onload = function() {
                    fullOverlay = L.imageOverlay(imgFull, mapBounds, { zIndex: 3 }).addTo(map);
                    if (hdBadge) hdBadge.style.display = 'none';
                };
                fullPreload.onerror = function() {
                    if (hdBadge) hdBadge.style.display = 'none';
                };
                fullPreload.src = imgFull;
            }
        }

        map.on('zoomend', checkHighRes);
    }

    function parsePoints(raw) {
        if (!raw) return [];
        var pts = raw;
        if (typeof raw === 'string') {
            try { pts = JSON.parse(raw); } catch (e) { return []; }
        }
        if (!Array.isArray(pts)) return [];
        return pts.map(function(p) {
            if (Array.isArray(p)) return [ Number(p[0]) || 0, Number(p[1]) || 0 ];
            if (p && typeof p === 'object') return [ Number(p.lat || p[0] || 0), Number(p.lng || p[1] || 0) ];
            return [0, 0];
        }).filter(function(p) {
            return !isNaN(p[0]) && !isNaN(p[1]);
        });
    }

    function isSegmentBuilt(segId) {
        return BUILT_IDS.indexOf(String(segId)) !== -1 || BUILT_IDS.indexOf(Number(segId)) !== -1;
    }

    function isSegmentReleased(seg) {
        if (IS_ADMIN) return true;
        if (!RELEASED_IDS || RELEASED_IDS.length === 0) return true;
        var secVal = Number(seg.sector || 0);
        if (!secVal || secVal === 0) return true;
        return RELEASED_IDS.some(function(r) { return Number(r) === secVal; });
    }

    function segColor(s) {
        if (s.type === 'lift') return COLORS.lift;
        var d = s.difficulty || '';
        if (d && COLORS[d]) return COLORS[d];
        if (s.type && COLORS[s.type]) return COLORS[s.type];
        return COLORS['default'];
    }

    function updateHeaderCounts() {
        var lifts = 0, slopes = 0;
        SEGMENTS.forEach(function(s) {
            if (s.type === 'lift') lifts++;
            else slopes++;
        });
        var slopeEl = document.getElementById('slopeCountEl');
        var liftEl  = document.getElementById('liftCountEl');
        var totalEl = document.getElementById('segmentCountEl');
        var inspEl  = document.getElementById('inspectorSegCount');
        if (slopeEl) slopeEl.textContent = slopes;
        if (liftEl)  liftEl.textContent  = lifts;
        if (totalEl) totalEl.textContent = SEGMENTS.length;
        if (inspEl)  inspEl.textContent  = SEGMENTS.length;
    }

    function renderSegments(mode) {
        if (typeof mode !== 'undefined') {
            buildMode = mode;
        }
        Object.values(segmentLayers).forEach(function(l) { map.removeLayer(l); });
        Object.values(hitLayers).forEach(function(l) { map.removeLayer(l); });
        stationMarkers.forEach(function(m) { map.removeLayer(m); });
        segmentLayers = {};
        hitLayers = {};
        stationMarkers = [];

        SEGMENTS.forEach(function(seg) {
            if (editingSegId && Number(seg.id) === Number(editingSegId)) return;

            var ll = parsePoints(seg.points);
            if (ll.length < 2) return;

            var built = isSegmentBuilt(seg.id);

            // Filter by top filter bar
            if (mapFilter === 'lift' && seg.type !== 'lift') return;
            if (mapFilter === 'slope' && seg.type === 'lift') return;
            if (mapFilter === 'built' && !built) return;
            if (mapFilter === 'unassigned' && Number(seg.sector || 0) !== 0) return;

            // Player vs Build Mode visibility rules
            if (!buildMode && !built && !IS_ADMIN) return;
            if (buildMode && !built) {
                if (buildMode === 'lift' && seg.type !== 'lift') return;
                if (buildMode === 'slope' && seg.type === 'lift') return;
                if (!isSegmentReleased(seg)) return;
            }

            var isAvailableHighlight = (buildMode && !built);
            var c = isAvailableHighlight ? '#a855f7' : segColor(seg);
            var weight = built ? 5 : (IS_ADMIN ? 4.5 : 6);
            var opacity = built ? 0.95 : 0.85;
            var dashArray = built ? (seg.type === 'lift' ? '10, 5' : null) : '6, 5';

            // Visible polyline
            var line = L.polyline(ll, {
                color: c,
                weight: weight,
                opacity: opacity,
                dashArray: dashArray,
                lineCap: 'round',
                lineJoin: 'round',
                interactive: false
            }).addTo(map);

            // Wide invisible hit-target polyline for effortless clicking & hovering
            var hitLine = L.polyline(ll, {
                color: '#000',
                weight: 18,
                opacity: 0.001,
                interactive: true
            }).addTo(map);

            var secName = getSectorName(seg.sector);
            var diffLabel = seg.difficulty ? (' • ' + seg.difficulty.replace('_', ' ').toUpperCase()) : '';
            var tooltipHtml = '<div style="text-align:center;padding:2px 6px">' +
                '<b>' + escHtml(seg.name || seg.type) + '</b>' +
                '<br><span style="font-family:monospace">' + Math.round(seg.length_meters || 0) + ' ' + DIST_UNIT + '</span>' + diffLabel +
                (IS_ADMIN ? '<br><span style="opacity:.75;font-size:10px">' + escHtml(secName) + ' • Click to edit</span>' :
                    (!built ? '<br><span style="color:#c084fc;font-weight:700">Click to construct</span>' : '<br><span style="color:#4ade80;font-size:10px">✓ Operational</span>')) +
                '</div>';

            hitLine.bindTooltip(tooltipHtml, { sticky: true });

            hitLine.on('mouseover', function() {
                if (editorMode) return;
                if (!selectedSegId || Number(selectedSegId) !== Number(seg.id)) {
                    line.setStyle({ weight: weight + 3, opacity: 1 });
                }
            });

            hitLine.on('mouseout', function() {
                if (editorMode) return;
                if (!selectedSegId || Number(selectedSegId) !== Number(seg.id)) {
                    line.setStyle({ weight: weight, opacity: opacity });
                }
            });

            hitLine.on('click', function(e) {
                if (L.DomEvent) L.DomEvent.stopPropagation(e);
                if (editorMode) {
                    // If drawing or appending and snap is enabled, allow clicking an existing line to snap a point onto it!
                    if (editorMode === 'draw' || (editorMode === 'edit' && appendInEditMode)) {
                        var pt = getSnappedLatLng(e.latlng);
                        pushUndoState();
                        editorPoints.push([pt.lat, pt.lng]);
                        updateEditorDisplay(true);
                    }
                    return;
                }
                if (IS_ADMIN) {
                    openAdminSegmentPopup(seg, e.latlng);
                } else if (!built) {
                    selectSegment(seg);
                } else {
                    openBuiltSegmentPopup(seg, e.latlng);
                }
            });

            segmentLayers[seg.id] = line;
            hitLayers[seg.id] = hitLine;
        });

        renderSectorPolygons();
    }

    function openBuiltSegmentPopup(seg, latlng) {
        var item = BUILT_ITEMS[seg.id] || {};
        var capInfo = seg.type === 'lift' && item.capacity ? ('<div class="flex justify-between"><span>Capacity:</span><b>' + Number(item.capacity).toLocaleString() + '/hr</b></div>') : '';
        var snowInfo = seg.type !== 'lift' && item.snow_quality ? ('<div class="flex justify-between"><span>Condition:</span><b class="capitalize">' + escHtml(item.snow_quality) + '</b></div>') : '';
        var status = item.status || 'open';
        var statusClass = { open: 'badge-success', closed: 'badge-ghost', broken: 'badge-error', building: 'badge-info' }[status] || 'badge-ghost';
        var canDemolish = !!item.id;
        var el = document.createElement('div');
        el.className = 'p-1 text-xs min-w-[180px]';
        el.innerHTML =
            '<div class="font-bold text-sm mb-1 flex items-center gap-1.5">' +
                (seg.type === 'lift' ? '<i class="fa-solid fa-elevator text-warning"></i>' : '<i class="fa-solid fa-person-skiing text-success"></i>') +
                ' <span>' + escHtml(seg.name || seg.type) + '</span>' +
            '</div>' +
            '<div class="space-y-1 text-base-content/80">' +
                '<div class="flex justify-between"><span>Length:</span><b class="font-mono">' + Math.round(seg.length_meters || 0) + ' ' + DIST_UNIT + '</b></div>' +
                capInfo + snowInfo +
                '<div class="flex justify-between"><span>Status:</span><span class="badge ' + statusClass + ' badge-xs capitalize">' + escHtml(status) + '</span></div>' +
            '</div>' +
            (canDemolish
                ? '<button type="button" class="btn btn-ghost btn-xs w-full mt-2 gap-1.5 text-error" data-demolish>' +
                      '<i class="fa-solid fa-trash-can"></i> Demolish · refund $' + Number(item.salvage || 0).toLocaleString() +
                  '</button>'
                : '');
        var btn = el.querySelector('[data-demolish]');
        if (btn) btn.addEventListener('click', function() { demolishBuilt(seg, item, btn); });
        L.popup({ className: 'admin-leaflet-popup' })
            .setLatLng(latlng)
            .setContent(el)
            .openOn(map);
    }

    function demolishBuilt(seg, item, btn) {
        var name = seg.name || (seg.type === 'lift' ? 'this lift' : 'this slope');
        var ok = confirm('Demolish ' + name + '?\n\nYou get $' + Number(item.salvage || 0).toLocaleString() +
            ' back (25% salvage). Snow cannons and terrain parks on it are kept but unassigned. This can\'t be undone.');
        if (!ok) return;
        btn.disabled = true;
        postJSON('/map/demolish/' + item.id, {}, function(res) {
            if (!res || !res.success) {
                btn.disabled = false;
                showToast((res && res.error) ? res.error : 'Demolition failed', 'error');
                return;
            }
            BUILT_IDS = BUILT_IDS.filter(function(id) { return Number(id) !== Number(seg.id); });
            delete BUILT_ITEMS[seg.id];
            if (typeof res.new_cash === 'number') {
                USER_CASH = res.new_cash;
                var cashEl = document.getElementById('drawerCashBalance');
                if (cashEl) cashEl.textContent = '$' + USER_CASH.toLocaleString();
            }
            map.closePopup();
            renderSegments(buildMode);
            populateList(activeTab);
            showToast(res.message || ('Demolished ' + name), 'success');
        });
    }

    function renderSectorPolygons() {
        sectorPolygons.forEach(function(p) { map.removeLayer(p); });
        sectorPolygons = [];
        if (!showSectorPolygons) return;

        SECTORS.forEach(function(sec) {
            if (editingSectorId && Number(sec.id) === Number(editingSectorId)) return;
            var pts = parsePoints(sec.boundary_points);
            if (pts.length < 3) return;
            var col = sec.color || '#3b82f6';
            var poly = L.polygon(pts, {
                color: col,
                weight: 2,
                dashArray: '6, 4',
                fillColor: col,
                fillOpacity: 0.14,
                interactive: false
            }).addTo(map);
            poly.bindTooltip('<b>' + escHtml(sec.name) + '</b>', { permanent: true, direction: 'center', className: 'badge badge-neutral badge-xs opacity-85' });
            sectorPolygons.push(poly);
        });
    }

    function syncDrawerLayoutState() {
        var buildEl = document.getElementById('buildDrawer');
        var inspEl  = document.getElementById('adminInspectorDrawer');
        var isOpen  = (buildEl && buildEl.style.display !== 'none') || (inspEl && inspEl.style.display !== 'none');
        var wrap    = document.querySelector('.admin-editor-toolbar-wrap');
        if (wrap) {
            wrap.classList.toggle('has-open-drawer', !!isOpen);
        }
        // Lets the layout move the tutorial card out from over the drawer
        document.body.classList.toggle('map-drawer-open', !!isOpen);
    }

    function updateTabCounts() {
        var availLifts = 0, availSlopes = 0;
        SEGMENTS.forEach(function(s) {
            if (!isSegmentReleased(s) || isSegmentBuilt(s.id)) return;
            if (s.type === 'lift') availLifts++;
            else availSlopes++;
        });
        var lc = document.getElementById('tabCountLift');
        var sc = document.getElementById('tabCountSlope');
        if (lc) lc.textContent = availLifts;
        if (sc) sc.textContent = availSlopes;
    }

    function getSegmentBaseCost(seg) {
        var m = parseFloat(seg.length_meters) || 0;
        if (seg.type === 'lift') {
            return Math.round(m * 150); // Standard 2-seat fixed chairlift baseline
        }
        return Math.round(m * 50);
    }

    function buildEmblemHtml(seg) {
        if (seg.type === 'lift') {
            return '<div class="build-item-emblem" title="Lift"><i class="fa-solid fa-elevator" style="color:#f0a830;font-size:14px"></i></div>';
        }
        var d = seg.difficulty || 'blue';
        if (d === 'green') return '<div class="build-item-emblem" title="Easiest"><i class="tsign green"></i></div>';
        if (d === 'black') return '<div class="build-item-emblem" title="Most difficult"><i class="tsign black"></i></div>';
        if (d === 'double_black') return '<div class="build-item-emblem" title="Experts only"><span class="tsign-pair"><i class="tsign black"></i><i class="tsign black"></i></span></div>';
        return '<div class="build-item-emblem" title="More difficult"><i class="tsign blue"></i></div>';
    }

    function buildDiffPillHtml(seg) {
        if (seg.type === 'lift') return '<span class="build-diff-pill">Lift</span>';
        var d = seg.difficulty || 'blue';
        var names = { green: 'Green', blue: 'Blue', black: 'Black', double_black: 'Double black' };
        return '<span class="build-diff-pill">' + (names[d] || 'Blue') + '</span>';
    }

    function openDrawer(tab) {
        var insp = document.getElementById('adminInspectorDrawer');
        if (insp) insp.style.display = 'none';

        var drawer = document.getElementById('buildDrawer'), fab = document.getElementById('buildFab');
        drawer.style.display = 'flex';
        if (fab) fab.style.display = 'none';
        var targetTab = tab || 'lift';
        activeTab = targetTab;

        var diffFilter = document.getElementById('buildDiffFilter');
        if (diffFilter) diffFilter.style.display = (targetTab === 'slope') ? 'inline-block' : 'none';

        document.querySelectorAll('.build-tab').forEach(function(b) {
            b.classList.toggle('is-active', b.dataset.tab === targetTab);
        });
        updateTabCounts();
        syncDrawerLayoutState();
        renderSegments(targetTab);
        populateList(targetTab);
        deselectSeg();
    }

    function closeDrawer() {
        var drawer = document.getElementById('buildDrawer'), fab = document.getElementById('buildFab');
        drawer.style.display = 'none';
        if (fab) fab.style.display = '';
        deselectSeg();
        syncDrawerLayoutState();
        renderSegments(null);
    }

    function syncOptionCardHighlights() {
        document.querySelectorAll('#liftTypeGrid .build-option-card').forEach(function(card) {
            var inp = card.querySelector('input[name="liftType"]');
            var checked = !!(inp && inp.checked);
            card.classList.toggle('is-checked', checked);
            var icon = card.querySelector('i, svg'); // the Font Awesome kit turns <i> into <svg>
            if (icon) icon.style.color = checked ? '#38bdf8' : '#94a3b8';
        });
        document.querySelectorAll('#slopeTypeGrid .build-option-card').forEach(function(card) {
            var inp = card.querySelector('input[name="slopeType"]');
            var checked = !!(inp && inp.checked);
            card.classList.toggle('is-checked', checked);
        });
    }

    function bindUI() {
        updateTabCounts();
        var fab = document.getElementById('buildFab');
        var headerBtn = document.getElementById('headerBuildBtn');
        if (fab) fab.addEventListener('click', function() { openDrawer('lift'); });
        if (headerBtn) headerBtn.addEventListener('click', function() { openDrawer('lift'); });
        document.getElementById('closeDrawer').addEventListener('click', closeDrawer);

        var btnZoomSel = document.getElementById('btnZoomSelectedSeg');
        if (btnZoomSel) {
            btnZoomSel.addEventListener('click', function() {
                if (selectedSegId && segmentLayers[selectedSegId] && typeof segmentLayers[selectedSegId].getBounds === 'function') {
                    map.fitBounds(segmentLayers[selectedSegId].getBounds(), { maxZoom: 2.2, padding: [70, 70], animate: true });
                }
            });
        }
        var btnDesel = document.getElementById('btnDeselectSeg');
        if (btnDesel) {
            btnDesel.addEventListener('click', function() {
                deselectSeg();
                populateList(activeTab);
            });
        }

        var btnFit = document.getElementById('btnFitMap');
        if (btnFit) {
            btnFit.addEventListener('click', function() {
                map.fitBounds(mapBounds, { animate: true });
            });
        }

        document.querySelectorAll('.map-filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                mapFilter = btn.dataset.filter || 'all';
                document.querySelectorAll('.map-filter-btn').forEach(function(b) {
                    b.classList.toggle('btn-primary', b === btn);
                    b.classList.toggle('btn-ghost', b !== btn);
                });
                renderSegments(buildMode);
            });
        });

        document.querySelectorAll('.build-tab').forEach(function(t) {
            t.addEventListener('click', function() {
                openDrawer(t.dataset.tab);
            });
        });

        var searchInput = document.getElementById('buildSearchInput');
        var diffFilter  = document.getElementById('buildDiffFilter');
        var sortSelect  = document.getElementById('buildSortSelect');
        if (searchInput) searchInput.addEventListener('input', function() { populateList(activeTab); });
        if (diffFilter)  diffFilter.addEventListener('change', function() { populateList(activeTab); });
        if (sortSelect)  sortSelect.addEventListener('change', function() { populateList(activeTab); });

        document.querySelectorAll('input[name="liftType"]').forEach(function(el) {
            el.addEventListener('change', function() {
                syncOptionCardHighlights();
                updateSeats();
            });
        });
        document.querySelectorAll('input[name="slopeType"]').forEach(function(el) {
            el.addEventListener('change', function() {
                syncOptionCardHighlights();
                updateCost();
            });
        });
        document.getElementById('btnBuild').addEventListener('click', doBuild);
    }

    function populateList(type) {
        activeTab = type;
        updateTabCounts();
        var list = document.getElementById('segmentList');
        if (!list) return;

        var q    = (document.getElementById('buildSearchInput') ? document.getElementById('buildSearchInput').value : '').trim().toLowerCase();
        var df   = (document.getElementById('buildDiffFilter') ? document.getElementById('buildDiffFilter').value : '');
        var sort = (document.getElementById('buildSortSelect') ? document.getElementById('buildSortSelect').value : 'cost_asc');

        var avail = SEGMENTS.filter(function(s) {
            if (type === 'lift' && s.type !== 'lift') return false;
            if (type === 'slope' && s.type === 'lift') return false;
            if (!isSegmentReleased(s)) return false;
            if (isSegmentBuilt(s.id)) return false;
            if (q && (s.name || '').toLowerCase().indexOf(q) === -1) return false;
            if (type === 'slope' && df && s.difficulty !== df) return false;
            return true;
        });

        avail.sort(function(a, b) {
            var lenA = parseFloat(a.length_meters) || 0;
            var lenB = parseFloat(b.length_meters) || 0;
            if (sort === 'cost_asc') return lenA - lenB;
            if (sort === 'cost_desc') return lenB - lenA;
            if (sort === 'len_desc') return lenB - lenA;
            return String(a.name || '').localeCompare(String(b.name || ''), undefined, { numeric: true, sensitivity: 'base' });
        });

        if (!avail.length) {
            list.innerHTML =
                '<div style="text-align:center;padding:32px 16px;background:rgba(15,23,42,0.5);border:1px dashed rgba(255,255,255,0.1);border-radius:12px">' +
                    '<i class="fa-solid ' + (type === 'lift' ? 'fa-elevator' : 'fa-person-skiing') + '" style="font-size:24px;color:#475569;margin-bottom:8px"></i>' +
                    '<p style="font-size:13px;font-weight:700;color:#cbd5e1">No available ' + type + 's match</p>' +
                    '<p style="font-size:11px;color:#64748b;margin-top:2px">Try clearing your search or grade filter</p>' +
                '</div>';
            return;
        }

        list.innerHTML =
            '<div class="flex items-center justify-between mb-2 px-0.5">' +
                '<span style="font-size:12px;font-weight:600;color:#cfd4da">' + avail.length + ' available</span>' +
                '<span style="font-size:11px;color:#7d8794">Pick one here or click a purple line</span>' +
            '</div>';

        avail.forEach(function(seg) {
            var btn = document.createElement('button');
            btn.type = 'button';
            var isSel = selectedSegId && Number(selectedSegId) === Number(seg.id);
            btn.className = 'build-item-card' + (isSel ? ' is-selected' : '');

            var baseCost = getSegmentBaseCost(seg);
            var canAfford = USER_CASH >= baseCost;
            var lenVal = Math.round(seg.length_meters || 0).toLocaleString();
            var days = (seg.length_meters || 0) < 200 ? 1 : ((seg.length_meters || 0) < 500 ? 2 : 3);
            var secTag = (seg.sector && Number(seg.sector) > 0) ? ('<span>· ' + escHtml(getSectorName(seg.sector)) + '</span>') : '';

            btn.innerHTML =
                buildEmblemHtml(seg) +
                '<div class="build-item-info">' +
                    '<div class="build-item-title-row">' +
                        '<span class="build-item-name">' + escHtml(seg.name || 'Unnamed') + '</span>' +
                        buildDiffPillHtml(seg) +
                    '</div>' +
                    '<div class="build-item-meta">' +
                        '<span>' + lenVal + ' ' + DIST_UNIT + '</span>' +
                        '<span>· ' + days + (days === 1 ? ' day' : ' days') + '</span>' +
                        secTag +
                    '</div>' +
                '</div>' +
                '<div class="build-item-price-col">' +
                    '<div class="build-item-price" style="color:' + (canAfford ? '#f1f3f5' : '#f87171') + '">' +
                        (seg.type === 'lift' ? '<span style="font-size:9px;color:#94a3b8;font-weight:600;margin-right:2px">from</span>' : '') +
                        '$' + baseCost.toLocaleString() +
                    '</div>' +
                    '<div class="build-item-cta">' + (isSel ? 'Selected' : 'Configure') + '</div>' +
                '</div>';

            btn.addEventListener('mouseenter', function() {
                if (editorMode) return;
                if (segmentLayers[seg.id] && (!selectedSegId || Number(selectedSegId) !== Number(seg.id))) {
                    segmentLayers[seg.id].setStyle({ weight: 8, opacity: 1, color: '#38bdf8', dashArray: null });
                }
            });

            btn.addEventListener('mouseleave', function() {
                if (editorMode) return;
                if (segmentLayers[seg.id] && (!selectedSegId || Number(selectedSegId) !== Number(seg.id))) {
                    var b = isSegmentBuilt(seg.id);
                    segmentLayers[seg.id].setStyle({
                        color: (b || !buildMode) ? segColor(seg) : '#a855f7',
                        weight: b ? 5 : 6,
                        opacity: b ? 0.95 : 0.85,
                        dashArray: b ? (seg.type === 'lift' ? '10, 5' : null) : '6, 5'
                    });
                }
            });

            btn.addEventListener('click', function() { selectSegment(seg); });
            list.appendChild(btn);
        });
    }

    function selectSegment(seg) {
        deselectSeg();
        selectedSegId = seg.id;
        var targetTab = (seg.type === 'lift') ? 'lift' : 'slope';
        activeTab = targetTab;

        document.querySelectorAll('.build-tab').forEach(function(b) {
            b.classList.toggle('is-active', b.dataset.tab === targetTab);
        });
        var diffFilter = document.getElementById('buildDiffFilter');
        if (diffFilter) diffFilter.style.display = (targetTab === 'slope') ? 'inline-block' : 'none';

        if (segmentLayers[seg.id]) {
            segmentLayers[seg.id].setStyle({ weight: 8, opacity: 1, color: '#38bdf8', dashArray: null });
            try {
                if (typeof segmentLayers[seg.id].getBounds === 'function') {
                    map.panTo(segmentLayers[seg.id].getBounds().getCenter(), { animate: true });
                }
            } catch (e) {}
        }
        document.getElementById('segmentDetail').style.display = 'block';
        document.getElementById('selSegName').textContent = seg.name || 'Unnamed';
        var badgeEl = document.getElementById('selSegBadge');
        if (badgeEl) {
            badgeEl.removeAttribute('style');
            badgeEl.style.display = 'inline-flex';
            badgeEl.style.alignItems = 'center';
            badgeEl.style.gap = '6px';
            badgeEl.innerHTML = buildEmblemHtml(seg).replace('class="build-item-emblem"', 'style="display:inline-flex"') + buildDiffPillHtml(seg);
        }
        document.getElementById('selSegLength').textContent = Math.round(seg.length_meters || 0).toLocaleString() + ' ' + DIST_UNIT;
        var days = (seg.length_meters || 0) < 200 ? 1 : ((seg.length_meters || 0) < 500 ? 2 : 3);
        document.getElementById('selSegTime').textContent = days + ' day' + (days > 1 ? 's' : '');
        var secEl = document.getElementById('selSegSector');
        if (secEl) secEl.textContent = getSectorName(seg.sector);

        document.getElementById('liftOptions').style.display = seg.type === 'lift' ? 'block' : 'none';
        document.getElementById('slopeOptions').style.display = seg.type === 'lift' ? 'none' : 'block';
        if (seg.type !== 'lift') {
            var sr = document.querySelector('input[name="slopeType"][value="' + seg.type + '"]');
            if (sr) sr.checked = true;
        }
        syncOptionCardHighlights();

        document.getElementById('buildDrawer').style.display = 'flex';
        var fab = document.getElementById('buildFab');
        if (fab) fab.style.display = 'none';
        syncDrawerLayoutState();

        if (seg.type === 'lift') updateSeats();
        else updateCost();
        populateList(targetTab);

        var drawerBody = document.getElementById('buildDrawerBody');
        if (drawerBody) drawerBody.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function deselectSeg() {
        if (selectedSegId && segmentLayers[selectedSegId]) {
            var s = SEGMENTS.find(function(x) { return Number(x.id) === Number(selectedSegId); });
            if (s) {
                var b = isSegmentBuilt(s.id);
                segmentLayers[selectedSegId].setStyle({
                    color: (b || !buildMode) ? segColor(s) : '#a855f7',
                    weight: b ? 5 : 6,
                    opacity: b ? 0.95 : 0.85,
                    dashArray: b ? (s.type === 'lift' ? '10, 5' : null) : '6, 5'
                });
            }
        }
        selectedSegId = null;
        document.getElementById('segmentDetail').style.display = 'none';
    }

    function updateCost() {
        if (!selectedSegId) return;
        var seg = SEGMENTS.find(function(s) { return Number(s.id) === Number(selectedSegId); });
        if (!seg) return;
        var m = parseFloat(seg.length_meters) || 0, cost;
        if (seg.type === 'lift') {
            var lt = document.querySelector('input[name="liftType"]:checked');
            var st = document.querySelector('input[name="seats"]:checked');
            cost = m * (COST_PER_METER[lt ? lt.value : 'chair_fixed'] || 150) * (SEAT_MULT[st ? parseInt(st.value, 10) : 4] || 1);
        } else {
            cost = m * 50;
        }
        var roundedCost = Math.round(cost);
        var canAfford = USER_CASH >= roundedCost;
        var runEl = document.getElementById('selSegRunInfo');
        if (runEl && ECONOMY_V2) {
            var info;
            if (seg.type === 'lift') {
                var ltv = (document.querySelector('input[name="liftType"]:checked') || {}).value || 'chair_fixed';
                var stv = parseInt((document.querySelector('input[name="seats"]:checked') || {}).value || 4, 10);
                var perHour = Math.round((ECONOMY_V2.capacity[ltv] || 2400) * Math.min(1, stv / (ECONOMY_V2.maxSeats[ltv] || 4)));
                info = 'Carries ~' + Math.round(perHour * ECONOMY_V2.perDay).toLocaleString() + ' skiers/day · runs $' + Math.round(m * (ECONOMY_V2.liftRun[ltv] || 0.8)).toLocaleString() + '/day + ' + Math.round(m * (ECONOMY_V2.liftKwh[ltv] || 0.8)).toLocaleString() + ' kWh';
            } else {
                info = 'Draws ~' + Math.round(30 + m / 40) + ' skiers/day · upkeep $' + Math.round(m * ECONOMY_V2.slopeRun).toLocaleString() + '/day';
            }
            runEl.textContent = info;
            runEl.hidden = false;
        }
        var costEl = document.getElementById('selSegCost');
        if (costEl) {
            costEl.textContent = '$' + roundedCost.toLocaleString();
            costEl.style.color = canAfford ? '#4ade80' : '#f87171';
        }
        var afterEl = document.getElementById('selSegAfterCash');
        if (afterEl) {
            if (canAfford) {
                afterEl.textContent = 'Balance after: $' + (USER_CASH - roundedCost).toLocaleString();
                afterEl.style.color = '#94a3b8';
            } else {
                afterEl.textContent = 'Need $' + (roundedCost - USER_CASH).toLocaleString() + ' more';
                afterEl.style.color = '#f87171';
            }
        }
        var buildBtn = document.getElementById('btnBuild');
        if (buildBtn) {
            buildBtn.disabled = !canAfford;
            buildBtn.title = canAfford ? '' : 'Not enough cash for this option';
        }
    }

    function updateSeats() {
        var lt = document.querySelector('input[name="liftType"]:checked');
        var type = lt ? lt.value : 'chair_fixed';
        var opts = SEAT_OPTIONS[type] || [2, 4, 6, 8];
        var grid = document.getElementById('seatGrid');
        grid.innerHTML = '';
        opts.forEach(function(n, i) {
            var label = document.createElement('label');
            label.className = 'seat-pill-btn' + (i === 0 ? ' is-checked' : '');
            label.innerHTML = '<input type="radio" name="seats" value="' + n + '" class="hidden"' + (i === 0 ? ' checked' : '') + '>' +
                '<span>' + n + (type === 'gondola' || type === 'cable_car' ? 'p' : ' seats') + '</span>';
            grid.appendChild(label);
        });
        grid.querySelectorAll('input[name="seats"]').forEach(function(el) {
            el.addEventListener('change', function() {
                grid.querySelectorAll('.seat-pill-btn').forEach(function(lbl) {
                    var r = lbl.querySelector('input[name="seats"]');
                    lbl.classList.toggle('is-checked', !!(r && r.checked));
                });
                updateCost();
            });
        });
        updateCost();
    }

    function doBuild() {
        if (!selectedSegId) return;
        var seg = SEGMENTS.find(function(s) { return Number(s.id) === Number(selectedSegId); });
        if (!seg) return;
        var body = { segment_id: seg.id };
        if (seg.type === 'lift') {
            var lt = document.querySelector('input[name="liftType"]:checked');
            var st = document.querySelector('input[name="seats"]:checked');
            body.lift_type = lt ? lt.value : 'chair_fixed';
            body.seats = st ? parseInt(st.value, 10) : 4;
        } else {
            var sl = document.querySelector('input[name="slopeType"]:checked');
            body.slope_type = sl ? sl.value : 'downhill';
        }

        var btn = document.getElementById('btnBuild');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="loading loading-spinner loading-xs"></span> Building...';
        }

        postJSON('/map/build', body, function(res) {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-hammer"></i> Construct';
            }
            if (res && res.success) {
                BUILT_IDS.push(String(seg.id));
                if (res.item) BUILT_ITEMS[seg.id] = res.item;
                if (typeof res.new_cash === 'number') {
                    USER_CASH = res.new_cash;
                    var cashEl = document.getElementById('drawerCashBalance');
                    if (cashEl) cashEl.textContent = '$' + USER_CASH.toLocaleString();
                }
                showToast('Constructed "' + (seg.name || seg.type) + '" for $' + Number(res.cost || 0).toLocaleString() + '!', 'success');
                deselectSeg();
                renderSegments(buildMode);
                populateList(activeTab);
                if (typeof loadTutorial === 'function') loadTutorial();
            } else {
                showToast((res && res.error) ? res.error : 'Construction failed', 'error');
                updateCost();
            }
        });
    }

    function postJSON(url, data, callback) {
        var payload = Object.assign({}, data || {});
        payload[CSRF_NAME] = CSRF_HASH;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': CSRF_HASH
            },
            body: JSON.stringify(payload)
        })
        .then(function(res) {
            return res.json().catch(function() {
                throw new Error('Server returned HTTP ' + res.status);
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
            if (callback) callback({ success: false, error: err.message || 'Request failed' });
        });
    }

    function calcLengthMeters(points) {
        if (!points || points.length < 2) return 0;
        var total = 0;
        for (var i = 1; i < points.length; i++) {
            var dy = points[i][0] - points[i - 1][0];
            var dx = points[i][1] - points[i - 1][1];
            total += Math.sqrt(dy * dy + dx * dx);
        }
        return Math.max(1, Math.round(total * 15.0));
    }

    function getSectorName(secId) {
        if (!secId || secId === '0' || secId === 0) return 'Default (0)';
        var sec = SECTORS.find(function(s) { return String(s.id) === String(secId) || s.name === String(secId); });
        return sec ? sec.name : 'Sector #' + secId;
    }

    function escHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function showToast(msg, type) {
        var toast = document.getElementById('adminToast');
        var alertEl = document.getElementById('adminToastAlert');
        var msgEl = document.getElementById('adminToastMsg');
        if (!toast || !alertEl || !msgEl) return;
        msgEl.textContent = msg;
        alertEl.className = 'alert py-2 px-3.5 text-xs shadow-xl font-semibold ' +
            (type === 'error' ? 'alert-error' : (type === 'warning' ? 'alert-warning' : (type === 'info' ? 'alert-info' : 'alert-success')));
        toast.classList.remove('opacity-0', 'translate-y-[-10px]');
        toast.classList.add('opacity-100', 'translate-y-0');
        if (window._toastTimeout) clearTimeout(window._toastTimeout);
        window._toastTimeout = setTimeout(function() {
            toast.classList.remove('opacity-100', 'translate-y-0');
            toast.classList.add('opacity-0', 'translate-y-[-10px]');
        }, 3200);
    }

    /* ======================================================================
     * ADMIN MAP STUDIO: LIFT, SLOPE & SECTOR BOUNDARY DRAWING ENGINE
     * ====================================================================== */

    function pushUndoState() {
        undoStack.push(editorPoints.map(function(p) { return [p[0], p[1]]; }));
        if (undoStack.length > 40) undoStack.shift();
    }

    /**
     * Magnetic endpoint & vertex snapping:
     * Finds the closest existing segment vertex within threshold (in CRS map units).
     */
    function getSnappedLatLng(latlng, altPressed) {
        currentSnapTarget = null;
        if (!snapEnabled || altPressed || editorMode === 'sector_boundary') {
            hideSnapIndicator();
            return latlng;
        }

        // Convert 14 screen pixels into current zoom's CRS coordinate distance
        var p1 = map.latLngToContainerPoint(latlng);
        var p2 = L.point(p1.x + 14, p1.y);
        var ll2 = map.containerPointToLatLng(p2);
        var maxDist = Math.abs(ll2.lng - latlng.lng) || 5;

        var bestDist = maxDist;
        var bestPt = null;
        var bestLabel = '';

        SEGMENTS.forEach(function(seg) {
            if (editingSegId && Number(seg.id) === Number(editingSegId)) return;
            var pts = parsePoints(seg.points);
            if (!pts.length) return;
            pts.forEach(function(pt, idx) {
                var dy = pt[0] - latlng.lat;
                var dx = pt[1] - latlng.lng;
                var d = Math.sqrt(dy * dy + dx * dx);
                // Prioritize start/end stations slightly
                var isEndPt = (idx === 0 || idx === pts.length - 1);
                var effDist = isEndPt ? d * 0.85 : d;
                if (effDist < bestDist) {
                    bestDist = effDist;
                    bestPt = pt;
                    bestLabel = (seg.name || seg.type) + (idx === 0 ? ' (Start)' : (idx === pts.length - 1 ? ' (End)' : ''));
                }
            });
        });

        if (bestPt) {
            currentSnapTarget = { lat: bestPt[0], lng: bestPt[1], label: bestLabel };
            showSnapIndicator([bestPt[0], bestPt[1]], bestLabel);
            return L.latLng(bestPt[0], bestPt[1]);
        }

        hideSnapIndicator();
        return latlng;
    }

    function showSnapIndicator(pt, label) {
        if (!snapMarker) {
            var icon = L.divIcon({
                className: 'snap-indicator-wrap',
                html: '<div class="snap-indicator-ring"></div>',
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });
            snapMarker = L.marker(pt, { icon: icon, interactive: false, zIndexOffset: 2000 }).addTo(map);
        } else {
            snapMarker.setLatLng(pt);
        }
        var pill = document.getElementById('snapStatusPill');
        if (pill) {
            pill.style.display = 'inline-flex';
            pill.textContent = '🧲 Snapped: ' + label;
        }
    }

    function hideSnapIndicator() {
        if (snapMarker) {
            map.removeLayer(snapMarker);
            snapMarker = null;
        }
        var pill = document.getElementById('snapStatusPill');
        if (pill) pill.style.display = 'none';
    }

    function clearEditorMarkers() {
        vertexMarkers.forEach(function(m) { map.removeLayer(m); });
        vertexMarkers = [];
        midpointMarkers.forEach(function(m) { map.removeLayer(m); });
        midpointMarkers = [];
        if (rubberBandLine) {
            map.removeLayer(rubberBandLine);
            rubberBandLine = null;
        }
        hideSnapIndicator();
    }

    function deleteVertexAt(index) {
        var minPts = (editorMode === 'sector_boundary') ? 3 : 2;
        if (editorMode === 'draw' && editorPoints.length <= 1) {
            editorPoints = [];
            selectedVertexIdx = -1;
            updateEditorDisplay(true);
            return;
        }
        if (editorPoints.length <= minPts) {
            showToast('A ' + (editorMode === 'sector_boundary' ? 'sector boundary requires at least 3' : 'line requires at least 2') + ' points', 'warning');
            return;
        }
        pushUndoState();
        editorPoints.splice(index, 1);
        selectedVertexIdx = -1;
        updateEditorDisplay(true);
    }

    function createVertexMarker(latlng, index, total, accentColor) {
        var isStart = (index === 0);
        var isEnd   = (total > 1 && index === total - 1 && editorMode !== 'sector_boundary');
        var isSel   = (index === selectedVertexIdx);

        var classes = 'custom-vertex-handle' +
            (isStart ? ' is-start' : (isEnd ? ' is-end' : '')) +
            (isSel ? ' is-selected' : '');
        var label = isStart ? 'S' : (isEnd ? 'E' : '');
        var size  = (isStart || isEnd) ? 20 : 16;
        var anchor = size / 2;

        var icon = L.divIcon({
            className: 'custom-vertex-wrap',
            html: '<div class="' + classes + '" style="' + (!isStart && !isEnd ? 'border-color:' + accentColor : '') + '">' + label + '</div>',
            iconSize: [size, size],
            iconAnchor: [anchor, anchor]
        });

        var marker = L.marker(latlng, {
            icon: icon,
            draggable: true,
            zIndexOffset: isStart || isEnd ? 1200 : 1000
        });

        var ptLabel = isStart ? 'Start Station (Point #1)' : (isEnd ? 'End Station (Point #' + (index + 1) + ')' : 'Vertex #' + (index + 1));
        marker.bindTooltip(ptLabel + ' • Drag to move • Right-click or Alt+click to delete', { direction: 'top', offset: [0, -8] });

        marker.on('dragstart', function() {
            pushUndoState();
            selectedVertexIdx = index;
        });

        marker.on('drag', function(e) {
            var rawLl = marker.getLatLng();
            var altKey = e.originalEvent && e.originalEvent.altKey;
            var snapped = getSnappedLatLng(rawLl, altKey);
            editorPoints[index] = [snapped.lat, snapped.lng];
            updateEditorDisplay(false);
        });

        marker.on('dragend', function() {
            if (currentSnapTarget) {
                editorPoints[index] = [currentSnapTarget.lat, currentSnapTarget.lng];
            }
            hideSnapIndicator();
            updateEditorDisplay(true);
        });

        marker.on('click', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            var orig = e.originalEvent;
            if (orig && (orig.altKey || orig.shiftKey)) {
                deleteVertexAt(index);
                return;
            }
            selectedVertexIdx = index;
            updateEditorDisplay(true);
        });

        marker.on('contextmenu', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            deleteVertexAt(index);
        });

        return marker;
    }

    function createMidpointMarker(index, nextIndex, accentColor) {
        var p1 = editorPoints[index];
        var p2 = editorPoints[nextIndex];
        var midLat = (p1[0] + p2[0]) / 2;
        var midLng = (p1[1] + p2[1]) / 2;

        var icon = L.divIcon({
            className: 'custom-midpoint-wrap',
            html: '<div class="custom-midpoint-handle" style="background:' + accentColor + '">+</div>',
            iconSize: [12, 12],
            iconAnchor: [6, 6]
        });

        var marker = L.marker([midLat, midLng], {
            icon: icon,
            draggable: true,
            zIndexOffset: 900
        });

        marker.bindTooltip('Click or drag to insert a bend point here', { direction: 'top', offset: [0, -6] });

        var insertedIndex = -1;

        marker.on('click', function(e) {
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            pushUndoState();
            var insertAt = index + 1;
            editorPoints.splice(insertAt, 0, [midLat, midLng]);
            selectedVertexIdx = insertAt;
            updateEditorDisplay(true);
            showToast('Inserted vertex #' + (insertAt + 1), 'info');
        });

        marker.on('dragstart', function() {
            pushUndoState();
            insertedIndex = index + 1;
            var ll = marker.getLatLng();
            editorPoints.splice(insertedIndex, 0, [ll.lat, ll.lng]);
            selectedVertexIdx = insertedIndex;
        });

        marker.on('drag', function(e) {
            if (insertedIndex !== -1) {
                var rawLl = marker.getLatLng();
                var altKey = e.originalEvent && e.originalEvent.altKey;
                var snapped = getSnappedLatLng(rawLl, altKey);
                editorPoints[insertedIndex] = [snapped.lat, snapped.lng];
                updateEditorDisplay(false);
            }
        });

        marker.on('dragend', function() {
            if (insertedIndex !== -1 && currentSnapTarget) {
                editorPoints[insertedIndex] = [currentSnapTarget.lat, currentSnapTarget.lng];
            }
            insertedIndex = -1;
            hideSnapIndicator();
            updateEditorDisplay(true);
        });

        return marker;
    }

    function getActiveEditorAccentColor() {
        if (editorMode === 'sector_boundary') {
            var sec = SECTORS.find(function(s) { return Number(s.id) === Number(editingSectorId); });
            return (sec && sec.color) ? sec.color : '#38bdf8';
        }
        if (editorType === 'lift') return COLORS.lift;
        var slopeType = document.getElementById('toolSlopeType') ? document.getElementById('toolSlopeType').value : 'downhill';
        var diff = document.getElementById('toolSegDiff') ? document.getElementById('toolSegDiff').value : 'blue';
        if (slopeType === 'snowpark') return COLORS.snowpark;
        return COLORS[diff] || COLORS.blue;
    }

    function updateEditorDisplay(rebuildMarkers) {
        var accentColor = getActiveEditorAccentColor();

        if (editorLine) {
            map.removeLayer(editorLine);
            editorLine = null;
        }

        if (editorPoints.length > 0) {
            if (editorMode === 'sector_boundary' && editorPoints.length >= 3) {
                editorLine = L.polygon(editorPoints, {
                    color: accentColor,
                    weight: 3,
                    opacity: 0.95,
                    fillColor: accentColor,
                    fillOpacity: 0.2,
                    dashArray: '6, 4',
                    interactive: false
                }).addTo(map);
            } else {
                editorLine = L.polyline(editorPoints, {
                    color: accentColor,
                    weight: 5,
                    opacity: 0.95,
                    dashArray: '8, 6',
                    interactive: false
                }).addTo(map);
            }
        }

        var lengthM = calcLengthMeters(editorPoints);
        var lenEl = document.getElementById('toolLiveLength');
        var ptsEl = document.getElementById('toolPointCount');
        if (lenEl) lenEl.textContent = lengthM.toLocaleString();
        if (ptsEl) ptsEl.textContent = editorPoints.length;

        if (rebuildMarkers) {
            clearEditorMarkers();
            var total = editorPoints.length;
            editorPoints.forEach(function(pt, idx) {
                var vm = createVertexMarker(pt, idx, total, accentColor);
                vm.addTo(map);
                vertexMarkers.push(vm);
            });
            if (total >= 2) {
                for (var i = 0; i < total - 1; i++) {
                    var mm = createMidpointMarker(i, i + 1, accentColor);
                    mm.addTo(map);
                    midpointMarkers.push(mm);
                }
                if (editorMode === 'sector_boundary' && total >= 3) {
                    var closeMm = createMidpointMarker(total - 1, 0, accentColor);
                    closeMm.addTo(map);
                    midpointMarkers.push(closeMm);
                }
            }
        }
    }

    function configureToolbarForType(isLift, slopeSubType, difficultyVal, sectorVal, segName) {
        var slopeTypeEl = document.getElementById('toolSlopeType');
        var diffEl      = document.getElementById('toolSegDiff');
        var secEl       = document.getElementById('toolSegSector');
        var nameEl      = document.getElementById('toolSegName');

        if (nameEl) nameEl.value = segName || '';
        if (secEl)  secEl.value  = String(Number(sectorVal || 0));

        if (!isLift) {
            slopeTypeEl.style.display = 'inline-block';
            slopeTypeEl.value = slopeSubType || 'downhill';
            diffEl.style.display = 'inline-block';
            diffEl.value = difficultyVal || 'blue';
        } else {
            slopeTypeEl.style.display = 'none';
            diffEl.style.display = 'none';
        }
    }

    function setAppendMode(enabled) {
        appendInEditMode = !!enabled;
        var btn = document.getElementById('toolBtnAppendToggle');
        var txt = document.getElementById('toolAppendText');
        if (btn && txt) {
            txt.textContent = 'Append: ' + (appendInEditMode ? 'ON' : 'OFF');
            btn.className = 'btn btn-xs gap-1 ' + (appendInEditMode ? 'btn-info font-bold' : 'btn-info btn-outline');
        }
        var mapEl = document.getElementById('map');
        if (mapEl) {
            if (editorMode === 'draw' || editorMode === 'sector_boundary' || appendInEditMode) {
                mapEl.classList.add('drawing-cursor');
            } else {
                mapEl.classList.remove('drawing-cursor');
            }
        }
    }

    function nextSuggestedName(type) {
        var isLift = (type === 'lift');
        var prefix = isLift ? 'Lift Line ' : 'Slope Path ';
        var maxNum = SEGMENTS.filter(function(s) {
            return isLift ? s.type === 'lift' : s.type !== 'lift';
        }).length + 1;
        while (SEGMENTS.some(function(s) { return (s.name || '').toLowerCase() === (prefix + maxNum).toLowerCase(); })) {
            maxNum++;
        }
        return prefix + maxNum;
    }

    function startDraw(type, preserveSettings) {
        var prevSec  = document.getElementById('toolSegSector') ? document.getElementById('toolSegSector').value : null;
        var prevDiff = document.getElementById('toolSegDiff') ? document.getElementById('toolSegDiff').value : 'blue';
        var prevSub  = document.getElementById('toolSlopeType') ? document.getElementById('toolSlopeType').value : 'downhill';

        if (editorMode) cancelEditor(true);
        editorMode = 'draw';
        editingSegId = null;
        editingSectorId = null;
        editorType = (type === 'lift') ? 'lift' : 'slope';
        editorPoints = [];
        undoStack = [];
        selectedVertexIdx = -1;

        document.getElementById('toolIdleView').style.display = 'none';
        document.getElementById('toolActiveView').style.display = 'flex';
        document.getElementById('toolHelperHint').style.display = 'flex';
        document.getElementById('toolBtnAppendToggle').style.display = 'none';
        document.getElementById('toolBtnSaveNext').style.display = 'inline-flex';
        document.getElementById('toolSegName').style.display = 'inline-block';
        document.getElementById('toolSegSector').style.display = 'inline-block';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge text-xs font-bold gap-1.5 py-2.5 px-2.5 ' + (editorType === 'lift' ? 'badge-warning' : 'badge-success');
        document.getElementById('toolModeText').textContent = 'New ' + (editorType === 'lift' ? 'Lift' : 'Slope');

        var activeSec = preserveSettings && prevSec !== null
            ? prevSec
            : (document.getElementById('toolActiveSector') ? document.getElementById('toolActiveSector').value : '0');

        configureToolbarForType(
            editorType === 'lift',
            preserveSettings ? prevSub : 'downhill',
            preserveSettings ? prevDiff : 'blue',
            activeSec,
            nextSuggestedName(editorType)
        );

        setAppendMode(true);
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Drawing ' + (editorType === 'lift' ? 'Lift (Bottom → Top)' : 'Slope (Top → Bottom)') + ': click map to place points', 'info');
    }

    function startEditSegment(seg) {
        if (editorMode) cancelEditor(true);
        editorMode = 'edit';
        editingSegId = seg.id;
        editingSectorId = null;
        // CRITICAL FIX: normalize editorType to 'lift' or 'slope' so slope subtypes ('downhill', etc.) preserve difficulty & subtype!
        editorType = (seg.type === 'lift') ? 'lift' : 'slope';
        editorPoints = parsePoints(seg.points);
        undoStack = [];
        selectedVertexIdx = -1;

        // Re-render static segments so the edited segment is hidden from static layer
        renderSegments(buildMode);

        document.getElementById('toolIdleView').style.display = 'none';
        document.getElementById('toolActiveView').style.display = 'flex';
        document.getElementById('toolHelperHint').style.display = 'flex';
        document.getElementById('toolBtnAppendToggle').style.display = 'inline-flex';
        document.getElementById('toolBtnSaveNext').style.display = 'none';
        document.getElementById('toolSegName').style.display = 'inline-block';
        document.getElementById('toolSegSector').style.display = 'inline-block';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge text-xs font-bold gap-1.5 py-2.5 px-2.5 ' + (editorType === 'lift' ? 'badge-warning' : 'badge-success');
        document.getElementById('toolModeText').textContent = 'Editing ' + (editorType === 'lift' ? 'Lift' : 'Slope');

        configureToolbarForType(
            editorType === 'lift',
            seg.type !== 'lift' ? seg.type : 'downhill',
            seg.difficulty || 'blue',
            seg.sector || 0,
            seg.name || ''
        );

        // In edit mode, default Append to OFF so clicking the map to pan doesn't accidentally add stray vertices
        setAppendMode(false);
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Editing "' + (seg.name || seg.type) + '": drag points to move, drag/click + midpoints to bend', 'info');
    }

    function startEditSectorBoundary(sec) {
        if (editorMode) cancelEditor(true);
        editorMode = 'sector_boundary';
        editingSectorId = sec.id;
        editingSegId = null;
        editorPoints = parsePoints(sec.boundary_points);
        undoStack = [];
        selectedVertexIdx = -1;
        showSectorPolygons = true;
        renderSectorPolygons();

        document.getElementById('toolIdleView').style.display = 'none';
        document.getElementById('toolActiveView').style.display = 'flex';
        document.getElementById('toolHelperHint').style.display = 'flex';
        document.getElementById('toolBtnAppendToggle').style.display = 'none';
        document.getElementById('toolBtnSaveNext').style.display = 'none';
        document.getElementById('toolSegName').style.display = 'none';
        document.getElementById('toolSegSector').style.display = 'none';
        document.getElementById('toolSlopeType').style.display = 'none';
        document.getElementById('toolSegDiff').style.display = 'none';

        var modeBadge = document.getElementById('toolModeBadge');
        modeBadge.className = 'badge badge-info text-xs font-bold gap-1.5 py-2.5 px-2.5';
        document.getElementById('toolModeText').textContent = 'Boundary: ' + sec.name;

        setAppendMode(true);
        map.doubleClickZoom.disable();

        updateEditorDisplay(true);
        showToast('Click around the mountain to outline "' + sec.name + '", then click Save', 'info');
    }

    function cancelEditor(skipRender) {
        editorMode = null;
        editingSegId = null;
        editingSectorId = null;
        editorPoints = [];
        undoStack = [];
        selectedVertexIdx = -1;
        setAppendMode(false);

        if (editorLine) {
            map.removeLayer(editorLine);
            editorLine = null;
        }
        clearEditorMarkers();

        map.doubleClickZoom.enable();

        var activeView = document.getElementById('toolActiveView');
        var idleView   = document.getElementById('toolIdleView');
        var hintView   = document.getElementById('toolHelperHint');
        if (activeView) activeView.style.display = 'none';
        if (hintView)   hintView.style.display   = 'none';
        if (idleView)   idleView.style.display   = 'flex';

        if (!skipRender) {
            renderSegments(buildMode);
        }
    }

    function saveEditor(drawNextAfterSave) {
        if (editorMode === 'sector_boundary') {
            if (editorPoints.length < 3) {
                showToast('Please place at least 3 points for a sector boundary polygon', 'warning');
                return;
            }
            var secId = editingSectorId;
            postJSON('/map/sector/boundary/' + secId, { id: secId, boundary_points: editorPoints }, function(res) {
                if (res && res.success) {
                    var sec = SECTORS.find(function(s) { return Number(s.id) === Number(secId); });
                    if (sec) sec.boundary_points = JSON.stringify(editorPoints);
                    showToast('Sector boundary saved!', 'success');
                    cancelEditor(false);
                    renderInspectorSectors();
                }
            });
            return;
        }

        if (editorPoints.length < 2) {
            showToast('Please place at least 2 points for a lift or slope', 'warning');
            return;
        }

        var name = document.getElementById('toolSegName').value.trim();
        if (!name) {
            name = nextSuggestedName(editorType);
        }

        var sectorId   = parseInt(document.getElementById('toolSegSector').value || '0', 10) || 0;
        var isLift     = (editorType === 'lift');
        var segType    = isLift ? 'lift' : (document.getElementById('toolSlopeType').value || 'downhill');
        var difficulty = isLift ? '' : (document.getElementById('toolSegDiff').value || 'blue');
        var lengthM    = calcLengthMeters(editorPoints);

        // Keep the idle toolbar's active sector synced with the last used sector
        var idleSecEl = document.getElementById('toolActiveSector');
        if (idleSecEl) idleSecEl.value = String(sectorId);

        var payload = {
            name: name,
            type: segType,
            sector: sectorId,
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

        var currentEditorType = editorType;

        postJSON('/map/segment', payload, function(res) {
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerHTML = '<i class="fa-solid fa-check"></i> Save';
            }

            if (res && res.success) {
                var savedId = Number(res.id || editingSegId);
                var updatedSeg = {
                    id: savedId,
                    name: name,
                    type: segType,
                    sector: sectorId,
                    difficulty: difficulty,
                    length_meters: lengthM,
                    points: JSON.stringify(editorPoints),
                    active: 1
                };

                if (editingSegId) {
                    var idx = SEGMENTS.findIndex(function(s) { return Number(s.id) === Number(editingSegId); });
                    if (idx !== -1) {
                        SEGMENTS[idx] = updatedSeg;
                    }
                    showToast('Updated "' + name + '" (' + lengthM + ' ' + DIST_UNIT + ')', 'success');
                } else {
                    SEGMENTS.push(updatedSeg);
                    showToast('Created "' + name + '" (' + lengthM + ' ' + DIST_UNIT + ')', 'success');
                }

                updateHeaderCounts();
                renderInspectorSegments();
                if (document.getElementById('buildDrawer').style.display !== 'none') {
                    populateList(activeTab);
                }

                if (drawNextAfterSave) {
                    cancelEditor(false);
                    startDraw(currentEditorType, true);
                } else {
                    cancelEditor(false);
                }
            } else {
                showToast((res && res.error) ? res.error : 'Failed to save segment', 'error');
            }
        });
    }

    function undoLastAction() {
        if (!editorMode) return;
        if (undoStack.length > 0) {
            editorPoints = undoStack.pop();
            selectedVertexIdx = -1;
            updateEditorDisplay(true);
        } else if (editorPoints.length > 0) {
            editorPoints.pop();
            selectedVertexIdx = -1;
            updateEditorDisplay(true);
        }
    }

    function reverseEditorPoints() {
        if (!editorMode || editorPoints.length < 2) return;
        pushUndoState();
        editorPoints.reverse();
        updateEditorDisplay(true);
        showToast('Reversed Start (S) and End (E) direction', 'info');
    }

    function openAdminSegmentPopup(seg, latlng) {
        var c = segColor(seg);
        var secName = getSectorName(seg.sector);
        var diffBadge = seg.difficulty ? '<span class="badge badge-xs text-[10px] font-bold uppercase" style="background:' + c + ';color:#fff">' + escHtml(seg.difficulty.replace('_', ' ')) + '</span>' : '';
        var popupContent = document.createElement('div');
        popupContent.className = 'admin-segment-popover p-1 text-xs';
        popupContent.innerHTML =
            '<div class="flex items-center justify-between gap-2 mb-2 pb-1.5 border-b border-white/10">' +
                '<div class="font-bold text-sm flex items-center gap-1.5">' +
                    (seg.type === 'lift' ? '<i class="fa-solid fa-elevator text-warning"></i>' : '<i class="fa-solid fa-person-skiing text-success"></i>') +
                    ' <span class="truncate max-w-[145px]">' + escHtml(seg.name || 'Unnamed') + '</span>' +
                '</div>' +
                diffBadge +
            '</div>' +
            '<div class="space-y-1 mb-3 text-base-content/80 text-[11px]">' +
                '<div class="flex items-center justify-between"><span>ID / Type:</span> <span class="font-mono">#' + seg.id + ' • <b class="capitalize">' + escHtml(seg.type) + '</b></span></div>' +
                '<div class="flex items-center justify-between"><span>Length:</span> <span class="font-mono font-bold">' + Math.round(seg.length_meters || 0) + ' ' + DIST_UNIT + '</span></div>' +
                '<div class="flex items-center justify-between"><span>Sector:</span> <span class="badge badge-outline badge-xs">' + escHtml(secName) + '</span></div>' +
            '</div>' +
            '<div class="grid grid-cols-2 gap-1.5 mb-1.5">' +
                '<button type="button" class="btn btn-xs btn-primary font-bold edit-path-btn gap-1"><i class="fa-solid fa-pen-to-square"></i> Edit Path</button>' +
                '<button type="button" class="btn btn-xs btn-error btn-outline font-bold delete-seg-btn gap-1"><i class="fa-solid fa-trash"></i> Delete</button>' +
            '</div>' +
            '<button type="button" class="btn btn-xs btn-ghost border border-white/15 w-full font-semibold build-as-player-btn gap-1"><i class="fa-solid fa-hammer text-[10px]"></i> Open in Build Drawer</button>';

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

        L.popup({ minWidth: 220, maxWidth: 280, className: 'admin-leaflet-popup' })
            .setLatLng(latlng)
            .setContent(popupContent)
            .openOn(map);
    }

    function confirmDeleteSegment(seg) {
        if (!confirm('Delete "' + (seg.name || 'Segment #' + seg.id) + '"? This cannot be undone.')) {
            return;
        }
        postJSON('/map/segment/delete/' + seg.id, {}, function(res) {
            if (res && res.success) {
                SEGMENTS = SEGMENTS.filter(function(s) { return Number(s.id) !== Number(seg.id); });
                updateHeaderCounts();
                renderSegments(buildMode);
                renderInspectorSegments();
                if (document.getElementById('buildDrawer').style.display !== 'none') {
                    populateList(activeTab);
                }
                showToast('Deleted "' + (seg.name || 'Segment') + '"', 'success');
            } else {
                showToast((res && res.error) ? res.error : 'Failed to delete segment', 'error');
            }
        });
    }

    /* ======================================================================
     * ADMIN INSPECTOR DRAWER (SEGMENTS DIRECTORY & SECTORS MANAGER)
     * ====================================================================== */

    function renderInspectorSectors() {
        var list = document.getElementById('sectorList');
        if (!list) return;
        if (!SECTORS.length) {
            list.innerHTML = '<p style="font-size:12px;color:#64748b;padding:8px 0">No sectors created for this mountain yet.</p>';
            return;
        }
        list.innerHTML = '';
        SECTORS.forEach(function(sec) {
            var bp = parsePoints(sec.boundary_points);
            var hasBoundary = bp.length >= 3;
            var segCountInSec = SEGMENTS.filter(function(s) { return Number(s.sector) === Number(sec.id); }).length;

            var row = document.createElement('div');
            row.className = 'build-item-card';
            row.style.cursor = 'default';
            row.style.marginBottom = '4px';
            row.innerHTML =
                '<div class="build-item-emblem" style="width:30px;height:30px;background:' + escHtml(sec.color || '#3b82f6') + '22;border:1.5px solid ' + escHtml(sec.color || '#3b82f6') + ';color:' + escHtml(sec.color || '#3b82f6') + '">' +
                    '<i class="fa-solid fa-layer-group text-xs"></i>' +
                '</div>' +
                '<div class="build-item-info">' +
                    '<div class="build-item-title-row">' +
                        '<span class="build-item-name">' + escHtml(sec.name) + '</span>' +
                        '<span style="font-size:10px;color:#64748b;font-family:monospace">#' + sec.id + '</span>' +
                    '</div>' +
                    '<div class="build-item-meta">' +
                        '<span>' + segCountInSec + ' runs</span>' +
                        '<span>• ' + (hasBoundary ? '✓ Boundary set' : 'No boundary') + '</span>' +
                    '</div>' +
                '</div>' +
                '<div class="flex items-center gap-1 flex-shrink-0">' +
                    '<button type="button" class="btn btn-ghost btn-xs draw-sec-boundary" title="Draw/Edit Sector Polygon Boundary on Map"><i class="fa-solid fa-draw-polygon ' + (hasBoundary ? 'text-info' : 'opacity-50') + '"></i></button>' +
                    '<button type="button" class="btn btn-ghost btn-xs toggle-sec-vis" title="Toggle Visibility"><i class="fa-solid fa-eye' + (Number(sec.visible) ? ' text-success' : '-slash opacity-40') + '"></i></button>' +
                    '<button type="button" class="btn btn-ghost btn-xs text-error delete-sec-btn" title="Delete Sector"><i class="fa-solid fa-trash"></i></button>' +
                '</div>';

            row.querySelector('.draw-sec-boundary').addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
                startEditSectorBoundary(sec);
            });
            row.querySelector('.toggle-sec-vis').addEventListener('click', function() {
                postJSON('/map/sector/toggle/' + sec.id, {}, function(res) {
                    if (res && res.success) {
                        sec.visible = res.visible ? 1 : 0;
                        renderInspectorSectors();
                        showToast(sec.name + ' is now ' + (sec.visible ? 'visible' : 'hidden'), 'info');
                    }
                });
            });
            row.querySelector('.delete-sec-btn').addEventListener('click', function() {
                if (!confirm('Delete sector "' + sec.name + '"? Segments in this sector will be reset to Default (0).')) return;
                postJSON('/map/sector/delete/' + sec.id, {}, function(res) {
                    if (res && res.success) {
                        SECTORS = SECTORS.filter(function(s) { return Number(s.id) !== Number(sec.id); });
                        SEGMENTS.forEach(function(sg) {
                            if (Number(sg.sector) === Number(sec.id)) sg.sector = 0;
                        });
                        renderInspectorSectors();
                        renderInspectorSegments();
                        renderSectorPolygons();
                        showToast('Sector deleted', 'success');
                    }
                });
            });

            list.appendChild(row);
        });
    }

    function renderInspectorSegments() {
        var list = document.getElementById('inspectorSegmentList');
        if (!list) return;

        var q  = (document.getElementById('inspectorSearch') ? document.getElementById('inspectorSearch').value : '').trim().toLowerCase();
        var ft = (document.getElementById('inspectorFilterType') ? document.getElementById('inspectorFilterType').value : 'all');

        var filtered = SEGMENTS.filter(function(s) {
            if (ft === 'lift' && s.type !== 'lift') return false;
            if (ft === 'slope' && s.type === 'lift') return false;
            if (ft === 'unassigned' && Number(s.sector || 0) !== 0) return false;
            if (q && (s.name || '').toLowerCase().indexOf(q) === -1) return false;
            return true;
        });

        if (!filtered.length) {
            list.innerHTML = '<p style="font-size:12px;color:#64748b;text-align:center;padding:24px 0">No segments match filter</p>';
            return;
        }

        list.innerHTML = '';
        filtered.forEach(function(seg) {
            var secName = getSectorName(seg.sector);
            var row = document.createElement('div');
            row.className = 'build-item-card';
            row.style.marginBottom = '4px';
            row.innerHTML =
                '<div class="flex items-center gap-2.5 min-w-0 cursor-pointer flex-1 seg-zoom-trigger">' +
                    buildEmblemHtml(seg) +
                    '<div class="build-item-info">' +
                        '<div class="build-item-title-row">' +
                            '<span class="build-item-name">' + escHtml(seg.name || 'Unnamed') + '</span>' +
                            buildDiffPillHtml(seg) +
                        '</div>' +
                        '<div class="build-item-meta">' +
                            '<span>' + Math.round(seg.length_meters || 0).toLocaleString() + ' ' + DIST_UNIT + '</span>' +
                            '<span>• ' + escHtml(secName) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<div class="flex items-center gap-1 flex-shrink-0">' +
                    '<button type="button" class="btn btn-ghost btn-xs seg-edit-trigger" title="Edit Path & Details"><i class="fa-solid fa-pen-to-square text-info"></i></button>' +
                    '<button type="button" class="btn btn-ghost btn-xs text-error seg-del-trigger" title="Delete Segment"><i class="fa-solid fa-trash"></i></button>' +
                '</div>';

            row.querySelector('.seg-zoom-trigger').addEventListener('click', function() {
                var layer = segmentLayers[seg.id];
                if (layer && typeof layer.getBounds === 'function') {
                    map.fitBounds(layer.getBounds(), { maxZoom: 2.5, padding: [60, 60], animate: true });
                    openAdminSegmentPopup(seg, layer.getBounds().getCenter());
                }
            });
            row.querySelector('.seg-edit-trigger').addEventListener('click', function() {
                var layer = segmentLayers[seg.id];
                if (layer && typeof layer.getBounds === 'function') {
                    map.fitBounds(layer.getBounds(), { maxZoom: 2, padding: [80, 80], animate: true });
                }
                startEditSegment(seg);
            });
            row.querySelector('.seg-del-trigger').addEventListener('click', function() {
                confirmDeleteSegment(seg);
            });

            list.appendChild(row);
        });
    }

    function bindAdmin() {
        var btnLift       = document.getElementById('btnNewLift');
        var btnSlope      = document.getElementById('btnNewSlope');
        var btnSave       = document.getElementById('toolBtnSave');
        var btnSaveNext   = document.getElementById('toolBtnSaveNext');
        var btnUndo       = document.getElementById('toolBtnUndo');
        var btnReverse    = document.getElementById('toolBtnReverse');
        var btnAppend     = document.getElementById('toolBtnAppendToggle');
        var btnCancel     = document.getElementById('toolBtnCancel');
        var btnSnap       = document.getElementById('btnToggleSnap');
        var btnSectors    = document.getElementById('btnToggleSectors');
        var btnInspector  = document.getElementById('btnOpenInspector');
        var closeInspBtn  = document.getElementById('closeInspectorBtn');

        if (btnLift)      btnLift.addEventListener('click', function() { startDraw('lift', false); });
        if (btnSlope)     btnSlope.addEventListener('click', function() { startDraw('slope', false); });
        if (btnSave)      btnSave.addEventListener('click', function() { saveEditor(false); });
        if (btnSaveNext)  btnSaveNext.addEventListener('click', function() { saveEditor(true); });
        if (btnUndo)      btnUndo.addEventListener('click', undoLastAction);
        if (btnReverse)   btnReverse.addEventListener('click', reverseEditorPoints);
        if (btnAppend)    btnAppend.addEventListener('click', function() { setAppendMode(!appendInEditMode); });
        if (btnCancel)    btnCancel.addEventListener('click', function() { cancelEditor(false); });

        if (btnSnap) {
            btnSnap.addEventListener('click', function() {
                snapEnabled = !snapEnabled;
                btnSnap.innerHTML = '<i class="fa-solid fa-magnet"></i> Snap: ' + (snapEnabled ? 'ON' : 'OFF');
                btnSnap.className = 'btn btn-xs gap-1 font-semibold ' + (snapEnabled ? 'btn-info btn-outline' : 'btn-ghost opacity-60');
                if (!snapEnabled) hideSnapIndicator();
            });
        }

        if (btnSectors) {
            btnSectors.addEventListener('click', function() {
                showSectorPolygons = !showSectorPolygons;
                btnSectors.classList.toggle('btn-warning', showSectorPolygons);
                btnSectors.classList.toggle('btn-ghost', !showSectorPolygons);
                renderSectorPolygons();
            });
        }

        if (btnInspector) {
            btnInspector.addEventListener('click', function() {
                closeDrawer();
                var insp = document.getElementById('adminInspectorDrawer');
                if (insp) {
                    insp.style.display = (insp.style.display === 'none' ? 'flex' : 'none');
                    syncDrawerLayoutState();
                    renderInspectorSectors();
                    renderInspectorSegments();
                }
            });
        }
        if (closeInspBtn) {
            closeInspBtn.addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
            });
        }

        var inspNewLift  = document.getElementById('inspectorNewLiftBtn');
        var inspNewSlope = document.getElementById('inspectorNewSlopeBtn');
        if (inspNewLift) {
            inspNewLift.addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
                startDraw('lift', false);
            });
        }
        if (inspNewSlope) {
            inspNewSlope.addEventListener('click', function() {
                document.getElementById('adminInspectorDrawer').style.display = 'none';
                syncDrawerLayoutState();
                startDraw('slope', false);
            });
        }

        var inspSearch = document.getElementById('inspectorSearch');
        var inspFilter = document.getElementById('inspectorFilterType');
        if (inspSearch) inspSearch.addEventListener('input', renderInspectorSegments);
        if (inspFilter) inspFilter.addEventListener('change', renderInspectorSegments);

        var diffEl      = document.getElementById('toolSegDiff');
        var slopeTypeEl = document.getElementById('toolSlopeType');
        if (diffEl) {
            diffEl.addEventListener('change', function() {
                if (editorMode) updateEditorDisplay(true);
            });
        }
        if (slopeTypeEl) {
            slopeTypeEl.addEventListener('change', function() {
                if (editorMode) updateEditorDisplay(true);
            });
        }

        // New Sector Form inside Inspector
        var newSecBtn    = document.getElementById('newSector');
        var newSecForm   = document.getElementById('newSectorForm');
        var cancelSecBtn = document.getElementById('cancelNewSectorBtn');
        var saveSecBtn   = document.getElementById('saveNewSectorBtn');

        if (newSecBtn && newSecForm) {
            newSecBtn.addEventListener('click', function() {
                newSecForm.style.display = 'block';
                document.getElementById('newSectorName').focus();
            });
        }
        if (cancelSecBtn && newSecForm) {
            cancelSecBtn.addEventListener('click', function() {
                newSecForm.style.display = 'none';
            });
        }
        if (saveSecBtn) {
            saveSecBtn.addEventListener('click', function() {
                var n = document.getElementById('newSectorName').value.trim();
                var col = document.getElementById('newSectorColor').value || '#3b82f6';
                if (!n) {
                    showToast('Please enter a sector name', 'warning');
                    return;
                }
                postJSON('/map/sector/create', { name: n, color: col }, function(res) {
                    if (res && res.success && res.sector) {
                        SECTORS.push(res.sector);
                        // Also add option to toolbar sector selects
                        ['toolActiveSector', 'toolSegSector'].forEach(function(selId) {
                            var sel = document.getElementById(selId);
                            if (sel) {
                                var opt = document.createElement('option');
                                opt.value = String(res.sector.id);
                                opt.textContent = res.sector.name;
                                sel.appendChild(opt);
                            }
                        });
                        document.getElementById('newSectorName').value = '';
                        newSecForm.style.display = 'none';
                        renderInspectorSectors();
                        showToast('Created sector "' + res.sector.name + '"', 'success');
                    }
                });
            });
        }

        var autoAssignBtn = document.getElementById('autoAssign');
        if (autoAssignBtn) {
            autoAssignBtn.addEventListener('click', function() {
                postJSON('/map/sector/auto-assign', {}, function(r) {
                    if (r && r.success) {
                        if (r.updated) {
                            SEGMENTS.forEach(function(seg) {
                                if (r.updated[seg.id]) seg.sector = r.updated[seg.id];
                            });
                        }
                        renderInspectorSectors();
                        renderInspectorSegments();
                        showToast('Auto-assigned ' + (r.assigned || 0) + ' segments to sectors!', 'success');
                    }
                });
            });
        }

        // Live Rubber-Band Cursor Guide & Magnetic Snapping Preview
        map.on('mousemove', function(e) {
            if (!editorMode) return;
            var isAppending = (editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode));
            if (!isAppending) {
                if (rubberBandLine) {
                    map.removeLayer(rubberBandLine);
                    rubberBandLine = null;
                }
                return;
            }

            var altKey = e.originalEvent && e.originalEvent.altKey;
            var targetLl = getSnappedLatLng(e.latlng, altKey);

            if (editorPoints.length > 0) {
                var lastPt = editorPoints[editorPoints.length - 1];
                var previewPts = [lastPt, [targetLl.lat, targetLl.lng]];
                var accent = getActiveEditorAccentColor();
                if (!rubberBandLine) {
                    rubberBandLine = L.polyline(previewPts, {
                        color: accent,
                        weight: 3,
                        opacity: 0.65,
                        dashArray: '4, 6',
                        interactive: false
                    }).addTo(map);
                } else {
                    rubberBandLine.setLatLngs(previewPts);
                    rubberBandLine.setStyle({ color: accent });
                }
            }
        });

        // Map Click to Add Points (with double-click deduplication guard)
        map.on('click', function(e) {
            if (!editorMode) return;
            var isAppending = (editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode));
            if (!isAppending) {
                selectedVertexIdx = -1;
                updateEditorDisplay(true);
                return;
            }

            var now = Date.now();
            var altKey = e.originalEvent && e.originalEvent.altKey;
            var targetLl = getSnappedLatLng(e.latlng, altKey);

            // Deduplicate rapid double-click so double-clicking to finish doesn't stack 2 identical vertices
            if (now - lastClickTime < 280 && editorPoints.length > 0) {
                var prev = editorPoints[editorPoints.length - 1];
                var dy = prev[0] - targetLl.lat;
                var dx = prev[1] - targetLl.lng;
                if (Math.sqrt(dy * dy + dx * dx) < 1.5) {
                    return;
                }
            }
            lastClickTime = now;

            pushUndoState();
            editorPoints.push([targetLl.lat, targetLl.lng]);
            selectedVertexIdx = editorPoints.length - 1;
            updateEditorDisplay(true);
        });

        map.on('dblclick', function(e) {
            if (!editorMode) return;
            if (L.DomEvent) L.DomEvent.stopPropagation(e);
            if (editorMode === 'draw' && editorPoints.length >= 2) {
                saveEditor(false);
            }
        });

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            var tag = e.target && e.target.tagName ? e.target.tagName.toLowerCase() : '';
            if (tag === 'input' || tag === 'select' || tag === 'textarea') {
                if (e.key === 'Enter' && editorMode) {
                    e.preventDefault();
                    saveEditor(e.shiftKey);
                } else if (e.key === 'Escape' && editorMode) {
                    e.preventDefault();
                    cancelEditor(false);
                }
                return;
            }

            if (editorMode) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEditor(e.shiftKey);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEditor(false);
                } else if ((e.key === 'z' || e.key === 'Z') && (e.metaKey || e.ctrlKey || !e.shiftKey)) {
                    e.preventDefault();
                    undoLastAction();
                } else if (e.key === 'r' || e.key === 'R') {
                    e.preventDefault();
                    reverseEditorPoints();
                } else if ((e.key === 'Backspace' || e.key === 'Delete') && selectedVertexIdx !== -1) {
                    e.preventDefault();
                    deleteVertexAt(selectedVertexIdx);
                }
            } else if (IS_ADMIN) {
                if (e.key === 'l' || e.key === 'L') {
                    e.preventDefault();
                    startDraw('lift', false);
                } else if (e.key === 's' || e.key === 'S') {
                    e.preventDefault();
                    startDraw('slope', false);
                } else if (e.key === 'f' || e.key === 'F') {
                    e.preventDefault();
                    map.fitBounds(mapBounds, { animate: true });
                }
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
