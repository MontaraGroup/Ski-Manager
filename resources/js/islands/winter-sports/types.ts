export type VenueAddon = {
  key: string;
  name: string;
  icon: string;
  desc: string;
  cost: number;
  costText: string;
  upkeepText: string;
  kwh: number;
  installed: boolean;
};

export type VenueEstimate = {
  guests: number;
  capacity: number;
  ticketText: string;
  addonText: string;
  contractText: string;
  revenueText: string;
  upkeepText: string;
  netText: string;
  kwh: number;
  visitorBoost: number;
  repBonus: number;
  weatherNote: string | null;
  highlight: string;
};

export type IceRinkSpecific = {
  slots: { morning: string; afternoon: string; evening: string };
  sessionOptions: { key: string; label: string; desc: string; eveningOnly: boolean; needsLights: boolean; locked: boolean }[];
  rentalPrice: number;
  hasSkateShop: boolean;
  hasChiller: boolean;
  hasLights: boolean;
};

export type CrossCountrySpecific = {
  loops: {
    key: string;
    name: string;
    km: number;
    difficulty: 'green' | 'blue' | 'black';
    cost: number;
    costText: string;
    upkeepText: string;
    capacity: number;
    draw: number;
    desc: string;
    built: boolean;
  }[];
  builtKm: number;
  mapNordicKm: number;
  totalKm: number;
  mapTrails: { name: string; km: number }[];
  grooming: string;
  groomingOptions: { key: string; label: string; desc: string; active: boolean }[];
  rentalPrice: number;
  hasWaxBench: boolean;
  hasSnowSupport: boolean;
  snowBaseCm: number | null;
};

export type HockeyArenaSpecific = {
  seats: number;
  bookedMatch: string;
  matchesHosted: number;
  matchOptions: {
    key: string;
    name: string;
    desc: string;
    bookingCost: number;
    costText: string;
    fillPct: number;
    rep: number;
    active: boolean;
    locked: boolean;
    lockReason: string | null;
  }[];
  hasDasherSponsors: boolean;
};

export type SkiJumpSpecific = {
  hills: {
    key: string;
    name: string;
    kPoint: number;
    maxM: number;
    cost: number;
    costText: string;
    upkeepText: string;
    capacity: number;
    draw: number;
    desc: string;
    built: boolean;
  }[];
  allocation: string;
  allocationOptions: { key: string; label: string; desc: string; active: boolean }[];
  windKmh: number | null;
  safeWindKmh: number;
  hillRecordM: number;
  hasSummerMat: boolean;
};

export type BobsleighSpecific = {
  mode: string;
  modeOptions: { key: string; label: string; desc: string; active: boolean }[];
  cooling: string;
  coolingOptions: { key: string; label: string; desc: string; costText: string; kwh: number; maxTemp: number; active: boolean }[];
  tempC: number | null;
  topSpeedKmh: number;
  hasSunShades: boolean;
};

export type HydratedVenue = {
  id: number;
  name: string;
  level: number;
  levelName: string;
  maxLevel: number;
  status: string;
  condition_pct: number;
  ice_quality: number;
  ticket_price: number;
  min_price: number;
  max_price: number;
  est: VenueEstimate;
  lastGuests: number;
  lastTicketText: string;
  lastAddonText: string;
  totalRevenueText: string;
  nextLevel: number | null;
  nextLevelName: string | null;
  upgradeCost: number;
  upgradeCostText: string;
  repairCost: number;
  repairCostText: string;
  resurfaceCost: number;
  resurfaceCostText: string;
  salvageText: string;
  addons: VenueAddon[];
  specific: IceRinkSpecific | CrossCountrySpecific | HockeyArenaSpecific | SkiJumpSpecific | BobsleighSpecific;
};

export type ActivityTab = {
  type: 'ice_rink' | 'cross_country' | 'hockey_arena' | 'ski_jump' | 'bobsleigh_track';
  name: string;
  tagline: string;
  icon: string;
  desc: string;
  surface: string;
  weather: string;
  built: boolean;
  buildCost: number;
  buildCostText: string;
  upkeepText: string;
  canBuild: boolean;
  eventName: string;
  eventKey: string;
  levels: string[];
  upgradePreviews: { name: string; icon: string; desc: string; costText: string }[];
  venue: HydratedVenue | null;
};

export type WinterSportsPageProps = {
  cash: number;
  cashText: string;
  activeTab: string;
  weather: {
    tempC: number | null;
    windKmh: number | null;
    snowBaseCm: number | null;
    condition: string;
    isWinter: boolean;
  };
  stats: {
    openCount: number;
    builtCount: number;
    totalTypes: number;
    estGuests: number;
    estRevenueText: string;
    estTicketText: string;
    estAddonText: string;
    totalUpkeepText: string;
    estNetText: string;
    totalKwh: number;
    visitorBoost: number;
  };
  tabs: ActivityTab[];
};
