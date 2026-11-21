import { parsePoints } from '../shared';
import type { MapConfig, MapSegment } from '../types';
import { clamp, lerp, normalize3, type Vec3 } from './math';

export interface Heightfield {
  cols: number;
  rows: number;
  width: number;
  height: number;
  data: Float32Array;
  sampleHeight: (mapX: number, mapY: number) => number;
  sampleNormal: (mapX: number, mapY: number) => Vec3;
  sampleSlopeDeg: (mapX: number, mapY: number) => number;
  /** Converts map coords [mapY, mapX] to 3D world coords [worldX, worldY, worldZ] */
  toWorld: (mapX: number, mapY: number, yOffset?: number) => Vec3;
}

// Smooth deterministic 2D noise for subtle alpine ridge relief
function hash2(ix: number, iy: number): number {
  const n = Math.sin(ix * 127.1 + iy * 311.7) * 43758.5453;
  return n - Math.floor(n);
}

function smoothNoise(x: number, y: number): number {
  const ix = Math.floor(x);
  const iy = Math.floor(y);
  const fx = x - ix;
  const fy = y - iy;
  const ux = fx * fx * (3 - 2 * fx);
  const uy = fy * fy * (3 - 2 * fy);
  const a = hash2(ix, iy);
  const b = hash2(ix + 1, iy);
  const c = hash2(ix, iy + 1);
  const d = hash2(ix + 1, iy + 1);
  return lerp(lerp(a, b, ux), lerp(c, d, ux), uy);
}

function ridgeFbm(x: number, y: number): number {
  let sum = 0;
  let amp = 0.6;
  let freq = 1;
  for (let o = 0; o < 3; o++) {
    const n = smoothNoise(x * freq, y * freq);
    const ridge = 1 - Math.abs(n * 2 - 1);
    sum += ridge * ridge * amp;
    freq *= 2.05;
    amp *= 0.45;
  }
  return sum;
}

const GRADE_SCALE: Record<string, number> = {
  green: 0.65,
  blue: 1.0,
  black: 1.35,
  double_black: 1.65,
};

/**
 * Constructs a smooth 128x76 elevation field over [0..width] x [0..height].
 * Combines a base valley-to-alpine slope, ridge relief, and soft elevation constraints
 * solved from lift ascents and slope descents so every run flows downhill cleanly.
 */
export function buildHeightfield(mapConfig: MapConfig, segments: MapSegment[]): Heightfield {
  const cols = 128;
  const rows = 76;
  const width = mapConfig.width || 600;
  const height = mapConfig.height || 340;
  const data = new Float32Array(cols * rows);
  const bias = new Float32Array(cols * rows);
  const weight = new Float32Array(cols * rows);

  // 1. Seed alpine valley baseline (mapY = 0 base -> mapY = height high alpine)
  for (let r = 0; r < rows; r++) {
    const ny = r / (rows - 1); // 0 = base (mapY=0), 1 = upper mountain (mapY=H)
    for (let c = 0; c < cols; c++) {
      const nx = c / (cols - 1);
      const idx = r * cols + c;
      const valleyFloor = clamp((ny - 0.1) / 0.82, 0, 1);
      const profile = Math.pow(valleyFloor, 1.18) * 52;
      // Gently crown central ridges while keeping far edges calm
      const edgeTaper = Math.sin(nx * Math.PI);
      const ridge = ridgeFbm(nx * 4.5 + 1.3, ny * 3.1 + 0.7) * (8 + 16 * ny) * (0.45 + 0.55 * edgeTaper);
      data[idx] = profile + ridge;
    }
  }

  // 2. Accumulate elevation constraints along every lift & slope polyline
  const addPointConstraint = (mx: number, my: number, targetGain: number, w: number) => {
    const c = clamp(Math.round((mx / width) * (cols - 1)), 0, cols - 1);
    const r = clamp(Math.round((my / height) * (rows - 1)), 0, rows - 1);
    const radius = 3;
    for (let dr = -radius; dr <= radius; dr++) {
      const rr = r + dr;
      if (rr < 0 || rr >= rows) continue;
      for (let dc = -radius; dc <= radius; dc++) {
        const cc = c + dc;
        if (cc < 0 || cc >= cols) continue;
        const falloff = Math.exp(-(dr * dr + dc * dc) / 4.2);
        const idx = rr * cols + cc;
        bias[idx] += (data[idx] + targetGain) * w * falloff;
        weight[idx] += w * falloff;
      }
    }
  };

  for (const seg of segments) {
    const pts = parsePoints(seg.points);
    if (pts.length < 2) continue;
    const isLift = seg.type === 'lift';
    const lenFactor = clamp((Number(seg.length_meters) || 1200) / 1800, 0.4, 2.2);
    const mult = isLift ? 1.1 : GRADE_SCALE[seg.difficulty || 'blue'] ?? 1.0;

    // Lifts ascend pts[0]->pts[last]; slopes descend pts[0]->pts[last]
    for (let i = 0; i < pts.length; i++) {
      const t = i / (pts.length - 1);
      const elevDelta = isLift ? (t - 0.35) * 14 * lenFactor : (0.55 - t) * 12 * lenFactor * mult;
      addPointConstraint(pts[i][1], pts[i][0], elevDelta, isLift ? 0.5 : 0.35);
    }
  }

  for (let i = 0; i < data.length; i++) {
    if (weight[i] > 0.001) {
      data[i] = lerp(data[i], clamp(bias[i] / weight[i], 0, 86), 0.38);
    }
  }

  // 3. Multi-pass smoothing so terrain slopes continuously with no sharp artifacts
  const scratch = new Float32Array(data.length);
  for (let pass = 0; pass < 4; pass++) {
    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) {
        let sum = 0;
        let cnt = 0;
        for (let dr = -1; dr <= 1; dr++) {
          const rr = r + dr;
          if (rr < 0 || rr >= rows) continue;
          for (let dc = -1; dc <= 1; dc++) {
            const cc = c + dc;
            if (cc < 0 || cc >= cols) continue;
            const w = dr === 0 && dc === 0 ? 4 : dr === 0 || dc === 0 ? 2 : 1;
            sum += data[rr * cols + cc] * w;
            cnt += w;
          }
        }
        scratch[r * cols + c] = sum / cnt;
      }
    }
    data.set(scratch);
  }

  const sampleHeight = (mapX: number, mapY: number): number => {
    const fx = clamp((mapX / width) * (cols - 1), 0, cols - 1.0001);
    const fy = clamp((mapY / height) * (rows - 1), 0, rows - 1.0001);
    const c0 = Math.floor(fx);
    const r0 = Math.floor(fy);
    const tx = fx - c0;
    const ty = fy - r0;
    const c1 = Math.min(cols - 1, c0 + 1);
    const r1 = Math.min(rows - 1, r0 + 1);
    const h00 = data[r0 * cols + c0];
    const h10 = data[r0 * cols + c1];
    const h01 = data[r1 * cols + c0];
    const h11 = data[r1 * cols + c1];
    return lerp(lerp(h00, h10, tx), lerp(h01, h11, tx), ty);
  };

  // Remember worldZ = height - mapY, so dz = -dMapY when computing normal
  const sampleNormal = (mapX: number, mapY: number): Vec3 => {
    const eps = 2.5;
    const hL = sampleHeight(mapX - eps, mapY);
    const hR = sampleHeight(mapX + eps, mapY);
    const hD = sampleHeight(mapX, mapY - eps); // +worldZ direction
    const hU = sampleHeight(mapX, mapY + eps); // -worldZ direction
    const dx = (hR - hL) / (2 * eps);
    const dz = (hD - hU) / (2 * eps);
    return normalize3(-dx, 1.0, -dz);
  };

  const sampleSlopeDeg = (mapX: number, mapY: number): number => {
    const n = sampleNormal(mapX, mapY);
    const cosA = clamp(n[1], 0, 1);
    // Exaggerate perceived pitch slightly so topographic steepness lens shows realistic blue/red/black bands
    return Math.min(62, (Math.acos(cosA) * 180) / Math.PI * 1.45);
  };

  const toWorld = (mapX: number, mapY: number, yOffset = 0): Vec3 => [
    mapX,
    sampleHeight(mapX, mapY) + yOffset,
    height - mapY,
  ];

  return { cols, rows, width, height, data, sampleHeight, sampleNormal, sampleSlopeDeg, toWorld };
}
