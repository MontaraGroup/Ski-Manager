import { useEffect, useLayoutEffect, useMemo, useRef, useState } from 'react';
import { Button, Chip, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { createMapEngine } from './resort-map/mapEngine';
import type { BuiltItem, MapSegment, ResortMapProps, Sector, EditorState } from './resort-map/types';
import { MapHeader } from './resort-map/MapHeader';
import { MapPanel } from './resort-map/MapPanel';
import { MapKey } from './resort-map/MapKey';
import { BuildFooter, BuildView } from './resort-map/BuildView';
import { BuiltRunView } from './resort-map/BuiltRunView';
import { BrowseView, type BuildNotice, type SortOrder, type Suggestion } from './resort-map/BrowseView';
import { TrailSign, buildCost, cheapestCost, endDistance, formatCurrency, sameId, settleText } from './resort-map/shared';

export default function ResortMapPage(props: ResortMapProps) {
  const rootRef = useRef<HTMLDivElement>(null);
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const engineRef = useRef<ReturnType<typeof createMapEngine> | null>(null);

  // Data states
  const [segments, setSegments] = useState<MapSegment[]>(props.segments);
  const [sectors, setSectors] = useState<Sector[]>(props.sectors);
  const [builtIds, setBuiltIds] = useState<(string | number)[]>(props.builtSegmentIds);
  const [builtItems, setBuiltItems] = useState<Record<string, BuiltItem>>(props.builtItems);
  const [userCash, setUserCash] = useState<number>(props.userCash);

  const isBuilt = (id: string | number) => builtIds.some((b) => sameId(b, id));
  const builtSlopes = useMemo(() => segments.filter((s) => s.type !== 'lift' && builtIds.some((b) => sameId(b, s.id))), [segments, builtIds]);
  const builtLiftCount = useMemo(() => segments.filter((s) => s.type === 'lift' && builtIds.some((b) => sameId(b, s.id))).length, [segments, builtIds]);
  // A new player gets the panel open on their first step: a slope, then a lift
  const needsFirst = !props.isAdmin && (builtSlopes.length === 0 || builtLiftCount === 0);

  // UI states
  const [mapFilter, setMapFilter] = useState<string>('all');
  const [showAvailable, setShowAvailable] = useState<boolean>(true);
  const [isBuildDrawerOpen, setIsBuildDrawerOpen] = useState<boolean>(needsFirst);
  const [isInspectorDrawerOpen, setIsInspectorDrawerOpen] = useState<boolean>(false);
  const [activeTab, setActiveTab] = useState<'lift' | 'slope'>(builtSlopes.length === 0 ? 'slope' : 'lift');
  const [selectedSeg, setSelectedSeg] = useState<MapSegment | null>(null);
  const [notice, setNotice] = useState<BuildNotice | null>(null);

  // Build options
  const [liftType, setLiftType] = useState<string>('chair_fixed');
  const [seats, setSeats] = useState<number>(props.costs.seatOptions.chair_fixed?.[0] ?? 2);
  const [slopeType, setSlopeType] = useState<string>('downhill');
  const [isConstructing, setIsConstructing] = useState<boolean>(false);

  // Build list search / sort
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [diffFilter, setDiffFilter] = useState<string>('');
  const [sortOrder, setSortOrder] = useState<SortOrder>('cost_asc');

  // Inspector search / filter
  const [inspectorSearch, setInspectorSearch] = useState<string>('');
  const [inspectorFilter, setInspectorFilter] = useState<string>('all');
  const [showNewSectorForm, setShowNewSectorForm] = useState<boolean>(false);
  const [newSecName, setNewSecName] = useState<string>('');
  const [newSecColor, setNewSecColor] = useState<string>('#3b82f6');

  // Editor state
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

  // Toast
  const toastTimeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const [toast, setToast] = useState<{ msg: string; type: 'success' | 'error' | 'warning' | 'info' } | null>(null);

  function triggerToast(msg: string, type: 'success' | 'error' | 'warning' | 'info' = 'success') {
    if (toastTimeoutRef.current) clearTimeout(toastTimeoutRef.current);
    setToast({ msg, type });
    toastTimeoutRef.current = setTimeout(() => setToast(null), 3200);
  }

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
    // The navbar and stats bar above can finish rendering after this island: measure again when they do
    const ro = new ResizeObserver(fit);
    ro.observe(document.body);
    return () => {
      window.removeEventListener('resize', fit);
      ro.disconnect();
    };
  }, []);

  // Initialize Map Engine
  useEffect(() => {
    if (!mapContainerRef.current) return;

    const engine = createMapEngine(props, {
      onSelectSegment: (seg) => {
        setSelectedSeg(seg);
        if (seg) {
          setNotice(null);
          if (seg.type !== 'lift') {
            setSlopeType(seg.type && seg.type !== 'slope' ? seg.type : 'downhill');
          }
        }
      },
      onOpenBuildDrawer: (tab) => {
        setIsInspectorDrawerOpen(false);
        setIsBuildDrawerOpen(true);
        setActiveTab(tab);
      },
      onCloseBuildDrawer: () => {
        setIsBuildDrawerOpen(false);
        setSelectedSeg(null);
      },
      onEditorStateChange: (state) => setEditorState(state),
      onSegmentsUpdate: (segs) => setSegments(segs),
      onSectorsUpdate: (secs) => setSectors(secs),
      onBuiltUpdate: (ids, items) => {
        setBuiltIds(ids);
        setBuiltItems(items);
      },
      onCashUpdate: (cash) => setUserCash(cash),
      onToast: triggerToast,
    });

    engine.init(mapContainerRef.current);
    engineRef.current = engine;
    setShowAvailable(engine.getShowAvailable());
    if (needsFirst) engine.setBuildMode(builtSlopes.length === 0 ? 'slope' : 'lift');
    // The frame may already have its final size by now; fit once the panel has laid out
    requestAnimationFrame(syncMapSize);

    return () => {
      engine.destroy();
      document.body.classList.remove('map-drawer-open');
    };
  }, []);

  /*
   * Leaflet measures its box once: tell it when the frame is resized. Until the player moves the map
   * (the engine tracks that), it also refits to the runs, above the phone's bottom sheet. The sheet's
   * maximum (half the map, MapPanel) is used rather than its height mid-layout.
   */
  const panelOpen = isBuildDrawerOpen || isInspectorDrawerOpen;
  function syncMapSize() {
    if (!engineRef.current || !mapContainerRef.current) return;
    const phone = window.matchMedia('(max-width: 767px)').matches;
    const pad = phone && panelOpen ? mapContainerRef.current.getBoundingClientRect().height * 0.5 : 0;
    engineRef.current.resize(pad);
  }
  useEffect(() => {
    if (frameHeight) syncMapSize();
  }, [frameHeight, panelOpen]);

  // The tutorial card (layouts/main.php) moves out of the way on phones while a panel is open
  useEffect(() => {
    document.body.classList.toggle('map-drawer-open', isBuildDrawerOpen || isInspectorDrawerOpen);
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

  // Runs for the list: this tab, search, difficulty, then sorted by the real price
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

  /*
   * First-build help. No slope yet: the three cheapest easy runs. A slope but no lift: the three lifts
   * whose ends are closest to an end of a built slope. The game doesn't require them to connect, so
   * this is a suggestion, not a rule.
   */
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

  function handleFilterClick(filter: string) {
    setMapFilter(filter);
    engineRef.current?.setFilter(filter);
  }

  function handleShowAvailable(v: boolean) {
    setShowAvailable(v);
    engineRef.current?.setShowAvailable(v);
  }

  function handleOpenBuildDrawer(tab: 'lift' | 'slope') {
    setIsInspectorDrawerOpen(false);
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
    setNotice(null);
    engineRef.current?.deselectSegment();
    engineRef.current?.setBuildMode(null);
  }

  /** Back from a run to the list, keeping the panel open */
  function handleBack() {
    engineRef.current?.deselectSegment();
    setSelectedSeg(null);
  }

  function handleSelectSeg(seg: MapSegment) {
    engineRef.current?.selectSegment(seg);
  }

  function handleConstruct() {
    if (!selectedSeg || isConstructing) return;
    const seg = selectedSeg;
    const wasSlope = seg.type !== 'lift';
    setIsConstructing(true);
    engineRef.current?.buildSegment(seg, { liftType, seats, slopeType }, (ok: boolean) => {
      setIsConstructing(false);
      if (!ok) return;
      // One next step: the other half of a working resort, then ticket prices before tonight
      const slopes = builtSlopes.length + (wasSlope ? 1 : 0);
      const lifts = builtLiftCount + (wasSlope ? 0 : 1);
      const next: BuildNotice['next'] = lifts === 0 ? 'lift' : slopes === 0 ? 'slope' : 'tickets';
      setNotice({ name: seg.name || (wasSlope ? 'your slope' : 'your lift'), next });
      if (next === 'lift' || next === 'slope') handleTab(next);
      if (typeof (window as any).loadTutorial === 'function') (window as any).loadTutorial();
    });
  }

  function handleDemolish(done: (ok: boolean) => void) {
    if (!selectedSeg) return done(false);
    engineRef.current?.demolish(selectedSeg, (ok: boolean) => {
      done(ok);
      if (ok) {
        engineRef.current?.deselectSegment();
        setSelectedSeg(null);
      }
    });
  }

  function handleResortChange(e: React.ChangeEvent<HTMLSelectElement>) {
    const val = e.target.value;
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/map/change-map';
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = props.csrf.name;
    csrfInput.value = props.csrf.hash;
    form.appendChild(csrfInput);
    const mapInput = document.createElement('input');
    mapInput.type = 'hidden';
    mapInput.name = 'map';
    mapInput.value = val;
    form.appendChild(mapInput);
    document.body.appendChild(form);
    form.submit();
  }

  // Used by the admin inspector's segment list
  function renderTrailSign(seg: MapSegment) {
    return <TrailSign seg={seg} />;
  }

  // Filtered segments for Inspector
  const inspectorSegments = useMemo(() => {
    const q = inspectorSearch.trim().toLowerCase();
    return segments.filter((s) => {
      if (inspectorFilter === 'lift' && s.type !== 'lift') return false;
      if (inspectorFilter === 'slope' && s.type === 'lift') return false;
      if (inspectorFilter === 'unassigned' && Number(s.sector || 0) !== 0) return false;
      if (q && (s.name || '').toLowerCase().indexOf(q) === -1) return false;
      return true;
    });
  }, [segments, inspectorSearch, inspectorFilter]);

  function getSectorLabel(secId: any) {
    if (!secId || secId === '0' || secId === 0) return props.isAdmin ? 'Default (0)' : 'Main area';
    const sec = sectors.find((s) => String(s.id) === String(secId) || s.name === String(secId));
    return sec ? sec.name : `Sector #${secId}`;
  }

  const adminActions = props.isAdmin ? (
    <>
      <Button
        variant="outline"
        onPress={() => {
          handleCloseBuildDrawer();
          setIsInspectorDrawerOpen((prev) => !prev);
        }}
        className="min-h-11 gap-1.5 font-semibold text-foreground md:min-h-9"
        aria-label="Open Segment & Sector Manager"
      >
        <Icon name="fa-list-check" className="text-link" />
        <span className="hidden sm:inline">Segments & Sectors</span>
      </Button>
      <select
        value={props.resortMap}
        onChange={handleResortChange}
        aria-label="Resort map"
        className="h-11 rounded-lg border border-border bg-surface px-2.5 text-sm font-medium text-foreground outline-none md:h-9"
      >
        {Object.entries(props.resortMaps).map(([k, rm]) => (
          <option key={k} value={k}>
            {rm.name}
          </option>
        ))}
      </select>
    </>
  ) : undefined;

  const cashText = formatCurrency(userCash, props.isImperial);
  const settle = settleText(props.settleAt);

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
        cashText={cashText}
        filter={mapFilter}
        onFilter={handleFilterClick}
        showAvailable={showAvailable}
        onShowAvailable={handleShowAvailable}
        onFit={() => engineRef.current?.fitBounds()}
        onBuild={() => handleOpenBuildDrawer(builtSlopes.length === 0 ? 'slope' : activeTab)}
        isAdmin={props.isAdmin}
        adminActions={adminActions}
      />

      {/* Main Map Viewport */}
      <div className="relative flex-1 overflow-hidden">
        <div ref={mapContainerRef} id="map" />

        {/* Shown by the engine while the full-size map image loads */}
        <div
          id="hdBadge"
          className="absolute top-3 right-3 z-[800] hidden items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1.5 text-sm text-foreground shadow-sm"
        >
          <Spinner size="sm" />
          <span>Loading the detailed map…</span>
        </div>

        <MapKey isAdmin={props.isAdmin} />

        {/* Admin Floating Top Editor Toolbar */}
        {props.isAdmin && (
          <div
            className={`pointer-events-none absolute top-3 left-0 right-0 z-[850] flex justify-center px-3 transition-all ${
              isBuildDrawerOpen || isInspectorDrawerOpen ? 'lg:right-[390px]' : ''
            }`}
          >
            <div className="pointer-events-auto max-w-[96vw] rounded-xl border border-white/15 bg-slate-900/95 p-2 shadow-2xl backdrop-blur-md">
              {!editorState.active ? (
                /* Idle View */
                <div className="flex flex-wrap items-center gap-2">
                  <Chip size="sm" variant="soft" color="accent" className="font-semibold">
                    <Icon name="fa-compass-drafting" className="mr-1" />
                    Map Studio
                  </Chip>

                  <div className="flex items-center gap-1.5 rounded-lg border border-white/10 bg-slate-800/80 px-2 py-0.5 text-xs">
                    <span className="font-semibold text-muted">Sector:</span>
                    <select
                      value={editorState.sector}
                      onChange={(e) => engineRef.current?.setEditorSector(Number(e.target.value))}
                      className="bg-transparent text-xs text-foreground outline-none"
                    >
                      <option value="0">Default (0)</option>
                      {sectors.map((sec) => (
                        <option key={sec.id} value={sec.id} className="bg-slate-900 text-foreground">
                          {sec.name} (#{sec.id})
                        </option>
                      ))}
                    </select>
                  </div>

                  <Button
                    size="sm"
                    variant="secondary"
                    onPress={() => engineRef.current?.startDraw('lift')}
                    className="h-7 text-xs font-semibold bg-amber-500/20 text-amber-300 hover:bg-amber-500/30"
                    aria-label="Draw New Lift (Shortcut: L)"
                  >
                    <Icon name="fa-elevator" />
                    + New Lift
                  </Button>

                  <Button
                    size="sm"
                    variant="secondary"
                    onPress={() => engineRef.current?.startDraw('slope')}
                    className="h-7 text-xs font-semibold bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30"
                    aria-label="Draw New Slope (Shortcut: S)"
                  >
                    <Icon name="fa-person-skiing" />
                    + New Slope
                  </Button>

                  <Button
                    size="sm"
                    variant={editorState.snapEnabled ? 'outline' : 'ghost'}
                    onPress={() => engineRef.current?.toggleSnap()}
                    className="h-7 text-xs font-semibold text-foreground"
                    aria-label="Magnetic Endpoint Snapping"
                  >
                    <Icon name="fa-magnet" />
                    Snap: {editorState.snapEnabled ? 'ON' : 'OFF'}
                  </Button>

                  <Button
                    size="sm"
                    variant={editorState.showSectorPolygons ? 'primary' : 'outline'}
                    onPress={() => engineRef.current?.toggleSectors()}
                    className="h-7 text-xs font-semibold"
                    aria-label="Show/Hide Sector Boundaries"
                  >
                    <Icon name="fa-draw-polygon" />
                    Sectors
                  </Button>

                  <span className="hidden text-xs text-muted xl:inline">
                    <b>L</b> Lift • <b>S</b> Slope • Click run to edit
                  </span>
                </div>
              ) : (
                /* Active Draw / Edit View */
                <div className="flex flex-col gap-2">
                  <div className="flex flex-wrap items-center gap-2">
                    <Chip
                      size="sm"
                      variant="soft"
                      color={editorState.type === 'lift' ? 'warning' : 'success'}
                      className="font-bold uppercase"
                    >
                      <Icon name="fa-pen-nib" className="mr-1" />
                      {editorState.mode === 'sector_boundary'
                        ? 'Sector Boundary'
                        : `${editorState.mode === 'edit' ? 'Edit' : 'New'} ${editorState.type}`}
                    </Chip>

                    <div className="flex items-center gap-2 rounded-lg border border-white/10 bg-slate-800/90 px-2 py-1 font-mono text-xs">
                      <span className="font-bold text-accent">
                        <Icon name="fa-ruler" className="mr-1" />
                        {editorState.liveLength.toLocaleString()} {props.distUnit}
                      </span>
                      <span className="text-muted">|</span>
                      <span className="text-link">
                        <Icon name="fa-location-dot" className="mr-1" />
                        {editorState.pointCount} pts
                      </span>
                    </div>

                    {editorState.mode !== 'sector_boundary' && (
                      <>
                        <input
                          type="text"
                          value={editorState.name}
                          onChange={(e) => engineRef.current?.setEditorName(e.target.value)}
                          placeholder="Segment name"
                          className="h-7 w-36 rounded-md border border-white/15 bg-slate-900 px-2 text-xs font-semibold text-foreground outline-none focus:border-accent"
                        />

                        <select
                          value={editorState.sector}
                          onChange={(e) => engineRef.current?.setEditorSector(Number(e.target.value))}
                          className="h-7 rounded-md border border-white/15 bg-slate-900 px-2 text-xs text-foreground outline-none"
                        >
                          <option value="0">Default (0)</option>
                          {sectors.map((sec) => (
                            <option key={sec.id} value={sec.id}>
                              {sec.name}
                            </option>
                          ))}
                        </select>

                        {editorState.type !== 'lift' && (
                          <>
                            <select
                              value={editorState.slopeType}
                              onChange={(e) => engineRef.current?.setEditorSlopeType(e.target.value)}
                              className="h-7 rounded-md border border-white/15 bg-slate-900 px-2 text-xs text-foreground outline-none"
                            >
                              <option value="downhill">Downhill</option>
                              <option value="crosscountry">Cross-Country</option>
                              <option value="snowpark">Snow Park</option>
                              <option value="luge">Luge</option>
                            </select>

                            <select
                              value={editorState.difficulty}
                              onChange={(e) => engineRef.current?.setEditorDifficulty(e.target.value)}
                              className="h-7 rounded-md border border-white/15 bg-slate-900 px-2 text-xs text-foreground outline-none"
                            >
                              <option value="green">● Green (Easy)</option>
                              <option value="blue">■ Blue (Intermediate)</option>
                              <option value="black">◆ Black (Advanced)</option>
                              <option value="double_black">◆◆ Double Black</option>
                            </select>
                          </>
                        )}

                        {editorState.mode === 'edit' && (
                          <Button
                            size="sm"
                            variant={editorState.appendMode ? 'primary' : 'outline'}
                            onPress={() => engineRef.current?.toggleAppend()}
                            className="h-7 text-xs font-semibold"
                            aria-label="Toggle whether clicking appends points"
                          >
                            <Icon name="fa-plus" /> Append: {editorState.appendMode ? 'ON' : 'OFF'}
                          </Button>
                        )}

                        <Button
                          size="sm"
                          variant="outline"
                          onPress={() => engineRef.current?.reverse()}
                          className="h-7 text-xs text-foreground"
                          aria-label="Reverse Start (S) and End (E)"
                        >
                          <Icon name="fa-right-left" /> Reverse
                        </Button>
                      </>
                    )}

                    <Button
                      size="sm"
                      variant="outline"
                      onPress={() => engineRef.current?.undo()}
                      className="h-7 text-xs text-warning"
                      aria-label="Undo point (Z)"
                    >
                      <Icon name="fa-rotate-left" /> Undo
                    </Button>

                    <Button
                      size="sm"
                      variant="secondary"
                      onPress={() => engineRef.current?.saveEditor(false)}
                      className="h-7 text-xs font-bold bg-emerald-600 text-white hover:bg-emerald-500"
                      aria-label="Save (Enter)"
                    >
                      <Icon name="fa-check" /> Save
                    </Button>

                    {editorState.mode === 'draw' && (
                      <Button
                        size="sm"
                        variant="primary"
                        onPress={() => engineRef.current?.saveEditor(true)}
                        className="h-7 text-xs font-bold"
                        aria-label="Save & Immediately Draw Next"
                      >
                        <Icon name="fa-forward-step" /> Save + Next
                      </Button>
                    )}

                    <Button
                      size="sm"
                      variant="ghost"
                      onPress={() => engineRef.current?.cancelEditor()}
                      className="h-7 text-xs text-danger"
                      aria-label="Cancel (Esc)"
                    >
                      <Icon name="fa-xmark" /> Cancel
                    </Button>
                  </div>

                  <div className="flex items-center justify-between border-t border-white/10 pt-1 text-xs text-muted">
                    <span>
                      <b>Click map</b> to add • <b>Drag dots</b> to move • <b>Click/drag + midpoints</b> to bend •{' '}
                      <b>Right-click</b> point to delete
                    </span>
                    {editorState.snapStatus && (
                      <span className="font-mono text-link">Snapped: {editorState.snapStatus}</span>
                    )}
                  </div>
                </div>
              )}
            </div>
          </div>
        )}


        {/* Build button on the map, for when the panel is closed */}
        {!isBuildDrawerOpen && !isInspectorDrawerOpen && !editorState.active && (
          <Button
            variant="primary"
            isIconOnly
            onPress={() => handleOpenBuildDrawer(builtSlopes.length === 0 ? 'slope' : activeTab)}
            className="absolute right-4 bottom-4 z-[900] size-14 rounded-full shadow-lg"
            aria-label="Build"
          >
            <Icon name="fa-hammer" className="text-xl" />
          </Button>
        )}

        {/* Player panel: a run's build view or details, or the list of runs */}
        {isBuildDrawerOpen && (
          selectedSeg && selectedIsBuilt && selectedItem ? (
            <MapPanel title={selectedSeg.name || 'Your run'} subtitle="Yours" onClose={handleCloseBuildDrawer} onBack={handleBack}>
              <BuiltRunView
                seg={selectedSeg}
                item={selectedItem}
                isImperial={props.isImperial}
                distUnit={props.distUnit}
                sectorLabel={getSectorLabel(selectedSeg.sector)}
                economyV2={props.economyV2}
                onZoom={() => engineRef.current?.zoomToSegment(selectedSeg.id)}
                onDemolish={handleDemolish}
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
                  economyV2={props.economyV2}
                  liftType={liftType}
                  seats={seats}
                  isBuilding={isConstructing}
                  onBuild={handleConstruct}
                />
              }
            >
              <BuildView
                seg={selectedSeg}
                costs={props.costs}
                isImperial={props.isImperial}
                distUnit={props.distUnit}
                sectorLabel={getSectorLabel(selectedSeg.sector)}
                liftType={liftType}
                onLiftType={setLiftType}
                seats={seats}
                onSeats={setSeats}
                slopeType={slopeType}
                onSlopeType={setSlopeType}
                onZoom={() => engineRef.current?.zoomToSegment(selectedSeg.id)}
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

        {/* Admin Inspector Drawer */}
        {props.isAdmin && isInspectorDrawerOpen && (
          <aside className="absolute top-3 right-3 bottom-3 z-[950] flex w-96 max-w-[calc(100vw-24px)] flex-col overflow-hidden rounded-xl border border-border bg-surface text-foreground shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between border-b border-border p-3.5">
              <div className="flex items-center gap-2.5">
                <div className="flex size-8 items-center justify-center rounded-lg bg-link/15 text-link">
                  <Icon name="fa-compass-drafting" />
                </div>
                <div>
                  <h2 className="text-base font-bold text-foreground">Map inspector</h2>
                  <p className="text-xs text-muted">Segments and sectors</p>
                </div>
              </div>
              <Button
                size="sm"
                variant="ghost"
                onPress={() => setIsInspectorDrawerOpen(false)}
                className="size-8 p-0 text-muted hover:text-foreground"
                aria-label="Close"
              >
                <Icon name="fa-xmark" />
              </Button>
            </div>

            {/* Body */}
            <div className="flex-1 space-y-5 overflow-y-auto p-3.5">
              {/* Mountain Sectors */}
              <div>
                <div className="mb-2 flex items-center justify-between">
                  <span className="text-xs font-bold uppercase tracking-wider text-muted">Mountain Sectors</span>
                  <div className="flex items-center gap-1">
                    <Button
                      size="sm"
                      variant="outline"
                      onPress={() => engineRef.current?.autoAssignSectors()}
                      className="h-7 text-xs font-semibold text-foreground"
                      aria-label="Auto-assign segments"
                    >
                      <Icon name="fa-object-group" className="text-link" />
                      Auto-assign
                    </Button>
                    <Button
                      size="sm"
                      variant="primary"
                      onPress={() => setShowNewSectorForm((prev) => !prev)}
                      className="h-7 text-xs font-semibold"
                    >
                      <Icon name="fa-plus" />
                      Add sector
                    </Button>
                  </div>
                </div>

                {/* Inline New Sector Form */}
                {showNewSectorForm && (
                  <div className="mb-3 rounded-lg border border-border bg-surface-secondary p-2.5 space-y-2">
                    <div className="flex gap-2">
                      <input
                        type="text"
                        value={newSecName}
                        onChange={(e) => setNewSecName(e.target.value)}
                        placeholder="Sector name (e.g. Sector 3)"
                        className="h-8 flex-1 rounded-md border border-border bg-surface px-2.5 text-xs text-foreground outline-none focus:border-accent"
                      />
                      <input
                        type="color"
                        value={newSecColor}
                        onChange={(e) => setNewSecColor(e.target.value)}
                        className="h-8 w-10 cursor-pointer rounded border border-border bg-transparent p-0.5"
                      />
                    </div>
                    <div className="flex justify-end gap-1.5">
                      <Button
                        size="sm"
                        variant="ghost"
                        onPress={() => setShowNewSectorForm(false)}
                        className="h-7 text-xs"
                      >
                        Cancel
                      </Button>
                      <Button
                        size="sm"
                        variant="secondary"
                        onPress={() => {
                          if (!newSecName.trim()) {
                            triggerToast('Please enter a sector name', 'warning');
                            return;
                          }
                          engineRef.current?.createSector(newSecName.trim(), newSecColor, () => {
                            setNewSecName('');
                            setShowNewSectorForm(false);
                          });
                        }}
                        className="h-7 text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-500"
                      >
                        Create
                      </Button>
                    </div>
                  </div>
                )}

                {/* Sector List */}
                <div className="flex flex-col gap-1.5">
                  {sectors.map((sec) => {
                    const bp = typeof sec.boundary_points === 'string' ? JSON.parse(sec.boundary_points || '[]') : sec.boundary_points || [];
                    const hasBoundary = bp.length >= 3;
                    const countInSec = segments.filter((s) => Number(s.sector) === Number(sec.id)).length;

                    return (
                      <div
                        key={sec.id}
                        className="flex items-center gap-2 rounded-lg border border-border bg-surface-secondary p-2 text-xs"
                      >
                        <div
                          className="flex size-7 shrink-0 items-center justify-center rounded-md border text-xs font-bold"
                          style={{
                            borderColor: sec.color || '#3b82f6',
                            color: sec.color || '#3b82f6',
                            background: `${sec.color || '#3b82f6'}22`,
                          }}
                        >
                          <Icon name="fa-layer-group" />
                        </div>

                        <div className="min-w-0 flex-1">
                          <div className="flex items-baseline gap-1.5">
                            <span className="font-semibold text-foreground">{sec.name}</span>
                            <span className="font-mono text-[10px] text-muted">#{sec.id}</span>
                          </div>
                          <div className="text-[11px] text-muted">
                            {countInSec} runs • {hasBoundary ? '✓ Boundary set' : 'No boundary'}
                          </div>
                        </div>

                        <div className="flex items-center gap-1 shrink-0">
                          <Button
                            size="sm"
                            variant="ghost"
                            onPress={() => {
                              setIsInspectorDrawerOpen(false);
                              engineRef.current?.startEditSectorBoundary(sec);
                            }}
                            className="size-7 p-0 text-xs text-muted hover:text-link"
                            aria-label="Draw Sector Boundary"
                          >
                            <Icon name="fa-draw-polygon" className={hasBoundary ? 'text-link' : 'opacity-40'} />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onPress={() => engineRef.current?.toggleSectorVisibility(sec.id)}
                            className="size-7 p-0 text-xs text-muted"
                            aria-label="Toggle Visibility"
                          >
                            <Icon
                              name={Number(sec.visible) ? 'fa-eye' : 'fa-eye-slash'}
                              className={Number(sec.visible) ? 'text-success' : 'opacity-40'}
                            />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onPress={() => engineRef.current?.deleteSector(sec.id)}
                            className="size-7 p-0 text-xs text-danger hover:bg-danger/10"
                            aria-label="Delete Sector"
                          >
                            <Icon name="fa-trash" />
                          </Button>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>

              {/* Segments Directory */}
              <div className="border-t border-border pt-4">
                <div className="mb-2.5 flex items-center justify-between">
                  <span className="text-xs font-bold uppercase tracking-wider text-muted">
                    All segments ({segments.length})
                  </span>
                  <div className="flex items-center gap-1">
                    <Button
                      size="sm"
                      variant="secondary"
                      onPress={() => {
                        setIsInspectorDrawerOpen(false);
                        engineRef.current?.startDraw('lift');
                      }}
                      className="h-7 text-xs font-semibold bg-amber-500/20 text-amber-300 hover:bg-amber-500/30"
                    >
                      <Icon name="fa-plus" /> Lift
                    </Button>
                    <Button
                      size="sm"
                      variant="secondary"
                      onPress={() => {
                        setIsInspectorDrawerOpen(false);
                        engineRef.current?.startDraw('slope');
                      }}
                      className="h-7 text-xs font-semibold bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30"
                    >
                      <Icon name="fa-plus" /> Slope
                    </Button>
                  </div>
                </div>

                <div className="mb-2.5 flex gap-2">
                  <div className="relative flex-1">
                    <Icon name="fa-magnifying-glass" className="absolute top-1/2 left-2.5 -translate-y-1/2 text-xs text-muted" />
                    <input
                      type="text"
                      value={inspectorSearch}
                      onChange={(e) => setInspectorSearch(e.target.value)}
                      placeholder="Filter by name..."
                      className="h-8 w-full rounded-lg border border-border bg-surface-secondary pl-8 pr-2.5 text-xs text-foreground outline-none focus:border-accent"
                    />
                  </div>
                  <select
                    value={inspectorFilter}
                    onChange={(e) => setInspectorFilter(e.target.value)}
                    className="h-8 rounded-lg border border-border bg-surface-secondary px-2 text-xs text-foreground outline-none"
                  >
                    <option value="all">All types</option>
                    <option value="lift">Lifts only</option>
                    <option value="slope">Slopes only</option>
                    <option value="unassigned">Unassigned</option>
                  </select>
                </div>

                <div className="flex flex-col gap-1.5">
                  {inspectorSegments.map((seg) => (
                    <div
                      key={seg.id}
                      className="flex items-center gap-2 rounded-lg border border-border bg-surface-secondary p-2 text-xs"
                    >
                      <div
                        onClick={() => engineRef.current?.zoomToSegment(seg.id)}
                        className="flex flex-1 cursor-pointer items-center gap-2.5 min-w-0"
                      >
                        <div className="flex size-6 shrink-0 items-center justify-center">
                          {renderTrailSign(seg)}
                        </div>
                        <div className="min-w-0 flex-1">
                          <div className="truncate font-semibold text-foreground">{seg.name || 'Unnamed'}</div>
                          <div className="text-[11px] text-muted">
                            <span className="font-mono">
                              {Math.round(seg.length_meters || 0)} {props.distUnit}
                            </span>{' '}
                            • {getSectorLabel(seg.sector)}
                          </div>
                        </div>
                      </div>

                      <div className="flex items-center gap-1 shrink-0">
                        <Button
                          size="sm"
                          variant="ghost"
                          onPress={() => {
                            setIsInspectorDrawerOpen(false);
                            engineRef.current?.zoomToSegment(seg.id);
                            engineRef.current?.startEditSegment(seg);
                          }}
                          className="size-7 p-0 text-xs text-link"
                          aria-label="Edit Path & Details"
                        >
                          <Icon name="fa-pen-to-square" />
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          onPress={() => {
                            if (!confirm(`Delete "${seg.name || 'Segment'}"? This cannot be undone.`)) return;
                            engineRef.current?.postJSON(`/map/segment/delete/${seg.id}`, {}, (res) => {
                              if (res && res.success) {
                                const updated = segments.filter((s) => String(s.id) !== String(seg.id));
                                setSegments(updated);
                                engineRef.current?.setFilter(mapFilter);
                                triggerToast(`Deleted "${seg.name || 'Segment'}"`, 'success');
                              }
                            });
                          }}
                          className="size-7 p-0 text-xs text-danger"
                          aria-label="Delete Segment"
                        >
                          <Icon name="fa-trash" />
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </aside>
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
