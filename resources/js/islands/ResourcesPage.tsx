import { useState } from 'react';
import { Button } from '@heroui/react';
import { Icon } from '@/components/Icon';
import { CsrfInput, Page, Panel } from '@/components/Page';

export type DemandPart = {
  key: string;
  label: string;
  icon: string;
  value: number;
  valueFormatted: string;
};

export type ResourceSource = {
  id: number;
  name: string;
  source_type: string;
  typeLabel: string;
  icon: string;
  status: 'active' | 'off' | 'broken' | 'under_construction';
  condition_pct: number;
  output: number;
  outputFormatted: string;
  used_output: number;
  upkeep: number;
  upkeepFormatted: string;
  repair_cost: number;
  repair_cost_formatted: string;
  canAffordRepair: boolean;
  build_days_left: number;
  dailyValueFormatted: string | null;
};

export type AvailableSource = {
  type: string;
  label: string;
  icon: string;
  cost: number;
  costFormatted: string;
  upkeep: number;
  upkeepFormatted: string;
  output: number;
  outputFormatted: string;
  build_days: number;
  owned: number;
  limit: number | null;
  canAfford: boolean;
  atLimit: boolean;
};

export type ResourcesPageProps = {
  category: 'energy' | 'water';
  unit: string;
  tariffLabel: string;
  cash: number;
  cashFormatted: string;
  demand: {
    total: number;
    totalFormatted: string;
    parts: DemandPart[];
  };
  output: number;
  outputFormatted: string;
  deficit: number;
  deficitFormatted: string;
  upkeep: number;
  upkeepFormatted: string;
  importCost: number;
  importCostFormatted: string;
  totalDailyCost: number;
  totalDailyCostFormatted: string;
  sources: ResourceSource[];
  availableSources: AvailableSource[];
};

export default function ResourcesPage({
  category = 'energy',
  tariffLabel = 'grid rate',
  cashFormatted = '€0',
  demand = { total: 0, totalFormatted: '0', parts: [] },
  outputFormatted = '0',
  deficit = 0,
  deficitFormatted = '0',
  upkeepFormatted = '€0',
  importCost = 0,
  importCostFormatted = '€0',
  totalDailyCostFormatted = '€0',
  sources = [],
  availableSources = [],
}: ResourcesPageProps) {
  const isEnergy = category === 'energy';
  const otherRoute = isEnergy ? '/water' : '/energy';
  const otherLabel = isEnergy ? 'Water' : 'Energy';
  const otherIcon = isEnergy ? 'fa-droplet' : 'fa-bolt';

  const [buildNames, setBuildNames] = useState<Record<string, string>>({});

  const handleNameChange = (type: string, val: string) => {
    setBuildNames((prev) => ({ ...prev, [type]: val }));
  };

  return (
    <Page
      title={isEnergy ? 'Energy' : 'Water'}
      icon={isEnergy ? 'fa-bolt' : 'fa-droplet'}
      iconTone={isEnergy ? 'text-warning' : 'text-info'}
      back="/dashboard"
      width="max-w-6xl"
      subtitle={`Your sources cover local demand; shortfalls are supplied automatically from the ${
        isEnergy ? 'grid' : 'water network'
      } at ${tariffLabel}`}
      actions={
        <a href={otherRoute}>
          <Button variant="outline" size="sm" className="font-semibold gap-1.5 min-h-11 sm:min-h-[36px]">
            <Icon name={otherIcon} /> {otherLabel}
          </Button>
        </a>
      }
    >
      <div className="space-y-6">
        {/* 4 KPI Cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-3">
          <div className="rounded-xl border border-border bg-base-100 p-4">
            <div className="text-xs text-muted">Daily Demand</div>
            <div className="text-2xl font-bold text-foreground tabular-nums mt-0.5">
              {demand.totalFormatted}
            </div>
            <div className="text-[11px] text-muted">Total resort draw</div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4">
            <div className="text-xs text-muted">Sources Produce</div>
            <div className="text-2xl font-bold text-success tabular-nums mt-0.5">
              {outputFormatted}
            </div>
            <div className="text-[11px] text-muted">From active sources</div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4">
            <div className="text-xs text-muted">Grid Imports</div>
            <div
              className={`text-2xl font-bold tabular-nums mt-0.5 ${
                deficit > 0 ? 'text-warning' : 'text-foreground'
              }`}
            >
              {deficitFormatted}
            </div>
            <div className="text-[11px] text-muted tabular-nums">
              {importCostFormatted}/day imports
            </div>
          </div>

          <div className="rounded-xl border border-border bg-base-100 p-4">
            <div className="text-xs text-muted">Total Cost / Day</div>
            <div className="text-2xl font-bold text-danger tabular-nums mt-0.5">
              {totalDailyCostFormatted}
            </div>
            <div className="text-[11px] text-muted tabular-nums">
              {upkeepFormatted} upkeep + {importCostFormatted} imports
            </div>
          </div>
        </div>

        {/* Demand Breakdown */}
        {demand.parts.length > 0 && (
          <Panel
            title={
              <span className="flex items-center gap-2">
                <Icon name="fa-chart-pie" className="text-primary" />
                Where Daily Demand Comes From
              </span>
            }
          >
            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
              {demand.parts.map((p) => {
                const iconName = p.icon.startsWith('fa-') ? p.icon : `fa-${p.icon}`;
                return (
                  <div
                    key={p.key}
                    className="p-3 rounded-lg border border-border/60 bg-base-200/40 text-center"
                  >
                    <div className="text-lg font-bold text-foreground tabular-nums">
                      {p.valueFormatted}
                    </div>
                    <div className="text-xs text-muted flex items-center justify-center gap-1.5 mt-0.5">
                      <Icon name={iconName} className="text-xs text-primary" />
                      <span>{p.label}</span>
                    </div>
                  </div>
                );
              })}
            </div>

            <p className="text-xs text-muted mt-3 leading-relaxed">
              {isEnergy
                ? 'Open ski lifts and lit night-skiing terrain consume the majority of grid power. Buildings consume by upgrade tier. Running snow guns draw according to their equipment rating. Shortfalls are imported without disruption.'
                : 'Snow guns draw water according to their capacity ratings. Operating reservoirs buffer municipal network costs.'}
            </p>
          </Panel>
        )}

        {/* Existing Sources */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name={isEnergy ? 'fa-plug' : 'fa-faucet-drip'} className="text-primary" />
              Your {isEnergy ? 'Energy' : 'Water'} Sources ({sources.length})
            </span>
          }
        >
          {sources.length === 0 ? (
            <div className="text-center py-8">
              <div className="w-12 h-12 rounded-full bg-base-200 flex items-center justify-center mx-auto mb-2 text-muted/40">
                <Icon name={isEnergy ? 'fa-bolt' : 'fa-droplet'} className="text-xl" />
              </div>
              <h4 className="font-semibold text-xs text-foreground">No local sources constructed</h4>
              <p className="text-xs text-muted mt-1 max-w-sm mx-auto">
                All resource draw is currently imported at utility tariff rates. Build solar panels,
                wind turbines, or reservoirs below to cut operating expenses.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
              {sources.map((s) => {
                const isBuilding = s.status === 'under_construction';
                const isActive = s.status === 'active';
                const isBroken = s.status === 'broken';
                const iconName = s.icon.startsWith('fa-') ? s.icon : `fa-${s.icon}`;

                return (
                  <div
                    key={s.id}
                    className={`rounded-xl border p-4 bg-base-100 transition-all ${
                      isBroken
                        ? 'border-danger/60 bg-danger/5 ring-1 ring-danger/20'
                        : isBuilding
                        ? 'border-warning/50 bg-warning/5'
                        : isActive
                        ? 'border-border'
                        : 'border-border opacity-70'
                    }`}
                  >
                    <div className="flex items-start justify-between gap-2 mb-2">
                      <div className="flex items-center gap-2.5 min-w-0">
                        <div className="w-9 h-9 rounded-lg bg-base-200 flex items-center justify-center shrink-0">
                          <Icon
                            name={iconName}
                            className={
                              isBroken
                                ? 'text-danger'
                                : isActive
                                ? 'text-success'
                                : isBuilding
                                ? 'text-warning'
                                : 'text-muted'
                            }
                          />
                        </div>
                        <div className="min-w-0">
                          <h4 className="font-bold text-sm text-foreground truncate">{s.name}</h4>
                          <p className="text-xs text-muted">
                            {s.typeLabel} · {s.outputFormatted}/day · {s.upkeepFormatted}/day upkeep
                          </p>
                        </div>
                      </div>

                      <span
                        className={`px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${
                          isBroken
                            ? 'bg-danger/20 text-danger'
                            : isBuilding
                            ? 'bg-warning/20 text-warning'
                            : isActive
                            ? 'bg-success/20 text-success'
                            : 'bg-muted/20 text-muted'
                        }`}
                      >
                        {isBroken
                          ? 'Broken'
                          : isBuilding
                          ? `${s.build_days_left}d Left`
                          : isActive
                          ? 'On'
                          : 'Off'}
                      </span>
                    </div>

                    {!isBuilding && (
                      <div className="space-y-1.5 my-3 text-xs">
                        <div className="flex items-center justify-between text-muted text-[11px]">
                          <span>Condition</span>
                          <span
                            className={`font-semibold tabular-nums ${
                              s.condition_pct >= 70
                                ? 'text-success'
                                : s.condition_pct >= 40
                                ? 'text-warning'
                                : 'text-danger'
                            }`}
                          >
                            {s.condition_pct}%
                          </span>
                        </div>
                        <div className="w-full bg-base-200 rounded-full h-1.5 overflow-hidden">
                          <div
                            className={`h-full rounded-full transition-all ${
                              s.condition_pct >= 70
                                ? 'bg-success'
                                : s.condition_pct >= 40
                                ? 'bg-warning'
                                : 'bg-danger'
                            }`}
                            style={{ width: `${s.condition_pct}%` }}
                          />
                        </div>

                        {s.dailyValueFormatted && (
                          <div className="text-[11px] text-muted pt-1">
                            Saves against grid imports:{' '}
                            <strong className="text-success">{s.dailyValueFormatted}/day</strong>
                          </div>
                        )}
                      </div>
                    )}

                    {!isBuilding && (
                      <div className="flex items-center justify-end pt-3 border-t border-border/40 gap-2">
                        {s.condition_pct < 100 && (
                          <form
                            action={`/${category}/repair/${s.id}`}
                            method="post"
                            onSubmit={(e) => {
                              if (!confirm(`Repair ${s.name} for ${s.repair_cost_formatted}?`)) {
                                e.preventDefault();
                              }
                            }}
                          >
                            <CsrfInput />
                            <Button
                              type="submit"
                              size="sm"
                              variant="outline"
                              isDisabled={!s.canAffordRepair}
                              className="min-h-11 sm:min-h-8 text-xs font-semibold gap-1 text-info hover:text-info"
                            >
                              <Icon name="fa-wrench" /> Repair ({s.repair_cost_formatted})
                            </Button>
                          </form>
                        )}

                        <form action={`/${category}/toggle/${s.id}`} method="post">
                          <CsrfInput />
                          <Button
                            type="submit"
                            size="sm"
                            variant={isActive ? 'secondary' : 'primary'}
                            isDisabled={isBroken}
                            className="min-h-11 sm:min-h-8 text-xs font-semibold"
                          >
                            {isActive ? 'Turn Off' : 'Turn On'}
                          </Button>
                        </form>
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          )}
        </Panel>

        {/* Build New Sources */}
        <Panel
          title={
            <span className="flex items-center gap-2">
              <Icon name="fa-hammer" className="text-warning" />
              Build New {isEnergy ? 'Energy' : 'Water'} Source
            </span>
          }
        >
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            {availableSources.map((avail) => {
              const iconName = avail.icon.startsWith('fa-') ? avail.icon : `fa-${avail.icon}`;
              const customName = buildNames[avail.type] || '';

              return (
                <div
                  key={avail.type}
                  className="rounded-xl border border-border bg-base-200/30 p-3.5 flex flex-col justify-between"
                >
                  <div>
                    <div className="flex items-center justify-between gap-2 mb-2">
                      <div className="flex items-center gap-2 min-w-0">
                        <div className="w-8 h-8 rounded-lg bg-base-100 flex items-center justify-center shrink-0">
                          <Icon name={iconName} className="text-primary text-sm" />
                        </div>
                        <h4 className="font-bold text-xs text-foreground truncate">
                          {avail.label}
                        </h4>
                      </div>

                      {avail.limit !== null && (
                        <span className="text-[10px] text-muted font-medium shrink-0">
                          {avail.owned}/{avail.limit} built
                        </span>
                      )}
                    </div>

                    <div className="space-y-1 text-xs text-muted mb-3">
                      <div>
                        Build Cost: <strong className="text-warning">{avail.costFormatted}</strong>
                      </div>
                      <div>
                        Output: <strong className="text-success">{avail.outputFormatted}/day</strong>
                      </div>
                      <div>
                        Upkeep: <strong className="text-foreground">{avail.upkeepFormatted}/day</strong>
                      </div>
                      <div>
                        Build Time:{' '}
                        <strong className="text-foreground">{avail.build_days} days</strong>
                      </div>
                    </div>
                  </div>

                  <form action={`/${category}/build`} method="post" className="space-y-2 pt-2 border-t border-border/40">
                    <CsrfInput />
                    <input type="hidden" name="source_type" value={avail.type} />
                    <input
                      type="text"
                      name="name"
                      aria-label="Source custom name"
                      value={customName}
                      onChange={(e) => handleNameChange(avail.type, e.target.value)}
                      placeholder={avail.label}
                      maxLength={60}
                      className="w-full min-h-11 sm:min-h-9 px-3 rounded-lg border border-border bg-base-100 text-base sm:text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <Button
                      type="submit"
                      variant="primary"
                      size="sm"
                      className="w-full min-h-11 sm:min-h-[36px] font-semibold text-xs"
                      isDisabled={!avail.canAfford || avail.atLimit}
                    >
                      {avail.atLimit
                        ? 'Limit Reached'
                        : !avail.canAfford
                        ? 'Cannot Afford'
                        : `Build ${avail.costFormatted}`}
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
