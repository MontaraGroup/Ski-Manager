import { parsePoints, sameId } from './shared';
import type {
  BuiltItem,
  MapBuilding,
  MapConfig,
  MapParking,
  MapSegment,
  MapTerrainPark,
  PlacedFacility,
} from './types';

// Fallback village anchors per mountain when no lifts are built yet ([mapY, mapX])
const BASE_ANCHORS: Record<string, [number, number][]> = {
  ParkCity: [
    [62, 146],
    [60, 392],
  ],
  DeerValley: [[66, 290]],
  AspenSnowmass: [[68, 280]],
  BigSkyCombo: [[65, 295]],
  Vail: [[68, 300]],
  PalisadesTahoe: [[66, 285]],
  Killington: [[65, 290]],
};

const VILLAGE_TYPES = new Set(['hotel', 'rental', 'retail', 'real_estate', 'off_season']);
const TRANSIT_TYPES = new Set(['parking', 'transportation']);

function clampX(x: number, w: number): number {
  return Math.max(12, Math.min(w - 12, x));
}

function clampY(y: number, h: number): number {
  return Math.max(12, Math.min(h - 12, y));
}

function goldenOffset(index: number, ringRadius: number, squashY = 0.65): [number, number] {
  const angle = index * 2.3999632; // golden angle in radians
  const r = ringRadius * Math.sqrt(index + 1);
  return [Math.sin(angle) * r * squashY, Math.cos(angle) * r];
}

function nudgeNoOverlap(
  y: number,
  x: number,
  taken: [number, number][],
  minDist: number,
  w: number,
  h: number
): [number, number] {
  let cy = clampY(y, h);
  let cx = clampX(x, w);
  for (let attempt = 0; attempt < 18; attempt++) {
    const clash = taken.some(([ty, tx]) => Math.hypot(ty - cy, tx - cx) < minDist);
    if (!clash) break;
    const [dy, dx] = goldenOffset(attempt + 1, minDist * 0.72, 0.7);
    cy = clampY(y + dy, h);
    cx = clampX(x + dx, w);
  }
  taken.push([cy, cx]);
  return [Number(cy.toFixed(2)), Number(cx.toFixed(2))];
}

/**
 * Computes deterministic map coordinates (mapX in [0..width], mapY in [0..height]) for all
 * player buildings, parking lots, and terrain parks. Shared identically between 3D and 2D maps.
 */
export function computePlacedFacilities(
  resortMap: string,
  mapConfig: MapConfig,
  segments: MapSegment[],
  builtIds: (string | number)[],
  builtItems: Record<string, BuiltItem>,
  buildings: MapBuilding[],
  parking: MapParking[],
  terrainParks: MapTerrainPark[]
): PlacedFacility[] {
  const w = mapConfig.width || 600;
  const h = mapConfig.height || 340;
  const isBuilt = (id: string | number) => builtIds.some((b) => sameId(b, id));

  const builtLiftSegs = segments.filter((s) => s.type === 'lift' && isBuilt(s.id));
  const anyLiftSegs = builtLiftSegs.length > 0 ? builtLiftSegs : segments.filter((s) => s.type === 'lift');

  // Derive base and summit hubs from lift stations
  const baseHubs: [number, number][] = [];
  const topHubs: [number, number][] = [];
  const segPts = new Map<string, [number, number][]>();

  for (const seg of segments) {
    const pts = parsePoints(seg.points);
    if (pts.length >= 2) segPts.set(String(seg.id), pts);
  }

  for (const lift of anyLiftSegs) {
    const pts = segPts.get(String(lift.id));
    if (!pts || pts.length < 2) continue;
    const pStart = pts[0];
    const pEnd = pts[pts.length - 1];
    // Lifts are typically base->top, take lower y as base just in case
    if (pStart[0] <= pEnd[0]) {
      baseHubs.push(pStart);
      topHubs.push(pEnd);
    } else {
      baseHubs.push(pEnd);
      topHubs.push(pStart);
    }
  }

  baseHubs.sort((a, b) => a[0] - b[0]);
  topHubs.sort((a, b) => b[0] - a[0]);

  const fallbackBases = BASE_ANCHORS[resortMap] ?? [[64, w * 0.48]];
  const villageAnchors = baseHubs.length > 0 ? baseHubs.slice(0, 4) : fallbackBases;
  const summitAnchors = topHubs.length > 0 ? topHubs.slice(0, 4) : [[165, w * 0.45]];

  // Map player_items.id -> segment points for terrain parks linked to slope_id
  const itemIdToPts = new Map<number, [number, number][]>();
  for (const seg of segments) {
    const bi = builtItems[String(seg.id)];
    const pts = segPts.get(String(seg.id));
    if (bi && bi.id && pts) {
      itemIdToPts.set(Number(bi.id), pts);
    }
  }

  const taken: [number, number][] = [];
  const rawList: PlacedFacility[] = [];

  // Record explicit custom positions first so auto-placed buildings avoid them
  for (const b of buildings) {
    if (b.map_x !== null && b.map_y !== null && (!b.resort_map || b.resort_map === resortMap)) {
      taken.push([b.map_y, b.map_x]);
    }
  }
  for (const p of parking) {
    if (p.map_x !== null && p.map_y !== null && (!p.resort_map || p.resort_map === resortMap)) {
      taken.push([p.map_y, p.map_x]);
    }
  }

  let villageIdx = 0;
  let transitIdx = 0;
  let lodgeIdx = 0;
  let patrolIdx = 0;

  for (const pk of parking) {
    const custom = pk.map_x !== null && pk.map_y !== null && (!pk.resort_map || pk.resort_map === resortMap);
    let my: number;
    let mx: number;
    if (custom) {
      my = pk.map_y!;
      mx = pk.map_x!;
    } else {
      const anchor = villageAnchors[transitIdx % villageAnchors.length];
      const spreadX = (Math.floor(transitIdx / villageAnchors.length) - 0.5) * 20;
      [my, mx] = nudgeNoOverlap(anchor[0] - 14, anchor[1] + spreadX, taken, 11, w, h);
      transitIdx++;
    }
    rawList.push({
      key: `parking_${pk.id}`,
      kind: 'parking',
      id: pk.id,
      subType: pk.parking_type,
      label: pk.name,
      typeLabel: pk.typeLabel,
      icon: pk.icon,
      route: '/parking',
      status: pk.status,
      level: pk.parking_type === 'garage' ? 2 : 1,
      conditionPct: Math.round(pk.condition_pct),
      occupancyPct: pk.occupancyPct,
      capacity: pk.capacity,
      currentCount: pk.occupied,
      dailyRevenue: Math.round(pk.daily_revenue),
      dailyUpkeep: 0,
      mapX: mx,
      mapY: my,
      customPlaced: custom,
      clusterCount: 1,
    });
  }

  for (const b of buildings) {
    const custom = b.map_x !== null && b.map_y !== null && (!b.resort_map || b.resort_map === resortMap);
    let my: number;
    let mx: number;

    if (custom) {
      my = b.map_y!;
      mx = b.map_x!;
    } else if (TRANSIT_TYPES.has(b.building_type)) {
      const anchor = villageAnchors[transitIdx % villageAnchors.length];
      const [dy, dx] = goldenOffset(transitIdx, 8, 0.4);
      [my, mx] = nudgeNoOverlap(anchor[0] - 11 + dy * 0.4, anchor[1] + dx + 8, taken, 10, w, h);
      transitIdx++;
    } else if (b.building_type === 'restaurant') {
      // Every third restaurant sits at a mountain top/mid station; others in base village
      if (lodgeIdx % 3 === 1 && summitAnchors.length > 0) {
        const sAnchor = summitAnchors[lodgeIdx % summitAnchors.length];
        [my, mx] = nudgeNoOverlap(sAnchor[0] - 4, sAnchor[1] + 5, taken, 10, w, h);
      } else {
        const vAnchor = villageAnchors[lodgeIdx % villageAnchors.length];
        const [dy, dx] = goldenOffset(villageIdx++, 6.5, 0.6);
        [my, mx] = nudgeNoOverlap(vAnchor[0] - 4 + dy, vAnchor[1] + dx, taken, 10, w, h);
      }
      lodgeIdx++;
    } else if (b.building_type === 'ski_patrol') {
      const hub =
        summitAnchors.length > 0 && baseHubs.length > 0
          ? [
              (summitAnchors[patrolIdx % summitAnchors.length][0] + villageAnchors[patrolIdx % villageAnchors.length][0]) * 0.5,
              (summitAnchors[patrolIdx % summitAnchors.length][1] + villageAnchors[patrolIdx % villageAnchors.length][1]) * 0.5,
            ]
          : villageAnchors[patrolIdx % villageAnchors.length];
      [my, mx] = nudgeNoOverlap(hub[0], hub[1] + 6, taken, 10, w, h);
      patrolIdx++;
    } else {
      // Hotel, rental, retail, real_estate, off_season
      const anchor = villageAnchors[villageIdx % villageAnchors.length];
      const [dy, dx] = goldenOffset(villageIdx, 6.2, 0.58);
      const yBias = VILLAGE_TYPES.has(b.building_type) ? -3 : -5;
      [my, mx] = nudgeNoOverlap(anchor[0] + yBias + dy, anchor[1] + dx, taken, 9.5, w, h);
      villageIdx++;
    }

    rawList.push({
      key: `building_${b.id}`,
      kind: 'building',
      id: b.id,
      subType: b.building_type,
      label: b.name,
      typeLabel: b.typeLabel,
      icon: b.icon,
      route: b.route,
      status: b.status,
      level: b.level,
      conditionPct: b.condition_pct,
      occupancyPct: b.occupancyPct,
      capacity: b.capacity,
      currentCount: b.guests,
      dailyRevenue: b.revenue_per_day,
      dailyUpkeep: b.upkeep_per_day,
      mapX: mx,
      mapY: my,
      customPlaced: custom,
      clusterCount: 1,
    });
  }

  for (const tp of terrainParks) {
    const slopePts = tp.slope_id ? itemIdToPts.get(tp.slope_id) : undefined;
    let ty = villageAnchors[0][0] + 10;
    let tx = villageAnchors[0][1] + 14;
    if (slopePts && slopePts.length >= 2) {
      const mid = slopePts[Math.floor(slopePts.length / 2)];
      ty = mid[0];
      tx = mid[1];
    }
    const [my, mx] = nudgeNoOverlap(ty, tx, taken, 8, w, h);
    const estCap = tp.size === 'large' ? 700 : tp.size === 'medium' ? 350 : 150;
    const occPct = tp.daily_visitors > 0 ? Math.min(100, Math.round((tp.daily_visitors / estCap) * 100)) : 0;
    rawList.push({
      key: `park_${tp.id}`,
      kind: 'terrain_park',
      id: tp.id,
      subType: tp.park_type,
      label: tp.name,
      typeLabel: `Terrain Park (${tp.size})`,
      icon: 'fa-person-snowboarding',
      route: '/terrain-parks',
      status: tp.status,
      level: tp.size === 'large' ? 3 : tp.size === 'medium' ? 2 : 1,
      conditionPct: Math.round(tp.condition_pct),
      occupancyPct: occPct,
      capacity: estCap,
      currentCount: tp.daily_visitors,
      dailyRevenue: 0,
      dailyUpkeep: 0,
      mapX: mx,
      mapY: my,
      customPlaced: false,
      slopeId: tp.slope_id,
      clusterCount: 1,
    });
  }

  // Cluster auto-placed buildings when a veteran resort has > 60 structures so the map stays clean & 60 FPS
  if (rawList.length <= 60) {
    return rawList;
  }

  const keepIndividual: PlacedFacility[] = [];
  const groups = new Map<string, PlacedFacility[]>();

  for (const fac of rawList) {
    if (fac.customPlaced || fac.kind === 'terrain_park') {
      keepIndividual.push(fac);
    } else {
      const gKey = `${fac.kind}:${fac.subType}`;
      const list = groups.get(gKey) || [];
      list.push(fac);
      groups.set(gKey, list);
    }
  }

  const clustered: PlacedFacility[] = [...keepIndividual];
  for (const [, list] of groups.entries()) {
    if (list.length <= 4) {
      clustered.push(...list);
      continue;
    }
    // Keep up to 3 anchors per type and fold the rest into clean village complexes
    const chunkSize = Math.ceil(list.length / 3);
    for (let i = 0; i < list.length; i += chunkSize) {
      const slice = list.slice(i, i + chunkSize);
      const lead = slice[0];
      if (slice.length === 1) {
        clustered.push(lead);
        continue;
      }
      const avgCond = Math.round(slice.reduce((a, c) => a + c.conditionPct, 0) / slice.length);
      const totalCap = slice.reduce((a, c) => a + c.capacity, 0);
      const totalCur = slice.reduce((a, c) => a + c.currentCount, 0);
      const totalRev = slice.reduce((a, c) => a + c.dailyRevenue, 0);
      const totalUpkeep = slice.reduce((a, c) => a + c.dailyUpkeep, 0);
      const maxLvl = Math.max(...slice.map((s) => s.level));
      clustered.push({
        ...lead,
        key: `${lead.key}_cluster_${slice.length}`,
        label: `${lead.typeLabel} District (${slice.length})`,
        level: maxLvl,
        conditionPct: avgCond,
        capacity: totalCap,
        currentCount: totalCur,
        occupancyPct: totalCap > 0 ? Math.min(100, Math.round((totalCur / totalCap) * 100)) : lead.occupancyPct,
        dailyRevenue: totalRev,
        dailyUpkeep: totalUpkeep,
        clusterCount: slice.length,
        clusteredFacilities: slice,
      });
    }
  }

  return clustered;
}
