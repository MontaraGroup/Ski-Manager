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
import { clamp, lerp, normalize3, samplePolylineAt, smoothTrailPolyline } from './math';

export interface StaticMeshData {
  vertices: Float32Array; // stride 10: pos(3), normal(3), color(4) [rgb + emissive]
  indices: Uint32Array;
}

export interface RibbonMeshData {
  vertices: Float32Array; // stride 16: pos(3), side(3), colorWidth(4), uvStyle(4), meta(2)
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

  /** Upright cylindrical or tapered conical column with outward CCW face winding */
  cylinder(cx: number, by: number, cz: number, rBot: number, rTop: number, h: number, segs: number, col: RGBA) {
    const ty = by + h;
    for (let k = 0; k < segs; k++) {
      const a0 = (k / segs) * Math.PI * 2;
      const a1 = ((k + 1) / segs) * Math.PI * 2;
      const c0 = Math.cos(a0), s0 = Math.sin(a0);
      const c1 = Math.cos(a1), s1 = Math.sin(a1);
      const b0x = cx + rBot * c0, b0z = cz - rBot * s0;
      const b1x = cx + rBot * c1, b1z = cz - rBot * s1;
      const t0x = cx + rTop * c0, t0z = cz - rTop * s0;
      const t1x = cx + rTop * c1, t1z = cz - rTop * s1;
      this.quad(b0x, by, b0z, b1x, by, b1z, t1x, ty, t1z, t0x, ty, t0z, col);
      if (rTop > 1e-4) this.tri(cx, ty, cz, t0x, ty, t0z, t1x, ty, t1z, col);
      if (rBot > 1e-4) this.tri(cx, by, cz, b1x, by, b1z, b0x, by, b0z, col);
    }
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
      return [0.09, 0.74, 0.34];
    case 'blue':
      return [0.18, 0.50, 0.95];
    case 'black':
      return [0.10, 0.14, 0.21];
    case 'double_black':
      return [0.86, 0.17, 0.20];
    case 'snowpark':
    case 'terrain_park':
      return [0.64, 0.34, 0.94];
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

class RibbonMeshBuilder {
  v: number[] = [];
  i: number[] = [];

  private pushVert(
    wx: number, wy: number, wz: number,
    sx: number, sy: number, sz: number,
    r: number, g: number, b: number, baseHalfW: number,
    capX: number, capY: number, along: number, style: number,
    segIdx: number, nightLit: number
  ): number {
    const idx = this.v.length / 16;
    this.v.push(
      wx, wy, wz,
      sx, sy, sz,
      r, g, b, baseHalfW,
      capX, capY, along, style,
      segIdx, nightLit
    );
    return idx;
  }

  addPolyline(
    hf: Heightfield,
    pts: [number, number][],
    options: {
      baseHalfW: number;
      yLift: number;
      style: number;
      segIdx: number;
      nightLit: number;
      baseRgb: [number, number, number];
      lens: MapLens;
    }
  ) {
    const m = pts.length;
    if (m < 2) return;

    const { baseHalfW, yLift, style, segIdx, nightLit, baseRgb, lens } = options;
    const cum = new Float64Array(m);
    for (let k = 1; k < m; k++) {
      cum[k] = cum[k - 1] + Math.hypot(pts[k][0] - pts[k - 1][0], pts[k][1] - pts[k - 1][1]);
    }
    const totalLen = cum[m - 1];
    if (totalLen < 1e-4) return;

    const segT: [number, number][] = new Array(m - 1);
    let lastValid: [number, number] = [1, 0];
    for (let k = 0; k < m - 1; k++) {
      const dx = pts[k + 1][1] - pts[k][1];
      const dy = pts[k + 1][0] - pts[k][0];
      const len = Math.hypot(dx, dy);
      if (len >= 1e-5) {
        lastValid = [dx / len, dy / len];
      }
      segT[k] = lastValid;
    }

    const eps = 0.8;
    const bodyLeftIdx: number[] = [];
    const bodyRightIdx: number[] = [];

    let firstMeta: {
      mx: number; wy: number; wz: number;
      sideL: [number, number, number]; sideR: [number, number, number];
      col: [number, number, number];
      t: [number, number]; hC: number;
    } | null = null;
    let lastMeta: {
      mx: number; wy: number; wz: number;
      sideL: [number, number, number]; sideR: [number, number, number];
      col: [number, number, number];
      t: [number, number]; hC: number;
    } | null = null;

    // Precompute body vertex info first so start cap precedes body rows cleanly
    const rows: {
      mx: number; wy: number; wz: number;
      sideL: [number, number, number]; sideR: [number, number, number];
      col: [number, number, number];
      along: number;
      hC: number;
    }[] = new Array(m);

    for (let k = 0; k < m; k++) {
      const my = pts[k][0];
      const mx = pts[k][1];
      let rx: number;
      let ry: number;
      let miterScale = 1.0;

      if (k === 0) {
        rx = segT[0][1];
        ry = -segT[0][0];
      } else if (k === m - 1) {
        rx = segT[m - 2][1];
        ry = -segT[m - 2][0];
      } else {
        const [t0x, t0y] = segT[k - 1];
        const [t1x, t1y] = segT[k];
        const n0x = t0y;
        const n0y = -t0x;
        const n1x = t1y;
        const n1y = -t1x;
        const dotT = t0x * t1x + t0y * t1y;
        if (dotT < -0.7) {
          rx = n1x;
          ry = n1y;
        } else {
          const mxv = n0x + n1x;
          const myv = n0y + n1y;
          const mLen = Math.hypot(mxv, myv);
          if (mLen < 1e-5) {
            rx = n1x;
            ry = n1y;
          } else {
            rx = mxv / mLen;
            ry = myv / mLen;
            const proj = rx * n1x + ry * n1y;
            miterScale = Math.min(1.65, 1.0 / Math.max(0.5, proj));
          }
        }
      }

      const hC = hf.sampleHeight(mx, my);
      const hR = hf.sampleHeight(mx + rx * eps, my + ry * eps);
      const hL = hf.sampleHeight(mx - rx * eps, my - ry * eps);
      const ridgeLift = Math.max(0, hC - 0.5 * (hL + hR));
      const dyR = clamp((hR - hC) / eps, -1.2, 1.2);
      const dyL = clamp((hL - hC) / eps, -1.2, 1.2);
      const wy = hC + ridgeLift + yLift;
      const wz = hf.height - my;

      const sideR: [number, number, number] = [rx * miterScale, dyR * miterScale, -ry * miterScale];
      const sideL: [number, number, number] = [-rx * miterScale, dyL * miterScale, ry * miterScale];
      const col = lens === 'steepness' ? steepColor(hf.sampleSlopeDeg(mx, my)) : baseRgb;
      rows[k] = { mx, wy, wz, sideL, sideR, col, along: cum[k], hC };
    }

    const r0 = rows[0];
    const [t0x, t0y] = segT[0];
    const dyFwd0 = clamp((hf.sampleHeight(r0.mx + t0x * eps, pts[0][0] + t0y * eps) - r0.hC) / eps, -1.2, 1.2);
    firstMeta = { ...r0, t: [t0x, t0y], hC: dyFwd0 };

    const rn = rows[m - 1];
    const [tnx, tny] = segT[m - 2];
    const dyFwdN = clamp((hf.sampleHeight(rn.mx + tnx * eps, pts[m - 1][0] + tny * eps) - rn.hC) / eps, -1.2, 1.2);
    lastMeta = { ...rn, t: [tnx, tny], hC: dyFwdN };

    // Start cap row
    const sL = this.pushVert(
      firstMeta.mx, firstMeta.wy, firstMeta.wz,
      firstMeta.sideL[0] - firstMeta.t[0], firstMeta.sideL[1] - firstMeta.hC, firstMeta.sideL[2] + firstMeta.t[1],
      firstMeta.col[0], firstMeta.col[1], firstMeta.col[2], baseHalfW,
      -1, -1, -baseHalfW, style, segIdx, nightLit
    );
    const sR = this.pushVert(
      firstMeta.mx, firstMeta.wy, firstMeta.wz,
      firstMeta.sideR[0] - firstMeta.t[0], firstMeta.sideR[1] - firstMeta.hC, firstMeta.sideR[2] + firstMeta.t[1],
      firstMeta.col[0], firstMeta.col[1], firstMeta.col[2], baseHalfW,
      1, -1, -baseHalfW, style, segIdx, nightLit
    );
    bodyLeftIdx.push(sL);
    bodyRightIdx.push(sR);

    for (let k = 0; k < m; k++) {
      const row = rows[k];
      bodyLeftIdx.push(
        this.pushVert(
          row.mx, row.wy, row.wz,
          row.sideL[0], row.sideL[1], row.sideL[2],
          row.col[0], row.col[1], row.col[2], baseHalfW,
          -1, 0, row.along, style, segIdx, nightLit
        )
      );
      bodyRightIdx.push(
        this.pushVert(
          row.mx, row.wy, row.wz,
          row.sideR[0], row.sideR[1], row.sideR[2],
          row.col[0], row.col[1], row.col[2], baseHalfW,
          1, 0, row.along, style, segIdx, nightLit
        )
      );
    }

    // End cap row
    const eL = this.pushVert(
      lastMeta.mx, lastMeta.wy, lastMeta.wz,
      lastMeta.sideL[0] + lastMeta.t[0], lastMeta.sideL[1] + lastMeta.hC, lastMeta.sideL[2] - lastMeta.t[1],
      lastMeta.col[0], lastMeta.col[1], lastMeta.col[2], baseHalfW,
      -1, 1, totalLen + baseHalfW, style, segIdx, nightLit
    );
    const eR = this.pushVert(
      lastMeta.mx, lastMeta.wy, lastMeta.wz,
      lastMeta.sideR[0] + lastMeta.t[0], lastMeta.sideR[1] + lastMeta.hC, lastMeta.sideR[2] - lastMeta.t[1],
      lastMeta.col[0], lastMeta.col[1], lastMeta.col[2], baseHalfW,
      1, 1, totalLen + baseHalfW, style, segIdx, nightLit
    );
    bodyLeftIdx.push(eL);
    bodyRightIdx.push(eR);

    for (let k = 0; k < bodyLeftIdx.length - 1; k++) {
      const aL = bodyLeftIdx[k];
      const aR = bodyRightIdx[k];
      const bR = bodyRightIdx[k + 1];
      const bL = bodyLeftIdx[k + 1];
      this.i.push(aL, aR, bR, aL, bR, bL);
    }
  }

  addEndpointPin(
    hf: Heightfield,
    mapX: number,
    mapY: number,
    radius: number,
    yLift: number,
    rgb: [number, number, number],
    segIdx: number
  ) {
    const hC = hf.sampleHeight(mapX, mapY);
    const wy = hC + yLift;
    const wz = hf.height - mapY;
    const eps = 0.8;
    const corners: [number, number][] = [
      [-1, -1], // SW
      [1, -1],  // SE
      [1, 1],   // NE
      [-1, 1],  // NW
    ];
    const base = this.v.length / 16;
    for (const [cx, cy] of corners) {
      const dy = clamp((hf.sampleHeight(mapX + cx * eps, mapY + cy * eps) - hC) / eps, -1.2, 1.2);
      this.pushVert(
        mapX, wy, wz,
        cx, dy, -cy,
        rgb[0], rgb[1], rgb[2], radius,
        cx, cy, 0, 6.0, segIdx, 0
      );
    }
    this.i.push(base, base + 1, base + 2, base, base + 2, base + 3);
  }

  build(): RibbonMeshData {
    return {
      vertices: new Float32Array(this.v),
      indices: new Uint32Array(this.i),
    };
  }
}

export function buildRibbonMesh(
  hf: Heightfield,
  segments: MapSegment[],
  builtIds: (string | number)[],
  builtItems: Record<string, BuiltItem>,
  liveSkiers: LiveSkiersSnapshot | null,
  options: {
    filter: string;
    showAvailable: boolean;
    selectedSegId: string | number | null;
    lens: MapLens;
  }
): RibbonMeshData {
  const rb = new RibbonMeshBuilder();
  if (options.filter === 'buildings') return rb.build();

  const isBuilt = (id: string | number) => builtIds.some((b) => sameId(b, id));
  const perSeg = liveSkiers?.perSegment ?? {};

  interface RibbonJob {
    pts: [number, number][];
    baseHalfW: number;
    yLift: number;
    style: number;
    segIdx: number;
    nightLit: number;
    baseRgb: [number, number, number];
    selected: boolean;
  }

  const layers: RibbonJob[][] = [[], [], [], [], []];

  segments.forEach((seg, segIdx) => {
    const built = isBuilt(seg.id);
    if (options.filter === 'lift' && seg.type !== 'lift') return;
    if (options.filter === 'slope' && seg.type === 'lift') return;
    if (options.filter === 'built' && !built) return;
    if (!built && !options.showAvailable) return;

    const rawPts = parsePoints(seg.points);
    if (rawPts.length < 2) return;

    const item = builtItems[String(seg.id)];
    const selected = options.selectedSegId !== null && sameId(options.selectedSegId, seg.id);
    const closed = built && item && item.status !== 'open';
    const isLift = seg.type === 'lift';

    let rgb = trailColor(seg);
    if (options.lens === 'traffic' && built) {
      rgb = heatLoadColor(perSeg[String(seg.id)]?.loadPct ?? 0);
    } else if (options.lens === 'snow' && built && !isLift) {
      rgb = snowColor(item?.snow_quality);
    }

    const orientedPts = isLift
      ? rawPts[0][0] <= rawPts[rawPts.length - 1][0]
        ? rawPts
        : [...rawPts].reverse()
      : rawPts[0][0] >= rawPts[rawPts.length - 1][0]
        ? rawPts
        : [...rawPts].reverse();

    const pts = smoothTrailPolyline(orientedPts, isLift);
    const nightLit = !isLift && built && !closed && item?.night_lit ? 1 : 0;

    let style: number;
    let baseHalfW: number;
    let yLift: number;
    let layerIdx: number;

    if (isLift) {
      style = built ? 3 : 4;
      baseHalfW = built ? 0.28 : 0.34;
      yLift = built ? 0.09 : 0.08;
      layerIdx = built ? 1 : 0;
      if (closed && !selected) {
        rgb = [rgb[0] * 0.62, rgb[1] * 0.62, rgb[2] * 0.62];
      }
    } else if (!built) {
      style = 1;
      baseHalfW = 0.38;
      yLift = 0.08;
      layerIdx = 0;
    } else if (closed) {
      style = 2;
      baseHalfW = 0.56;
      yLift = 0.11;
      layerIdx = 2;
    } else {
      const isXC = seg.type === 'crosscountry' || seg.difficulty === 'crosscountry';
      style = isXC ? 5 : 0;
      baseHalfW = 0.62;
      yLift = 0.12;
      layerIdx = 3;
    }

    if (selected) {
      layerIdx = 4;
      yLift = 0.15;
    }

    layers[layerIdx].push({
      pts,
      baseHalfW,
      yLift,
      style,
      segIdx,
      nightLit,
      baseRgb: rgb,
      selected,
    });
  });

  for (const bucket of layers) {
    for (const job of bucket) {
      rb.addPolyline(hf, job.pts, {
        baseHalfW: job.baseHalfW,
        yLift: job.yLift,
        style: job.style,
        segIdx: job.segIdx,
        nightLit: job.nightLit,
        baseRgb: job.baseRgb,
        lens: options.lens,
      });
      if (job.selected) {
        const start = job.pts[0];
        const end = job.pts[job.pts.length - 1];
        const pinRadius = Math.max(0.72, job.baseHalfW * 1.45);
        const startCol = options.lens === 'steepness' ? steepColor(hf.sampleSlopeDeg(start[1], start[0])) : job.baseRgb;
        const endCol = options.lens === 'steepness' ? steepColor(hf.sampleSlopeDeg(end[1], end[0])) : job.baseRgb;
        rb.addEndpointPin(hf, start[1], start[0], pinRadius, 0.17, startCol, job.segIdx);
        rb.addEndpointPin(hf, end[1], end[0], pinRadius, 0.17, endCol, job.segIdx);
      }
    }
  }

  return rb.build();
}

export interface LiftNode {
  mapX: number;
  mapY: number;
  groundY: number;
  topY: number;
  tx: number;
  ty: number;
}

export interface LiftProfile {
  nodes: LiftNode[];
  sags: number[];
  spans: number;
  cableOff: number;
  cableH: number;
  totalLen: number;
  height: number;
}

const LIFT_SPECS: Record<
  string,
  { cableH: number; cableOff: number; spanLen: number; minS: number; maxS: number; sagK: number; minClear: number }
> = {
  button: { cableH: 0.95, cableOff: 0.20, spanLen: 14, minS: 2, maxS: 7, sagK: 0.012, minClear: 0.55 },
  chair_fixed: { cableH: 1.55, cableOff: 0.28, spanLen: 16, minS: 2, maxS: 7, sagK: 0.018, minClear: 0.95 },
  chair_detach: { cableH: 1.68, cableOff: 0.34, spanLen: 19, minS: 2, maxS: 6, sagK: 0.020, minClear: 1.05 },
  gondola: { cableH: 1.85, cableOff: 0.36, spanLen: 22, minS: 2, maxS: 6, sagK: 0.022, minClear: 1.15 },
  cable_car: { cableH: 2.45, cableOff: 0.44, spanLen: 34, minS: 1, maxS: 4, sagK: 0.030, minClear: 1.45 },
};

export function buildLiftProfile(hf: Heightfield, pts: [number, number][], sub: string): LiftProfile {
  const spec = LIFT_SPECS[sub] || LIFT_SPECS.chair_fixed;
  let totalLen = 0;
  for (let k = 1; k < pts.length; k++) {
    totalLen += Math.hypot(pts[k][0] - pts[k - 1][0], pts[k][1] - pts[k - 1][1]);
  }
  const spans = clamp(Math.round(totalLen / spec.spanLen), spec.minS, spec.maxS);
  const nodes: LiftNode[] = [];
  for (let s = 0; s <= spans; s++) {
    const smp = samplePolylineAt(pts, s / spans);
    const groundY = hf.sampleHeight(smp.mapX, smp.mapY);
    nodes.push({
      mapX: smp.mapX,
      mapY: smp.mapY,
      groundY,
      topY: groundY + spec.cableH,
      tx: smp.tx,
      ty: smp.ty,
    });
  }

  for (let pass = 0; pass < 2; pass++) {
    for (let s = 0; s < spans; s++) {
      const a = nodes[s];
      const b = nodes[s + 1];
      const hMid = hf.sampleHeight((a.mapX + b.mapX) * 0.5, (a.mapY + b.mapY) * 0.5);
      const needed = hMid + spec.minClear + 0.12;
      const avgTop = (a.topY + b.topY) * 0.5;
      if (needed > avgTop) {
        const deficit = needed - avgTop;
        if (s > 0 && s + 1 < spans) {
          a.topY = Math.min(a.groundY + spec.cableH + 2.0, a.topY + deficit * 0.65);
          b.topY = Math.min(b.groundY + spec.cableH + 2.0, b.topY + deficit * 0.65);
        } else if (s + 1 < spans) {
          b.topY = Math.min(b.groundY + spec.cableH + 2.0, b.topY + deficit * 1.15);
        } else if (s > 0) {
          a.topY = Math.min(a.groundY + spec.cableH + 2.0, a.topY + deficit * 1.15);
        } else {
          a.topY = Math.min(a.groundY + spec.cableH + 1.5, a.topY + deficit * 0.65);
          b.topY = Math.min(b.groundY + spec.cableH + 1.5, b.topY + deficit * 0.65);
        }
      }
    }
  }

  const sags: number[] = new Array(spans);
  for (let s = 0; s < spans; s++) {
    const a = nodes[s];
    const b = nodes[s + 1];
    const spanDist = Math.hypot(b.mapX - a.mapX, b.mapY - a.mapY);
    const rawSag = Math.min(0.85, spanDist * spec.sagK);
    const hMid = hf.sampleHeight((a.mapX + b.mapX) * 0.5, (a.mapY + b.mapY) * 0.5);
    const maxSafe = Math.max(0.03, (a.topY + b.topY) * 0.5 - (hMid + spec.minClear));
    sags[s] = Math.min(rawSag, maxSafe);
  }

  return { nodes, sags, spans, cableOff: spec.cableOff, cableH: spec.cableH, totalLen, height: hf.height };
}

export function sampleLiftCable(
  profile: LiftProfile,
  t: number,
  sideSign: number
): { wx: number; wy: number; wz: number; tx: number; ty: number } {
  const ct = clamp(t, 0, 1);
  const scaled = ct * profile.spans;
  const si = Math.min(profile.spans - 1, Math.floor(scaled));
  const u = scaled - si;
  const a = profile.nodes[si];
  const b = profile.nodes[si + 1];
  const mx = lerp(a.mapX, b.mapX, u);
  const my = lerp(a.mapY, b.mapY, u);
  const wy = lerp(a.topY, b.topY, u) - 4 * u * (1 - u) * profile.sags[si];
  const dx = b.mapX - a.mapX;
  const dy = b.mapY - a.mapY;
  const dLen = Math.hypot(dx, dy) || 1;
  const tx = dx / dLen;
  const ty = dy / dLen;
  const offX = -ty * profile.cableOff * sideSign;
  const offZ = -tx * profile.cableOff * sideSign;
  return { wx: mx + offX, wy, wz: profile.height - my + offZ, tx, ty };
}

function addBullwheel(mb: MeshBuilder, cx: number, cy: number, cz: number, radius: number, rimCol: RGBA, steelCol: RGBA) {
  mb.cylinder(cx, cy - 0.04, cz, radius, radius, 0.08, 10, rimCol);
  mb.cylinder(cx, cy - 0.07, cz, radius * 0.28, radius * 0.28, 0.14, 8, steelCol);
  const s = radius * 0.92;
  mb.beam(cx - s, cy, cz, cx + s, cy, cz, 0.028, steelCol);
  mb.beam(cx, cy, cz - s, cx, cy, cz + s, 0.028, steelCol);
}

function addLiftStation(
  mb: MeshBuilder,
  node: LiftNode,
  dirSign: number,
  isBase: boolean,
  sub: string,
  profile: LiftProfile,
  cols: {
    concreteCol: RGBA;
    steelCol: RGBA;
    darkSteel: RGBA;
    shellCol: RGBA;
    roofCol: RGBA;
    trimCol: RGBA;
    glassCol: RGBA;
    kioskCol: RGBA;
  }
) {
  const nx = node.mapX;
  const groundY = node.groundY;
  const nz = profile.height - node.mapY;
  const localTop = node.topY - groundY;
  const yaw = Math.atan2(node.tx, -node.ty);
  const c = Math.cos(yaw);
  const s = Math.sin(yaw);

  const worldOf = (lx: number, lz: number): [number, number] => {
    const wz = lz * dirSign;
    return [nx + lx * c + wz * s, nz - lx * s + wz * c];
  };
  const obox = (lx: number, byRel: number, lz: number, sx: number, sy: number, sz: number, col: RGBA) => {
    const [gx, gz] = worldOf(lx, lz);
    mb.orientedBox(gx, groundY + byRel, gz, sx, sy, sz, yaw, col);
  };
  const boxes = (specs: [number, number, number, number, number, number, RGBA][]) => {
    for (const [lx, byRel, lz, sx, sy, sz, col] of specs) obox(lx, byRel, lz, sx, sy, sz, col);
  };
  const cyl = (lx: number, byRel: number, lz: number, rBot: number, rTop: number, h: number, segs: number, col: RGBA) => {
    const [gx, gz] = worldOf(lx, lz);
    mb.cylinder(gx, groundY + byRel, gz, rBot, rTop, h, segs, col);
  };

  const { concreteCol, steelCol, darkSteel, shellCol, roofCol, trimCol, glassCol, kioskCol } = cols;

  if (sub === 'button') {
    cyl(0, -0.22, 0, 0.34, 0.30, 0.28, 8, concreteCol);
    cyl(0, 0.04, 0, 0.085, 0.075, localTop + 0.06, 8, steelCol);
    obox(0, localTop - 0.06, 0, 0.58, 0.08, 0.16, darkSteel);
    addBullwheel(mb, nx, node.topY + 0.05, nz, 0.36, trimCol, steelCol);
    if (isBase) {
      boxes([
        [-0.78, -0.18, -0.14, 0.44, 0.62, 0.48, kioskCol],
        [-0.78, 0.14, -0.14, 0.46, 0.18, 0.24, glassCol],
        [-0.78, 0.44, -0.14, 0.50, 0.10, 0.54, roofCol],
        [-0.78, 0.54, -0.14, 0.52, 0.06, 0.56, trimCol],
      ]);
    }
    return;
  }

  if (sub === 'chair_fixed') {
    cyl(-0.32, -0.20, 0, 0.095, 0.075, localTop + 0.20, 8, darkSteel);
    cyl(0.32, -0.20, 0, 0.095, 0.075, localTop + 0.20, 8, darkSteel);
    addBullwheel(mb, nx, node.topY, nz, 0.44, trimCol, steelCol);
    boxes([
      [0, -0.18, isBase ? 0.35 : 0.25, isBase ? 0.92 : 0.86, 0.20, isBase ? 0.72 : 0.60, concreteCol],
      [0, localTop - 0.04, 0, 0.96, 0.10, 0.22, steelCol],
      [0, localTop + 0.06, -0.12, 0.42, 0.20, 0.32, shellCol],
    ]);
    if (isBase) {
      boxes([
        [-0.88, -0.20, -0.10, 0.54, 0.66, 0.56, kioskCol],
        [-0.88, 0.14, -0.10, 0.56, 0.20, 0.30, glassCol],
        [-0.88, 0.46, -0.10, 0.64, 0.11, 0.66, roofCol],
        [-0.88, 0.57, -0.10, 0.66, 0.06, 0.68, trimCol],
      ]);
    } else {
      boxes([
        [-0.74, -0.18, -0.08, 0.40, 0.56, 0.42, shellCol],
        [-0.74, 0.38, -0.08, 0.44, 0.08, 0.46, trimCol],
      ]);
    }
    return;
  }

  if (sub === 'chair_detach') {
    cyl(0, -0.26, 0, 0.44, 0.40, 0.30, 8, concreteCol);
    addBullwheel(mb, nx, node.topY - 0.04, nz, 0.40, trimCol, steelCol);
    boxes([
      [-0.16, -0.24, 0, 0.12, localTop + 0.22, 0.22, darkSteel],
      [0.16, -0.24, 0, 0.12, localTop + 0.22, 0.22, darkSteel],
      [0, localTop + 0.06, -0.08, 1.12, 0.24, 1.54, shellCol],
      [0, localTop + 0.11, -0.08, 1.15, 0.11, 1.10, glassCol],
      [0, localTop + 0.24, -0.08, 1.16, 0.07, 1.58, trimCol],
      [0, localTop + 0.31, -0.12, 0.86, 0.12, 1.24, roofCol],
    ]);
    if (isBase) {
      boxes([
        [-0.88, -0.20, -0.20, 0.48, 0.68, 0.56, shellCol],
        [-0.88, 0.14, -0.20, 0.50, 0.22, 0.44, glassCol],
        [-0.88, 0.48, -0.20, 0.56, 0.08, 0.64, roofCol],
      ]);
    }
    return;
  }

  if (sub === 'gondola') {
    addBullwheel(mb, nx, node.topY, nz, 0.40, trimCol, steelCol);
    boxes([
      [0, -0.28, -0.16, 1.32, 0.34, 1.66, concreteCol],
      [-0.52, 0.04, -0.22, 0.18, localTop - 0.02, 1.46, shellCol],
      [0.52, 0.04, -0.22, 0.18, localTop - 0.02, 1.46, shellCol],
      [0, 0.04, -0.78, 0.90, localTop - 0.02, 0.34, shellCol],
      [-0.54, 0.42, -0.24, 0.19, 0.46, 0.98, glassCol],
      [0.54, 0.42, -0.24, 0.19, 0.46, 0.98, glassCol],
      [0, localTop + 0.02, -0.06, 1.26, 0.18, 1.70, shellCol],
      [0, localTop + 0.20, -0.06, 1.30, 0.08, 1.74, trimCol],
      [0, localTop + 0.28, -0.12, 0.90, 0.14, 1.32, roofCol],
    ]);
    return;
  }

  // Cable Car: bold open-deck alpine tramway dock
  boxes([
    [0, -0.34, -0.22, 1.54, 0.44, 1.94, concreteCol],
    [-0.62, 0.08, -0.28, 0.24, localTop + 0.02, 1.66, shellCol],
    [0.62, 0.08, -0.28, 0.24, localTop + 0.02, 1.66, shellCol],
    [0, 0.08, -0.92, 1.04, localTop + 0.02, 0.42, shellCol],
    [-0.64, 0.66, -0.30, 0.25, 0.62, 1.12, glassCol],
    [0.64, 0.66, -0.30, 0.25, 0.62, 1.12, glassCol],
    [0, localTop + 0.10, -0.16, 1.50, 0.24, 1.92, shellCol],
    [0, localTop + 0.34, -0.16, 1.56, 0.10, 1.98, trimCol],
    [0, localTop + 0.44, -0.34, 0.96, 0.28, 1.24, roofCol],
  ]);
}

function addLiftTower(
  mb: MeshBuilder,
  hf: Heightfield,
  profile: LiftProfile,
  s: number,
  sub: string,
  concreteCol: RGBA,
  steelCol: RGBA,
  darkSteel: RGBA
) {
  const node = profile.nodes[s];
  const prev = profile.nodes[s - 1];
  const next = profile.nodes[s + 1];
  const gx = node.mapX;
  const gz = hf.height - node.mapY;
  const gY = node.groundY;
  const tY = node.topY;

  const spanDx = next.mapX - prev.mapX;
  const spanDy = next.mapY - prev.mapY;
  const hDist = Math.hypot(spanDx, spanDy) || 1;
  const tx = spanDx / hDist;
  const ty = spanDy / hDist;
  const slopePerUnit = (next.topY - prev.topY) / hDist;
  const rx = -ty;
  const rz = -tx;
  const offX = rx * profile.cableOff;
  const offZ = rz * profile.cableOff;

  if (sub === 'cable_car') {
    const legX = Math.max(0.55, profile.cableOff * 1.45);
    const legZ = 0.42;
    const topX = profile.cableOff * 0.72;
    const topZ = 0.10;
    const legTopY = tY - 0.10;
    const midY = lerp(gY, legTopY, 0.52);

    const corners: [number, number][] = [
      [-1, -1],
      [1, -1],
      [1, 1],
      [-1, 1],
    ];
    for (const [sx, sz] of corners) {
      const bx = gx + rx * legX * sx + tx * legZ * sz;
      const bz = gz + rz * legX * sx - ty * legZ * sz;
      const ux = gx + rx * topX * sx + tx * topZ * sz;
      const uz = gz + rz * topX * sx - ty * topZ * sz;
      mb.cylinder(bx, gY - 0.26, bz, 0.14, 0.11, 0.28, 6, concreteCol);
      mb.beam(bx, gY, bz, ux, legTopY, uz, 0.048, steelCol);
    }
    const midXSpan = lerp(legX, topX, 0.52);
    mb.beam(gx - rx * midXSpan, midY, gz - rz * midXSpan, gx + rx * midXSpan, midY, gz + rz * midXSpan, 0.036, steelCol);
    mb.beam(gx - offX * 1.18, tY, gz - offZ * 1.18, gx + offX * 1.18, tY, gz + offZ * 1.18, 0.065, darkSteel);
    for (const side of [-1, 1]) {
      const sx = gx + offX * side;
      const sz = gz + offZ * side;
      mb.beam(
        sx - tx * 0.26, tY - slopePerUnit * 0.26, sz + ty * 0.26,
        sx + tx * 0.26, tY + slopePerUnit * 0.26, sz - ty * 0.26,
        0.056,
        darkSteel
      );
    }
    return;
  }

  mb.cylinder(gx, gY - 0.28, gz, 0.19, 0.14, 0.36, 8, concreteCol);
  const rBot = sub === 'button' ? 0.095 : 0.135;
  mb.cylinder(gx, gY + 0.06, gz, rBot, rBot * 0.68, Math.max(0.25, tY - gY - 0.10), 8, steelCol);
  mb.beam(gx - offX * 1.18, tY - 0.04, gz - offZ * 1.18, gx + offX * 1.18, tY - 0.04, gz + offZ * 1.18, 0.042, steelCol);
  mb.beam(gx, tY - 0.40, gz, gx + offX * 0.88, tY - 0.05, gz + offZ * 0.88, 0.030, steelCol);
  mb.beam(gx, tY - 0.40, gz, gx - offX * 0.88, tY - 0.05, gz - offZ * 0.88, 0.030, steelCol);

  const halfBat = sub === 'button' ? 0.11 : sub === 'chair_fixed' ? 0.17 : 0.22;
  for (const side of [-1, 1]) {
    const sx = gx + offX * side;
    const sz = gz + offZ * side;
    mb.beam(
      sx - tx * halfBat, tY - slopePerUnit * halfBat, sz + ty * halfBat,
      sx + tx * halfBat, tY + slopePerUnit * halfBat, sz - ty * halfBat,
      0.045,
      darkSteel
    );
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
      // Compact snowmaking fan guns & slender night light poles placed cleanly outside the slope casing
      if (built && item) {
        const oriented = rawPts[0][0] >= rawPts[rawPts.length - 1][0] ? rawPts : [...rawPts].reverse();
        const smoothPts = smoothTrailPolyline(oriented, false);
        const smCount = Math.min(3, item.snowmakers_count || 0);
        for (let g = 0; g < smCount; g++) {
          const sample = samplePolylineAt(smoothPts, (g + 0.5) / Math.max(1, smCount));
          const [gx, gy, gz] = hf.toWorld(sample.mapX - sample.ty * 0.85, sample.mapY + sample.tx * 0.85, 0);
          mb.box(gx, gy - 0.1, gz, 0.22, 0.26, 0.22, [0.82, 0.86, 0.92, 0.06]);
          mb.box(gx, gy + 0.16, gz, 0.26, 0.24, 0.34, [0.22, 0.56, 0.96, item.snowmakers_active ? 0.32 : 0.05]);
        }
        if (item.night_lit && isNight) {
          for (let l = 0; l < 4; l++) {
            const sample = samplePolylineAt(smoothPts, (l + 0.5) / 4);
            const [lx, ly, lz] = hf.toWorld(sample.mapX + sample.ty * 0.85, sample.mapY - sample.tx * 0.85, 0);
            mb.box(lx, ly - 0.1, lz, 0.09, 1.15, 0.09, [0.34, 0.40, 0.50, 0.0]);
            mb.box(lx, ly + 1.05, lz, 0.24, 0.14, 0.24, [1.0, 0.88, 0.45, 0.85]);
          }
        }
      }
    } else {
      // Lifts always oriented ascending low->high
      const pts = rawPts[0][0] <= rawPts[rawPts.length - 1][0] ? rawPts : [...rawPts].reverse();
      if (!built) continue;

      const sub = item?.subtype || 'chair_fixed';
      const profile = buildLiftProfile(hf, pts, sub);

      const concreteCol: RGBA = [0.64, 0.68, 0.74, 0.04];
      const steelCol: RGBA = [0.66, 0.72, 0.80, 0.08];
      const darkSteel: RGBA = [0.26, 0.32, 0.40, 0.06];
      const shellCol: RGBA = closed ? [0.22, 0.25, 0.30, 0.04] : [0.24, 0.29, 0.38, 0.10];
      const roofCol: RGBA = closed ? [0.18, 0.21, 0.26, 0.04] : [0.20, 0.25, 0.34, 0.08];
      const trimCol: RGBA = selected
        ? [0.22, 0.78, 0.98, 0.55]
        : closed
          ? [0.52, 0.24, 0.24, 0.08]
          : [rgb[0], rgb[1], rgb[2], 0.24];
      const glassCol: RGBA = closed ? [0.18, 0.22, 0.28, 0.0] : [0.56, 0.80, 0.96, isNight ? 0.48 : 0.16];
      const kioskCol: RGBA = [0.42, 0.34, 0.26, 0.06];
      const stationCols = { concreteCol, steelCol, darkSteel, shellCol, roofCol, trimCol, glassCol, kioskCol };

      addLiftStation(mb, profile.nodes[0], 1, true, sub, profile, stationCols);
      addLiftStation(mb, profile.nodes[profile.spans], -1, false, sub, profile, stationCols);

      for (let s = 1; s < profile.spans; s++) {
        addLiftTower(mb, hf, profile, s, sub, concreteCol, steelCol, darkSteel);
      }

      const cableCol: RGBA = selected ? [0.24, 0.78, 0.98, 0.50] : [0.46, 0.52, 0.60, 0.10];
      const cableR = sub === 'button' ? 0.030 : sub === 'cable_car' ? 0.048 : sub === 'gondola' ? 0.038 : 0.034;
      const steps = profile.spans * 5;
      for (const side of [-1, 1]) {
        let pC = sampleLiftCable(profile, 0, side);
        for (let step = 1; step <= steps; step++) {
          const nC = sampleLiftCable(profile, step / steps, side);
          mb.beam(pC.wx, pC.wy, pC.wz, nC.wx, nC.wy, nC.wz, cableR, cableCol);
          if (sub === 'cable_car') {
            mb.beam(pC.wx, pC.wy - 0.14, pC.wz, nC.wx, nC.wy - 0.14, nC.wz, 0.022, cableCol);
          }
          pC = nC;
        }
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

      if (fac.kind === 'winter_sport') {
        if (fac.subType === 'ice_rink') {
          mb.box(wx, wy - 0.10, wz, 2.8, 0.22, 2.0, [0.85, 0.88, 0.92, 0.08]);
          mb.box(wx, wy + 0.12, wz, 2.45, 0.06, 1.65, [0.68, 0.88, 0.98, 0.32]);
          mb.box(wx - 1.25, wy + 0.12, wz, 0.12, 0.55, 0.12, winCol);
          mb.box(wx + 1.25, wy + 0.12, wz, 0.12, 0.55, 0.12, winCol);
        } else if (fac.subType === 'hockey_arena') {
          mb.box(wx, wy - 0.20, wz, 3.0, 1.25, 2.3, [0.78, 0.82, 0.88, 0.10]);
          mb.gableRoof(wx, wy + 1.05, wz, 3.15, 0.65, 2.45, [0.22, 0.42, 0.68, 0.16]);
          mb.box(wx, wy + 0.45, wz + 1.16, 1.8, 0.38, 0.08, winCol);
        } else if (fac.subType === 'cross_country') {
          mb.box(wx, wy - 0.20, wz, 1.9, 0.95, 1.6, [0.54, 0.38, 0.24, 0.08]);
          mb.gableRoof(wx, wy + 0.75, wz, 2.15, 0.65, 1.85, [0.16, 0.52, 0.48, 0.16]);
          mb.box(wx, wy + 0.35, wz + 0.81, 0.95, 0.35, 0.08, winCol);
        } else if (fac.subType === 'ski_jump') {
          mb.box(wx, wy - 0.10, wz - 0.75, 0.75, 2.1, 0.75, [0.72, 0.76, 0.84, 0.12]);
          mb.beam(wx, wy + 1.95, wz - 0.65, wx, wy + 0.25, wz + 0.95, 0.42, [0.88, 0.94, 1.0, 0.20]);
          mb.box(wx, wy - 0.05, wz + 1.35, 1.6, 0.12, 1.4, [0.92, 0.96, 1.0, 0.14]);
        } else {
          // bobsleigh_track
          mb.box(wx, wy - 0.15, wz - 0.85, 1.4, 0.95, 1.1, [0.36, 0.42, 0.52, 0.12]);
          mb.beam(wx, wy + 0.55, wz - 0.5, wx + 0.45, wy + 0.10, wz + 1.15, 0.48, [0.64, 0.86, 0.98, 0.28]);
          mb.box(wx, wy + 0.35, wz - 0.85, 0.7, 0.32, 1.15, winCol);
        }
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
  quad(x0, y0, z0, x1, y0, z0, x1, y0, z1, x0, y0, z1);
}

type UnitBoxSpec = [number, number, number, number, number, number, [number, number, number]];

function makeUnit(specs: UnitBoxSpec[]): UnitMeshData {
  const verts: number[] = [];
  const idx: number[] = [];
  for (const s of specs) buildUnitBox(verts, idx, s[0], s[1], s[2], s[3], s[4], s[5], s[6]);
  return { vertices: new Float32Array(verts), indices: new Uint16Array(idx) };
}

/** Instanced actor & carrier templates with y = 0 at exact cable grip point for lift vehicles */
export function buildUnitMeshes(): {
  skier: UnitMeshData;
  chair: UnitMeshData;
  bubbleChair: UnitMeshData;
  cabin: UnitMeshData;
  tram: UnitMeshData;
  surface: UnitMeshData;
  rider: UnitMeshData;
  snowcat: UnitMeshData;
  flake: UnitMeshData;
} {
  const TINT: [number, number, number] = [-1, -1, -1];
  const DARK: [number, number, number] = [0.22, 0.26, 0.34];
  const STEEL: [number, number, number] = [0.66, 0.72, 0.80];
  const SKI: [number, number, number] = [0.15, 0.20, 0.28];
  const BUBBLE: [number, number, number] = [0.46, 0.72, 0.92];
  return {
    skier: makeUnit([
      [-0.09, 0.0, 0, 0.06, 0.04, 0.52, SKI],
      [0.09, 0.0, 0, 0.06, 0.04, 0.52, SKI],
      [0, 0.04, 0, 0.24, 0.34, 0.18, TINT],
      [0, 0.38, 0, 0.17, 0.17, 0.17, [0.96, 0.86, 0.76]],
    ]),
    chair: makeUnit([
      [0, -0.03, 0, 0.09, 0.08, 0.12, STEEL],
      [0, -0.44, -0.06, 0.045, 0.44, 0.05, STEEL],
      [0, -0.46, 0.02, 0.56, 0.05, 0.22, TINT],
      [0, -0.41, -0.08, 0.56, 0.22, 0.05, TINT],
      [-0.23, -0.58, 0.09, 0.03, 0.16, 0.03, DARK],
      [0.23, -0.58, 0.09, 0.03, 0.16, 0.03, DARK],
      [0, -0.59, 0.11, 0.48, 0.03, 0.04, DARK],
    ]),
    bubbleChair: makeUnit([
      [0, -0.03, 0, 0.10, 0.09, 0.14, STEEL],
      [0, -0.46, -0.07, 0.05, 0.46, 0.055, STEEL],
      [0, -0.49, 0.02, 0.68, 0.055, 0.24, TINT],
      [0, -0.44, -0.09, 0.68, 0.24, 0.055, TINT],
      [0, -0.61, 0.12, 0.58, 0.03, 0.04, DARK],
      [0, -0.22, -0.06, 0.70, 0.16, 0.12, BUBBLE],
      [0, -0.09, 0.01, 0.68, 0.09, 0.22, BUBBLE],
    ]),
    cabin: makeUnit([
      [0, -0.03, 0, 0.10, 0.08, 0.14, STEEL],
      [0, -0.22, 0, 0.05, 0.22, 0.05, STEEL],
      [0, -0.28, 0, 0.34, 0.06, 0.36, [0.88, 0.92, 0.96]],
      [0, -0.48, 0, 0.42, 0.20, 0.44, BUBBLE],
      [0, -0.66, 0, 0.36, 0.18, 0.38, TINT],
      [-0.20, -0.63, 0, 0.04, 0.16, 0.18, DARK],
      [0.20, -0.63, 0, 0.04, 0.16, 0.18, DARK],
    ]),
    tram: makeUnit([
      [0, -0.03, 0, 0.14, 0.10, 0.48, DARK],
      [0, -0.40, -0.20, 0.07, 0.38, 0.07, STEEL],
      [0, -0.40, 0.20, 0.07, 0.38, 0.07, STEEL],
      [0, -0.44, 0, 0.50, 0.08, 0.88, [0.92, 0.95, 0.98]],
      [0, -0.72, 0, 0.54, 0.28, 0.92, [0.54, 0.78, 0.94]],
      [0, -0.72, 0, 0.55, 0.28, 0.06, STEEL],
      [0, -0.92, 0, 0.50, 0.20, 0.88, TINT],
    ]),
    surface: makeUnit([
      [0, -0.04, 0, 0.10, 0.08, 0.12, DARK],
      [0, -0.60, 0, 0.032, 0.60, 0.032, STEEL],
      [0, -0.63, 0, 0.28, 0.045, 0.09, TINT],
    ]),
    rider: makeUnit([
      [-0.08, -0.10, 0.06, 0.045, 0.025, 0.44, SKI],
      [0.08, -0.10, 0.06, 0.045, 0.025, 0.44, SKI],
      [0, -0.06, 0.05, 0.18, 0.09, 0.14, SKI],
      [0, 0.0, -0.01, 0.20, 0.24, 0.15, TINT],
      [0, 0.24, 0.0, 0.13, 0.12, 0.13, [0.96, 0.86, 0.76]],
    ]),
    snowcat: makeUnit([
      [0, 0, 0, 0.75, 0.26, 1.05, [0.14, 0.18, 0.24]],
      [0, 0.26, -0.05, 0.64, 0.34, 0.68, [0.95, 0.58, 0.08]],
      [0, 0.05, 0.62, 0.96, 0.22, 0.12, [0.78, 0.82, 0.88]],
    ]),
    flake: makeUnit([[0, -0.10, 0, 0.20, 0.20, 0.20, [0.94, 0.98, 1.0]]]),
  };
}
