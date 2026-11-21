export type Vec3 = [number, number, number];

export function clamp(v: number, lo: number, hi: number): number {
  return v < lo ? lo : v > hi ? hi : v;
}

export function lerp(a: number, b: number, t: number): number {
  return a + (b - a) * t;
}

export function normalize3(x: number, y: number, z: number): Vec3 {
  const len = Math.hypot(x, y, z) || 1;
  return [x / len, y / len, z / len];
}

export function mat4Perspective(out: Float32Array, fovyRad: number, aspect: number, near: number, far: number): Float32Array {
  const f = 1.0 / Math.tan(fovyRad * 0.5);
  const nf = 1 / (near - far);
  out.fill(0);
  out[0] = f / Math.max(0.01, aspect);
  out[5] = f;
  out[10] = (far + near) * nf;
  out[11] = -1;
  out[14] = 2 * far * near * nf;
  return out;
}

export function mat4LookAt(
  out: Float32Array,
  ex: number,
  ey: number,
  ez: number,
  tx: number,
  ty: number,
  tz: number
): Float32Array {
  let zx = ex - tx;
  let zy = ey - ty;
  let zz = ez - tz;
  let len = Math.hypot(zx, zy, zz);
  if (len === 0) {
    zz = 1;
  } else {
    zx /= len;
    zy /= len;
    zz /= len;
  }

  // up = (0, 1, 0)
  let xx = 1 * zz - 0 * zy; // zz
  let xy = 0 * zx - 0 * zz; // 0
  let xz = 0 * zy - 1 * zx; // -zx
  len = Math.hypot(xx, xy, xz);
  if (len === 0) {
    xx = 1;
    xy = 0;
    xz = 0;
  } else {
    xx /= len;
    xy /= len;
    xz /= len;
  }

  const yx = zy * xz - zz * xy;
  const yy = zz * xx - zx * xz;
  const yz = zx * xy - zy * xx;

  out[0] = xx;
  out[1] = yx;
  out[2] = zx;
  out[3] = 0;
  out[4] = xy;
  out[5] = yy;
  out[6] = zy;
  out[7] = 0;
  out[8] = xz;
  out[9] = yz;
  out[10] = zz;
  out[11] = 0;
  out[12] = -(xx * ex + xy * ey + xz * ez);
  out[13] = -(yx * ex + yy * ey + yz * ez);
  out[14] = -(zx * ex + zy * ey + zz * ez);
  out[15] = 1;
  return out;
}

export function mat4Multiply(out: Float32Array, a: Float32Array, b: Float32Array): Float32Array {
  for (let col = 0; col < 4; col++) {
    const b0 = b[col * 4];
    const b1 = b[col * 4 + 1];
    const b2 = b[col * 4 + 2];
    const b3 = b[col * 4 + 3];
    out[col * 4] = a[0] * b0 + a[4] * b1 + a[8] * b2 + a[12] * b3;
    out[col * 4 + 1] = a[1] * b0 + a[5] * b1 + a[9] * b2 + a[13] * b3;
    out[col * 4 + 2] = a[2] * b0 + a[6] * b1 + a[10] * b2 + a[14] * b3;
    out[col * 4 + 3] = a[3] * b0 + a[7] * b1 + a[11] * b2 + a[15] * b3;
  }
  return out;
}

export function mat4Invert(out: Float32Array, m: Float32Array): boolean {
  const a00 = m[0], a01 = m[1], a02 = m[2], a03 = m[3];
  const a10 = m[4], a11 = m[5], a12 = m[6], a13 = m[7];
  const a20 = m[8], a21 = m[9], a22 = m[10], a23 = m[11];
  const a30 = m[12], a31 = m[13], a32 = m[14], a33 = m[15];

  const b00 = a00 * a11 - a01 * a10;
  const b01 = a00 * a12 - a02 * a10;
  const b02 = a00 * a13 - a03 * a10;
  const b03 = a01 * a12 - a02 * a11;
  const b04 = a01 * a13 - a03 * a11;
  const b05 = a02 * a13 - a03 * a12;
  const b06 = a20 * a31 - a21 * a30;
  const b07 = a20 * a32 - a22 * a30;
  const b08 = a20 * a33 - a23 * a30;
  const b09 = a21 * a32 - a22 * a31;
  const b10 = a21 * a33 - a23 * a31;
  const b11 = a22 * a33 - a23 * a32;

  let det = b00 * b11 - b01 * b10 + b02 * b09 + b03 * b08 - b04 * b07 + b05 * b06;
  if (!det) return false;
  det = 1.0 / det;

  out[0] = (a11 * b11 - a12 * b10 + a13 * b09) * det;
  out[1] = (a02 * b10 - a01 * b11 - a03 * b09) * det;
  out[2] = (a31 * b05 - a32 * b04 + a33 * b03) * det;
  out[3] = (a22 * b04 - a21 * b05 - a23 * b03) * det;
  out[4] = (a12 * b08 - a10 * b11 - a13 * b07) * det;
  out[5] = (a00 * b11 - a02 * b08 + a03 * b07) * det;
  out[6] = (a32 * b02 - a30 * b05 - a33 * b01) * det;
  out[7] = (a20 * b05 - a22 * b02 + a23 * b01) * det;
  out[8] = (a10 * b10 - a11 * b08 + a13 * b06) * det;
  out[9] = (a01 * b08 - a00 * b10 - a03 * b06) * det;
  out[10] = (a30 * b04 - a31 * b02 + a33 * b00) * det;
  out[11] = (a21 * b02 - a20 * b04 - a23 * b00) * det;
  out[12] = (a11 * b07 - a10 * b09 - a12 * b06) * det;
  out[13] = (a00 * b09 - a01 * b07 + a02 * b06) * det;
  out[14] = (a31 * b01 - a30 * b03 - a32 * b00) * det;
  out[15] = (a20 * b03 - a21 * b01 + a22 * b00) * det;
  return true;
}

export function unprojectPoint(invVP: Float32Array, ndcX: number, ndcY: number, ndcZ: number): Vec3 {
  const w = invVP[3] * ndcX + invVP[7] * ndcY + invVP[11] * ndcZ + invVP[15];
  const iw = Math.abs(w) > 1e-6 ? 1 / w : 1;
  return [
    (invVP[0] * ndcX + invVP[4] * ndcY + invVP[8] * ndcZ + invVP[12]) * iw,
    (invVP[1] * ndcX + invVP[5] * ndcY + invVP[9] * ndcZ + invVP[13]) * iw,
    (invVP[2] * ndcX + invVP[6] * ndcY + invVP[10] * ndcZ + invVP[14]) * iw,
  ];
}

export interface PolylineSample {
  mapY: number;
  mapX: number;
  tx: number;
  ty: number;
  length: number;
}

export function samplePolylineAt(pts: [number, number][], frac: number): PolylineSample {
  if (pts.length === 0) return { mapY: 0, mapX: 0, tx: 1, ty: 0, length: 0 };
  if (pts.length === 1) return { mapY: pts[0][0], mapX: pts[0][1], tx: 1, ty: 0, length: 0 };

  let total = 0;
  const cum: number[] = [0];
  for (let i = 1; i < pts.length; i++) {
    total += Math.hypot(pts[i][0] - pts[i - 1][0], pts[i][1] - pts[i - 1][1]);
    cum.push(total);
  }
  if (total < 1e-4) {
    return { mapY: pts[0][0], mapX: pts[0][1], tx: 1, ty: 0, length: 0 };
  }
  const target = clamp(frac, 0, 1) * total;
  let seg = 0;
  for (let i = 1; i < cum.length; i++) {
    if (cum[i] >= target) {
      seg = i - 1;
      break;
    }
  }
  const segLen = Math.max(1e-4, cum[seg + 1] - cum[seg]);
  const t = (target - cum[seg]) / segLen;
  const a = pts[seg];
  const b = pts[seg + 1];
  const dy = b[0] - a[0];
  const dx = b[1] - a[1];
  const dLen = Math.hypot(dx, dy) || 1;
  return {
    mapY: lerp(a[0], b[0], t),
    mapX: lerp(a[1], b[1], t),
    tx: dx / dLen,
    ty: dy / dLen,
    length: total,
  };
}

export function distToSegment2D(px: number, py: number, ax: number, ay: number, bx: number, by: number): number {
  const vx = bx - ax;
  const vy = by - ay;
  const len2 = vx * vx + vy * vy;
  if (len2 === 0) return Math.hypot(px - ax, py - ay);
  const t = clamp(((px - ax) * vx + (py - ay) * vy) / len2, 0, 1);
  return Math.hypot(px - (ax + t * vx), py - (ay + t * vy));
}

export function smoothTrailPolyline(rawPts: [number, number][], isLift: boolean, stepUnits = 1.8): [number, number][] {
  if (rawPts.length < 2) return rawPts;
  const clean: [number, number][] = [rawPts[0]];
  for (let i = 1; i < rawPts.length; i++) {
    const prev = clean[clean.length - 1];
    if (Math.hypot(rawPts[i][0] - prev[0], rawPts[i][1] - prev[1]) >= 0.05) {
      clean.push(rawPts[i]);
    }
  }
  if (clean.length < 2) return rawPts;

  const out: [number, number][] = [];
  const pushPoint = (y: number, x: number) => {
    const last = out[out.length - 1];
    if (!last || Math.hypot(y - last[0], x - last[1]) >= 0.05) {
      out.push([y, x]);
    }
  };

  if (isLift || clean.length === 2) {
    const linearStep = Math.max(2.2, stepUnits);
    for (let i = 0; i < clean.length - 1; i++) {
      const a = clean[i];
      const b = clean[i + 1];
      const dist = Math.hypot(b[0] - a[0], b[1] - a[1]);
      const sub = clamp(Math.ceil(dist / linearStep), 1, 24);
      for (let s = 0; s < sub; s++) {
        const t = s / sub;
        pushPoint(lerp(a[0], b[0], t), lerp(a[1], b[1], t));
      }
    }
    const end = clean[clean.length - 1];
    out.push([end[0], end[1]]);
    return out;
  }

  const n = clean.length;
  const pPre: [number, number] = [2 * clean[0][0] - clean[1][0], 2 * clean[0][1] - clean[1][1]];
  const pPost: [number, number] = [2 * clean[n - 1][0] - clean[n - 2][0], 2 * clean[n - 1][1] - clean[n - 2][1]];

  const evalCatmull = (
    p0: [number, number],
    p1: [number, number],
    p2: [number, number],
    p3: [number, number],
    t: number,
    t0: number,
    t1: number,
    t2: number,
    t3: number
  ): [number, number] => {
    const d10 = Math.max(1e-5, t1 - t0);
    const d21 = Math.max(1e-5, t2 - t1);
    const d32 = Math.max(1e-5, t3 - t2);
    const d20 = Math.max(1e-5, t2 - t0);
    const d31 = Math.max(1e-5, t3 - t1);
    const interp = (k: 0 | 1) => {
      const a1 = ((t1 - t) / d10) * p0[k] + ((t - t0) / d10) * p1[k];
      const a2 = ((t2 - t) / d21) * p1[k] + ((t - t1) / d21) * p2[k];
      const a3 = ((t3 - t) / d32) * p2[k] + ((t - t2) / d32) * p3[k];
      const b1 = ((t2 - t) / d20) * a1 + ((t - t0) / d20) * a2;
      const b2 = ((t3 - t) / d31) * a2 + ((t - t1) / d31) * a3;
      return ((t2 - t) / d21) * b1 + ((t - t1) / d21) * b2;
    };
    return [interp(0), interp(1)];
  };

  for (let i = 0; i < n - 1; i++) {
    const p0 = i === 0 ? pPre : clean[i - 1];
    const p1 = clean[i];
    const p2 = clean[i + 1];
    const p3 = i + 2 < n ? clean[i + 2] : pPost;

    const dt0 = Math.pow(Math.max(1e-4, Math.hypot(p1[0] - p0[0], p1[1] - p0[1])), 0.5);
    const dt1 = Math.pow(Math.max(1e-4, Math.hypot(p2[0] - p1[0], p2[1] - p1[1])), 0.5);
    const dt2 = Math.pow(Math.max(1e-4, Math.hypot(p3[0] - p2[0], p3[1] - p2[1])), 0.5);
    const t0 = 0;
    const t1 = dt0;
    const t2 = t1 + dt1;
    const t3 = t2 + dt2;

    const spanDist = Math.hypot(p2[0] - p1[0], p2[1] - p1[1]);
    const steps = clamp(Math.ceil(spanDist / stepUnits), 2, 16);
    for (let s = 0; s < steps; s++) {
      const t = lerp(t1, t2, s / steps);
      const [py, px] = evalCatmull(p0, p1, p2, p3, t, t0, t1, t2, t3);
      pushPoint(py, px);
    }
  }

  const end = clean[n - 1];
  if (out.length === 0 || Math.hypot(end[0] - out[out.length - 1][0], end[1] - out[out.length - 1][1]) >= 0.05) {
    out.push([end[0], end[1]]);
  } else {
    out[out.length - 1] = [end[0], end[1]];
  }
  return out;
}
