import { Icon } from '@/components/Icon';
import { DIFFICULTY_LABEL as SIGN_LABEL, TrailSign as Sign, type Difficulty } from '@/components/TrailSign';
import type { BuildCosts, MapSegment } from './types';

export function formatCurrency(n: number, isImperial: boolean): string {
  const prefix = isImperial ? '$' : '';
  const suffix = isImperial ? '' : ' €';
  return (n < 0 ? '-' : '') + prefix + Math.abs(Math.round(n)).toLocaleString('en-US') + suffix;
}

/** Lift systems in the order the panel lists them */
export const LIFT_TYPES: { id: string; name: string }[] = [
  { id: 'button', name: 'Surface lift' },
  { id: 'chair_fixed', name: 'Fixed-grip chairlift' },
  { id: 'chair_detach', name: 'Detachable chairlift' },
  { id: 'gondola', name: 'Gondola' },
  { id: 'cable_car', name: 'Cable car' },
];

export const SLOPE_TYPES: { id: string; name: string; icon: string }[] = [
  { id: 'downhill', name: 'Downhill', icon: 'fa-person-skiing' },
  { id: 'crosscountry', name: 'Cross-country', icon: 'fa-person-skiing-nordic' },
  { id: 'snowpark', name: 'Snow park', icon: 'fa-person-snowboarding' },
  { id: 'luge', name: 'Alpine luge', icon: 'fa-sleigh' },
];

/** The site's difficulty names (components/TrailSign), so the map and the other pages agree */
export const DIFFICULTY_LABEL: Record<string, string> = SIGN_LABEL;

/**
 * Line colours the 2D map draws each run in (mapEngine) and the 3D view matches (scene3d/geometry).
 * The key shows these next to the trail sign, so what's on the map can be read off the key.
 */
export const LINE_COLORS: Record<string, string> = {
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

function asDifficulty(d: string | undefined | null): Difficulty {
  return d === 'green' || d === 'black' || d === 'double_black' ? d : 'blue';
}

/**
 * What the server charges (ResortMap::calcCost, which truncates to whole units). The rates come
 * from the page props, so the price shown is the price paid.
 */
export function buildCost(seg: MapSegment, costs: BuildCosts, liftType = 'chair_fixed', seats = 2): number {
  const m = Number(seg.length_meters) || 0;
  if (seg.type === 'lift') {
    const rate = costs.liftPerM[liftType] ?? costs.liftPerM.chair_fixed ?? 150;
    const mult = costs.seatMult[String(seats)] ?? 1;
    return Math.trunc(m * rate * mult);
  }
  return Math.trunc(m * costs.slopePerM);
}

/** The lowest price a run can be built for: slopes have one price, lifts start at the smallest system */
export function cheapestCost(seg: MapSegment, costs: BuildCosts): number {
  if (seg.type !== 'lift') return buildCost(seg, costs);
  let min = Infinity;
  for (const [type, seatList] of Object.entries(costs.seatOptions)) {
    for (const s of seatList) min = Math.min(min, buildCost(seg, costs, type, s));
  }
  return Number.isFinite(min) ? min : buildCost(seg, costs);
}

export function parsePoints(raw: unknown): [number, number][] {
  if (!raw) return [];
  let pts: unknown = raw;
  if (typeof raw === 'string') {
    try {
      pts = JSON.parse(raw);
    } catch {
      return [];
    }
  }
  if (!Array.isArray(pts)) return [];
  return pts
    .map((p: any): [number, number] => {
      if (Array.isArray(p)) return [Number(p[0]) || 0, Number(p[1]) || 0];
      if (p && typeof p === 'object') return [Number(p.lat ?? 0), Number(p.lng ?? 0)];
      return [0, 0];
    })
    .filter((p) => !isNaN(p[0]) && !isNaN(p[1]));
}

/** Both ends of a run, in map coordinates */
export function endpoints(seg: MapSegment): [number, number][] {
  const pts = parsePoints(seg.points);
  return pts.length >= 2 ? [pts[0], pts[pts.length - 1]] : [];
}

/** Shortest distance between any end of `a` and any end of `b` (map units) */
export function endDistance(a: MapSegment, b: MapSegment): number {
  let best = Infinity;
  for (const p of endpoints(a)) {
    for (const q of endpoints(b)) {
      best = Math.min(best, Math.hypot(p[0] - q[0], p[1] - q[1]));
    }
  }
  return best;
}

export function sameId(a: string | number, b: string | number): boolean {
  return String(a) === String(b);
}

/** The nightly settle in the player's own time, e.g. "12:05 AM EST" */
export function settleText(iso: string | undefined): string {
  const at = iso ? new Date(iso) : null;
  if (!at || isNaN(at.getTime())) return '00:05 server time';
  return at.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' });
}

/** Trail-sign mark: shape and colour together, so colour is never the only signal. Lifts get the lift icon. */
export function TrailSign({ seg }: { seg: MapSegment }) {
  if (seg.type === 'lift') {
    return (
      <span role="img" aria-label="Lift" className="inline-flex size-5 shrink-0 items-center justify-center text-warning">
        <Icon name="fa-elevator" />
      </span>
    );
  }
  return <Sign difficulty={asDifficulty(seg.difficulty)} />;
}

/** "Lift" or the difficulty, for one line under a run's name */
export function runKind(seg: MapSegment): string {
  if (seg.type === 'lift') return 'Lift';
  return DIFFICULTY_LABEL[asDifficulty(seg.difficulty)];
}
