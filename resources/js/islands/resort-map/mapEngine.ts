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

export function createMapEngine(props: ResortMapProps, callbacks: MapEngineCallbacks) {
  let map: any = null;
  let mapBounds: any = null;
  let segmentLayers: Record<string, any> = {};
  let hitLayers: Record<string, any> = {};
  let stationMarkers: any[] = [];
  let sectorPolygons: any[] = [];
  let showSectorPolygons = false;
  let selectedSegId: number | string | null = null;
  let buildMode: string | null = null;
  let mapFilter = 'all';

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
  const isImperial = props.isImperial;

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
  let editorLine: any = null;
  let rubberBandLine: any = null;
  let snapMarker: any = null;
  let currentSnapTarget: { lat: number; lng: number; label: string } | null = null;
  let vertexMarkers: any[] = [];
  let midpointMarkers: any[] = [];
  let lastClickTime = 0;

  let editorSegName = '';
  let editorSegSector = 0;
  let editorSlopeType = 'downhill';
  let editorSegDiff = 'blue';

  function fmtMoney(n: number) {
    const prefix = isImperial ? '$' : '';
    const suffix = isImperial ? '' : ' €';
    return (n < 0 ? '-' : '') + prefix + Math.abs(Math.round(n)).toLocaleString('en-US') + suffix;
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
    return pts
      .map((p): [number, number] => {
        if (Array.isArray(p)) return [Number(p[0]) || 0, Number(p[1]) || 0];
        if (p && typeof p === 'object') return [Number(p.lat ?? p[0] ?? 0), Number(p.lng ?? p[1] ?? 0)];
        return [0, 0];
      })
      .filter((p) => !isNaN(p[0]) && !isNaN(p[1]));
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
    const L = (window as any).L;
    if (!L) {
      console.error('Leaflet library (window.L) is not loaded.');
      return;
    }
    if (map) return;

    mapBounds = [
      [0, 0],
      [mapConfig.height, mapConfig.width],
    ];

    map = L.map(container, {
      crs: L.CRS.Simple,
      minZoom: -3,
      maxZoom: 4,
      zoomSnap: 0.25,
      zoomDelta: 0.5,
      zoomControl: true,
      attributionControl: false,
    }).setView([mapConfig.height / 2, mapConfig.width / 2], 0);

    initProgressiveOverlays();

    map.fitBounds(mapBounds);
    map.setMaxBounds([
      [-mapConfig.height * 0.2, -mapConfig.width * 0.2],
      [mapConfig.height * 1.2, mapConfig.width * 1.2],
    ]);

    renderSegments();

    // Map Event Listeners for Editor
    map.on('mousemove', (e: any) => {
      if (!editorMode) return;
      const isAppending =
        editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode);
      if (!isAppending) {
        if (rubberBandLine) {
          map.removeLayer(rubberBandLine);
          rubberBandLine = null;
        }
        return;
      }

      const altKey = e.originalEvent && e.originalEvent.altKey;
      const targetLl = getSnappedLatLng(e.latlng, altKey);

      if (editorPoints.length > 0) {
        const lastPt = editorPoints[editorPoints.length - 1];
        const previewPts = [lastPt, [targetLl.lat, targetLl.lng]];
        const accent = getActiveEditorAccentColor();
        if (!rubberBandLine) {
          rubberBandLine = L.polyline(previewPts, {
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

    map.on('click', (e: any) => {
      if (!editorMode) return;
      const isAppending =
        editorMode === 'draw' || editorMode === 'sector_boundary' || (editorMode === 'edit' && appendInEditMode);
      if (!isAppending) {
        selectedVertexIdx = -1;
        updateEditorDisplay(true);
        return;
      }

      const now = Date.now();
      const altKey = e.originalEvent && e.originalEvent.altKey;
      const targetLl = getSnappedLatLng(e.latlng, altKey);

      if (now - lastClickTime < 280 && editorPoints.length > 0) {
        const prev = editorPoints[editorPoints.length - 1];
        const dy = prev[0] - targetLl.lat;
        const dx = prev[1] - targetLl.lng;
        if (Math.sqrt(dy * dy + dx * dx) < 1.5) {
          return;
        }
      }
      lastClickTime = now;

      pushUndoState();
      editorPoints.push([targetLl.lat, targetLl.lng]);
      selectedVertexIdx = editorPoints.length - 1;
      updateEditorDisplay(true);
      notifyEditorState();
    });

    map.on('dblclick', (e: any) => {
      if (!editorMode) return;
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
      if (editorMode === 'draw' && editorPoints.length >= 2) {
        saveEditor(false);
      }
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', handleKeydown);

    // Initial drawer open for new non-admin players with 0 built runs
    if (builtIds.length === 0 && !isAdmin) {
      setTimeout(() => {
        callbacks.onOpenBuildDrawer('slope');
      }, 300);
    }
  }

  function handleKeydown(e: KeyboardEvent) {
    const tag = (e.target as HTMLElement)?.tagName?.toLowerCase() || '';
    if (tag === 'input' || tag === 'select' || tag === 'textarea') {
      if (e.key === 'Enter' && editorMode) {
        e.preventDefault();
        saveEditor(e.shiftKey);
      } else if (e.key === 'Escape' && editorMode) {
        e.preventDefault();
        cancelEditor();
      }
      return;
    }

    if (editorMode) {
      if (e.key === 'Enter') {
        e.preventDefault();
        saveEditor(e.shiftKey);
      } else if (e.key === 'Escape') {
        e.preventDefault();
        cancelEditor();
      } else if ((e.key === 'z' || e.key === 'Z') && (e.metaKey || e.ctrlKey || !e.shiftKey)) {
        e.preventDefault();
        undo();
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

  function initProgressiveOverlays() {
    const L = (window as any).L;
    const basePath = mapConfig.image.replace(/\.jpg$/i, '');
    const imgLow = basePath + '_low.jpg';
    const imgMed = basePath + '_med.jpg';
    const imgFull = mapConfig.image;

    L.imageOverlay(imgLow, mapBounds, { zIndex: 1 }).addTo(map);
    let fullOverlay: any = null;
    let fullLoading = false;

    const medPreload = new Image();
    medPreload.onload = () => {
      L.imageOverlay(imgMed, mapBounds, { zIndex: 2 }).addTo(map);
    };
    medPreload.src = imgMed;

    map.on('zoomend', () => {
      if (map.getZoom() >= 1.5 && !fullOverlay && !fullLoading) {
        fullLoading = true;
        const hdBadge = document.getElementById('hdBadge');
        if (hdBadge) hdBadge.style.display = 'inline-flex';

        const fullPreload = new Image();
        fullPreload.onload = () => {
          fullOverlay = L.imageOverlay(imgFull, mapBounds, { zIndex: 3 }).addTo(map);
          if (hdBadge) hdBadge.style.display = 'none';
        };
        fullPreload.onerror = () => {
          if (hdBadge) hdBadge.style.display = 'none';
        };
        fullPreload.src = imgFull;
      }
    });
  }

  function renderSegments(newBuildMode?: string | null) {
    const L = (window as any).L;
    if (!L || !map) return;

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

      // Visibility rules
      if (!buildMode && !built && !isAdmin) return;
      if (buildMode && !built) {
        if (buildMode === 'lift' && seg.type !== 'lift') return;
        if (buildMode === 'slope' && seg.type === 'lift') return;
        if (!isSegmentReleased(seg)) return;
      }

      const isAvailableHighlight = buildMode && !built;
      const c = isAvailableHighlight ? '#a855f7' : segColor(seg);
      const weight = built ? 5 : isAdmin ? 4.5 : 6;
      const opacity = built ? 0.95 : 0.85;
      const dashArray = built ? (seg.type === 'lift' ? '10, 5' : null) : '6, 5';

      const line = L.polyline(ll, {
        color: c,
        weight,
        opacity,
        dashArray,
        lineCap: 'round',
        lineJoin: 'round',
        interactive: false,
      }).addTo(map);

      const hitLine = L.polyline(ll, {
        color: '#000',
        weight: 18,
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
            ? '<br><span style="color:#c084fc;font-weight:700">Click to construct</span>'
            : '<br><span style="color:#4ade80;font-size:10px">✓ Operational</span>') +
        '</div>';

      hitLine.bindTooltip(tooltipHtml, { sticky: true });

      hitLine.on('mouseover', () => {
        if (editorMode) return;
        if (!selectedSegId || String(selectedSegId) !== String(seg.id)) {
          line.setStyle({ weight: weight + 3, opacity: 1 });
        }
      });

      hitLine.on('mouseout', () => {
        if (editorMode) return;
        if (!selectedSegId || String(selectedSegId) !== String(seg.id)) {
          line.setStyle({ weight, opacity });
        }
      });

      hitLine.on('click', (e: any) => {
        if (L.DomEvent) L.DomEvent.stopPropagation(e);
        if (editorMode) {
          if (editorMode === 'draw' || (editorMode === 'edit' && appendInEditMode)) {
            const pt = getSnappedLatLng(e.latlng);
            pushUndoState();
            editorPoints.push([pt.lat, pt.lng]);
            updateEditorDisplay(true);
            notifyEditorState();
          }
          return;
        }

        if (isAdmin) {
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

  function renderSectorPolygons() {
    const L = (window as any).L;
    if (!L || !map) return;

    sectorPolygons.forEach((p) => map.removeLayer(p));
    sectorPolygons = [];
    if (!showSectorPolygons) return;

    sectors.forEach((sec) => {
      if (editingSectorId && Number(sec.id) === Number(editingSectorId)) return;
      const pts = parsePoints(sec.boundary_points);
      if (pts.length < 3) return;
      const col = sec.color || '#3b82f6';
      const poly = L.polygon(pts, {
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

  function openBuiltSegmentPopup(seg: MapSegment, latlng: any) {
    const L = (window as any).L;
    const item = builtItems[seg.id] || {};
    const capInfo =
      seg.type === 'lift' && item.capacity
        ? '<div class="flex justify-between"><span>Capacity:</span><b>' +
          Number(item.capacity).toLocaleString() +
          '/hr</b></div>'
        : '';
    const snowInfo =
      seg.type !== 'lift' && item.snow_quality
        ? '<div class="flex justify-between"><span>Condition:</span><b class="capitalize">' +
          escHtml(item.snow_quality) +
          '</b></div>'
        : '';
    const status = item.status || 'open';
    const statusClass =
      { open: 'text-success', closed: 'text-muted', broken: 'text-danger', building: 'text-link' }[status] ||
      'text-muted';
    const canDemolish = !!item.id;
    const el = document.createElement('div');
    el.className = 'p-2 text-xs min-w-[200px] text-foreground bg-surface rounded-lg';
    el.innerHTML =
      '<div class="font-bold text-sm mb-1.5 flex items-center gap-1.5">' +
      (seg.type === 'lift'
        ? '<i class="fa-solid fa-elevator text-warning"></i>'
        : '<i class="fa-solid fa-person-skiing text-success"></i>') +
      ' <span>' +
      escHtml(seg.name || seg.type) +
      '</span>' +
      '</div>' +
      '<div class="space-y-1 text-muted text-xs">' +
      '<div class="flex justify-between"><span>Length:</span><b class="tabular-nums font-semibold text-foreground">' +
      Math.round(seg.length_meters || 0) +
      ' ' +
      distUnit +
      '</b></div>' +
      capInfo +
      snowInfo +
      '<div class="flex justify-between"><span>Status:</span><span class="font-semibold ' +
      statusClass +
      ' capitalize">' +
      escHtml(status) +
      '</span></div>' +
      '</div>' +
      (canDemolish
        ? '<button type="button" class="w-full mt-3 py-1.5 px-2 rounded-md bg-danger/10 hover:bg-danger/20 text-danger text-xs font-semibold flex items-center justify-center gap-1.5 cursor-pointer transition-colors" data-demolish>' +
          '<i class="fa-solid fa-trash-can"></i> Demolish · refund ' +
          fmtMoney(Number(item.salvage || 0)) +
          '</button>'
        : '');

    const btn = el.querySelector('[data-demolish]') as HTMLButtonElement;
    if (btn) {
      btn.addEventListener('click', () => demolishBuilt(seg, item, btn));
    }

    L.popup({ className: 'admin-leaflet-popup' }).setLatLng(latlng).setContent(el).openOn(map);
  }

  function demolishBuilt(seg: MapSegment, item: BuiltItem, btn: HTMLButtonElement) {
    const name = seg.name || (seg.type === 'lift' ? 'this lift' : 'this slope');
    const ok = confirm(
      'Demolish ' +
        name +
        '?\n\nYou get ' +
        fmtMoney(Number(item.salvage || 0)) +
        ' back (25% salvage). Snow cannons and terrain parks on it are kept but unassigned. This cannot be undone.'
    );
    if (!ok) return;

    btn.disabled = true;
    postJSON('/map/demolish/' + item.id, {}, (res) => {
      if (!res || !res.success) {
        btn.disabled = false;
        callbacks.onToast(res?.error || 'Demolition failed', 'error');
        return;
      }
      builtIds = builtIds.filter((id) => String(id) !== String(seg.id) && Number(id) !== Number(seg.id));
      delete builtItems[seg.id];
      if (typeof res.new_cash === 'number') {
        userCash = res.new_cash;
        callbacks.onCashUpdate(userCash);
      }
      callbacks.onBuiltUpdate(builtIds, builtItems);
      map.closePopup();
      renderSegments(buildMode);
      callbacks.onToast(res.message || 'Demolished ' + name, 'success');
    });
  }

  function openAdminSegmentPopup(seg: MapSegment, latlng: any) {
    const L = (window as any).L;
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

    L.popup({ minWidth: 220, maxWidth: 280, className: 'admin-leaflet-popup' })
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
      segmentLayers[seg.id].setStyle({ weight: 8, opacity: 1, color: '#38bdf8', dashArray: null });
      try {
        if (typeof segmentLayers[seg.id].getBounds === 'function') {
          map.panTo(segmentLayers[seg.id].getBounds().getCenter(), { animate: true });
        }
      } catch {}
    }

    callbacks.onOpenBuildDrawer(targetTab);
    callbacks.onSelectSegment(seg);
  }

  function deselectSegment() {
    if (selectedSegId && segmentLayers[selectedSegId]) {
      const s = segments.find((x) => String(x.id) === String(selectedSegId));
      if (s) {
        const b = isSegmentBuilt(s.id);
        segmentLayers[selectedSegId].setStyle({
          color: b || !buildMode ? segColor(s) : '#a855f7',
          weight: b ? 5 : 6,
          opacity: b ? 0.95 : 0.85,
          dashArray: b ? (s.type === 'lift' ? '10, 5' : null) : '6, 5',
        });
      }
    }
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
  function getSnappedLatLng(latlng: any, altPressed?: boolean) {
    const L = (window as any).L;
    currentSnapTarget = null;
    if (!snapEnabled || altPressed || editorMode === 'sector_boundary') {
      hideSnapIndicator();
      return latlng;
    }

    const p1 = map.latLngToContainerPoint(latlng);
    const p2 = L.point(p1.x + 14, p1.y);
    const ll2 = map.containerPointToLatLng(p2);
    const maxDist = Math.abs(ll2.lng - latlng.lng) || 5;

    let bestDist = maxDist;
    let bestPt: [number, number] | null = null;
    let bestLabel = '';

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

  function showSnapIndicator(pt: [number, number], label: string) {
    const L = (window as any).L;
    if (!snapMarker) {
      const icon = L.divIcon({
        className: 'snap-indicator-wrap',
        html: '<div class="snap-indicator-ring"></div>',
        iconSize: [24, 24],
        iconAnchor: [12, 12],
      });
      snapMarker = L.marker(pt, { icon, interactive: false, zIndexOffset: 2000 }).addTo(map);
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
    const L = (window as any).L;
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

    const icon = L.divIcon({
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

    const marker = L.marker(latlng, {
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

    marker.on('drag', (e: any) => {
      const rawLl = marker.getLatLng();
      const altKey = e.originalEvent && e.originalEvent.altKey;
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

    marker.on('click', (e: any) => {
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
      const orig = e.originalEvent;
      if (orig && (orig.altKey || orig.shiftKey)) {
        deleteVertexAt(index);
        return;
      }
      selectedVertexIdx = index;
      updateEditorDisplay(true);
    });

    marker.on('contextmenu', (e: any) => {
      if (L.DomEvent) L.DomEvent.stopPropagation(e);
      deleteVertexAt(index);
    });

    return marker;
  }

  function createMidpointMarker(index: number, nextIndex: number, accentColor: string) {
    const L = (window as any).L;
    const p1 = editorPoints[index];
    const p2 = editorPoints[nextIndex];
    const midLat = (p1[0] + p2[0]) / 2;
    const midLng = (p1[1] + p2[1]) / 2;

    const icon = L.divIcon({
      className: 'custom-midpoint-wrap',
      html: '<div class="custom-midpoint-handle" style="background:' + accentColor + '">+</div>',
      iconSize: [12, 12],
      iconAnchor: [6, 6],
    });

    const marker = L.marker([midLat, midLng], {
      icon,
      draggable: true,
      zIndexOffset: 900,
    });

    marker.bindTooltip('Click or drag to insert a bend point here', { direction: 'top', offset: [0, -6] });

    let insertedIndex = -1;

    marker.on('click', (e: any) => {
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

    marker.on('drag', (e: any) => {
      if (insertedIndex !== -1) {
        const rawLl = marker.getLatLng();
        const altKey = e.originalEvent && e.originalEvent.altKey;
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
    const L = (window as any).L;
    if (!L || !map) return;
    const accentColor = getActiveEditorAccentColor();

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
          interactive: false,
        }).addTo(map);
      } else {
        editorLine = L.polyline(editorPoints, {
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
      editorSegDiff = 'blue';
      editorSlopeType = 'downhill';
    }

    setAppendMode(true);
    if (map) map.doubleClickZoom.disable();
    updateEditorDisplay(true);
    notifyEditorState();

    callbacks.onToast(
      'Drawing ' + (type === 'lift' ? 'Lift (Bottom → Top)' : 'Slope (Top → Bottom)') + ': click map to place points',
      'info'
    );
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

    callbacks.onToast(
      'Editing "' + (seg.name || seg.type) + '": drag points to move, drag/click + midpoints to bend',
      'info'
    );
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
    editorMode = null;
    editingSegId = null;
    editingSectorId = null;
    editorPoints = [];
    undoStack = [];
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
        }
      });
      return;
    }

    if (editorPoints.length < 2) {
      callbacks.onToast('Please place at least 2 points for a lift or slope', 'warning');
      return;
    }

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

  function undo() {
    if (!editorMode) return;
    if (undoStack.length > 0) {
      editorPoints = undoStack.pop()!;
      selectedVertexIdx = -1;
      updateEditorDisplay(true);
      notifyEditorState();
    } else if (editorPoints.length > 0) {
      editorPoints.pop();
      selectedVertexIdx = -1;
      updateEditorDisplay(true);
      notifyEditorState();
    }
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
        callbacks.onToast(err.message || 'Request failed', 'error');
        if (callback) callback({ success: false, error: err.message || 'Request failed' });
      });
  }

  function destroy() {
    document.removeEventListener('keydown', handleKeydown);
    if (map) {
      map.remove();
      map = null;
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
    startDraw,
    startEditSegment,
    startEditSectorBoundary,
    cancelEditor,
    saveEditor,
    undo,
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
          callbacks.onBuiltUpdate(builtIds, builtItems);
          callbacks.onToast(
            'Constructed "' + (seg.name || seg.type) + '" for ' + fmtMoney(Number(res.cost || 0)) + '!',
            'success'
          );
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
