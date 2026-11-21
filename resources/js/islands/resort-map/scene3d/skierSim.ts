import { parsePoints, sameId } from '../shared';
import type {
  BuiltItem,
  LiveSkiersSnapshot,
  MapConfig,
  MapSegment,
  MapWeather,
  PlacedFacility,
} from '../types';
import type { Heightfield } from './heightfield';
import { clamp, samplePolylineAt, smoothTrailPolyline } from './math';

interface SkierAgent {
  kind: 'slope' | 'village' | 'queue';
  pts?: [number, number][];
  progress: number;
  speed: number;
  carvePhase: number;
  carveAmp: number;
  baseX: number;
  baseY: number;
  color: [number, number, number];
}

interface CarrierAgent {
  pts: [number, number][];
  progress: number;
  speed: number;
  yOffset: number;
  scale: number;
  color: [number, number, number];
}

interface SnowcatAgent {
  pts: [number, number][];
  progress: number;
  dir: number;
  speed: number;
}

interface Particle {
  x: number;
  y: number;
  z: number;
  vy: number;
  vx: number;
  scale: number;
}

const JACKET_COLORS: [number, number, number][] = [
  [0.92, 0.24, 0.26],
  [0.16, 0.54, 0.96],
  [0.96, 0.68, 0.14],
  [0.12, 0.76, 0.68],
  [0.94, 0.96, 0.99],
  [0.24, 0.28, 0.36],
];

function deterministicRand(seed: number): () => number {
  let s = (seed % 2147483647) || 1;
  return () => {
    s = (s * 16807) % 2147483647;
    return (s - 1) / 2147483646;
  };
}

export class SkierSimulation {
  private skiers: SkierAgent[] = [];
  private chairs: CarrierAgent[] = [];
  private cabins: CarrierAgent[] = [];
  private surfaces: CarrierAgent[] = [];
  private snowcats: SnowcatAgent[] = [];
  private flakes: Particle[] = [];

  skierPos = new Float32Array(0);
  skierYawScale = new Float32Array(0);
  skierColor = new Float32Array(0);
  skierCount = 0;

  chairPos = new Float32Array(0);
  chairYawScale = new Float32Array(0);
  chairColor = new Float32Array(0);
  chairCount = 0;

  cabinPos = new Float32Array(0);
  cabinYawScale = new Float32Array(0);
  cabinColor = new Float32Array(0);
  cabinCount = 0;

  surfacePos = new Float32Array(0);
  surfaceYawScale = new Float32Array(0);
  surfaceColor = new Float32Array(0);
  surfaceCount = 0;

  snowcatPos = new Float32Array(0);
  snowcatYawScale = new Float32Array(0);
  snowcatColor = new Float32Array(0);
  snowcatCount = 0;

  flakePos = new Float32Array(0);
  flakeYawScale = new Float32Array(0);
  flakeColor = new Float32Array(0);
  flakeCount = 0;

  private hf: Heightfield;
  private mapConfig: MapConfig;

  constructor(
    hf: Heightfield,
    mapConfig: MapConfig,
    segments: MapSegment[],
    builtIds: (string | number)[],
    builtItems: Record<string, BuiltItem>,
    facilities: PlacedFacility[],
    liveSkiers: LiveSkiersSnapshot | null,
    groomerActive: number,
    weather: MapWeather | null,
    showSkiers: boolean
  ) {
    this.hf = hf;
    this.mapConfig = mapConfig;
    this.rebuild(segments, builtIds, builtItems, facilities, liveSkiers, groomerActive, weather, showSkiers);
  }

  rebuild(
    segments: MapSegment[],
    builtIds: (string | number)[],
    builtItems: Record<string, BuiltItem>,
    facilities: PlacedFacility[],
    liveSkiers: LiveSkiersSnapshot | null,
    groomerActive: number,
    weather: MapWeather | null,
    showSkiers: boolean
  ) {
    this.skiers = [];
    this.chairs = [];
    this.cabins = [];
    this.surfaces = [];
    this.snowcats = [];
    this.flakes = [];

    const rand = deterministicRand(1337 + builtIds.length * 31);
    const isBuilt = (id: string | number) => builtIds.some((b) => sameId(b, id));
    const perSeg = liveSkiers?.perSegment ?? {};
    const resortOpen = liveSkiers ? liveSkiers.resortOpen : true;

    const openSlopes: { seg: MapSegment; item: BuiltItem; pts: [number, number][] }[] = [];
    const openLifts: { seg: MapSegment; item: BuiltItem; pts: [number, number][] }[] = [];

    for (const seg of segments) {
      if (!isBuilt(seg.id)) continue;
      const item = builtItems[String(seg.id)];
      if (!item || item.status !== 'open') continue;
      const rawPts = parsePoints(seg.points);
      if (rawPts.length < 2) continue;
      if (seg.type === 'lift') {
        const pts = rawPts[0][0] <= rawPts[rawPts.length - 1][0] ? rawPts : [...rawPts].reverse();
        openLifts.push({ seg, item, pts });
      } else {
        const oriented = rawPts[0][0] >= rawPts[rawPts.length - 1][0] ? rawPts : [...rawPts].reverse();
        const pts = smoothTrailPolyline(oriented, false, 2.2);
        openSlopes.push({ seg, item, pts });
      }
    }

    // Lift carriers split accurately by lift subtype
    for (const { seg, item, pts } of openLifts) {
      const sub = item.subtype || 'chair_fixed';
      const seats = Number(item.seats || 2);
      if (sub === 'button') {
        const n = 6;
        for (let k = 0; k < n; k++) {
          this.surfaces.push({
            pts,
            progress: k / n,
            speed: 0.048,
            yOffset: 0.34,
            scale: 0.95,
            color: [0.95, 0.64, 0.14],
          });
        }
      } else if (sub === 'gondola' || sub === 'cable_car') {
        const isTram = sub === 'cable_car';
        const n = isTram ? 2 : 5;
        const scale = isTram ? 1.38 : 0.98;
        const cableH = isTram ? 2.15 : 1.65;
        for (let k = 0; k < n; k++) {
          this.cabins.push({
            pts,
            progress: k / n,
            speed: 0.08,
            yOffset: cableH - 0.45 * scale,
            scale,
            color: [0.84, 0.24, 0.22],
          });
        }
      } else {
        // chair_fixed or chair_detach: open bench chairs hung directly below the cable
        const isDetach = sub === 'chair_detach';
        const cableH = isDetach ? 1.55 : 1.45;
        const scale = seats >= 6 ? 1.12 : seats >= 4 ? 1.0 : 0.88;
        const col: [number, number, number] = isDetach ? [0.16, 0.34, 0.58] : [0.24, 0.30, 0.40];
        const n = 6;
        for (let k = 0; k < n; k++) {
          this.chairs.push({
            pts,
            progress: k / n,
            speed: isDetach ? 0.08 : 0.052,
            yOffset: cableH - 0.42 * scale,
            scale,
            color: col,
          });
        }
      }

      // Queue skiers neatly lined near lift base
      if (showSkiers && resortOpen) {
        const queueN = Math.min(14, perSeg[String(seg.id)]?.queue ?? 0);
        const base = pts[0];
        for (let q = 0; q < queueN; q++) {
          this.skiers.push({
            kind: 'queue',
            progress: q,
            speed: 0,
            carvePhase: rand() * Math.PI * 2,
            carveAmp: 0,
            baseY: base[0] - 0.85 - (q % 4) * 0.45 + (rand() - 0.5) * 0.2,
            baseX: base[1] + (Math.floor(q / 4) - 1.0) * 0.45 + (rand() - 0.5) * 0.2,
            color: JACKET_COLORS[Math.floor(rand() * JACKET_COLORS.length)],
          });
        }
      }
    }

    if (showSkiers && resortOpen && (liveSkiers?.liveTotal ?? 0) > 0) {
      const totalSlopeTarget = Math.min(360, Math.max(openSlopes.length > 0 ? 10 : 0, Math.round((liveSkiers?.onSlopes ?? 0) * 0.6)));
      const slopeWeights = openSlopes.map(({ seg }) => Math.max(1, perSeg[String(seg.id)]?.skiers ?? 4));
      const sumW = slopeWeights.reduce((a, b) => a + b, 0) || 1;

      openSlopes.forEach(({ item, pts }, idx) => {
        const count = Math.max(1, Math.round((slopeWeights[idx] / sumW) * totalSlopeTarget));
        const qualityMult = item.snow_quality === 'powder' || item.snow_quality === 'groomed' ? 1.15 : item.snow_quality === 'bare' ? 0.72 : 1.0;
        const baseSpd = (item.difficulty === 'double_black' ? 0.14 : item.difficulty === 'black' ? 0.12 : 0.095) * qualityMult;

        for (let k = 0; k < count; k++) {
          this.skiers.push({
            kind: 'slope',
            pts,
            progress: rand(),
            speed: baseSpd * (0.8 + rand() * 0.45),
            carvePhase: rand() * Math.PI * 2,
            carveAmp: item.difficulty === 'green' ? 0.32 : 0.46,
            baseX: 0,
            baseY: 0,
            color: JACKET_COLORS[Math.floor(rand() * JACKET_COLORS.length)],
          });
        }
      });

      const openFacs = facilities.filter((f) => f.kind !== 'terrain_park' && f.status === 'open');
      const villageCount = Math.min(50, Math.round((liveSkiers?.inVillage ?? 0) * 0.35));
      if (openFacs.length > 0) {
        for (let v = 0; v < villageCount; v++) {
          const anchor = openFacs[v % openFacs.length];
          const angle = rand() * Math.PI * 2;
          this.skiers.push({
            kind: 'village',
            progress: rand() * Math.PI * 2,
            speed: 0.45 + rand() * 0.5,
            carvePhase: angle,
            carveAmp: 0.9 + rand() * 0.9,
            baseX: anchor.mapX,
            baseY: anchor.mapY,
            color: JACKET_COLORS[Math.floor(rand() * JACKET_COLORS.length)],
          });
        }
      }
    }

    if (groomerActive > 0 && openSlopes.length > 0) {
      const catCount = Math.min(5, groomerActive, openSlopes.length);
      for (let g = 0; g < catCount; g++) {
        this.snowcats.push({
          pts: openSlopes[(g * 3) % openSlopes.length].pts,
          progress: rand(),
          dir: g % 2 === 0 ? 1 : -1,
          speed: 0.032,
        });
      }
    }

    const cond = (weather?.condition_name || '').toLowerCase();
    const isSnowing = (weather?.snowfall ?? 0) > 0 || cond.includes('snow') || cond.includes('blizzard');
    const flakeTarget = isSnowing ? (cond.includes('blizzard') ? 220 : 120) : 0;
    const W = this.mapConfig.width || 600;
    const H = this.mapConfig.height || 340;
    for (let i = 0; i < flakeTarget; i++) {
      this.flakes.push({
        x: rand() * W,
        y: 8 + rand() * 52,
        z: rand() * H,
        vy: 10 + rand() * 12,
        vx: (weather?.wind ?? 10) * 0.3,
        scale: 0.7 + rand() * 0.6,
      });
    }
    for (const { item, pts } of openSlopes) {
      if ((item.snowmakers_active ?? 0) > 0) {
        const mid = samplePolylineAt(pts, 0.5);
        const [wx, wy, wz] = this.hf.toWorld(mid.mapX, mid.mapY, 1.2);
        for (let m = 0; m < 8; m++) {
          this.flakes.push({
            x: wx + (rand() - 0.5) * 3,
            y: wy + rand() * 2,
            z: wz + (rand() - 0.5) * 3,
            vy: 2.2 + rand() * 2.5,
            vx: (rand() - 0.5) * 2.5,
            scale: 0.7 + rand() * 0.5,
          });
        }
      }
    }

    this.skierCount = this.skiers.length;
    this.skierPos = new Float32Array(this.skierCount * 3);
    this.skierYawScale = new Float32Array(this.skierCount * 2);
    this.skierColor = new Float32Array(this.skierCount * 4);

    this.chairCount = this.chairs.length;
    this.chairPos = new Float32Array(this.chairCount * 3);
    this.chairYawScale = new Float32Array(this.chairCount * 2);
    this.chairColor = new Float32Array(this.chairCount * 4);

    this.cabinCount = this.cabins.length;
    this.cabinPos = new Float32Array(this.cabinCount * 3);
    this.cabinYawScale = new Float32Array(this.cabinCount * 2);
    this.cabinColor = new Float32Array(this.cabinCount * 4);

    this.surfaceCount = this.surfaces.length;
    this.surfacePos = new Float32Array(this.surfaceCount * 3);
    this.surfaceYawScale = new Float32Array(this.surfaceCount * 2);
    this.surfaceColor = new Float32Array(this.surfaceCount * 4);

    this.snowcatCount = this.snowcats.length;
    this.snowcatPos = new Float32Array(this.snowcatCount * 3);
    this.snowcatYawScale = new Float32Array(this.snowcatCount * 2);
    this.snowcatColor = new Float32Array(this.snowcatCount * 4);

    this.flakeCount = this.flakes.length;
    this.flakePos = new Float32Array(this.flakeCount * 3);
    this.flakeYawScale = new Float32Array(this.flakeCount * 2);
    this.flakeColor = new Float32Array(this.flakeCount * 4);

    this.step(0);
  }

  private stepCarrierPool(
    pool: CarrierAgent[],
    pos: Float32Array,
    yawScale: Float32Array,
    color: Float32Array,
    stepDt: number
  ) {
    for (let i = 0; i < pool.length; i++) {
      const c = pool[i];
      c.progress = (c.progress + c.speed * stepDt) % 1;
      const goingUp = c.progress < 0.5;
      const t = goingUp ? c.progress * 2 : 1 - (c.progress - 0.5) * 2;
      const sample = samplePolylineAt(c.pts, t);
      const side = goingUp ? 0.24 : -0.24;
      const mx = sample.mapX - sample.ty * side;
      const my = sample.mapY + sample.tx * side;
      const [wx, wy, wz] = this.hf.toWorld(mx, my, c.yOffset);
      const yaw = goingUp ? Math.atan2(sample.tx, -sample.ty) : Math.atan2(-sample.tx, sample.ty);
      pos[i * 3] = wx;
      pos[i * 3 + 1] = wy;
      pos[i * 3 + 2] = wz;
      yawScale[i * 2] = yaw;
      yawScale[i * 2 + 1] = c.scale;
      color[i * 4] = c.color[0];
      color[i * 4 + 1] = c.color[1];
      color[i * 4 + 2] = c.color[2];
      color[i * 4 + 3] = 1;
    }
  }

  step(dt: number) {
    const stepDt = clamp(dt, 0, 0.08);
    const W = this.mapConfig.width || 600;
    const H = this.mapConfig.height || 340;

    for (let i = 0; i < this.skierCount; i++) {
      const s = this.skiers[i];
      let mx = s.baseX;
      let my = s.baseY;
      let yaw = s.carvePhase;
      let scale = 0.60;

      if (s.kind === 'slope' && s.pts) {
        s.progress = (s.progress + s.speed * stepDt) % 1;
        const sample = samplePolylineAt(s.pts, s.progress);
        const carve = Math.sin(s.progress * 30 + s.carvePhase) * s.carveAmp;
        mx = sample.mapX - sample.ty * carve;
        my = sample.mapY + sample.tx * carve;
        const dirX = sample.tx + Math.cos(s.progress * 30 + s.carvePhase) * 0.25;
        const dirZ = -sample.ty;
        yaw = Math.atan2(dirX, dirZ);
        scale = 0.72;
      } else if (s.kind === 'village') {
        s.progress += s.speed * stepDt;
        mx = s.baseX + Math.cos(s.progress + s.carvePhase) * s.carveAmp;
        my = s.baseY + Math.sin(s.progress * 0.8 + s.carvePhase) * s.carveAmp * 0.7;
        yaw = s.progress;
        scale = 0.58;
      } else {
        yaw = s.carvePhase * 0.2;
        scale = 0.58;
      }

      const [wx, wy, wz] = this.hf.toWorld(mx, my, 0.21);
      this.skierPos[i * 3] = wx;
      this.skierPos[i * 3 + 1] = wy;
      this.skierPos[i * 3 + 2] = wz;
      this.skierYawScale[i * 2] = yaw;
      this.skierYawScale[i * 2 + 1] = scale;
      this.skierColor[i * 4] = s.color[0];
      this.skierColor[i * 4 + 1] = s.color[1];
      this.skierColor[i * 4 + 2] = s.color[2];
      this.skierColor[i * 4 + 3] = 1;
    }

    this.stepCarrierPool(this.chairs, this.chairPos, this.chairYawScale, this.chairColor, stepDt);
    this.stepCarrierPool(this.cabins, this.cabinPos, this.cabinYawScale, this.cabinColor, stepDt);
    this.stepCarrierPool(this.surfaces, this.surfacePos, this.surfaceYawScale, this.surfaceColor, stepDt);

    for (let i = 0; i < this.snowcatCount; i++) {
      const g = this.snowcats[i];
      g.progress += g.dir * g.speed * stepDt;
      if (g.progress >= 1) {
        g.progress = 1;
        g.dir = -1;
      } else if (g.progress <= 0) {
        g.progress = 0;
        g.dir = 1;
      }
      const sample = samplePolylineAt(g.pts, g.progress);
      const [wx, wy, wz] = this.hf.toWorld(sample.mapX, sample.mapY, 0.20);
      const yaw = Math.atan2(sample.tx * g.dir, -sample.ty * g.dir);
      this.snowcatPos[i * 3] = wx;
      this.snowcatPos[i * 3 + 1] = wy;
      this.snowcatPos[i * 3 + 2] = wz;
      this.snowcatYawScale[i * 2] = yaw;
      this.snowcatYawScale[i * 2 + 1] = 0.85;
      this.snowcatColor[i * 4] = 1;
      this.snowcatColor[i * 4 + 1] = 1;
      this.snowcatColor[i * 4 + 2] = 1;
      this.snowcatColor[i * 4 + 3] = 0;
    }

    for (let i = 0; i < this.flakeCount; i++) {
      const f = this.flakes[i];
      f.y -= f.vy * stepDt;
      f.x += f.vx * stepDt;
      if (f.y < 1) f.y = 55;
      if (f.x > W) f.x -= W;
      if (f.x < 0) f.x += W;
      this.flakePos[i * 3] = f.x;
      this.flakePos[i * 3 + 1] = f.y;
      this.flakePos[i * 3 + 2] = clamp(f.z, 0, H);
      this.flakeYawScale[i * 2] = f.y * 0.1;
      this.flakeYawScale[i * 2 + 1] = f.scale;
      this.flakeColor[i * 4] = 1;
      this.flakeColor[i * 4 + 1] = 1;
      this.flakeColor[i * 4 + 2] = 1;
      this.flakeColor[i * 4 + 3] = 0;
    }
  }
}
