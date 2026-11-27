import { Suspense, lazy, useCallback, useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { Button, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Select } from '@/components/Select';
// Leaflet's own CSS loads with this chunk, after islands.css, so resort-map.css overrides keep applying
import 'leaflet/dist/leaflet.css';
import { BuildingInspectorView } from './resort-map/BuildingInspectorView';
import { BuildFooter, BuildView } from './resort-map/BuildView';
import { BrowseView, type BuildNotice, type SortOrder, type Suggestion } from './resort-map/BrowseView';
import { BuiltRunView } from './resort-map/BuiltRunView';
import type { LiftSwapOption } from './resort-map/LiftSwapDialog';
import { MapHeader } from './resort-map/MapHeader';
import { MapKey } from './resort-map/MapKey';
import { MapPanel } from './resort-map/MapPanel';
import { createMapApi } from './resort-map/mapApi';
import { computePlacedFacilities } from './resort-map/placement';
import type { Map3DViewportHandle } from './resort-map/scene3d/Map3DViewport';
import { buildCost, cheapestCost, endDistance, formatCurrency, sameId, settleText } from './resort-map/shared';
import { emitCashChanged } from '@/lib/cash';
import type {
  BuiltItem,
  EditorState,
  LiveSkiersSnapshot,
  MapBuilding,
  MapLens,
  MapLighting,
  MapParking,
  MapSegment,
  MapViewMode,
  MapWeather,
  PlacedFacility,
  ResortMapProps,
  Sector,
} from './resort-map/types';

type MapEngineInstance = ReturnType<typeof import('./resort-map/mapEngine')['createMapEngine']>;

const Map3DViewport = lazy(() => import('./resort-map/scene3d/Map3DViewport'));
const AdminEditorToolbar = lazy(() =>
  import('./resort-map/AdminMapStudio').then((m) => ({ default: m.AdminEditorToolbar }))
);
const AdminInspectorDrawer = lazy(() =>
  import('./resort-map/AdminMapStudio').then((m) => ({ default: m.AdminInspectorDrawer }))
);

function detectWebGL2(): boolean {
  if (typeof document === 'undefined') return false;
  try {
    const canvas = document.createElement('canvas');
    return Boolean(canvas.getContext('webgl2'));
  } catch {
    return false;
  }
}

function readShowAvailable(): boolean {
  try {
    return localStorage.getItem('map_show_available') !== '0';
  } catch {
    return true;
  }
}

function readInitialViewMode(supported: boolean): MapViewMode {
  if (!supported) return '2d';
  try {
    if (localStorage.getItem('map_view_mode') === '2d') return '2d';
  } catch {}
  return '3d';
}

export default function ResortMapPage(props: ResortMapProps) {
  const rootRef = useRef<HTMLDivElement>(null);
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const engineRef = useRef<MapEngineInstance | null>(null);
  const [engineApi, setEngineApi] = useState<MapEngineInstance | null>(null);
  const viewport3dRef = useRef<Map3DViewportHandle | null>(null);

  const [webglSupported, setWebglSupported] = useState<boolean>(detectWebGL2);
  const [viewMode, setViewMode] = useState<MapViewMode>(() => readInitialViewMode(detectWebGL2()));

  // Core world & live states
  const [segments, setSegments] = useState<MapSegment[]>(props.segments);
  const [sectors, setSectors] = useState<Sector[]>(props.sectors);
  const [builtIds, setBuiltIds] = useState<(string | number)[]>(props.builtSegmentIds);
  const [builtItems, setBuiltItems] = useState<Record<string, BuiltItem>>(props.builtItems);
  const [userCash, setUserCash] = useState<number>(props.userCash);
  const [buildings, setBuildings] = useState<MapBuilding[]>(props.buildings ?? []);
  const [parking, setParking] = useState<MapParking[]>(props.parking ?? []);
  const terrainParks = props.terrainParks ?? [];
  const [weather, setWeather] = useState<MapWeather | null>(props.weather ?? null);
  const [liveSkiers, setLiveSkiers] = useState<LiveSkiersSnapshot | null>(props.liveSkiers ?? null);

  const isBuilt = useCallback(
    (id: string | number) => builtIds.some((b) => sameId(b, id)),
    [builtIds]
  );
  const builtSlopes = useMemo(
    () => segments.filter((s) => s.type !== 'lift' && builtIds.some((b) => sameId(b, s.id))),
    [segments, builtIds]
  );
  const builtLiftCount = useMemo(
    () => segments.filter((s) => s.type === 'lift' && builtIds.some((b) => sameId(b, s.id))).length,
    [segments, builtIds]
  );
  const needsFirst = !props.isAdmin && (builtSlopes.length === 0 || builtLiftCount === 0);

  // UI states
  const [mapFilter, setMapFilter] = useState<string>('all');
  const [showAvailable, setShowAvailable] = useState<boolean>(readShowAvailable);
  const [lens, setLens] = useState<MapLens>('trails');
  const [lighting, setLighting] = useState<MapLighting>('day');
  const [showBuildings, setShowBuildings] = useState<boolean>(true);
  const [showSkiers, setShowSkiers] = useState<boolean>(true);

  const [isBuildDrawerOpen, setIsBuildDrawerOpen] = useState<boolean>(needsFirst);
  const [isInspectorDrawerOpen, setIsInspectorDrawerOpen] = useState<boolean>(false);
  const [activeTab, setActiveTab] = useState<'lift' | 'slope'>(builtSlopes.length === 0 ? 'slope' : 'lift');
  const [selectedSeg, setSelectedSeg] = useState<MapSegment | null>(null);
  const [selectedFacilityKey, setSelectedFacilityKey] = useState<string | null>(null);
  const [placingFacility, setPlacingFacility] = useState<PlacedFacility | null>(null);
  const [notice, setNotice] = useState<BuildNotice | null>(null);

  // Build options
  const [liftType, setLiftType] = useState<string>('chair_fixed');
  const [seats, setSeats] = useState<number>(props.costs.seatOptions.chair_fixed?.[0] ?? 2);
  const [slopeType, setSlopeType] = useState<string>('downhill');
  const [isConstructing, setIsConstructing] = useState<boolean>(false);
  const liftChoiceRef = useRef({ liftType, seats });
  liftChoiceRef.current = { liftType, seats };

  // Build list search / sort
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [diffFilter, setDiffFilter] = useState<string>('');
  const [sortOrder, setSortOrder] = useState<SortOrder>('cost_asc');

  // Admin 2D editor state
  const [editorState, setEditorState] = useState<EditorState>({
    active: false,
    mode: null,
    type: 'lift',
    segId: null,
    sectorId: null,
    name: '',
    sector: 0,
    slopeType: 'downhill',
    difficulty: 'blue',
    liveLength: 0,
    pointCount: 0,
    appendMode: false,
    snapEnabled: true,
    showSectorPolygons: false,
    snapStatus: null,
  });

  // Shared CSRF client
  const mapApi = useMemo(
    () =>
      createMapApi(props.csrf, (nextHash) => {
        engineRef.current?.setCsrfHash(nextHash);
      }),
    []
  );

  // Toast
  const toastTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const [toast, setToast] = useState<{ msg: string; type: 'success' | 'error' | 'warning' | 'info' } | null>(null);

  const triggerToast = useCallback((msg: string, type: 'success' | 'error' | 'warning' | 'info' = 'success') => {
    if (toastTimeoutRef.current) clearTimeout(toastTimeoutRef.current);
    setToast({ msg, type });
    toastTimeoutRef.current = setTimeout(() => setToast(null), 3200);
  }, []);

  // Computed facility placements shared identically by 3D and 2D maps
  const facilities = useMemo(
    () =>
      computePlacedFacilities(
        props.resortMap,
        props.mapConfig,
        segments,
        builtIds,
        builtItems,
        buildings,
        parking,
        terrainParks
      ),
    [props.resortMap, props.mapConfig, segments, builtIds, builtItems, buildings, parking, terrainParks]
  );

  const selectedFacility = useMemo(() => {
    if (!selectedFacilityKey) return null;
    for (const f of facilities) {
      if (f.key === selectedFacilityKey) return f;
      const child = f.clusteredFacilities?.find((c) => c.key === selectedFacilityKey);
      if (child) return child;
    }
    return null;
  }, [facilities, selectedFacilityKey]);

  // Ref for latest props/handlers used by async callbacks and 2D engine init
  const stateRef = useRef({
    builtIds,
    builtItems,
    userCash,
    segments,
    sectors,
    facilities,
    showBuildings,
    selectedFacilityKey,
    liveSkiers,
    showSkiers,
    mapFilter,
    placingFacility,
  });
  stateRef.current = {
    builtIds,
    builtItems,
    userCash,
    segments,
    sectors,
    facilities,
    showBuildings,
    selectedFacilityKey,
    liveSkiers,
    showSkiers,
    mapFilter,
    placingFacility,
  };

  /*
   * Fill the screen between the navbar and the phone dock. A fixed calc() left the bottom of the
   * map, and anything placed there, under the dock.
   */
  const [frameHeight, setFrameHeight] = useState<number | null>(null);
  useLayoutEffect(() => {
    function fit() {
      const el = rootRef.current;
      if (!el) return;
      const top = el.getBoundingClientRect().top + window.scrollY;
      const dock = document.querySelector('.mobile-bottom-dock') as HTMLElement | null;
      const dockH = dock && getComputedStyle(dock).display !== 'none' ? dock.offsetHeight : 0;
      setFrameHeight(Math.max(320, window.innerHeight - top - dockH));
    }
    fit();
    window.addEventListener('resize', fit);
    const ro = new ResizeObserver(fit);
    ro.observe(document.body);
    return () => {
      window.removeEventListener('resize', fit);
      ro.disconnect();
    };
  }, []);

  // Facility select & place handlers
  const handleSelectFacility = useCallback((fac: PlacedFacility) => {
    setPlacingFacility(null);
    setSelectedSeg(null);
    setNotice(null);
    engineRef.current?.deselectSegment();
    setSelectedFacilityKey(fac.key);
    setIsInspectorDrawerOpen(false);
    setIsBuildDrawerOpen(true);
  }, []);

  const handlePlaceFacility = useCallback(
    async (mapX: number, mapY: number) => {
      const target = stateRef.current.placingFacility;
      if (!target || (target.kind !== 'building' && target.kind !== 'parking')) return;
      setPlacingFacility(null);
      try {
        const res = await mapApi.postJSON('/map/building-position', {
          kind: target.kind,
          id: target.id,
          map_x: mapX,
          map_y: mapY,
        });
        if (!res?.success) {
          triggerToast(res?.error || 'Could not move facility.', 'error');
          return;
        }
        if (target.kind === 'building') {
          setBuildings((prev) =>
            prev.map((b) =>
              b.id === target.id
                ? { ...b, map_x: res.map_x, map_y: res.map_y, resort_map: res.resort_map }
                : b
            )
          );
        } else {
          setParking((prev) =>
            prev.map((p) =>
              p.id === target.id
                ? { ...p, map_x: res.map_x, map_y: res.map_y, resort_map: res.resort_map }
                : p
            )
          );
        }
        triggerToast(`Moved ${target.label}`, 'success');
      } catch (err: any) {
        triggerToast(err?.message || 'Could not save position.', 'error');
      }
    },
    [mapApi, triggerToast]
  );

  const handleResetFacility = useCallback(
    async (fac: PlacedFacility) => {
      if (fac.kind !== 'building' && fac.kind !== 'parking') return;
      try {
        const res = await mapApi.postJSON('/map/building-position/reset', {
          kind: fac.kind,
          id: fac.id,
        });
        if (!res?.success) {
          triggerToast(res?.error || 'Could not reset position.', 'error');
          return;
        }
        if (fac.kind === 'building') {
          setBuildings((prev) =>
            prev.map((b) => (b.id === fac.id ? { ...b, map_x: null, map_y: null, resort_map: null } : b))
          );
        } else {
          setParking((prev) =>
            prev.map((p) => (p.id === fac.id ? { ...p, map_x: null, map_y: null, resort_map: null } : p))
          );
        }
        triggerToast(`Reset ${fac.label} to auto placement`, 'info');
      } catch (err: any) {
        triggerToast(err?.message || 'Could not reset position.', 'error');
      }
    },
    [mapApi, triggerToast]
  );

  const applyLiveSnapshot = useCallback((live: LiveSkiersSnapshot | null, nextWeather?: MapWeather | null) => {
    if (live) {
      setLiveSkiers(live);
      if (live.perBuilding) {
        setBuildings((prev) =>
          prev.map((b) => {
            const u = live.perBuilding[String(b.id)];
            return u ? { ...b, guests: u.guests, occupancyPct: u.occupancyPct } : b;
          })
        );
      }
    }
    if (nextWeather) setWeather(nextWeather);
  }, []);

  // Dynamically load & mount 2D Leaflet engine only when viewMode === '2d'
  useEffect(() => {
    if (viewMode !== '2d' || !mapContainerRef.current) return;
    let cancelled = false;
    let mountedEngine: MapEngineInstance | null = null;

    import('./resort-map/mapEngine').then(({ createMapEngine }) => {
      if (cancelled || !mapContainerRef.current) return;
      const snap = stateRef.current;
      const engine = createMapEngine(
        {
          ...props,
          segments: snap.segments,
          sectors: snap.sectors,
          builtSegmentIds: snap.builtIds,
          builtItems: snap.builtItems,
          userCash: snap.userCash,
          liveSkiers: snap.liveSkiers,
          csrf: { name: props.csrf.name, hash: mapApi.getCsrfHash() },
        },
        {
          onSelectSegment: (seg) => {
            setSelectedFacilityKey(null);
            setSelectedSeg(seg);
            if (seg) {
              setNotice(null);
              fitLiftChoice(seg);
              if (seg.type !== 'lift') {
                setSlopeType(seg.type && seg.type !== 'slope' ? seg.type : 'downhill');
              }
            }
          },
          onSelectFacility: handleSelectFacility,
          onPlaceFacility: handlePlaceFacility,
          onOpenBuildDrawer: (tab) => {
            setIsInspectorDrawerOpen(false);
            setIsBuildDrawerOpen(true);
            setActiveTab(tab);
          },
          onCloseBuildDrawer: () => {
            setIsBuildDrawerOpen(false);
            setSelectedSeg(null);
            setSelectedFacilityKey(null);
          },
          onEditorStateChange: (state) => setEditorState(state),
          onSegmentsUpdate: (segs) => setSegments(segs),
          onSectorsUpdate: (secs) => setSectors(secs),
          onBuiltUpdate: (ids, items) => {
            setBuiltIds(ids);
            setBuiltItems(items);
          },
          onCashUpdate: (cash) => {
            setUserCash(cash);
            emitCashChanged(cash);
          },
          onLiveSkiersUpdate: (live) => applyLiveSnapshot(live),
          onCsrfRotate: (h) => mapApi.setCsrfHash(h),
          onToast: triggerToast,
        }
      );

      engine.init(mapContainerRef.current);
      mountedEngine = engine;
      engineRef.current = engine;
      setEngineApi(engine);
      setShowAvailable(engine.getShowAvailable());
      if (needsFirst) engine.setBuildMode(builtSlopes.length === 0 ? 'slope' : 'lift');
      if (snap.mapFilter !== 'all') engine.setFilter(snap.mapFilter);
      engine.setFacilities(snap.facilities, snap.showBuildings, snap.selectedFacilityKey);
      engine.setPlacingMode(Boolean(snap.placingFacility));
      engine.updateLiveSkiers(snap.liveSkiers, snap.showSkiers);
      requestAnimationFrame(syncMapSize);
    });

    return () => {
      cancelled = true;
      if (mountedEngine) {
        mountedEngine.destroy();
      }
      if (engineRef.current === mountedEngine) {
        engineRef.current = null;
      }
      setEngineApi(null);
    };
  }, [viewMode]);

  // Keep 2D engine overlays synced when state changes while 2D is mounted
  useEffect(() => {
    if (viewMode !== '2d' || !engineRef.current) return;
    engineRef.current.setFacilities(facilities, showBuildings, selectedFacilityKey);
  }, [viewMode, facilities, showBuildings, selectedFacilityKey]);

  useEffect(() => {
    if (viewMode !== '2d' || !engineRef.current) return;
    engineRef.current.updateLiveSkiers(liveSkiers, showSkiers);
  }, [viewMode, liveSkiers, showSkiers]);

  useEffect(() => {
    if (viewMode !== '2d' || !engineRef.current) return;
    engineRef.current.setPlacingMode(Boolean(placingFacility));
  }, [viewMode, placingFacility]);

  // Live skiers and weather every 30 s while the tab is visible
  useEffect(() => {
    let lastFetchAt = Date.now();
    const poll = async () => {
      if (document.hidden) return;
      try {
        const res = await mapApi.getJSON('/map/live');
        if (res?.success) {
          lastFetchAt = Date.now();
          applyLiveSnapshot(res.liveSkiers ?? null, res.weather ?? null);
        }
      } catch {
        // Silent background refresh
      }
    };

    const timer = setInterval(poll, 30000);
    const onVis = () => {
      if (!document.hidden && Date.now() - lastFetchAt > 20000) {
        poll();
      }
    };
    document.addEventListener('visibilitychange', onVis);
    return () => {
      clearInterval(timer);
      document.removeEventListener('visibilitychange', onVis);
    };
  }, [mapApi, applyLiveSnapshot]);

  // Keyboard shortcuts (Escape cancels placement or closes panel; F fits mountain)
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      const tag = (e.target as HTMLElement)?.tagName?.toLowerCase() || '';
      if (tag === 'input' || tag === 'select' || tag === 'textarea') return;
      if (e.key === 'Escape' && placingFacility) {
        e.preventDefault();
        setPlacingFacility(null);
      } else if ((e.key === 'f' || e.key === 'F') && !editorState.active) {
        if (viewMode === '3d') viewport3dRef.current?.fit();
        else engineRef.current?.fitBounds();
      }
    };
    document.addEventListener('keydown', onKey);
    return () => document.removeEventListener('keydown', onKey);
  }, [placingFacility, editorState.active, viewMode]);

  const panelOpen = isBuildDrawerOpen || isInspectorDrawerOpen;
  function syncMapSize() {
    if (!engineRef.current || !mapContainerRef.current) return;
    const phone = window.matchMedia('(max-width: 767px)').matches;
    const pad = phone && panelOpen ? mapContainerRef.current.getBoundingClientRect().height * 0.5 : 0;
    engineRef.current.resize(pad);
  }
  useEffect(() => {
    if (frameHeight && viewMode === '2d') syncMapSize();
  }, [frameHeight, panelOpen, viewMode]);

  useEffect(() => {
    document.body.classList.toggle('map-drawer-open', isBuildDrawerOpen || isInspectorDrawerOpen);
    return () => {
      document.body.classList.remove('map-drawer-open');
    };
  }, [isBuildDrawerOpen, isInspectorDrawerOpen]);

  // Keep the carrier size valid for the chosen lift system
  useEffect(() => {
    const opts = props.costs.seatOptions[liftType] || [2];
    if (!opts.includes(seats)) setSeats(opts[0]);
  }, [liftType]);

  const isReleased = (s: MapSegment) => {
    if (props.isAdmin || props.releasedSectorIds.length === 0) return true;
    const secVal = Number(s.sector || 0);
    return secVal === 0 || props.releasedSectorIds.includes(secVal);
  };

  const buildable = useMemo(() => segments.filter((s) => !isBuilt(s.id) && isReleased(s)), [segments, builtIds]);
  const availLiftsCount = buildable.filter((s) => s.type === 'lift').length;
  const availSlopesCount = buildable.length - availLiftsCount;

  const availableItems = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    const list = buildable.filter((s) => {
      if ((activeTab === 'lift') !== (s.type === 'lift')) return false;
      if (q && !(s.name || '').toLowerCase().includes(q)) return false;
      if (activeTab === 'slope' && diffFilter && s.difficulty !== diffFilter) return false;
      return true;
    });
    const price = (s: MapSegment) => cheapestCost(s, props.costs);
    list.sort((a, b) => {
      if (sortOrder === 'cost_asc') return price(a) - price(b);
      if (sortOrder === 'cost_desc') return price(b) - price(a);
      if (sortOrder === 'len_desc') return (Number(b.length_meters) || 0) - (Number(a.length_meters) || 0);
      return String(a.name || '').localeCompare(String(b.name || ''), undefined, { numeric: true, sensitivity: 'base' });
    });
    return list;
  }, [buildable, activeTab, searchQuery, diffFilter, sortOrder]);

  const { suggestions, suggestionTitle } = useMemo((): { suggestions: Suggestion[]; suggestionTitle: string | null } => {
    if (props.isAdmin) return { suggestions: [], suggestionTitle: null };
    if (builtSlopes.length === 0 && activeTab === 'slope') {
      const slopes = buildable.filter((s) => s.type !== 'lift');
      const easy = slopes.filter((s) => s.difficulty === 'green' || s.difficulty === 'blue');
      const pool = (easy.length >= 3 ? easy : slopes).slice().sort((a, b) => buildCost(a, props.costs) - buildCost(b, props.costs));
      return { suggestionTitle: 'Build your first slope', suggestions: pool.slice(0, 3).map((seg) => ({ seg, reason: 'Good first slope' })) };
    }
    if (builtSlopes.length > 0 && builtLiftCount === 0 && activeTab === 'lift') {
      const ranked = buildable
        .filter((s) => s.type === 'lift')
        .map((seg) => {
          let best = Infinity;
          let near = builtSlopes[0];
          for (const slope of builtSlopes) {
            const d = endDistance(seg, slope);
            if (d < best) {
              best = d;
              near = slope;
            }
          }
          return { seg, best, near };
        })
        .filter((r) => Number.isFinite(r.best))
        .sort((a, b) => a.best - b.best)
        .slice(0, 3);
      return { suggestionTitle: 'Now add a lift', suggestions: ranked.map((r) => ({ seg: r.seg, reason: `Near ${r.near.name || 'your slope'}` })) };
    }
    return { suggestions: [], suggestionTitle: null };
  }, [buildable, builtSlopes, builtLiftCount, activeTab, props.isAdmin]);

  const calculatedCost = selectedSeg ? buildCost(selectedSeg, props.costs, liftType, seats) : 0;
  const selectedItem = selectedSeg ? builtItems[String(selectedSeg.id)] ?? builtItems[Number(selectedSeg.id) as any] : undefined;
  const selectedIsBuilt = !!selectedSeg && isBuilt(selectedSeg.id);

  function handleViewMode(mode: MapViewMode) {
    if (mode === '3d' && !webglSupported) return;
    setPlacingFacility(null);
    if (mode === '3d') {
      setIsInspectorDrawerOpen(false);
    }
    setViewMode(mode);
    try {
      localStorage.setItem('map_view_mode', mode);
    } catch {}
  }

  function handleOpenMapStudio() {
    handleCloseBuildDrawer();
    setPlacingFacility(null);
    setViewMode('2d');
    try {
      localStorage.setItem('map_view_mode', '2d');
    } catch {}
    setIsInspectorDrawerOpen(true);
  }

  function handleFilterClick(filter: string) {
    setMapFilter(filter);
    if (filter === 'buildings') setShowBuildings(true);
    engineRef.current?.setFilter(filter);
  }

  function handleShowAvailable(v: boolean) {
    setShowAvailable(v);
    try {
      localStorage.setItem('map_show_available', v ? '1' : '0');
    } catch {}
    engineRef.current?.setShowAvailable(v);
  }

  function handleOpenBuildDrawer(tab: 'lift' | 'slope') {
    setIsInspectorDrawerOpen(false);
    setSelectedFacilityKey(null);
    setIsBuildDrawerOpen(true);
    setActiveTab(tab);
    engineRef.current?.setBuildMode(tab);
  }

  function handleTab(tab: 'lift' | 'slope') {
    setActiveTab(tab);
    engineRef.current?.setBuildMode(tab);
  }

  function handleCloseBuildDrawer() {
    setIsBuildDrawerOpen(false);
    setSelectedSeg(null);
    setSelectedFacilityKey(null);
    setPlacingFacility(null);
    setNotice(null);
    engineRef.current?.deselectSegment();
    engineRef.current?.setBuildMode(null);
  }

  function handleBack() {
    engineRef.current?.deselectSegment();
    setSelectedSeg(null);
    setSelectedFacilityKey(null);
    setPlacingFacility(null);
  }

  /**
   * The run list prices a lift at its cheapest system ("from ..."). If the system picked last time costs
   * more than the player has, open on the cheapest one they can afford, so the panel doesn't start on
   * "Not enough cash" for a lift the list showed as buildable.
   */
  function fitLiftChoice(seg: MapSegment) {
    if (seg.type !== 'lift') return;
    const cash = stateRef.current.userCash;
    const { liftType: t, seats: n } = liftChoiceRef.current;
    if (buildCost(seg, props.costs, t, n) <= cash) return;
    let best: { t: string; n: number; cost: number } | null = null;
    for (const [type, seatList] of Object.entries(props.costs.seatOptions)) {
      for (const sn of seatList) {
        const cost = buildCost(seg, props.costs, type, sn);
        if (cost <= cash && (!best || cost < best.cost)) best = { t: type, n: sn, cost };
      }
    }
    if (best) {
      setLiftType(best.t);
      setSeats(best.n);
    }
  }

  function handleSelectSeg(seg: MapSegment) {
    fitLiftChoice(seg);
    setSelectedFacilityKey(null);
    setPlacingFacility(null);
    setSelectedSeg(seg);
    setNotice(null);
    if (seg.type !== 'lift') {
      setSlopeType(seg.type && seg.type !== 'slope' ? seg.type : 'downhill');
    }
    setIsInspectorDrawerOpen(false);
    setIsBuildDrawerOpen(true);
    if (viewMode === '2d') {
      engineRef.current?.selectSegment(seg);
    } else {
      viewport3dRef.current?.focusSegment(seg.id);
    }
  }

  async function handleConstruct() {
    if (!selectedSeg || isConstructing) return;
    const seg = selectedSeg;
    const wasSlope = seg.type !== 'lift';
    const body: Record<string, any> = { segment_id: seg.id };
    if (wasSlope) {
      body.slope_type = slopeType || 'downhill';
    } else {
      body.lift_type = liftType || 'chair_fixed';
      body.seats = seats || 4;
    }

    setIsConstructing(true);
    try {
      const res = await mapApi.postJSON('/map/build', body);
      setIsConstructing(false);
      if (!res?.success) {
        triggerToast(res?.error || 'Construction failed', 'error');
        return;
      }

      const nextBuiltIds = [...builtIds, seg.id];
      const nextBuiltItems = {
        ...builtItems,
        ...(res.item ? { [String(seg.id)]: res.item } : {}),
      };
      setBuiltIds(nextBuiltIds);
      setBuiltItems(nextBuiltItems);
      const nextCash = typeof res.new_cash === 'number' ? res.new_cash : userCash;
      if (typeof res.new_cash === 'number') {
        setUserCash(res.new_cash);
        emitCashChanged(res.new_cash);
      }
      if (res.liveSkiers) applyLiveSnapshot(res.liveSkiers);
      engineRef.current?.syncBuiltState(nextBuiltIds, nextBuiltItems, nextCash);
      engineRef.current?.deselectSegment();

      setSelectedSeg(null);
      const slopes = builtSlopes.length + (wasSlope ? 1 : 0);
      const lifts = builtLiftCount + (wasSlope ? 0 : 1);
      const next: BuildNotice['next'] = lifts === 0 ? 'lift' : slopes === 0 ? 'slope' : 'tickets';
      setNotice({ name: seg.name || (wasSlope ? 'your slope' : 'your lift'), next });
      if (next === 'lift' || next === 'slope') handleTab(next);
      if (typeof (window as any).loadTutorial === 'function') (window as any).loadTutorial();
    } catch (err: any) {
      setIsConstructing(false);
      triggerToast(err?.message || 'Construction failed', 'error');
    }
  }

  async function handleDemolish(done: (ok: boolean) => void) {
    if (!selectedSeg || !selectedItem?.id) return done(false);
    const seg = selectedSeg;
    const name = seg.name || (seg.type === 'lift' ? 'this lift' : 'this slope');
    try {
      const res = await mapApi.postJSON(`/map/demolish/${selectedItem.id}`, {});
      if (!res?.success) {
        triggerToast(res?.error || `Could not demolish ${name}. Nothing was changed.`, 'error');
        return done(false);
      }
      const nextBuiltIds = builtIds.filter((id) => !sameId(id, seg.id));
      const nextBuiltItems = { ...builtItems };
      delete nextBuiltItems[String(seg.id)];
      delete (nextBuiltItems as Record<number, BuiltItem>)[Number(seg.id)];
      setBuiltIds(nextBuiltIds);
      setBuiltItems(nextBuiltItems);
      const nextCash = typeof res.new_cash === 'number' ? res.new_cash : userCash;
      if (typeof res.new_cash === 'number') {
        setUserCash(res.new_cash);
        emitCashChanged(res.new_cash);
      }
      if (res.liveSkiers) applyLiveSnapshot(res.liveSkiers);
      engineRef.current?.syncBuiltState(nextBuiltIds, nextBuiltItems, nextCash);
      engineRef.current?.deselectSegment();
      setSelectedSeg(null);
      triggerToast(res.message || `${name} demolished.`, 'success');
      done(true);
    } catch (err: any) {
      triggerToast(err?.message || `Could not demolish ${name}.`, 'error');
      done(false);
    }
  }

  async function handleSwap(option: LiftSwapOption, done: (ok: boolean) => void) {
    if (!selectedSeg || !selectedItem?.id) return done(false);
    const seg = selectedSeg;
    const name = seg.name || 'this lift';
    try {
      const res = await mapApi.postJSON(`/map/upgrade/${selectedItem.id}`, {
        lift_type: option.lift_type,
        seats: option.seats,
      });
      if (!res?.success) {
        triggerToast(res?.error || `Could not replace ${name}. Nothing was changed.`, 'error');
        return done(false);
      }
      const nextBuiltItems = {
        ...builtItems,
        ...(res.item ? { [String(seg.id)]: res.item } : {}),
      };
      setBuiltItems(nextBuiltItems);
      const nextCash = typeof res.new_cash === 'number' ? res.new_cash : userCash;
      if (typeof res.new_cash === 'number') {
        setUserCash(res.new_cash);
        emitCashChanged(res.new_cash);
      }
      if (res.liveSkiers) applyLiveSnapshot(res.liveSkiers);
      engineRef.current?.syncBuiltState(builtIds, nextBuiltItems, nextCash);
      triggerToast(res.message || `${name} replaced.`, 'success');
      done(true);
    } catch (err: any) {
      triggerToast(err?.message || `Could not replace ${name}.`, 'error');
      done(false);
    }
  }

  function handleResortChange(eOrVal: React.ChangeEvent<HTMLSelectElement> | string | number) {
    const val = typeof eOrVal === 'object' && eOrVal !== null && 'target' in eOrVal ? eOrVal.target.value : String(eOrVal);
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/map/change-map';
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = props.csrf.name;
    csrfInput.value = mapApi.getCsrfHash();
    form.appendChild(csrfInput);
    const mapInput = document.createElement('input');
    mapInput.type = 'hidden';
    mapInput.name = 'map';
    mapInput.value = val;
    form.appendChild(mapInput);
    document.body.appendChild(form);
    form.submit();
  }

  function getSectorLabel(secId: any) {
    if (!secId || secId === '0' || secId === 0) return props.isAdmin ? 'Default (0)' : 'Main area';
    const sec = sectors.find((s) => String(s.id) === String(secId) || s.name === String(secId));
    return sec ? sec.name : `Sector #${secId}`;
  }

  const adminActions = props.isAdmin ? (
    <>
      {viewMode === '2d' && (
        <Button
          variant="outline"
          onPress={() => {
            handleCloseBuildDrawer();
            setIsInspectorDrawerOpen((prev) => !prev);
          }}
          className="min-h-11 gap-1.5 font-semibold text-foreground md:min-h-9"
          aria-label="Open Segment and Sector Manager"
        >
          <Icon name="fa-list-check" className="text-link" />
          <span className="hidden sm:inline">Segments & Sectors</span>
        </Button>
      )}
      <Select
        value={props.resortMap}
        onChange={handleResortChange}
        aria-label="Resort map"
        options={Object.entries(props.resortMaps).map(([k, rm]) => ({
          id: k,
          label: rm.name,
        }))}
      />
    </>
  ) : undefined;

  const cashText = formatCurrency(userCash, props.isImperial);
  const settle = settleText(props.settleAt);
  const builtFacilityCount = buildings.length + parking.length + terrainParks.length;

  return (
    <div
      ref={rootRef}
      style={frameHeight ? { height: frameHeight } : undefined}
      className="relative flex h-[calc(100dvh-65px)] w-full flex-col overflow-hidden bg-background text-foreground"
    >
      <MapHeader
        mapName={props.mapConfig.name}
        builtSlopes={builtSlopes.length}
        builtLifts={builtLiftCount}
        builtBuildingsCount={builtFacilityCount}
        cashText={cashText}
        filter={mapFilter}
        onFilter={handleFilterClick}
        showAvailable={showAvailable}
        onShowAvailable={handleShowAvailable}
        onFit={() => (viewMode === '3d' ? viewport3dRef.current?.fit() : engineRef.current?.fitBounds())}
        onBuild={() => handleOpenBuildDrawer(builtSlopes.length === 0 ? 'slope' : activeTab)}
        isAdmin={props.isAdmin}
        adminActions={adminActions}
        viewMode={viewMode}
        onViewMode={handleViewMode}
        webglSupported={webglSupported}
        lens={lens}
        onLens={setLens}
        showBuildings={showBuildings}
        onShowBuildings={setShowBuildings}
        showSkiers={showSkiers}
        onShowSkiers={setShowSkiers}
        liveSkiers={liveSkiers}
        weather={weather}
        onOpenMapStudio={handleOpenMapStudio}
      />

      {/* Main Map Viewport */}
      <div className="relative flex-1 overflow-hidden">
        {viewMode === '3d' ? (
          <Suspense
            fallback={
              <div className="flex h-full w-full items-center justify-center gap-2 text-sm text-muted">
                <Spinner size="sm" />
                <span>Loading 3D mountain view...</span>
              </div>
            }
          >
            <Map3DViewport
              mapConfig={props.mapConfig}
              segments={segments}
              builtIds={builtIds}
              builtItems={builtItems}
              facilities={facilities}
              liveSkiers={liveSkiers}
              groomerActive={props.groomerFleet?.active ?? 0}
              nightLitCount={props.nightLighting?.activeCount ?? 0}
              weather={weather}
              filter={mapFilter}
              showAvailable={showAvailable}
              showBuildings={showBuildings}
              showSkiers={showSkiers}
              selectedSegId={selectedSeg?.id ?? null}
              selectedFacilityKey={selectedFacilityKey}
              lens={lens}
              lighting={lighting}
              onLightingChange={setLighting}
              placingFacility={placingFacility}
              onCancelPlacing={() => setPlacingFacility(null)}
              onSelectSegment={handleSelectSeg}
              onSelectFacility={handleSelectFacility}
              onPlaceFacility={handlePlaceFacility}
              onFallbackTo2D={(reason) => {
                setWebglSupported(false);
                setViewMode('2d');
                triggerToast(reason || 'Switched to 2D map.', 'info');
              }}
              viewportRef={viewport3dRef}
              distUnit={props.distUnit}
              isImperial={props.isImperial}
            />
          </Suspense>
        ) : (
          <>
            <div ref={mapContainerRef} id="map" />
            {!engineApi && (
              <div role="status" className="absolute inset-0 z-[700] flex items-center justify-center gap-2 bg-background text-sm text-muted">
                <Spinner size="sm" />
                <span>Loading the trail map…</span>
              </div>
            )}
            <div
              id="hdBadge"
              className="absolute top-3 right-3 z-[800] hidden items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1.5 text-sm text-foreground shadow-sm"
            >
              <Spinner size="sm" />
              <span>Loading the detailed map…</span>
            </div>
            {placingFacility && (
              <div className="absolute top-3 left-1/2 z-[880] flex -translate-x-1/2 items-center gap-2 rounded-xl border border-accent bg-surface px-3.5 py-2 text-sm text-foreground shadow-lg">
                <Icon name="fa-location-dot" className="text-link" />
                <span>
                  Click anywhere on the mountain to move <b>{placingFacility.label}</b>
                </span>
                <Button size="sm" variant="ghost" onPress={() => setPlacingFacility(null)} className="min-h-9 text-muted hover:text-foreground">
                  Cancel
                </Button>
              </div>
            )}
          </>
        )}

        <MapKey
          isAdmin={props.isAdmin}
          viewMode={viewMode}
          lens={lens}
          showBuildings={showBuildings}
          showSkiers={showSkiers}
        />

        {/* Lazy Admin 2D Map Studio Toolbar */}
        {props.isAdmin && viewMode === '2d' && (
          <Suspense fallback={null}>
            <AdminEditorToolbar
              editorState={editorState}
              sectors={sectors}
              distUnit={props.distUnit}
              panelOpen={panelOpen}
              engine={engineApi}
            />
          </Suspense>
        )}

        {/* Build button on the map, for when the panel is closed */}
        {!isBuildDrawerOpen && !isInspectorDrawerOpen && !editorState.active && !placingFacility && (
          <Button
            variant="primary"
            isIconOnly
            onPress={() => handleOpenBuildDrawer(builtSlopes.length === 0 ? 'slope' : activeTab)}
            className="absolute right-4 bottom-4 z-[900] size-14 rounded-full shadow-lg md:hidden"
            aria-label="Build"
          >
            <Icon name="fa-hammer" className="text-xl" />
          </Button>
        )}

        {/* Inspector / Build Panel */}
        {isBuildDrawerOpen && (
          selectedFacility ? (
            <MapPanel
              title={selectedFacility.label}
              subtitle={selectedFacility.typeLabel}
              icon={selectedFacility.icon}
              onClose={handleCloseBuildDrawer}
              onBack={handleBack}
            >
              <BuildingInspectorView
                facility={selectedFacility}
                isImperial={props.isImperial}
                canReposition
                onStartMove={(f) => {
                  setShowBuildings(true);
                  setPlacingFacility(f);
                }}
                onResetMove={handleResetFacility}
                onSelectClusterItem={(child) => setSelectedFacilityKey(child.key)}
                onZoom={() => {
                  if (viewMode === '3d') viewport3dRef.current?.focusPoint(selectedFacility.mapX, selectedFacility.mapY);
                  else engineRef.current?.zoomToPoint(selectedFacility.mapX, selectedFacility.mapY);
                }}
              />
            </MapPanel>
          ) : selectedSeg && selectedIsBuilt && selectedItem ? (
            <MapPanel title={selectedSeg.name || 'Your run'} subtitle="Yours" onClose={handleCloseBuildDrawer} onBack={handleBack}>
              <BuiltRunView
                seg={selectedSeg}
                item={selectedItem}
                isImperial={props.isImperial}
                distUnit={props.distUnit}
                sectorLabel={getSectorLabel(selectedSeg.sector)}
                economy={props.economy}
                traffic={liveSkiers?.perSegment?.[String(selectedSeg.id)] ?? null}
                onZoom={() =>
                  viewMode === '3d'
                    ? viewport3dRef.current?.focusSegment(selectedSeg.id)
                    : engineRef.current?.zoomToSegment(selectedSeg.id)
                }
                onDemolish={handleDemolish}
                cash={userCash}
                onSwap={handleSwap}
              />
            </MapPanel>
          ) : selectedSeg && !selectedIsBuilt ? (
            <MapPanel
              title={selectedSeg.name || 'Unnamed run'}
              subtitle={`Cash ${cashText}`}
              onClose={handleCloseBuildDrawer}
              onBack={handleBack}
              footer={
                <BuildFooter
                  seg={selectedSeg}
                  cost={calculatedCost}
                  cash={userCash}
                  isImperial={props.isImperial}
                  isBuilding={isConstructing}
                  onBuild={handleConstruct}
                />
              }
            >
              <BuildView
                seg={selectedSeg}
                costs={props.costs}
                economy={props.economy}
                isImperial={props.isImperial}
                distUnit={props.distUnit}
                sectorLabel={getSectorLabel(selectedSeg.sector)}
                liftType={liftType}
                onLiftType={setLiftType}
                seats={seats}
                onSeats={setSeats}
                slopeType={slopeType}
                onSlopeType={setSlopeType}
                onZoom={() =>
                  viewMode === '3d'
                    ? viewport3dRef.current?.focusSegment(selectedSeg.id)
                    : engineRef.current?.zoomToSegment(selectedSeg.id)
                }
              />
            </MapPanel>
          ) : (
            <MapPanel title="Build" subtitle={`Cash ${cashText}`} icon="fa-hammer" onClose={handleCloseBuildDrawer}>
              <BrowseView
                tab={activeTab}
                onTab={handleTab}
                liftCount={availLiftsCount}
                slopeCount={availSlopesCount}
                items={availableItems}
                suggestions={suggestions}
                suggestionTitle={suggestionTitle}
                notice={notice}
                settle={settle}
                costs={props.costs}
                cash={userCash}
                isImperial={props.isImperial}
                distUnit={props.distUnit}
                search={searchQuery}
                onSearch={setSearchQuery}
                grade={diffFilter}
                onGrade={setDiffFilter}
                sort={sortOrder}
                onSort={setSortOrder}
                onSelect={handleSelectSeg}
              />
            </MapPanel>
          )
        )}

        {/* Lazy Admin Inspector Drawer (2D Map Studio) */}
        {props.isAdmin && viewMode === '2d' && isInspectorDrawerOpen && (
          <Suspense fallback={null}>
            <AdminInspectorDrawer
              sectors={sectors}
              segments={segments}
              distUnit={props.distUnit}
              mapFilter={mapFilter}
              engine={engineApi}
              getSectorLabel={getSectorLabel}
              onSegmentsChange={setSegments}
              onClose={() => setIsInspectorDrawerOpen(false)}
              onToast={triggerToast}
            />
          </Suspense>
        )}

        {/* Toast for results the panel doesn't show itself (errors, admin actions) */}
        {toast && (
          <div role={toast.type === 'error' ? 'alert' : 'status'} className="absolute top-16 right-3 left-3 z-[1100] flex justify-center md:top-3 md:left-auto">
            <div
              className={`rounded-lg border px-4 py-2 text-sm font-semibold shadow-lg ${
                toast.type === 'error'
                  ? 'border-danger bg-danger text-danger-foreground'
                  : toast.type === 'warning'
                    ? 'border-warning bg-warning text-warning-foreground'
                    : toast.type === 'info'
                      ? 'border-border bg-surface text-foreground'
                      : 'border-success bg-success text-success-foreground'
              }`}
            >
              {toast.msg}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
