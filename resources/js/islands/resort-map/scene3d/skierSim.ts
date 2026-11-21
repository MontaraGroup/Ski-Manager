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
import { buildLiftProfile, sampleLiftCable, type LiftProfile } from './geometry';
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
  profile: LiftProfile;
  progress: number;
  speed: number;
  scale: number;
  seats: number;
  color: [number, number, number];
  mode: 'loop' | 'jigback';
  sideSign: number;
  riderCount: number;
  riderColors: [number, number, number][];
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
  private bubbleChairs: CarrierAgent[] = [];
  private cabins: CarrierAgent[] = [];
  private trams: CarrierAgent[] = [];
  private surfaces: CarrierAgent[] = [];
  private snowcats: SnowcatAgent[] = [];
  private flakes: Particle[] = [];
  private riderMax = 0;

  skierPos = new Float32Array(0);
  skierYawScale = new Float32Array(0);
  skierColor = new Float32Array(0);
  skierCount = 0;

  chairPos = new Float32Array(0);
  chairYawScale = new Float32Array(0);
  chairColor = new Float32Array(0);
  chairCount = 0;

  bubbleChairPos = new Float32Array(0);
  bubbleChairYawScale = new Float32Array(0);
  bubbleChairColor = new Float32Array(0);
  bubbleChairCount = 0;

  cabinPos = new Float32Array(0);
  cabinYawScale = new Float32Array(0);
  cabinColor = new Float32Array(0);
  cabinCount = 0;

  tramPos = new Float32Array(0);
  tramYawScale = new Float32Array(0);
  tramColor = new Float32Array(0);
  tramCount = 0;

  surfacePos = new Float32Array(0);
  surfaceYawScale = new Float32Array(0);
  surfaceColor = new Float32Array(0);
  surfaceCount = 0;

  riderPos = new Float32Array(0);
  riderYawScale = new Float32Array(0);
  riderColor = new Float32Array(0);
  riderCount = 0;

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
    this.bubbleChairs = [];
    this.cabins = [];
    this.trams = [];
    this.surfaces = [];
    this.snowcats = [];
    this.flakes = [];
    this.riderMax = 0;

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

    const pickRiderColors = (cnt: number): [number, number, number][] => {
      const cols: [number, number, number][] = [];
      for (let r = 0; r < cnt; r++) {
        cols.push(JACKET_COLORS[Math.floor(rand() * JACKET_COLORS.length)]);
      }
      return cols;
    };

    // Lift carriers locked to shared catenary cables and split accurately by lift subtype
    for (const { seg, item, pts } of openLifts) {
      const sub = item.subtype || 'chair_fixed';
      const seats = Number(item.seats || 2);
      const profile = buildLiftProfile(this.hf, pts, sub);
      const hasRiders = showSkiers && resortOpen;

      if (sub === 'button') {
        const n = clamp(Math.round(profile.totalLen / 11) * 2, 4, 14);
        const scale = seats >= 2 ? 1.05 : 0.90;
        for (let k = 0; k < n; k++) {
          this.surfaces.push({
            profile,
            progress: k / n,
            speed: 0.048,
            scale,
            seats,
            color: [0.95, 0.64, 0.14],
            mode: 'loop',
            sideSign: 1,
            riderCount: 0,
            riderColors: [],
          });
        }
      } else if (sub === 'cable_car') {
        const scale = seats >= 30 ? 1.14 : 1.0;
        for (const [k, sideSign] of [
          [0, 1],
          [0.5, -1],
        ] as [number, number][]) {
          this.trams.push({
            profile,
            progress: k,
            speed: 0.055,
            scale,
            seats,
            color: [0.82, 0.18, 0.20],
            mode: 'jigback',
            sideSign,
            riderCount: 0,
            riderColors: [],
          });
        }
      } else if (sub === 'gondola') {
        const n = clamp(Math.round(profile.totalLen / 14) * 2, 4, 14);
        const scale = seats >= 10 ? 1.10 : seats >= 8 ? 1.02 : 0.94;
        for (let k = 0; k < n; k++) {
          this.cabins.push({
            profile,
            progress: k / n,
            speed: 0.084,
            scale,
            seats,
            color: [0.84, 0.22, 0.20],
            mode: 'loop',
            sideSign: 1,
            riderCount: 0,
            riderColors: [],
          });
        }
      } else if (sub === 'chair_detach') {
        const n = clamp(Math.round(profile.totalLen / 12) * 2, 4, 16);
        const scale = seats >= 8 ? 1.12 : seats >= 6 ? 1.02 : 0.92;
        const rc = hasRiders ? (seats >= 6 ? 3 : seats >= 4 ? 2 : 1) : 0;
        for (let k = 0; k < n; k++) {
          this.bubbleChairs.push({
            profile,
            progress: k / n,
            speed: 0.082,
            scale,
            seats,
            color: [0.16, 0.34, 0.58],
            mode: 'loop',
            sideSign: 1,
            riderCount: rc,
            riderColors: rc > 0 ? pickRiderColors(rc) : [],
          });
          this.riderMax += rc;
        }
      } else {
        const n = clamp(Math.round(profile.totalLen / 11) * 2, 4, 16);
        const scale = seats >= 4 ? 1.02 : seats === 3 ? 0.92 : 0.82;
        const rc = hasRiders ? (seats >= 4 ? 2 : 1) : 0;
        for (let k = 0; k < n; k++) {
          this.chairs.push({
            profile,
            progress: k / n,
            speed: 0.052,
            scale,
            seats,
            color: [0.24, 0.30, 0.40],
            mode: 'loop',
            sideSign: 1,
            riderCount: rc,
            riderColors: rc > 0 ? pickRiderColors(rc) : [],
          });
          this.riderMax += rc;
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

    this.bubbleChairCount = this.bubbleChairs.length;
    this.bubbleChairPos = new Float32Array(this.bubbleChairCount * 3);
    this.bubbleChairYawScale = new Float32Array(this.bubbleChairCount * 2);
    this.bubbleChairColor = new Float32Array(this.bubbleChairCount * 4);

    this.cabinCount = this.cabins.length;
    this.cabinPos = new Float32Array(this.cabinCount * 3);
    this.cabinYawScale = new Float32Array(this.cabinCount * 2);
    this.cabinColor = new Float32Array(this.cabinCount * 4);

    this.tramCount = this.trams.length;
    this.tramPos = new Float32Array(this.tramCount * 3);
    this.tramYawScale = new Float32Array(this.tramCount * 2);
    this.tramColor = new Float32Array(this.tramCount * 4);

    this.surfaceCount = this.surfaces.length;
    this.surfacePos = new Float32Array(this.surfaceCount * 3);
    this.surfaceYawScale = new Float32Array(this.surfaceCount * 2);
    this.surfaceColor = new Float32Array(this.surfaceCount * 4);

    this.riderPos = new Float32Array(this.riderMax * 3);
    this.riderYawScale = new Float32Array(this.riderMax * 2);
    this.riderColor = new Float32Array(this.riderMax * 4);
    this.riderCount = 0;

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
    stepDt: number,
    riderWriteStart: number
  ): number {
    let rWrite = riderWriteStart;
    const turnFrac = 0.04;
    const dwell = 0.08;

    for (let i = 0; i < pool.length; i++) {
      const c = pool[i];
      c.progress = (c.progress + c.speed * stepDt) % 1;
      const pr = c.profile;

      let wx = 0;
      let wy = 0;
      let wz = 0;
      let yaw = 0;
      let goingUp = false;

      if (c.mode === 'jigback') {
        let t = 0;
        if (c.progress < dwell) {
          t = 0;
        } else if (c.progress < 0.5) {
          const p = (c.progress - dwell) / (0.5 - dwell);
          t = 0.5 - 0.5 * Math.cos(p * Math.PI);
        } else if (c.progress < 0.5 + dwell) {
          t = 1;
        } else {
          const p = (c.progress - (0.5 + dwell)) / (0.5 - dwell);
          t = 0.5 + 0.5 * Math.cos(p * Math.PI);
        }
        const smp = sampleLiftCable(pr, t, c.sideSign);
        wx = smp.wx;
        wy = smp.wy;
        wz = smp.wz;
        yaw = Math.atan2(smp.tx, -smp.ty);
      } else if (c.progress < 0.5 - turnFrac) {
        goingUp = true;
        const t = c.progress / (0.5 - turnFrac);
        const smp = sampleLiftCable(pr, t, 1);
        wx = smp.wx;
        wy = smp.wy;
        wz = smp.wz;
        yaw = Math.atan2(smp.tx, -smp.ty);
      } else if (c.progress < 0.5) {
        const theta = ((c.progress - (0.5 - turnFrac)) / turnFrac) * Math.PI;
        const top = pr.nodes[pr.spans];
        const prev = pr.nodes[pr.spans - 1];
        const dx = top.mapX - prev.mapX;
        const dy = top.mapY - prev.mapY;
        const dLen = Math.hypot(dx, dy) || 1;
        const tx = dx / dLen;
        const ty = dy / dLen;
        const cosT = Math.cos(theta);
        const sinT = Math.sin(theta);
        const rx = -ty * pr.cableOff;
        const rz = -tx * pr.cableOff;
        const fx = tx * pr.cableOff;
        const fz = -ty * pr.cableOff;
        wx = top.mapX + rx * cosT + fx * sinT;
        wy = top.topY;
        wz = pr.height - top.mapY + rz * cosT + fz * sinT;
        yaw = Math.atan2(fx * cosT - rx * sinT, fz * cosT - rz * sinT);
      } else if (c.progress < 1.0 - turnFrac) {
        const t = 1 - (c.progress - 0.5) / (0.5 - turnFrac);
        const smp = sampleLiftCable(pr, t, -1);
        wx = smp.wx;
        wy = smp.wy;
        wz = smp.wz;
        yaw = Math.atan2(-smp.tx, smp.ty);
      } else {
        const theta = ((c.progress - (1.0 - turnFrac)) / turnFrac) * Math.PI;
        const base = pr.nodes[0];
        const next = pr.nodes[1];
        const dx = next.mapX - base.mapX;
        const dy = next.mapY - base.mapY;
        const dLen = Math.hypot(dx, dy) || 1;
        const tx = dx / dLen;
        const ty = dy / dLen;
        const cosT = Math.cos(theta);
        const sinT = Math.sin(theta);
        const rx = -ty * pr.cableOff;
        const rz = -tx * pr.cableOff;
        const fx = tx * pr.cableOff;
        const fz = -ty * pr.cableOff;
        wx = base.mapX - rx * cosT - fx * sinT;
        wy = base.topY;
        wz = pr.height - base.mapY - rz * cosT - fz * sinT;
        yaw = Math.atan2(rx * sinT - fx * cosT, rz * sinT - fz * cosT);
      }

      pos[i * 3] = wx;
      pos[i * 3 + 1] = wy;
      pos[i * 3 + 2] = wz;
      yawScale[i * 2] = yaw;
      yawScale[i * 2 + 1] = c.scale;
      color[i * 4] = c.color[0];
      color[i * 4 + 1] = c.color[1];
      color[i * 4 + 2] = c.color[2];
      color[i * 4 + 3] = 1;

      if (goingUp && c.riderCount > 0) {
        const latX = Math.cos(yaw);
        const latZ = -Math.sin(yaw);
        const fwdX = Math.sin(yaw) * 0.02 * c.scale;
        const fwdZ = Math.cos(yaw) * 0.02 * c.scale;
        const rScale = 0.70 * c.scale;
        const rY = wy - 0.43 * c.scale;
        for (let r = 0; r < c.riderCount; r++) {
          if (rWrite >= this.riderMax) break;
          const off = c.riderCount === 1 ? 0 : c.riderCount === 2 ? (r === 0 ? -0.11 : 0.11) * c.scale : (r - 1) * 0.16 * c.scale;
          const rc = c.riderColors[r];
          this.riderPos[rWrite * 3] = wx + latX * off + fwdX;
          this.riderPos[rWrite * 3 + 1] = rY;
          this.riderPos[rWrite * 3 + 2] = wz + latZ * off + fwdZ;
          this.riderYawScale[rWrite * 2] = yaw;
          this.riderYawScale[rWrite * 2 + 1] = rScale;
          this.riderColor[rWrite * 4] = rc[0];
          this.riderColor[rWrite * 4 + 1] = rc[1];
          this.riderColor[rWrite * 4 + 2] = rc[2];
          this.riderColor[rWrite * 4 + 3] = 1;
          rWrite++;
        }
      }
    }
    return rWrite;
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

    let rCount = this.stepCarrierPool(this.chairs, this.chairPos, this.chairYawScale, this.chairColor, stepDt, 0);
    rCount = this.stepCarrierPool(this.bubbleChairs, this.bubbleChairPos, this.bubbleChairYawScale, this.bubbleChairColor, stepDt, rCount);
    this.stepCarrierPool(this.cabins, this.cabinPos, this.cabinYawScale, this.cabinColor, stepDt, rCount);
    this.stepCarrierPool(this.trams, this.tramPos, this.tramYawScale, this.tramColor, stepDt, rCount);
    this.stepCarrierPool(this.surfaces, this.surfacePos, this.surfaceYawScale, this.surfaceColor, stepDt, rCount);
    this.riderCount = rCount;

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
