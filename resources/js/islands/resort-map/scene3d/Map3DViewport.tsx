import { useEffect, useRef, useState } from 'react';
import { Button, Spinner } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { Select } from '@/components/Select';
import { TrailSign, formatCurrency, sameId } from '../shared';
import type {
  BuiltItem,
  LiveSkiersSnapshot,
  MapConfig,
  MapLens,
  MapLighting,
  MapSegment,
  MapWeather,
  PlacedFacility,
} from '../types';
import { createMap3DRenderer, type PickResult } from './renderer';

const LIGHTING_OPTIONS: { id: MapLighting; label: string }[] = [
  { id: 'live', label: 'Resort clock' },
  { id: 'day', label: 'Daylight' },
  { id: 'sunset', label: 'Sunset' },
  { id: 'night', label: 'Night' },
];

export interface Map3DViewportHandle {
  fit: () => void;
  focusSegment: (segId: string | number) => void;
  focusPoint: (mapX: number, mapY: number) => void;
}

export default function Map3DViewport({
  mapConfig,
  segments,
  builtIds,
  builtItems,
  facilities,
  liveSkiers,
  groomerActive,
  nightLitCount,
  weather,
  filter,
  showAvailable,
  showBuildings,
  showSkiers,
  selectedSegId,
  selectedFacilityKey,
  lens,
  lighting,
  onLightingChange,
  placingFacility,
  onCancelPlacing,
  onSelectSegment,
  onSelectFacility,
  onPlaceFacility,
  onFallbackTo2D,
  viewportRef,
  distUnit,
  isImperial,
}: {
  mapConfig: MapConfig;
  segments: MapSegment[];
  builtIds: (string | number)[];
  builtItems: Record<string, BuiltItem>;
  facilities: PlacedFacility[];
  liveSkiers: LiveSkiersSnapshot | null;
  groomerActive: number;
  nightLitCount: number;
  weather: MapWeather | null;
  filter: string;
  showAvailable: boolean;
  showBuildings: boolean;
  showSkiers: boolean;
  selectedSegId: string | number | null;
  selectedFacilityKey: string | null;
  lens: MapLens;
  lighting: MapLighting;
  onLightingChange: (l: MapLighting) => void;
  placingFacility: PlacedFacility | null;
  onCancelPlacing: () => void;
  onSelectSegment: (seg: MapSegment) => void;
  onSelectFacility: (fac: PlacedFacility) => void;
  onPlaceFacility: (mapX: number, mapY: number) => void;
  onFallbackTo2D: (reason: string) => void;
  viewportRef: React.MutableRefObject<Map3DViewportHandle | null>;
  distUnit: string;
  isImperial: boolean;
}) {
  const canvasRef = useRef<HTMLCanvasElement>(null);
  const rendererRef = useRef<ReturnType<typeof createMap3DRenderer>>(null);
  const [hdLoading, setHdLoading] = useState(false);
  const [tilt, setTilt] = useState<'3d' | 'top'>('3d');
  const [paused, setPaused] = useState<boolean>(
    () => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches
  );
  const [hoverInfo, setHoverInfo] = useState<{
    pick: NonNullable<PickResult>;
    x: number;
    y: number;
  } | null>(null);

  const latestCallbacks = useRef({ onSelectSegment, onSelectFacility, onPlaceFacility, placingFacility });
  latestCallbacks.current = { onSelectSegment, onSelectFacility, onPlaceFacility, placingFacility };

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;
    const renderer = createMap3DRenderer(
      canvas,
      mapConfig,
      {
        segments,
        builtIds,
        builtItems,
        facilities,
        liveSkiers,
        groomerActive,
        nightLitCount,
        weather,
        filter,
        showAvailable,
        showBuildings,
        showSkiers,
        selectedSegId,
        selectedFacilityKey,
        lens,
        lighting,
        paused,
        placingMode: !!placingFacility,
      },
      {
        onHover: (pick, clientX, clientY) => {
          if (!pick || pick.kind === 'terrain' || latestCallbacks.current.placingFacility) {
            setHoverInfo(null);
            return;
          }
          const rect = canvas.getBoundingClientRect();
          setHoverInfo({ pick, x: clientX - rect.left, y: clientY - rect.top });
        },
        onClick: (pick) => {
          if (!pick) return;
          if (latestCallbacks.current.placingFacility) {
            latestCallbacks.current.onPlaceFacility(pick.mapX, pick.mapY);
            return;
          }
          if (pick.kind === 'facility') {
            latestCallbacks.current.onSelectFacility(pick.facility);
          } else if (pick.kind === 'segment') {
            latestCallbacks.current.onSelectSegment(pick.segment);
          }
        },
        onHdLoading: setHdLoading,
        onWebGLFailed: onFallbackTo2D,
      }
    );
    if (!renderer) return;
    rendererRef.current = renderer;
    viewportRef.current = {
      fit: () => renderer.fit(),
      focusSegment: (id) => renderer.focusSegment(id),
      focusPoint: (x, y) => renderer.focusPoint(x, y),
    };
    return () => {
      viewportRef.current = null;
      rendererRef.current = null;
      renderer.destroy();
    };
  }, []);

  useEffect(() => {
    rendererRef.current?.updateState({
      segments,
      builtIds,
      builtItems,
      facilities,
      liveSkiers,
      groomerActive,
      nightLitCount,
      weather,
      filter,
      showAvailable,
      showBuildings,
      showSkiers,
      selectedSegId,
      selectedFacilityKey,
      lens,
      lighting,
      paused,
      placingMode: !!placingFacility,
    });
  }, [
    segments,
    builtIds,
    builtItems,
    facilities,
    liveSkiers,
    groomerActive,
    nightLitCount,
    weather,
    filter,
    showAvailable,
    showBuildings,
    showSkiers,
    selectedSegId,
    selectedFacilityKey,
    lens,
    lighting,
    paused,
    placingFacility,
  ]);

  /** Arrow keys move, Shift+arrows turn and tilt, + and - zoom. F (fit) and Escape live on the page. */
  function onCanvasKey(e: React.KeyboardEvent<HTMLCanvasElement>) {
    const r = rendererRef.current;
    if (!r) return;
    const step = 60;
    let handled = true;
    if (e.shiftKey && e.key === 'ArrowLeft') r.orbitBy(0.15, 0);
    else if (e.shiftKey && e.key === 'ArrowRight') r.orbitBy(-0.15, 0);
    else if (e.shiftKey && e.key === 'ArrowUp') r.orbitBy(0, 0.08);
    else if (e.shiftKey && e.key === 'ArrowDown') r.orbitBy(0, -0.08);
    else if (e.key === 'ArrowLeft') r.panBy(-step, 0);
    else if (e.key === 'ArrowRight') r.panBy(step, 0);
    else if (e.key === 'ArrowUp') r.panBy(0, -step);
    else if (e.key === 'ArrowDown') r.panBy(0, step);
    else if (e.key === '+' || e.key === '=') r.zoomBy(0.78);
    else if (e.key === '-' || e.key === '_') r.zoomBy(1.28);
    else handled = false;
    if (handled) e.preventDefault();
  }

  const hoverContent = (() => {
    if (!hoverInfo) return null;
    const { pick } = hoverInfo;
    if (pick.kind === 'facility') {
      const f = pick.facility;
      return (
        <div className="space-y-0.5 text-xs">
          <div className="flex items-center gap-1.5 font-semibold text-foreground">
            <Icon name={f.icon} className="text-link" />
            <span>{f.label}</span>
          </div>
          <div className="text-muted">
            {f.typeLabel} · <span className="tabular-nums">{f.occupancyPct}%</span> full · Cond{' '}
            <span className="tabular-nums">{f.conditionPct}%</span>
          </div>
          {f.dailyRevenue > 0 && (
            <div className="tabular-nums text-success">+{formatCurrency(f.dailyRevenue, isImperial)}/day</div>
          )}
        </div>
      );
    }
    if (pick.kind === 'segment') {
      const seg = pick.segment;
      const built = builtIds.some((b) => sameId(b, seg.id));
      const item = builtItems[String(seg.id)];
      const traffic = liveSkiers?.perSegment[String(seg.id)];
      return (
        <div className="space-y-0.5 text-xs">
          <div className="flex items-center gap-1.5 font-semibold text-foreground">
            <TrailSign seg={seg} />
            <span>{seg.name || seg.type}</span>
          </div>
          <div className="text-muted">
            <span className="tabular-nums">{Math.round(seg.length_meters || 0).toLocaleString()} {distUnit}</span>
            {built && item ? (
              <>
                {' · '}
                <span className="capitalize">{item.status || 'open'}</span>
                {item.condition_pct != null && ` · ${item.condition_pct}%`}
              </>
            ) : (
              ' · not built, click for the price'
            )}
          </div>
          {built && traffic && traffic.skiers > 0 && (
            <div className="tabular-nums font-medium text-link">
              {traffic.skiers} {seg.type === 'lift' ? 'riders' : 'skiers'} live
              {traffic.queue > 0 ? ` (${traffic.queue} in queue)` : ''}
            </div>
          )}
        </div>
      );
    }
    return null;
  })();

  return (
    <div className="relative h-full w-full select-none overflow-hidden bg-background">
      <canvas
        ref={canvasRef}
        role="application"
        tabIndex={0}
        onKeyDown={onCanvasKey}
        aria-label={`3D trail map of ${mapConfig.name}. Arrow keys move, Shift and arrows turn, plus and minus zoom. With a mouse: drag to move, right-drag to turn, scroll to zoom. On touch: drag, pinch and twist.`}
        className={`block h-full w-full touch-none outline-none focus-visible:ring-2 focus-visible:ring-focus focus-visible:ring-inset ${
          placingFacility ? 'cursor-crosshair' : hoverInfo ? 'cursor-pointer' : 'cursor-grab active:cursor-grabbing'
        }`}
      />

      {hdLoading && (
        <div className="pointer-events-none absolute top-3 right-3 z-[800] inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-1.5 text-sm text-foreground shadow-sm">
          <Spinner size="sm" />
          <span>Loading detailed terrain…</span>
        </div>
      )}

      {placingFacility && (
        <div className="absolute top-3 left-1/2 z-[880] flex -translate-x-1/2 items-center gap-2 rounded-xl border border-accent bg-surface px-3.5 py-2 text-sm text-foreground shadow-lg">
          <Icon name="fa-location-dot" className="text-link" />
          <span>
            Click anywhere on the mountain to move <b>{placingFacility.label}</b>
          </span>
          <Button size="sm" variant="ghost" onPress={onCancelPlacing} className="min-h-9 text-muted hover:text-foreground">
            Cancel
          </Button>
        </div>
      )}

      {hoverContent && hoverInfo && (
        <div
          style={{
            left: Math.min(hoverInfo.x + 14, (canvasRef.current?.clientWidth || 600) - 220),
            top: Math.max(8, hoverInfo.y - 52),
          }}
          className="pointer-events-none absolute z-[820] max-w-[220px] rounded-lg border border-border bg-surface px-2.5 py-1.5 shadow-lg"
        >
          {hoverContent}
        </div>
      )}

      {/* Camera and light controls. On phones they stop short of the round Build button on the right. */}
      <div className="absolute bottom-4 left-3 z-[800] flex max-w-[calc(100%-6rem)] flex-wrap items-center gap-1.5 md:max-w-[calc(100%-1.5rem)]">
        <div className="flex rounded-lg border border-border bg-surface p-0.5 shadow-sm" role="group" aria-label="Camera angle">
          {(
            [
              ['3d', 'Angled'],
              ['top', 'Top down'],
            ] as const
          ).map(([id, label]) => (
            <Button
              key={id}
              size="sm"
              variant={tilt === id ? 'primary' : 'ghost'}
              aria-pressed={tilt === id}
              onPress={() => {
                setTilt(id);
                rendererRef.current?.setTilt(id);
              }}
              className="min-h-10 px-2.5 text-xs font-semibold md:min-h-8"
            >
              {label}
            </Button>
          ))}
        </div>

        <div className="flex rounded-lg border border-border bg-surface p-0.5 shadow-sm" role="group" aria-label="Zoom">
          <Button
            size="sm"
            variant="ghost"
            isIconOnly
            onPress={() => rendererRef.current?.zoomBy(1.28)}
            aria-label="Zoom out"
            className="size-10 md:size-8"
          >
            <Icon name="fa-minus" />
          </Button>
          <Button
            size="sm"
            variant="ghost"
            isIconOnly
            onPress={() => rendererRef.current?.zoomBy(0.78)}
            aria-label="Zoom in"
            className="size-10 md:size-8"
          >
            <Icon name="fa-plus" />
          </Button>
        </div>

        <Select
          value={lighting}
          onChange={(v) => onLightingChange(v as MapLighting)}
          aria-label="Light on the mountain"
          options={LIGHTING_OPTIONS}
          className="w-36 shadow-sm"
        />

        <Button
          size="sm"
          variant="outline"
          onPress={() => setPaused((p) => !p)}
          aria-pressed={!paused}
          className="min-h-10 gap-1.5 bg-surface px-2.5 text-xs font-semibold shadow-sm md:min-h-8"
        >
          <Icon name={paused ? 'fa-play' : 'fa-pause'} />
          {paused ? 'Play skiers' : 'Pause skiers'}
        </Button>

        <span className="pointer-events-none hidden h-8 items-center rounded-lg border border-border bg-surface px-2.5 text-xs text-muted shadow-sm xl:inline-flex">
          Drag to move · right-drag to turn · scroll to zoom
        </span>
      </div>
    </div>
  );
}
