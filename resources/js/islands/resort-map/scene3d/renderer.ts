import { parsePoints, sameId } from '../shared';
import type {
  BuiltItem,
  LiveSkiersSnapshot,
  MapConfig,
  MapLens,
  MapLighting,
  MapSegment,
  MapWeather,
  PlacedFacility,
} from '../types';
import {
  buildOverlayStaticMesh,
  buildTerrainMesh,
  buildUnitMeshes,
  type StaticMeshData,
  type UnitMeshData,
} from './geometry';
import { buildHeightfield, type Heightfield } from './heightfield';
import {
  clamp,
  distToSegment2D,
  lerp,
  mat4Invert,
  mat4LookAt,
  mat4Multiply,
  mat4Perspective,
  unprojectPoint,
} from './math';
import { compileProgram, INSTANCED_FS, INSTANCED_VS, SOLID_FS, SOLID_VS, TERRAIN_FS, TERRAIN_VS } from './shaders';
import { SkierSimulation } from './skierSim';

export type PickResult =
  | { kind: 'facility'; facility: PlacedFacility; mapX: number; mapY: number }
  | { kind: 'segment'; segment: MapSegment; mapX: number; mapY: number }
  | { kind: 'terrain'; mapX: number; mapY: number }
  | null;

export interface Scene3DState {
  segments: MapSegment[];
  builtIds: (string | number)[];
  builtItems: Record<string, BuiltItem>;
  facilities: PlacedFacility[];
  liveSkiers: LiveSkiersSnapshot | null;
  groomerActive: number;
  nightLitCount: number;
  weather: MapWeather | null;
  filter: string;
  showAvailable: boolean;
  showBuildings: boolean;
  showSkiers: boolean;
  selectedSegId: string | number | null;
  selectedFacilityKey: string | null;
  lens: MapLens;
  lighting: MapLighting;
  paused: boolean;
  placingMode: boolean;
}

interface InstancedGPU {
  vao: WebGLVertexArrayObject;
  vbo: WebGLBuffer;
  ibo: WebGLBuffer;
  instPosVbo: WebGLBuffer;
  instYawVbo: WebGLBuffer;
  instColVbo: WebGLBuffer;
  indexCount: number;
  count: number;
}

const LIGHT_PRESETS: Record<'day' | 'sunset' | 'night', { sun: [number, number, number]; sunCol: [number, number, number]; sky: [number, number, number]; gnd: [number, number, number]; fog: [number, number, number]; mode: number }> = {
  day: {
    sun: [0.55, 0.78, 0.42],
    sunCol: [1.0, 0.98, 0.94],
    sky: [0.48, 0.56, 0.68],
    gnd: [0.22, 0.27, 0.34],
    fog: [0.76, 0.85, 0.94],
    mode: 0,
  },
  sunset: {
    sun: [-0.72, 0.32, 0.45],
    sunCol: [1.0, 0.62, 0.34],
    sky: [0.38, 0.3, 0.44],
    gnd: [0.2, 0.16, 0.24],
    fog: [0.55, 0.4, 0.42],
    mode: 1,
  },
  night: {
    sun: [-0.25, 0.82, 0.35],
    sunCol: [0.26, 0.38, 0.62],
    sky: [0.14, 0.2, 0.32],
    gnd: [0.06, 0.08, 0.14],
    fog: [0.08, 0.12, 0.2],
    mode: 2,
  },
};

function resolveLighting(lighting: MapLighting, phase?: string): 'day' | 'sunset' | 'night' {
  if (lighting !== 'live') return lighting;
  if (phase === 'night_skiing' || phase === 'evening') return 'night';
  if (phase === 'afternoon') return 'sunset';
  return 'day';
}

export function createMap3DRenderer(
  canvas: HTMLCanvasElement,
  mapConfig: MapConfig,
  initialState: Scene3DState,
  callbacks: {
    onHover: (pick: PickResult, clientX: number, clientY: number) => void;
    onClick: (pick: PickResult) => void;
    onHdLoading: (loading: boolean) => void;
    onWebGLFailed: (reason: string) => void;
  }
) {
  const gl = canvas.getContext('webgl2', { antialias: true, alpha: false, powerPreference: 'high-performance' });
  if (!gl) {
    callbacks.onWebGLFailed('WebGL2 is unavailable in this browser.');
    return null;
  }

  let state = initialState;
  const hf: Heightfield = buildHeightfield(mapConfig, state.segments);
  const segPtsCache = new Map<string, [number, number][]>();
  for (const s of state.segments) {
    const pts = parsePoints(s.points);
    if (pts.length >= 2) segPtsCache.set(String(s.id), pts);
  }

  // Camera spherical coordinates around target (mapX, mapZ in world space)
  const W = mapConfig.width || 600;
  const H = mapConfig.height || 340;
  const cam = {
    tx: W * 0.42,
    ty: 18,
    tz: H * 0.58,
    yaw: 0.08,
    pitch: 0.62,
    dist: 300,
  };
  const targetCam = { ...cam };

  // Compute initial framing around player's built or visible runs
  function computeFitTarget(): { tx: number; ty: number; tz: number; dist: number } {
    const builtSegs = state.segments.filter((s) => state.builtIds.some((b) => sameId(b, s.id)));
    const pool = builtSegs.length > 0 ? builtSegs : state.segments;
    let minX = W, maxX = 0, minY = H, maxY = 0;
    let has = false;
    for (const s of pool) {
      const pts = segPtsCache.get(String(s.id));
      if (!pts) continue;
      for (const [my, mx] of pts) {
        has = true;
        if (mx < minX) minX = mx;
        if (mx > maxX) maxX = mx;
        if (my < minY) minY = my;
        if (my > maxY) maxY = my;
      }
    }
    if (!has) return { tx: W * 0.5, ty: 16, tz: H * 0.5, dist: 340 };
    const cx = (minX + maxX) * 0.5;
    const cy = (minY + maxY) * 0.5;
    const span = Math.max(85, Math.hypot(maxX - minX, maxY - minY));
    return {
      tx: cx,
      ty: hf.sampleHeight(cx, cy) * 0.8,
      tz: H - cy,
      dist: clamp(span * 1.15, 110, 520),
    };
  }

  const initialFit = computeFitTarget();
  Object.assign(cam, initialFit);
  Object.assign(targetCam, initialFit);

  let terrainProg: WebGLProgram;
  let solidProg: WebGLProgram;
  let instProg: WebGLProgram;
  try {
    terrainProg = compileProgram(gl, TERRAIN_VS, TERRAIN_FS);
    solidProg = compileProgram(gl, SOLID_VS, SOLID_FS);
    instProg = compileProgram(gl, INSTANCED_VS, INSTANCED_FS);
  } catch (err: any) {
    callbacks.onWebGLFailed(err?.message || 'Could not compile 3D shaders.');
    return null;
  }

  // Build Terrain VAO
  const terrainData = buildTerrainMesh(hf);
  const terrainVao = gl.createVertexArray()!;
  const terrainVbo = gl.createBuffer()!;
  const terrainIbo = gl.createBuffer()!;
  gl.bindVertexArray(terrainVao);
  gl.bindBuffer(gl.ARRAY_BUFFER, terrainVbo);
  gl.bufferData(gl.ARRAY_BUFFER, terrainData.vertices, gl.STATIC_DRAW);
  gl.bindBuffer(gl.ELEMENT_ARRAY_BUFFER, terrainIbo);
  gl.bufferData(gl.ELEMENT_ARRAY_BUFFER, terrainData.indices, gl.STATIC_DRAW);
  const TS = 9 * 4;
  gl.enableVertexAttribArray(0);
  gl.vertexAttribPointer(0, 3, gl.FLOAT, false, TS, 0);
  gl.enableVertexAttribArray(1);
  gl.vertexAttribPointer(1, 3, gl.FLOAT, false, TS, 12);
  gl.enableVertexAttribArray(2);
  gl.vertexAttribPointer(2, 2, gl.FLOAT, false, TS, 24);
  gl.enableVertexAttribArray(3);
  gl.vertexAttribPointer(3, 1, gl.FLOAT, false, TS, 32);
  gl.bindVertexArray(null);

  // Build Overlay Static VAO
  const solidVao = gl.createVertexArray()!;
  const solidVbo = gl.createBuffer()!;
  const solidIbo = gl.createBuffer()!;
  let solidIndexCount = 0;

  function uploadStaticOverlay() {
    const mesh: StaticMeshData = buildOverlayStaticMesh(
      hf,
      state.segments,
      state.builtIds,
      state.builtItems,
      state.facilities,
      state.liveSkiers,
      {
        filter: state.filter,
        showAvailable: state.showAvailable,
        showBuildings: state.showBuildings,
        selectedSegId: state.selectedSegId,
        selectedFacilityKey: state.selectedFacilityKey,
        lens: state.lens,
        lighting: resolveLighting(state.lighting, state.liveSkiers?.operatingPhase) as any,
      }
    );
    gl!.bindVertexArray(solidVao);
    gl!.bindBuffer(gl!.ARRAY_BUFFER, solidVbo);
    gl!.bufferData(gl!.ARRAY_BUFFER, mesh.vertices, gl!.DYNAMIC_DRAW);
    gl!.bindBuffer(gl!.ELEMENT_ARRAY_BUFFER, solidIbo);
    gl!.bufferData(gl!.ELEMENT_ARRAY_BUFFER, mesh.indices, gl!.DYNAMIC_DRAW);
    const SS = 10 * 4;
    gl!.enableVertexAttribArray(0);
    gl!.vertexAttribPointer(0, 3, gl!.FLOAT, false, SS, 0);
    gl!.enableVertexAttribArray(1);
    gl!.vertexAttribPointer(1, 3, gl!.FLOAT, false, SS, 12);
    gl!.enableVertexAttribArray(2);
    gl!.vertexAttribPointer(2, 4, gl!.FLOAT, false, SS, 24);
    gl!.bindVertexArray(null);
    solidIndexCount = mesh.indices.length;
  }
  uploadStaticOverlay();

  // Build Instanced Unit Meshes
  function makeInstancedGPU(unit: UnitMeshData): InstancedGPU {
    const vao = gl!.createVertexArray()!;
    const vbo = gl!.createBuffer()!;
    const ibo = gl!.createBuffer()!;
    const instPosVbo = gl!.createBuffer()!;
    const instYawVbo = gl!.createBuffer()!;
    const instColVbo = gl!.createBuffer()!;

    gl!.bindVertexArray(vao);
    gl!.bindBuffer(gl!.ARRAY_BUFFER, vbo);
    gl!.bufferData(gl!.ARRAY_BUFFER, unit.vertices, gl!.STATIC_DRAW);
    gl!.bindBuffer(gl!.ELEMENT_ARRAY_BUFFER, ibo);
    gl!.bufferData(gl!.ELEMENT_ARRAY_BUFFER, unit.indices, gl!.STATIC_DRAW);
    const US = 9 * 4;
    gl!.enableVertexAttribArray(0);
    gl!.vertexAttribPointer(0, 3, gl!.FLOAT, false, US, 0);
    gl!.enableVertexAttribArray(1);
    gl!.vertexAttribPointer(1, 3, gl!.FLOAT, false, US, 12);
    gl!.enableVertexAttribArray(2);
    gl!.vertexAttribPointer(2, 3, gl!.FLOAT, false, US, 24);

    gl!.bindBuffer(gl!.ARRAY_BUFFER, instPosVbo);
    gl!.enableVertexAttribArray(3);
    gl!.vertexAttribPointer(3, 3, gl!.FLOAT, false, 0, 0);
    gl!.vertexAttribDivisor(3, 1);

    gl!.bindBuffer(gl!.ARRAY_BUFFER, instYawVbo);
    gl!.enableVertexAttribArray(4);
    gl!.vertexAttribPointer(4, 2, gl!.FLOAT, false, 0, 0);
    gl!.vertexAttribDivisor(4, 1);

    gl!.bindBuffer(gl!.ARRAY_BUFFER, instColVbo);
    gl!.enableVertexAttribArray(5);
    gl!.vertexAttribPointer(5, 4, gl!.FLOAT, false, 0, 0);
    gl!.vertexAttribDivisor(5, 1);

    gl!.bindVertexArray(null);
    return { vao, vbo, ibo, instPosVbo, instYawVbo, instColVbo, indexCount: unit.indices.length, count: 0 };
  }

  const unitMeshes = buildUnitMeshes();
  const skierGpu = makeInstancedGPU(unitMeshes.skier);
  const carrierGpu = makeInstancedGPU(unitMeshes.carrier);
  const snowcatGpu = makeInstancedGPU(unitMeshes.snowcat);
  const flakeGpu = makeInstancedGPU(unitMeshes.flake);

  const sim = new SkierSimulation(
    hf,
    mapConfig,
    state.segments,
    state.builtIds,
    state.builtItems,
    state.facilities,
    state.liveSkiers,
    state.groomerActive,
    state.weather,
    state.showSkiers
  );

  function uploadSimInstances() {
    const syncOne = (gpu: InstancedGPU, pos: Float32Array, ys: Float32Array, col: Float32Array, cnt: number) => {
      gpu.count = cnt;
      if (cnt === 0) return;
      gl!.bindBuffer(gl!.ARRAY_BUFFER, gpu.instPosVbo);
      gl!.bufferData(gl!.ARRAY_BUFFER, pos, gl!.DYNAMIC_DRAW);
      gl!.bindBuffer(gl!.ARRAY_BUFFER, gpu.instYawVbo);
      gl!.bufferData(gl!.ARRAY_BUFFER, ys, gl!.DYNAMIC_DRAW);
      gl!.bindBuffer(gl!.ARRAY_BUFFER, gpu.instColVbo);
      gl!.bufferData(gl!.ARRAY_BUFFER, col, gl!.DYNAMIC_DRAW);
    };
    syncOne(skierGpu, sim.skierPos, sim.skierYawScale, sim.skierColor, sim.skierCount);
    syncOne(carrierGpu, sim.carrierPos, sim.carrierYawScale, sim.carrierColor, sim.carrierCount);
    syncOne(snowcatGpu, sim.snowcatPos, sim.snowcatYawScale, sim.snowcatColor, sim.snowcatCount);
    syncOne(flakeGpu, sim.flakePos, sim.flakeYawScale, sim.flakeColor, sim.flakeCount);
  }
  uploadSimInstances();

  // Progressive clean map texture loader
  const mapTex = gl.createTexture()!;
  gl.bindTexture(gl.TEXTURE_2D, mapTex);
  gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, 1, 1, 0, gl.RGBA, gl.UNSIGNED_BYTE, new Uint8Array([215, 224, 234, 255]));
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
  gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);

  const anisoExt = gl.getExtension('EXT_texture_filter_anisotropic');
  const maxAniso = anisoExt ? gl.getParameter(anisoExt.MAX_TEXTURE_MAX_ANISOTROPY_EXT) : 1;

  function uploadTexture(img: HTMLImageElement) {
    if (destroyed) return;
    const maxTexSize = gl!.getParameter(gl!.MAX_TEXTURE_SIZE) || 4096;
    let source: TexImageSource = img;
    if (img.width > maxTexSize || img.height > maxTexSize) {
      const ratio = Math.min(maxTexSize / img.width, maxTexSize / img.height);
      const c = document.createElement('canvas');
      c.width = Math.max(1, Math.floor(img.width * ratio));
      c.height = Math.max(1, Math.floor(img.height * ratio));
      c.getContext('2d')?.drawImage(img, 0, 0, c.width, c.height);
      source = c;
    }
    gl!.bindTexture(gl!.TEXTURE_2D, mapTex);
    gl!.texImage2D(gl!.TEXTURE_2D, 0, gl!.RGBA, gl!.RGBA, gl!.UNSIGNED_BYTE, source);
    gl!.generateMipmap(gl!.TEXTURE_2D);
    gl!.texParameteri(gl!.TEXTURE_2D, gl!.TEXTURE_MIN_FILTER, gl!.LINEAR_MIPMAP_LINEAR);
    gl!.texParameteri(gl!.TEXTURE_2D, gl!.TEXTURE_MAG_FILTER, gl!.LINEAR);
    if (anisoExt && maxAniso > 1) {
      gl!.texParameterf(gl!.TEXTURE_2D, anisoExt.TEXTURE_MAX_ANISOTROPY_EXT, Math.min(8, maxAniso));
    }
  }

  let destroyed = false;
  let fullRequested = false;
  const texSource = mapConfig.cleanImage || mapConfig.image;
  const webp = mapConfig.cleanWebp ?? mapConfig.webp ?? { low: false, med: false, full: false };
  const basePath = texSource.replace(/\.jpg$/i, '');
  const lowUrl = basePath + (webp.low ? '_low.webp' : '_low.jpg');
  const medUrl = basePath + (webp.med ? '_med.webp' : '_med.jpg');
  const fullUrl = webp.full ? basePath + '.webp' : texSource;

  const lowImg = new Image();
  lowImg.onload = () => uploadTexture(lowImg);
  lowImg.src = lowUrl;

  const medImg = new Image();
  medImg.onload = () => uploadTexture(medImg);
  medImg.src = medUrl;

  function maybeLoadFullTex() {
    if (fullRequested || cam.dist > 180) return;
    fullRequested = true;
    callbacks.onHdLoading(true);
    const fullImg = new Image();
    fullImg.onload = () => {
      uploadTexture(fullImg);
      callbacks.onHdLoading(false);
    };
    fullImg.onerror = () => callbacks.onHdLoading(false);
    fullImg.src = fullUrl;
  }

  // Matrices cached for CPU picking
  const projMat = new Float32Array(16);
  const viewMat = new Float32Array(16);
  const vpMat = new Float32Array(16);
  const invVpMat = new Float32Array(16);
  let camPos: [number, number, number] = [cam.tx, 180, cam.tz + 200];

  function updateMatrices() {
    const aspect = canvas.width / Math.max(1, canvas.height);
    mat4Perspective(projMat, 0.72, aspect, 1.5, 2200);
    const horiz = Math.cos(cam.pitch) * cam.dist;
    const vert = Math.sin(cam.pitch) * cam.dist;
    const ex = cam.tx + Math.sin(cam.yaw) * horiz;
    const ey = Math.max(hf.sampleHeight(clamp(cam.tx, 0, W), clamp(H - cam.tz, 0, H)) + 4, cam.ty + vert);
    const ez = cam.tz + Math.cos(cam.yaw) * horiz;
    camPos = [ex, ey, ez];
    mat4LookAt(viewMat, ex, ey, ez, cam.tx, cam.ty, cam.tz);
    mat4Multiply(vpMat, projMat, viewMat);
    mat4Invert(invVpMat, vpMat);
  }

  function resizeCanvas() {
    const rect = canvas.getBoundingClientRect();
    const dpr = Math.min(2, window.devicePixelRatio || 1);
    const nw = Math.max(1, Math.round(rect.width * dpr));
    const nh = Math.max(1, Math.round(rect.height * dpr));
    if (canvas.width !== nw || canvas.height !== nh) {
      canvas.width = nw;
      canvas.height = nh;
      gl!.viewport(0, 0, nw, nh);
    }
  }

  function pickAt(clientX: number, clientY: number): PickResult {
    const rect = canvas.getBoundingClientRect();
    if (rect.width <= 0 || rect.height <= 0) return null;
    updateMatrices();
    const ndcX = ((clientX - rect.left) / rect.width) * 2 - 1;
    const ndcY = 1 - ((clientY - rect.top) / rect.height) * 2;
    const pNear = unprojectPoint(invVpMat, ndcX, ndcY, -1);
    const pFar = unprojectPoint(invVpMat, ndcX, ndcY, 1);
    const dx = pFar[0] - pNear[0];
    const dy = pFar[1] - pNear[1];
    const dz = pFar[2] - pNear[2];

    // March ray along terrain bounds
    let prevT = 0;
    let prevDiff = pNear[1] - hf.sampleHeight(pNear[0], H - pNear[2]);
    let hitT = -1;
    const steps = 42;
    for (let s = 1; s <= steps; s++) {
      const t = s / steps;
      const rx = pNear[0] + dx * t;
      const ry = pNear[1] + dy * t;
      const rz = pNear[2] + dz * t;
      const diff = ry - hf.sampleHeight(clamp(rx, 0, W), clamp(H - rz, 0, H));
      if (prevDiff > 0 && diff <= 0) {
        let lo = prevT;
        let hi = t;
        for (let b = 0; b < 5; b++) {
          const mid = (lo + hi) * 0.5;
          const mx = pNear[0] + dx * mid;
          const my = pNear[1] + dy * mid;
          const mz = pNear[2] + dz * mid;
          if (my - hf.sampleHeight(clamp(mx, 0, W), clamp(H - mz, 0, H)) > 0) lo = mid;
          else hi = mid;
        }
        hitT = (lo + hi) * 0.5;
        break;
      }
      prevT = t;
      prevDiff = diff;
    }

    if (hitT < 0) return null;
    const hitX = pNear[0] + dx * hitT;
    const hitZ = pNear[2] + dz * hitT;
    const mapX = clamp(hitX, 0, W);
    const mapY = clamp(H - hitZ, 0, H);

    if (hitX < -10 || hitX > W + 10 || hitZ < -10 || hitZ > H + 10) return null;
    if (state.placingMode) {
      return { kind: 'terrain', mapX, mapY };
    }

    // 1. Check facilities first when visible
    if (state.showBuildings && (state.filter === 'all' || state.filter === 'buildings' || state.filter === 'built')) {
      let bestFac: PlacedFacility | null = null;
      let bestDist = Math.max(4.8, cam.dist * 0.022);
      for (const fac of state.facilities) {
        const d = Math.hypot(fac.mapX - mapX, fac.mapY - mapY);
        if (d < bestDist) {
          bestDist = d;
          bestFac = fac;
        }
      }
      if (bestFac) {
        return { kind: 'facility', facility: bestFac, mapX, mapY };
      }
    }

    // 2. Check segments
    if (state.filter !== 'buildings') {
      let bestSeg: MapSegment | null = null;
      let bestDist = Math.max(3.8, cam.dist * 0.02);
      for (const seg of state.segments) {
        const built = state.builtIds.some((b) => sameId(b, seg.id));
        if (state.filter === 'lift' && seg.type !== 'lift') continue;
        if (state.filter === 'slope' && seg.type === 'lift') continue;
        if (state.filter === 'built' && !built) continue;
        if (!built && !state.showAvailable) continue;
        const pts = segPtsCache.get(String(seg.id));
        if (!pts) continue;
        for (let i = 1; i < pts.length; i++) {
          const d = distToSegment2D(mapX, mapY, pts[i - 1][1], pts[i - 1][0], pts[i][1], pts[i][0]);
          if (d < bestDist) {
            bestDist = d;
            bestSeg = seg;
          }
        }
      }
      if (bestSeg) {
        return { kind: 'segment', segment: bestSeg, mapX, mapY };
      }
    }

    return { kind: 'terrain', mapX, mapY };
  }

  // Pointer & touch navigation
  let dragMode: 'pan' | 'orbit' | null = null;
  let lastPx = 0;
  let lastPy = 0;
  let downPx = 0;
  let downPy = 0;
  let activePointers = new Map<number, { x: number; y: number }>();
  let lastPinchDist = 0;
  let lastPinchAngle = 0;

  const onPointerDown = (e: PointerEvent) => {
    canvas.setPointerCapture(e.pointerId);
    activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    if (activePointers.size === 1) {
      dragMode = e.button === 2 || e.button === 1 || e.shiftKey ? 'orbit' : 'pan';
      lastPx = downPx = e.clientX;
      lastPy = downPy = e.clientY;
    } else if (activePointers.size === 2) {
      dragMode = null;
      const pts = Array.from(activePointers.values());
      lastPinchDist = Math.hypot(pts[1].x - pts[0].x, pts[1].y - pts[0].y);
      lastPinchAngle = Math.atan2(pts[1].y - pts[0].y, pts[1].x - pts[0].x);
    }
  };

  const onPointerMove = (e: PointerEvent) => {
    if (activePointers.has(e.pointerId)) {
      activePointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
    }
    if (activePointers.size === 2) {
      const pts = Array.from(activePointers.values());
      const dist = Math.hypot(pts[1].x - pts[0].x, pts[1].y - pts[0].y);
      const angle = Math.atan2(pts[1].y - pts[0].y, pts[1].x - pts[0].x);
      if (lastPinchDist > 5) {
        targetCam.dist = clamp(targetCam.dist * (lastPinchDist / Math.max(5, dist)), 38, 650);
      }
      let da = angle - lastPinchAngle;
      if (da > Math.PI) da -= Math.PI * 2;
      if (da < -Math.PI) da += Math.PI * 2;
      targetCam.yaw += da * 0.9;
      lastPinchDist = dist;
      lastPinchAngle = angle;
      return;
    }
    if (dragMode) {
      const dx = e.clientX - lastPx;
      const dy = e.clientY - lastPy;
      lastPx = e.clientX;
      lastPy = e.clientY;
      if (dragMode === 'orbit') {
        targetCam.yaw -= dx * 0.0065;
        targetCam.pitch = clamp(targetCam.pitch + dy * 0.0055, 0.22, 1.48);
      } else {
        const panScale = targetCam.dist * 0.0017;
        const cy = Math.cos(targetCam.yaw);
        const sy = Math.sin(targetCam.yaw);
        targetCam.tx = clamp(targetCam.tx - (dx * cy + dy * sy) * panScale, 0, W);
        targetCam.tz = clamp(targetCam.tz - (-dx * sy + dy * cy) * panScale, 0, H);
        targetCam.ty = hf.sampleHeight(targetCam.tx, clamp(H - targetCam.tz, 0, H)) * 0.85;
      }
      return;
    }
    const p = pickAt(e.clientX, e.clientY);
    callbacks.onHover(p, e.clientX, e.clientY);
  };

  const onPointerUp = (e: PointerEvent) => {
    const moved = Math.hypot(e.clientX - downPx, e.clientY - downPy);
    const wasSingle = activePointers.size === 1 && dragMode === 'pan';
    activePointers.delete(e.pointerId);
    if (activePointers.size === 0) dragMode = null;
    if (wasSingle && moved < 6) {
      callbacks.onClick(pickAt(e.clientX, e.clientY));
    }
  };

  const onPointerCancel = (e: PointerEvent) => {
    activePointers.delete(e.pointerId);
    if (activePointers.size === 0) dragMode = null;
  };

  const onWheel = (e: WheelEvent) => {
    e.preventDefault();
    const factor = Math.exp(clamp(e.deltaY, -180, 180) * 0.0016);
    targetCam.dist = clamp(targetCam.dist * factor, 38, 650);
  };

  const onContextMenu = (e: MouseEvent) => e.preventDefault();

  canvas.addEventListener('pointerdown', onPointerDown);
  window.addEventListener('pointermove', onPointerMove);
  window.addEventListener('pointerup', onPointerUp);
  window.addEventListener('pointercancel', onPointerCancel);
  canvas.addEventListener('wheel', onWheel, { passive: false });
  canvas.addEventListener('contextmenu', onContextMenu);

  const ro = new ResizeObserver(resizeCanvas);
  ro.observe(canvas);
  resizeCanvas();

  let raf = 0;
  let prevTime = performance.now();

  function frame(now: number) {
    if (destroyed) return;
    const dt = (now - prevTime) / 1000;
    prevTime = now;

    // Smooth camera interpolation
    const damp = 1 - Math.exp(-12 * clamp(dt, 0, 0.05));
    cam.tx = lerp(cam.tx, targetCam.tx, damp);
    cam.ty = lerp(cam.ty, targetCam.ty, damp);
    cam.tz = lerp(cam.tz, targetCam.tz, damp);
    cam.yaw = lerp(cam.yaw, targetCam.yaw, damp);
    cam.pitch = lerp(cam.pitch, targetCam.pitch, damp);
    cam.dist = lerp(cam.dist, targetCam.dist, damp);

    maybeLoadFullTex();

    if (!state.paused) {
      sim.step(dt);
      uploadSimInstances();
    }

    updateMatrices();

    const lightKey = resolveLighting(state.lighting, state.liveSkiers?.operatingPhase);
    const lp = LIGHT_PRESETS[lightKey];

    gl!.enable(gl!.DEPTH_TEST);
    gl!.enable(gl!.CULL_FACE);
    gl!.clearColor(lp.fog[0], lp.fog[1], lp.fog[2], 1.0);
    gl!.clear(gl!.COLOR_BUFFER_BIT | gl!.DEPTH_BUFFER_BIT);

    // 1. Draw Terrain
    gl!.useProgram(terrainProg);
    gl!.uniformMatrix4fv(gl!.getUniformLocation(terrainProg, 'uViewProj'), false, vpMat);
    gl!.uniform3fv(gl!.getUniformLocation(terrainProg, 'uSunDir'), lp.sun);
    gl!.uniform3fv(gl!.getUniformLocation(terrainProg, 'uSunColor'), lp.sunCol);
    gl!.uniform3fv(gl!.getUniformLocation(terrainProg, 'uSkyColor'), lp.sky);
    gl!.uniform3fv(gl!.getUniformLocation(terrainProg, 'uGroundColor'), lp.gnd);
    gl!.uniform3fv(gl!.getUniformLocation(terrainProg, 'uFogColor'), lp.fog);
    gl!.uniform3fv(gl!.getUniformLocation(terrainProg, 'uCameraPos'), camPos);
    gl!.uniform1f(gl!.getUniformLocation(terrainProg, 'uLightMode'), lp.mode);
    gl!.uniform1f(gl!.getUniformLocation(terrainProg, 'uContours'), state.lens === 'steepness' ? 1.0 : 0.0);
    gl!.uniform1f(gl!.getUniformLocation(terrainProg, 'uNightLit'), state.nightLitCount > 0 ? 1.0 : 0.25);
    gl!.activeTexture(gl!.TEXTURE0);
    gl!.bindTexture(gl!.TEXTURE_2D, mapTex);
    gl!.uniform1i(gl!.getUniformLocation(terrainProg, 'uMapTex'), 0);
    gl!.bindVertexArray(terrainVao);
    gl!.drawElements(gl!.TRIANGLES, terrainData.indices.length, gl!.UNSIGNED_INT, 0);

    // 2. Draw Slopes, Lifts & Buildings
    if (solidIndexCount > 0) {
      gl!.useProgram(solidProg);
      gl!.uniformMatrix4fv(gl!.getUniformLocation(solidProg, 'uViewProj'), false, vpMat);
      gl!.uniform3fv(gl!.getUniformLocation(solidProg, 'uSunDir'), lp.sun);
      gl!.uniform3fv(gl!.getUniformLocation(solidProg, 'uSunColor'), lp.sunCol);
      gl!.uniform3fv(gl!.getUniformLocation(solidProg, 'uSkyColor'), lp.sky);
      gl!.uniform3fv(gl!.getUniformLocation(solidProg, 'uGroundColor'), lp.gnd);
      gl!.uniform3fv(gl!.getUniformLocation(solidProg, 'uFogColor'), lp.fog);
      gl!.uniform3fv(gl!.getUniformLocation(solidProg, 'uCameraPos'), camPos);
      gl!.uniform1f(gl!.getUniformLocation(solidProg, 'uLightMode'), lp.mode);
      gl!.bindVertexArray(solidVao);
      gl!.drawElements(gl!.TRIANGLES, solidIndexCount, gl!.UNSIGNED_INT, 0);
    }

    // 3. Draw Instanced Actors
    gl!.useProgram(instProg);
    gl!.uniformMatrix4fv(gl!.getUniformLocation(instProg, 'uViewProj'), false, vpMat);
    gl!.uniform3fv(gl!.getUniformLocation(instProg, 'uSunDir'), lp.sun);
    gl!.uniform3fv(gl!.getUniformLocation(instProg, 'uSunColor'), lp.sunCol);
    gl!.uniform3fv(gl!.getUniformLocation(instProg, 'uSkyColor'), lp.sky);
    gl!.uniform3fv(gl!.getUniformLocation(instProg, 'uGroundColor'), lp.gnd);
    gl!.uniform1f(gl!.getUniformLocation(instProg, 'uLightMode'), lp.mode);

    const drawInst = (g: InstancedGPU) => {
      if (g.count <= 0) return;
      gl!.bindVertexArray(g.vao);
      gl!.drawElementsInstanced(gl!.TRIANGLES, g.indexCount, gl!.UNSIGNED_SHORT, 0, g.count);
    };
    drawInst(carrierGpu);
    drawInst(skierGpu);
    drawInst(snowcatGpu);
    drawInst(flakeGpu);

    gl!.bindVertexArray(null);
    raf = requestAnimationFrame(frame);
  }

  raf = requestAnimationFrame(frame);

  return {
    updateState(next: Scene3DState) {
      const rebuildSim =
        next.builtIds !== state.builtIds ||
        next.liveSkiers !== state.liveSkiers ||
        next.showSkiers !== state.showSkiers ||
        next.facilities !== state.facilities ||
        next.groomerActive !== state.groomerActive ||
        next.weather !== state.weather;
      state = next;
      uploadStaticOverlay();
      if (rebuildSim) {
        sim.rebuild(
          state.segments,
          state.builtIds,
          state.builtItems,
          state.facilities,
          state.liveSkiers,
          state.groomerActive,
          state.weather,
          state.showSkiers
        );
        uploadSimInstances();
      }
    },
    fit() {
      const f = computeFitTarget();
      targetCam.tx = f.tx;
      targetCam.ty = f.ty;
      targetCam.tz = f.tz;
      targetCam.dist = f.dist;
      targetCam.yaw = 0.08;
      targetCam.pitch = 0.62;
    },
    setTilt(preset: '3d' | 'top') {
      targetCam.pitch = preset === 'top' ? 1.44 : 0.62;
      if (preset === 'top') targetCam.yaw = 0;
    },
    zoomBy(factor: number) {
      targetCam.dist = clamp(targetCam.dist * factor, 38, 650);
    },
    focusPoint(mapX: number, mapY: number, dist = 115) {
      targetCam.tx = clamp(mapX, 0, W);
      targetCam.tz = clamp(H - mapY, 0, H);
      targetCam.ty = hf.sampleHeight(targetCam.tx, clamp(mapY, 0, H));
      targetCam.dist = Math.min(targetCam.dist, dist);
    },
    focusSegment(segId: string | number) {
      const pts = segPtsCache.get(String(segId));
      if (!pts || pts.length === 0) return;
      const mid = pts[Math.floor(pts.length / 2)];
      this.focusPoint(mid[1], mid[0], 130);
    },
    destroy() {
      destroyed = true;
      cancelAnimationFrame(raf);
      ro.disconnect();
      canvas.removeEventListener('pointerdown', onPointerDown);
      window.removeEventListener('pointermove', onPointerMove);
      window.removeEventListener('pointerup', onPointerUp);
      window.removeEventListener('pointercancel', onPointerCancel);
      canvas.removeEventListener('wheel', onWheel);
      canvas.removeEventListener('contextmenu', onContextMenu);
      gl.getExtension('WEBGL_lose_context')?.loseContext();
    },
  };
}
