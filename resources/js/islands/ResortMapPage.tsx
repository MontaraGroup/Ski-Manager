import { useEffect, useMemo, useRef, useState } from 'react';
import { Button, Chip, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { createMapEngine } from './resort-map/mapEngine';
import type { BuiltItem, MapSegment, ResortMapProps, Sector, EditorState } from './resort-map/types';

const COST_PER_METER: Record<string, number> = {
  button: 80,
  chair_fixed: 150,
  chair_detach: 250,
  gondola: 400,
  cable_car: 600,
};

const SEAT_MULT: Record<number, number> = {
  1: 0.7,
  2: 1.0,
  3: 1.15,
  4: 1.3,
  6: 1.6,
  8: 2.0,
  10: 2.5,
  20: 4.0,
  30: 5.0,
};

const SEAT_OPTIONS: Record<string, number[]> = {
  button: [1, 2],
  chair_fixed: [2, 3, 4],
  chair_detach: [4, 6, 8],
  gondola: [6, 8, 10],
  cable_car: [20, 30],
};

function formatCurrency(n: number, isImperial: boolean): string {
  const prefix = isImperial ? '$' : '';
  const suffix = isImperial ? '' : ' €';
  return (n < 0 ? '-' : '') + prefix + Math.abs(Math.round(n)).toLocaleString('en-US') + suffix;
}

export default function ResortMapPage(props: ResortMapProps) {
  const mapContainerRef = useRef<HTMLDivElement>(null);
  const engineRef = useRef<ReturnType<typeof createMapEngine> | null>(null);

  // Data states
  const [segments, setSegments] = useState<MapSegment[]>(props.segments);
  const [sectors, setSectors] = useState<Sector[]>(props.sectors);
  const [builtIds, setBuiltIds] = useState<(string | number)[]>(props.builtSegmentIds);
  const [builtItems, setBuiltItems] = useState<Record<string, BuiltItem>>(props.builtItems);
  const [userCash, setUserCash] = useState<number>(props.userCash);

  // UI state
  const [mapFilter, setMapFilter] = useState<string>('all');
  const [isBuildDrawerOpen, setIsBuildDrawerOpen] = useState<boolean>(false);
  const [isInspectorDrawerOpen, setIsInspectorDrawerOpen] = useState<boolean>(false);
  const [activeTab, setActiveTab] = useState<'lift' | 'slope'>('lift');
  const [selectedSeg, setSelectedSeg] = useState<MapSegment | null>(null);

  // Build blueprint options
  const [liftType, setLiftType] = useState<string>('chair_fixed');
  const [seats, setSeats] = useState<number>(4);
  const [slopeType, setSlopeType] = useState<string>('downhill');
  const [isConstructing, setIsConstructing] = useState<boolean>(false);

  // Build list filters
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [diffFilter, setDiffFilter] = useState<string>('');
  const [sortOrder, setSortOrder] = useState<'cost_asc' | 'cost_desc' | 'len_desc' | 'name_asc'>('cost_asc');

  // Admin inspector filters & form
  const [inspectorSearch, setInspectorSearch] = useState<string>('');
  const [inspectorFilter, setInspectorFilter] = useState<string>('all');
  const [showNewSectorForm, setShowNewSectorForm] = useState<boolean>(false);
  const [newSecName, setNewSecName] = useState<string>('');
  const [newSecColor, setNewSecColor] = useState<string>('#3b82f6');

  // Admin Editor state
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

  // Local Toast
  const [toast, setToast] = useState<{ msg: string; type: 'success' | 'error' | 'warning' | 'info' } | null>(null);
  const toastTimeoutRef = useRef<any>(null);

  function triggerToast(msg: string, type: 'success' | 'error' | 'warning' | 'info' = 'success') {
    if (toastTimeoutRef.current) clearTimeout(toastTimeoutRef.current);
    setToast({ msg, type });
    toastTimeoutRef.current = setTimeout(() => setToast(null), 3200);
  }

  // Initialize Map Engine
  useEffect(() => {
    if (!mapContainerRef.current) return;

    const engine = createMapEngine(props, {
      onSelectSegment: (seg) => {
        setSelectedSeg(seg);
        if (seg) {
          if (seg.type !== 'lift') {
            setSlopeType(seg.type || 'downhill');
          }
        }
      },
      onOpenBuildDrawer: (tab) => {
        setIsInspectorDrawerOpen(false);
        setIsBuildDrawerOpen(true);
        setActiveTab(tab);
        document.body.classList.add('map-drawer-open');
      },
      onCloseBuildDrawer: () => {
        setIsBuildDrawerOpen(false);
        setSelectedSeg(null);
        document.body.classList.remove('map-drawer-open');
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

    return () => {
      engine.destroy();
      document.body.classList.remove('map-drawer-open');
    };
  }, []);

  // Update seats default when liftType changes
  useEffect(() => {
    const opts = SEAT_OPTIONS[liftType] || [2, 4];
    if (!opts.includes(seats)) {
      setSeats(opts[0]);
    }
  }, [liftType]);

  // Sync drawer body class
  useEffect(() => {
    const isOpen = isBuildDrawerOpen || isInspectorDrawerOpen;
    document.body.classList.toggle('map-drawer-open', isOpen);
  }, [isBuildDrawerOpen, isInspectorDrawerOpen]);

  // Header stats
  const { liftCount, slopeCount } = useMemo(() => {
    let lifts = 0;
    let slopes = 0;
    segments.forEach((s) => {
      if (s.type === 'lift') lifts++;
      else slopes++;
    });
    return { liftCount: lifts, slopeCount: slopes };
  }, [segments]);

  // Available segments counts
  const { availLiftsCount, availSlopesCount } = useMemo(() => {
    let lifts = 0;
    let slopes = 0;
    segments.forEach((s) => {
      const isBuilt = builtIds.some((b) => String(b) === String(s.id) || Number(b) === Number(s.id));
      if (isBuilt) return;
      if (!props.isAdmin && props.releasedSectorIds.length > 0) {
        const secVal = Number(s.sector || 0);
        if (secVal > 0 && !props.releasedSectorIds.includes(secVal)) return;
      }
      if (s.type === 'lift') lifts++;
      else slopes++;
    });
    return { availLiftsCount: lifts, availSlopesCount: slopes };
  }, [segments, builtIds, props.isAdmin, props.releasedSectorIds]);

  // Filtered available runs for build list
  const availableItems = useMemo(() => {
    const q = searchQuery.trim().toLowerCase();
    const list = segments.filter((s) => {
      if (activeTab === 'lift' && s.type !== 'lift') return false;
      if (activeTab === 'slope' && s.type === 'lift') return false;

      const isBuilt = builtIds.some((b) => String(b) === String(s.id) || Number(b) === Number(s.id));
      if (isBuilt) return false;

      if (!props.isAdmin && props.releasedSectorIds.length > 0) {
        const secVal = Number(s.sector || 0);
        if (secVal > 0 && !props.releasedSectorIds.includes(secVal)) return false;
      }

      if (q && (s.name || '').toLowerCase().indexOf(q) === -1) return false;
      if (activeTab === 'slope' && diffFilter && s.difficulty !== diffFilter) return false;

      return true;
    });

    list.sort((a, b) => {
      const lenA = Number(a.length_meters) || 0;
      const lenB = Number(b.length_meters) || 0;
      if (sortOrder === 'cost_asc') return lenA - lenB;
      if (sortOrder === 'cost_desc') return lenB - lenA;
      if (sortOrder === 'len_desc') return lenB - lenA;
      return String(a.name || '').localeCompare(String(b.name || ''), undefined, {
        numeric: true,
        sensitivity: 'base',
      });
    });

    return list;
  }, [segments, builtIds, activeTab, searchQuery, diffFilter, sortOrder, props.isAdmin, props.releasedSectorIds]);

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

  // Selected segment construction cost
  const calculatedCost = useMemo(() => {
    if (!selectedSeg) return 0;
    const len = Number(selectedSeg.length_meters) || 0;
    if (selectedSeg.type === 'lift') {
      const rate = COST_PER_METER[liftType] || 150;
      const mult = SEAT_MULT[seats] || 1.3;
      return Math.round(len * rate * mult);
    }
    return Math.round(len * 50);
  }, [selectedSeg, liftType, seats]);

  const canAfford = userCash >= calculatedCost;

  // Economy info
  const economyInfo = useMemo(() => {
    if (!selectedSeg || !props.economyV2) return null;
    const m = Number(selectedSeg.length_meters) || 0;
    if (selectedSeg.type === 'lift') {
      const cap = props.economyV2.capacity[liftType] || 2400;
      const maxS = props.economyV2.maxSeats[liftType] || 4;
      const perHour = Math.round(cap * Math.min(1, seats / maxS));
      const daily = Math.round(perHour * props.economyV2.perDay);
      const runCost = m * (props.economyV2.liftRun[liftType] || 0.8);
      const kwh = Math.round(m * (props.economyV2.liftKwh[liftType] || 0.8));
      return `Carries ~${daily.toLocaleString()} skiers/day · runs ${formatCurrency(runCost, props.isImperial)}/day + ${kwh.toLocaleString()} kWh`;
    }
    const skiers = Math.round(30 + m / 40);
    const upkeep = m * props.economyV2.slopeRun;
    return `Draws ~${skiers} skiers/day · upkeep ${formatCurrency(upkeep, props.isImperial)}/day`;
  }, [selectedSeg, liftType, seats, props.economyV2, props.isImperial]);

  function handleFilterClick(filter: string) {
    setMapFilter(filter);
    engineRef.current?.setFilter(filter);
  }

  function handleOpenBuildDrawer(tab: 'lift' | 'slope') {
    setIsInspectorDrawerOpen(false);
    setIsBuildDrawerOpen(true);
    setActiveTab(tab);
    engineRef.current?.setBuildMode(tab);
  }

  function handleCloseBuildDrawer() {
    setIsBuildDrawerOpen(false);
    setSelectedSeg(null);
    engineRef.current?.deselectSegment();
    engineRef.current?.setBuildMode(null);
  }

  function handleSelectSeg(seg: MapSegment) {
    engineRef.current?.selectSegment(seg);
  }

  function handleConstruct() {
    if (!selectedSeg || isConstructing) return;
    setIsConstructing(true);
    engineRef.current?.buildSegment(
      selectedSeg,
      {
        liftType,
        seats,
        slopeType,
      },
      () => {
        setIsConstructing(false);
      }
    );
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

  function renderTrailSign(seg: MapSegment) {
    if (seg.type === 'lift') {
      return (
        <span className="inline-flex size-5 shrink-0 items-center justify-center text-warning" title="Lift">
          <Icon name="fa-elevator" />
        </span>
      );
    }
    const d = seg.difficulty || 'blue';
    if (d === 'green') return <span className="tsign green" title="Easiest" />;
    if (d === 'black') return <span className="tsign black" title="Most difficult" />;
    if (d === 'double_black') {
      return (
        <span className="tsign-pair" title="Experts only">
          <span className="tsign black" />
          <span className="tsign black" />
        </span>
      );
    }
    return <span className="tsign blue" title="More difficult" />;
  }

  function getSectorLabel(secId: any) {
    if (!secId || secId === '0' || secId === 0) return 'Default (0)';
    const sec = sectors.find((s) => String(s.id) === String(secId) || s.name === String(secId));
    return sec ? sec.name : `Sector #${secId}`;
  }

  return (
    <div className="relative flex h-[calc(100vh-65px)] w-full flex-col overflow-hidden bg-background text-foreground">
      {/* Top Header Bar */}
      <header className="flex flex-wrap items-center justify-between gap-2 border-b border-border bg-surface px-4 py-2">
        <div className="flex items-center gap-3">
          <a
            href="/resort"
            className="flex size-9 items-center justify-center rounded-lg border border-border text-foreground hover:bg-surface-secondary"
            aria-label="Back to resort"
          >
            <Icon name="fa-arrow-left" />
          </a>
          <div>
            <h1 className="text-lg font-semibold tracking-tight text-foreground">
              {props.mapConfig.name} <span className="text-xs font-normal text-muted">Trail map</span>
            </h1>
            <div className="flex items-center gap-3 text-xs text-muted">
              <span>
                <b className="tabular-nums text-foreground">{slopeCount}</b> slopes
              </span>
              <span>
                <b className="tabular-nums text-foreground">{liftCount}</b> lifts
              </span>
              <span>
                <b className="tabular-nums text-foreground">{segments.length}</b> segments
              </span>
            </div>
          </div>
        </div>

        {/* Filter Pills & Actions */}
        <div className="flex flex-wrap items-center gap-2">
          <div className="hidden rounded-lg border border-border bg-surface-secondary p-0.5 md:flex">
            <button
              type="button"
              onClick={() => handleFilterClick('all')}
              className={`rounded-md px-2.5 py-1 text-xs font-semibold transition-colors ${
                mapFilter === 'all' ? 'bg-accent text-accent-foreground' : 'text-muted hover:text-foreground'
              }`}
            >
              All
            </button>
            <button
              type="button"
              onClick={() => handleFilterClick('lift')}
              className={`rounded-md px-2.5 py-1 text-xs font-semibold transition-colors ${
                mapFilter === 'lift' ? 'bg-accent text-accent-foreground' : 'text-muted hover:text-foreground'
              }`}
            >
              Lifts
            </button>
            <button
              type="button"
              onClick={() => handleFilterClick('slope')}
              className={`rounded-md px-2.5 py-1 text-xs font-semibold transition-colors ${
                mapFilter === 'slope' ? 'bg-accent text-accent-foreground' : 'text-muted hover:text-foreground'
              }`}
            >
              Slopes
            </button>
            <button
              type="button"
              onClick={() => handleFilterClick('built')}
              className={`rounded-md px-2.5 py-1 text-xs font-semibold transition-colors ${
                mapFilter === 'built' ? 'bg-accent text-accent-foreground' : 'text-muted hover:text-foreground'
              }`}
            >
              Built
            </button>
            {props.isAdmin && (
              <button
                type="button"
                onClick={() => handleFilterClick('unassigned')}
                className={`rounded-md px-2.5 py-1 text-xs font-semibold transition-colors ${
                  mapFilter === 'unassigned' ? 'bg-accent text-accent-foreground' : 'text-muted hover:text-foreground'
                }`}
                title="Segments with Sector 0"
              >
                Unassigned
              </button>
            )}
          </div>

          <Button
            size="sm"
            variant="outline"
            onPress={() => engineRef.current?.fitBounds()}
            className="min-h-8 px-2.5 text-foreground"
            aria-label="Reset Map Zoom (F)"
          >
            <Icon name="fa-expand" />
          </Button>

          {props.isAdmin && (
            <Button
              size="sm"
              variant="outline"
              onPress={() => {
                handleCloseBuildDrawer();
                setIsInspectorDrawerOpen((prev) => !prev);
              }}
              className="min-h-8 gap-1.5 font-semibold text-foreground"
              aria-label="Open Segment & Sector Manager"
            >
              <Icon name="fa-list-check" className="text-link" />
              <span className="hidden sm:inline">Segments & Sectors</span>
            </Button>
          )}

          <Button
            size="sm"
            variant="primary"
            onPress={() => handleOpenBuildDrawer('lift')}
            className="min-h-8 gap-1.5 font-semibold"
          >
            <Icon name="fa-hammer" />
            Build
          </Button>

          {props.isAdmin && (
            <select
              value={props.resortMap}
              onChange={handleResortChange}
              className="h-8 rounded-lg border border-border bg-surface px-2.5 text-xs font-medium text-foreground outline-none"
            >
              {Object.entries(props.resortMaps).map(([k, rm]) => (
                <option key={k} value={k}>
                  {rm.name}
                </option>
              ))}
            </select>
          )}
        </div>
      </header>

      {/* Main Map Viewport */}
      <div className="relative flex-1 overflow-hidden">
        <div ref={mapContainerRef} id="map" />

        {/* Subtle HD Resolution Indicator */}
        <div
          id="hdBadge"
          className="absolute bottom-4 right-20 z-[800] hidden items-center gap-1.5 rounded-lg border border-white/10 bg-slate-900/90 px-3 py-1.5 text-xs text-foreground shadow-lg"
        >
          <Spinner size="sm" />
          <span>Loading HD terrain...</span>
        </div>

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

        {/* Map Legend */}
        <div className="absolute bottom-3 left-3 z-[800] flex flex-col gap-1 rounded-lg border border-border bg-surface/95 p-2.5 text-xs text-foreground shadow-xl backdrop-blur-md">
          <div className="text-[11px] font-bold uppercase tracking-wider text-muted">Trail key</div>
          <div className="flex items-center gap-2">
            <span className="inline-flex w-5 justify-center">
              <span className="tsign green" />
            </span>
            <span className="inline-block h-1 w-4 rounded-sm bg-emerald-500" />
            <span>Easiest</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="inline-flex w-5 justify-center">
              <span className="tsign blue" />
            </span>
            <span className="inline-block h-1 w-4 rounded-sm bg-blue-500" />
            <span>More difficult</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="inline-flex w-5 justify-center">
              <span className="tsign black" />
            </span>
            <span className="inline-block h-1 w-4 rounded-sm bg-slate-900" />
            <span>Most difficult</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="inline-flex w-5 justify-center">
              <span className="tsign-pair">
                <span className="tsign black" />
                <span className="tsign black" />
              </span>
            </span>
            <span className="inline-block h-1 w-4 rounded-sm bg-red-600" />
            <span>Experts only</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="inline-flex w-5 justify-center text-warning">
              <Icon name="fa-elevator" />
            </span>
            <span className="inline-block h-1 w-4 rounded-sm bg-amber-500" />
            <span>Lift</span>
          </div>
          <div className="flex items-center gap-2">
            <span className="inline-flex w-5 justify-center" />
            <span className="inline-block h-1 w-4 rounded-sm bg-purple-500" />
            <span>Available to build</span>
          </div>
        </div>

        {/* Build FAB */}
        {!isBuildDrawerOpen && (
          <Button
            size="lg"
            variant="primary"
            onPress={() => handleOpenBuildDrawer('lift')}
            className="absolute bottom-6 right-6 z-[900] size-14 rounded-full shadow-2xl p-0 min-w-0"
            aria-label="Open Build Menu"
          >
            <Icon name="fa-hammer" className="text-xl" />
          </Button>
        )}

        {/* Player Build Drawer */}
        {isBuildDrawerOpen && (
          <aside className="absolute top-3 right-3 bottom-3 z-[950] flex w-96 max-w-[calc(100vw-24px)] flex-col overflow-hidden rounded-xl border border-border bg-surface text-foreground shadow-2xl">
            {/* Header */}
            <div className="flex items-center justify-between border-b border-border p-3.5">
              <div className="flex items-center gap-2.5">
                <div className="flex size-8 items-center justify-center rounded-lg bg-accent/15 text-accent">
                  <Icon name="fa-hammer" />
                </div>
                <div>
                  <h2 className="text-base font-bold text-foreground">Build</h2>
                  <div className="flex items-center gap-1.5 text-xs text-muted">
                    <span>Cash</span>
                    <span className="tabular-nums font-semibold text-foreground">
                      {formatCurrency(userCash, props.isImperial)}
                    </span>
                  </div>
                </div>
              </div>
              <Button
                size="sm"
                variant="ghost"
                onPress={handleCloseBuildDrawer}
                className="size-8 p-0 text-muted hover:text-foreground"
                aria-label="Close"
              >
                <Icon name="fa-xmark" />
              </Button>
            </div>

            {/* Content Body */}
            <div className="flex-1 overflow-y-auto p-3.5">
              {/* Segmented Tabs */}
              <div className="mb-3 grid grid-cols-2 gap-1 rounded-lg border border-border bg-surface-secondary p-1">
                <button
                  type="button"
                  onClick={() => {
                    setActiveTab('lift');
                    engineRef.current?.setBuildMode('lift');
                  }}
                  className={`flex items-center justify-center gap-2 rounded-md py-1.5 text-xs font-semibold transition-colors ${
                    activeTab === 'lift'
                      ? 'bg-surface text-foreground shadow-sm'
                      : 'text-muted hover:text-foreground'
                  }`}
                >
                  <Icon name="fa-elevator" className="text-warning" />
                  <span>Lifts</span>
                  <Chip size="sm" variant="soft" color="default" className="h-5 px-1.5 text-[11px] tabular-nums">
                    {availLiftsCount}
                  </Chip>
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setActiveTab('slope');
                    engineRef.current?.setBuildMode('slope');
                  }}
                  className={`flex items-center justify-center gap-2 rounded-md py-1.5 text-xs font-semibold transition-colors ${
                    activeTab === 'slope'
                      ? 'bg-surface text-foreground shadow-sm'
                      : 'text-muted hover:text-foreground'
                  }`}
                >
                  <Icon name="fa-person-skiing" className="text-success" />
                  <span>Slopes</span>
                  <Chip size="sm" variant="soft" color="default" className="h-5 px-1.5 text-[11px] tabular-nums">
                    {availSlopesCount}
                  </Chip>
                </button>
              </div>

              {/* Selected Segment Blueprint Card */}
              {selectedSeg && (
                <div className="mb-4 rounded-xl border border-border bg-surface-secondary p-3.5">
                  <div className="mb-3 flex items-start justify-between gap-2">
                    <div className="min-w-0">
                      <div className="flex items-center gap-1.5 text-xs font-semibold text-link">
                        <span>Selected</span>
                        <span className="flex items-center gap-1">
                          {renderTrailSign(selectedSeg)}
                          <span className="capitalize">{selectedSeg.difficulty?.replace('_', ' ') || 'run'}</span>
                        </span>
                      </div>
                      <h3 className="truncate text-base font-bold text-foreground">{selectedSeg.name || 'Unnamed'}</h3>
                    </div>
                    <div className="flex items-center gap-1 shrink-0">
                      <Button
                        size="sm"
                        variant="ghost"
                        onPress={() => engineRef.current?.zoomToSegment(selectedSeg.id)}
                        className="size-7 p-0 text-xs text-muted hover:text-foreground"
                        aria-label="Center map on this run"
                      >
                        <Icon name="fa-location-crosshairs" />
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        onPress={() => {
                          setSelectedSeg(null);
                          engineRef.current?.deselectSegment();
                        }}
                        className="size-7 p-0 text-xs text-muted hover:text-foreground"
                        aria-label="Deselect"
                      >
                        <Icon name="fa-xmark" />
                      </Button>
                    </div>
                  </div>

                  {/* Stat Grid */}
                  <div className="mb-3 grid grid-cols-3 gap-2 border-y border-border py-2 text-xs">
                    <div>
                      <div className="text-muted">Length</div>
                      <div className="tabular-nums font-semibold text-foreground">
                        {Math.round(selectedSeg.length_meters || 0).toLocaleString()} {props.distUnit}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Build time</div>
                      <div className="font-semibold text-foreground">
                        {(selectedSeg.length_meters || 0) < 200 ? '1 day' : (selectedSeg.length_meters || 0) < 500 ? '2 days' : '3 days'}
                      </div>
                    </div>
                    <div>
                      <div className="text-muted">Sector</div>
                      <div className="truncate font-semibold text-foreground">{getSectorLabel(selectedSeg.sector)}</div>
                    </div>
                  </div>

                  {/* Lift Options */}
                  {selectedSeg.type === 'lift' ? (
                    <div className="space-y-3">
                      <div>
                        <div className="mb-1.5 flex items-center justify-between text-xs font-semibold text-foreground">
                          <span>Lift system</span>
                          <span className="text-muted font-normal">Hourly capacity</span>
                        </div>
                        <div className="flex flex-col gap-1.5">
                          {[
                            { id: 'button', name: 'Surface / Button Lift', rate: 80, cap: '1,000/hr' },
                            { id: 'chair_fixed', name: 'Fixed-Grip Chairlift', rate: 150, cap: '2,400/hr' },
                            { id: 'chair_detach', name: 'Detachable High-Speed', rate: 250, cap: '3,400/hr' },
                            { id: 'gondola', name: 'Cabin Gondola', rate: 400, cap: '3,500/hr' },
                            { id: 'cable_car', name: 'Aerial Tram / Cable Car', rate: 600, cap: '4,000/hr' },
                          ].map((l) => (
                            <button
                              key={l.id}
                              type="button"
                              onClick={() => setLiftType(l.id)}
                              className={`flex items-center justify-between rounded-lg border p-2 text-xs transition-colors ${
                                liftType === l.id
                                  ? 'border-accent bg-accent/10 text-foreground'
                                  : 'border-border bg-surface text-muted hover:border-border/80 hover:text-foreground'
                              }`}
                            >
                              <div className="text-left">
                                <div className="font-semibold text-foreground">{l.name}</div>
                                <div className="text-[11px] text-muted">
                                  {formatCurrency(l.rate, props.isImperial)} / m base
                                </div>
                              </div>
                              <span className="tabular-nums font-semibold text-warning">{l.cap}</span>
                            </button>
                          ))}
                        </div>
                      </div>

                      <div>
                        <div className="mb-1.5 text-xs font-semibold text-foreground">Carrier size</div>
                        <div className="flex gap-1.5">
                          {(SEAT_OPTIONS[liftType] || [2, 4]).map((n) => (
                            <button
                              key={n}
                              type="button"
                              onClick={() => setSeats(n)}
                              className={`flex-1 rounded-lg border py-1.5 text-center text-xs font-semibold tabular-nums transition-colors ${
                                seats === n
                                  ? 'border-accent bg-accent/15 text-accent-foreground'
                                  : 'border-border bg-surface text-muted hover:text-foreground'
                              }`}
                            >
                              {n} {liftType === 'gondola' || liftType === 'cable_car' ? 'p' : 'seats'}
                            </button>
                          ))}
                        </div>
                      </div>
                    </div>
                  ) : (
                    /* Slope Options */
                    <div>
                      <div className="mb-1.5 text-xs font-semibold text-foreground">Trail discipline</div>
                      <div className="grid grid-cols-2 gap-1.5">
                        {[
                          { id: 'downhill', name: 'Downhill', icon: 'fa-person-skiing', tone: 'text-success' },
                          { id: 'crosscountry', name: 'Cross-Country', icon: 'fa-person-skiing-nordic', tone: 'text-link' },
                          { id: 'snowpark', name: 'Snow Park', icon: 'fa-person-snowboarding', tone: 'text-purple-400' },
                          { id: 'luge', name: 'Alpine Luge', icon: 'fa-sleigh', tone: 'text-pink-400' },
                        ].map((d) => (
                          <button
                            key={d.id}
                            type="button"
                            onClick={() => setSlopeType(d.id)}
                            className={`flex items-center gap-2 rounded-lg border p-2 text-xs font-semibold transition-colors ${
                              slopeType === d.id
                                ? 'border-accent bg-accent/10 text-foreground'
                                : 'border-border bg-surface text-muted hover:text-foreground'
                            }`}
                          >
                            <Icon name={d.icon} className={d.tone} />
                            <span>{d.name}</span>
                          </button>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Construction Cost & Action */}
                  <div className="mt-3 border-t border-border pt-3">
                    <div className="mb-3 flex items-center justify-between">
                      <div>
                        <div className="text-[11px] font-semibold text-muted">Construction cost</div>
                        <div
                          className={`text-xl font-bold tracking-tight tabular-nums ${
                            canAfford ? 'text-success' : 'text-danger'
                          }`}
                        >
                          {formatCurrency(calculatedCost, props.isImperial)}
                        </div>
                        <div className="text-[11px] tabular-nums text-muted">
                          {canAfford
                            ? `Balance after: ${formatCurrency(userCash - calculatedCost, props.isImperial)}`
                            : `Need ${formatCurrency(calculatedCost - userCash, props.isImperial)} more`}
                        </div>
                      </div>

                      <Button
                        size="md"
                        variant={canAfford ? 'secondary' : 'outline'}
                        isDisabled={!canAfford || isConstructing}
                        onPress={handleConstruct}
                        className={`font-bold ${canAfford ? 'bg-emerald-600 text-white hover:bg-emerald-500' : ''}`}
                      >
                        {isConstructing ? (
                          <>
                            <Spinner size="sm" /> Building...
                          </>
                        ) : (
                          <>
                            <Icon name="fa-hammer" /> Construct
                          </>
                        )}
                      </Button>
                    </div>

                    {economyInfo && <p className="text-xs text-muted">{economyInfo}</p>}
                  </div>
                </div>
              )}

              {/* Search, Filter & Sort */}
              <div className="mb-3 flex flex-col gap-2">
                <div className="relative">
                  <Icon name="fa-magnifying-glass" className="absolute top-1/2 left-3 -translate-y-1/2 text-xs text-muted" />
                  <input
                    type="text"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    placeholder="Search by name"
                    className="h-9 w-full rounded-lg border border-border bg-surface-secondary pl-8 pr-3 text-xs text-foreground outline-none focus:border-accent"
                  />
                </div>

                <div className="flex items-center gap-2">
                  {activeTab === 'slope' && (
                    <select
                      value={diffFilter}
                      onChange={(e) => setDiffFilter(e.target.value)}
                      className="h-8 flex-1 rounded-lg border border-border bg-surface-secondary px-2 text-xs text-foreground outline-none"
                    >
                      <option value="">All grades</option>
                      <option value="green">● Green (Easy)</option>
                      <option value="blue">■ Blue (Intermediate)</option>
                      <option value="black">◆ Black (Advanced)</option>
                      <option value="double_black">◆◆ Double Black</option>
                    </select>
                  )}

                  <select
                    value={sortOrder}
                    onChange={(e) => setSortOrder(e.target.value as any)}
                    className="h-8 flex-1 rounded-lg border border-border bg-surface-secondary px-2 text-xs text-foreground outline-none"
                  >
                    <option value="cost_asc">Sort: Cheapest first</option>
                    <option value="cost_desc">Sort: Expensive first</option>
                    <option value="len_desc">Sort: Longest first</option>
                    <option value="name_asc">Sort: Name (A–Z)</option>
                  </select>
                </div>
              </div>

              {/* Segment List */}
              <div className="flex flex-col gap-1.5">
                <div className="flex items-center justify-between px-1 text-xs text-muted">
                  <span>{availableItems.length} available</span>
                  <span>Click to configure</span>
                </div>

                {availableItems.length === 0 ? (
                  <div className="rounded-xl border border-dashed border-border p-6 text-center text-xs text-muted">
                    <Icon
                      name={activeTab === 'lift' ? 'fa-elevator' : 'fa-person-skiing'}
                      className="mb-2 text-2xl text-muted/60"
                    />
                    <p className="font-semibold text-foreground">No available {activeTab}s match</p>
                    <p className="mt-1 text-muted">Try clearing your search or grade filter</p>
                  </div>
                ) : (
                  availableItems.map((seg) => {
                    const isSelected = selectedSeg && String(selectedSeg.id) === String(seg.id);
                    const lenM = Number(seg.length_meters) || 0;
                    const baseCost = Math.round(lenM * (seg.type === 'lift' ? 150 : 50));
                    const afford = userCash >= baseCost;
                    const days = lenM < 200 ? 1 : lenM < 500 ? 2 : 3;

                    return (
                      <button
                        key={seg.id}
                        type="button"
                        onClick={() => handleSelectSeg(seg)}
                        className={`flex items-center gap-3 rounded-lg border p-2.5 text-left transition-colors ${
                          isSelected
                            ? 'border-accent bg-accent/15 text-foreground ring-1 ring-accent'
                            : 'border-border bg-surface-secondary hover:border-border/80 hover:bg-surface-secondary/80'
                        }`}
                      >
                        <div className="flex size-7 shrink-0 items-center justify-center">
                          {renderTrailSign(seg)}
                        </div>

                        <div className="min-w-0 flex-1">
                          <div className="flex items-baseline gap-2">
                            <span className="truncate text-xs font-semibold text-foreground">
                              {seg.name || 'Unnamed'}
                            </span>
                            <span className="text-[11px] text-muted capitalize">
                              {seg.type === 'lift' ? 'Lift' : seg.difficulty?.replace('_', ' ') || 'Blue'}
                            </span>
                          </div>
                          <div className="flex items-center gap-1.5 text-[11px] text-muted">
                            <span className="tabular-nums font-mono">
                              {Math.round(lenM).toLocaleString()} {props.distUnit}
                            </span>
                            <span>•</span>
                            <span>
                              {days} {days === 1 ? 'day' : 'days'}
                            </span>
                            {Number(seg.sector || 0) > 0 && (
                              <>
                                <span>•</span>
                                <span className="truncate">{getSectorLabel(seg.sector)}</span>
                              </>
                            )}
                          </div>
                        </div>

                        <div className="shrink-0 text-right">
                          <div
                            className={`text-xs font-semibold tabular-nums ${
                              afford ? 'text-foreground' : 'text-danger'
                            }`}
                          >
                            {seg.type === 'lift' && <span className="text-[10px] text-muted font-normal mr-1">from</span>}
                            {formatCurrency(baseCost, props.isImperial)}
                          </div>
                          <div className="text-[10px] text-muted">{isSelected ? 'Selected' : 'Configure'}</div>
                        </div>
                      </button>
                    );
                  })
                )}
              </div>
            </div>
          </aside>
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

        {/* Toast Notification */}
        {toast && (
          <div className="absolute top-4 right-4 z-[1100] transition-all duration-300">
            <div
              className={`rounded-lg px-4 py-2 text-xs font-semibold shadow-xl border ${
                toast.type === 'error'
                  ? 'bg-danger text-danger-foreground border-danger'
                  : toast.type === 'warning'
                    ? 'bg-warning text-warning-foreground border-warning'
                    : toast.type === 'info'
                      ? 'bg-link text-white border-link'
                      : 'bg-success text-success-foreground border-success'
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
