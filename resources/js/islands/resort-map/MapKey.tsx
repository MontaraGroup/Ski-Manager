import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import type { MapLens, MapViewMode } from './types';

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
        <dl id="map-key" className="mt-2 grid grid-cols-[auto_1fr] items-center gap-x-2 gap-y-1.5 rounded-lg border border-border bg-surface p-3 shadow-sm">
          {(!map3dEnabled || lens === 'trails') && (
            <>
              <dt className="flex w-6 justify-center"><span className="tsign green" /></dt>
              <dd>Easiest</dd>
              <dt className="flex w-6 justify-center"><span className="tsign blue" /></dt>
              <dd>More difficult</dd>
              <dt className="flex w-6 justify-center"><span className="tsign black" /></dt>
              <dd>Most difficult</dd>
              <dt className="flex w-6 justify-center">
                <span className="tsign-pair"><span className="tsign black" /><span className="tsign black" /></span>
              </dt>
              <dd>Experts only</dd>
              <dt className="flex w-6 justify-center text-warning"><Icon name="fa-elevator" /></dt>
              <dd>Lift</dd>
            </>
          )}

          {map3dEnabled && lens === 'traffic' && (
            <>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-success" /></dt>
              <dd>Quiet flow (&lt;50%)</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-warning" /></dt>
              <dd>Moderate (50-80%)</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-danger" /></dt>
              <dd>Busy (&gt;80%)</dd>
            </>
          )}

          {map3dEnabled && lens === 'snow' && (
            <>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-sky-500" /></dt>
              <dd>Powder / Packed</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-emerald-500" /></dt>
              <dd>Groomed</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-amber-400" /></dt>
              <dd>Icy</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-danger" /></dt>
              <dd>Bare</dd>
            </>
          )}

          {map3dEnabled && lens === 'steepness' && (
            <>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-emerald-500" /></dt>
              <dd>Gentle grade</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-amber-500" /></dt>
              <dd>Mid pitch</dd>
              <dt className="flex w-6 justify-center"><span className="inline-block size-3 rounded-full bg-rose-600" /></dt>
              <dd>Steep fall line</dd>
            </>
          )}

          <dt className="flex w-6 justify-center" aria-hidden="true">
            <svg width="22" height="6" viewBox="0 0 22 6"><line x1="1" y1="3" x2="21" y2="3" stroke="currentColor" strokeWidth="4" strokeLinecap="round" /></svg>
          </dt>
          <dd>{isAdmin ? 'Built by you' : 'Yours'}</dd>
          {!isAdmin && (
            <>
              <dt className="flex w-6 justify-center" aria-hidden="true">
                <svg width="22" height="6" viewBox="0 0 22 6"><line x1="1" y1="3" x2="21" y2="3" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeDasharray="1 5" /></svg>
              </dt>
              <dd>You can build it</dd>
            </>
          )}

          {map3dEnabled && showBuildings && (
            <>
              <dt className="flex w-6 justify-center text-link"><Icon name="fa-building" /></dt>
              <dd>Village facility / park</dd>
            </>
          )}
          {map3dEnabled && showSkiers && (
            <>
              <dt className="flex w-6 justify-center text-warning"><Icon name="fa-person-skiing" /></dt>
              <dd>Live skier</dd>
            </>
          )}
        </dl>
      )}
    </div>
  );
}
