import * as L from 'leaflet';
import type { BuiltItem, MapConfig, MapSegment, ResortMapProps, Sector, EditorState } from './types';

// Palette mapping
const COLORS: Record<string, string> = {
  lift: '#f59e0b',
  green: '#22c55e',
  blue: '#3b82f6',
  black: '#1e293b',
  double_black: '#dc2626',
  snowpark: '#a855f7',
  terrain_park: '#a855f7',
  crosscountry: '#14b8a6',
  luge: '#ec4899',
  default: '#94a3b8',
};

export interface MapEngineCallbacks {
  onSelectSegment: (seg: MapSegment | null) => void;
  onOpenBuildDrawer: (tab: 'lift' | 'slope') => void;
  onCloseBuildDrawer: () => void;
  onEditorStateChange: (state: EditorState) => void;
  onSegmentsUpdate: (segments: MapSegment[]) => void;
  onSectorsUpdate: (sectors: Sector[]) => void;
  onBuiltUpdate: (builtIds: (string | number)[], builtItems: Record<string, BuiltItem>) => void;
  onCashUpdate: (cash: number) => void;
  onToast: (msg: string, type?: 'success' | 'error' | 'warning' | 'info') => void;
}

// Two clicks on the same spot within this time are a double-click
const DOUBLE_CLICK_MS = 400;

function readShowAvailable(): boolean {
  try {
    return localStorage.getItem('map_show_available') !== '0';
  } catch {
    return true;
  }
}

interface LastDraw {
  sector: number;
  slopeType: string;
  difficulty: string;
}

// Sectors belong to one trail map, so each map keeps its own last settings
function lastDrawKey(mapName: string): string {
  return 'map_editor_last:' + mapName;
}

function readLastDraw(mapName: string): LastDraw {
  const fallback: LastDraw = { sector: 0, slopeType: 'downhill', difficulty: 'blue' };
  try {
    const raw = JSON.parse(localStorage.getItem(lastDrawKey(mapName)) || 'null');
    if (!raw || typeof raw !== 'object') return fallback;
    return {
      sector: Number(raw.sector) || 0,
      slopeType: typeof raw.slopeType === 'string' && raw.slopeType ? raw.slopeType : fallback.slopeType,
      difficulty: typeof raw.difficulty === 'string' && raw.difficulty ? raw.difficulty : fallback.difficulty,
    };
  } catch {
    return fallback;
  }
}

/** Closest point to p on the line a-b, in the map's [lat, lng] (= [y, x]) space */
function closestOnLine(p: [number, number], a: [number, number], b: [number, number]): [number, number] {
  const dy = b[0] - a[0];
  const dx = b[1] - a[1];
  const len2 = dy * dy + dx * dx;
  if (len2 === 0) return a;
  const t = Math.max(0, Math.min(1, ((p[0] - a[0]) * dy + (p[1] - a[1]) * dx) / len2));
  return [a[0] + t * dy, a[1] + t * dx];
}

/** Marker `drag` events carry the pointer event at runtime (Leaflet's Draggable sets it); its type omits it */
type DragEventWithPointer = L.MarkerMoveEvent & { originalEvent?: PointerEvent };

export function createMapEngine(props: ResortMapProps, callbacks: MapEngineCallbacks) {
  // Both set by init(); every other function runs after it (guards check `map` for calls that come early)
  let map!: L.Map;
  let mapBounds!: L.LatLngBoundsExpression;
  let segmentLayers: Record<string, L.Polyline> = {};
  let hitLayers: Record<string, L.Polyline> = {};
  let stationMarkers: L.Marker[] = [];
  let sectorPolygons: L.Polygon[] = [];
  let showSectorPolygons = false;
  let selectedSegId: number | string | null = null;
  let buildMode: string | null = null;
  let mapFilter = 'all';
  let selectionLayers: L.Layer[] = [];
  // Set when the player drags, scrolls or touches the map; automatic fitting stops then
  let userMoved = false;
  // Players see the runs they can build unless they switch them off (remembered per browser)
  let showAvailable = readShowAvailable();

  // State mirror
  let segments = [...props.segments];
  let sectors = [...props.sectors];
  let builtIds = [...props.builtSegmentIds];
  let builtItems = { ...props.builtItems };
  let releasedIds = [...props.releasedSectorIds];
  let userCash = props.userCash;
  const isAdmin = props.isAdmin;
  const mapConfig = props.mapConfig;
  const distUnit = props.distUnit;

  let csrfName = props.csrf.name;
  let csrfHash = props.csrf.hash;

  // Admin Editor State
  let editorMode: 'draw' | 'edit' | 'sector_boundary' | null = null;
  let editorType: 'lift' | 'slope' = 'lift';
  let editingSegId: number | string | null = null;
  let editingSectorId: number | null = null;
  let appendInEditMode = false;
  let snapEnabled = true;
  let selectedVertexIdx = -1;
  let editorPoints: [number, number][] = [];
  let undoStack: [number, number][][] = [];
  let redoStack: [number, number][][] = [];
  // Esc on a line with work in it only arms the discard; a second Esc before this time cancels
  let discardArmedUntil = 0;
  let editorLine: L.Polyline | L.Polygon | null = null;
  let rubberBandLine: L.Polyline | null = null;
  let snapMarker: L.Marker | null = null;
  let currentSnapTarget: { lat: number; lng: number; label: string } | null = null;
  let vertexMarkers: L.Marker[] = [];
  let midpointMarkers: L.Marker[] = [];
  let lastClickTime = 0;
  // A save is on its way: Enter or a double-click again must not post the line twice
  let saving = false;

  // A new line starts with the sector, slope type and difficulty used last (remembered per browser)
  const lastDraw = readLastDraw(props.mapConfig.name);
  // A sector that is not on this map (deleted since) falls back to Default (0)
  if (!props.sectors.some((sec) => Number(sec.id) === lastDraw.sector)) lastDraw.sector = 0;
  let editorSegName = '';
  let editorSegSector = lastDraw.sector;
  let editorSlopeType = lastDraw.slopeType;
  let editorSegDiff = lastDraw.difficulty;

  function rememberDrawSettings() {
    lastDraw.sector = editorSegSector;
    lastDraw.slopeType = editorSlopeType;
    lastDraw.difficulty = editorSegDiff;
    try {
      localStorage.setItem(lastDrawKey(mapConfig.name), JSON.stringify(lastDraw));
    } catch {}
  }

  function escHtml(str: any): string {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function parsePoints(raw: any): [number, number][] {
    if (!raw) return [];
    let pts = raw;
    if (typeof raw === 'string') {
      try {
        pts = JSON.parse(raw);
      } catch {
        return [];
      }
    }
    if (!Array.isArray(pts)) return [];
    // Leaflet 2 throws on coordinates that aren't numbers; LatLng.validate() accepts exactly what it takes,
    // so a broken point is dropped instead of being drawn at the map's corner (0, 0)
    const valid = (a: unknown, b: unknown) => L.LatLng.validate(a as number, b as number);
    return pts
      .map((p): [any, any] | null => {
        if (Array.isArray(p)) return [p[0], p[1]];
        if (p && typeof p === 'object') return [p.lat ?? p[0], p.lng ?? p[1]];
        return null;
      })
      .filter((p): p is [any, any] => p !== null && p[0] !== null && p[1] !== null && p[0] !== '' && p[1] !== '' && valid(p[0], p[1]))
      .map((p): [number, number] => [Number(p[0]), Number(p[1])]);
  }

  function isSegmentBuilt(segId: number | string): boolean {
    const sId = String(segId);
    const nId = Number(segId);
    return builtIds.some((b) => String(b) === sId || Number(b) === nId);
  }

  function isSegmentReleased(seg: MapSegment): boolean {
    if (isAdmin) return true;
    if (!releasedIds || releasedIds.length === 0) return true;
    const secVal = Number(seg.sector || 0);
    if (!secVal || secVal === 0) return true;
    return releasedIds.some((r) => Number(r) === secVal);
  }

  function segColor(s: MapSegment): string {
    if (s.type === 'lift') return COLORS.lift;
    const d = s.difficulty || '';
    if (d && COLORS[d]) return COLORS[d];
    if (s.type && COLORS[s.type]) return COLORS[s.type];
    return COLORS.default;
  }

  function getSectorName(secId: any): string {
    if (!secId || secId === '0' || secId === 0) return 'Default (0)';
    const sec = sectors.find((s) => String(s.id) === String(secId) || s.name === String(secId));
    return sec ? sec.name : 'Sector #' + secId;
  }

  function calcLengthMeters(points: [number, number][]): number {
    if (!points || points.length < 2) return 0;
    let total = 0;
    for (let i = 1; i < points.length; i++) {
      const dy = points[i][0] - points[i - 1][0];
      const dx = points[i][1] - points[i - 1][1];
      total += Math.sqrt(dy * dy + dx * dx);
    }
    return Math.max(1, Math.round(total * 15.0));
  }

  function pushUndoState() {
    undoStack.push(editorPoints.map((p) => [p[0], p[1]]));
    if (undoStack.length > 40) undoStack.shift();
    redoStack = [];
    discardArmedUntil = 0;
  }

  /** Adds a point where the admin clicked (snapped unless Alt is held). A lift is a straight line: its second point saves it. */
  function addEditorPoint(latlng: L.LatLng, altKey: boolean, shiftKey: boolean) {
    const targetLl = getSnappedLatLng(latlng, altKey);

    // The two clicks of a double-click land on the same spot: keep one point, and finish a new line
    const now = Date.now();
    if (now - lastClickTime < DOUBLE_CLICK_MS && editorPoints.length > 0) {
      const prev = editorPoints[editorPoints.length - 1];
      const a = map.latLngToContainerPoint(new L.LatLng(prev[0], prev[1]));
      const b = map.latLngToContainerPoint(targetLl);
      if (a.distanceTo(b) < 6) {
        finishOnDoubleClick();
        return;
      }
    }
    lastClickTime = now;

    pushUndoState();
    editorPoints.push([targetLl.lat, targetLl.lng]);
    selectedVertexIdx = editorPoints.length - 1;
    updateEditorDisplay(true);
    notifyEditorState();

    // Shift-click keeps a lift open for a bent line
    if (editorMode === 'draw' && editorType === 'lift' && editorPoints.length === 2 && !shiftKey) {
      saveEditor(false);
    }
  }

  function finishOnDoubleClick() {
    lastClickTime = 0;
    if (editorMode === 'draw' && editorPoints.length >= 2) saveEditor(false);
  }

  function isAppending(): boolean {
    return editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode);
  }

  function notifyEditorState() {
    callbacks.onEditorStateChange({
      active: editorMode !== null,
      mode: editorMode,
      type: editorType,
      segId: editingSegId,
      sectorId: editingSectorId,
      name: editorSegName,
      sector: editorSegSector,
      slopeType: editorSlopeType,
      difficulty: editorSegDiff,
      liveLength: calcLengthMeters(editorPoints),
      pointCount: editorPoints.length,
      appendMode: appendInEditMode,
      snapEnabled,
      showSectorPolygons,
      snapStatus: currentSnapTarget ? currentSnapTarget.label : null,
    });
  }

  function init(container: HTMLElement) {
    if (map) return;

    mapBounds = [
      [0, 0],
      [mapConfig.height, mapConfig.width],
    ];

    map = new L.Map(container, {
      crs: L.SimpleCRS,
      minZoom: -3,
      maxZoom: 4,
      zoomSnap: 0.25,
      zoomDelta: 0.5,
      zoomControl: true,
      attributionControl: false,
    }).setView([mapConfig.height / 2, mapConfig.width / 2], 0);

    initProgressiveOverlays();

    map.fitBounds(mapBounds);
    // A full mountain's height of slack below, so on a tall phone the map can sit above the bottom sheet
    // (Leaflet centres the map in the frame when the frame is taller than these bounds)
    map.setMaxBounds([
      [-mapConfig.height * 1.0, -mapConfig.width * 0.2],
      [mapConfig.height * 1.2, mapConfig.width * 1.2],
    ]);

    renderSegments();

    // Leaflet 2 puts originalEvent on movestart/zoomstart only when the player moved the view
    // (drag, wheel, pinch, keyboard, zoom buttons), not for our own fitBounds/setView calls
    map.on('movestart zoomstart', (e) => {
      if (e.originalEvent) userMoved = true;
    });

    // Fullscreen button under the zoom buttons (Leaflet 2 core); fills the window where the Fullscreen API is missing
    new L.FullscreenControl({ position: 'topleft', enterTitle: 'Full screen map', exitTitle: 'Exit full screen' }).addTo(map);

    // Map Event Listeners for Editor
    map.on('pointermove', (e) => {
      if (!editorMode) return;
      if (!isAppending()) {
        if (rubberBandLine) {
          map.removeLayer(rubberBandLine);
          rubberBandLine = null;
        }
        return;
      }

      const altKey = !!e.originalEvent?.altKey;
      const targetLl = getSnappedLatLng(e.latlng, altKey);

      if (editorPoints.length > 0) {
        const lastPt = editorPoints[editorPoints.length - 1];
        const previewPts: L.LatLngExpression[] = [lastPt, [targetLl.lat, targetLl.lng]];
        const accent = getActiveEditorAccentColor();
        if (!rubberBandLine) {
          rubberBandLine = new L.Polyline(previewPts, {
            color: accent,
            weight: 3,
            opacity: 0.65,
            dashArray: '4, 6',
            interactive: false,
          }).addTo(map);
        } else {
          rubberBandLine.setLatLngs(previewPts);
          rubberBandLine.setStyle({ color: accent });
        }
      }
    });

    map.on('click', (e) => {
      if (!editorMode) return;
      if (!isAppending()) {
        selectedVertexIdx = -1;
        updateEditorDisplay(true);
        return;
      }
      const orig = e.originalEvent;
      addEditorPoint(e.latlng, !!(orig && orig.altKey), !!(orig && orig.shiftKey));
    });

    map.on('dblclick', (e) => {
      if (!editorMode) return;
      // The second click already finished the line (addEditorPoint / vertex click)
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', handleKeydown);

  }

  function handleKeydown(e: KeyboardEvent) {
    const tag = (e.target as HTMLElement)?.tagName?.toLowerCase() || '';
    if (tag === 'input' || tag === 'select' || tag === 'textarea') {
      if (e.key === 'Enter' && editorMode) {
        e.preventDefault();
        saveEditor(e.shiftKey);
      } else if (e.key === 'Escape' && editorMode) {
        e.preventDefault();
        escapeEditor();
      }
      return;
    }

    if (editorMode) {
      if (e.key === 'Enter') {
        e.preventDefault();
        saveEditor(e.shiftKey);
      } else if (e.key === 'Escape') {
        e.preventDefault();
        escapeEditor();
      } else if (e.key === 'z' || e.key === 'Z') {
        e.preventDefault();
        if (e.shiftKey) redo();
        else undo();
      } else if ((e.key === 'y' || e.key === 'Y') && (e.metaKey || e.ctrlKey)) {
        e.preventDefault();
        redo();
      } else if (e.key === 'r' || e.key === 'R') {
        e.preventDefault();
        reverse();
      } else if ((e.key === 'Backspace' || e.key === 'Delete') && selectedVertexIdx !== -1) {
        e.preventDefault();
        deleteVertexAt(selectedVertexIdx);
      }
    } else if (isAdmin) {
      if (e.key === 'l' || e.key === 'L') {
        e.preventDefault();
        startDraw('lift');
      } else if (e.key === 's' || e.key === 'S') {
        e.preventDefault();
        startDraw('slope');
      } else if (e.key === 'f' || e.key === 'F') {
        e.preventDefault();
        fitBounds();
      }
    }
  }

  /** Esc cancels at once on a fresh line; with work in it, the first press only warns */
  function escapeEditor() {
    const hasWork = editorMode === 'draw' || editorMode === 'sector_boundary' ? editorPoints.length >= 3 : undoStack.length > 0;
    if (!hasWork || Date.now() < discardArmedUntil) {
      cancelEditor();
      return;
    }
    discardArmedUntil = Date.now() + 2500;
    callbacks.onToast(editorMode === 'edit' ? 'Press Esc again to discard your changes' : 'Press Esc again to discard this line', 'warning');
  }

  function initProgressiveOverlays() {
    const basePath = mapConfig.image.replace(/\.jpg$/i, '');
    const webp = mapConfig.webp ?? { low: false, med: false, full: false };
    const imgLow = basePath + (webp.low ? '_low.webp' : '_low.jpg');
    const imgMed = basePath + (webp.med ? '_med.webp' : '_med.jpg');
    const imgFull = webp.full ? basePath + '.webp' : mapConfig.image;

    new L.ImageOverlay(imgLow, mapBounds, { zIndex: 1 }).addTo(map);

    // Tiles: the browser fetches and decodes only the 256 px pieces on screen, instead of a 1.3 MB image and
    // then a 100+ megapixel one that froze the page while it decoded. The small image stays underneath, so
    // there's never a blank map while tiles arrive.
    if (mapConfig.tiles) {
      new L.TileLayer(mapConfig.tiles.url, {
        bounds: new L.LatLngBounds(mapBounds),
        minNativeZoom: 0,
        maxNativeZoom: mapConfig.tiles.maxZoom,
        minZoom: map.getMinZoom(),
        maxZoom: map.getMaxZoom(),
        noWrap: true,
        keepBuffer: 4,
        zIndex: 2,
      }).addTo(map);
      return;
    }

    let fullOverlay: L.ImageOverlay | null = null;
    let fullLoading = false;

    const medPreload = new Image();
    medPreload.onload = () => {
      new L.ImageOverlay(imgMed, mapBounds, { zIndex: 2 }).addTo(map);
    };
    medPreload.src = imgMed;

    map.on('zoomend', () => {
      if (map.getZoom() >= 1.5 && !fullOverlay && !fullLoading) {
        fullLoading = true;
        const hdBadge = document.getElementById('hdBadge');
        if (hdBadge) hdBadge.style.display = 'inline-flex';

        const fullPreload = new Image();
        fullPreload.onload = () => {
          fullOverlay = new L.ImageOverlay(imgFull, mapBounds, { zIndex: 3 }).addTo(map);
          if (hdBadge) hdBadge.style.display = 'none';
        };
        fullPreload.onerror = () => {
          if (hdBadge) hdBadge.style.display = 'none';
        };
        fullPreload.src = imgFull;
      }
    });
  }

  /**
   * How a run is drawn. Built runs are solid in their trail colour (lifts dashed amber). Runs a player
   * can build are thin and dotted in their own colour, so difficulty shows before anything is built.
   */
  function segStyle(seg: MapSegment) {
    const built = isSegmentBuilt(seg.id);
    if (isAdmin) {
      return { color: segColor(seg), weight: built ? 5 : 4.5, opacity: built ? 0.95 : 0.85, dashArray: built ? (seg.type === 'lift' ? '10, 5' : null) : '6, 5' };
    }
    if (built) {
      return { color: segColor(seg), weight: 5, opacity: 0.95, dashArray: seg.type === 'lift' ? '10, 5' : null };
    }
    // The tab the build panel is on stands out; the other type stays visible but quieter
    const focused = !buildMode || (buildMode === 'lift') === (seg.type === 'lift');
    return { color: segColor(seg), weight: focused ? 4 : 3, opacity: focused ? 0.75 : 0.35, dashArray: '2, 7' };
  }

  function clearSelectionMarks() {
    selectionLayers.forEach((l) => map && map.removeLayer(l));
    selectionLayers = [];
  }

  /** A halo under the selected run and a dot on each end, so it reads at any zoom */
  function drawSelectionMarks(seg: MapSegment) {
    if (!map) return;
    clearSelectionMarks();
    const ll = parsePoints(seg.points);
    if (ll.length < 2) return;
    selectionLayers.push(
      new L.Polyline(ll, { color: '#ffffff', weight: 14, opacity: 0.55, lineCap: 'round', lineJoin: 'round', interactive: false }).addTo(map)
    );
    selectionLayers.push(
      new L.Polyline(ll, { color: segColor(seg), weight: 7, opacity: 1, lineCap: 'round', lineJoin: 'round', interactive: false }).addTo(map)
    );
    [ll[0], ll[ll.length - 1]].forEach((pt) => {
      selectionLayers.push(
        new L.CircleMarker(pt, { radius: 6, color: '#ffffff', weight: 2, fillColor: segColor(seg), fillOpacity: 1, interactive: false }).addTo(map)
      );
    });
  }

  function renderSegments(newBuildMode?: string | null) {
    if (!map) return;

    if (typeof newBuildMode !== 'undefined') {
      buildMode = newBuildMode;
    }

    Object.values(segmentLayers).forEach((l) => map.removeLayer(l));
    Object.values(hitLayers).forEach((l) => map.removeLayer(l));
    stationMarkers.forEach((m) => map.removeLayer(m));
    segmentLayers = {};
    hitLayers = {};
    stationMarkers = [];

    segments.forEach((seg) => {
      if (editingSegId && String(seg.id) === String(editingSegId)) return;

      const ll = parsePoints(seg.points);
      if (ll.length < 2) return;

      const built = isSegmentBuilt(seg.id);

      // Filter check
      if (mapFilter === 'lift' && seg.type !== 'lift') return;
      if (mapFilter === 'slope' && seg.type === 'lift') return;
      if (mapFilter === 'built' && !built) return;
      if (mapFilter === 'unassigned' && Number(seg.sector || 0) !== 0) return;

      // Players see what they built, plus every run they can build unless they've hidden those
      if (!isAdmin && !built && (!showAvailable || !isSegmentReleased(seg))) return;

      const style = segStyle(seg);
      const { weight, opacity } = style;

      const line = new L.Polyline(ll, {
        ...style,
        lineCap: 'round',
        lineJoin: 'round',
        interactive: false,
      }).addTo(map);

      // Wide invisible line so a finger can hit a thin run
      const hitLine = new L.Polyline(ll, {
        color: '#000',
        weight: 24,
        opacity: 0.001,
        interactive: true,
      }).addTo(map);

      const secName = getSectorName(seg.sector);
      const diffLabel = seg.difficulty ? ' • ' + seg.difficulty.replace('_', ' ').toUpperCase() : '';
      const tooltipHtml =
        '<div style="text-align:center;padding:2px 6px">' +
        '<b>' +
        escHtml(seg.name || seg.type) +
        '</b>' +
        '<br><span style="font-family:monospace">' +
        Math.round(seg.length_meters || 0) +
        ' ' +
        distUnit +
        '</span>' +
        diffLabel +
        (isAdmin
          ? '<br><span style="opacity:.75;font-size:10px">' + escHtml(secName) + ' • Click to edit</span>'
          : !built
            ? '<br><span style="font-size:11px;font-weight:600">Not built · click for the price</span>'
            : '<br><span style="font-size:11px;font-weight:600">Yours · click for details</span>') +
        '</div>';

      hitLine.bindTooltip(tooltipHtml, { sticky: true });

      hitLine.on('pointerover', () => {
        if (editorMode) return;
        if (!selectedSegId || String(selectedSegId) !== String(seg.id)) {
          line.setStyle({ weight: weight + 3, opacity: 1 });
        }
      });

      hitLine.on('pointerout', () => {
        if (editorMode) return;
        if (!selectedSegId || String(selectedSegId) !== String(seg.id)) {
          line.setStyle({ weight, opacity });
        }
      });

      hitLine.on('click', (e) => {
        if (L.DomEvent) L.DomEvent.stopPropagation(e);
        if (editorMode) {
          if (isAppending()) {
            const orig = e.originalEvent;
            addEditorPoint(e.latlng, !!(orig && orig.altKey), !!(orig && orig.shiftKey));
          }
          return;
        }

        if (isAdmin) {
          openAdminSegmentPopup(seg, e.latlng);
        } else {
          // Built or not, the panel shows it: the build view or the run's details
          selectSegment(seg);
        }
      });

      segmentLayers[seg.id] = line;
      hitLayers[seg.id] = hitLine;
    });

    if (!isAdmin && selectedSegId !== null) {
      const sel = segments.find((s) => String(s.id) === String(selectedSegId));
      if (sel) drawSelectionMarks(sel);
    }

    renderSectorPolygons();
  }

  function renderSectorPolygons() {
    if (!map) return;

    sectorPolygons.forEach((p) => map.removeLayer(p));
    sectorPolygons = [];
    if (!showSectorPolygons) return;

    sectors.forEach((sec) => {
      if (editingSectorId && Number(sec.id) === Number(editingSectorId)) return;
      const pts = parsePoints(sec.boundary_points);
      if (pts.length < 3) return;
      const col = sec.color || '#3b82f6';
      const poly = new L.Polygon(pts, {
        color: col,
        weight: 2,
        dashArray: '6, 4',
        fillColor: col,
        fillOpacity: 0.14,
        interactive: false,
      }).addTo(map);

      poly.bindTooltip('<b>' + escHtml(sec.name) + '</b>', {
        permanent: true,
        direction: 'center',
        className: 'badge badge-neutral badge-xs opacity-85',
      });
      sectorPolygons.push(poly);
    });
  }

  /** Replace a built lift with another system on the same line (ResortMap::swapLift). The dialog asks first. */
  function swapLift(seg: MapSegment, liftType: string, seats: number, cb?: (ok: boolean) => void) {
    const item = builtItems[seg.id];
    if (!item || !item.id) {
      if (cb) cb(false);
      return;
    }
    const name = seg.name || 'this lift';
    postJSON('/map/upgrade/' + item.id, { lift_type: liftType, seats }, (res) => {
      if (!res || !res.success) {
        callbacks.onToast(res?.error || 'Could not replace ' + name + '. Nothing was changed.', 'error');
        if (cb) cb(false);
        return;
      }
      if (res.item) builtItems[seg.id] = res.item;
      if (typeof res.new_cash === 'number') {
        userCash = res.new_cash;
        callbacks.onCashUpdate(userCash);
      }
      callbacks.onBuiltUpdate([...builtIds], { ...builtItems });
      renderSegments(buildMode);
      callbacks.onToast(res.message || name + ' replaced.', 'success');
      if (cb) cb(true);
    });
  }

  /** Demolish a built run for its salvage refund. The panel asks first; this only does it. */
  function demolish(seg: MapSegment, cb?: (ok: boolean) => void) {
    const item = builtItems[seg.id];
    if (!item || !item.id) {
      if (cb) cb(false);
      return;
    }
    const name = seg.name || (seg.type === 'lift' ? 'this lift' : 'this slope');
    postJSON('/map/demolish/' + item.id, {}, (res) => {
      if (!res || !res.success) {
        callbacks.onToast(res?.error || 'Could not demolish ' + name + '. Nothing was changed.', 'error');
        if (cb) cb(false);
        return;
      }
      builtIds = builtIds.filter((id) => String(id) !== String(seg.id) && Number(id) !== Number(seg.id));
      delete builtItems[seg.id];
      if (typeof res.new_cash === 'number') {
        userCash = res.new_cash;
        callbacks.onCashUpdate(userCash);
      }
      callbacks.onBuiltUpdate([...builtIds], { ...builtItems });
      renderSegments(buildMode);
      callbacks.onToast(res.message || name + ' demolished.', 'success');
      if (cb) cb(true);
    });
  }

  function openAdminSegmentPopup(seg: MapSegment, latlng: L.LatLngExpression) {
    const c = segColor(seg);
    const secName = getSectorName(seg.sector);
    const diffBadge = seg.difficulty
      ? '<span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded" style="background:' +
        c +
        ';color:#fff">' +
        escHtml(seg.difficulty.replace('_', ' ')) +
        '</span>'
      : '';
    const popupContent = document.createElement('div');
    popupContent.className = 'admin-segment-popover p-1.5 text-xs min-w-[220px] text-foreground';
    popupContent.innerHTML =
      '<div class="flex items-center justify-between gap-2 mb-2 pb-1.5 border-b border-white/10">' +
      '<div class="font-bold text-sm flex items-center gap-1.5">' +
      (seg.type === 'lift'
        ? '<i class="fa-solid fa-elevator text-warning"></i>'
        : '<i class="fa-solid fa-person-skiing text-success"></i>') +
      ' <span class="truncate max-w-[145px]">' +
      escHtml(seg.name || 'Unnamed') +
      '</span>' +
      '</div>' +
      diffBadge +
      '</div>' +
      '<div class="space-y-1 mb-3 text-muted text-[11px]">' +
      '<div class="flex items-center justify-between"><span>ID / Type:</span> <span class="font-mono text-foreground">#' +
      seg.id +
      ' • <b class="capitalize">' +
      escHtml(seg.type) +
      '</b></span></div>' +
      '<div class="flex items-center justify-between"><span>Length:</span> <span class="font-mono font-bold text-foreground">' +
      Math.round(seg.length_meters || 0) +
      ' ' +
      distUnit +
      '</span></div>' +
      '<div class="flex items-center justify-between"><span>Sector:</span> <span class="text-foreground font-semibold">' +
      escHtml(secName) +
      '</span></div>' +
      '</div>' +
      '<div class="grid grid-cols-2 gap-1.5 mb-1.5">' +
      '<button type="button" class="py-1 px-2 rounded bg-accent text-accent-foreground font-semibold text-xs flex items-center justify-center gap-1 edit-path-btn cursor-pointer"><i class="fa-solid fa-pen-to-square"></i> Edit Path</button>' +
      '<button type="button" class="py-1 px-2 rounded border border-danger/40 text-danger hover:bg-danger/10 font-semibold text-xs flex items-center justify-center gap-1 delete-seg-btn cursor-pointer"><i class="fa-solid fa-trash"></i> Delete</button>' +
      '</div>' +
      '<button type="button" class="w-full py-1 px-2 rounded border border-white/15 hover:bg-white/5 text-foreground text-xs font-semibold flex items-center justify-center gap-1 build-as-player-btn cursor-pointer"><i class="fa-solid fa-hammer text-[10px]"></i> Open in Build Drawer</button>';

    popupContent.querySelector('.edit-path-btn')?.addEventListener('click', () => {
      map.closePopup();
      startEditSegment(seg);
    });
    popupContent.querySelector('.delete-seg-btn')?.addEventListener('click', () => {
      map.closePopup();
      confirmDeleteSegment(seg);
    });
    popupContent.querySelector('.build-as-player-btn')?.addEventListener('click', () => {
      map.closePopup();
      selectSegment(seg);
    });

    new L.Popup({ minWidth: 220, maxWidth: 280, className: 'admin-leaflet-popup' })
      .setLatLng(latlng)
      .setContent(popupContent)
      .openOn(map);
  }

  function confirmDeleteSegment(seg: MapSegment) {
    if (!confirm('Delete "' + (seg.name || 'Segment #' + seg.id) + '"? This cannot be undone.')) {
      return;
    }
    postJSON('/map/segment/delete/' + seg.id, {}, (res) => {
      if (res && res.success) {
        segments = segments.filter((s) => String(s.id) !== String(seg.id));
        callbacks.onSegmentsUpdate(segments);
        renderSegments(buildMode);
        callbacks.onToast('Deleted "' + (seg.name || 'Segment') + '"', 'success');
      } else {
        callbacks.onToast(res?.error || 'Failed to delete segment', 'error');
      }
    });
  }

  function selectSegment(target: MapSegment | number | string) {
    const seg =
      typeof target === 'object' && target !== null && 'id' in target
        ? target
        : segments.find((s) => String(s.id) === String(target));
    if (!seg) return;
    deselectSegment();
    selectedSegId = seg.id;
    const targetTab = seg.type === 'lift' ? 'lift' : 'slope';

    if (segmentLayers[seg.id]) {
      if (isAdmin) {
        segmentLayers[seg.id].setStyle({ weight: 8, opacity: 1, color: '#38bdf8', dashArray: null });
      } else {
        drawSelectionMarks(seg);
      }
      try {
        if (typeof segmentLayers[seg.id].getBounds === 'function') {
          // On a phone the panel covers the lower part of the map: keep the run in the part still showing
          const phone = window.matchMedia('(max-width: 767px)').matches;
          const bounds = segmentLayers[seg.id].getBounds();
          if (phone) {
            map.fitBounds(bounds, { maxZoom: map.getZoom(), paddingTopLeft: [24, 24], paddingBottomRight: [24, Math.round(window.innerHeight * 0.55)], animate: true });
          } else {
            map.panTo(bounds.getCenter(), { animate: true });
          }
        }
      } catch {}
    }

    callbacks.onOpenBuildDrawer(targetTab);
    callbacks.onSelectSegment(seg);
  }

  function deselectSegment() {
    if (selectedSegId && segmentLayers[selectedSegId]) {
      const s = segments.find((x) => String(x.id) === String(selectedSegId));
      if (s) segmentLayers[selectedSegId].setStyle(segStyle(s));
    }
    clearSelectionMarks();
    selectedSegId = null;
    callbacks.onSelectSegment(null);
  }

  function zoomToSegment(segId: number | string) {
    const layer = segmentLayers[segId];
    if (layer && typeof layer.getBounds === 'function') {
      map.fitBounds(layer.getBounds(), { maxZoom: 2.2, padding: [70, 70], animate: true });
    }
  }

  function fitBounds() {
    if (map && mapBounds) {
      map.fitBounds(mapBounds, { animate: true });
    }
  }

  // Snapping logic
  function getSnappedLatLng(latlng: L.LatLng, altPressed?: boolean) {
    currentSnapTarget = null;
    if (!snapEnabled || altPressed || editorMode === 'sector_boundary') {
      hideSnapIndicator();
      return latlng;
    }

    const p1 = map.latLngToContainerPoint(latlng);
    const p2 = new L.Point(p1.x + 14, p1.y);
    const ll2 = map.containerPointToLatLng(p2);
    const maxDist = Math.abs(ll2.lng - latlng.lng) || 5;

    let bestDist = maxDist;
    let bestPt: [number, number] | null = null;
    let bestLabel = '';
    // Nearest spot along a run or lift, used only when no existing point is in reach
    let lineDist = maxDist * 0.7;
    let linePt: [number, number] | null = null;
    let lineLabel = '';
    const here: [number, number] = [latlng.lat, latlng.lng];

    segments.forEach((seg) => {
      if (editingSegId && String(seg.id) === String(editingSegId)) return;
      const pts = parsePoints(seg.points);
      if (!pts.length) return;
      pts.forEach((pt, idx) => {
        const dy = pt[0] - latlng.lat;
        const dx = pt[1] - latlng.lng;
        const d = Math.sqrt(dy * dy + dx * dx);
        const isEndPt = idx === 0 || idx === pts.length - 1;
        const effDist = isEndPt ? d * 0.85 : d;
        if (effDist < bestDist) {
          bestDist = effDist;
          bestPt = pt;
          bestLabel = (seg.name || seg.type) + (idx === 0 ? ' (Start)' : idx === pts.length - 1 ? ' (End)' : '');
        }
        if (idx > 0) {
          const c = closestOnLine(here, pts[idx - 1], pt);
          const d2 = Math.hypot(c[0] - here[0], c[1] - here[1]);
          if (d2 < lineDist) {
            lineDist = d2;
            linePt = c;
            lineLabel = (seg.name || seg.type) + ' (on line)';
          }
        }
      });
    });

    if (!bestPt && linePt) {
      bestPt = linePt;
      bestLabel = lineLabel;
    }

    if (bestPt) {
      currentSnapTarget = { lat: bestPt[0], lng: bestPt[1], label: bestLabel };
      showSnapIndicator([bestPt[0], bestPt[1]], bestLabel);
      return new L.LatLng(bestPt[0], bestPt[1]);
    }

    hideSnapIndicator();
    return latlng;
  }

  function showSnapIndicator(pt: [number, number], label: string) {
    if (!snapMarker) {
      const icon = new L.DivIcon({
        className: 'snap-indicator-wrap',
        html: '<div class="snap-indicator-ring"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12],
      });
      snapMarker = new L.Marker(pt, { icon, interactive: false, zIndexOffset: 2000 }).addTo(map);
    } else {
      snapMarker.setLatLng(pt);
    }
    notifyEditorState();
  }

  function hideSnapIndicator() {
    if (snapMarker) {
      map.removeLayer(snapMarker);
      snapMarker = null;
    }
  }

  function clearEditorMarkers() {
    vertexMarkers.forEach((m) => map.removeLayer(m));
    vertexMarkers = [];
    midpointMarkers.forEach((m) => map.removeLayer(m));
    midpointMarkers = [];
    if (rubberBandLine) {
      map.removeLayer(rubberBandLine);
      rubberBandLine = null;
    }
    hideSnapIndicator();
  }

  function deleteVertexAt(index: number) {
    const minPts = editorMode === 'sector_boundary' ? 3 : 2;
    if (editorMode === 'draw' && editorPoints.length <= 1) {
      editorPoints = [];
      selectedVertexIdx = -1;
      updateEditorDisplay(true);
      notifyEditorState();
      return;
    }
    if (editorPoints.length <= minPts) {
      callbacks.onToast(
        'A ' +
          (editorMode === 'sector_boundary' ? 'sector boundary requires at least 3' : 'line requires at least 2') +
          ' points',
        'warning'
      );
      return;
    }
    pushUndoState();
    editorPoints.splice(index, 1);
    selectedVertexIdx = -1;
    updateEditorDisplay(true);
    notifyEditorState();
  }

  function createVertexMarker(latlng: [number, number], index: number, total: number, accentColor: string) {
    const isStart = index === 0;
    const isEnd = total > 1 && index === total - 1 && editorMode !== 'sector_boundary';
    const isSel = index === selectedVertexIdx;

    const classes =
      'custom-vertex-handle' +
      (isStart ? ' is-start' : isEnd ? ' is-end' : '') +
      (isSel ? ' is-selected' : '');
    const label = isStart ? 'S' : isEnd ? 'E' : '';
    const size = isStart || isEnd ? 20 : 16;
    const anchor = size / 2;

    const icon = new L.DivIcon({
      className: 'custom-vertex-wrap',
      html:
        '<div class="' +
        classes +
        '" style="' +
        (!isStart && !isEnd ? 'border-color:' + accentColor : '') +
        '">' +
        label +
        '</div>',
      iconSize: [size, size],
      iconAnchor: [anchor, anchor],
    });

    const marker = new L.Marker(latlng, {
      icon,
      draggable: true,
      zIndexOffset: isStart || isEnd ? 1200 : 1000,
    });

    const ptLabel = isStart
      ? 'Start Station (Point #1)'
      : isEnd
        ? 'End Station (Point #' + (index + 1) + ')'
        : 'Vertex #' + (index + 1);
    marker.bindTooltip(ptLabel + ' • Drag to move • Right-click or Alt+click to delete', {
      direction: 'top',
      offset: [0, -8],
    });

    marker.on('dragstart', () => {
      pushUndoState();
      selectedVertexIdx = index;
    });

    marker.on('drag', (e) => {
      const rawLl = marker.getLatLng();
      const altKey = !!(e as DragEventWithPointer).originalEvent?.altKey;
      const snapped = getSnappedLatLng(rawLl, altKey);
      editorPoints[index] = [snapped.lat, snapped.lng];
      updateEditorDisplay(false);
    });

    marker.on('dragend', () => {
      if (currentSnapTarget) {
        editorPoints[index] = [currentSnapTarget.lat, currentSnapTarget.lng];
      }
      hideSnapIndicator();
      updateEditorDisplay(true);
      notifyEditorState();
    });

    marker.on('click', (e) => {
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
      const orig = e.originalEvent;
      if (orig && (orig.altKey || orig.shiftKey)) {
        deleteVertexAt(index);
        return;
      }
      // The second click of a double-click lands on the point the first click just placed
      if (index === editorPoints.length - 1 && Date.now() - lastClickTime < DOUBLE_CLICK_MS) {
        finishOnDoubleClick();
        return;
      }
      if (selectedVertexIdx === index) return;
      selectedVertexIdx = index;
      updateEditorDisplay(true);
    });

    marker.on('contextmenu', (e) => {
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
      deleteVertexAt(index);
    });

    marker.on('dblclick', (e) => {
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
    });

    return marker;
  }

  function createMidpointMarker(index: number, nextIndex: number, accentColor: string) {
    const p1 = editorPoints[index];
    const p2 = editorPoints[nextIndex];
    const midLat = (p1[0] + p2[0]) / 2;
    const midLng = (p1[1] + p2[1]) / 2;

    const icon = new L.DivIcon({
      className: 'custom-midpoint-wrap',
      html: '<div class="custom-midpoint-handle" style="background:' + accentColor + '">+</div>',
      iconSize: [12, 12],
      iconAnchor: [6, 6],
    });

    const marker = new L.Marker([midLat, midLng], {
      icon,
      draggable: true,
      zIndexOffset: 900,
    });

    marker.bindTooltip('Click or drag to insert a bend point here', { direction: 'top', offset: [0, -6] });

    let insertedIndex = -1;

    marker.on('click', (e) => {
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
      pushUndoState();
      const insertAt = index + 1;
      editorPoints.splice(insertAt, 0, [midLat, midLng]);
      selectedVertexIdx = insertAt;
      updateEditorDisplay(true);
      notifyEditorState();
      callbacks.onToast('Inserted vertex #' + (insertAt + 1), 'info');
    });

    marker.on('dragstart', () => {
      pushUndoState();
      insertedIndex = index + 1;
      const ll = marker.getLatLng();
      editorPoints.splice(insertedIndex, 0, [ll.lat, ll.lng]);
      selectedVertexIdx = insertedIndex;
    });

    marker.on('drag', (e) => {
      if (insertedIndex !== -1) {
        const rawLl = marker.getLatLng();
        const altKey = !!(e as DragEventWithPointer).originalEvent?.altKey;
        const snapped = getSnappedLatLng(rawLl, altKey);
        editorPoints[insertedIndex] = [snapped.lat, snapped.lng];
        updateEditorDisplay(false);
      }
    });

    marker.on('dragend', () => {
      if (insertedIndex !== -1 && currentSnapTarget) {
        editorPoints[insertedIndex] = [currentSnapTarget.lat, currentSnapTarget.lng];
      }
      insertedIndex = -1;
      hideSnapIndicator();
      updateEditorDisplay(true);
      notifyEditorState();
    });

    return marker;
  }

  function getActiveEditorAccentColor(): string {
    if (editorMode === 'sector_boundary') {
      const sec = sectors.find((s) => Number(s.id) === Number(editingSectorId));
      return sec && sec.color ? sec.color : '#38bdf8';
    }
    if (editorType === 'lift') return COLORS.lift;
    if (editorSlopeType === 'snowpark') return COLORS.snowpark;
    return COLORS[editorSegDiff] || COLORS.blue;
  }

  function updateEditorDisplay(rebuildMarkers: boolean) {
    if (!map) return;
    const accentColor = getActiveEditorAccentColor();

    if (editorLine) {
      map.removeLayer(editorLine);
      editorLine = null;
    }

    if (editorPoints.length > 0) {
      if (editorMode === 'sector_boundary' && editorPoints.length >= 3) {
        editorLine = new L.Polygon(editorPoints, {
          color: accentColor,
          weight: 3,
          opacity: 0.95,
          fillColor: accentColor,
          fillOpacity: 0.2,
          dashArray: '6, 4',
          interactive: false,
        }).addTo(map);
      } else {
        editorLine = new L.Polyline(editorPoints, {
          color: accentColor,
          weight: 5,
          opacity: 0.95,
          dashArray: '8, 6',
          interactive: false,
        }).addTo(map);
      }
    }

    if (rebuildMarkers) {
      clearEditorMarkers();
      const total = editorPoints.length;
      editorPoints.forEach((pt, idx) => {
        const vm = createVertexMarker(pt, idx, total, accentColor);
        vm.addTo(map);
        vertexMarkers.push(vm);
      });
      if (total >= 2) {
        for (let i = 0; i < total - 1; i++) {
          const mm = createMidpointMarker(i, i + 1, accentColor);
          mm.addTo(map);
          midpointMarkers.push(mm);
        }
        if (editorMode === 'sector_boundary' && total >= 3) {
          const closeMm = createMidpointMarker(total - 1, 0, accentColor);
          closeMm.addTo(map);
          midpointMarkers.push(closeMm);
        }
      }
    }
  }

  function nextSuggestedName(type: 'lift' | 'slope'): string {
    const isLift = type === 'lift';
    const prefix = isLift ? 'Lift Line ' : 'Slope Path ';
    let maxNum = segments.filter((s) => (isLift ? s.type === 'lift' : s.type !== 'lift')).length + 1;
    while (segments.some((s) => (s.name || '').toLowerCase() === (prefix + maxNum).toLowerCase())) {
      maxNum++;
    }
    return prefix + maxNum;
  }

  function setAppendMode(enabled: boolean) {
    appendInEditMode = enabled;
    const mapEl = document.getElementById('map');
    if (mapEl) {
      if (editorMode === 'draw' || editorMode === 'sector_boundary' || appendInEditMode) {
        mapEl.classList.add('drawing-cursor');
      } else {
        mapEl.classList.remove('drawing-cursor');
      }
    }
    notifyEditorState();
  }

  function startDraw(type: 'lift' | 'slope', preserveSettings?: boolean) {
    if (editorMode) cancelEditor(true);
    editorMode = 'draw';
    editingSegId = null;
    editingSectorId = null;
    editorType = type;
    editorPoints = [];
    undoStack = [];
    selectedVertexIdx = -1;

    editorSegName = nextSuggestedName(type);
    if (!preserveSettings) {
      editorSegSector = lastDraw.sector;
      editorSegDiff = lastDraw.difficulty;
      editorSlopeType = lastDraw.slopeType;
    }

    setAppendMode(true);
    if (map) map.doubleClickZoom.disable();
    updateEditorDisplay(true);
    notifyEditorState();
  }

  function startEditSegment(seg: MapSegment) {
    if (editorMode) cancelEditor(true);
    editorMode = 'edit';
    editingSegId = seg.id;
    editingSectorId = null;
    editorType = seg.type === 'lift' ? 'lift' : 'slope';
    editorPoints = parsePoints(seg.points);
    undoStack = [];
    selectedVertexIdx = -1;

    editorSegName = seg.name || '';
    editorSegSector = Number(seg.sector || 0);
    editorSlopeType = seg.type !== 'lift' ? seg.type : 'downhill';
    editorSegDiff = seg.difficulty || 'blue';

    renderSegments(buildMode);

    setAppendMode(false);
    if (map) map.doubleClickZoom.disable();
    updateEditorDisplay(true);
    notifyEditorState();
  }

  function startEditSectorBoundary(sec: Sector) {
    if (editorMode) cancelEditor(true);
    editorMode = 'sector_boundary';
    editingSectorId = sec.id;
    editingSegId = null;
    editorPoints = parsePoints(sec.boundary_points);
    undoStack = [];
    selectedVertexIdx = -1;
    showSectorPolygons = true;
    renderSectorPolygons();

    setAppendMode(true);
    if (map) map.doubleClickZoom.disable();
    updateEditorDisplay(true);
    notifyEditorState();

    callbacks.onToast('Click around the mountain to outline "' + sec.name + '", then click Save', 'info');
  }

  function cancelEditor(skipRender?: boolean) {
    const wasEditing = editorMode === 'edit';
    editorMode = null;
    editingSegId = null;
    editingSectorId = null;
    editorPoints = [];
    undoStack = [];
    redoStack = [];
    discardArmedUntil = 0;
    // Editing loaded another line's settings: the idle toolbar goes back to the ones for new lines
    if (wasEditing) {
      editorSegSector = lastDraw.sector;
      editorSegDiff = lastDraw.difficulty;
      editorSlopeType = lastDraw.slopeType;
    }
    selectedVertexIdx = -1;
    setAppendMode(false);

    if (editorLine && map) {
      map.removeLayer(editorLine);
      editorLine = null;
    }
    clearEditorMarkers();

    if (map) map.doubleClickZoom.enable();
    notifyEditorState();

    if (!skipRender) {
      renderSegments(buildMode);
    }
  }

  function saveEditor(drawNextAfterSave?: boolean) {
    if (editorMode === 'sector_boundary') {
      if (editorPoints.length < 3) {
        callbacks.onToast('Please place at least 3 points for a sector boundary polygon', 'warning');
        return;
      }
      const secId = editingSectorId;
      postJSON('/map/sector/boundary/' + secId, { id: secId, boundary_points: editorPoints }, (res) => {
        if (res && res.success) {
          const sec = sectors.find((s) => Number(s.id) === Number(secId));
          if (sec) sec.boundary_points = JSON.stringify(editorPoints);
          callbacks.onSectorsUpdate([...sectors]);
          callbacks.onToast('Sector boundary saved!', 'success');
          cancelEditor(false);
        } else {
          callbacks.onToast(res?.error || 'Failed to save the sector boundary', 'error');
        }
      });
      return;
    }

    if (editorPoints.length < 2) {
      callbacks.onToast('Please place at least 2 points for a lift or slope', 'warning');
      return;
    }
    if (saving) return;
    saving = true;

    let name = editorSegName.trim();
    if (!name) {
      name = nextSuggestedName(editorType);
    }

    const sectorId = editorSegSector;
    const isLift = editorType === 'lift';
    const segType = isLift ? 'lift' : editorSlopeType;
    const difficulty = isLift ? '' : editorSegDiff;
    const lengthM = calcLengthMeters(editorPoints);

    const payload: any = {
      name,
      type: segType,
      sector: sectorId,
      difficulty,
      length_meters: lengthM,
      points: editorPoints,
    };
    if (editingSegId) {
      payload.id = editingSegId;
    }

    const currentEditorType = editorType;

    postJSON('/map/segment', payload, (res) => {
      saving = false;
      if (res && res.success) {
        const savedId = Number(res.id || editingSegId);
        const updatedSeg: MapSegment = {
          id: savedId,
          name,
          type: segType,
          sector: sectorId,
          difficulty,
          length_meters: lengthM,
          points: JSON.stringify(editorPoints),
          active: 1,
        };

        if (editingSegId) {
          const idx = segments.findIndex((s) => String(s.id) === String(editingSegId));
          if (idx !== -1) {
            segments[idx] = updatedSeg;
          }
          callbacks.onToast('Updated "' + name + '" (' + lengthM + ' ' + distUnit + ')', 'success');
        } else {
          segments.push(updatedSeg);
          rememberDrawSettings();
          callbacks.onToast('Created "' + name + '" (' + lengthM + ' ' + distUnit + ')', 'success');
        }

        callbacks.onSegmentsUpdate([...segments]);

        if (drawNextAfterSave) {
          cancelEditor(false);
          startDraw(currentEditorType, true);
        } else {
          cancelEditor(false);
        }
      } else {
        callbacks.onToast(res?.error || 'Failed to save segment', 'error');
      }
    });
  }

  function snapshot(): [number, number][] {
    return editorPoints.map((p) => [p[0], p[1]] as [number, number]);
  }

  function undo() {
    if (!editorMode) return;
    if (undoStack.length > 0) {
      redoStack.push(snapshot());
      editorPoints = undoStack.pop()!;
    } else if (editorPoints.length > 0) {
      redoStack.push(snapshot());
      editorPoints.pop();
    } else {
      return;
    }
    selectedVertexIdx = -1;
    updateEditorDisplay(true);
    notifyEditorState();
  }

  function redo() {
    if (!editorMode || redoStack.length === 0) return;
    undoStack.push(snapshot());
    editorPoints = redoStack.pop()!;
    selectedVertexIdx = -1;
    updateEditorDisplay(true);
    notifyEditorState();
  }

  function reverse() {
    if (!editorMode || editorPoints.length < 2) return;
    pushUndoState();
    editorPoints.reverse();
    updateEditorDisplay(true);
    notifyEditorState();
    callbacks.onToast('Reversed Start (S) and End (E) direction', 'info');
  }

  function toggleSnap(): boolean {
    snapEnabled = !snapEnabled;
    if (!snapEnabled) hideSnapIndicator();
    notifyEditorState();
    return snapEnabled;
  }

  function toggleSectors(): boolean {
    showSectorPolygons = !showSectorPolygons;
    renderSectorPolygons();
    notifyEditorState();
    return showSectorPolygons;
  }

  function toggleAppend(): boolean {
    setAppendMode(!appendInEditMode);
    return appendInEditMode;
  }

  function postJSON(url: string, data: any, callback?: (res: any) => void) {
    const payload = { ...(data || {}) };
    payload[csrfName] = csrfHash;

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfHash,
      },
      body: JSON.stringify(payload),
    })
      .then((res) => {
        return res.json().catch(() => {
          throw new Error('Server returned HTTP ' + res.status);
        });
      })
      .then((json) => {
        if (json && json.csrf_hash) {
          csrfHash = json.csrf_hash;
        }
        if (callback) callback(json);
      })
      .catch((err) => {
        console.error('API Error:', err);
        // A caller with a callback shows its own error; without one this is the only message
        if (callback) callback({ success: false, error: err.message || 'Request failed' });
        else callbacks.onToast(err.message || 'Request failed', 'error');
      });
  }

  function destroy() {
    document.removeEventListener('keydown', handleKeydown);
    if (map) {
      map.remove();
      // Back to "not initialised" so the guards (`if (!map)`) skip late calls and init() can run again
      map = undefined as unknown as L.Map;
    }
  }

  return {
    init,
    destroy,
    fitBounds,
    zoomToSegment,
    selectSegment,
    deselectSegment,
    setFilter: (f: string) => {
      mapFilter = f;
      renderSegments(buildMode);
    },
    setBuildMode: (m: string | null) => {
      buildMode = m;
      renderSegments(buildMode);
    },
    setShowAvailable: (show: boolean) => {
      showAvailable = show;
      try {
        localStorage.setItem('map_show_available', show ? '1' : '0');
      } catch {}
      renderSegments(buildMode);
    },
    getShowAvailable: () => showAvailable,
    /** The container changed size (the page fits itself to the screen after load): redraw and refit */
    resize: (bottomPad = 0) => {
      if (!map) return;
      map.invalidateSize({ animate: false });
      // Once the player has moved the map themselves, leave their view alone
      if (userMoved || !mapBounds) return;
      // Fit the part of the mountain with runs on it, in the space above the phone's bottom sheet
      const layers = Object.values(segmentLayers).filter((l) => typeof l.getBounds === 'function');
      let bounds = layers.length ? new L.FeatureGroup(layers).getBounds() : new L.LatLngBounds(mapBounds);
      const size = map.getSize();
      if (size.x < size.y) {
        // Portrait phone: the runs spread wider than the screen. Fit their height and centre them;
        // fitting the width too would shrink the mountain to a strip the sheet then covers.
        const c = bounds.getCenter();
        bounds = new L.LatLngBounds([bounds.getSouth(), c.lng], [bounds.getNorth(), c.lng]);
      }
      map.fitBounds(bounds, { animate: false, paddingTopLeft: [16, 56], paddingBottomRight: [16, bottomPad + 16] });
    },
    demolish,
    swapLift,
    startDraw,
    startEditSegment,
    startEditSectorBoundary,
    cancelEditor,
    saveEditor,
    undo,
    redo,
    reverse,
    toggleSnap,
    toggleSectors,
    toggleAppend,
    setEditorName: (n: string) => {
      editorSegName = n;
      notifyEditorState();
    },
    setEditorSector: (s: number) => {
      editorSegSector = s;
      // Picked on the idle toolbar: that is the sector for the next new line
      if (!editorMode) rememberDrawSettings();
      notifyEditorState();
    },
    setEditorSlopeType: (t: string) => {
      editorSlopeType = t;
      if (editorMode) updateEditorDisplay(true);
      notifyEditorState();
    },
    setEditorDifficulty: (d: string) => {
      editorSegDiff = d;
      if (editorMode) updateEditorDisplay(true);
      notifyEditorState();
    },
    postJSON,
    createSector: (name: string, color: string, cb?: (sec: Sector | null) => void) => {
      postJSON('/map/sector/create', { name, color }, (res) => {
        if (res && res.success && res.sector) {
          sectors.push(res.sector);
          callbacks.onSectorsUpdate([...sectors]);
          callbacks.onToast('Created sector "' + res.sector.name + '"', 'success');
          if (cb) cb(res.sector);
        } else {
          callbacks.onToast(res?.error || 'Failed to create sector', 'error');
          if (cb) cb(null);
        }
      });
    },
    deleteSector: (secId: number) => {
      if (!confirm('Delete this sector? Segments in this sector will be reset to Default (0).')) return;
      postJSON('/map/sector/delete/' + secId, {}, (res) => {
        if (res && res.success) {
          sectors = sectors.filter((s) => Number(s.id) !== Number(secId));
          segments.forEach((sg) => {
            if (Number(sg.sector) === Number(secId)) sg.sector = 0;
          });
          callbacks.onSectorsUpdate([...sectors]);
          callbacks.onSegmentsUpdate([...segments]);
          renderSectorPolygons();
          callbacks.onToast('Sector deleted', 'success');
        } else {
          callbacks.onToast(res?.error || 'Failed to delete sector', 'error');
        }
      });
    },
    toggleSectorVisibility: (secId: number) => {
      postJSON('/map/sector/toggle/' + secId, {}, (res) => {
        if (res && res.success) {
          const sec = sectors.find((s) => Number(s.id) === Number(secId));
          if (sec) {
            sec.visible = res.visible ? 1 : 0;
            callbacks.onSectorsUpdate([...sectors]);
            callbacks.onToast(sec.name + ' is now ' + (sec.visible ? 'visible' : 'hidden'), 'info');
          }
        } else {
          callbacks.onToast(res?.error || 'Failed to change sector visibility', 'error');
        }
      });
    },
    autoAssignSectors: () => {
      postJSON('/map/sector/auto-assign', {}, (r) => {
        if (r && r.success) {
          if (r.updated) {
            segments.forEach((seg) => {
              if (r.updated[seg.id]) seg.sector = r.updated[seg.id];
            });
          }
          callbacks.onSegmentsUpdate([...segments]);
          callbacks.onToast('Auto-assigned ' + (r.assigned || 0) + ' segments to sectors!', 'success');
        } else {
          callbacks.onToast(r?.error || 'Failed to auto-assign', 'error');
        }
      });
    },
    buildSegment: (seg: MapSegment, options: any, cb?: (success: boolean) => void) => {
      const body: any = { segment_id: seg.id };
      if (seg.type === 'lift') {
        body.lift_type = options.liftType || 'chair_fixed';
        body.seats = options.seats || 4;
      } else {
        body.slope_type = options.slopeType || 'downhill';
      }

      postJSON('/map/build', body, (res) => {
        if (res && res.success) {
          builtIds.push(String(seg.id));
          if (res.item) builtItems[seg.id] = res.item;
          if (typeof res.new_cash === 'number') {
            userCash = res.new_cash;
            callbacks.onCashUpdate(userCash);
          }
          callbacks.onBuiltUpdate([...builtIds], { ...builtItems });
          // The panel says what was built and what to do next
          deselectSegment();
          renderSegments(buildMode);
          if (typeof (window as any).loadTutorial === 'function') (window as any).loadTutorial();
          if (cb) cb(true);
        } else {
          callbacks.onToast(res?.error || 'Construction failed', 'error');
          if (cb) cb(false);
        }
      });
    },
  };
}
