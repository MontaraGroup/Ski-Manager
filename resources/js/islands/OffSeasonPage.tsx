import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type SeasonMeta = {
  gameDay: number;
  seasonDay: number;
  isWinter: boolean;
  isSummer: boolean;
  summerDay: number;
  daysUntilWinter: number;
  daysUntilSummer: number;
  winterDays: number;
  summerDays: number;
  seasonLength: number;
};

export type HealthItem = {
  count: number;
  avgCondition: number;
};

export type MaintenanceTask = {
  key: string;
  name: string;
  icon: string;
  desc: string;
  target: string;
  cost_per_unit: number;
  cost_per_unit_formatted: string;
  total_items: number;
  needs_repair: number;
  total_cost: number;
  total_cost_formatted: string;
  canAfford: boolean;
};

export type SummerActivity = {
  id: number;
  name: string;
  specialty: string;
  revenueFormatted: string;
  upkeepFormatted: string;
  capacity: number;
  status: string;
  isActive: boolean;
};

export type CatalogActivity = {
  key: string;
  name: string;
  icon: string;
  desc: string;
  cost: number;
  costFormatted: string;
  revenueFormatted: string;
  upkeepFormatted: string;
  capacity: number;
  canAfford: boolean;
};

export type OffSeasonPageProps = {
  season: SeasonMeta;
  cash: number;
  cashFormatted: string;
  health: {
    slopes: HealthItem;
    lifts: HealthItem;
    buildings: HealthItem;
    equipment: HealthItem;
    terrainParks: HealthItem;
  };
  maintenanceTasks: MaintenanceTask[];
  summerActivities: SummerActivity[];
  catalogActivities: CatalogActivity[];
};

export default function OffSeasonPage({
  season,
  health = {
    slopes: { count: 0, avgCondition: 100 },
    lifts: { count: 0, avgCondition: 100 },
    buildings: { count: 0, avgCondition: 100 },
    equipment: { count: 0, avgCondition: 100 },
    terrainParks: { count: 0, avgCondition: 100 },
  },
  maintenanceTasks = [],
  summerActivities = [],
  catalogActivities = [],
}: OffSeasonPageProps) {
  const isWinter = season?.isWinter ?? true;
  const isSummer = season?.isSummer ?? false;

  const winterProgress = Math.min(
    100,
    Math.round(((season?.seasonDay ?? 1) / (season?.winterDays || 100)) * 100)
  );
  const summerProgress = isSummer
    ? Math.min(
        100,
        Math.round(((season?.summerDay ?? 1) / (season?.summerDays || 35)) * 100)
      )
    : 0;

  const healthList = [
    { label: 'Slopes', icon: 'fa-mountain', color: 'text-info', data: health.slopes },
    { label: 'Lifts', icon: 'fa-cable-car', color: 'text-success', data: health.lifts },
    { label: 'Buildings', icon: 'fa-building', color: 'text-primary', data: health.buildings },
    { label: 'Equipment', icon: 'fa-toolbox', color: 'text-warning', data: health.equipment },
    {
      label: 'Parks',
      icon: 'fa-person-snowboarding',
      color: 'text-secondary',
      data: health.terrainParks,
    },
  ];

  return (
    <Page
      title="Off-Season Management"
      icon="fa-sun"
      iconTone="text-warning"
      back="/dashboard"
      width="max-w-6xl"
      subtitle="Summer activities, bulk maintenance overhauls, and preparing infrastructure for winter"
    >
      <div className="space-y-6">
        {/* Season Status Banner */}
        <div className="rounded-2xl border border-border bg-base-100 p-5 shadow-sm space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div className="flex items-center gap-3">
              <div
                className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 ${
                  isWinter ? 'bg-info/15 text-info' : 'bg-warning/15 text-warning'
                }`}
              >
                <Icon name={isWinter ? 'fa-snowflake' : 'fa-sun'} className="text-xl" />
              </div>
              <div>
                <h3 className="text-base font-bold text-foreground">
                  {isWinter ? 'Winter Ski Season Active' : 'Summer Off-Season Active'}
                </h3>
                <p className="text-xs text-muted">
                  Game Day {season?.gameDay} · Season Day {season?.seasonDay} of{' '}
                  {season?.seasonLength}
                </p>
              </div>
            </div>

            <span
              className={`px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5 self-start sm:self-auto ${
                isWinter
                  ? 'bg-info/15 text-info border border-info/30'
                  : 'bg-warning/15 text-warning border border-warning/30'
              }`}
            >
              {isWinter
                ? `Day ${season?.seasonDay}/100 · ${season?.daysUntilSummer}d until summer`
                : `Summer Day ${season?.summerDay}/35 · ${season?.daysUntilWinter}d until winter`}
            </span>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-3 pt-2">
            <div>
              <div className="flex items-center justify-between text-xs text-muted mb-1">
                <span>Winter Season (Days 1–{season?.winterDays})</span>
                <span className="font-semibold text-foreground">{winterProgress}%</span>
              </div>
              <div className="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                <div
                  className="h-full bg-info rounded-full transition-all"
                  style={{ width: `${winterProgress}%` }}
                />
              </div>
            </div>

            <div>
              <div className="flex items-center justify-between text-xs text-muted mb-1">
                <span>Summer Season (Days {season?.winterDays + 1}–{season?.seasonLength})</span>
                <span className="font-semibold text-foreground">{summerProgress}%</span>
              </div>
              <div className="w-full bg-base-200 rounded-full h-2 overflow-hidden">
                <div
                  className="h-full bg-warning rounded-full transition-all"
                  style={{ width: `${summerProgress}%` }}
                />
              </div>
            </div>
          </div>
        </div>

        {/* Infrastructure Health */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-heart-pulse" className="text-danger" />
              Infrastructure Health
            </span>
          }
        >
          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            {healthList.map((h) => {
              const cond = h.data?.avgCondition ?? 100;
              const count = h.data?.count ?? 0;
              return (
                <div
                  key={h.label}
                  className="p-3 rounded-xl border border-border bg-base-200/30 text-center flex flex-col justify-between"
                >
                  <div>
                    <div className="w-8 h-8 rounded-lg bg-base-100 flex items-center justify-center mx-auto mb-2">
                      <Icon name={h.icon} className={`${h.color} text-sm`} />
                    </div>
                    <div
                      className={`text-2xl font-bold tabular-nums ${
                        cond >= 70
                          ? 'text-success'
                          : cond >= 40
                          ? 'text-warning'
                          : 'text-danger'
                      }`}
                    >
                      {cond}%
                    </div>
                    <div className="text-xs text-muted mt-0.5">
                      {h.label} ({count})
                    </div>
                  </div>

                  <div className="w-full bg-base-200 rounded-full h-1.5 overflow-hidden mt-2">
                    <div
                      className={`h-full rounded-full transition-all ${
                        cond >= 70
                          ? 'bg-success'
                          : cond >= 40
                          ? 'bg-warning'
                          : 'bg-danger'
                      }`}
                      style={{ width: `${cond}%` }}
                    />
                  </div>
                </div>
              );
            })}
          </div>
        </Panel>

        {/* Bulk Maintenance */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-wrench" className="text-primary" />
              Bulk Infrastructure Maintenance
            </span>
          }
        >
          <p className="text-xs text-muted mb-4 leading-relaxed">
            Service and restore every asset in a category back to 100% condition simultaneously.
            Available year-round, but most cost-effective during summer downtime before winter opening.
          </p>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            {maintenanceTasks.map((t) => {
              const iconName = t.icon.startsWith('fa-') ? t.icon : `fa-${t.icon}`;
              const allGood = t.needs_repair === 0;

              return (
                <div
                  key={t.key}
                  className="rounded-xl border border-border bg-base-200/30 p-3.5 flex flex-col justify-between"
                >
                  <div>
                    <div className="flex items-center gap-2.5 mb-2">
                      <div className="w-8 h-8 rounded-lg bg-base-100 flex items-center justify-center shrink-0">
                        <Icon name={iconName} className="text-warning text-xs" />
                      </div>
                      <div className="min-w-0">
                        <h4 className="font-bold text-xs text-foreground truncate">{t.name}</h4>
                        <p className="text-xs text-muted">{t.cost_per_unit_formatted} / item</p>
                      </div>
                    </div>

                    <p className="text-xs text-muted mb-3 leading-relaxed">{t.desc}</p>
                  </div>

                  <div className="pt-2 border-t border-border/40">
                    <div className="flex items-center justify-between text-xs mb-2 text-muted">
                      <span>Status:</span>
                      <strong className={allGood ? 'text-success' : 'text-warning'}>
                        {allGood ? 'All 100% Good' : `${t.needs_repair} need service`}
                      </strong>
                    </div>

                    <form
                      action="/off-season/maintenance"
                      method="post"
                      onSubmit={(e) => {
                        if (allGood) {
                          e.preventDefault();
                          return;
                        }
                        if (
                          !confirm(
                            `Run ${t.name} for ${t.needs_repair} items costing ${t.total_cost_formatted}?`
                          )
                        ) {
                          e.preventDefault();
                        }
                      }}
                    >
                      <CsrfInput />
                      <input type="hidden" name="task" value={t.key} />
                      <Button
                        type="submit"
                        size="sm"
                        variant={allGood ? 'secondary' : 'primary'}
                        className="w-full min-h-11 sm:min-h-8 font-semibold text-xs gap-1.5"
                        isDisabled={allGood || !t.canAfford}
                      >
                        <Icon name="fa-wrench" />
                        {allGood ? 'All Good' : `Run Overhaul (${t.total_cost_formatted})`}
                      </Button>
                    </form>
                  </div>
                </div>
              );
            })}
          </div>
        </Panel>

        {/* Summer Activities */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-sun" className="text-warning" />
              Summer Activities ({summerActivities.length})
            </span>
          }
        >
          <p className="text-xs text-muted mb-4 leading-relaxed">
            Construct summer attractions to generate revenue and maintain guest interest during the
            off-season. These generate cash flow during summer days and remain resort assets permanently.
          </p>

          {summerActivities.length > 0 && (
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
              {summerActivities.map((act) => (
                <div
                  key={act.id}
                  className="p-3.5 rounded-xl border border-border bg-base-100 shadow-sm flex items-center justify-between"
                >
                  <div>
                    <div className="flex items-center gap-2">
                      <h4 className="font-bold text-sm text-foreground">{act.name}</h4>
                      <span
                        className={`px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wider ${
                          act.isActive
                            ? 'bg-success/20 text-success'
                            : 'bg-muted/20 text-muted'
                        }`}
                      >
                        {act.isActive ? 'Active' : 'Off-Season'}
                      </span>
                    </div>
                    <div className="text-xs text-muted mt-1 flex items-center gap-3">
                      <span>
                        Rev: <strong className="text-success">{act.revenueFormatted}</strong>/d
                      </span>
                      <span>
                        Upkeep: <strong className="text-danger">{act.upkeepFormatted}</strong>/d
                      </span>
                      <span>Cap: {act.capacity} guests</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          )}

          <h4 className="font-semibold text-xs text-muted uppercase tracking-wider mb-3">
            Available Summer Attractions
          </h4>
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {catalogActivities.map((cfg) => {
              const iconName = cfg.icon.startsWith('fa-') ? cfg.icon : `fa-${cfg.icon}`;

              return (
                <div
                  key={cfg.key}
                  className="rounded-xl border border-border bg-base-200/30 p-3.5 flex flex-col justify-between"
                >
                  <div>
                    <div className="flex items-center gap-2 mb-2">
                      <div className="w-8 h-8 rounded-lg bg-base-100 flex items-center justify-center shrink-0">
                        <Icon name={iconName} className="text-warning text-xs" />
                      </div>
                      <h5 className="font-bold text-xs text-foreground truncate">{cfg.name}</h5>
                    </div>

                    <p className="text-xs text-muted mb-3 leading-relaxed">{cfg.desc}</p>

                    <div className="space-y-1 text-xs text-muted mb-3">
                      <div>
                        Cost: <strong className="text-warning">{cfg.costFormatted}</strong>
                      </div>
                      <div>
                        Revenue: <strong className="text-success">{cfg.revenueFormatted}/d</strong>
                      </div>
                      <div>
                        Upkeep: <strong className="text-foreground">{cfg.upkeepFormatted}/d</strong>
                      </div>
                      <div>
                        Capacity: <strong className="text-foreground">{cfg.capacity} guests</strong>
                      </div>
                    </div>
                  </div>

                  <form
                    action="/off-season/build"
                    method="post"
                    onSubmit={(e) => {
                      if (!confirm(`Build ${cfg.name} for ${cfg.costFormatted}?`)) {
                        e.preventDefault();
                      }
                    }}
                    className="pt-2 border-t border-border/40"
                  >
                    <CsrfInput />
                    <input type="hidden" name="activity_key" value={cfg.key} />
                    <Button
                      type="submit"
                      variant="primary"
                      size="sm"
                      className="w-full min-h-11 sm:min-h-8 font-semibold text-xs gap-1.5"
                      isDisabled={!cfg.canAfford}
                    >
                      <Icon name="fa-hammer" />
                      {cfg.canAfford ? `Build ${cfg.costFormatted}` : 'Cannot Afford'}
                    </Button>
                  </form>
                </div>
              );
            })}
          </div>
        </Panel>
      </div>
    </Page>
  );
}
