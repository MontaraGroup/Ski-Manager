import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type ResortMeta = {
  name: string;
  location: string;
  description: string;
  altitude: string;
  aspect: string;
  is_open: boolean;
};

export type LiftItem = {
  id: number;
  name: string;
  subtype: string;
  subtypeName: string;
  status: 'open' | 'closed' | 'broken';
  condition_pct: number;
  repair_cost: number;
  repair_cost_formatted: string;
  canAffordRepair: boolean;
  length_meters: number;
};

export type SlopeItem = {
  id: number;
  name: string;
  difficulty: 'green' | 'blue' | 'red' | 'black' | 'double_black';
  difficultyLabel: string;
  status: 'open' | 'closed' | 'broken';
  condition_pct: number;
  snow_depth: number;
  snow_depth_formatted: string;
  snow_quality: string;
};

export type SectorData = {
  sectorNum: number;
  name: string;
  lifts: LiftItem[];
  slopes: SlopeItem[];
};

export type ResortPageProps = {
  resort: ResortMeta;
  stats: {
    openSlopes: number;
    totalSlopes: number;
    openLifts: number;
    totalLifts: number;
    staffCount: number;
    buildingCount: number;
    avgCondition: number;
  };
  weather: {
    temp: number;
    tempFormatted: string;
    snowBase: number;
    snowBaseFormatted: string;
    condition: string;
    windSpeed: number;
    windSpeedFormatted: string;
  };
  cash: number;
  cashFormatted: string;
  sectors: SectorData[];
};

export default function ResortPage({
  resort = {
    name: 'My Resort',
    location: 'Park City, Utah',
    description: '',
    altitude: 'medium',
    aspect: 'north',
    is_open: true,
  },
  stats = {
    openSlopes: 0,
    totalSlopes: 0,
    openLifts: 0,
    totalLifts: 0,
    staffCount: 0,
    buildingCount: 0,
    avgCondition: 100,
  },
  weather = {
    temp: -2,
    tempFormatted: '-2°C',
    snowBase: 120,
    snowBaseFormatted: '120 cm',
    condition: 'Clear',
    windSpeed: 10,
    windSpeedFormatted: '10 km/h',
  },
  cashFormatted = '€0',
  sectors = [],
}: ResortPageProps) {
  const getDifficultyBadge = (diff: string) => {
    switch (diff) {
      case 'green':
        return 'bg-success/15 text-success border-success/30';
      case 'red':
        return 'bg-danger/15 text-danger border-danger/30';
      case 'black':
      case 'double_black':
        return 'bg-neutral/20 text-foreground border-neutral/40';
      default:
        return 'bg-primary/15 text-primary border-primary/30';
    }
  };

  return (
    <Page
      title={resort.name}
      icon="fa-mountain-sun"
      iconTone="text-primary"
      back="/dashboard"
      width="max-w-7xl"
      subtitle={`${resort.location || 'Park City, Utah'} · Comprehensive resort operations and mountain profile`}
      actions={
        <div className="flex items-center gap-2 flex-wrap">
          <form action="/resort/toggle-resort" method="post">
            <CsrfInput />
            <Button
              type="submit"
              size="sm"
              variant={resort.is_open ? 'danger' : 'primary'}
              className="font-semibold text-xs min-h-11 sm:min-h-[36px] gap-1.5"
            >
              <Icon name={resort.is_open ? 'fa-door-closed' : 'fa-door-open'} />
              {resort.is_open ? 'Close Resort' : 'Open Resort'}
            </Button>
          </form>

          <a href="/resort/edit">
            <Button variant="outline" size="sm" className="font-semibold text-xs min-h-11 sm:min-h-[36px] gap-1.5">
              <Icon name="fa-pen-to-square" /> Edit Profile
            </Button>
          </a>

          <a href="/map">
            <Button variant="primary" size="sm" className="font-semibold text-xs min-h-11 sm:min-h-[36px] gap-1.5">
              <Icon name="fa-map" /> Trail Map
            </Button>
          </a>
        </div>
      }
    >
      <div className="space-y-6">
        {/* Quick Operations Controls */}
        <div className="flex items-center justify-between p-3.5 rounded-xl border border-border bg-base-100 shadow-sm flex-wrap gap-3">
          <div className="flex items-center gap-3">
            <span
              className={`px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider ${
                resort.is_open ? 'bg-success/20 text-success' : 'bg-danger/20 text-danger'
              }`}
            >
              {resort.is_open ? 'Resort Open for Skiers' : 'Resort Closed'}
            </span>
            <span className="text-xs text-muted">
              {stats.avgCondition}% average mountain condition
            </span>
          </div>

          <div className="flex items-center gap-2">
            <form action="/resort/open-all" method="post">
              <CsrfInput />
              <Button type="submit" size="sm" variant="secondary" className="text-xs min-h-11 sm:min-h-[32px] gap-1">
                <Icon name="fa-door-open" /> Open All Slopes & Lifts
              </Button>
            </form>
            <form action="/resort/close-all" method="post">
              <CsrfInput />
              <Button type="submit" size="sm" variant="outline" className="text-xs min-h-11 sm:min-h-[32px] gap-1">
                <Icon name="fa-door-closed" /> Close All
              </Button>
            </form>
          </div>
        </div>

        {/* 4 Stats Grid */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-2xl font-bold text-primary tabular-nums">
              {stats.openSlopes}{' '}
              <span className="text-xs font-normal text-muted">/ {stats.totalSlopes}</span>
            </div>
            <div className="text-xs text-muted">Open Slopes</div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-2xl font-bold text-success tabular-nums">
              {stats.openLifts}{' '}
              <span className="text-xs font-normal text-muted">/ {stats.totalLifts}</span>
            </div>
            <div className="text-xs text-muted">Open Lifts</div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-2xl font-bold text-warning tabular-nums">{stats.staffCount}</div>
            <div className="text-xs text-muted">Active Staff</div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4 text-center">
            <div className="text-2xl font-bold text-secondary tabular-nums">
              {stats.buildingCount}
            </div>
            <div className="text-xs text-muted">Resort Buildings</div>
          </div>
        </div>

        {/* Environmental Overview */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div className="rounded-xl border border-border bg-base-100 p-3.5 flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-info/15 flex items-center justify-center shrink-0">
              <Icon name="fa-snowflake" className="text-info text-base" />
            </div>
            <div>
              <div className="text-sm font-bold text-foreground tabular-nums">
                {weather.tempFormatted}
              </div>
              <div className="text-xs text-muted">{weather.condition}</div>
            </div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-3.5 flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-info/15 flex items-center justify-center shrink-0">
              <Icon name="fa-layer-group" className="text-info text-base" />
            </div>
            <div>
              <div className="text-sm font-bold text-foreground tabular-nums">
                {weather.snowBaseFormatted}
              </div>
              <div className="text-xs text-muted">Snow Base</div>
            </div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-3.5 flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-warning/15 flex items-center justify-center shrink-0">
              <Icon name="fa-wind" className="text-warning text-base" />
            </div>
            <div>
              <div className="text-sm font-bold text-foreground tabular-nums">
                {weather.windSpeedFormatted}
              </div>
              <div className="text-xs text-muted">Wind Speed</div>
            </div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-3.5 flex items-center gap-3">
            <div className="w-9 h-9 rounded-lg bg-success/15 flex items-center justify-center shrink-0">
              <Icon name="fa-sack-dollar" className="text-success text-base" />
            </div>
            <div>
              <div className="text-sm font-bold text-success tabular-nums">{cashFormatted}</div>
              <div className="text-xs text-muted">Treasury Cash</div>
            </div>
          </div>
        </div>

        {/* Mountain Sectors & Infrastructure */}
        {sectors.map((sec) => (
          <Panel
            key={sec.sectorNum}
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-mountain" className="text-primary" />
                {sec.name} (Sector {sec.sectorNum})
              </span>
            }
          >
            <div className="space-y-6">
              {/* Slopes */}
              {sec.slopes.length > 0 && (
                <div>
                  <h4 className="font-semibold text-xs text-muted uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <Icon name="fa-person-skiing" className="text-info text-xs" /> Slopes (
                    {sec.slopes.length})
                  </h4>
                  <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="w-full text-left text-xs">
                      <thead>
                        <tr className="border-b border-border text-muted uppercase text-[10px] font-bold">
                          <th className="py-2.5 px-3">Slope</th>
                          <th className="py-2.5 px-3">Difficulty</th>
                          <th className="py-2.5 px-3">Snow Depth</th>
                          <th className="py-2.5 px-3">Condition</th>
                          <th className="py-2.5 px-3">Status</th>
                          <th className="py-2.5 px-3 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/40">
                        {sec.slopes.map((slope) => (
                          <tr key={slope.id} className="hover:bg-base-200/40 transition-colors">
                            <td className="py-2.5 px-3 font-semibold text-foreground">
                              {slope.name}
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`px-2 py-0.5 rounded text-[10px] font-bold border uppercase tracking-wider ${getDifficultyBadge(
                                  slope.difficulty
                                )}`}
                              >
                                {slope.difficultyLabel}
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-muted tabular-nums">
                              {slope.snow_depth_formatted} · {slope.snow_quality}
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`font-semibold tabular-nums ${
                                  slope.condition_pct >= 70
                                    ? 'text-success'
                                    : slope.condition_pct >= 40
                                    ? 'text-warning'
                                    : 'text-danger'
                                }`}
                              >
                                {slope.condition_pct}%
                              </span>
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                                  slope.status === 'open'
                                    ? 'bg-success/20 text-success'
                                    : 'bg-muted/20 text-muted'
                                }`}
                              >
                                {slope.status}
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <form action={`/resort/toggle/${slope.id}`} method="post" className="inline">
                                <CsrfInput />
                                <Button
                                  type="submit"
                                  size="sm"
                                  variant={slope.status === 'open' ? 'secondary' : 'primary'}
                                  className="min-h-11 sm:min-h-7 text-xs font-semibold px-3"
                                >
                                  {slope.status === 'open' ? 'Close' : 'Open'}
                                </Button>
                              </form>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}

              {/* Lifts */}
              {sec.lifts.length > 0 && (
                <div>
                  <h4 className="font-semibold text-xs text-muted uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                    <Icon name="fa-cable-car" className="text-success text-xs" /> Ski Lifts (
                    {sec.lifts.length})
                  </h4>
                  <div className="overflow-x-auto -mx-4 sm:mx-0">
                    <table className="w-full text-left text-xs">
                      <thead>
                        <tr className="border-b border-border text-muted uppercase text-[10px] font-bold">
                          <th className="py-2.5 px-3">Lift</th>
                          <th className="py-2.5 px-3">Type</th>
                          <th className="py-2.5 px-3">Length</th>
                          <th className="py-2.5 px-3">Condition</th>
                          <th className="py-2.5 px-3">Status</th>
                          <th className="py-2.5 px-3 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-border/40">
                        {sec.lifts.map((lift) => (
                          <tr key={lift.id} className="hover:bg-base-200/40 transition-colors">
                            <td className="py-2.5 px-3 font-semibold text-foreground">
                              {lift.name}
                            </td>
                            <td className="py-2.5 px-3 text-muted">{lift.subtypeName}</td>
                            <td className="py-2.5 px-3 text-muted tabular-nums">
                              {lift.length_meters}m
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`font-semibold tabular-nums ${
                                  lift.condition_pct >= 70
                                    ? 'text-success'
                                    : lift.condition_pct >= 40
                                    ? 'text-warning'
                                    : 'text-danger'
                                }`}
                              >
                                {lift.condition_pct}%
                              </span>
                            </td>
                            <td className="py-2.5 px-3">
                              <span
                                className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                                  lift.status === 'open'
                                    ? 'bg-success/20 text-success'
                                    : lift.status === 'broken'
                                    ? 'bg-danger/20 text-danger'
                                    : 'bg-muted/20 text-muted'
                                }`}
                              >
                                {lift.status}
                              </span>
                            </td>
                            <td className="py-2.5 px-3 text-right">
                              <div className="flex items-center justify-end gap-1.5">
                                {lift.condition_pct < 100 && (
                                  <form
                                    action={`/resort/repair-lift/${lift.id}`}
                                    method="post"
                                    onSubmit={(e) => {
                                      if (
                                        !confirm(
                                          `Repair ${lift.name} for ${lift.repair_cost_formatted}?`
                                        )
                                      ) {
                                        e.preventDefault();
                                      }
                                    }}
                                    className="inline"
                                  >
                                    <CsrfInput />
                                    <Button
                                      type="submit"
                                      size="sm"
                                      variant="outline"
                                      isDisabled={!lift.canAffordRepair}
                                      className="min-h-11 sm:min-h-7 text-xs font-semibold text-info px-3"
                                    >
                                      Repair ({lift.repair_cost_formatted})
                                    </Button>
                                  </form>
                                )}

                                <form action={`/resort/toggle/${lift.id}`} method="post" className="inline">
                                  <CsrfInput />
                                  <Button
                                    type="submit"
                                    size="sm"
                                    variant={lift.status === 'open' ? 'secondary' : 'primary'}
                                    isDisabled={lift.status === 'broken'}
                                    className="min-h-11 sm:min-h-7 text-xs font-semibold px-3"
                                  >
                                    {lift.status === 'open' ? 'Close' : 'Open'}
                                  </Button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>
              )}
            </div>
          </Panel>
        ))}
      </div>
    </Page>
  );
}
