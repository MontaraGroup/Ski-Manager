import type { LiftSwapOption } from './LiftSwapDialog';

export type MapViewMode = '3d' | '2d';
export type MapLens = 'trails' | 'traffic' | 'snow' | 'steepness';
export type MapLighting = 'day' | 'sunset' | 'night' | 'live';

export interface MapSegment {
  id: number | string;
  name?: string;
  type: string;
  sector?: number | string;
  difficulty?: string;
  length_meters?: number;
  points: string | [number, number][];
  active?: number;
}

export interface Sector {
  id: number;
  resort_map?: string;
  name: string;
  color?: string;
  boundary_points?: string | [number, number][];
  visible?: number;
  released?: number;
  sort_order?: number;
}

export interface SlopeTerrainParkInfo {
  id: number;
  name: string;
  park_type: string;
  size: string;
  condition_pct: number;
  daily_visitors: number;
  status: string;
}

export interface BuiltItem {
  id: number;
  segment_id: number | string;
  item_type: string;
  subtype?: string;
  name?: string;
  level?: number;
  length_meters?: number;
  capacity?: number;
  difficulty?: string;
  sector?: number | string;
  status?: string;
  condition_pct?: number;
  snow_quality?: string;
  snow_depth?: number;
  made_snow_cm?: number;
  snow_target_cm?: number;
  groomed_day?: number;
  salvage?: number;
  seats?: number;
  /** Lifts: running cost per day, mechanic assigned, scenic upgrade, swaps */
  run_cost?: number;
  swaps?: LiftSwapOption[];
  mechanic_name?: string | null;
  scenic?: { ticket_price: number; revenue_per_day: number } | null;
  /** Slopes: snowmakers, attached terrain park, night lighting */
  snowmakers_count?: number;
  snowmakers_active?: number;
  terrain_park?: SlopeTerrainParkInfo | null;
  night_lit?: boolean;
}

export interface MapBuilding {
  id: number;
  building_type: string;
  typeLabel: string;
  icon: string;
  route: string;
  name: string;
  level: number;
  capacity: number;
  revenue_per_day: number;
  upkeep_per_day: number;
  condition_pct: number;
  status: string;
  map_x: number | null;
  map_y: number | null;
  resort_map: string | null;
  guests: number;
  occupancyPct: number;
}

export interface MapParking {
  id: number;
  parking_type: string;
  typeLabel: string;
  icon: string;
  name: string;
  capacity: number;
  occupied: number;
  occupancyPct: number;
  daily_revenue: number;
  condition_pct: number;
  status: string;
  map_x: number | null;
  map_y: number | null;
  resort_map: string | null;
}

export interface MapTerrainPark {
  id: number;
  name: string;
  park_type: string;
  size: string;
  condition_pct: number;
  daily_visitors: number;
  status: string;
  slope_id: number | null;
}

export interface PlacedFacility {
  key: string;
  kind: 'building' | 'parking' | 'terrain_park';
  id: number;
  subType: string;
  label: string;
  typeLabel: string;
  icon: string;
  route: string;
  status: string;
  level: number;
  conditionPct: number;
  occupancyPct: number;
  capacity: number;
  currentCount: number;
  dailyRevenue: number;
  dailyUpkeep: number;
  mapX: number;
  mapY: number;
  customPlaced: boolean;
  slopeId?: number | null;
  clusterCount: number;
  clusteredFacilities?: PlacedFacility[];
}

export interface MapWeather {
  condition_name: string;
  temp: number;
  tempFormatted: string;
  snow_base: number;
  snowBaseFormatted: string;
  snowfall: number;
  wind: number;
  isWinter: boolean;
}

export interface SegmentTraffic {
  skiers: number;
  loadPct: number;
  queue: number;
}

export interface LiveSkiersSnapshot {
  liveTotal: number;
  onSlopes: number;
  onLifts: number;
  inVillage: number;
  dailyProjected: number;
  dailyLastTick: number;
  liftUtilizationPct: number;
  resortOpen: boolean;
  operatingPhase: string;
  nightLit: boolean;
  serverNow: number;
  perSegment: Record<string, SegmentTraffic>;
  perBuilding: Record<string, { guests: number; occupancyPct: number }>;
}

export interface MapConfig {
  name: string;
  location: string;
  image: string;
  cleanImage?: string;
  admin_image?: string;
  width: number;
  height: number;
  /** Which sizes also exist as .webp (smaller); the engine uses those and the JPEGs for the rest */
  webp?: { low: boolean; med: boolean; full: boolean };
  cleanWebp?: { low: boolean; med: boolean; full: boolean };
  /** 256 px tiles (scripts/make_map_tiles.py); null until the server has built them */
  tiles?: { url: string; maxZoom: number } | null;
}

export interface EconomyV2Config {
  capacity: Record<string, number>;
  maxSeats: Record<string, number>;
  liftRun: Record<string, number>;
  slopeRun: number;
  perDay: number;
  liftKwh: Record<string, number>;
}

export interface BuildCosts {
  liftPerM: Record<string, number>;
  /** Keyed by seat count; JSON object keys arrive as strings */
  seatMult: Record<string, number>;
  seatOptions: Record<string, number[]>;
  slopePerM: number;
}

export interface ResortMapProps {
  resortMap: string;
  resortMaps: Record<string, MapConfig>;
  mapConfig: MapConfig;
  segments: MapSegment[];
  sectors: Sector[];
  builtSegmentIds: (string | number)[];
  builtItems: Record<string, BuiltItem>;
  releasedSectorIds: number[];
  userCash: number;
  userCashText: string;
  isAdmin: boolean;
  map3dEnabled?: boolean;
  resortOpen?: boolean;
  buildings?: MapBuilding[];
  parking?: MapParking[];
  terrainParks?: MapTerrainPark[];
  groomerFleet?: { active: number; total: number };
  nightLighting?: { activeCount: number; totalCoverage: number };
  weather?: MapWeather | null;
  liveSkiers?: LiveSkiersSnapshot | null;
  liftCount: number;
  slopeCount: number;
  segmentCount: number;
  distUnit: string;
  isImperial: boolean;
  economyV2: EconomyV2Config | null;
  /** ResortMap::calcCost's table, so the page shows what the server charges */
  costs: BuildCosts;
  /** Next nightly settle (ISO 8601) */
  settleAt: string;
  csrf: {
    name: string;
    hash: string;
  };
}

export interface EditorState {
  active: boolean;
  mode: 'draw' | 'edit' | 'sector_boundary' | null;
  type: 'lift' | 'slope';
  segId: number | string | null;
  sectorId: number | null;
  name: string;
  sector: number;
  slopeType: string;
  difficulty: string;
  liveLength: number;
  pointCount: number;
  appendMode: boolean;
  snapEnabled: boolean;
  showSectorPolygons: boolean;
  snapStatus: string | null;
}
