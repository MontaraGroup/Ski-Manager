import { parsePoints, sameId } from '../shared';
import type {
  BuiltItem,
  LiveSkiersSnapshot,
  MapLens,
  MapLighting,
  MapSegment,
  PlacedFacility,
} from '../types';
import type { Heightfield } from './heightfield';
import { clamp, lerp, normalize3, samplePolylineAt } from './math';

export interface StaticMeshData {
  vertices: Float32Array; // stride 10: pos(3), normal(3), color(4) [rgb + emissive]
  indices: Uint32Array;
}

export interface UnitMeshData {
  vertices: Float32Array; // stride 9: pos(3), normal(3), baseColor(3)
  indices: Uint16Array;
}

type RGBA = [number, number, number, number];

class MeshBuilder {
  v: number[] = [];
  i: number[] = [];

  tri(
    ax: number, ay: number, az: number,
    bx: number, by: number, bz: number,
    cx: number, cy: number, cz: number,
    col: RGBA
  ) {
    const ux = bx - ax, uy = by - ay, uz = bz - az;
    const vx = cx - ax, vy = cy - ay, vz = cz - az;
    const [nx, ny, nz] = normalize3(uy * vz - uz * vy, uz * vx - ux * vz, ux * vy - uy * vx);
    const base = this.v.length / 10;
    this.v.push(
      ax, ay, az, nx, ny, nz, col[0], col[1], col[2], col[3],
      bx, by, bz, nx, ny, nz, col[0], col[1], col[2], col[3],
      cx, cy, cz, nx, ny, nz, col[0], col[1], col[2], col[3]
    );
    this.i.push(base, base + 1, base + 2);
  }

  quad(
    ax: number, ay: number, az: number,
    bx: number, by: number, bz: number,
    cx: number, cy: number, cz: number,
    dx: number, dy: number, dz: number,
    col: RGBA
  ) {
    this.tri(ax, ay, az, bx, by, bz, cx, cy, cz, col);
    this.tri(ax, ay, az, cx, cy, cz, dx, dy, dz, col);
  }

  box(cx: number, by: number, cz: number, sx: number, sy: number, sz: number, col: RGBA) {
    const x0 = cx - sx * 0.5, x1 = cx + sx * 0.5;
    const y0 = by, y1 = by + sy;
    const z0 = cz - sz * 0.5, z1 = cz + sz * 0.5;
    // Front (+Z)
    this.quad(x0, y0, z1, x1, y0, z1, x1, y1, z1, x0, y1, z1, col);
    // Back (-Z)
    this.quad(x1, y0, z0, x0, y0, z0, x0, y1, z0, x1, y1, z0, col);
    // Right (+X)
    this.quad(x1, y0, z1, x1, y0, z0, x1, y1, z0, x1, y1, z1, col);
    // Left (-X)
    this.quad(x0, y0, z0, x0, y0, z1, x0, y1, z1, x0, y1, z0, col);
    // Top (+Y)
    this.quad(x0, y1, z1, x1, y1, z1, x1, y1, z0, x0, y1, z0, col);
  }

  gableRoof(cx: number, by: number, cz: number, sx: number, sy: number, sz: number, col: RGBA) {
    const x0 = cx - sx * 0.5, x1 = cx + sx * 0.5;
    const y0 = by, y1 = by + sy;
    const z0 = cz - sz * 0.5, z1 = cz + sz * 0.5;
    // Sloped front (+Z) & back (-Z) meeting along X ridge
    this.quad(x0, y0, z1, x1, y0, z1, x1, y1, cz, x0, y1, cz, col);
    this.quad(x1, y0, z0, x0, y0, z0, x0, y1, cz, x1, y1, cz, col);
    // Gable end triangles (+X, -X)
    this.tri(x1, y0, z1, x1, y0, z0, x1, y1, cz, col);
    this.tri(x0, y0, z0, x0, y0, z1, x0, y1, cz, col);
  }

  /** Thin beam / cable connecting two 3D points */
  beam(ax: number, ay: number, az: number, bx: number, by: number, bz: number, r: number, col: RGBA) {
    const dx = bx - ax, dy = by - ay, dz = bz - az;
    const len = Math.hypot(dx, dy, dz);
    if (len < 0.05) return;
    // Perpendicular basis
    const ux = dx / len, uy = dy / len, uz = dz / len;
    const refX = Math.abs(uy) < 0.9 ? 0 : 1;
    const refY = Math.abs(uy) < 0.9 ? 1 : 0;
    const [px, py, pz] = normalize3(uy * 0 - uz * refY, uz * refX - ux * 0, ux * refY - uy * refX);
    const [qx, qy, qz] = normalize3(py * uz - pz * uy, pz * ux - px * uz, px * uy - py * ux);

    const a0x = ax + (-px - qx) * r, a0y = ay + (-py - qy) * r, a0z = az + (-pz - qz) * r;
    const a1x = ax + (px - qx) * r, a1y = ay + (py - qy) * r, a1z = az + (pz - qz) * r;
    const a2x = ax + (px + qx) * r, a2y = ay + (py + qy) * r, a2z = az + (pz + qz) * r;
    const a3x = ax + (-px + qx) * r, a3y = ay + (-py + qy) * r, a3z = az + (-pz + qz) * r;

    const b0x = bx + (-px - qx) * r, b0y = by + (-py - qy) * r, b0z = bz + (-pz - qz) * r;
    const b1x = bx + (px - qx) * r, b1y = by + (py - qy) * r, b1z = bz + (pz - qz) * r;
    const b2x = bx + (px + qx) * r, b2y = by + (py + qy) * r, b2z = bz + (pz + qz) * r;
    const b3x = bx + (-px + qx) * r, b3y = by + (-py + qy) * r, b3z = bz + (-pz + qz) * r;

    this.quad(a0x, a0y, a0z, b0x, b0y, b0z, b1x, b1y, b1z, a1x, a1y, a1z, col);
    this.quad(a1x, a1y, a1z, b1x, b1y, b1z, b2x, b2y, b2z, a2x, a2y, a2z, col);
    this.quad(a2x, a2y, a2z, b2x, b2y, b2z, b3x, b3y, b3z, a3x, a3y, a3z, col);
    this.quad(a3x, a3y, a3z, b3x, b3y, b3z, b0x, b0y, b0z, a0x, a0y, a0z, col);
  }

  build(): StaticMeshData {
    return {
      vertices: new Float32Array(this.v),
      indices: new Uint32Array(this.i),
    };
  }
}

export function buildTerrainMesh(hf: Heightfield): { vertices: Float32Array; indices: Uint32Array } {
  const { cols, rows, width, height } = hf;
  const verts = new Float32Array(cols * rows * 9);
  let p = 0;
  for (let r = 0; r < rows; r++) {
    const my = (r / (rows - 1)) * height;
    const wz = height - my;
    const v = wz / height;
    for (let c = 0; c < cols; c++) {
      const mx = (c / (cols - 1)) * width;
      const wy = hf.sampleHeight(mx, my);
      const [nx, ny, nz] = hf.sampleNormal(mx, my);
      const slopeDeg = hf.sampleSlopeDeg(mx, my);
      verts[p++] = mx;
      verts[p++] = wy;
      verts[p++] = wz;
      verts[p++] = nx;
      verts[p++] = ny;
      verts[p++] = nz;
      verts[p++] = mx / width;
      verts[p++] = v;
      verts[p++] = slopeDeg;
    }
  }
  const indices = new Uint32Array((cols - 1) * (rows - 1) * 6);
  let q = 0;
  for (let r = 0; r < rows - 1; r++) {
    for (let c = 0; c < cols - 1; c++) {
      const a = r * cols + c;         // bottom-left (higher worldZ)
      const b = r * cols + c + 1;     // bottom-right
      const d = (r + 1) * cols + c;   // top-left (lower worldZ)
      const e = (r + 1) * cols + c + 1; // top-right
      // CCW upward (+Y) winding
      indices[q++] = d;
      indices[q++] = a;
      indices[q++] = b;
      indices[q++] = d;
      indices[q++] = b;
      indices[q++] = e;
    }
  }
  return { vertices: verts, indices };
}

function trailColor(seg: MapSegment): [number, number, number] {
  if (seg.type === 'lift') return [0.96, 0.62, 0.04];
  switch (seg.difficulty || seg.type) {
    case 'green':
      return [0.16, 0.78, 0.4];
    case 'blue':
      return [0.22, 0.52, 0.96];
    case 'black':
      return [0.14, 0.19, 0.28];
    case 'double_black':
      return [0.86, 0.22, 0.22];
    case 'snowpark':
    case 'terrain_park':
      return [0.66, 0.34, 0.94];
    case 'crosscountry':
      return [0.08, 0.72, 0.65];
    default:
      return [0.58, 0.64, 0.72];
  }
}

function heatLoadColor(loadPct: number): [number, number, number] {
  const t = clamp(loadPct / 110, 0, 1);
  if (t < 0.5) {
    const k = t * 2;
    return [lerp(0.15, 0.95, k), lerp(0.75, 0.78, k), lerp(0.9, 0.18, k)];
  }
  const k = (t - 0.5) * 2;
  return [lerp(0.95, 0.92, k), lerp(0.78, 0.2, k), lerp(0.18, 0.2, k)];
}

function snowColor(quality?: string): [number, number, number] {
  switch (quality) {
    case 'powder':
      return [0.58, 0.9, 1.0];
    case 'groomed':
      return [0.24, 0.85, 0.58];
    case 'packed':
      return [0.38, 0.68, 0.95];
    case 'icy':
      return [0.95, 0.65, 0.22];
    case 'bare':
      return [0.76, 0.32, 0.24];
    default:
      return [0.58, 0.64, 0.72];
  }
}

function steepColor(deg: number): [number, number, number] {
  if (deg < 14) return [0.18, 0.78, 0.42];
  if (deg < 24) return [0.22, 0.52, 0.96];
  if (deg < 34) return [0.95, 0.62, 0.14];
  return [0.88, 0.22, 0.24];
}

export function buildOverlayStaticMesh(
  hf: Heightfield,
  segments: MapSegment[],
  builtIds: (string | number)[],
  builtItems: Record<string, BuiltItem>,
  facilities: PlacedFacility[],
  liveSkiers: LiveSkiersSnapshot | null,
  options: {
    filter: string;
    showAvailable: boolean;
    showBuildings: boolean;
    selectedSegId: string | number | null;
    selectedFacilityKey: string | null;
    lens: MapLens;
    lighting: MapLighting;
  }
): StaticMeshData {
  const mb = new MeshBuilder();
  const isBuilt = (id: string | number) => builtIds.some((b) => sameId(b, id));
  const perSeg = liveSkiers?.perSegment ?? {};
  const isNight = options.lighting === 'night' || options.lighting === 'sunset';

  // 1. Slopes, Lifts, Pylons, Snowmakers & Night Lights
  for (const seg of segments) {
    const built = isBuilt(seg.id);
    if (options.filter === 'lift' && seg.type !== 'lift') continue;
    if (options.filter === 'slope' && seg.type === 'lift') continue;
    if (options.filter === 'built' && !built) continue;
    if (options.filter === 'buildings') continue;
    if (!built && !options.showAvailable) continue;

    const pts = parsePoints(seg.points);
    if (pts.length < 2) continue;

    const item = builtItems[String(seg.id)];
    const selected = options.selectedSegId !== null && sameId(options.selectedSegId, seg.id);
    const closed = built && item && item.status !== 'open';

    let rgb = trailColor(seg);
    if (options.lens === 'traffic' && built) {
      rgb = heatLoadColor(perSeg[String(seg.id)]?.loadPct ?? 0);
    } else if (options.lens === 'snow' && built && seg.type !== 'lift') {
      rgb = snowColor(item?.snow_quality);
    }

    if (closed) {
      rgb = [rgb[0] * 0.45, rgb[1] * 0.45, rgb[2] * 0.45];
    }

    if (seg.type !== 'lift') {
      // Terrain-following ribbon
      const halfW = selected ? 1.7 : built ? 1.15 : 0.65;
      const yLift = selected ? 0.65 : built ? 0.45 : 0.3;
      const emissive = selected ? 0.45 : built ? 0.14 : 0.05;

      // Subdivide long segments slightly so ribbon conforms cleanly to curved slopes
      const dense: [number, number][] = [];
      for (let i = 0; i < pts.length; i++) {
        if (i > 0) {
          const dist = Math.hypot(pts[i][0] - pts[i - 1][0], pts[i][1] - pts[i - 1][1]);
          const sub = Math.min(8, Math.max(1, Math.floor(dist / 6)));
          for (let s = 1; s < sub; s++) {
            const t = s / sub;
            dense.push([lerp(pts[i - 1][0], pts[i][0], t), lerp(pts[i - 1][1], pts[i][1], t)]);
          }
        }
        dense.push(pts[i]);
      }

      for (let i = 0; i < dense.length - 1; i++) {
        // Dashed appearance for unbuilt runs
        if (!built && !selected && i % 2 === 1) continue;

        const [ay, ax] = dense[i];
        const [by, bx] = dense[i + 1];
        const dx = bx - ax;
        const dy = by - ay;
        const dLen = Math.hypot(dx, dy) || 1;
        // Normal perpendicular in map space
        const px = (-dy / dLen) * halfW;
        const py = (dx / dLen) * halfW;

        let segCol = rgb;
        if (options.lens === 'steepness') {
          segCol = steepColor(hf.sampleSlopeDeg((ax + bx) * 0.5, (ay + by) * 0.5));
        }
        const col: RGBA = [segCol[0], segCol[1], segCol[2], emissive];

        const [ax0, ay0, az0] = hf.toWorld(ax - px, ay - py, yLift);
        const [ax1, ay1, az1] = hf.toWorld(ax + px, ay + py, yLift);
        const [bx0, by0, bz0] = hf.toWorld(bx - px, by - py, yLift);
        const [bx1, by1, bz1] = hf.toWorld(bx + px, by + py, yLift);
        mb.quad(ax0, ay0, az0, bx0, by0, bz0, bx1, by1, bz1, ax1, ay1, az1, col);

        if (selected) {
          const hw = halfW + 0.7;
          const hpx = (-dy / dLen) * hw;
          const hpy = (dx / dLen) * hw;
          const [hx0, hy0, hz0] = hf.toWorld(ax - hpx, ay - hpy, yLift - 0.1);
          const [hx1, hy1, hz1] = hf.toWorld(ax + hpx, ay + hpy, yLift - 0.1);
          const [kx0, ky0, kz0] = hf.toWorld(bx - hpx, by - hpy, yLift - 0.1);
          const [kx1, ky1, kz1] = hf.toWorld(bx + hpx, by + hpy, yLift - 0.1);
          mb.quad(hx0, hy0, hz0, kx0, ky0, kz0, kx1, ky1, kz1, hx1, hy1, hz1, [1, 1, 1, 0.55]);
        }
      }

      // Snowmaking fan guns & night-skiing light poles along built slopes
      if (built && item) {
        const smCount = Math.min(4, item.snowmakers_count || 0);
        for (let g = 0; g < smCount; g++) {
          const sample = samplePolylineAt(pts, (g + 0.5) / Math.max(1, smCount));
          const [gx, gy, gz] = hf.toWorld(sample.mapX + 1.6, sample.mapY, 0);
          mb.box(gx, gy, gz, 0.9, 1.4, 0.9, [0.95, 0.85, 0.25, 0.1]);
          mb.box(gx, gy + 1.4, gz, 1.1, 0.9, 1.4, [0.22, 0.55, 0.95, item.snowmakers_active ? 0.35 : 0.05]);
        }
        if (item.night_lit && isNight) {
          for (let l = 0; l < 4; l++) {
            const sample = samplePolylineAt(pts, (l + 0.5) / 4);
            const [lx, ly, lz] = hf.toWorld(sample.mapX - 1.5, sample.mapY, 0);
            mb.box(lx, ly, lz, 0.45, 3.2, 0.45, [0.35, 0.42, 0.52, 0.0]);
            mb.box(lx, ly + 3.2, lz, 1.1, 0.6, 1.1, [1.0, 0.88, 0.45, 0.9]);
          }
        }
      }
    } else {
      // Lifts: cables, terminals, pylons
      const cableHeight = built ? 4.4 : 2.2;
      const col: RGBA = selected ? [0.22, 0.74, 0.97, 0.5] : [rgb[0], rgb[1], rgb[2], built ? 0.22 : 0.05];

      if (built || selected) {
        // Base and top stations
        const bPt = pts[0];
        const tPt = pts[pts.length - 1];
        const [bx, by, bz] = hf.toWorld(bPt[1], bPt[0], 0);
        const [tx, ty, tz] = hf.toWorld(tPt[1], tPt[0], 0);
        const stCol: RGBA = item?.subtype === 'gondola' || item?.subtype === 'cable_car' ? [0.25, 0.34, 0.46, 0.1] : [0.32, 0.38, 0.46, 0.1];
        mb.box(bx, by, bz, 3.4, 2.8, 3.4, stCol);
        mb.gableRoof(bx, by + 2.8, bz, 3.8, 1.2, 3.8, [0.85, 0.42, 0.12, 0.15]);
        mb.box(tx, ty, tz, 3.0, 2.6, 3.0, stCol);
        mb.gableRoof(tx, ty + 2.6, tz, 3.4, 1.1, 3.4, [0.85, 0.42, 0.12, 0.15]);

        // Dual haul cables & intermediate steel pylons
        const totalMapLen = pts.reduce((acc, p, idx) => (idx === 0 ? 0 : acc + Math.hypot(p[0] - pts[idx - 1][0], p[1] - pts[idx - 1][1])), 0);
        const spans = Math.max(2, Math.min(12, Math.round(totalMapLen / 12)));
        const pylonCol: RGBA = [0.68, 0.74, 0.82, 0.08];

        let prevSample = samplePolylineAt(pts, 0);
        let prevW = hf.toWorld(prevSample.mapX, prevSample.mapY, cableHeight);

        for (let s = 1; s <= spans; s++) {
          const curSample = samplePolylineAt(pts, s / spans);
          const curW = hf.toWorld(curSample.mapX, curSample.mapY, cableHeight);
          const offX = -curSample.ty * 0.7;
          const offZ = -curSample.tx * 0.7;
          mb.beam(prevW[0] + offX, prevW[1], prevW[2] + offZ, curW[0] + offX, curW[1], curW[2] + offZ, 0.22, col);
          mb.beam(prevW[0] - offX, prevW[1], prevW[2] - offZ, curW[0] - offX, curW[1], curW[2] - offZ, 0.22, col);

          if (s < spans) {
            const groundY = hf.sampleHeight(curSample.mapX, curSample.mapY);
            mb.box(curW[0], groundY, curW[2], 0.5, cableHeight, 0.5, pylonCol);
            mb.beam(curW[0] - offX * 1.4, curW[1], curW[2] - offZ * 1.4, curW[0] + offX * 1.4, curW[1], curW[2] + offZ * 1.4, 0.22, pylonCol);
          }
          prevW = curW;
        }
      } else {
        // Unbuilt lift dashed guide line
        const sampleSteps = 12;
        for (let s = 0; s < sampleSteps; s += 2) {
          const p0 = samplePolylineAt(pts, s / sampleSteps);
          const p1 = samplePolylineAt(pts, Math.min(1, (s + 1) / sampleSteps));
          const w0 = hf.toWorld(p0.mapX, p0.mapY, cableHeight);
          const w1 = hf.toWorld(p1.mapX, p1.mapY, cableHeight);
          mb.beam(w0[0], w0[1], w0[2], w1[0], w1[1], w1[2], 0.28, col);
        }
      }
    }
  }

  // 2. Buildings, Parking & Terrain Parks
  if (options.showBuildings && (options.filter === 'all' || options.filter === 'buildings' || options.filter === 'built')) {
    for (const fac of facilities) {
      const [wx, wy, wz] = hf.toWorld(fac.mapX, fac.mapY, 0);
      const selected = options.selectedFacilityKey === fac.key;
      const scale = fac.clusterCount > 1 ? 1.25 : 1.0;
      const closed = fac.status === 'closed' || fac.status === 'broken';
      const glow = isNight && !closed ? 0.55 : 0.12;
      const winCol: RGBA = closed ? [0.22, 0.26, 0.32, 0] : [1.0, 0.85, 0.45, glow];

      if (selected) {
        mb.box(wx, wy + 0.05, wz, 7.6 * scale, 0.35, 7.6 * scale, [0.22, 0.74, 0.97, 0.7]);
      }

      if (fac.kind === 'terrain_park') {
        // Groomed snow deck + kicker jumps + blue/orange rails
        mb.box(wx, wy, wz, 6.4, 0.45, 5.2, [0.92, 0.96, 1.0, 0.1]);
        mb.gableRoof(wx - 1.4, wy + 0.45, wz, 2.2, 1.1, 2.2, [0.75, 0.88, 1.0, 0.15]);
        mb.beam(wx + 0.8, wy + 0.9, wz - 1.5, wx + 0.8, wy + 0.7, wz + 1.5, 0.2, [0.66, 0.34, 0.94, 0.35]);
        continue;
      }

      if (fac.kind === 'parking') {
        const isGarage = fac.subType === 'garage' || fac.subType === 'village_gondola';
        const lotW = (isGarage ? 6.2 : 7.0) * scale;
        const lotD = 5.4 * scale;
        mb.box(wx, wy, wz, lotW, isGarage ? 2.4 : 0.35, lotD, [0.24, 0.28, 0.35, 0.05]);
        if (isGarage) {
          mb.box(wx, wy + 2.4, wz, lotW + 0.4, 0.35, lotD + 0.4, [0.42, 0.48, 0.58, 0.1]);
        }
        // Render parked cars scaled by occupancyPct
        const cars = Math.max(1, Math.round((fac.occupancyPct / 100) * 6));
        for (let c = 0; c < cars; c++) {
          const cx = wx - lotW * 0.28 + (c % 3) * (lotW * 0.28);
          const cz = wz - 1.0 + Math.floor(c / 3) * 2.0;
          const cy = wy + (isGarage ? 2.75 : 0.35);
          const carCol: RGBA = c % 2 === 0 ? [0.25, 0.55, 0.9, 0.1] : [0.85, 0.32, 0.28, 0.1];
          mb.box(cx, cy, cz, 1.1, 0.65, 1.6, carCol);
        }
        continue;
      }

      // Buildings by type
      const lvl = Math.max(1, Math.min(3, fac.level));
      if (fac.subType === 'hotel') {
        const h = (2.6 + lvl * 1.4) * scale;
        const bw = (4.8 + lvl * 0.6) * scale;
        mb.box(wx, wy, wz, bw, h, 4.2 * scale, [0.84, 0.78, 0.68, 0.08]);
        mb.gableRoof(wx, wy + h, wz, bw + 0.7, 1.8, 4.8 * scale, [0.26, 0.34, 0.46, 0.08]);
        mb.box(wx, wy + 1.2, wz, bw + 0.15, h * 0.55, 2.2 * scale, winCol);
      } else if (fac.subType === 'restaurant') {
        const h = (2.2 + lvl * 0.8) * scale;
        mb.box(wx, wy, wz, 4.8 * scale, 1.2, 4.4 * scale, [0.55, 0.36, 0.22, 0.06]);
        mb.gableRoof(wx, wy + 1.2, wz, 5.2 * scale, h, 4.8 * scale, [0.76, 0.42, 0.16, 0.12]);
        mb.box(wx, wy + 1.3, wz + 1.7 * scale, 2.4 * scale, 0.9, 0.9, winCol);
      } else if (fac.subType === 'ski_patrol') {
        mb.box(wx, wy, wz, 4.0 * scale, 2.4 * scale, 3.8 * scale, [0.94, 0.96, 0.98, 0.12]);
        mb.gableRoof(wx, wy + 2.4 * scale, wz, 4.4 * scale, 1.1, 4.2 * scale, [0.84, 0.18, 0.2, 0.25]);
        // Red cross mark
        mb.box(wx, wy + 3.55 * scale, wz, 1.8, 0.2, 0.5, [0.96, 0.22, 0.22, 0.4]);
        mb.box(wx, wy + 3.55 * scale, wz, 0.5, 0.2, 1.8, [0.96, 0.22, 0.22, 0.4]);
      } else if (fac.subType === 'transportation') {
        mb.box(wx, wy, wz, 5.4 * scale, 1.9, 3.2 * scale, [0.24, 0.45, 0.78, 0.12]);
        mb.gableRoof(wx, wy + 1.9, wz, 5.8 * scale, 0.8, 3.6 * scale, [0.88, 0.92, 0.96, 0.1]);
      } else {
        // rental, retail, real_estate, off_season
        const roofCol: RGBA =
          fac.subType === 'retail'
            ? [0.64, 0.32, 0.82, 0.12]
            : fac.subType === 'real_estate'
              ? [0.18, 0.68, 0.48, 0.12]
              : [0.22, 0.52, 0.88, 0.12];
        const h = (2.1 + lvl * 0.7) * scale;
        mb.box(wx, wy, wz, 4.2 * scale, h, 3.8 * scale, [0.76, 0.72, 0.66, 0.08]);
        mb.gableRoof(wx, wy + h, wz, 4.7 * scale, 1.3, 4.2 * scale, roofCol);
        mb.box(wx, wy + 0.9, wz, 4.35 * scale, 0.8, 1.8 * scale, winCol);
      }
    }
  }

  return mb.build();
}

function buildUnitBox(
  verts: number[],
  idx: number[],
  cx: number, by: number, cz: number, sx: number, sy: number, sz: number,
  col: [number, number, number]
) {
  const tri = (
    ax: number, ay: number, az: number,
    bx: number, by2: number, bz: number,
    cx2: number, cy: number, cz2: number
  ) => {
    const ux = bx - ax, uy = by2 - ay, uz = bz - az;
    const vx = cx2 - ax, vy = cy - ay, vz = cz2 - az;
    const [nx, ny, nz] = normalize3(uy * vz - uz * vy, uz * vx - ux * vz, ux * vy - uy * vx);
    const b = verts.length / 9;
    verts.push(
      ax, ay, az, nx, ny, nz, col[0], col[1], col[2],
      bx, by2, bz, nx, ny, nz, col[0], col[1], col[2],
      cx2, cy, cz2, nx, ny, nz, col[0], col[1], col[2]
    );
    idx.push(b, b + 1, b + 2);
  };
  const quad = (
    ax: number, ay: number, az: number,
    bx: number, by2: number, bz: number,
    cx2: number, cy: number, cz2: number,
    dx: number, dy: number, dz: number
  ) => {
    tri(ax, ay, az, bx, by2, bz, cx2, cy, cz2);
    tri(ax, ay, az, cx2, cy, cz2, dx, dy, dz);
  };
  const x0 = cx - sx * 0.5, x1 = cx + sx * 0.5;
  const y0 = by, y1 = by + sy;
  const z0 = cz - sz * 0.5, z1 = cz + sz * 0.5;
  quad(x0, y0, z1, x1, y0, z1, x1, y1, z1, x0, y1, z1);
  quad(x1, y0, z0, x0, y0, z0, x0, y1, z0, x1, y1, z0);
  quad(x1, y0, z1, x1, y0, z0, x1, y1, z0, x1, y1, z1);
  quad(x0, y0, z0, x0, y0, z1, x0, y1, z1, x0, y1, z0);
  quad(x0, y1, z1, x1, y1, z1, x1, y1, z0, x0, y1, z0);
}

/** Instanced actor templates: skier (head + tinted jacket + twin skis), carrier, snowcat, snowflake */
export function buildUnitMeshes(): {
  skier: UnitMeshData;
  carrier: UnitMeshData;
  snowcat: UnitMeshData;
  flake: UnitMeshData;
} {
  // Skier: total ~1.55 world units tall; negative red channel signals INSTANCED_VS to apply jacket tint
  const sv: number[] = [];
  const si: number[] = [];
  buildUnitBox(sv, si, -0.22, 0.0, 0, 0.14, 0.08, 1.15, [0.15, 0.2, 0.28]);
  buildUnitBox(sv, si, 0.22, 0.0, 0, 0.14, 0.08, 1.15, [0.15, 0.2, 0.28]);
  buildUnitBox(sv, si, 0, 0.08, 0, 0.55, 0.72, 0.42, [-1, -1, -1]); // Jacket uses instance color
  buildUnitBox(sv, si, 0, 0.8, 0, 0.38, 0.38, 0.38, [0.96, 0.86, 0.76]); // Helmet / head

  // Lift carrier (chair / gondola shell tinted by instance color)
  const cv: number[] = [];
  const ci: number[] = [];
  buildUnitBox(cv, ci, 0, 0.35, 0, 0.12, 0.9, 0.12, [0.65, 0.72, 0.8]); // hanger
  buildUnitBox(cv, ci, 0, -0.45, 0, 1.1, 0.8, 0.85, [-1, -1, -1]);     // cabin / chair body

  // Snowcat groomer (bright orange/yellow body + blade + treads)
  const gv: number[] = [];
  const gi: number[] = [];
  buildUnitBox(gv, gi, 0, 0, 0, 1.6, 0.55, 2.2, [0.14, 0.18, 0.24]);
  buildUnitBox(gv, gi, 0, 0.55, -0.1, 1.35, 0.7, 1.4, [0.95, 0.58, 0.08]);
  buildUnitBox(gv, gi, 0, 0.1, 1.35, 2.1, 0.45, 0.25, [0.78, 0.82, 0.88]);

  // Snowflake / mist cube
  const fv: number[] = [];
  const fi: number[] = [];
  buildUnitBox(fv, fi, 0, -0.25, 0, 0.5, 0.5, 0.5, [0.94, 0.98, 1.0]);

  return {
    skier: { vertices: new Float32Array(sv), indices: new Uint16Array(si) },
    carrier: { vertices: new Float32Array(cv), indices: new Uint16Array(ci) },
    snowcat: { vertices: new Float32Array(gv), indices: new Uint16Array(gi) },
    flake: { vertices: new Float32Array(fv), indices: new Uint16Array(fi) },
  };
}
