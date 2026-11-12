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

export interface BuiltItem {
  id: number;
  segment_id: number | string;
  item_type: string;
  subtype?: string;
  name?: string;
  length_meters?: number;
  capacity?: number;
  difficulty?: string;
  sector?: number | string;
  status?: string;
  snow_quality?: string;
  salvage?: number;
}

export interface MapConfig {
  name: string;
  location: string;
  image: string;
  admin_image?: string;
  width: number;
  height: number;
  /** Which sizes also exist as .webp (smaller); the engine uses those and the JPEGs for the rest */
  webp?: { low: boolean; med: boolean; full: boolean };
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
