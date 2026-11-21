import { useMemo, useState } from 'react';
import { Button, Chip } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Select } from '@/components/Select';
import { TrailSign } from './shared';
import type { EditorState, MapSegment, Sector } from './types';

export function AdminEditorToolbar({
  editorState,
  sectors,
  distUnit,
  panelOpen,
  engine,
}: {
  editorState: EditorState;
  sectors: Sector[];
  distUnit: string;
  panelOpen: boolean;
  engine: any;
}) {
  return (
    <div
      className={`pointer-events-none absolute top-3 left-0 right-0 z-[850] flex justify-center px-3 transition-all ${
        panelOpen ? 'lg:right-[390px]' : ''
      }`}
    >
      <div className="pointer-events-auto max-w-[96vw] rounded-xl border border-white/15 bg-slate-900/95 p-2 shadow-2xl backdrop-blur-md">
        {!editorState.active ? (
          <div className="flex flex-wrap items-center gap-2">
            <Chip size="sm" variant="soft" color="accent" className="font-semibold">
              <Icon name="fa-compass-drafting" className="mr-1" />
              Map Studio
            </Chip>

            <div className="flex items-center gap-1.5 rounded-lg border border-white/10 bg-slate-800/80 px-2 py-0.5 text-xs">
              <span className="font-semibold text-muted">Sector:</span>
              <Select
                value={editorState.sector}
                onChange={(val) => engine?.setEditorSector(Number(val))}
                aria-label="Editor Sector"
                options={[
                  { id: 0, label: 'Default (0)' },
                  ...sectors.map((sec) => ({
                    id: sec.id,
                    label: `${sec.name} (#${sec.id})`,
                  })),
                ]}
                className="text-xs"
              />
            </div>

            <Button
              size="sm"
              variant="secondary"
              onPress={() => engine?.startDraw('lift')}
              className="h-7 bg-amber-500/20 text-xs font-semibold text-amber-300 hover:bg-amber-500/30"
              aria-label="Draw New Lift (Shortcut: L)"
            >
              <Icon name="fa-elevator" />+ New Lift
            </Button>

            <Button
              size="sm"
              variant="secondary"
              onPress={() => engine?.startDraw('slope')}
              className="h-7 bg-emerald-500/20 text-xs font-semibold text-emerald-300 hover:bg-emerald-500/30"
              aria-label="Draw New Slope (Shortcut: S)"
            >
              <Icon name="fa-person-skiing" />+ New Slope
            </Button>

            <Button
              size="sm"
              variant={editorState.snapEnabled ? 'outline' : 'ghost'}
              onPress={() => engine?.toggleSnap()}
              className="h-7 text-xs font-semibold text-foreground"
              aria-label="Magnetic Endpoint Snapping"
            >
              <Icon name="fa-magnet" />
              Snap: {editorState.snapEnabled ? 'ON' : 'OFF'}
            </Button>

            <Button
              size="sm"
              variant={editorState.showSectorPolygons ? 'primary' : 'outline'}
              onPress={() => engine?.toggleSectors()}
              className="h-7 text-xs font-semibold"
              aria-label="Show/Hide Sector Boundaries"
            >
              <Icon name="fa-draw-polygon" />
              Sectors
            </Button>

            <span className="hidden text-xs text-muted xl:inline">
              <b>L</b> Lift · <b>S</b> Slope · Click run to edit
            </span>
          </div>
        ) : (
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
                  {editorState.liveLength.toLocaleString()} {distUnit}
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
                    onChange={(e) => engine?.setEditorName(e.target.value)}
                    placeholder="Segment name"
                    className="h-7 w-36 rounded-md border border-white/15 bg-slate-900 px-2 text-xs font-semibold text-foreground outline-none focus:border-accent"
                  />

                  <Select
                    value={editorState.sector}
                    onChange={(val) => engine?.setEditorSector(Number(val))}
                    aria-label="Sector"
                    options={[
                      { id: 0, label: 'Default (0)' },
                      ...sectors.map((sec) => ({
                        id: sec.id,
                        label: sec.name,
                      })),
                    ]}
                    className="h-7 w-28 text-xs"
                  />

                  {editorState.type !== 'lift' && (
                    <>
                      <Select
                        value={editorState.slopeType}
                        onChange={(val) => engine?.setEditorSlopeType(String(val))}
                        aria-label="Slope type"
                        options={[
                          { id: 'downhill', label: 'Downhill' },
                          { id: 'crosscountry', label: 'Cross-Country' },
                          { id: 'snowpark', label: 'Snow Park' },
                          { id: 'luge', label: 'Luge' },
                        ]}
                        className="h-7 w-28 text-xs"
                      />

                      <Select
                        value={editorState.difficulty}
                        onChange={(val) => engine?.setEditorDifficulty(String(val))}
                        aria-label="Difficulty"
                        options={[
                          { id: 'green', label: 'Green (Easy)' },
                          { id: 'blue', label: 'Blue (Intermediate)' },
                          { id: 'black', label: 'Black (Advanced)' },
                          { id: 'double_black', label: 'Double Black' },
                        ]}
                        className="h-7 w-32 text-xs"
                      />
                    </>
                  )}

                  {editorState.mode === 'edit' && (
                    <Button
                      size="sm"
                      variant={editorState.appendMode ? 'primary' : 'outline'}
                      onPress={() => engine?.toggleAppend()}
                      className="h-7 text-xs font-semibold"
                      aria-label="Toggle whether clicking appends points"
                    >
                      <Icon name="fa-plus" /> Append: {editorState.appendMode ? 'ON' : 'OFF'}
                    </Button>
                  )}

                  <Button
                    size="sm"
                    variant="outline"
                    onPress={() => engine?.reverse()}
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
                onPress={() => engine?.undo()}
                className="h-7 text-xs text-warning"
                aria-label="Undo point (Z)"
              >
                <Icon name="fa-rotate-left" /> Undo
              </Button>

              <Button
                size="sm"
                variant="outline"
                onPress={() => engine?.redo()}
                className="h-7 text-xs text-foreground"
                aria-label="Redo point (Shift+Z)"
              >
                <Icon name="fa-rotate-right" /> Redo
              </Button>

              <Button
                size="sm"
                variant="secondary"
                onPress={() => engine?.saveEditor(false)}
                className="h-7 bg-emerald-600 text-xs font-bold text-white hover:bg-emerald-500"
                aria-label="Save (Enter)"
              >
                <Icon name="fa-check" /> Save
              </Button>

              {editorState.mode === 'draw' && (
                <Button
                  size="sm"
                  variant="primary"
                  onPress={() => engine?.saveEditor(true)}
                  className="h-7 text-xs font-bold"
                  aria-label="Save & Immediately Draw Next"
                >
                  <Icon name="fa-forward-step" /> Save + Next
                </Button>
              )}

              <Button
                size="sm"
                variant="ghost"
                onPress={() => engine?.cancelEditor()}
                className="h-7 text-xs text-danger"
                aria-label="Cancel (Esc)"
              >
                <Icon name="fa-xmark" /> Cancel
              </Button>
            </div>

            <div className="flex items-center justify-between border-t border-white/10 pt-1 text-xs text-muted">
              <span>
                {editorState.mode === 'draw' && editorState.type === 'lift' ? (
                  <>
                    <b>Click</b> the bottom station, then the top: the lift saves itself · <b>Shift-click</b> the top to add bends
                  </>
                ) : editorState.mode === 'draw' ? (
                  <>
                    <b>Click</b> from the top down · <b>Double-click</b> or <b>Enter</b> to finish · <b>Alt</b> to skip snapping
                  </>
                ) : (
                  <>
                    <b>Click map</b> to add · <b>Drag dots</b> to move · <b>Click/drag + midpoints</b> to bend ·{' '}
                    <b>Right-click</b> point to delete
                  </>
                )}
              </span>
              {editorState.snapStatus && (
                <span className="font-mono text-link">Snapped: {editorState.snapStatus}</span>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export function AdminInspectorDrawer({
  sectors,
  segments,
  distUnit,
  mapFilter,
  engine,
  getSectorLabel,
  onSegmentsChange,
  onClose,
  onToast,
}: {
  sectors: Sector[];
  segments: MapSegment[];
  distUnit: string;
  mapFilter: string;
  engine: any;
  getSectorLabel: (id: any) => string;
  onSegmentsChange: (segs: MapSegment[]) => void;
  onClose: () => void;
  onToast: (msg: string, type?: 'success' | 'error' | 'warning' | 'info') => void;
}) {
  const [inspectorSearch, setInspectorSearch] = useState('');
  const [inspectorFilter, setInspectorFilter] = useState('all');
  const [showNewSectorForm, setShowNewSectorForm] = useState(false);
  const [newSecName, setNewSecName] = useState('');
  const [newSecColor, setNewSecColor] = useState('#3b82f6');

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

  return (
    <aside className="absolute top-3 right-3 bottom-3 z-[950] flex w-96 max-w-[calc(100vw-24px)] flex-col overflow-hidden rounded-xl border border-border bg-surface text-foreground shadow-2xl">
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
        <Button size="sm" variant="ghost" onPress={onClose} className="size-8 p-0 text-muted hover:text-foreground" aria-label="Close">
          <Icon name="fa-xmark" />
        </Button>
      </div>

      <div className="flex-1 space-y-5 overflow-y-auto p-3.5">
        <div>
          <div className="mb-2 flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-muted">Mountain Sectors</span>
            <div className="flex items-center gap-1">
              <Button
                size="sm"
                variant="outline"
                onPress={() => engine?.autoAssignSectors()}
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

          {showNewSectorForm && (
            <div className="mb-3 space-y-2 rounded-lg border border-border bg-surface-secondary p-2.5">
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
                <Button size="sm" variant="ghost" onPress={() => setShowNewSectorForm(false)} className="h-7 text-xs">
                  Cancel
                </Button>
                <Button
                  size="sm"
                  variant="secondary"
                  onPress={() => {
                    if (!newSecName.trim()) {
                      onToast('Please enter a sector name', 'warning');
                      return;
                    }
                    engine?.createSector(newSecName.trim(), newSecColor, () => {
                      setNewSecName('');
                      setShowNewSectorForm(false);
                    });
                  }}
                  className="h-7 bg-emerald-600 text-xs font-semibold text-white hover:bg-emerald-500"
                >
                  Create
                </Button>
              </div>
            </div>
          )}

          <div className="flex flex-col gap-1.5">
            {sectors.map((sec) => {
              const bp = typeof sec.boundary_points === 'string' ? JSON.parse(sec.boundary_points || '[]') : sec.boundary_points || [];
              const hasBoundary = bp.length >= 3;
              const countInSec = segments.filter((s) => Number(s.sector) === Number(sec.id)).length;

              return (
                <div key={sec.id} className="flex items-center gap-2 rounded-lg border border-border bg-surface-secondary p-2 text-xs">
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
                      <span className="font-mono text-xs text-muted">#{sec.id}</span>
                    </div>
                    <div className="text-xs text-muted">
                      {countInSec} runs · {hasBoundary ? 'Boundary set' : 'No boundary'}
                    </div>
                  </div>

                  <div className="flex shrink-0 items-center gap-1">
                    <Button
                      size="sm"
                      variant="ghost"
                      onPress={() => {
                        onClose();
                        engine?.startEditSectorBoundary(sec);
                      }}
                      className="size-7 p-0 text-xs text-muted hover:text-link"
                      aria-label="Draw Sector Boundary"
                    >
                      <Icon name="fa-draw-polygon" className={hasBoundary ? 'text-link' : 'opacity-40'} />
                    </Button>
                    <Button
                      size="sm"
                      variant="ghost"
                      onPress={() => engine?.toggleSectorVisibility(sec.id)}
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
                      onPress={() => engine?.deleteSector(sec.id)}
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

        <div className="border-t border-border pt-4">
          <div className="mb-2.5 flex items-center justify-between">
            <span className="text-xs font-bold uppercase tracking-wider text-muted">All segments ({segments.length})</span>
            <div className="flex items-center gap-1">
              <Button
                size="sm"
                variant="secondary"
                onPress={() => {
                  onClose();
                  engine?.startDraw('lift');
                }}
                className="h-7 bg-amber-500/20 text-xs font-semibold text-amber-300 hover:bg-amber-500/30"
              >
                <Icon name="fa-plus" /> Lift
              </Button>
              <Button
                size="sm"
                variant="secondary"
                onPress={() => {
                  onClose();
                  engine?.startDraw('slope');
                }}
                className="h-7 bg-emerald-500/20 text-xs font-semibold text-emerald-300 hover:bg-emerald-500/30"
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
                className="h-8 w-full rounded-lg border border-border bg-surface-secondary pr-2.5 pl-8 text-xs text-foreground outline-none focus:border-accent"
              />
            </div>
            <Select
              value={inspectorFilter}
              onChange={(val) => setInspectorFilter(String(val))}
              aria-label="Filter inspector items"
              options={[
                { id: 'all', label: 'All types' },
                { id: 'lift', label: 'Lifts only' },
                { id: 'slope', label: 'Slopes only' },
                { id: 'unassigned', label: 'Unassigned' },
              ]}
              className="h-8 text-xs"
            />
          </div>

          <div className="flex flex-col gap-1.5">
            {inspectorSegments.map((seg) => (
              <div key={seg.id} className="flex items-center gap-2 rounded-lg border border-border bg-surface-secondary p-2 text-xs">
                <div onClick={() => engine?.zoomToSegment(seg.id)} className="flex min-w-0 flex-1 cursor-pointer items-center gap-2.5">
                  <div className="flex size-6 shrink-0 items-center justify-center">
                    <TrailSign seg={seg} />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="truncate font-semibold text-foreground">{seg.name || 'Unnamed'}</div>
                    <div className="text-xs text-muted">
                      <span className="font-mono">
                        {Math.round(seg.length_meters || 0)} {distUnit}
                      </span>{' '}
                      · {getSectorLabel(seg.sector)}
                    </div>
                  </div>
                </div>

                <div className="flex shrink-0 items-center gap-1">
                  <Button
                    size="sm"
                    variant="ghost"
                    onPress={() => {
                      onClose();
                      engine?.zoomToSegment(seg.id);
                      engine?.startEditSegment(seg);
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
                      engine?.postJSON(`/map/segment/delete/${seg.id}`, {}, (res: any) => {
                        if (res && res.success) {
                          const updated = segments.filter((s) => String(s.id) !== String(seg.id));
                          onSegmentsChange(updated);
                          engine?.setFilter(mapFilter);
                          onToast(`Deleted "${seg.name || 'Segment'}"`, 'success');
                        } else {
                          onToast(res?.error || 'Failed to delete segment', 'error');
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
  );
}
