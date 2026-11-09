import { useState } from 'react';
import { Icon } from '@/components/Icon';

function readOpen(): boolean {
  try {
    const saved = localStorage.getItem('map_key_open');
    if (saved !== null) return saved === '1';
  } catch {}
  // Closed on phones by default, where it would cover a lot of the map
  return typeof window !== 'undefined' && window.matchMedia('(min-width: 768px)').matches;
}

/** The trail key, behind a "Key" button so it doesn't sit on the map all the time */
export function MapKey({ isAdmin }: { isAdmin: boolean }) {
  const [open, setOpen] = useState(readOpen);

  function toggle() {
    const next = !open;
    setOpen(next);
    try {
      localStorage.setItem('map_key_open', next ? '1' : '0');
    } catch {}
  }

  return (
    <div className="absolute top-3 left-14 z-[800] text-sm text-foreground">
      <button
        type="button"
        onClick={toggle}
        aria-expanded={open}
        aria-controls="map-key"
        className="inline-flex min-h-11 items-center gap-2 rounded-lg border border-border bg-surface px-3 font-medium shadow-sm hover:bg-surface-secondary md:min-h-9"
      >
        <Icon name="fa-circle-info" /> Key
        <Icon name={open ? 'fa-chevron-up' : 'fa-chevron-down'} className="text-muted" />
      </button>
      {open && (
        <dl id="map-key" className="mt-2 grid grid-cols-[auto_1fr] items-center gap-x-2 gap-y-1.5 rounded-lg border border-border bg-surface p-3 shadow-sm">
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
        </dl>
      )}
    </div>
  );
}
