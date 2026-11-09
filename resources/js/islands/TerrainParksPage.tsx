import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';
import { Select } from '@/components/Select';

export type ParkFeature = {
  id: number;
  name: string;
  park_type: string;
  typeLabel: string;
  icon: string;
  size: 'small' | 'medium' | 'large';
  status: 'open' | 'closed' | 'under_construction';
  condition_pct: number;
  popularity: number;
  daily_visitors: number;
  build_days_left: number;
  slope_id: number | null;
  slope_name: string | null;
  upkeepFormatted: string;
  repair_cost: number;
  repair_cost_formatted: string;
  refund_amount: number;
  refund_formatted: string;
  canAffordRepair: boolean;
};

export type ParkCrewMember = {
  id: number;
  name: string;
  level: number;
  morale: number;
  salary: number;
  salaryFormatted: string;
};

export type SlopeOption = {
  id: number;
  name: string;
};

export type ParkSizeConfig = {
  cost: number;
  costFormatted: string;
  upkeep: number;
  upkeepFormatted: string;
  build_days: number;
  capacity: number;
  popularity_base: number;
};

export type ParkTypeConfig = {
  key: string;
  label: string;
  icon: string;
  sizes: Record<string, ParkSizeConfig>;
};

export type TerrainParksPageProps = {
  parks: ParkFeature[];
  parkCrew: ParkCrewMember[];
  slopes: SlopeOption[];
  parkConfig: Record<string, ParkTypeConfig>;
  cash: number;
  cashFormatted: string;
  stats: {
    openCount: number;
    buildingCount: number;
    dailyVisitors: number;
    crewCount: number;
    avgCondition: number;
  };
};

export default function TerrainParksPage({
  parks = [],
  parkCrew = [],
  slopes = [],
  parkConfig = {},
  cash = 0,
  cashFormatted = '€0',
  stats = {
    openCount: 0,
    buildingCount: 0,
    dailyVisitors: 0,
    crewCount: 0,
    avgCondition: 0,
  },
}: TerrainParksPageProps) {
  const configKeys = Object.keys(parkConfig);
  const defaultType = configKeys[0] || 'jump_line';
  const [selectedType, setSelectedType] = useState<string>(defaultType);
  const [selectedSize, setSelectedSize] = useState<string>('small');
  const [featureName, setFeatureName] = useState<string>('');
  const [selectedSlope, setSelectedSlope] = useState<string>('');

  const currentTypeConfig = parkConfig[selectedType];
  const currentSizeConfig = currentTypeConfig?.sizes?.[selectedSize];
  const canAffordBuild = currentSizeConfig ? cash >= currentSizeConfig.cost : false;

  return (
    <Page
      title="Terrain Parks"
      icon="fa-person-snowboarding"
      iconTone="text-warning"
      back="/dashboard"
      width="max-w-6xl"
      subtitle="Build halfpipes, jump lines, rail gardens, and freestyle zones"
      actions={
        <a href="/staff/hire">
          <Button variant="outline" size="sm" className="font-semibold gap-1.5 min-h-[36px]">
            <Icon name="fa-user-plus" /> Hire Crew
          </Button>
        </a>
      }
    >
      <div className="space-y-6">
        {/* KPI Strip */}
        <div className="grid grid-cols-2 sm:grid-cols-5 gap-3">
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-success tabular-nums">{stats.openCount}</div>
            <div className="text-xs text-muted">Open Features</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-warning tabular-nums">
              {stats.buildingCount}
            </div>
            <div className="text-xs text-muted">Building</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-primary tabular-nums">
              {stats.dailyVisitors}
            </div>
            <div className="text-xs text-muted">Daily Riders</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div className="text-2xl font-bold text-foreground tabular-nums">{stats.crewCount}</div>
            <div className="text-xs text-muted">Park Crew</div>
          </div>
          <div className="rounded-xl border border-border bg-base-100 p-3 text-center">
            <div
              className={`text-2xl font-bold tabular-nums ${
                stats.avgCondition >= 70
                  ? 'text-success'
                  : stats.avgCondition >= 40
                  ? 'text-warning'
                  : 'text-danger'
              }`}
            >
              {stats.avgCondition}%
            </div>
            <div className="text-xs text-muted">Avg Condition</div>
          </div>
        </div>

        {/* Park Crew Section */}
        {parkCrew.length > 0 ? (
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-users-gear" className="text-primary" />
                Park Maintenance Crew ({parkCrew.length})
              </span>
            }
          >
            <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
              {parkCrew.map((crew) => (
                <div
                  key={crew.id}
                  className="flex items-center gap-2.5 p-2.5 rounded-lg border border-border/60 bg-base-200/40"
                >
                  <div className="w-8 h-8 rounded-full bg-warning/15 flex items-center justify-center shrink-0">
                    <Icon name="fa-user" className="text-warning text-xs" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <div className="text-xs font-semibold text-foreground truncate">{crew.name}</div>
                    <div className="text-[11px] text-muted flex items-center gap-1.5">
                      <span className="text-warning flex items-center">
                        {Array.from({ length: Math.min(crew.level, 5) }).map((_, i) => (
                          <Icon key={i} name="fa-star" className="text-[8px]" />
                        ))}
                      </span>
                      <span>· {crew.morale}%</span>
                      <span>· {crew.salaryFormatted}/d</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </Panel>
        ) : (
          <div className="flex items-center gap-3 rounded-xl border border-warning/40 bg-warning/10 p-4 text-xs text-foreground">
            <Icon name="fa-user-slash" className="text-warning text-base shrink-0" />
            <div className="flex-1">
              No dedicated park crew on staff.{' '}
              <a href="/staff/hire" className="font-bold underline text-primary">
                Hire park crew
              </a>{' '}
              to maintain features and slow down condition decay.
            </div>
          </div>
        )}

        {/* Build New Feature */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-hammer" className="text-warning" />
              Build New Terrain Feature
            </span>
          }
        >
          <form action="/terrain-parks/build" method="post" className="space-y-4">
            <CsrfInput />
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
              <div>
                <label className="text-xs font-semibold text-muted block mb-1">
                  Name (optional)
                </label>
                <input
                  type="text"
                  name="name"
                  aria-label="Feature Name"
                  value={featureName}
                  onChange={(e) => setFeatureName(e.target.value)}
                  placeholder="e.g. Stunt Zone"
                  maxLength={100}
                  className="w-full h-10 px-3 rounded-lg border border-border bg-base-100 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
              </div>

              <div>
                <label className="text-xs font-semibold text-muted block mb-1">Feature Type</label>
                <Select
                  name="park_type"
                  aria-label="Feature Type"
                  value={selectedType}
                  onChange={(val) => setSelectedType(String(val))}
                  options={configKeys.map((k) => ({
                    id: k,
                    label: parkConfig[k].label,
                  }))}
                  className="w-full"
                />
              </div>

              <div>
                <label className="text-xs font-semibold text-muted block mb-1">Scale / Size</label>
                <Select
                  name="size"
                  aria-label="Size"
                  value={selectedSize}
                  onChange={(val) => setSelectedSize(String(val))}
                  options={[
                    { id: 'small', label: 'Small' },
                    { id: 'medium', label: 'Medium' },
                    { id: 'large', label: 'Large' },
                  ]}
                  className="w-full"
                />
              </div>

              <div>
                <label className="text-xs font-semibold text-muted block mb-1">Attach to Slope</label>
                <Select
                  name="slope_id"
                  aria-label="Slope attachment"
                  value={selectedSlope}
                  onChange={(val) => setSelectedSlope(String(val))}
                  options={[
                    { id: '', label: 'Standalone Feature' },
                    ...slopes.map((s) => ({ id: String(s.id), label: s.name })),
                  ]}
                  className="w-full"
                />
              </div>
            </div>

            {currentSizeConfig && (
              <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 p-3 rounded-lg bg-base-200/60 border border-border/40 text-xs">
                <div>
                  <span className="text-muted">Build Cost: </span>
                  <strong className="text-warning tabular-nums">
                    {currentSizeConfig.costFormatted}
                  </strong>
                </div>
                <div>
                  <span className="text-muted">Build Time: </span>
                  <strong className="text-foreground tabular-nums">
                    {currentSizeConfig.build_days} days
                  </strong>
                </div>
                <div>
                  <span className="text-muted">Upkeep: </span>
                  <strong className="text-foreground tabular-nums">
                    {currentSizeConfig.upkeepFormatted}/day
                  </strong>
                </div>
                <div>
                  <span className="text-muted">Capacity: </span>
                  <strong className="text-success tabular-nums">
                    {currentSizeConfig.capacity} riders/d
                  </strong>
                </div>
              </div>
            )}

            <Button
              type="submit"
              variant="primary"
              className="min-h-[44px] px-6 font-semibold flex items-center gap-2"
              isDisabled={!canAffordBuild}
            >
              <Icon name="fa-hammer" /> Build Feature
            </Button>
          </form>
        </Panel>

        {/* Existing Terrain Parks List */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-mountain" className="text-primary" />
              Existing Features ({parks.length})
            </span>
          }
        >
          {parks.length === 0 ? (
            <div className="text-center py-10">
              <div className="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-2 text-muted/50">
                <Icon name="fa-person-snowboarding" className="text-xl" />
              </div>
              <h4 className="font-semibold text-xs text-foreground">No terrain features yet</h4>
              <p className="text-xs text-muted mt-1">
                Construct your first jump line, rail garden, or halfpipe using the form above.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              {parks.map((p) => {
                const isUnderConstruction = p.status === 'under_construction';
                const isOpen = p.status === 'open';
                const iconName = p.icon.startsWith('fa-') ? p.icon : `fa-${p.icon}`;

                return (
                  <div
                    key={p.id}
                    className={`rounded-xl border p-4 bg-base-100 transition-all ${
                      isUnderConstruction
                        ? 'border-warning/50 bg-warning/5'
                        : isOpen
                        ? 'border-border'
                        : 'border-border opacity-75'
                    }`}
                  >
                    <div className="flex items-start justify-between gap-2 mb-2">
                      <div className="flex items-center gap-2.5 min-w-0">
                        <div className="w-9 h-9 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                          <Icon name={iconName} className="text-warning text-sm" />
                        </div>
                        <div className="min-w-0">
                          <h4 className="font-bold text-sm text-foreground truncate">{p.name}</h4>
                          <p className="text-xs text-muted">
                            {p.typeLabel} · <span className="capitalize">{p.size}</span>
                            {p.slope_name ? ` · On ${p.slope_name}` : ''}
                          </p>
                        </div>
                      </div>

                      <span
                        className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                          isUnderConstruction
                            ? 'bg-warning/20 text-warning'
                            : isOpen
                            ? 'bg-success/20 text-success'
                            : 'bg-muted/20 text-muted'
                        }`}
                      >
                        {isUnderConstruction
                          ? `${p.build_days_left}d Left`
                          : isOpen
                          ? 'Open'
                          : 'Closed'}
                      </span>
                    </div>

                    {!isUnderConstruction && (
                      <div className="space-y-2 my-3 text-xs">
                        <div className="flex items-center justify-between text-muted">
                          <span>Condition</span>
                          <span
                            className={`font-bold tabular-nums ${
                              p.condition_pct >= 70
                                ? 'text-success'
                                : p.condition_pct >= 40
                                ? 'text-warning'
                                : 'text-danger'
                            }`}
                          >
                            {p.condition_pct}%
                          </span>
                        </div>
                        <div className="w-full bg-base-200 rounded-full h-1.5 overflow-hidden">
                          <div
                            className={`h-full rounded-full transition-all ${
                              p.condition_pct >= 70
                                ? 'bg-success'
                                : p.condition_pct >= 40
                                ? 'bg-warning'
                                : 'bg-danger'
                            }`}
                            style={{ width: `${p.condition_pct}%` }}
                          />
                        </div>

                        <div className="grid grid-cols-2 gap-2 pt-1 text-muted text-[11px]">
                          <div>
                            Riders: <strong className="text-foreground">{p.daily_visitors}</strong>/d
                          </div>
                          <div>
                            Upkeep: <strong className="text-foreground">{p.upkeepFormatted}</strong>/d
                          </div>
                        </div>
                      </div>
                    )}

                    <div className="flex items-center justify-between pt-3 border-t border-border/40 mt-3 gap-2">
                      <div className="flex items-center gap-2">
                        {!isUnderConstruction && (
                          <form action={`/terrain-parks/toggle/${p.id}`} method="post">
                            <CsrfInput />
                            <Button
                              type="submit"
                              size="sm"
                              variant={isOpen ? 'secondary' : 'primary'}
                              className="min-h-[32px] text-xs font-semibold"
                            >
                              {isOpen ? 'Close' : 'Open'}
                            </Button>
                          </form>
                        )}

                        {!isUnderConstruction && p.condition_pct < 100 && (
                          <form
                            action={`/terrain-parks/repair/${p.id}`}
                            method="post"
                            onSubmit={(e) => {
                              if (!confirm(`Repair ${p.name} for ${p.repair_cost_formatted}?`)) {
                                e.preventDefault();
                              }
                            }}
                          >
                            <CsrfInput />
                            <Button
                              type="submit"
                              size="sm"
                              variant="outline"
                              isDisabled={!p.canAffordRepair}
                              className="min-h-[32px] text-xs font-semibold gap-1 text-info hover:text-info"
                            >
                              <Icon name="fa-wrench" /> Repair ({p.repair_cost_formatted})
                            </Button>
                          </form>
                        )}
                      </div>

                      <form
                        action={`/terrain-parks/demolish/${p.id}`}
                        method="post"
                        onSubmit={(e) => {
                          if (
                            !confirm(
                              `Demolish "${p.name}"? You will receive ${p.refund_formatted} (25% refund).`
                            )
                          ) {
                            e.preventDefault();
                          }
                        }}
                      >
                        <CsrfInput />
                        <Button
                          type="submit"
                          size="sm"
                          variant="ghost"
                          className="min-h-[32px] text-xs text-danger hover:bg-danger/10"
                          aria-label={`Demolish ${p.name}`}
                        >
                          <Icon name="fa-trash" />
                        </Button>
                      </form>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
        </Panel>
      </div>
    </Page>
  );
}
