import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { TrailSign, type Difficulty } from '@/components/TrailSign';
import { DIFFICULTY_LABEL, LINE_COLORS } from './shared';
import type { MapLens, MapViewMode } from './types';

const RUNS: Difficulty[] = ['green', 'blue', 'black', 'double_black'];

/**
 * A short stroke in the colour and dash the map draws, so a line on the map can be matched to its row.
 * `onSnow` lays it on a light strip like the snowy map, or the dark Advanced line vanishes on a dark panel.
 */
function Stroke({ color, dash, width = 4, onSnow = false }: { color: string; dash?: string; width?: number; onSnow?: boolean }) {
  return (
    <svg width="26" height="10" viewBox="0 0 26 10" aria-hidden="true" className="shrink-0">
      {onSnow && <rect x="0" y="0" width="26" height="10" rx="3" fill="#eef2f6" />}
      <line x1="4" y1="5" x2="22" y2="5" stroke={color} strokeWidth={width} strokeLinecap="round" strokeDasharray={dash} />
    </svg>
  );
}

function readOpen(): boolean {
  try {
    const saved = localStorage.getItem('map_key_open');
    if (saved !== null) return saved === '1';
  } catch {}
  // Closed on phones by default, where it would cover a lot of the map
  return typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches;
}

/** The trail key, behind a "Key" button so it doesn't sit on the map all the time */
export function MapKey({
  isAdmin,
  map3dEnabled = false,
  viewMode = '2d',
  lens = 'trails',
  showBuildings = true,
  showSkiers = true,
}: {
  isAdmin: boolean;
  map3dEnabled?: boolean;
  viewMode?: MapViewMode;
  lens?: MapLens;
  showBuildings?: boolean;
  showSkiers?: boolean;
}) {
  const [open, setOpen] = useState(readOpen);

  function toggle() {
    const next = !open;
    setOpen(next);
    try {
      localStorage.setItem('map_key_open', next ? '1' : '0');
    } catch {}
  }

  const posClass = map3dEnabled && viewMode === '3d' ? 'left-3' : 'left-14';

  return (
    <div className={`absolute top-3 ${posClass} z-[800] text-sm text-foreground`}>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        onPress={toggle}
        aria-expanded={open}
        aria-controls="map-key"
        className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-border bg-surface px-3 font-medium shadow-sm md:min-h-9"
      >
        <Icon name="fa-circle-info" /> Key
        <Icon name={open ? 'fa-chevron-up' : 'fa-chevron-down'} className="text-muted" />
      </Button>
      {open && (
        <dl id="map-key" className="mt-2 grid max-h-[min(60vh,24rem)] grid-cols-[auto_1fr] items-center gap-x-2.5 gap-y-1.5 overflow-y-auto rounded-xl border border-border bg-surface p-3 shadow-md">
          {(!map3dEnabled || lens === 'trails') && (
            <>
              {RUNS.map((d) => (
                <div key={d} className="contents">
                  <dt className="flex items-center gap-1.5">
                    <span className="flex w-7 justify-center">
                      <TrailSign difficulty={d} />
                    </span>
                    <Stroke color={LINE_COLORS[d]} onSnow />
                  </dt>
                  <dd>{DIFFICULTY_LABEL[d]}</dd>
                </div>
              ))}
              <dt className="flex items-center gap-1.5">
                <span className="flex w-7 justify-center text-warning">
                  <Icon name="fa-elevator" />
                </span>
                <Stroke color={LINE_COLORS.lift} dash="6 3" onSnow />
              </dt>
              <dd>Lift</dd>
            </>
          )}

          {map3dEnabled && lens === 'traffic' && (
            <>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-success" /></dt>
              <dd>Quiet flow (&lt;50%)</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-warning" /></dt>
              <dd>Moderate (50 to 80%)</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-danger" /></dt>
              <dd>Busy (&gt;80%)</dd>
            </>
          )}

          {map3dEnabled && lens === 'snow' && (
            <>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-sky-500" /></dt>
              <dd>Powder / Packed</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-emerald-500" /></dt>
              <dd>Groomed</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-amber-400" /></dt>
              <dd>Icy</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-danger" /></dt>
              <dd>Bare</dd>
            </>
          )}

          {map3dEnabled && lens === 'steepness' && (
            <>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-emerald-500" /></dt>
              <dd>Gentle grade</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-amber-500" /></dt>
              <dd>Mid pitch</dd>
              <dt className="flex justify-end pr-2"><span className="inline-block size-3 rounded-full bg-rose-600" /></dt>
              <dd>Steep fall line</dd>
            </>
          )}

          <dt className="flex items-center justify-end">
            <Stroke color="currentColor" />
          </dt>
          <dd>{isAdmin ? 'Built by you' : 'Yours (solid line)'}</dd>
          {!isAdmin && (
            <>
              <dt className="flex items-center justify-end">
                <Stroke color="currentColor" dash="1 5" width={3} />
              </dt>
              <dd>You can build it (dotted)</dd>
            </>
          )}

          {map3dEnabled && showBuildings && (
            <>
              <dt className="flex justify-end pr-2 text-link"><Icon name="fa-building" /></dt>
              <dd>Building, parking or park</dd>
            </>
          )}
          {map3dEnabled && showSkiers && (
            <>
              <dt className="flex justify-end pr-2 text-warning"><Icon name="fa-person-skiing" /></dt>
              <dd>Live skier</dd>
            </>
          )}
        </dl>
      )}
    </div>
  );
}
