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
    // Front (+Z), Back (-Z), Right (+X), Left (-X), Top (+Y)
    this.quad(x0, y0, z1, x1, y0, z1, x1, y1, z1, x0, y1, z1, col);
    this.quad(x1, y0, z0, x0, y0, z0, x0, y1, z0, x1, y1, z0, col);
    this.quad(x1, y0, z1, x1, y0, z0, x1, y1, z0, x1, y1, z1, col);
    this.quad(x0, y0, z0, x0, y0, z1, x0, y1, z1, x0, y1, z0, col);
    this.quad(x0, y1, z1, x1, y1, z1, x1, y1, z0, x0, y1, z0, col);
  }

  orientedBox(cx: number, by: number, cz: number, sx: number, sy: number, sz: number, yaw: number, col: RGBA) {
    const hx = sx * 0.5;
    const hz = sz * 0.5;
    const y0 = by;
    const y1 = by + sy;
    const c = Math.cos(yaw);
    const s = Math.sin(yaw);
    const pt = (lx: number, ly: number, lz: number): [number, number, number] => [
      cx + lx * c + lz * s,
      ly,
      cz - lx * s + lz * c,
    ];
    const p001 = pt(-hx, y0, hz), p101 = pt(hx, y0, hz), p111 = pt(hx, y1, hz), p011 = pt(-hx, y1, hz);
    const p000 = pt(-hx, y0, -hz), p100 = pt(hx, y0, -hz), p110 = pt(hx, y1, -hz), p010 = pt(-hx, y1, -hz);

    this.quad(p001[0], p001[1], p001[2], p101[0], p101[1], p101[2], p111[0], p111[1], p111[2], p011[0], p011[1], p011[2], col);
    this.quad(p100[0], p100[1], p100[2], p000[0], p000[1], p000[2], p010[0], p010[1], p010[2], p110[0], p110[1], p110[2], col);
    this.quad(p101[0], p101[1], p101[2], p100[0], p100[1], p100[2], p110[0], p110[1], p110[2], p111[0], p111[1], p111[2], col);
    this.quad(p000[0], p000[1], p000[2], p001[0], p001[1], p001[2], p011[0], p011[1], p011[2], p010[0], p010[1], p010[2], col);
    this.quad(p011[0], p011[1], p011[2], p111[0], p111[1], p111[2], p110[0], p110[1], p110[2], p010[0], p010[1], p010[2], col);
  }

  gableRoof(cx: number, by: number, cz: number, sx: number, sy: number, sz: number, col: RGBA) {
    const x0 = cx - sx * 0.5, x1 = cx + sx * 0.5;
    const y0 = by, y1 = by + sy;
    const z0 = cz - sz * 0.5, z1 = cz + sz * 0.5;
    this.quad(x0, y0, z1, x1, y0, z1, x1, y1, cz, x0, y1, cz, col);
    this.quad(x1, y0, z0, x0, y0, z0, x0, y1, cz, x1, y1, cz, col);
    this.tri(x1, y0, z1, x1, y0, z0, x1, y1, cz, col);
    this.tri(x0, y0, z0, x0, y0, z1, x0, y1, cz, col);
  }

  /** Thin beam or cable connecting two 3D points with outward CCW face winding */
  beam(ax: number, ay: number, az: number, bx: number, by: number, bz: number, r: number, col: RGBA) {
    const dx = bx - ax, dy = by - ay, dz = bz - az;
    const len = Math.hypot(dx, dy, dz);
    if (len < 0.04) return;
    const ux = dx / len, uy = dy / len, uz = dz / len;
    const refX = Math.abs(uy) < 0.9 ? 0 : 1;
    const refY = Math.abs(uy) < 0.9 ? 1 : 0;
    const [px, py, pz] = normalize3(-uz * refY, uz * refX, ux * refY - uy * refX);
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
      const a = r * cols + c;
      const b = r * cols + c + 1;
      const d = (r + 1) * cols + c;
      const e = (r + 1) * cols + c + 1;
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
  if (seg.type === 'lift') return [0.95, 0.62, 0.08];
  switch (seg.difficulty || seg.type) {
    case 'green':
      return [0.16, 0.76, 0.38];
    case 'blue':
      return [0.22, 0.52, 0.94];
    case 'black':
      return [0.16, 0.21, 0.30];
    case 'double_black':
      return [0.86, 0.22, 0.22];
    case 'snowpark':
    case 'terrain_park':
      return [0.64, 0.34, 0.92];
    case 'crosscountry':
      return [0.08, 0.72, 0.65];
    default:
      return [0.56, 0.62, 0.70];
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
      return [0.55, 0.88, 0.98];
    case 'groomed':
      return [0.24, 0.82, 0.56];
    case 'packed':
      return [0.36, 0.66, 0.94];
    case 'icy':
      return [0.94, 0.64, 0.22];
    case 'bare':
      return [0.76, 0.32, 0.24];
    default:
      return [0.56, 0.62, 0.70];
  }
}

function steepColor(deg: number): [number, number, number] {
  if (deg < 14) return [0.18, 0.76, 0.42];
  if (deg < 24) return [0.22, 0.52, 0.94];
  if (deg < 34) return [0.94, 0.62, 0.14];
  return [0.88, 0.22, 0.24];
}

function subdividePolyline(pts: [number, number][], stepUnits: number, maxSubPerSeg = 12): [number, number][] {
  const out: [number, number][] = [];
  for (let i = 0; i < pts.length; i++) {
    if (i > 0) {
      const dist = Math.hypot(pts[i][0] - pts[i - 1][0], pts[i][1] - pts[i - 1][1]);
      const sub = Math.min(maxSubPerSeg, Math.max(1, Math.round(dist / stepUnits)));
      for (let s = 1; s < sub; s++) {
        const t = s / sub;
        out.push([lerp(pts[i - 1][0], pts[i][0], t), lerp(pts[i - 1][1], pts[i][1], t)]);
      }
    }
    out.push(pts[i]);
  }
  return out;
}

function drawGroundRibbon(
  mb: MeshBuilder,
  hf: Heightfield,
  pts: [number, number][],
  halfW: number,
  yLift: number,
  emissive: number,
  baseRgb: [number, number, number],
  lens: MapLens,
  dashed: boolean,
  selected: boolean
) {
  const dense = subdividePolyline(pts, dashed ? 3.0 : 4.2, dashed ? 24 : 10);
  for (let i = 0; i < dense.length - 1; i++) {
    if (dashed && !selected && i % 2 === 1) continue;
    const [ay, ax] = dense[i];
    const [by, bx] = dense[i + 1];
    const dx = bx - ax;
    const dy = by - ay;
    const dLen = Math.hypot(dx, dy) || 1;
    const px = (-dy / dLen) * halfW;
    const py = (dx / dLen) * halfW;

    let rgb = baseRgb;
    if (lens === 'steepness') {
      rgb = steepColor(hf.sampleSlopeDeg((ax + bx) * 0.5, (ay + by) * 0.5));
    }
    const col: RGBA = [rgb[0], rgb[1], rgb[2], emissive];

    const [ax0, ay0, az0] = hf.toWorld(ax - px, ay - py, yLift);
    const [ax1, ay1, az1] = hf.toWorld(ax + px, ay + py, yLift);
    const [bx0, by0, bz0] = hf.toWorld(bx - px, by - py, yLift);
    const [bx1, by1, bz1] = hf.toWorld(bx + px, by + py, yLift);
    mb.quad(ax0, ay0, az0, bx0, by0, bz0, bx1, by1, bz1, ax1, ay1, az1, col);

    if (selected) {
      const hw = halfW + 0.34;
      const hpx = (-dy / dLen) * hw;
      const hpy = (dx / dLen) * hw;
      const [hx0, hy0, hz0] = hf.toWorld(ax - hpx, ay - hpy, yLift - 0.04);
      const [hx1, hy1, hz1] = hf.toWorld(ax + hpx, ay + hpy, yLift - 0.04);
      const [kx0, ky0, kz0] = hf.toWorld(bx - hpx, by - hpy, yLift - 0.04);
      const [kx1, ky1, kz1] = hf.toWorld(bx + hpx, by + hpy, yLift - 0.04);
      mb.quad(hx0, hy0, hz0, kx0, ky0, kz0, kx1, ky1, kz1, hx1, hy1, hz1, [0.95, 0.98, 1.0, 0.5]);
    }
  }
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
  const { width: W, height: H, cols, rows } = hf;
  const plinthCol: RGBA = [0.16, 0.21, 0.28, 0.0];
  const floorY = -5.5;

  // 0. Architectural diorama plinth skirt around outer terrain edges
  for (let c = 0; c < cols - 1; c++) {
    const x0 = (c / (cols - 1)) * W;
    const x1 = ((c + 1) / (cols - 1)) * W;
    mb.quad(x0, floorY, H, x1, floorY, H, x1, hf.sampleHeight(x1, 0), H, x0, hf.sampleHeight(x0, 0), H, plinthCol);
    mb.quad(x1, floorY, 0, x0, floorY, 0, x0, hf.sampleHeight(x0, H), 0, x1, hf.sampleHeight(x1, H), 0, plinthCol);
  }
  for (let r = 0; r < rows - 1; r++) {
    const my0 = (r / (rows - 1)) * H;
    const my1 = ((r + 1) / (rows - 1)) * H;
    const z0 = H - my0;
    const z1 = H - my1;
    mb.quad(0, floorY, z1, 0, floorY, z0, 0, hf.sampleHeight(0, my0), z0, 0, hf.sampleHeight(0, my1), z1, plinthCol);
    mb.quad(W, floorY, z0, W, floorY, z1, W, hf.sampleHeight(W, my1), z1, W, hf.sampleHeight(W, my0), z0, plinthCol);
  }

  const isBuilt = (id: string | number) => builtIds.some((b) => sameId(b, id));
  const perSeg = liveSkiers?.perSegment ?? {};
  const isNight = options.lighting === 'night' || options.lighting === 'sunset';

  // 1. Slopes, Sleek Lifts, Pylons, Snowmakers & Night Lights
  for (const seg of segments) {
    const built = isBuilt(seg.id);
    if (options.filter === 'lift' && seg.type !== 'lift') continue;
    if (options.filter === 'slope' && seg.type === 'lift') continue;
    if (options.filter === 'built' && !built) continue;
    if (options.filter === 'buildings') continue;
    if (!built && !options.showAvailable) continue;

    const rawPts = parsePoints(seg.points);
    if (rawPts.length < 2) continue;

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
      const pts = rawPts[0][0] >= rawPts[rawPts.length - 1][0] ? rawPts : [...rawPts].reverse();
      drawGroundRibbon(
        mb,
        hf,
        pts,
        selected ? 0.76 : built ? 0.46 : 0.24,
        selected ? 0.16 : built ? 0.12 : 0.09,
        selected ? 0.42 : built ? 0.14 : 0.05,
        rgb,
        options.lens,
        !built && !selected,
        selected
      );

      // Compact snowmaking fan guns & slender night light poles along built slopes
      if (built && item) {
        const smCount = Math.min(3, item.snowmakers_count || 0);
        for (let g = 0; g < smCount; g++) {
          const sample = samplePolylineAt(pts, (g + 0.5) / Math.max(1, smCount));
          const [gx, gy, gz] = hf.toWorld(sample.mapX - sample.ty * 0.72, sample.mapY + sample.tx * 0.72, 0);
          mb.box(gx, gy - 0.1, gz, 0.22, 0.26, 0.22, [0.82, 0.86, 0.92, 0.06]);
          mb.box(gx, gy + 0.16, gz, 0.26, 0.24, 0.34, [0.22, 0.56, 0.96, item.snowmakers_active ? 0.32 : 0.05]);
        }
        if (item.night_lit && isNight) {
          for (let l = 0; l < 4; l++) {
            const sample = samplePolylineAt(pts, (l + 0.5) / 4);
            const [lx, ly, lz] = hf.toWorld(sample.mapX + sample.ty * 0.72, sample.mapY - sample.tx * 0.72, 0);
            mb.box(lx, ly - 0.1, lz, 0.09, 1.15, 0.09, [0.34, 0.40, 0.50, 0.0]);
            mb.box(lx, ly + 1.05, lz, 0.24, 0.14, 0.24, [1.0, 0.88, 0.45, 0.85]);
          }
        }
      }
    } else {
      // Lifts always oriented ascending low->high
      const pts = rawPts[0][0] <= rawPts[rawPts.length - 1][0] ? rawPts : [...rawPts].reverse();

      if (!built && !selected) {
        // Available unbuilt lifts sit cleanly as subtle dashed ground ribbons (zero overhead clutter)
        drawGroundRibbon(mb, hf, pts, 0.24, 0.10, 0.06, rgb, options.lens, true, false);
        continue;
      }

      // Ground track guide under the lift cable
      drawGroundRibbon(mb, hf, pts, selected ? 0.36 : 0.20, 0.08, selected ? 0.42 : 0.16, rgb, options.lens, false, selected);

      const sub = item?.subtype || 'chair_fixed';
      const cableHeight =
        sub === 'button' ? 0.85 : sub === 'chair_fixed' ? 1.45 : sub === 'chair_detach' ? 1.55 : sub === 'cable_car' ? 2.15 : 1.65;
      const baseSample = samplePolylineAt(pts, 0);
      const topSample = samplePolylineAt(pts, 1);
      const baseYaw = Math.atan2(baseSample.tx, -baseSample.ty);
      const topYaw = Math.atan2(topSample.tx, -topSample.ty);

      const [bx, by, bz] = hf.toWorld(baseSample.mapX, baseSample.mapY, 0);
      const [tx, ty, tz] = hf.toWorld(topSample.mapX, topSample.mapY, 0);
      const steelCol: RGBA = [0.68, 0.74, 0.82, 0.08];
      const darkSteel: RGBA = [0.34, 0.40, 0.48, 0.06];
      const shellCol: RGBA = closed ? [0.22, 0.26, 0.32, 0.04] : [0.24, 0.29, 0.38, 0.10];
      const trimCol: RGBA = selected ? [0.22, 0.78, 0.98, 0.55] : [rgb[0], rgb[1], rgb[2], 0.22];
      const glassCol: RGBA = closed ? [0.22, 0.28, 0.36, 0.04] : [0.58, 0.80, 0.95, isNight ? 0.45 : 0.15];

      const hutX = -baseSample.ty * 0.92;
      const hutZ = -baseSample.tx * 0.92;

      if (sub === 'button') {
        mb.orientedBox(bx, by - 0.25, bz, 0.16, 0.88, 0.16, baseYaw, steelCol);
        mb.orientedBox(bx, by + 0.63, bz, 0.56, 0.14, 0.56, baseYaw, trimCol);
        mb.orientedBox(bx + hutX, by - 0.25, bz + hutZ, 0.42, 0.62, 0.42, baseYaw, shellCol);
        mb.orientedBox(tx, ty - 0.25, tz, 0.16, 0.88, 0.16, topYaw, steelCol);
        mb.orientedBox(tx, ty + 0.63, tz, 0.52, 0.14, 0.52, topYaw, trimCol);
      } else if (sub === 'chair_fixed') {
        // Open steel bullwheel portal frame (air open beneath) + tiny offset liftie hut
        mb.orientedBox(bx, by - 0.28, bz, 0.22, cableHeight + 0.20, 0.22, baseYaw, darkSteel);
        mb.orientedBox(bx, by + cableHeight - 0.08, bz, 0.88, 0.14, 0.88, baseYaw, steelCol);
        mb.orientedBox(bx, by + cableHeight + 0.06, bz, 0.78, 0.09, 0.78, baseYaw, trimCol);
        mb.orientedBox(bx + hutX, by - 0.25, bz + hutZ, 0.50, 0.68, 0.50, baseYaw, shellCol);
        mb.orientedBox(bx + hutX, by + 0.43, bz + hutZ, 0.56, 0.10, 0.56, baseYaw, trimCol);

        mb.orientedBox(tx, ty - 0.28, tz, 0.20, cableHeight + 0.20, 0.20, topYaw, darkSteel);
        mb.orientedBox(tx, ty + cableHeight - 0.08, tz, 0.82, 0.14, 0.82, topYaw, steelCol);
        mb.orientedBox(tx, ty + cableHeight + 0.06, tz, 0.72, 0.09, 0.72, topYaw, trimCol);
      } else if (sub === 'chair_detach') {
        // Detachable chairlift: open cantilevered bullwheel canopy hood on central steel piers + side booth
        mb.orientedBox(bx, by - 0.28, bz, 0.24, cableHeight + 0.16, 0.42, baseYaw, darkSteel);
        mb.orientedBox(bx, by + cableHeight - 0.12, bz, 1.12, 0.18, 1.40, baseYaw, shellCol);
        mb.orientedBox(bx, by + cableHeight + 0.06, bz, 1.18, 0.10, 1.46, baseYaw, trimCol);
        mb.orientedBox(bx + hutX * 1.1, by - 0.25, bz + hutZ * 1.1, 0.52, 0.72, 0.52, baseYaw, shellCol);

        mb.orientedBox(tx, ty - 0.28, tz, 0.22, cableHeight + 0.16, 0.38, topYaw, darkSteel);
        mb.orientedBox(tx, ty + cableHeight - 0.12, tz, 1.04, 0.18, 1.28, topYaw, shellCol);
        mb.orientedBox(tx, ty + cableHeight + 0.06, tz, 1.10, 0.10, 1.34, topYaw, trimCol);
      } else {
        // Gondola or Cable Car: enclosed architectural terminal with glass window band
        const stScale = sub === 'cable_car' ? 1.25 : 1.0;
        const stH = cableHeight - 0.18;
        mb.orientedBox(bx, by - 0.32, bz, 1.25 * stScale, stH + 0.32, 1.50 * stScale, baseYaw, shellCol);
        mb.orientedBox(bx, by + 0.28, bz, 1.29 * stScale, 0.34, 0.96 * stScale, baseYaw, glassCol);
        mb.orientedBox(bx, by + stH, bz, 1.34 * stScale, 0.14, 1.58 * stScale, baseYaw, trimCol);

        mb.orientedBox(tx, ty - 0.32, tz, 1.14 * stScale, stH + 0.28, 1.36 * stScale, topYaw, shellCol);
        mb.orientedBox(tx, ty + 0.26, tz, 1.18 * stScale, 0.32, 0.86 * stScale, topYaw, glassCol);
        mb.orientedBox(tx, ty + stH - 0.04, tz, 1.22 * stScale, 0.14, 1.44 * stScale, topYaw, trimCol);
      }

      // Slender metallic haul cables & minimalist T-pylons
      const totalMapLen = pts.reduce((acc, p, idx) => (idx === 0 ? 0 : acc + Math.hypot(p[0] - pts[idx - 1][0], p[1] - pts[idx - 1][1])), 0);
      const spans = Math.max(2, Math.min(6, Math.round(totalMapLen / 18)));
      const cableCol: RGBA = selected ? [0.24, 0.78, 0.98, 0.5] : [0.46, 0.52, 0.60, 0.10];
      const cableOff = 0.24;
      const mastW = sub === 'button' ? 0.10 : 0.14;

      let prevSample = samplePolylineAt(pts, 0);
      let prevW = hf.toWorld(prevSample.mapX, prevSample.mapY, cableHeight);

      for (let s = 1; s <= spans; s++) {
        const curSample = samplePolylineAt(pts, s / spans);
        const curW = hf.toWorld(curSample.mapX, curSample.mapY, cableHeight);
        const offX = -curSample.ty * cableOff;
        const offZ = -curSample.tx * cableOff;
        const pOffX = -prevSample.ty * cableOff;
        const pOffZ = -prevSample.tx * cableOff;

        mb.beam(prevW[0] + pOffX, prevW[1], prevW[2] + pOffZ, curW[0] + offX, curW[1], curW[2] + offZ, 0.042, cableCol);
        mb.beam(prevW[0] - pOffX, prevW[1], prevW[2] - pOffZ, curW[0] - offX, curW[1], curW[2] - offZ, 0.042, cableCol);

        if (s < spans) {
          const groundY = hf.sampleHeight(curSample.mapX, curSample.mapY);
          const pYaw = Math.atan2(curSample.tx, -curSample.ty);
          mb.box(curW[0], groundY - 0.30, curW[2], mastW, cableHeight + 0.30, mastW, steelCol);
          mb.beam(
            curW[0] - offX * 1.35, curW[1], curW[2] - offZ * 1.35,
            curW[0] + offX * 1.35, curW[1], curW[2] + offZ * 1.35,
            0.038,
            steelCol
          );
          mb.orientedBox(curW[0] + offX, curW[1] - 0.02, curW[2] + offZ, 0.10, 0.12, 0.24, pYaw, steelCol);
          mb.orientedBox(curW[0] - offX, curW[1] - 0.02, curW[2] - offZ, 0.10, 0.12, 0.24, pYaw, steelCol);
        }
        prevSample = curSample;
        prevW = curW;
      }
    }
  }

  // 2. Miniature Buildings, Parking Lots & Terrain Parks
  if (options.showBuildings && (options.filter === 'all' || options.filter === 'buildings' || options.filter === 'built')) {
    for (const fac of facilities) {
      const [wx, wy, wz] = hf.toWorld(fac.mapX, fac.mapY, 0);
      const selected = options.selectedFacilityKey === fac.key;
      const scale = fac.clusterCount > 1 ? 1.18 : 1.0;
      const closed = fac.status === 'closed' || fac.status === 'broken';
      const glow = isNight && !closed ? 0.65 : 0.16;
      const winCol: RGBA = closed ? [0.20, 0.24, 0.30, 0] : [1.0, 0.86, 0.45, glow];

      if (selected) {
        mb.box(wx, wy + 0.04, wz, 3.0 * scale, 0.14, 2.6 * scale, [0.22, 0.76, 0.98, 0.65]);
      }

      if (fac.kind === 'terrain_park') {
        mb.box(wx, wy - 0.08, wz, 2.4, 0.20, 1.85, [0.92, 0.96, 1.0, 0.08]);
        mb.gableRoof(wx - 0.55, wy + 0.12, wz, 0.85, 0.42, 0.85, [0.78, 0.90, 1.0, 0.12]);
        mb.beam(wx + 0.32, wy + 0.28, wz - 0.65, wx + 0.32, wy + 0.20, wz + 0.65, 0.06, [0.66, 0.34, 0.94, 0.35]);
        continue;
      }

      if (fac.kind === 'parking') {
        const isGarage = fac.subType === 'garage' || fac.subType === 'village_gondola';
        const lotW = (isGarage ? 2.4 : 2.7) * scale;
        const lotD = 1.9 * scale;
        const deckH = isGarage ? 0.90 : 0.22;
        mb.box(wx, wy - 0.12, wz, lotW, deckH, lotD, [0.24, 0.28, 0.35, 0.05]);
        if (isGarage) {
          mb.box(wx, wy - 0.12 + deckH, wz, lotW + 0.16, 0.14, lotD + 0.16, [0.42, 0.48, 0.58, 0.08]);
        }
        const cars = Math.max(1, Math.round((fac.occupancyPct / 100) * 6));
        for (let c = 0; c < cars; c++) {
          const cx = wx - lotW * 0.28 + (c % 3) * (lotW * 0.28);
          const cz = wz - 0.42 + Math.floor(c / 3) * 0.84;
          const cy = wy - 0.12 + deckH + (isGarage ? 0.14 : 0);
          const carCol: RGBA = c % 2 === 0 ? [0.26, 0.56, 0.92, 0.1] : [0.86, 0.32, 0.28, 0.1];
          mb.box(cx, cy, cz, 0.38, 0.22, 0.62, carCol);
        }
        continue;
      }

      const lvl = Math.max(1, Math.min(3, fac.level));
      if (fac.subType === 'hotel') {
        const h = (0.95 + lvl * 0.35) * scale;
        const bw = (1.95 + lvl * 0.20) * scale;
        mb.box(wx, wy - 0.25, wz, bw, h + 0.25, 1.7 * scale, [0.84, 0.78, 0.68, 0.08]);
        mb.gableRoof(wx, wy + h, wz, bw + 0.28, 0.72, 1.95 * scale, [0.26, 0.34, 0.46, 0.08]);
        mb.box(wx, wy + 0.42, wz, bw + 0.06, h * 0.50, 0.9 * scale, winCol);
      } else if (fac.subType === 'restaurant') {
        const h = (0.85 + lvl * 0.25) * scale;
        mb.box(wx, wy - 0.25, wz, 1.85 * scale, h + 0.25, 1.65 * scale, [0.56, 0.38, 0.24, 0.06]);
        mb.gableRoof(wx, wy + h, wz, 2.05 * scale, 0.68, 1.85 * scale, [0.76, 0.42, 0.16, 0.12]);
        mb.box(wx, wy + 0.42, wz + 0.65 * scale, 0.95 * scale, 0.36, 0.36, winCol);
      } else if (fac.subType === 'ski_patrol') {
        mb.box(wx, wy - 0.25, wz, 1.5 * scale, 0.85 + 0.25, 1.4 * scale, [0.94, 0.96, 0.98, 0.12]);
        mb.gableRoof(wx, wy + 0.85, wz, 1.68 * scale, 0.46, 1.58 * scale, [0.84, 0.18, 0.20, 0.24]);
        mb.box(wx, wy + 1.32, wz, 0.68, 0.08, 0.18, [0.96, 0.22, 0.22, 0.45]);
        mb.box(wx, wy + 1.32, wz, 0.18, 0.08, 0.68, [0.96, 0.22, 0.22, 0.45]);
      } else if (fac.subType === 'transportation') {
        mb.box(wx, wy - 0.25, wz, 2.1 * scale, 0.80 + 0.25, 1.3 * scale, [0.24, 0.46, 0.78, 0.12]);
        mb.gableRoof(wx, wy + 0.80, wz, 2.28 * scale, 0.34, 1.48 * scale, [0.88, 0.92, 0.96, 0.1]);
      } else {
        const roofCol: RGBA =
          fac.subType === 'retail'
            ? [0.64, 0.32, 0.82, 0.12]
            : fac.subType === 'real_estate'
              ? [0.18, 0.68, 0.48, 0.12]
              : [0.22, 0.52, 0.88, 0.12];
        const h = (0.85 + lvl * 0.22) * scale;
        mb.box(wx, wy - 0.25, wz, 1.7 * scale, h + 0.25, 1.5 * scale, [0.76, 0.72, 0.66, 0.08]);
        mb.gableRoof(wx, wy + h, wz, 1.9 * scale, 0.54, 1.7 * scale, roofCol);
        mb.box(wx, wy + 0.36, wz, 1.76 * scale, 0.32, 0.72 * scale, winCol);
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

/** Crisp miniature instanced actor templates scaled to fit 0.92-wide trails and distinct lift types */
export function buildUnitMeshes(): {
  skier: UnitMeshData;
  chair: UnitMeshData;
  cabin: UnitMeshData;
  surface: UnitMeshData;
  snowcat: UnitMeshData;
  flake: UnitMeshData;
} {
  // Skier (~0.62 world tall at scale 1.0): twin skis + jacket torso + helmet
  const sv: number[] = [];
  const si: number[] = [];
  buildUnitBox(sv, si, -0.09, 0.0, 0, 0.06, 0.04, 0.52, [0.15, 0.2, 0.28]);
  buildUnitBox(sv, si, 0.09, 0.0, 0, 0.06, 0.04, 0.52, [0.15, 0.2, 0.28]);
  buildUnitBox(sv, si, 0, 0.04, 0, 0.24, 0.34, 0.18, [-1, -1, -1]);
  buildUnitBox(sv, si, 0, 0.38, 0, 0.17, 0.17, 0.17, [0.96, 0.86, 0.76]);

  // Open chairlift bench (slim steel hanger + horizontal seat + upright backrest, open air)
  const chv: number[] = [];
  const chi: number[] = [];
  buildUnitBox(chv, chi, 0, 0.10, 0, 0.04, 0.34, 0.04, [0.68, 0.74, 0.82]);
  buildUnitBox(chv, chi, 0, -0.02, 0.02, 0.52, 0.06, 0.20, [-1, -1, -1]);
  buildUnitBox(chv, chi, 0, 0.04, -0.07, 0.52, 0.20, 0.05, [-1, -1, -1]);

  // Surface lift platter / T-bar hanger
  const sfv: number[] = [];
  const sfi: number[] = [];
  buildUnitBox(sfv, sfi, 0, 0.02, 0, 0.035, 0.52, 0.035, [0.70, 0.76, 0.84]);
  buildUnitBox(sfv, sfi, 0, -0.02, 0, 0.26, 0.05, 0.07, [-1, -1, -1]);

  // Enclosed Gondola / Cable Car cabin with panoramic glass band
  const cbv: number[] = [];
  const cbi: number[] = [];
  buildUnitBox(cbv, cbi, 0, 0.16, 0, 0.05, 0.32, 0.05, [0.65, 0.72, 0.80]);
  buildUnitBox(cbv, cbi, 0, -0.18, 0, 0.40, 0.36, 0.36, [-1, -1, -1]);
  buildUnitBox(cbv, cbi, 0, -0.04, 0, 0.42, 0.14, 0.38, [0.66, 0.82, 0.94]);

  // Snowcat groomer (compact piste cab + blade)
  const gv: number[] = [];
  const gi: number[] = [];
  buildUnitBox(gv, gi, 0, 0, 0, 0.75, 0.26, 1.05, [0.14, 0.18, 0.24]);
  buildUnitBox(gv, gi, 0, 0.26, -0.05, 0.64, 0.34, 0.68, [0.95, 0.58, 0.08]);
  buildUnitBox(gv, gi, 0, 0.05, 0.62, 0.96, 0.22, 0.12, [0.78, 0.82, 0.88]);

  // Crisp snowflake speck
  const fv: number[] = [];
  const fi: number[] = [];
  buildUnitBox(fv, fi, 0, -0.10, 0, 0.20, 0.20, 0.20, [0.94, 0.98, 1.0]);

  return {
    skier: { vertices: new Float32Array(sv), indices: new Uint16Array(si) },
    chair: { vertices: new Float32Array(chv), indices: new Uint16Array(chi) },
    cabin: { vertices: new Float32Array(cbv), indices: new Uint16Array(cbi) },
    surface: { vertices: new Float32Array(sfv), indices: new Uint16Array(sfi) },
    snowcat: { vertices: new Float32Array(gv), indices: new Uint16Array(gi) },
    flake: { vertices: new Float32Array(fv), indices: new Uint16Array(fi) },
  };
}
